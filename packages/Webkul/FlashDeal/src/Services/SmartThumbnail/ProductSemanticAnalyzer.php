<?php

namespace Webkul\FlashDeal\Services\SmartThumbnail;

use Webkul\Product\Contracts\Product;

class ProductSemanticAnalyzer
{
    /**
     * Semantic archetypes and their associated multilingual keywords.
     */
    protected array $archetypes = [
        'screen_display' => [
            'ar' => ['شاشة', 'تلفزيون', 'تلفاز', 'شاشه', 'مونيتور', 'بروجيكتور', 'ال سي دي', 'ليد', 'تلفزة', 'ديسبلاي'],
            'en' => ['screen', 'tv', 'television', 'monitor', 'display', 'projector', 'lcd', 'oled', 'qled', 'smart tv'],
        ],
        'dresses_tops' => [
            'ar' => ['فستان', 'فساتين', 'بلوزة', 'قميص', 'تيشيرت', 'جاكيت', 'معطف', 'تنورة', 'كنزة', 'سويتر', 'تونيك', 'جلابية', 'عباية'],
            'en' => ['dress', 'dresses', 'shirt', 't-shirt', 'blouse', 'top', 'jacket', 'coat', 'skirt', 'sweater', 'hoodie', 'gown', 'tunic', 'robe'],
        ],
        'footwear' => [
            'ar' => ['حذاء', 'أحذية', 'احذية', 'صندل', 'كعب', 'بوت', 'نعال', 'سنيكرز', 'جزمة', 'شوز'],
            'en' => ['shoe', 'shoes', 'boot', 'boots', 'sandal', 'sandals', 'heel', 'heels', 'sneaker', 'sneakers', 'slipper', 'slippers', 'footwear'],
        ],
        'accessories' => [
            'ar' => ['ساعة', 'سوار', 'خاتم', 'قلادة', 'مجوهرات', 'سلسال', 'أقراط', 'نظارة', 'نظارات', 'حقيبة', 'شنطة'],
            'en' => ['watch', 'bracelet', 'ring', 'necklace', 'jewelry', 'earring', 'sunglasses', 'glasses', 'bag', 'handbag', 'wallet'],
        ],
    ];

    /**
     * Analyze a product and determine its semantic archetype and cropping hints.
     */
    public function analyze(?Product $product): array
    {
        if (! $product) {
            return $this->defaultAnalysis();
        }

        $textToAnalyze = mb_strtolower(
            ($product->name ?? '').' '.
            ($product->parent?->name ?? '').' '.
            ($product->description ?? '').' '.
            ($product->short_description ?? '').' '.
            ($product->url_key ?? '')
        );

        foreach ($this->archetypes as $archetype => $languages) {
            foreach ($languages as $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strpos($textToAnalyze, mb_strtolower($keyword)) !== false) {
                        return $this->getHintsForArchetype($archetype);
                    }
                }
            }
        }

        return $this->defaultAnalysis();
    }

    /**
     * Get cropping hints and safe boundary adjustments for a specific archetype.
     */
    protected function getHintsForArchetype(string $archetype): array
    {
        return match ($archetype) {
            'screen_display' => [
                'archetype' => 'screen_display',
                'safe_trim_bottom_pct' => 0.30, // Safe to trim lower ~30% (stand pole/base)
                'safe_trim_top_pct' => 0.05,
                'focal_focus' => 'top',
                'allow_portrait_zoom' => true,
            ],
            'dresses_tops' => [
                'archetype' => 'dresses_tops',
                'safe_trim_bottom_pct' => 0.20, // Safe to trim lower ~20% (model legs/feet)
                'safe_trim_top_pct' => 0.03,
                'focal_focus' => 'upper_middle',
                'allow_portrait_zoom' => true,
            ],
            'footwear' => [
                'archetype' => 'footwear',
                'safe_trim_bottom_pct' => 0.02,
                'safe_trim_top_pct' => 0.25, // Safe to trim upper ~25% (legs/torso)
                'focal_focus' => 'bottom',
                'allow_portrait_zoom' => true,
            ],
            'accessories' => [
                'archetype' => 'accessories',
                'safe_trim_bottom_pct' => 0.05,
                'safe_trim_top_pct' => 0.05,
                'focal_focus' => 'center',
                'allow_portrait_zoom' => true,
            ],
            default => $this->defaultAnalysis(),
        };
    }

    /**
     * Default analysis for general products.
     */
    protected function defaultAnalysis(): array
    {
        return [
            'archetype' => 'general',
            'safe_trim_bottom_pct' => 0.0,
            'safe_trim_top_pct' => 0.0,
            'focal_focus' => 'center',
            'allow_portrait_zoom' => false,
        ];
    }

    protected ?array $storeVisualProfiles = null;

    /**
     * Get the learned store visual profile for a specific archetype.
     */
    public function getProfileForArchetype(string $archetype): array
    {
        if ($this->storeVisualProfiles === null) {
            $path = __DIR__.'/../../Config/store_visual_profiles.json';
            if (file_exists($path)) {
                $json = json_decode(file_get_contents($path), true);
                $this->storeVisualProfiles = $json['archetypes'] ?? [];
            } else {
                $this->storeVisualProfiles = [];
            }
        }

        return $this->storeVisualProfiles[$archetype] ?? $this->storeVisualProfiles['general'] ?? [
            'golden_subject_fill' => 0.826,
            'safe_margin_top' => 0.05,
            'safe_margin_bottom' => 0.05,
            'safe_margin_left' => 0.08,
            'safe_margin_right' => 0.08,
        ];
    }
}
