<?php

namespace Webkul\Shop\Transformers;

use App\Enums\PricingTrigger;
use App\Models\AliExpressSetting;
use App\Models\HigestPricingRule;
use App\Services\Pricing\PriceRecalculationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Webkul\Product\Contracts\Product;
use Webkul\Product\Helpers\Review as ReviewHelper;
use Webkul\Product\Helpers\View as ProductViewHelper;

class ProductPDPTransformer
{
    /**
     * Create a new transformer instance.
     *
     * @return void
     */
    public function __construct(
        protected ReviewHelper $reviewHelper,
        protected ProductViewHelper $productViewHelper
    ) {}

    /**
     * Transform Eloquent product model into a structured PDP ViewModel payload.
     *
     * @param  Product  $product
     */
    public function transform($product): array
    {
        if (! $product) {
            return [];
        }

        // Instant on-demand price synchronization for dropshipping products when pricing rules/shipping options updated
        if ($product->sku && str_starts_with($product->sku, 'ae-')) {
            try {
                $setting = AliExpressSetting::first();
                $lastRuleUpdate = HigestPricingRule::max('updated_at');
                $lastConfigTimestamp = max(
                    $setting?->updated_at?->timestamp ?? 0,
                    $lastRuleUpdate ? Carbon::parse($lastRuleUpdate)->timestamp : 0
                );

                if ($lastConfigTimestamp > 0 && (! $product->updated_at || $product->updated_at->timestamp < $lastConfigTimestamp)) {
                    app(PriceRecalculationService::class)->recalculateOne($product->id, PricingTrigger::MANUAL);
                    $product->refresh();
                }
            } catch (\Throwable $e) {
                // non-blocking fallback
            }
        }

        $typeInstance = $product->getTypeInstance();
        $baseImage = product_image()->getProductBaseImage($product);
        $galleryImages = product_image()->getGalleryImages($product);
        $videos = product_video()->getVideos($product);

        $avgRatings = $this->reviewHelper->getAverageRating($product);
        $totalRatings = $this->reviewHelper->getTotalFeedback($product);
        $percentageRatings = $this->reviewHelper->getPercentageRating($product);

        $customAttributeValues = $this->productViewHelper->getAdditionalData($product);
        $dropshipping = $this->productViewHelper->getDropshippingMetadata($product);

        $totalQty = (int) $typeInstance->totalQuantity();
        if ($totalQty <= 0) {
            if ($product->type === 'simple') {
                $totalQty = (int) $product->inventories()->sum('qty');
            } elseif ($product->type === 'configurable') {
                foreach ($product->variants as $variant) {
                    $totalQty += (int) $variant->inventories()->sum('qty');
                }
            }
        }

        $isSaleable = (bool) $product->isSaleable(1);

        // Resolve Expected Shipping Duration from product settings / import
        $aeImport = \App\Models\AliExpressProductImport::where('product_id', $product->id)
            ->orWhere('sku', $product->sku)
            ->orWhere('aliexpress_product_id', $product->sku)
            ->orWhere(function ($q) use ($product) {
                if (! empty($product->parent_id)) {
                    $q->where('product_id', $product->parent_id);
                }
            })
            ->first();

        $shippingDaysText = null;
        $shippingMinDays = null;
        $shippingMaxDays = null;

        if ($aeImport && ($aeImport->shipping_min_days !== null || $aeImport->shipping_max_days !== null)) {
            $aeSettings = \App\Models\AliExpressSetting::first();
            $extraDays = (int) ($aeSettings->shipping_extra_days ?? 0);
            $shippingMinDays = $aeImport->shipping_min_days !== null ? ((int) $aeImport->shipping_min_days + $extraDays) : null;
            $shippingMaxDays = $aeImport->shipping_max_days !== null ? ((int) $aeImport->shipping_max_days + $extraDays) : null;

            if ($shippingMinDays && $shippingMaxDays && $shippingMinDays !== $shippingMaxDays) {
                $shippingDaysText = "{$shippingMinDays} - {$shippingMaxDays} يوم";
            } elseif ($shippingMaxDays ?: $shippingMinDays) {
                $shippingDaysText = ($shippingMaxDays ?: $shippingMinDays) . ' يوم';
            }
        } elseif (! empty($product->shipping_days) || ! empty($product->delivery_days) || ! empty($product->shipping_time)) {
            $val = $product->shipping_days ?: ($product->delivery_days ?: $product->shipping_time);
            $shippingDaysText = is_numeric($val) ? "{$val} يوم" : (string) $val;
        }

        // Resolve Return Policy (if defined for product)
        $allowRmaAttrId = once(fn () => \Illuminate\Support\Facades\DB::table('attributes')->where('code', 'allow_rma')->value('id'));
        $rmaRuleAttrId = once(fn () => \Illuminate\Support\Facades\DB::table('attributes')->where('code', 'rma_rule_id')->value('id'));
        $prodIds = array_filter([$product->id, $product->parent_id]);

        $allowRma = false;
        if ($allowRmaAttrId) {
            $allowRma = (bool) \Illuminate\Support\Facades\DB::table('product_attribute_values')
                ->whereIn('product_id', $prodIds)
                ->where('attribute_id', $allowRmaAttrId)
                ->value('boolean_value');
        }

        $returnDays = null;
        $returnDaysText = null;
        if ($allowRma) {
            if ($rmaRuleAttrId) {
                $returnDays = \Illuminate\Support\Facades\DB::table('product_attribute_values')
                    ->whereIn('product_attribute_values.product_id', $prodIds)
                    ->where('product_attribute_values.attribute_id', $rmaRuleAttrId)
                    ->join('rma_rules', 'product_attribute_values.integer_value', '=', 'rma_rules.id')
                    ->where('rma_rules.status', 1)
                    ->value('rma_rules.return_period');
            }

            if ($returnDays === null || $returnDays === '') {
                $defaultRmaDays = core()->getConfigData('sales.rma.setting.default_allow_days');
                if ($defaultRmaDays) {
                    $returnDays = (int) $defaultRmaDays;
                }
            } else {
                $returnDays = (int) $returnDays;
            }

            if ($returnDays && $returnDays > 0) {
                if ($returnDays == 1) {
                    $returnDaysText = 'خلال يوم واحد';
                } elseif ($returnDays == 2) {
                    $returnDaysText = 'خلال يومين';
                } elseif ($returnDays >= 3 && $returnDays <= 10) {
                    $returnDaysText = "خلال {$returnDays} أيام";
                } else {
                    $returnDaysText = "خلال {$returnDays} يوم";
                }
            }
        }

        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'type' => $product->type,
            'name' => $product->name,
            'url_key' => $product->url_key,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'meta_title' => trim((string) $product->meta_title) !== '' ? $product->meta_title : $product->name,
            'meta_description' => trim((string) $product->meta_description) !== ''
                ? $product->meta_description
                : Str::limit(strip_tags((string) $product->description), 120, ''),
            'meta_keywords' => $product->meta_keywords,
            'is_saleable' => $isSaleable,
            'total_qty' => $totalQty,
            'in_stock' => $isSaleable && ($totalQty > 0 || ! $typeInstance->showQuantityBox()),
            'price_html' => $typeInstance->getPriceHtml(),
            'minimal_price' => $typeInstance->getMinimalPrice(),
            'base_image' => $baseImage,
            'gallery_images' => $galleryImages,
            'videos' => $videos,
            'ratings' => [
                'average' => $avgRatings,
                'total' => $totalRatings,
                'percentages' => $percentageRatings,
            ],
            'custom_attributes' => $customAttributeValues,
            'dropshipping' => $dropshipping,
            'shipping_estimation' => [
                'min_days' => $shippingMinDays,
                'max_days' => $shippingMaxDays,
                'text'     => $shippingDaysText,
            ],
            'return_policy' => [
                'allowed'  => (bool) $allowRma,
                'days'     => $returnDays,
                'text'     => $returnDaysText,
            ],
            'model' => $product,
        ];
    }
}
