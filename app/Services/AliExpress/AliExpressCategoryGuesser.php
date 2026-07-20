<?php

namespace App\Services\AliExpress;

use Webkul\Category\Models\Category;

/**
 * Guesses a product's catalog category from its title when the AliExpress
 * category id cannot be resolved (the ds.product.get category_id uses the
 * legacy taxonomy that ds.category.get / category-by-id APIs do not expose to
 * the app's permission level).
 *
 * Matching is keyword-based against the synced top-level AliExpress categories.
 * Each rule maps a set of English/Arabic keywords to a top-level AliExpress
 * category id; the first rule whose keyword appears in the (lowercased) title
 * wins. Returns null when nothing matches (caller falls back to "Other").
 */
class AliExpressCategoryGuesser
{
    /**
     * Ordered keyword rules: [aliexpressTopCategoryId => [keywords...]].
     * Order matters — strong, specific signals (jeans, shoes, bra, charger)
     * are checked before generic/ambiguous ones (light, sport) so a stray word
     * like "light" in a colour name ("Light Blue") cannot misroute a garment.
     *
     * @var array<int, string[]>
     */
    protected array $rules = [
        // --- Strong, specific signals first ---
        // Shoes
        322 => ['shoes', 'shoe', 'sneaker', 'sneakers', 'boots', 'sandal', 'sandals', 'loafer', 'heels', 'حذاء', 'احذية', 'أحذية'],
        // Underwear (before sports, so "sports bra" -> underwear)
        200574005 => ['underwear', 'lingerie', 'bra', 'panties', 'briefs', 'boxer', 'ملابس داخلية', 'حمالة'],
        // Bikinis / swimwear / beachwear
        200001866 => ['bikini', 'swimwear', 'swimsuit', 'swimming suit', 'beachwear', 'bathing suit', 'بكيني', 'بيكيني', 'ملابس سباحة', 'ملابس السباحة', 'مايوه', 'مايوه سباحة'],
        // Watches
        1511 => ['watch', 'watches', 'wristwatch', 'smartwatch', 'ساعة', 'ساعات'],
        // Phones & accessories
        509 => ['phone', 'iphone', 'smartphone', 'charger', 'power bank', 'powerbank', 'earbud', 'earbuds', 'usb c', 'usb-c', 'هاتف', 'جوال', 'شاحن', 'شاحنة'],
        // Jewelry
        36 => ['jewelry', 'necklace', 'bracelet', 'earring', 'earrings', 'pendant', 'مجوهرات', 'قلادة', 'سوار', 'خاتم', 'اقراط'],
        // Bags & luggage
        1524 => ['backpack', 'handbag', 'luggage', 'suitcase', 'wallet', 'purse', 'حقيبة', 'حقائب', 'محفظة'],
        // Beauty & health
        66 => ['makeup', 'cosmetic', 'lipstick', 'skincare', 'serum', 'shampoo', 'perfume', 'مكياج', 'عطر', 'عناية', 'بشرة'],
        // Mother & kids
        1501 => ['baby', 'toddler', 'infant', 'diaper', 'stroller', 'طفل', 'اطفال', 'أطفال', 'رضيع'],
        // Toys
        26 => ['toy', 'toys', 'lego', 'puzzle', 'doll', 'لعبة', 'العاب', 'ألعاب', 'دمية'],
        // Men's clothing (jeans/pants/shirt are strong garment signals)
        200000343 => ['jeans', 'trousers', 'hoodie', 'mens', "men's", 'menswear', 'رجالي', 'رجالية', 'قميص', 'بنطال', 'جينز'],
        // Women's clothing
        200000345 => ['dress', 'blouse', 'skirt', 'womens', "women's", 'نسائي', 'نسائية', 'فستان', 'فساتين', 'بلوزة', 'تنورة'],
        // Apparel accessories (belts, hats, scarves)
        200000297 => ['belt', 'scarf', 'gloves', 'sunglasses', 'حزام', 'قبعة', 'وشاح', 'نظارة'],
        // Computer & office
        7 => ['laptop', 'keyboard', 'mouse', 'monitor', 'router', 'حاسوب', 'لابتوب', 'كيبورد', 'ماوس'],
        // Consumer electronics
        44 => ['headphone', 'headphones', 'speaker', 'camera', 'projector', 'drone', 'سماعة', 'سماعات', 'كاميرا'],
        // Home appliances
        6 => ['blender', 'mixer', 'air fryer', 'vacuum', 'kettle', 'microwave', 'خلاط', 'مكنسة', 'غلاية'],
        // Tools
        1420 => ['drill', 'screwdriver', 'wrench', 'مفك', 'مثقاب'],
        // Automobiles
        34 => ['automobile', 'سيارة', 'سيارات'],
        // --- Generic / ambiguous signals last ---
        // Sports
        18 => ['fitness', 'yoga', 'gym', 'bicycle', 'camping', 'fishing', 'رياضة', 'دراجة', 'تخييم'],
        // Home & garden / kitchen
        15 => ['kitchen', 'cookware', 'bedding', 'curtain', 'garden', 'decor', 'مطبخ', 'ديكور', 'حديقة'],
        // Lights (ambiguous "light" requires a lighting-specific phrase)
        39 => ['led strip', 'light bulb', 'lamp', 'chandelier', 'مصباح', 'اضاءة', 'إضاءة', 'لمبة'],
        // Generic clothing fallback
        3 => ['clothing', 'apparel', 'pants', 'shirt', 'jacket', 'ملابس', 'أزياء'],
        // Food
        2 => ['snack', 'coffee', 'طعام', 'قهوة'],
    ];

    /**
     * Guess the Bagisto category id for a product title, or null when no rule
     * matches and no synced category exists for the matched AliExpress id.
     */
    public function guessCategoryId(string $title): ?int
    {
        $haystack = mb_strtolower(trim($title));

        if ($haystack === '') {
            return null;
        }

        foreach ($this->rules as $aliCategoryId => $keywords) {
            foreach ($keywords as $keyword) {
                if ($this->matchesWord($haystack, mb_strtolower($keyword))) {
                    $bagisto = Category::where('aliexpress_category_id', $aliCategoryId)->first();

                    if ($bagisto) {
                        return (int) $bagisto->id;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Whole-word (or phrase) match so a keyword cannot match inside an
     * unrelated word (e.g. "light" in "Light Blue" only matches as a word, and
     * "led" never matches inside "loaded").
     */
    protected function matchesWord(string $haystack, string $needle): bool
    {
        // Multi-word phrases: a simple substring check is fine.
        if (str_contains($needle, ' ')) {
            return str_contains($haystack, $needle);
        }

        return (bool) preg_match('/(?<![\p{L}\p{N}])'.preg_quote($needle, '/').'(?![\p{L}\p{N}])/u', $haystack);
    }
}
