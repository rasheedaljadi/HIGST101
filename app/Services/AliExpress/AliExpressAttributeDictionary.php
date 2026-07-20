<?php

namespace App\Services\AliExpress;

use Illuminate\Support\Str;

/**
 * Static, offline normaliser + Arabic translator for AliExpress variant
 * attribute option values (colors, sizes, and similar axes).
 *
 * Because the store is 100% AliExpress-sourced and the dropshipping API exposes
 * NO category-attribute definition endpoint (confirmed via
 * aliexpress:explore-attributes — the Solution/Postproduct attribute APIs
 * return InsufficientPermission), option values only ever arrive through the
 * products we actually import. This dictionary unifies their messy casing /
 * synonyms and renders Arabic labels deterministically, with no AI calls.
 *
 * Design:
 *  - Colors: many raw spellings/casings collapse to one canonical Arabic label
 *    (e.g. "black", "BLACK", "Coal Black" → "أسود").
 *  - Sizes: universal letter sizes (S/M/L/XL…) map to themselves with an Arabic
 *    rendering; numeric sizes are left untouched.
 *  - Unknown values fall back to the original text (never blocks an import).
 *
 * The translator is intentionally axis-aware: callers pass the axis kind so a
 * value like "Gold" resolves as a color, and numeric size codes are preserved.
 */
class AliExpressAttributeDictionary
{
    /**
     * Canonical color map: normalised english spelling => Arabic label.
     *
     * Keys are matched after {@see self::normalize()} (lowercased, trimmed,
     * collapsed whitespace), so "SKY BLUE", "Sky  Blue" and "sky blue" all hit
     * the same entry.
     *
     * @var array<string, string>
     */
    protected const COLORS = [
        'black' => 'أسود',
        'coal black' => 'أسود',
        'white' => 'أبيض',
        'off white' => 'أبيض مطفأ',
        'ivory' => 'عاجي',
        'red' => 'أحمر',
        'wine red' => 'أحمر نبيذي',
        'blue' => 'أزرق',
        'sky blue' => 'أزرق سماوي',
        'navy blue' => 'أزرق كحلي',
        'navy' => 'كحلي',
        'royal blue' => 'أزرق ملكي',
        'light blue' => 'أزرق فاتح',
        'dark blue' => 'أزرق غامق',
        'azure' => 'أزرق سماوي',
        'green' => 'أخضر',
        'army green' => 'أخضر عسكري',
        'light green' => 'أخضر فاتح',
        'dark green' => 'أخضر غامق',
        'emerald green' => 'أخضر زمردي',
        'olive' => 'زيتي',
        'olive flak' => 'زيتي',
        'mint green' => 'أخضر نعناعي',
        'yellow' => 'أصفر',
        'light yellow' => 'أصفر فاتح',
        'gold' => 'ذهبي',
        'golden' => 'ذهبي',
        'champagne' => 'شمبانيا',
        'orange' => 'برتقالي',
        'pink' => 'وردي',
        'peach blush' => 'خوخي',
        'peach' => 'خوخي',
        'rose' => 'وردي',
        'rose red' => 'أحمر وردي',
        'hot pink' => 'وردي فاقع',
        'purple' => 'بنفسجي',
        'violet' => 'بنفسجي',
        'lavender' => 'لافندر',
        'brown' => 'بني',
        'coffee' => 'بني قهوي',
        'chocolate' => 'بني شوكولاتي',
        'khaki' => 'كاكي',
        'beige' => 'بيج',
        'gray' => 'رمادي',
        'grey' => 'رمادي',
        'dark grey' => 'رمادي غامق',
        'dark gray' => 'رمادي غامق',
        'light grey' => 'رمادي فاتح',
        'light gray' => 'رمادي فاتح',
        'silver' => 'فضي',
        'sliver' => 'فضي',
        'clear' => 'شفاف',
        'transparent' => 'شفاف',
        'multi' => 'متعدد الألوان',
        'multicolor' => 'متعدد الألوان',
        'colorful' => 'متعدد الألوان',
        'other' => 'أخرى',
        'dragon fruit' => 'فاكهة التنين',
        // Extra common AliExpress color spellings
        'apricot' => 'مشمشي',
        'burgundy' => 'خمري',
        'wine' => 'خمري',
        'maroon' => 'كستنائي',
        'turquoise' => 'فيروزي',
        'teal' => 'أزرق مخضر',
        'cyan' => 'سماوي',
        'aqua' => 'أزرق مائي',
        'indigo' => 'نيلي',
        'magenta' => 'أرجواني',
        'fuchsia' => 'فوشيا',
        'coral' => 'مرجاني',
        'salmon' => 'سلموني',
        'cream' => 'كريمي',
        'tan' => 'بني فاتح',
        'camel' => 'جملي',
        'bronze' => 'برونزي',
        'copper' => 'نحاسي',
        'rose gold' => 'ذهبي وردي',
        'gun black' => 'أسود معدني',
        'gunmetal' => 'رمادي معدني',
        'nude' => 'لون الجلد',
        'skin' => 'لون الجلد',
        'mustard' => 'خردلي',
        'lime' => 'أخضر ليموني',
        'lime green' => 'أخضر ليموني',
        'fluorescent green' => 'أخضر فسفوري',
        'dark red' => 'أحمر غامق',
        'bright red' => 'أحمر فاقع',
        'light pink' => 'وردي فاتح',
        'dark pink' => 'وردي غامق',
        'baby blue' => 'أزرق فاتح',
        'jean blue' => 'أزرق جينز',
        'denim blue' => 'أزرق جينز',
        'dark brown' => 'بني غامق',
        'light brown' => 'بني فاتح',
        'dark khaki' => 'كاكي غامق',
        'smoke gray' => 'رمادي دخاني',
        'smoke grey' => 'رمادي دخاني',
        'metal' => 'معدني',
        'natural' => 'طبيعي',
        'as picture' => 'كما في الصورة',
        'as the picture' => 'كما في الصورة',
        'random' => 'عشوائي',
        'random color' => 'لون عشوائي',
        'mixed' => 'ألوان مختلطة',
        'mixed color' => 'ألوان مختلطة',
        // More common AliExpress color spellings (batch 2)
        'jet black' => 'أسود حالك',
        'matte black' => 'أسود مطفي',
        'pure white' => 'أبيض ناصع',
        'snow white' => 'أبيض ثلجي',
        'milk white' => 'أبيض حليبي',
        'pearl white' => 'أبيض لؤلؤي',
        'crimson' => 'قرمزي',
        'scarlet' => 'قرمزي',
        'brick red' => 'أحمر طوبي',
        'cobalt blue' => 'أزرق كوبالت',
        'peacock blue' => 'أزرق طاووسي',
        'denim' => 'أزرق جينز',
        'grass green' => 'أخضر عشبي',
        'forest green' => 'أخضر غابي',
        'neon green' => 'أخضر فسفوري',
        'neon' => 'فسفوري',
        'fluorescent' => 'فسفوري',
        'golden yellow' => 'أصفر ذهبي',
        'lemon yellow' => 'أصفر ليموني',
        'baby pink' => 'وردي فاتح',
        'fuschia' => 'فوشيا',
        'plum' => 'برقوقي',
        'eggplant' => 'باذنجاني',
        'caramel' => 'كراميل',
        'mocha' => 'موكا',
        'taupe' => 'بني رمادي',
        'charcoal' => 'فحمي',
        'slate' => 'رمادي إردوازي',
        'graphite' => 'رصاصي',
        'pewter' => 'قصديري',
        'sand' => 'رملي',
        'stone' => 'حجري',
        'wheat' => 'قمحي',
    ];

    /**
     * Compound color words used to translate multi-word colors (e.g. "Red
     * Black", "Blue Pink") that are not in {@see self::COLORS} verbatim. Each
     * word is translated then joined, so band colors render readably.
     *
     * @var array<string, string>
     */
    protected const COLOR_WORDS = [
        'black' => 'أسود',
        'white' => 'أبيض',
        'red' => 'أحمر',
        'blue' => 'أزرق',
        'green' => 'أخضر',
        'yellow' => 'أصفر',
        'gold' => 'ذهبي',
        'orange' => 'برتقالي',
        'pink' => 'وردي',
        'purple' => 'بنفسجي',
        'brown' => 'بني',
        'khaki' => 'كاكي',
        'beige' => 'بيج',
        'gray' => 'رمادي',
        'grey' => 'رمادي',
        'silver' => 'فضي',
        'coal' => 'فحمي',
        'olive' => 'زيتي',
        'flak' => '',
        'navy' => 'كحلي',
        'sky' => 'سماوي',
        'ivory' => 'عاجي',
        'rose' => 'وردي',
        'wine' => 'خمري',
        'light' => 'فاتح',
        'dark' => 'غامق',
        'deep' => 'غامق',
        'bright' => 'فاقع',
        'coffee' => 'قهوي',
        'champagne' => 'شمبانيا',
        'lavender' => 'لافندر',
        'apricot' => 'مشمشي',
        'mint' => 'نعناعي',
        'royal' => 'ملكي',
        'army' => 'عسكري',
    ];

    /**
     * Universal letter sizes => Arabic rendering. Numeric sizes are preserved
     * as-is (not in this map).
     *
     * @var array<string, string>
     */
    protected const SIZES = [
        'xxxs' => 'XXXS - صغير جداً جداً جداً',
        'xxs' => 'XXS - صغير جداً جداً',
        'xs' => 'XS - صغير جداً',
        's' => 'S - صغير',
        'm' => 'M - وسط',
        'l' => 'L - كبير',
        'xl' => 'XL - كبير جداً',
        'xxl' => 'XXL - كبير جداً جداً',
        '2xl' => '2XL - كبير جداً جداً',
        'xxxl' => 'XXXL - كبير جداً جداً جداً',
        '3xl' => '3XL - كبير جداً جداً جداً',
        '4xl' => '4XL - كبير جداً ×4',
        '5xl' => '5XL - كبير جداً ×5',
        '6xl' => '6XL - كبير جداً ×6',
        '7xl' => '7XL - كبير جداً ×7',
        '1xl' => '1XL - كبير',
        'sml' => 'S/M/L',
        'one size' => 'مقاس واحد',
        'onesize' => 'مقاس واحد',
        'one-size' => 'مقاس واحد',
        'free size' => 'مقاس حر',
        'freesize' => 'مقاس حر',
        'standard' => 'قياسي',
        'adult' => 'بالغ',
        'kids' => 'أطفال',
        'children' => 'أطفال',
    ];

    /**
     * Material values => Arabic. Common across apparel, bags, jewelry, etc.
     *
     * @var array<string, string>
     */
    protected const MATERIALS = [
        'cotton' => 'قطن',
        'pure cotton' => 'قطن خالص',
        'polyester' => 'بوليستر',
        'nylon' => 'نايلون',
        'leather' => 'جلد',
        'genuine leather' => 'جلد طبيعي',
        'pu leather' => 'جلد صناعي',
        'faux leather' => 'جلد صناعي',
        'pu' => 'جلد صناعي',
        'silk' => 'حرير',
        'wool' => 'صوف',
        'linen' => 'كتان',
        'denim' => 'دنيم',
        'spandex' => 'سباندكس',
        'lycra' => 'ليكرا',
        'velvet' => 'مخمل',
        'rubber' => 'مطاط',
        'silicone' => 'سيليكون',
        'plastic' => 'بلاستيك',
        'metal' => 'معدن',
        'stainless steel' => 'ستانلس ستيل',
        'steel' => 'فولاذ',
        'aluminum' => 'ألمنيوم',
        'aluminium' => 'ألمنيوم',
        'glass' => 'زجاج',
        'wood' => 'خشب',
        'bamboo' => 'خيزران',
        'ceramic' => 'سيراميك',
        'paper' => 'ورق',
        'canvas' => 'قماش كانفا',
        'fleece' => 'صوف ناعم',
        'chiffon' => 'شيفون',
        'acrylic' => 'أكريليك',
        'alloy' => 'سبيكة معدنية',
        'zinc alloy' => 'سبيكة زنك',
        'sterling silver' => 'فضة إسترليني',
        'titanium' => 'تيتانيوم',
    ];

    /**
     * Style values => Arabic. Common style/design descriptors across apparel,
     * accessories, home decor, etc.
     *
     * @var array<string, string>
     */
    protected const STYLES = [
        'casual' => 'كاجوال',
        'formal' => 'رسمي',
        'fashion' => 'عصري',
        'fashionable' => 'عصري',
        'classic' => 'كلاسيكي',
        'vintage' => 'كلاسيكي قديم',
        'retro' => 'ريترو',
        'modern' => 'حديث',
        'simple' => 'بسيط',
        'elegant' => 'أنيق',
        'sexy' => 'مثير',
        'sporty' => 'رياضي',
        'sport' => 'رياضي',
        'business' => 'رسمي للأعمال',
        'streetwear' => 'ستريت وير',
        'bohemian' => 'بوهيمي',
        'boho' => 'بوهيمي',
        'korean' => 'كوري',
        'japanese' => 'ياباني',
        'european' => 'أوروبي',
        'american' => 'أمريكي',
        'chinese' => 'صيني',
        'ethnic' => 'تراثي',
        'punk' => 'بانك',
        'gothic' => 'قوطي',
        'minimalist' => 'بسيط أنيق',
        'luxury' => 'فاخر',
        'cute' => 'لطيف',
        'sweet' => 'ناعم',
        'cool' => 'أنيق عصري',
        'solid' => 'سادة',
        'solid color' => 'لون سادة',
        'printed' => 'مطبوع',
        'striped' => 'مخطط',
        'floral' => 'منقوش بالورود',
        'plaid' => 'كاروهات',
        'plain' => 'سادة',
    ];

    /**
     * Length values => Arabic. Garment/length descriptors (numeric lengths like
     * "120cm" are preserved as-is).
     *
     * @var array<string, string>
     */
    protected const LENGTHS = [
        'short' => 'قصير',
        'mid' => 'متوسط',
        'medium' => 'متوسط',
        'long' => 'طويل',
        'extra long' => 'طويل جداً',
        'regular' => 'عادي',
        'knee length' => 'حتى الركبة',
        'ankle length' => 'حتى الكاحل',
        'full length' => 'بطول كامل',
        'mini' => 'قصير جداً',
        'midi' => 'متوسط الطول',
        'maxi' => 'طويل',
        'cropped' => 'مقصوص',
        'above knee' => 'فوق الركبة',
        'below knee' => 'تحت الركبة',
        'floor length' => 'حتى الأرض',
    ];

    /**
     * Capacity values => Arabic. Storage / volume descriptors (numeric
     * capacities like "64GB", "500ml" are preserved as-is).
     *
     * @var array<string, string>
     */
    protected const CAPACITIES = [
        'small' => 'صغير',
        'medium' => 'متوسط',
        'large' => 'كبير',
        'extra large' => 'كبير جداً',
        'mini' => 'صغير جداً',
        'standard' => 'قياسي',
    ];

    /**
     * Metal color values => Arabic (jewelry/watches "Metal Color" axis).
     *
     * @var array<string, string>
     */
    protected const METAL_COLORS = [
        'gold' => 'ذهبي',
        'rose gold' => 'ذهبي وردي',
        'white gold' => 'ذهبي أبيض',
        'yellow gold' => 'ذهبي أصفر',
        'silver' => 'فضي',
        'platinum' => 'بلاتيني',
        'bronze' => 'برونزي',
        'copper' => 'نحاسي',
        'gun black' => 'أسود معدني',
        'gunmetal' => 'رمادي معدني',
        'steel' => 'فولاذي',
        'titanium' => 'تيتانيوم',
        'black' => 'أسود',
        'multicolor' => 'متعدد الألوان',
    ];

    /**
     * Flavor values => Arabic (food/supplement "Flavor" axis).
     *
     * @var array<string, string>
     */
    protected const FLAVORS = [
        'original' => 'أصلي',
        'vanilla' => 'فانيلا',
        'chocolate' => 'شوكولاتة',
        'strawberry' => 'فراولة',
        'banana' => 'موز',
        'mango' => 'مانجو',
        'apple' => 'تفاح',
        'orange' => 'برتقال',
        'lemon' => 'ليمون',
        'mint' => 'نعناع',
        'coffee' => 'قهوة',
        'caramel' => 'كراميل',
        'coconut' => 'جوز الهند',
        'honey' => 'عسل',
        'peach' => 'خوخ',
        'grape' => 'عنب',
        'watermelon' => 'بطيخ',
        'blueberry' => 'توت أزرق',
        'mixed' => 'منوع',
        'unflavored' => 'بدون نكهة',
        'natural' => 'طبيعي',
    ];

    /**
     * Scent values => Arabic (perfume/cosmetics "Scent" axis).
     *
     * @var array<string, string>
     */
    protected const SCENTS = [
        'rose' => 'ورد',
        'jasmine' => 'ياسمين',
        'lavender' => 'لافندر',
        'vanilla' => 'فانيلا',
        'musk' => 'مسك',
        'oud' => 'عود',
        'sandalwood' => 'خشب الصندل',
        'citrus' => 'حمضيات',
        'lemon' => 'ليمون',
        'ocean' => 'منعش بحري',
        'fresh' => 'منعش',
        'floral' => 'زهري',
        'fruity' => 'فواكه',
        'woody' => 'خشبي',
        'mint' => 'نعناع',
        'coconut' => 'جوز الهند',
        'unscented' => 'بدون رائحة',
        'original' => 'أصلي',
    ];

    /**
     * Pattern values => Arabic ("Pattern" axis).
     *
     * @var array<string, string>
     */
    protected const PATTERNS = [
        'solid' => 'سادة',
        'solid color' => 'لون سادة',
        'plain' => 'سادة',
        'striped' => 'مخطط',
        'stripe' => 'مخطط',
        'floral' => 'منقوش بالورود',
        'flower' => 'منقوش بالورود',
        'plaid' => 'كاروهات',
        'checkered' => 'كاروهات',
        'polka dot' => 'منقّط',
        'dot' => 'منقّط',
        'geometric' => 'هندسي',
        'animal print' => 'جلد الحيوان',
        'leopard' => 'نمري',
        'camouflage' => 'تمويه',
        'camo' => 'تمويه',
        'printed' => 'مطبوع',
        'print' => 'مطبوع',
        'cartoon' => 'كرتوني',
        'letter' => 'حروف',
        'patchwork' => 'مرقّع',
        'embroidered' => 'مطرّز',
        'embroidery' => 'تطريز',
    ];

    /**
     * Shape values => Arabic ("Shape" axis).
     *
     * @var array<string, string>
     */
    protected const SHAPES = [
        'round' => 'دائري',
        'circle' => 'دائري',
        'square' => 'مربع',
        'rectangle' => 'مستطيل',
        'rectangular' => 'مستطيل',
        'oval' => 'بيضاوي',
        'heart' => 'قلب',
        'star' => 'نجمة',
        'triangle' => 'مثلث',
        'cat eye' => 'عين القطة',
        'aviator' => 'أفياتور',
        'irregular' => 'غير منتظم',
        'teardrop' => 'دمعة',
        'pear' => 'كمثري',
        'hexagon' => 'سداسي',
    ];

    /**
     * Gender values => Arabic ("Gender" axis).
     *
     * @var array<string, string>
     */
    protected const GENDERS = [
        'men' => 'رجالي',
        'mens' => 'رجالي',
        'male' => 'رجالي',
        'women' => 'نسائي',
        'womens' => 'نسائي',
        'female' => 'نسائي',
        'unisex' => 'للجنسين',
        'kids' => 'أطفال',
        'children' => 'أطفال',
        'boys' => 'أولاد',
        'girls' => 'بنات',
        'baby' => 'رضّع',
    ];

    /**
     * Age-group values => Arabic ("Age Group"/"Applicable Age" axis).
     *
     * @var array<string, string>
     */
    protected const AGE_GROUPS = [
        'adult' => 'بالغ',
        'adults' => 'بالغ',
        'kids' => 'أطفال',
        'children' => 'أطفال',
        'child' => 'طفل',
        'baby' => 'رضيع',
        'infant' => 'رضيع',
        'toddler' => 'طفل صغير',
        'teenager' => 'مراهق',
        'teen' => 'مراهق',
        'all ages' => 'كل الأعمار',
    ];

    /**
     * Voltage values => Arabic ("Voltage" axis). Numeric voltages are kept and
     * only common descriptive tokens are mapped.
     *
     * @var array<string, string>
     */
    protected const VOLTAGES = [
        'dual voltage' => 'جهد مزدوج',
        'universal' => 'جهد عالمي',
    ];

    /**
     * Sleeve-length values => Arabic ("Sleeve Length" axis).
     *
     * @var array<string, string>
     */
    protected const SLEEVES = [
        'sleeveless' => 'بدون أكمام',
        'short sleeve' => 'كم قصير',
        'short sleeves' => 'كم قصير',
        'half sleeve' => 'نصف كم',
        'three quarter' => 'ثلاثة أرباع كم',
        'long sleeve' => 'كم طويل',
        'long sleeves' => 'كم طويل',
        'full sleeve' => 'كم كامل',
        'cap sleeve' => 'كم قصير جداً',
    ];

    /**
     * Neckline / collar values => Arabic ("Collar"/"Neckline" axis).
     *
     * @var array<string, string>
     */
    protected const NECKLINES = [
        'round neck' => 'رقبة دائرية',
        'o-neck' => 'رقبة دائرية',
        'v-neck' => 'رقبة على شكل V',
        'v neck' => 'رقبة على شكل V',
        'crew neck' => 'رقبة مستديرة',
        'turtleneck' => 'رقبة عالية',
        'high neck' => 'رقبة عالية',
        'collar' => 'بياقة',
        'lapel' => 'ياقة كلاسيكية',
        'hooded' => 'بقبعة',
        'square neck' => 'رقبة مربعة',
        'off shoulder' => 'مكشوف الكتفين',
        'one shoulder' => 'كتف واحد',
        'halter' => 'رسن',
    ];

    /**
     * Fit / cut values => Arabic ("Fit" axis).
     *
     * @var array<string, string>
     */
    protected const FITS = [
        'slim fit' => 'قصة ضيقة',
        'slim' => 'ضيق',
        'regular fit' => 'قصة عادية',
        'regular' => 'عادي',
        'loose' => 'فضفاض',
        'loose fit' => 'قصة فضفاضة',
        'oversize' => 'واسع',
        'oversized' => 'واسع',
        'skinny' => 'ضيق جداً',
        'straight' => 'مستقيم',
        'relaxed' => 'مريح',
        'tight' => 'ضيق',
        'standard' => 'قياسي',
    ];

    /**
     * Pack/quantity-set values => Arabic ("Pack"/"Set"/"Quantity" axis).
     * Numeric-only values are preserved by the value-translator.
     *
     * @var array<string, string>
     */
    protected const PACKS = [
        'single' => 'قطعة واحدة',
        'pair' => 'زوج',
        '1pc' => 'قطعة واحدة',
        '1 pc' => 'قطعة واحدة',
        '1pcs' => 'قطعة واحدة',
        '2pcs' => 'قطعتان',
        '3pcs' => '3 قطع',
        '4pcs' => '4 قطع',
        '5pcs' => '5 قطع',
        'set' => 'طقم',
        'a set' => 'طقم',
        'full set' => 'طقم كامل',
        'bundle' => 'باقة',
        'bundle 1' => 'باقة 1',
        'bundle 2' => 'باقة 2',
        'bundle 3' => 'باقة 3',
    ];

    /**
     * Country/origin labels (the "Ships From" axis). Translated for
     * completeness, though this axis is typically hidden for branded
     * dropshipping (it reveals the AliExpress origin).
     *
     * @var array<string, string>
     */
    protected const COUNTRIES = [
        'china' => 'الصين',
        'china mainland' => 'الصين',
        'poland' => 'بولندا',
        'mexico' => 'المكسيك',
        'spain' => 'إسبانيا',
        'russian federation' => 'روسيا',
        'russia' => 'روسيا',
        'ukraine' => 'أوكرانيا',
        'france' => 'فرنسا',
        'israel' => 'إسرائيل',
        'saudi arabia' => 'السعودية',
        'uzbekistan' => 'أوزبكستان',
        'united states' => 'الولايات المتحدة',
        'turkey' => 'تركيا',
        'germany' => 'ألمانيا',
        'italy' => 'إيطاليا',
        'czech republic' => 'التشيك',
        'belgium' => 'بلجيكا',
        'united kingdom' => 'المملكة المتحدة',
        'united arab emirates' => 'الإمارات',
        'uae' => 'الإمارات',
        'netherlands' => 'هولندا',
        'south korea' => 'كوريا الجنوبية',
        'korea' => 'كوريا',
        'japan' => 'اليابان',
        'india' => 'الهند',
        'thailand' => 'تايلاند',
        'vietnam' => 'فيتنام',
        'indonesia' => 'إندونيسيا',
        'malaysia' => 'ماليزيا',
        'brazil' => 'البرازيل',
        'canada' => 'كندا',
        'australia' => 'أستراليا',
        'hong kong' => 'هونغ كونغ',
        'taiwan' => 'تايوان',
        'singapore' => 'سنغافورة',
        'portugal' => 'البرتغال',
        'switzerland' => 'سويسرا',
        'austria' => 'النمسا',
        'sweden' => 'السويد',
        'egypt' => 'مصر',
        'kuwait' => 'الكويت',
        'qatar' => 'قطر',
        'bahrain' => 'البحرين',
        'oman' => 'عُمان',
        'jordan' => 'الأردن',
        'morocco' => 'المغرب',
        'belarus' => 'بيلاروسيا',
        'kazakhstan' => 'كازاخستان',
    ];

    /**
     * Plug types (kept as a short standard rendering with Arabic hint).
     *
     * @var array<string, string>
     */
    protected const PLUG_TYPES = [
        'au' => 'AU - أسترالي',
        'uk' => 'UK - بريطاني',
        'eu' => 'EU - أوروبي',
        'us' => 'US - أمريكي',
        'cn' => 'CN - صيني',
        'jp' => 'JP - ياباني',
        'kr' => 'KR - كوري',
        'in' => 'IN - هندي',
        'br' => 'BR - برازيلي',
        'za' => 'ZA - جنوب أفريقي',
    ];

    /**
     * Axis (attribute) display names => Arabic. Used for the attribute label
     * itself (e.g. the "Color" attribute renders as "اللون").
     *
     * @var array<string, string>
     */
    protected const AXIS_NAMES = [
        'color' => 'اللون',
        'colour' => 'اللون',
        'band color' => 'لون السوار',
        'size' => 'المقاس',
        'shoe size' => 'مقاس الحذاء',
        'ships from' => 'يُشحن من',
        'plug type' => 'نوع القابس',
        'pieces' => 'عدد القطع',
        'bundle' => 'الباقة',
        'material' => 'الخامة',
        'style' => 'الطراز',
        'length' => 'الطول',
        'capacity' => 'السعة',
        'quantity' => 'الكمية',
        'model' => 'الموديل',
        'type' => 'النوع',
        'metal color' => 'لون المعدن',
        'main stone' => 'الحجر الرئيسي',
        'flavor' => 'النكهة',
        'flavour' => 'النكهة',
        'scent' => 'الرائحة',
        'fragrance' => 'الرائحة',
        'pattern' => 'النقشة',
        'shape' => 'الشكل',
        'gender' => 'الجنس',
        'age group' => 'الفئة العمرية',
        'applicable age' => 'الفئة العمرية',
        'voltage' => 'الجهد',
        'wattage' => 'القدرة',
        'power' => 'القدرة',
        'width' => 'العرض',
        'height' => 'الارتفاع',
        'diameter' => 'القطر',
        'thickness' => 'السماكة',
        'weight' => 'الوزن',
        'ring size' => 'مقاس الخاتم',
        'lens color' => 'لون العدسة',
        'frame color' => 'لون الإطار',
        'number of pcs' => 'عدد القطع',
        'sleeve length' => 'طول الكم',
        'sleeve' => 'الكم',
        'neckline' => 'فتحة الرقبة',
        'collar' => 'الياقة',
        'fit' => 'القصة',
        'fit type' => 'نوع القصة',
        'pack' => 'العبوة',
        'set' => 'الطقم',
        'season' => 'الموسم',
        'occasion' => 'المناسبة',
        'edition' => 'الإصدار',
        'version' => 'الإصدار',
        'specification' => 'المواصفة',
        'spec' => 'المواصفة',
        'connector' => 'الموصّل',
        'interface' => 'المنفذ',
    ];

    /**
     * Translate an axis (attribute) display name to Arabic, falling back to the
     * original when unknown.
     */
    public static function translateAxisName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return $name;
        }

        $normalized = static::normalize($name);
        $normalized = preg_replace('/^ae[_ ]/', '', $normalized) ?? $normalized;

        return static::AXIS_NAMES[$normalized] ?? $name;
    }

    /**
     * Translate one option value for a given axis kind.
     *
     * @param  string  $axis  The Bagisto axis code (e.g. "ae_color", "ae_size")
     *                        or AliExpress axis name (e.g. "Color", "Ships From").
     * @return string The Arabic/normalised label, or the original when unknown.
     */
    public static function translate(string $value, string $axis = ''): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        $kind = static::axisKind($axis);
        $normalized = static::normalize($value);

        switch ($kind) {
            case 'color':
                return static::translateColor($value, $normalized);

            case 'size':
                return static::SIZES[$normalized] ?? $value;

            case 'material':
                return static::MATERIALS[$normalized] ?? $value;

            case 'style':
                return static::STYLES[$normalized] ?? $value;

            case 'length':
                return static::LENGTHS[$normalized] ?? $value;

            case 'capacity':
                return static::CAPACITIES[$normalized] ?? $value;

            case 'metal_color':
                return static::METAL_COLORS[$normalized] ?? static::translateColor($value, $normalized);

            case 'flavor':
                return static::FLAVORS[$normalized] ?? $value;

            case 'scent':
                return static::SCENTS[$normalized] ?? $value;

            case 'pattern':
                return static::PATTERNS[$normalized] ?? $value;

            case 'shape':
                return static::SHAPES[$normalized] ?? $value;

            case 'gender':
                return static::GENDERS[$normalized] ?? $value;

            case 'age':
                return static::AGE_GROUPS[$normalized] ?? $value;

            case 'voltage':
                return static::VOLTAGES[$normalized] ?? $value;

            case 'sleeve':
                return static::SLEEVES[$normalized] ?? $value;

            case 'neckline':
                return static::NECKLINES[$normalized] ?? $value;

            case 'fit':
                return static::FITS[$normalized] ?? $value;

            case 'pack':
                return static::PACKS[$normalized] ?? $value;

            case 'country':
                return static::COUNTRIES[$normalized] ?? $value;

            case 'plug':
                return static::PLUG_TYPES[$normalized] ?? $value;
        }

        // Unknown axis: try the most common maps in turn, then leave as-is.
        return static::COLORS[$normalized]
            ?? static::SIZES[$normalized]
            ?? static::MATERIALS[$normalized]
            ?? static::STYLES[$normalized]
            ?? $value;
    }

    /**
     * Translate a batch of values for one axis: [original => translated].
     *
     * @param  string[]  $values
     * @return array<string, string>
     */
    public static function translateBatch(array $values, string $axis = ''): array
    {
        $result = [];

        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value === '') {
                continue;
            }

            $result[$value] = static::translate($value, $axis);
        }

        return $result;
    }

    /**
     * Determine whether the "Ships From" / origin axis should be hidden from
     * customers (branded dropshipping hides the AliExpress origin).
     */
    public static function isOriginAxis(string $axis): bool
    {
        return static::axisKind($axis) === 'country';
    }

    /**
     * Whether the axis name itself has an Arabic entry in {@see self::AXIS_NAMES}.
     * Used by the audit command to flag untranslated axis labels.
     */
    public static function hasAxisName(string $name): bool
    {
        return static::translateAxisName($name) !== trim($name);
    }

    /**
     * Whether a value resolves to a different (Arabic) label for the given axis.
     * Returns false when the value passes through unchanged (i.e. untranslated),
     * with pure-numeric values treated as intentionally preserved (true).
     */
    public static function isValueTranslated(string $value, string $axis = ''): bool
    {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        // Pure numbers / codes are intentionally preserved, not "missing".
        if (preg_match('/^[\d.,\s]+$/u', $value)) {
            return true;
        }

        return static::translate($value, $axis) !== $value;
    }

    /**
     * HEX swatch values for common color names (normalised English keys).
     * Used to render real color swatches on the storefront. Unknown colors get
     * no hex (the attribute then falls back to a text swatch).
     *
     * @var array<string, string>
     */
    protected const COLOR_HEX = [
        'black' => '#000000',
        'coal black' => '#1c1c1c',
        'jet black' => '#0a0a0a',
        'matte black' => '#28282b',
        'white' => '#ffffff',
        'pure white' => '#ffffff',
        'snow white' => '#fffafa',
        'milk white' => '#fdfff5',
        'pearl white' => '#f8f6f0',
        'off white' => '#f5f5f0',
        'ivory' => '#fffff0',
        'cream' => '#fffdd0',
        'beige' => '#f5f5dc',
        'red' => '#e0242a',
        'dark red' => '#8b0000',
        'bright red' => '#ff1a1a',
        'wine red' => '#722f37',
        'wine' => '#722f37',
        'burgundy' => '#800020',
        'crimson' => '#dc143c',
        'scarlet' => '#ff2400',
        'brick red' => '#cb4154',
        'maroon' => '#800000',
        'blue' => '#1e63d0',
        'sky blue' => '#87ceeb',
        'navy blue' => '#1b2a55',
        'navy' => '#1b2a55',
        'royal blue' => '#4169e1',
        'light blue' => '#add8e6',
        'dark blue' => '#00008b',
        'azure' => '#007fff',
        'cobalt blue' => '#0047ab',
        'peacock blue' => '#1a7090',
        'baby blue' => '#bfe3f0',
        'jean blue' => '#5d76a9',
        'denim blue' => '#5d76a9',
        'denim' => '#5d76a9',
        'teal' => '#008080',
        'cyan' => '#00bcd4',
        'aqua' => '#7fdbff',
        'turquoise' => '#40e0d0',
        'indigo' => '#4b0082',
        'green' => '#2e9e3f',
        'army green' => '#4b5320',
        'light green' => '#90ee90',
        'dark green' => '#006400',
        'emerald green' => '#2ecc71',
        'grass green' => '#3f9b0b',
        'forest green' => '#228b22',
        'neon green' => '#39ff14',
        'lime' => '#bfff00',
        'lime green' => '#bfff00',
        'mint green' => '#98ff98',
        'olive' => '#808000',
        'olive flak' => '#5b6238',
        'yellow' => '#ffd400',
        'light yellow' => '#ffffe0',
        'golden yellow' => '#ffdf00',
        'lemon yellow' => '#fff44f',
        'mustard' => '#e1ad01',
        'gold' => '#d4af37',
        'golden' => '#d4af37',
        'champagne' => '#f7e7ce',
        'orange' => '#ff7f1a',
        'apricot' => '#fbceb1',
        'coral' => '#ff7f50',
        'salmon' => '#fa8072',
        'pink' => '#ff86b3',
        'light pink' => '#ffb6c1',
        'dark pink' => '#e75480',
        'hot pink' => '#ff69b4',
        'baby pink' => '#f4c2c2',
        'rose' => '#ff66a5',
        'rose red' => '#c21e56',
        'peach' => '#ffe5b4',
        'peach blush' => '#ffcba4',
        'fuchsia' => '#ff00ff',
        'fuschia' => '#ff00ff',
        'magenta' => '#ff00ff',
        'purple' => '#7e3ff2',
        'violet' => '#7f00ff',
        'lavender' => '#b57edc',
        'plum' => '#8e4585',
        'eggplant' => '#614051',
        'brown' => '#8b5a2b',
        'dark brown' => '#5c4033',
        'light brown' => '#a0522d',
        'coffee' => '#6f4e37',
        'mocha' => '#3b2f2f',
        'chocolate' => '#7b3f00',
        'caramel' => '#c68e17',
        'tan' => '#d2b48c',
        'camel' => '#c19a6b',
        'taupe' => '#483c32',
        'khaki' => '#c3b091',
        'dark khaki' => '#bdb76b',
        'sand' => '#c2b280',
        'wheat' => '#f5deb3',
        'stone' => '#928e85',
        'gray' => '#808080',
        'grey' => '#808080',
        'dark grey' => '#4f4f4f',
        'dark gray' => '#4f4f4f',
        'light grey' => '#d3d3d3',
        'light gray' => '#d3d3d3',
        'smoke gray' => '#738276',
        'smoke grey' => '#738276',
        'charcoal' => '#36454f',
        'slate' => '#708090',
        'graphite' => '#383838',
        'pewter' => '#8a9a9a',
        'silver' => '#c0c0c0',
        'sliver' => '#c0c0c0',
        'bronze' => '#cd7f32',
        'copper' => '#b87333',
        'gun black' => '#2a3439',
        'gunmetal' => '#2a3439',
        'rose gold' => '#b76e79',
        'clear' => '#f0f8ff',
        'transparent' => '#f0f8ff',
        'nude' => '#e3bc9a',
        'skin' => '#e3bc9a',
    ];

    /**
     * The storefront swatch type to use for an axis: 'color' for color axes
     * (renders color circles), otherwise 'text' (renders selectable buttons,
     * matching AliExpress's size/option chips). Never 'dropdown'.
     */
    public static function swatchTypeForAxis(string $axis): string
    {
        $kind = static::axisKind($axis);

        if ($kind === 'color' || $kind === 'metal_color') {
            return 'color';
        }

        return 'text';
    }

    /**
     * Resolve a HEX swatch value for a color option, or null when unknown.
     * Compound colors (e.g. "Red Black") use the first recognised word.
     */
    public static function colorHex(string $value): ?string
    {
        $normalized = static::normalize($value);

        if (isset(static::COLOR_HEX[$normalized])) {
            return static::COLOR_HEX[$normalized];
        }

        foreach (preg_split('/\s+/', $normalized) ?: [] as $word) {
            if (isset(static::COLOR_HEX[$word])) {
                return static::COLOR_HEX[$word];
            }
        }

        return null;
    }

    /**
     * Resolve a color value to Arabic, handling single and compound spellings.
     */
    protected static function translateColor(string $original, string $normalized): string
    {
        if (isset(static::COLORS[$normalized])) {
            return static::COLORS[$normalized];
        }

        // Pure numbers or codes (e.g. "2", "4") are not real color names; keep.
        if (preg_match('/^\d+$/', $normalized)) {
            return $original;
        }

        // Compound color (e.g. "Red Black", "Blue Pink"): translate each known
        // word and join with a separator; bail to original if nothing matched.
        $words = preg_split('/\s+/', $normalized) ?: [];
        $parts = [];
        $matchedAny = false;

        foreach ($words as $word) {
            if (isset(static::COLOR_WORDS[$word])) {
                $matchedAny = true;
                $translated = static::COLOR_WORDS[$word];
                if ($translated !== '') {
                    $parts[] = $translated;
                }
            } elseif (isset(static::COLORS[$word])) {
                $matchedAny = true;
                $parts[] = static::COLORS[$word];
            } else {
                $parts[] = $word;
            }
        }

        return $matchedAny && $parts !== [] ? implode(' ', $parts) : $original;
    }

    /**
     * Classify an axis identifier into a known kind.
     */
    protected static function axisKind(string $axis): string
    {
        $a = static::normalize($axis);
        $a = preg_replace('/^ae[_ ]/', '', $a) ?? $a;

        // Metal color must be checked before the generic color rule.
        if (str_contains($a, 'metal color') || str_contains($a, 'metal colour')) {
            return 'metal_color';
        }

        if ($a === 'color' || str_contains($a, 'color') || str_contains($a, 'colour')) {
            return 'color';
        }

        if ($a === 'size' || str_contains($a, 'size')) {
            return 'size';
        }

        if ($a === 'material' || str_contains($a, 'material') || str_contains($a, 'fabric')) {
            return 'material';
        }

        // Sleeve must be checked before length ("sleeve length" contains "length").
        if (str_contains($a, 'sleeve')) {
            return 'sleeve';
        }

        if (str_contains($a, 'neckline') || str_contains($a, 'collar') || str_contains($a, 'neck')) {
            return 'neckline';
        }

        if ($a === 'fit' || str_contains($a, 'fit type') || str_contains($a, 'fitting')) {
            return 'fit';
        }

        if ($a === 'pack' || $a === 'set' || $a === 'pieces' || str_contains($a, 'pack') || str_contains($a, 'number of pcs') || str_contains($a, 'quantity') || str_contains($a, 'pcs') || str_contains($a, 'pieces') || str_contains($a, 'bundle')) {
            return 'pack';
        }

        if ($a === 'style' || str_contains($a, 'style')) {
            return 'style';
        }

        if ($a === 'length' || str_contains($a, 'length')) {
            return 'length';
        }

        if ($a === 'capacity' || str_contains($a, 'capacity') || str_contains($a, 'volume')) {
            return 'capacity';
        }

        if (str_contains($a, 'flavor') || str_contains($a, 'flavour') || str_contains($a, 'taste')) {
            return 'flavor';
        }

        if (str_contains($a, 'scent') || str_contains($a, 'fragrance')) {
            return 'scent';
        }

        if (str_contains($a, 'pattern')) {
            return 'pattern';
        }

        if (str_contains($a, 'shape')) {
            return 'shape';
        }

        if ($a === 'gender' || str_contains($a, 'gender')) {
            return 'gender';
        }

        if (str_contains($a, 'voltage') || str_contains($a, 'volt')) {
            return 'voltage';
        }

        if ($a === 'age' || str_contains($a, 'age group') || str_contains($a, 'age range') || str_contains($a, 'applicable age')) {
            return 'age';
        }

        if (str_contains($a, 'ships from') || str_contains($a, 'ships_from') || str_contains($a, 'ship from') || str_contains($a, 'origin')) {
            return 'country';
        }

        if (str_contains($a, 'plug')) {
            return 'plug';
        }

        return '';
    }

    /**
     * Normalise a label for matching: lowercase, collapse whitespace, strip
     * decorative suffixes like "（old）".
     */
    protected static function normalize(string $value): string
    {
        $value = preg_replace('/（[^）]*）/u', '', $value);
        $value = preg_replace('/\([^)]*\)/u', '', (string) $value);
        $value = preg_replace('/\s+/u', ' ', (string) $value);

        return Str::lower(trim((string) $value));
    }
}
