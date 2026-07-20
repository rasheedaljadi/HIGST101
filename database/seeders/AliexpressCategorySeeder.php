<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Locale;

/**
 * Seeder for the five most popular AliExpress-style categories.
 *
 * All category details (name, description, SEO meta data) are provided in
 * Arabic, tailored for an Arabic e-commerce experience. Categories are created
 * as children of the existing root category (id = 1) and rely on the
 * nested-set NodeTrait to manage the tree boundaries automatically.
 */
class AliexpressCategorySeeder extends Seeder
{
    /**
     * The five most popular AliExpress categories with full Arabic details.
     *
     * @var array<int, array<string, string>>
     */
    protected array $categories = [
        [
            'slug' => 'electronics',
            'ar' => [
                'name' => 'الإلكترونيات والأجهزة',
                'description' => 'تشكيلة واسعة من الأجهزة الإلكترونية وأحدث التقنيات: أجهزة الحاسوب المحمولة، السماعات، الساعات الذكية، الكاميرات، وملحقات الألعاب بأفضل الأسعار وجودة مضمونة.',
                'meta_title' => 'الإلكترونيات والأجهزة | تسوق أحدث التقنيات أونلاين',
                'meta_description' => 'اكتشف أحدث الأجهزة الإلكترونية والتقنيات الذكية بأسعار تنافسية مع شحن سريع وضمان الجودة.',
                'meta_keywords' => 'إلكترونيات, أجهزة ذكية, لابتوب, سماعات, ساعات ذكية, كاميرات',
            ],
            'en' => [
                'name' => 'Electronics & Gadgets',
                'description' => 'A wide range of electronic devices and the latest technology: laptops, headphones, smartwatches, cameras, and gaming accessories at the best prices with guaranteed quality.',
                'meta_title' => 'Electronics & Gadgets | Shop the Latest Tech Online',
                'meta_description' => 'Discover the latest electronic devices and smart technology at competitive prices with fast shipping and quality assurance.',
                'meta_keywords' => 'electronics, smart devices, laptop, headphones, smartwatch, cameras',
            ],
        ],
        [
            'slug' => 'mobiles-accessories',
            'ar' => [
                'name' => 'الجوّالات وملحقاتها',
                'description' => 'أحدث الهواتف الذكية وملحقاتها من أشهر العلامات التجارية، تشمل الأغطية الواقية، الشواحن، سماعات البلوتوث، والبطاريات المتنقلة لتلبية كل احتياجاتك.',
                'meta_title' => 'الجوّالات وملحقاتها | أحدث الهواتف الذكية',
                'meta_description' => 'تسوّق أحدث الهواتف الذكية وملحقاتها الأصلية بأسعار مميزة وعروض حصرية مع توصيل سريع.',
                'meta_keywords' => 'جوالات, هواتف ذكية, ملحقات الجوال, شواحن, أغطية حماية, سماعات',
            ],
            'en' => [
                'name' => 'Mobiles & Accessories',
                'description' => 'The latest smartphones and accessories from top brands, including protective cases, chargers, Bluetooth headsets, and power banks to meet all your needs.',
                'meta_title' => 'Mobiles & Accessories | The Latest Smartphones',
                'meta_description' => 'Shop the latest smartphones and genuine accessories at great prices with exclusive deals and fast delivery.',
                'meta_keywords' => 'mobiles, smartphones, phone accessories, chargers, protective cases, headsets',
            ],
        ],
        [
            'slug' => 'women-fashion',
            'ar' => [
                'name' => 'أزياء النساء',
                'description' => 'إطلالات عصرية تناسب كل المناسبات: ملابس نسائية، فساتين، أحذية، حقائب، وإكسسوارات من أحدث صيحات الموضة وبجودة عالية تناسب ذوقك.',
                'meta_title' => 'أزياء النساء | أحدث صيحات الموضة النسائية',
                'meta_description' => 'تألقي بأحدث صيحات الموضة النسائية من ملابس وأحذية وحقائب وإكسسوارات بأسعار في متناول الجميع.',
                'meta_keywords' => 'أزياء نسائية, ملابس نساء, فساتين, أحذية نسائية, حقائب, موضة',
            ],
            'en' => [
                'name' => "Women's Fashion",
                'description' => 'Modern looks for every occasion: women\'s clothing, dresses, shoes, bags, and accessories from the latest fashion trends with high quality to suit your taste.',
                'meta_title' => "Women's Fashion | The Latest Fashion Trends",
                'meta_description' => 'Shine with the latest women\'s fashion trends in clothing, shoes, bags, and accessories at affordable prices.',
                'meta_keywords' => "women's fashion, women's clothing, dresses, women's shoes, bags, fashion",
            ],
        ],
        [
            'slug' => 'home-garden',
            'ar' => [
                'name' => 'المنزل والحديقة',
                'description' => 'كل ما يحتاجه منزلك ليصبح أكثر راحة وأناقة: أدوات المطبخ، المفروشات، الإضاءة، الديكور، ومستلزمات الحديقة بتصاميم عصرية وأسعار مناسبة.',
                'meta_title' => 'المنزل والحديقة | مستلزمات منزلية وديكور',
                'meta_description' => 'جهّز منزلك وحديقتك بأفضل المستلزمات من أدوات مطبخ ومفروشات وديكورات بأسعار تنافسية وجودة عالية.',
                'meta_keywords' => 'مستلزمات منزلية, ديكور, أدوات مطبخ, مفروشات, إضاءة, حديقة',
            ],
            'en' => [
                'name' => 'Home & Garden',
                'description' => 'Everything your home needs to be more comfortable and elegant: kitchenware, furnishings, lighting, decor, and garden supplies with modern designs at affordable prices.',
                'meta_title' => 'Home & Garden | Home Supplies & Decor',
                'meta_description' => 'Equip your home and garden with the best supplies, from kitchenware and furnishings to decor, at competitive prices and high quality.',
                'meta_keywords' => 'home supplies, decor, kitchenware, furnishings, lighting, garden',
            ],
        ],
        [
            'slug' => 'beauty-health',
            'ar' => [
                'name' => 'الجمال والعناية الشخصية',
                'description' => 'منتجات العناية والجمال التي تستحقينها: مستحضرات التجميل، العناية بالبشرة والشعر، العطور، وأدوات التجميل من علامات موثوقة لإطلالة مثالية.',
                'meta_title' => 'الجمال والعناية الشخصية | مستحضرات تجميل وعناية',
                'meta_description' => 'اعتني بجمالك مع تشكيلة واسعة من مستحضرات التجميل ومنتجات العناية بالبشرة والشعر والعطور بأسعار مميزة.',
                'meta_keywords' => 'مستحضرات تجميل, العناية بالبشرة, العناية بالشعر, عطور, أدوات تجميل, جمال',
            ],
            'en' => [
                'name' => 'Beauty & Personal Care',
                'description' => 'The beauty and care products you deserve: cosmetics, skin and hair care, perfumes, and beauty tools from trusted brands for a perfect look.',
                'meta_title' => 'Beauty & Personal Care | Cosmetics & Care',
                'meta_description' => 'Take care of your beauty with a wide range of cosmetics, skincare, haircare, and perfumes at great prices.',
                'meta_keywords' => 'cosmetics, skincare, haircare, perfumes, beauty tools, beauty',
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rootCategory = Category::whereNull('parent_id')->orderBy('_lft')->first();

        if (! $rootCategory) {
            $this->command->error('Root category not found. Please run the base installer seeders first.');

            return;
        }

        $locales = Locale::all();

        $position = (int) Category::where('parent_id', $rootCategory->id)->max('position');

        foreach ($this->categories as $data) {
            if (Category::whereTranslation('slug', $data['slug'])->exists()) {
                $this->command->warn("Category '{$data['slug']}' already exists, skipping.");

                continue;
            }

            $position++;

            $category = new Category([
                'position' => $position,
                'status' => 1,
                'display_mode' => 'products_and_description',
            ]);

            $category->parent_id = $rootCategory->id;
            $category->save();

            foreach ($locales as $locale) {
                $localeData = $data[strtolower($locale->code)] ?? $data['ar'];

                $category->translations()->create([
                    'locale_id' => $locale->id,
                    'locale' => $locale->code,
                    'name' => $localeData['name'],
                    'slug' => $data['slug'],
                    'description' => $localeData['description'],
                    'meta_title' => $localeData['meta_title'],
                    'meta_description' => $localeData['meta_description'],
                    'meta_keywords' => $localeData['meta_keywords'],
                ]);
            }

            $this->command->info("Created category: {$data['ar']['name']} ({$data['slug']})");
        }
    }
}
