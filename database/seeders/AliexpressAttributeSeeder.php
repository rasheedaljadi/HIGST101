<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Core\Models\Locale;

/**
 * Seeder for AliExpress-style product attributes (السمات والخصائص).
 *
 * This seeder does two things:
 *   1. Enriches the existing default attributes (color, size, brand) with
 *      Arabic translations, Arabic option labels, and colour swatches.
 *   2. Creates new e-commerce attributes commonly used on AliExpress
 *      (material, storage capacity, RAM, country of origin, warranty) with
 *      full Arabic + English translations and options.
 *
 * It is idempotent: existing attributes/options are updated in place and new
 * ones are only created when missing.
 */
class AliexpressAttributeSeeder extends Seeder
{
    /**
     * Locales cache.
     */
    protected $locales;

    /**
     * Arabic translations + colour swatches for the existing default options.
     *
     * Keyed by the option admin_name already stored in the database.
     *
     * @var array<string, array<string, string>>
     */
    protected array $existingOptionEnrichment = [
        'Red' => ['ar' => 'أحمر', 'swatch' => '#e02b2b'],
        'Green' => ['ar' => 'أخضر', 'swatch' => '#1f9d55'],
        'Yellow' => ['ar' => 'أصفر', 'swatch' => '#f2c200'],
        'Black' => ['ar' => 'أسود', 'swatch' => '#000000'],
        'White' => ['ar' => 'أبيض', 'swatch' => '#ffffff'],
        'S' => ['ar' => 'صغير (S)'],
        'M' => ['ar' => 'متوسط (M)'],
        'L' => ['ar' => 'كبير (L)'],
        'XL' => ['ar' => 'كبير جدًا (XL)'],
    ];

    /**
     * Arabic names for the existing default attributes.
     *
     * @var array<string, string>
     */
    protected array $existingAttributeNames = [
        'color' => 'اللون',
        'size' => 'المقاس',
        'brand' => 'العلامة التجارية',
    ];

    /**
     * Popular brand options to seed for the existing (empty) brand attribute.
     *
     * @var array<int, array<string, string>>
     */
    protected array $brandOptions = [
        ['admin_name' => 'Samsung', 'ar' => 'سامسونج', 'en' => 'Samsung'],
        ['admin_name' => 'Apple', 'ar' => 'آبل', 'en' => 'Apple'],
        ['admin_name' => 'Xiaomi', 'ar' => 'شاومي', 'en' => 'Xiaomi'],
        ['admin_name' => 'Huawei', 'ar' => 'هواوي', 'en' => 'Huawei'],
        ['admin_name' => 'Sony', 'ar' => 'سوني', 'en' => 'Sony'],
        ['admin_name' => 'Lenovo', 'ar' => 'لينوفو', 'en' => 'Lenovo'],
        ['admin_name' => 'HP', 'ar' => 'إتش بي', 'en' => 'HP'],
        ['admin_name' => 'Anker', 'ar' => 'أنكر', 'en' => 'Anker'],
    ];

    /**
     * New attributes to create (AliExpress-style), with options.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $newAttributes = [
        [
            'code' => 'material',
            'admin_name' => 'Material',
            'name' => ['ar' => 'الخامة', 'en' => 'Material'],
            'type' => 'select',
            'swatch_type' => 'dropdown',
            'options' => [
                ['admin_name' => 'Cotton', 'ar' => 'قطن', 'en' => 'Cotton'],
                ['admin_name' => 'Polyester', 'ar' => 'بوليستر', 'en' => 'Polyester'],
                ['admin_name' => 'Leather', 'ar' => 'جلد', 'en' => 'Leather'],
                ['admin_name' => 'Stainless Steel', 'ar' => 'ستانلس ستيل', 'en' => 'Stainless Steel'],
                ['admin_name' => 'Plastic', 'ar' => 'بلاستيك', 'en' => 'Plastic'],
                ['admin_name' => 'Silicone', 'ar' => 'سيليكون', 'en' => 'Silicone'],
                ['admin_name' => 'Glass', 'ar' => 'زجاج', 'en' => 'Glass'],
                ['admin_name' => 'Wood', 'ar' => 'خشب', 'en' => 'Wood'],
            ],
        ],
        [
            'code' => 'storage_capacity',
            'admin_name' => 'Storage Capacity',
            'name' => ['ar' => 'سعة التخزين', 'en' => 'Storage Capacity'],
            'type' => 'select',
            'swatch_type' => 'dropdown',
            'options' => [
                ['admin_name' => '32GB', 'ar' => '32 جيجابايت', 'en' => '32GB'],
                ['admin_name' => '64GB', 'ar' => '64 جيجابايت', 'en' => '64GB'],
                ['admin_name' => '128GB', 'ar' => '128 جيجابايت', 'en' => '128GB'],
                ['admin_name' => '256GB', 'ar' => '256 جيجابايت', 'en' => '256GB'],
                ['admin_name' => '512GB', 'ar' => '512 جيجابايت', 'en' => '512GB'],
                ['admin_name' => '1TB', 'ar' => '1 تيرابايت', 'en' => '1TB'],
            ],
        ],
        [
            'code' => 'ram',
            'admin_name' => 'RAM',
            'name' => ['ar' => 'الذاكرة العشوائية', 'en' => 'RAM'],
            'type' => 'select',
            'swatch_type' => 'dropdown',
            'options' => [
                ['admin_name' => '2GB', 'ar' => '2 جيجابايت', 'en' => '2GB'],
                ['admin_name' => '4GB', 'ar' => '4 جيجابايت', 'en' => '4GB'],
                ['admin_name' => '6GB', 'ar' => '6 جيجابايت', 'en' => '6GB'],
                ['admin_name' => '8GB', 'ar' => '8 جيجابايت', 'en' => '8GB'],
                ['admin_name' => '12GB', 'ar' => '12 جيجابايت', 'en' => '12GB'],
                ['admin_name' => '16GB', 'ar' => '16 جيجابايت', 'en' => '16GB'],
            ],
        ],
        [
            'code' => 'country_of_origin',
            'admin_name' => 'Country of Origin',
            'name' => ['ar' => 'بلد المنشأ', 'en' => 'Country of Origin'],
            'type' => 'select',
            'swatch_type' => 'dropdown',
            'options' => [
                ['admin_name' => 'China', 'ar' => 'الصين', 'en' => 'China'],
                ['admin_name' => 'Saudi Arabia', 'ar' => 'السعودية', 'en' => 'Saudi Arabia'],
                ['admin_name' => 'UAE', 'ar' => 'الإمارات', 'en' => 'UAE'],
                ['admin_name' => 'Turkey', 'ar' => 'تركيا', 'en' => 'Turkey'],
                ['admin_name' => 'USA', 'ar' => 'الولايات المتحدة', 'en' => 'USA'],
                ['admin_name' => 'Germany', 'ar' => 'ألمانيا', 'en' => 'Germany'],
                ['admin_name' => 'Japan', 'ar' => 'اليابان', 'en' => 'Japan'],
            ],
        ],
        [
            'code' => 'warranty',
            'admin_name' => 'Warranty',
            'name' => ['ar' => 'الضمان', 'en' => 'Warranty'],
            'type' => 'select',
            'swatch_type' => 'dropdown',
            'options' => [
                ['admin_name' => 'No Warranty', 'ar' => 'بدون ضمان', 'en' => 'No Warranty'],
                ['admin_name' => '6 Months', 'ar' => '6 أشهر', 'en' => '6 Months'],
                ['admin_name' => '1 Year', 'ar' => 'سنة واحدة', 'en' => '1 Year'],
                ['admin_name' => '2 Years', 'ar' => 'سنتان', 'en' => '2 Years'],
                ['admin_name' => '3 Years', 'ar' => '3 سنوات', 'en' => '3 Years'],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->locales = Locale::all();

        $this->enrichExistingAttributes();

        $this->createNewAttributes();
    }

    /**
     * Add Arabic translations + swatches to the default color/size/brand data.
     */
    protected function enrichExistingAttributes(): void
    {
        foreach ($this->existingAttributeNames as $code => $arName) {
            $attribute = Attribute::where('code', $code)->first();

            if (! $attribute) {
                continue;
            }

            $this->upsertAttributeTranslation($attribute, 'AR', $arName);

            $this->command->info("Enriched attribute '{$code}' with Arabic name: {$arName}");
        }

        foreach (AttributeOption::all() as $option) {
            $enrichment = $this->existingOptionEnrichment[$option->admin_name] ?? null;

            if (! $enrichment) {
                continue;
            }

            $this->upsertOptionTranslation($option, 'AR', $enrichment['ar']);

            if (! empty($enrichment['swatch']) && empty($option->swatch_value)) {
                $option->swatch_value = $enrichment['swatch'];
                $option->save();
            }
        }

        $color = Attribute::where('code', 'color')->first();

        if ($color && $color->swatch_type !== 'color') {
            $color->swatch_type = 'color';
            $color->save();

            $this->command->info("Set 'color' attribute swatch type to color.");
        }

        $this->seedBrandOptions();
    }

    /**
     * Seed popular brand options if the brand attribute has none.
     */
    protected function seedBrandOptions(): void
    {
        $brand = Attribute::where('code', 'brand')->first();

        if (! $brand || $brand->options()->count() > 0) {
            return;
        }

        foreach ($this->brandOptions as $index => $optionData) {
            $option = AttributeOption::create([
                'admin_name' => $optionData['admin_name'],
                'attribute_id' => $brand->id,
                'sort_order' => $index + 1,
            ]);

            foreach ($this->locales as $locale) {
                $label = $optionData[strtolower($locale->code)] ?? $optionData['ar'];

                $this->upsertOptionTranslation($option, $locale->code, $label);
            }
        }

        $this->command->info('Seeded '.count($this->brandOptions).' brand options.');
    }

    /**
     * Create the new AliExpress-style attributes with their options.
     */
    protected function createNewAttributes(): void
    {
        $position = (int) Attribute::max('position');

        foreach ($this->newAttributes as $data) {
            if (Attribute::where('code', $data['code'])->exists()) {
                $this->command->warn("Attribute '{$data['code']}' already exists, skipping.");

                continue;
            }

            $position++;

            $attribute = Attribute::create([
                'code' => $data['code'],
                'admin_name' => $data['admin_name'],
                'type' => $data['type'],
                'position' => $position,
                'is_required' => 0,
                'is_unique' => 0,
                'value_per_locale' => 0,
                'value_per_channel' => 0,
                'is_filterable' => 1,
                'is_configurable' => 0,
                'is_visible_on_front' => 1,
                'is_user_defined' => 1,
                'is_comparable' => 1,
                'swatch_type' => $data['swatch_type'],
            ]);

            foreach ($this->locales as $locale) {
                $name = $data['name'][strtolower($locale->code)] ?? $data['name']['ar'];

                $this->upsertAttributeTranslation($attribute, $locale->code, $name);
            }

            foreach ($data['options'] as $index => $optionData) {
                $option = AttributeOption::create([
                    'admin_name' => $optionData['admin_name'],
                    'attribute_id' => $attribute->id,
                    'sort_order' => $index + 1,
                ]);

                foreach ($this->locales as $locale) {
                    $label = $optionData[strtolower($locale->code)] ?? $optionData['ar'];

                    $this->upsertOptionTranslation($option, $locale->code, $label);
                }
            }

            $this->command->info("Created attribute: {$data['name']['ar']} ({$data['code']}) with ".count($data['options']).' options.');
        }
    }

    /**
     * Create or update an attribute translation for a locale.
     */
    protected function upsertAttributeTranslation(Attribute $attribute, string $localeCode, string $name): void
    {
        $attribute->translations()->updateOrCreate(
            ['locale' => $localeCode],
            ['name' => $name]
        );
    }

    /**
     * Create or update an attribute option translation for a locale.
     */
    protected function upsertOptionTranslation(AttributeOption $option, string $localeCode, string $label): void
    {
        $option->translations()->updateOrCreate(
            ['locale' => $localeCode],
            ['label' => $label]
        );
    }
}
