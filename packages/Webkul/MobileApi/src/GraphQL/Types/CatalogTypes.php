<?php

namespace Webkul\MobileApi\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Helpers\ConfigurableOption;

class CatalogTypes
{
    public static function categoryTranslation(): ObjectType
    {
        return TypeRegistry::get('CategoryTranslation', fn () => new ObjectType([
            'name' => 'CategoryTranslation',
            'fields' => [
                'id' => Type::id(),
                'name' => Type::string(),
                'slug' => Type::string(),
                'description' => Type::string(),
                'urlPath' => Type::string(),
                'metaTitle' => Type::string(),
            ],
        ]));
    }

    public static function categoryTree(): ObjectType
    {
        static $type = null;

        if ($type) {
            return $type;
        }

        return $type = new ObjectType([
            'name' => 'CategoryTree',
            'fields' => fn () => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($c) => $c->id ?? ($c['id'] ?? null)],
                'name' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->name ?? ($c->translation->name ?? '')],
                'slug' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->slug ?? ($c->translation->slug ?? '')],
                'urlPath' => ['type' => Type::string(), 'resolve' => fn ($c) => $c->url_path ?? ($c->translation->url_path ?? '')],
                'position' => Type::int(),
                'logoPath' => Type::string(),
                'logoUrl' => ['type' => Type::string(), 'resolve' => fn ($c) => method_exists($c, 'logo_url') ? $c->logo_url() : ($c->logo_path ? asset('storage/'.$c->logo_path) : null)],
                'bannerUrl' => ['type' => Type::string(), 'resolve' => fn ($c) => method_exists($c, 'banner_url') ? $c->banner_url() : ($c->banner_path ? asset('storage/'.$c->banner_path) : null)],
                'status' => Type::int(),
                'translation' => [
                    'type' => self::categoryTranslation(),
                    'resolve' => function ($c) {
                        $trans = method_exists($c, 'translate') ? $c->translate(app()->getLocale()) : ($c->translation ?? null);

                        return $trans ?: $c;
                    },
                ],
                'children' => [
                    'type' => TypeRegistry::connection('CategoryTreeChild', self::categoryTree()),
                    'resolve' => function ($c) {
                        $children = $c->children ?? [];
                        $edges = collect($children)->map(fn ($ch) => ['node' => $ch, 'cursor' => (string) $ch->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]);
    }

    public static function productImage(): ObjectType
    {
        return TypeRegistry::get('ProductImage', fn () => new ObjectType([
            'name' => 'ProductImage',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($i) => $i->id ?? ($i['id'] ?? null)],
                'path' => Type::string(),
                'publicPath' => ['type' => Type::string(), 'resolve' => fn ($i) => $i->url ?? (method_exists($i, 'url') ? $i->url() : asset('storage/'.($i->path ?? '')))],
                'type' => Type::string(),
                'position' => ['type' => Type::string(), 'resolve' => fn ($i) => (string) ($i->position ?? '')],
            ],
        ]));
    }

    public static function productReview(): ObjectType
    {
        return TypeRegistry::get('ProductReview', fn () => new ObjectType([
            'name' => 'ProductReview',
            'fields' => [
                'id' => Type::id(),
                'rating' => Type::int(),
                'name' => Type::string(),
                'title' => Type::string(),
                'comment' => Type::string(),
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($r) => (string) ($r->created_at ?? '')],
            ],
        ]));
    }

    public static function product(): ObjectType
    {
        static $type = null;

        if ($type) {
            return $type;
        }

        return $type = new ObjectType([
            'name' => 'Product',
            'fields' => fn () => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($p) => $p->id],
                'sku' => Type::string(),
                'type' => Type::string(),
                'name' => Type::string(),
                'urlKey' => ['type' => Type::string(), 'resolve' => fn ($p) => $p->url_key],
                'description' => Type::string(),
                'shortDescription' => ['type' => Type::string(), 'resolve' => fn ($p) => $p->short_description],
                'price' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) round(core()->convertPrice($p->price ?? 0), 2)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => core()->currency($p->price ?? 0)],
                'baseImageUrl' => ['type' => Type::string(), 'resolve' => fn ($p) => product_image()->getProductBaseImage($p)['medium_image_url'] ?? null],
                'minimumPrice' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) round(core()->convertPrice($p->getTypeInstance()->getMinimalPrice() ?? $p->price ?? 0), 2)],
                'formattedMinimumPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => core()->currency($p->getTypeInstance()->getMinimalPrice() ?? $p->price ?? 0)],
                'specialPrice' => ['type' => Type::float(), 'resolve' => fn ($p) => $p->special_price ? (float) round(core()->convertPrice($p->special_price), 2) : null],
                'formattedSpecialPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => $p->special_price ? core()->currency($p->special_price) : null],
                'maximumPrice' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) round(core()->convertPrice($p->getTypeInstance()->getMaximumPrice() ?? $p->price ?? 0), 2)],
                'formattedMaximumPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => core()->currency($p->getTypeInstance()->getMaximumPrice() ?? $p->price ?? 0)],
                'regularMinimumPrice' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) round(core()->convertPrice($p->price ?? 0), 2)],
                'regularMaximumPrice' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) round(core()->convertPrice($p->price ?? 0), 2)],
                'formattedRegularMinimumPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => core()->currency($p->price ?? 0)],
                'formattedRegularMaximumPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => core()->currency($p->price ?? 0)],
                'isSaleable' => ['type' => Type::boolean(), 'resolve' => fn ($p) => (bool) $p->getTypeInstance()->isSaleable()],
                'guestCheckout' => ['type' => Type::boolean(), 'resolve' => fn () => true],
                'color' => ['type' => Type::string(), 'resolve' => fn ($p) => $p->color ?? null],
                'size' => ['type' => Type::string(), 'resolve' => fn ($p) => $p->size ?? null],
                'brand' => ['type' => Type::string(), 'resolve' => fn ($p) => $p->brand ?? null],
                'attributeFamilyId' => ['type' => Type::id(), 'resolve' => fn ($p) => $p->attribute_family_id ?? null],
                'parentId' => ['type' => Type::id(), 'resolve' => fn ($p) => $p->parent_id ?? null],
                'new' => ['type' => Type::boolean(), 'resolve' => fn ($p) => (bool) ($p->new ?? false)],
                'featured' => ['type' => Type::boolean(), 'resolve' => fn ($p) => (bool) ($p->featured ?? false)],
                'status' => ['type' => Type::boolean(), 'resolve' => fn ($p) => (bool) ($p->status ?? true)],
                'hasVariants' => ['type' => Type::boolean(), 'resolve' => fn ($p) => method_exists($p->getTypeInstance(), 'hasVariants') ? (bool) $p->getTypeInstance()->hasVariants() : false],
                'specialPriceFrom' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->special_price_from ?? '')],
                'specialPriceTo' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->special_price_to ?? '')],
                'weight' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) ($p->weight ?? 0)],
                'metaTitle' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->meta_title ?? '')],
                'metaKeywords' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->meta_keywords ?? '')],
                'metaDescription' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->meta_description ?? '')],
                'locale' => ['type' => Type::string(), 'resolve' => fn () => app()->getLocale()],
                'channel' => ['type' => Type::string(), 'resolve' => fn () => core()->getCurrentChannelCode()],
                'isSaved' => ['type' => Type::boolean(), 'resolve' => fn () => false],
                'images' => [
                    'type' => TypeRegistry::connection('ProductImage', self::productImage()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $images = $p->images ?? [];
                        if (isset($args['first'])) {
                            $images = collect($images)->take($args['first']);
                        }
                        $edges = collect($images)->map(fn ($img) => ['node' => $img, 'cursor' => (string) $img->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'videos' => [
                    'type' => TypeRegistry::connection('ProductVideo', self::productImage()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $videos = $p->videos ?? [];
                        if (isset($args['first'])) {
                            $videos = collect($videos)->take($args['first']);
                        }
                        $edges = collect($videos)->map(fn ($v) => ['node' => $v, 'cursor' => (string) $v->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'reviews' => [
                    'type' => TypeRegistry::connection('ProductReview', self::productReview()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $reviews = $p->reviews ?? [];
                        if (isset($args['first'])) {
                            $reviews = collect($reviews)->take($args['first']);
                        }
                        $edges = collect($reviews)->map(fn ($r) => ['node' => $r, 'cursor' => (string) $r->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'variants' => [
                    'type' => TypeRegistry::connection('ProductVariant', self::product()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $variants = $p->variants ?? [];
                        if (isset($args['first'])) {
                            $variants = collect($variants)->take($args['first']);
                        }
                        $edges = collect($variants)->map(fn ($v) => ['node' => $v, 'cursor' => (string) $v->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'relatedProducts' => [
                    'type' => TypeRegistry::connection('RelatedProduct', self::product()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $related = method_exists($p, 'related_products') ? $p->related_products : [];
                        if (isset($args['first'])) {
                            $related = collect($related)->take($args['first']);
                        }
                        $edges = collect($related)->map(fn ($r) => ['node' => $r, 'cursor' => (string) $r->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'categories' => [
                    'type' => TypeRegistry::connection('ProductCategory', self::categoryTree()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $categories = method_exists($p, 'categories') ? $p->categories : [];
                        if (isset($args['first'])) {
                            $categories = collect($categories)->take($args['first']);
                        }
                        $edges = collect($categories)->map(fn ($c) => ['node' => $c, 'cursor' => (string) $c->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'customizableOptions' => [
                    'type' => TypeRegistry::connection('ProductCustomizableOption', self::customizableOption()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $options = method_exists($p, 'customizable_options') ? ($p->customizable_options ?? []) : [];
                        if (isset($args['first'])) {
                            $options = collect($options)->take($args['first']);
                        }
                        $edges = collect($options)->map(fn ($o) => ['node' => $o, 'cursor' => (string) $o->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'downloadableLinks' => [
                    'type' => TypeRegistry::connection('ProductDownloadableLink', self::downloadableLink()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $links = method_exists($p, 'downloadable_links') ? ($p->downloadable_links ?? []) : [];
                        if (isset($args['first'])) {
                            $links = collect($links)->take($args['first']);
                        }
                        $edges = collect($links)->map(fn ($l) => ['node' => $l, 'cursor' => (string) $l->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'downloadableSamples' => [
                    'type' => TypeRegistry::connection('ProductDownloadableSample', self::downloadableSample()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $samples = method_exists($p, 'downloadable_samples') ? ($p->downloadable_samples ?? []) : [];
                        if (isset($args['first'])) {
                            $samples = collect($samples)->take($args['first']);
                        }
                        $edges = collect($samples)->map(fn ($s) => ['node' => $s, 'cursor' => (string) $s->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'groupedProducts' => [
                    'type' => TypeRegistry::connection('ProductGroupedProduct', self::groupedProduct()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $grouped = method_exists($p, 'grouped_products') ? ($p->grouped_products ?? []) : [];
                        if (isset($args['first'])) {
                            $grouped = collect($grouped)->take($args['first']);
                        }
                        $edges = collect($grouped)->map(fn ($g) => ['node' => $g, 'cursor' => (string) $g->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'bundleOptions' => [
                    'type' => TypeRegistry::connection('ProductBundleOption', self::bundleOption()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $bundles = method_exists($p, 'bundle_options') ? ($p->bundle_options ?? []) : [];
                        if (isset($args['first'])) {
                            $bundles = collect($bundles)->take($args['first']);
                        }
                        $edges = collect($bundles)->map(fn ($b) => ['node' => $b, 'cursor' => (string) $b->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'bookingProducts' => [
                    'type' => TypeRegistry::connection('ProductBookingProduct', self::bookingProduct()),
                    'args' => [
                        'first' => Type::int(),
                    ],
                    'resolve' => function ($p, $args = []) {
                        $bookings = method_exists($p, 'booking_products') ? ($p->booking_products ?? []) : [];
                        if (isset($args['first'])) {
                            $bookings = collect($bookings)->take($args['first']);
                        }
                        $edges = collect($bookings)->map(fn ($b) => ['node' => $b, 'cursor' => (string) $b->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
                'superAttributeOptions' => [
                    'type' => TypeRegistry::json(),
                    'resolve' => function ($p) {
                        if ($p->type !== 'configurable') {
                            return null;
                        }

                        $helper = app(ConfigurableOption::class);
                        $allowedVariants = $helper->getAllowedVariants($p);
                        $options = $helper->getOptions($p, $allowedVariants);
                        $attributesData = $helper->getAttributesData($p, $options);

                        $variantImages = [];
                        foreach ($allowedVariants as $variant) {
                            $gallery = ProductImage::getGalleryImages($variant);
                            if (! empty($gallery)) {
                                $variantImages[$variant->id] = $gallery[0]['small_image_url']
                                    ?? $gallery[0]['medium_image_url']
                                    ?? $gallery[0]['large_image_url']
                                    ?? null;
                            }
                        }

                        foreach ($attributesData as &$attr) {
                            if (! empty($attr['options'])) {
                                foreach ($attr['options'] as &$opt) {
                                    $opt['image_url'] = null;
                                    if (! empty($opt['swatch_value']) && (str_starts_with($opt['swatch_value'], 'http') || str_contains($opt['swatch_value'], 'storage'))) {
                                        $opt['image_url'] = $opt['swatch_value'];
                                    } elseif (! empty($opt['products'])) {
                                        foreach ($opt['products'] as $pId) {
                                            if (isset($variantImages[$pId])) {
                                                $opt['image_url'] = $variantImages[$pId];
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        return $attributesData;
                    },
                ],
                'combinations' => [
                    'type' => TypeRegistry::json(),
                    'resolve' => function ($p) {
                        if ($p->type !== 'configurable') {
                            return null;
                        }

                        $helper = app(ConfigurableOption::class);
                        $allowedVariants = $helper->getAllowedVariants($p);
                        $allowAttributes = $helper->getAllowAttributes($p);

                        $combinations = [];
                        foreach ($allowedVariants as $variant) {
                            $variantAttrs = [];
                            foreach ($allowAttributes as $attr) {
                                $val = $variant->{$attr->code};
                                if ($val !== null) {
                                    $variantAttrs[$attr->code] = is_numeric($val) ? (int) $val : $val;
                                }
                            }
                            $combinations[(string) $variant->id] = $variantAttrs;
                        }

                        return $combinations;
                    },
                ],
            ],
        ]);
    }

    public static function cmsPageTranslation(): ObjectType
    {
        return TypeRegistry::get('CmsPageTranslation', fn () => new ObjectType([
            'name' => 'CmsPageTranslation',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($t) => (int) ($t->id ?? 0)],
                'pageTitle' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->page_title ?? $t->title ?? '')],
                'urlKey' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->url_key ?? '')],
                'htmlContent' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->html_content ?? $t->content ?? '')],
                'metaTitle' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->meta_title ?? '')],
                'metaDescription' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->meta_description ?? '')],
                'metaKeywords' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->meta_keywords ?? '')],
                'locale' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->locale ?? '')],
            ],
        ]));
    }

    public static function cmsPage(): ObjectType
    {
        return TypeRegistry::get('CmsPage', fn () => new ObjectType([
            'name' => 'CmsPage',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::int(), 'resolve' => fn ($p) => (int) ($p->id ?? 0)],
                'layout' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->layout ?? 'default')],
                'createdAt' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->created_at ?? '')],
                'updatedAt' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->updated_at ?? '')],
                'translation' => [
                    'type' => self::cmsPageTranslation(),
                    'resolve' => function ($p) {
                        $trans = method_exists($p, 'translate') ? $p->translate(app()->getLocale()) : ($p->translation ?? null);

                        return $trans ?: ($p->translations->first() ?? $p);
                    },
                ],
            ],
        ]));
    }

    public static function customizableOptionPrice(): ObjectType
    {
        return TypeRegistry::get('CustomizableOptionPrice', fn () => new ObjectType([
            'name' => 'CustomizableOptionPrice',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($p) => $p->id ?? null],
                'label' => ['type' => Type::string(), 'resolve' => fn ($p) => (string) ($p->label ?? '')],
                'price' => ['type' => Type::float(), 'resolve' => fn ($p) => (float) round(core()->convertPrice($p->price ?? 0), 2)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($p) => core()->currency($p->price ?? 0)],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($p) => (int) ($p->sort_order ?? 0)],
            ],
        ]));
    }

    public static function customizableOptionTranslation(): ObjectType
    {
        return TypeRegistry::get('CustomizableOptionTranslation', fn () => new ObjectType([
            'name' => 'CustomizableOptionTranslation',
            'fields' => [
                'id' => Type::id(),
                'locale' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->locale ?? '')],
                'label' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->label ?? '')],
            ],
        ]));
    }

    public static function customizableOption(): ObjectType
    {
        return TypeRegistry::get('CustomizableOption', fn () => new ObjectType([
            'name' => 'CustomizableOption',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($o) => $o->id ?? null],
                'type' => Type::string(),
                'isRequired' => ['type' => Type::boolean(), 'resolve' => fn ($o) => (bool) ($o->is_required ?? false)],
                'maxCharacters' => ['type' => Type::int(), 'resolve' => fn ($o) => $o->max_characters ? (int) $o->max_characters : null],
                'supportedFileExtensions' => ['type' => Type::string(), 'resolve' => fn ($o) => (string) ($o->supported_file_extensions ?? '')],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($o) => (int) ($o->sort_order ?? 0)],
                'translation' => [
                    'type' => self::customizableOptionTranslation(),
                    'resolve' => function ($o) {
                        $trans = method_exists($o, 'translate') ? $o->translate(app()->getLocale()) : ($o->translation ?? null);

                        return $trans ?: ($o->translations->first() ?? $o);
                    },
                ],
                'customizableOptionPrices' => [
                    'type' => TypeRegistry::connection('CustomizableOptionPrice', self::customizableOptionPrice()),
                    'resolve' => function ($o) {
                        $prices = method_exists($o, 'customizable_option_prices') ? ($o->customizable_option_prices ?? []) : [];
                        $edges = collect($prices)->map(fn ($p) => ['node' => $p, 'cursor' => (string) $p->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function downloadableLinkTranslation(): ObjectType
    {
        return TypeRegistry::get('DownloadableLinkTranslation', fn () => new ObjectType([
            'name' => 'DownloadableLinkTranslation',
            'fields' => [
                'title' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->title ?? '')],
            ],
        ]));
    }

    public static function downloadableLink(): ObjectType
    {
        return TypeRegistry::get('DownloadableLink', fn () => new ObjectType([
            'name' => 'DownloadableLink',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($l) => $l->id ?? null],
                'type' => Type::string(),
                'price' => ['type' => Type::float(), 'resolve' => fn ($l) => (float) round(core()->convertPrice($l->price ?? 0), 2)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($l) => core()->currency($l->price ?? 0)],
                'downloads' => ['type' => Type::int(), 'resolve' => fn ($l) => (int) ($l->downloads ?? 0)],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($l) => (int) ($l->sort_order ?? 0)],
                'fileUrl' => ['type' => Type::string(), 'resolve' => fn ($l) => method_exists($l, 'file_url') ? $l->file_url() : null],
                'sampleFileUrl' => ['type' => Type::string(), 'resolve' => fn ($l) => $l->sample_url ?? ($l->sample_file ? asset('storage/'.$l->sample_file) : null)],
                'translation' => [
                    'type' => self::downloadableLinkTranslation(),
                    'resolve' => function ($l) {
                        $trans = method_exists($l, 'translate') ? $l->translate(app()->getLocale()) : ($l->translation ?? null);

                        return $trans ?: ($l->translations->first() ?? $l);
                    },
                ],
            ],
        ]));
    }

    public static function downloadableSample(): ObjectType
    {
        return TypeRegistry::get('DownloadableSample', fn () => new ObjectType([
            'name' => 'DownloadableSample',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->id ?? null],
                'type' => Type::string(),
                'fileUrl' => ['type' => Type::string(), 'resolve' => fn ($s) => method_exists($s, 'file_url') ? $s->file_url() : null],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->sort_order ?? 0)],
                'translation' => [
                    'type' => self::downloadableLinkTranslation(),
                    'resolve' => function ($s) {
                        $trans = method_exists($s, 'translate') ? $s->translate(app()->getLocale()) : ($s->translation ?? null);

                        return $trans ?: ($s->translations->first() ?? $s);
                    },
                ],
            ],
        ]));
    }

    public static function groupedProduct(): ObjectType
    {
        return TypeRegistry::get('GroupedProduct', fn () => new ObjectType([
            'name' => 'GroupedProduct',
            'fields' => fn () => [
                'id' => Type::id(),
                'qty' => ['type' => Type::int(), 'resolve' => fn ($g) => (int) ($g->qty ?? 0)],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($g) => (int) ($g->sort_order ?? 0)],
                'associatedProduct' => [
                    'type' => self::product(),
                    'resolve' => fn ($g) => $g->associated_product ?? null,
                ],
            ],
        ]));
    }

    public static function bundleOptionTranslation(): ObjectType
    {
        return TypeRegistry::get('BundleOptionTranslation', fn () => new ObjectType([
            'name' => 'BundleOptionTranslation',
            'fields' => [
                'label' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->label ?? '')],
            ],
        ]));
    }

    public static function bundleOptionProduct(): ObjectType
    {
        return TypeRegistry::get('BundleOptionProduct', fn () => new ObjectType([
            'name' => 'BundleOptionProduct',
            'fields' => fn () => [
                'id' => Type::id(),
                'qty' => ['type' => Type::int(), 'resolve' => fn ($b) => (int) ($b->qty ?? 0)],
                'isDefault' => ['type' => Type::boolean(), 'resolve' => fn ($b) => (bool) ($b->is_default ?? false)],
                'isUserDefined' => ['type' => Type::boolean(), 'resolve' => fn ($b) => (bool) ($b->is_user_defined ?? false)],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($b) => (int) ($b->sort_order ?? 0)],
                'product' => [
                    'type' => self::product(),
                    'resolve' => fn ($b) => $b->product ?? null,
                ],
            ],
        ]));
    }

    public static function bundleOption(): ObjectType
    {
        return TypeRegistry::get('BundleOption', fn () => new ObjectType([
            'name' => 'BundleOption',
            'fields' => fn () => [
                'id' => Type::id(),
                'type' => Type::string(),
                'isRequired' => ['type' => Type::boolean(), 'resolve' => fn ($b) => (bool) ($b->is_required ?? false)],
                'sortOrder' => ['type' => Type::int(), 'resolve' => fn ($b) => (int) ($b->sort_order ?? 0)],
                'translation' => [
                    'type' => self::bundleOptionTranslation(),
                    'resolve' => function ($b) {
                        $trans = method_exists($b, 'translate') ? $b->translate(app()->getLocale()) : ($b->translation ?? null);

                        return $trans ?: ($b->translations->first() ?? $b);
                    },
                ],
                'bundleOptionProducts' => [
                    'type' => TypeRegistry::connection('BundleOptionProduct', self::bundleOptionProduct()),
                    'resolve' => function ($b) {
                        $products = method_exists($b, 'bundle_option_products') ? ($b->bundle_option_products ?? []) : [];
                        $edges = collect($products)->map(fn ($p) => ['node' => $p, 'cursor' => (string) $p->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }

    public static function bookingDefaultSlot(): ObjectType
    {
        return TypeRegistry::get('BookingDefaultSlot', fn () => new ObjectType([
            'name' => 'BookingDefaultSlot',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->id ?? null],
                'bookingType' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->booking_type ?? '')],
                'duration' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->duration ?? 0)],
                'breakTime' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->break_time ?? 0)],
                'slots' => ['type' => Type::string(), 'resolve' => fn ($s) => is_array($s->slots ?? null) ? json_encode($s->slots) : (string) ($s->slots ?? '')],
            ],
        ]));
    }

    public static function bookingAppointmentSlot(): ObjectType
    {
        return TypeRegistry::get('BookingAppointmentSlot', fn () => new ObjectType([
            'name' => 'BookingAppointmentSlot',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->id ?? null],
                'bookingProductId' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->booking_product_id ?? null],
                'duration' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->duration ?? 0)],
                'breakTime' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->break_time ?? 0)],
                'sameSlotAllDays' => ['type' => Type::boolean(), 'resolve' => fn ($s) => (bool) ($s->same_slot_all_days ?? false)],
                'slots' => ['type' => Type::string(), 'resolve' => fn ($s) => is_array($s->slots ?? null) ? json_encode($s->slots) : (string) ($s->slots ?? '')],
            ],
        ]));
    }

    public static function bookingRentalSlot(): ObjectType
    {
        return TypeRegistry::get('BookingRentalSlot', fn () => new ObjectType([
            'name' => 'BookingRentalSlot',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->id ?? null],
                'bookingProductId' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->booking_product_id ?? null],
                'rentingType' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->renting_type ?? '')],
                'dailyPrice' => ['type' => Type::float(), 'resolve' => fn ($s) => (float) ($s->daily_price ?? 0)],
                'hourlyPrice' => ['type' => Type::float(), 'resolve' => fn ($s) => (float) ($s->hourly_price ?? 0)],
                'sameSlotAllDays' => ['type' => Type::boolean(), 'resolve' => fn ($s) => (bool) ($s->same_slot_all_days ?? false)],
                'slots' => ['type' => Type::string(), 'resolve' => fn ($s) => is_array($s->slots ?? null) ? json_encode($s->slots) : (string) ($s->slots ?? '')],
            ],
        ]));
    }

    public static function bookingTableSlot(): ObjectType
    {
        return TypeRegistry::get('BookingTableSlot', fn () => new ObjectType([
            'name' => 'BookingTableSlot',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->id ?? null],
                'bookingProductId' => ['type' => Type::id(), 'resolve' => fn ($s) => $s->booking_product_id ?? null],
                'priceType' => ['type' => Type::string(), 'resolve' => fn ($s) => (string) ($s->price_type ?? '')],
                'guestLimit' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->guest_limit ?? 0)],
                'duration' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->duration ?? 0)],
                'breakTime' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->break_time ?? 0)],
                'preventSchedulingBefore' => ['type' => Type::int(), 'resolve' => fn ($s) => (int) ($s->prevent_scheduling_before ?? 0)],
                'sameSlotAllDays' => ['type' => Type::boolean(), 'resolve' => fn ($s) => (bool) ($s->same_slot_all_days ?? false)],
                'slots' => ['type' => Type::string(), 'resolve' => fn ($s) => is_array($s->slots ?? null) ? json_encode($s->slots) : (string) ($s->slots ?? '')],
            ],
        ]));
    }

    public static function bookingEventTicketTranslation(): ObjectType
    {
        return TypeRegistry::get('BookingEventTicketTranslation', fn () => new ObjectType([
            'name' => 'BookingEventTicketTranslation',
            'fields' => [
                'locale' => Type::string(),
                'name' => Type::string(),
                'description' => Type::string(),
            ],
        ]));
    }

    public static function bookingEventTicket(): ObjectType
    {
        return TypeRegistry::get('BookingEventTicket', fn () => new ObjectType([
            'name' => 'BookingEventTicket',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($t) => $t->id ?? null],
                'bookingProductId' => ['type' => Type::id(), 'resolve' => fn ($t) => $t->booking_product_id ?? null],
                'price' => ['type' => Type::float(), 'resolve' => fn ($t) => (float) round(core()->convertPrice($t->price ?? 0), 2)],
                'formattedPrice' => ['type' => Type::string(), 'resolve' => fn ($t) => core()->currency($t->price ?? 0)],
                'qty' => ['type' => Type::int(), 'resolve' => fn ($t) => (int) ($t->qty ?? 0)],
                'specialPrice' => ['type' => Type::float(), 'resolve' => fn ($t) => $t->special_price ? (float) round(core()->convertPrice($t->special_price), 2) : null],
                'formattedSpecialPrice' => ['type' => Type::string(), 'resolve' => fn ($t) => $t->special_price ? core()->currency($t->special_price) : null],
                'specialPriceFrom' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->special_price_from ?? '')],
                'specialPriceTo' => ['type' => Type::string(), 'resolve' => fn ($t) => (string) ($t->special_price_to ?? '')],
                'translation' => [
                    'type' => self::bookingEventTicketTranslation(),
                    'resolve' => function ($t) {
                        $trans = method_exists($t, 'translate') ? $t->translate(app()->getLocale()) : ($t->translation ?? null);

                        return $trans ?: ($t->translations->first() ?? $t);
                    },
                ],
            ],
        ]));
    }

    public static function bookingProduct(): ObjectType
    {
        return TypeRegistry::get('BookingProduct', fn () => new ObjectType([
            'name' => 'BookingProduct',
            'fields' => [
                'id' => Type::id(),
                '_id' => ['type' => Type::id(), 'resolve' => fn ($b) => $b->id ?? null],
                'type' => Type::string(),
                'qty' => ['type' => Type::int(), 'resolve' => fn ($b) => (int) ($b->qty ?? 0)],
                'location' => ['type' => Type::string(), 'resolve' => fn ($b) => (string) ($b->location ?? '')],
                'showLocation' => ['type' => Type::boolean(), 'resolve' => fn ($b) => (bool) ($b->show_location ?? false)],
                'availableFrom' => ['type' => Type::string(), 'resolve' => fn ($b) => (string) ($b->available_from ?? '')],
                'availableTo' => ['type' => Type::string(), 'resolve' => fn ($b) => (string) ($b->available_to ?? '')],
                'defaultSlot' => [
                    'type' => self::bookingDefaultSlot(),
                    'resolve' => fn ($b) => $b->default_slot ?? null,
                ],
                'appointmentSlot' => [
                    'type' => self::bookingAppointmentSlot(),
                    'resolve' => fn ($b) => $b->appointment_slot ?? null,
                ],
                'rentalSlot' => [
                    'type' => self::bookingRentalSlot(),
                    'resolve' => fn ($b) => $b->rental_slot ?? null,
                ],
                'tableSlot' => [
                    'type' => self::bookingTableSlot(),
                    'resolve' => fn ($b) => $b->table_slot ?? null,
                ],
                'eventTickets' => [
                    'type' => TypeRegistry::connection('BookingEventTicket', self::bookingEventTicket()),
                    'resolve' => function ($b) {
                        $tickets = method_exists($b, 'event_tickets') ? ($b->event_tickets ?? []) : [];
                        $edges = collect($tickets)->map(fn ($t) => ['node' => $t, 'cursor' => (string) $t->id])->all();

                        return ['edges' => $edges, 'totalCount' => count($edges)];
                    },
                ],
            ],
        ]));
    }
}
