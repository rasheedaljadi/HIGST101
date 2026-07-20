<?php

namespace App\Services\AliExpress;

use Illuminate\Support\Str;

/**
 * Static English => Arabic dictionary for AliExpress category names.
 *
 * Category names come from a fixed, finite taxonomy, so they never need a live
 * (paid / rate-limited) translation. Resolving them locally keeps the entire
 * import pipeline free of any external AI and fully offline/deterministic.
 *
 * Lookups are case-insensitive and tolerate trailing decorations AliExpress
 * appends to some names (e.g. "Basketball（New）", "Coffee1", "(hidden)").
 * Unknown names fall back to their original English text.
 */
class AliExpressCategoryDictionary
{
    /**
     * Exact English => Arabic map (normalised keys are matched case-insensitively).
     *
     * @var array<string, string>
     */
    protected const MAP = [
        // ── Top-level departments ──
        'Apparel & Accessories' => 'الملابس والإكسسوارات',
        'Home Appliances' => 'الأجهزة المنزلية',
        'Computer & Office' => 'الكمبيوتر والمكتب',
        'Home Improvement' => 'تحسين المنزل',
        'Home & Garden' => 'المنزل والحديقة',
        'Sports & Entertainment' => 'الرياضة والترفيه',
        'Office & School Supplies' => 'مستلزمات المكتب والمدرسة',
        'Toys & Hobbies' => 'الألعاب والهوايات',
        'Security & Protection' => 'الأمن والحماية',
        'Automobiles, Parts & Accessories' => 'السيارات وقطع الغيار والإكسسوارات',
        'Beauty & Health' => 'الجمال والصحة',
        'Jewelry & Accessories' => 'المجوهرات والإكسسوارات',
        'Lights & Lighting' => 'الإضاءة والمصابيح',
        'Consumer Electronics' => 'الإلكترونيات الاستهلاكية',
        'Phones & Telecommunications' => 'الهواتف والاتصالات',
        'Electronic Components & Supplies' => 'المكونات واللوازم الإلكترونية',
        'Mother & Kids' => 'الأم والطفل',
        'Luggage & Bags' => 'الحقائب والأمتعة',
        'Shoes' => 'الأحذية',
        'Watches' => 'الساعات',
        'Hair Extensions & Wigs' => 'وصلات الشعر والباروكات',
        'Weddings & Events' => 'الأعراس والمناسبات',
        'Food' => 'الأطعمة',
        'Furniture' => 'الأثاث',
        'Grocery' => 'البقالة',
        'Industrial & Business' => 'الصناعة والأعمال',
        'Tools' => 'الأدوات',
        'Motorcycle & ATV' => 'الدراجات النارية والمركبات الرباعية',
        'Novelty & Special Use' => 'المنتجات المبتكرة والاستخدامات الخاصة',
        'Underwear' => 'الملابس الداخلية',

        // ── Apparel ──
        'Women\'s Clothing' => 'ملابس نسائية',
        'Men\'s Clothing' => 'ملابس رجالية',
        'Children\'s Clothing' => 'ملابس أطفال',
        'Apparel Accessories' => 'إكسسوارات الملابس',
        'Apparel Fabrics & Textiles' => 'أقمشة ومنسوجات الملابس',
        'Basic Clothing' => 'الملابس الأساسية',
        'Functional Apparel' => 'الملابس الوظيفية',
        'Traditional Men\'s Clothing' => 'الملابس الرجالية التقليدية',
        'Traditional Women\'s Clothing' => 'الملابس النسائية التقليدية',
        'Muslim Fashion' => 'الأزياء الإسلامية',
        'Middle East Fashion' => 'أزياء الشرق الأوسط',
        'World Apparel' => 'الأزياء العالمية',
        'Maternity Clothings' => 'ملابس الحوامل',
        'Plus Size Clothes' => 'ملابس المقاسات الكبيرة',
        'Plus Size Men\'s Clothing' => 'ملابس رجالية بمقاسات كبيرة',
        'Exotic Apparel' => 'الملابس المثيرة',
        'Dresses' => 'الفساتين',
        'Blouses & Shirts' => 'البلوزات والقمصان',
        'Shirts & Blouses' => 'القمصان والبلوزات',
        'Coats & Jackets' => 'المعاطف والجاكيتات',
        'Down Coats' => 'معاطف الريش',
        'Down payment/ Purchasing Agent' => 'دفعة مقدمة / وكيل شراء',
        'Hoodies & Sweatshirts' => 'الهوديات والسويت شيرت',
        'Jeans（New）' => 'الجينز',
        'Denim（New）' => 'الدنيم',
        'Pants' => 'البناطيل',
        'Pants & Capris' => 'البناطيل والكابري',
        'Shorts' => 'الشورتات',
        'Skirts' => 'التنانير',
        'Sweaters' => 'الكنزات',
        'Sweaters&Jumpers' => 'الكنزات والبلوفرات',
        'Suits & Blazer' => 'البدلات والبليزر',
        'Blazer & Suits' => 'البليزر والبدلات',
        'Tops & Tees' => 'القمصان والتيشيرتات',
        'Leggings' => 'اللقنز',
        'Parkas' => 'معاطف الباركا',
        'Jumpsuits, Playsuits & Bodysuits' => 'أفرول وبودي سوت',
        'Jumpsuits&Rompers' => 'الأفرولات والرومبرز',
        'Matching Sets' => 'الأطقم المتناسقة',
        'Women\'s Sets' => 'أطقم نسائية',
        'Men\'s Sets（new）' => 'أطقم رجالية',
        'Men\'s Shirts' => 'قمصان رجالية',
        'Men\'s Sleep & Lounge' => 'ملابس النوم والاسترخاء الرجالية',
        'Women\'s Sleep & Lounge' => 'ملابس النوم والاسترخاء النسائية',
        'Men\'s Underwears' => 'الملابس الداخلية الرجالية',
        'Women\'s Intimates' => 'الملابس الداخلية النسائية',
        'Bikinis' => 'البيكيني',
        'Swimwears' => 'ملابس السباحة',
        'Sportswear' => 'الملابس الرياضية',
        'Sportswears' => 'الملابس الرياضية',
        'Stage & Dance Wear' => 'ملابس المسرح والرقص',
        'Work Wear & Uniforms' => 'ملابس العمل والزي الموحد',
        'Party & Vacation Wear' => 'ملابس الحفلات والإجازات',
        'Ready-to-wear Dresses' => 'فساتين جاهزة',
        'Haute Couture Dresses' => 'فساتين الأزياء الراقية',
        'Special Occasion Dresses' => 'فساتين المناسبات الخاصة',

        // ── Apparel accessories ──
        'Belts' => 'الأحزمة',
        'Belt Buckle' => 'إبزيم الحزام',
        'Hats & Caps' => 'القبعات',
        'Headwear' => 'أغطية الرأس',
        'New Headwear' => 'أغطية الرأس الجديدة',
        'Scarves & Wraps' => 'الأوشحة واللفحات',
        'Scarf, Hat & Glove Sets' => 'أطقم الأوشحة والقبعات والقفازات',
        'Gloves & Mittens' => 'القفازات',
        'Arm Warmers' => 'مدفئات الذراع',
        'Earmuffs' => 'واقيات الأذن',
        'Ties&Bows' => 'ربطات العنق والفيونكات',
        'Tie Accessories' => 'إكسسوارات ربطة العنق',
        'Eyewear & Accessories' => 'النظارات والإكسسوارات',
        'Wedding Accessories' => 'إكسسوارات الزفاف',
        'Kids Accessories' => 'إكسسوارات الأطفال',

        // ── Shoes ──
        'Women\'s Shoes' => 'أحذية نسائية',
        'Men\'s Shoes' => 'أحذية رجالية',
        'Kids Shoes' => 'أحذية أطفال',
        'Sneakers' => 'الأحذية الرياضية',
        'Sports Shoes,Clothing&Accessories' => 'الأحذية والملابس والإكسسوارات الرياضية',
        'Mules & Clogs' => 'القباقيب',
        'Other Shoes' => 'أحذية أخرى',
        'Shoe Accessories' => 'إكسسوارات الأحذية',

        // ── Bags ──
        'Women\'s Handbags' => 'حقائب اليد النسائية',
        'Men\'s Bags' => 'حقائب رجالية',
        'Kids\' Bags' => 'حقائب الأطفال',
        'Backpack' => 'حقائب الظهر',
        'School Bags' => 'الحقائب المدرسية',
        'Travel Bags' => 'حقائب السفر',
        'Luggage & Travel Bags' => 'الأمتعة وحقائب السفر',
        'Wallets & Holders' => 'المحافظ والحوامل',
        'Chest Bags' => 'حقائب الصدر',
        'Waist Packs' => 'حقائب الخصر',
        'Sport Bags' => 'الحقائب الرياضية',
        'Leisure Bags' => 'حقائب الترفيه',
        'Summer Bags' => 'حقائب صيفية',
        'Winter Bags' => 'حقائب شتوية',
        'Organizer Bag' => 'حقيبة منظمة',
        'Bag Parts & Accessories' => 'أجزاء وإكسسوارات الحقائب',
        'Special Purpose Bags' => 'حقائب لأغراض خاصة',
        'Other Luggage & Bags' => 'حقائب وأمتعة أخرى',
        'Travel Accessories' => 'إكسسوارات السفر',

        // ── Jewelry & watches ──
        'Fashion Jewelry' => 'مجوهرات الموضة',
        'Fine Jewelry' => 'المجوهرات الفاخرة',
        'Smart Jewelry' => 'المجوهرات الذكية',
        'Jewelry Making' => 'صناعة المجوهرات',
        'Jewelry Packaging & Display' => 'تغليف وعرض المجوهرات',
        'Jewelry Tools & Equipments' => 'أدوات ومعدات المجوهرات',
        'Men\'s Watches' => 'ساعات رجالية',
        'Women\'s Watches' => 'ساعات نسائية',
        'Couple Watches' => 'ساعات للأزواج',
        'Children\'s Watches' => 'ساعات أطفال',
        'Pocket & Fob Watches' => 'ساعات الجيب',
        'Watches Accessories' => 'إكسسوارات الساعات',

        // ── Beauty & health ──
        'Makeup' => 'المكياج',
        'Skin Care' => 'العناية بالبشرة',
        'Skin Care Tool' => 'أدوات العناية بالبشرة',
        'Hair Care & Styling' => 'العناية بالشعر وتصفيفه',
        'Hair Tools & Accessories' => 'أدوات وإكسسوارات الشعر',
        'Hair Salon Supply' => 'مستلزمات صالونات الشعر',
        'Nail Art & Tools' => 'فن الأظافر والأدوات',
        'Fragrances & Deodorants' => 'العطور ومزيلات العرق',
        'Perfume' => 'العطور',
        'Health Care' => 'الرعاية الصحية',
        'Oral Hygiene' => 'العناية بالفم',
        'Shaving & Hair Removal' => 'الحلاقة وإزالة الشعر',
        'Personal Care Appliances' => 'أجهزة العناية الشخصية',
        'Beauty Equipment' => 'معدات التجميل',
        'Beauty Supply' => 'مستلزمات التجميل',
        'Tattoo & Body Art' => 'الوشم وفن الجسد',
        'Massage & Relaxation' => 'التدليك والاسترخاء',
        'Bath & Shower' => 'الحمام والاستحمام',

        // ── Hair ──
        'Human Hair (For Black)' => 'الشعر الطبيعي (للبشرة السمراء)',
        'Human Hair (For White)' => 'الشعر الطبيعي (للبشرة البيضاء)',
        'Human Wigs( For Black)' => 'الباروكات الطبيعية (للبشرة السمراء)',
        'Synthetic Hair' => 'الشعر الصناعي',
        'Synthetic Hair(For White)' => 'الشعر الصناعي (للبشرة البيضاء)',
        'Hair For Asian' => 'الشعر الآسيوي',

        // ── Mother & kids ──
        'Baby Clothing' => 'ملابس الأطفال الرضع',
        'Baby & Toddler Toys' => 'ألعاب الرضع والأطفال الصغار',
        'Baby Care' => 'العناية بالطفل',
        'Baby Food' => 'طعام الأطفال',
        'Baby Furniture' => 'أثاث الأطفال',
        'Baby Diaper & Wipes' => 'حفاضات ومناديل الأطفال',
        'Baby Strollers&Accessories' => 'عربات الأطفال وإكسسواراتها',
        'Baby Sterilization & Appliances' => 'تعقيم وأجهزة الأطفال',
        'Feeding' => 'الرضاعة والتغذية',
        'Diapering & Toilet Training' => 'الحفاضات والتدريب على المرحاض',
        'Pregnancy & Maternity' => 'الحمل والأمومة',
        'Children Furniture' => 'أثاث الأطفال',
        'Children\'s Sports' => 'رياضة الأطفال',
        'Kid\'s Party' => 'حفلات الأطفال',
        'Learning & Education' => 'التعلّم والتعليم',

        // ── Electronics & phones ──
        'Mobile Phones' => 'الهواتف المحمولة',
        'Mobile Phone Accessories' => 'إكسسوارات الهواتف المحمولة',
        'Mobile Phone Cases & Covers' => 'حافظات وأغطية الهواتف',
        'Mobile Phone Parts' => 'قطع غيار الهواتف',
        'Mobile Phone Protective Film' => 'واقيات شاشة الهواتف',
        'Mobile Phone Photography Accessories' => 'إكسسوارات تصوير الهواتف',
        'Mobile Phone Decorations' => 'زينة الهواتف',
        'Used Phones' => 'هواتف مستعملة',
        'Sim Cards & Accessories' => 'بطاقات SIM والإكسسوارات',
        'Walkie Talkie' => 'أجهزة اللاسلكي',
        'Communication Equipment' => 'معدات الاتصالات',
        'Phones & Telecommunications Accessories' => 'إكسسوارات الهواتف والاتصالات',
        'Camera & Photo' => 'الكاميرات والتصوير',
        'Portable Audio & Video' => 'الصوت والفيديو المحمول',
        'Home Audio & Video' => 'الصوت والفيديو المنزلي',
        'Smart Electronics' => 'الإلكترونيات الذكية',
        'Electronic Accessories & Supplies' => 'الإكسسوارات واللوازم الإلكترونية',
        'Video Surveillance' => 'المراقبة بالفيديو',

        // ── Computer & office ──
        'Laptops' => 'أجهزة اللابتوب',
        'Desktops & AIO' => 'أجهزة الكمبيوتر المكتبية',
        'Tablets' => 'الأجهزة اللوحية',
        'Tablet Accessories & Parts' => 'إكسسوارات وقطع الأجهزة اللوحية',
        'Computer Components' => 'مكونات الكمبيوتر',
        'Computer Peripherals' => 'ملحقات الكمبيوتر',
        'Computer Cleaners' => 'منظفات الكمبيوتر',
        'Laptop Parts & Accessories' => 'قطع وإكسسوارات اللابتوب',
        'Networking' => 'الشبكات',
        'Internal Storage' => 'وسائط التخزين الداخلية',
        'Storage Device' => 'أجهزة التخزين',
        'Office Electronics' => 'الإلكترونيات المكتبية',
        'Office Software' => 'برامج المكتب',
        'Servers & Industrial Computer' => 'الخوادم وأجهزة الكمبيوتر الصناعية',

        // ── Home & garden ──
        'Home Decor' => 'ديكور المنزل',
        'Home Textile' => 'المنسوجات المنزلية',
        'Home Storage & Organization' => 'تخزين وتنظيم المنزل',
        'Home Furniture' => 'أثاث المنزل',
        'Kitchen,Dining & Bar' => 'المطبخ والطعام والبار',
        'Bedding' => 'مفروشات السرير',
        'Garden Supplies' => 'مستلزمات الحديقة',
        'Garden Tools' => 'أدوات الحديقة',
        'Festive & Party Supplies' => 'مستلزمات الأعياد والحفلات',
        'Pet Products' => 'مستلزمات الحيوانات الأليفة',
        'Arts,Crafts & Sewing' => 'الفنون والحرف والخياطة',
        'Household Merchandises' => 'السلع المنزلية',
        'Household Appliances' => 'الأجهزة المنزلية',
        'Major Appliances' => 'الأجهزة الكبيرة',
        'Kitchen Appliances' => 'أجهزة المطبخ',
        'Cleaning Appliances' => 'أجهزة التنظيف',
        'Home Appliance Parts' => 'قطع غيار الأجهزة المنزلية',

        // ── Tools & home improvement ──
        'Hand Tools' => 'الأدوات اليدوية',
        'Power Tools' => 'الأدوات الكهربائية',
        'Power Tool Parts & Accessories' => 'قطع وإكسسوارات الأدوات الكهربائية',
        'Tool Sets' => 'أطقم الأدوات',
        'Tool Parts' => 'قطع الأدوات',
        'Tools & Accessories' => 'الأدوات والإكسسوارات',
        'Construction Tools' => 'أدوات البناء',
        'Building Supplies' => 'مستلزمات البناء',
        'Electrical Equipment & Supplies' => 'المعدات واللوازم الكهربائية',
        'Electrical Equipment' => 'المعدات الكهربائية',
        'Plumbing' => 'السباكة',
        'Hardware' => 'الأجهزة والعتاد',
        'Painting Supplies & Wall Treatments' => 'مستلزمات الطلاء ومعالجة الجدران',
        'Welding & Soldering Supplies' => 'مستلزمات اللحام',
        'Kitchen Fixture' => 'تجهيزات المطبخ',
        'Bathroom Fixture' => 'تجهيزات الحمام',

        // ── Automobiles ──
        'Auto Replacement Parts' => 'قطع غيار السيارات',
        'Interior Accessories' => 'الإكسسوارات الداخلية',
        'Exterior Accessories' => 'الإكسسوارات الخارجية',
        'Car Electronics' => 'إلكترونيات السيارات',
        'Car Lights' => 'أضواء السيارات',
        'Car Wash & Maintenance' => 'غسيل وصيانة السيارات',
        'Car Repair Tool' => 'أدوات إصلاح السيارات',
        'Motorcycle Parts' => 'قطع غيار الدراجات النارية',
        'Motorcycle Accessories' => 'إكسسوارات الدراجات النارية',
        'Motorcycle Equipments & Parts' => 'معدات وقطع الدراجات النارية',
        'RV Parts & Accessories' => 'قطع وإكسسوارات المركبات الترفيهية',
        'Other Vehicle Parts & Accessories' => 'قطع وإكسسوارات مركبات أخرى',

        // ── Sports ──
        'Fitness & Body Building' => 'اللياقة وكمال الأجسام',
        'Camping & Hiking' => 'التخييم والمشي لمسافات طويلة',
        'Cycling' => 'ركوب الدراجات',
        'Fishing' => 'صيد السمك',
        'Hunting' => 'الصيد',
        'Golf' => 'الجولف',
        'Water Sports' => 'الرياضات المائية',
        'Skiing & Snowboarding' => 'التزلج على الجليد',
        'Team Sports' => 'الرياضات الجماعية',
        'Racquet Sports' => 'رياضات المضرب',
        'Sports Accessories' => 'الإكسسوارات الرياضية',
        'Sports Bags(hidden)' => 'الحقائب الرياضية',
        'Outdoor Fun & Sports' => 'المرح والرياضة في الهواء الطلق',
        'Horse Riding' => 'ركوب الخيل',
        'Roller,Skateboard' => 'التزلج واللوح المتحرك',
        'Shooting' => 'الرماية',
        'Dance' => 'الرقص',
        'Cheerleading' => 'التشجيع',

        // ── Toys & hobbies ──
        'Classic Toys' => 'الألعاب الكلاسيكية',
        'Electronic Toys' => 'الألعاب الإلكترونية',
        'High Tech Toys' => 'الألعاب عالية التقنية',
        'Remote Control Toys' => 'الألعاب بالتحكم عن بعد',
        'Dolls & Stuffed Toys' => 'الدمى والألعاب المحشوة',
        'Dolls & Accessories' => 'الدمى والإكسسوارات',
        'Stuffed Animals & Plush' => 'الحيوانات المحشوة',
        'Building & Construction Toys' => 'ألعاب البناء والتركيب',
        'Action & Toy Figures' => 'مجسمات الحركة',
        'Games and Puzzles' => 'الألعاب والألغاز',
        'Games & Accessories' => 'الألعاب والإكسسوارات',
        'Model Building' => 'بناء النماذج',
        'Play Vehicles & Models' => 'المركبات والنماذج اللعبة',
        'Pretend Play' => 'ألعاب التظاهر',
        'Arts & Crafts, DIY toys' => 'الفنون والحرف وألعاب اصنعها بنفسك',
        'Novelty & Gag Toys' => 'الألعاب الطريفة',
        'Stress Relief Toy' => 'ألعاب تخفيف التوتر',
        'Hobby & Collectibles' => 'الهوايات والمقتنيات',
        'Trendy Blind Box' => 'الصناديق العشوائية الرائجة',
        'Musical Instruments' => 'الآلات الموسيقية',

        // ── Food & grocery ──
        'Coffee' => 'القهوة',
        'Tea' => 'الشاي',
        'Dried Fruit' => 'الفواكه المجففة',
        'Nut & Kernel' => 'المكسرات والبذور',
        'Canned Food' => 'الأطعمة المعلبة',
        'Grain Products' => 'منتجات الحبوب',
        'Bread and Pastries' => 'الخبز والمعجنات',
        'Cheese' => 'الجبن',
        'Meat' => 'اللحوم',
        'Sausages' => 'النقانق',
        'Fish and Sea Food' => 'الأسماك والمأكولات البحرية',
        'Milk and Eggs' => 'الحليب والبيض',
        'Vegetables and Greens' => 'الخضروات والورقيات',
        'Fruits and Berries' => 'الفواكه والتوت',
        'Frozen Products' => 'المنتجات المجمدة',
        'Ready Meal' => 'الوجبات الجاهزة',
        'Alcoholic Beverages' => 'المشروبات الكحولية',
        'Water/ Juices/ Drinks' => 'المياه والعصائر والمشروبات',

        // ── Lighting ──
        'LED Lighting' => 'إضاءة LED',
        'Indoor Lighting' => 'الإضاءة الداخلية',
        'Outdoor Lighting' => 'الإضاءة الخارجية',
        'Holiday Lighting' => 'إضاءة الأعياد',
        'Commercial Lighting' => 'الإضاءة التجارية',
        'Portable Lighting' => 'الإضاءة المحمولة',
        'Night Lights' => 'الأضواء الليلية',
        'Lighting Bulbs & Tubes' => 'المصابيح والأنابيب',
        'Lighting Accessories' => 'إكسسوارات الإضاءة',

        // ── Office & school ──
        'Notebooks & Writing Pads' => 'الدفاتر ولوحات الكتابة',
        'Pens, Pencils & Writing Supplies' => 'الأقلام ومستلزمات الكتابة',
        'Art Supplies' => 'مستلزمات الفنون',
        'School Supplies' => 'المستلزمات المدرسية',
        'Desk Accessories & Organizer' => 'إكسسوارات ومنظمات المكتب',
        'Filing Products' => 'منتجات الحفظ والأرشفة',
        'Office Binding Supplies' => 'مستلزمات التجليد المكتبية',
        'Office Furniture' => 'أثاث المكتب',
        'Paper & Printable Media' => 'الورق ووسائط الطباعة',
        'Books' => 'الكتب',

        // ── Misc / common ──
        'Other' => 'أخرى',
        'Special Category' => 'فئة خاصة',
        'Virtual Products' => 'المنتجات الافتراضية',
        'Safety' => 'السلامة',
        'Emergency Safety Supplies' => 'مستلزمات السلامة الطارئة',
        'Wedding Dresses' => 'فساتين الزفاف',
        'Wedding Party Dress' => 'فساتين حفلات الزفاف',

        // ── Additional real subcategories ──
        '3D Printing & Additive Manufacturing' => 'الطباعة ثلاثية الأبعاد والتصنيع الإضافي',
        'Abrasive Tools & Abrasives' => 'أدوات الكشط والمواد الكاشطة',
        'Access Building Automation' => 'أنظمة التحكم بالدخول وأتمتة المباني',
        'Accessories & Parts' => 'الإكسسوارات وقطع الغيار',
        'Accounting Supplies' => 'مستلزمات المحاسبة',
        'Active Components' => 'المكونات الفعّالة',
        'Activity & Gear' => 'الأنشطة والمعدات',
        'Agricultural Machinery & Supplies' => 'الآلات والمستلزمات الزراعية',
        'Air Compressors, Pneumatics & Hydraulics' => 'ضواغط الهواء والأنظمة الهوائية والهيدروليكية',
        'Aircraft' => 'الطائرات',
        'Art Tool Kits' => 'أطقم أدوات الفنون',
        'Automotive Sensors' => 'حساسات السيارات',
        'Baby Souvenirs' => 'هدايا تذكارية للأطفال',
        'Bar Furniture' => 'أثاث البار',
        'Barebone & Mini PC' => 'أجهزة الكمبيوتر المصغّرة',
        'Basketball' => 'كرة السلة',
        'Blend Wigs' => 'الباروكات المخلوطة',
        'Blowers, Industrial Fans & Exhaust Equipment' => 'المنافيخ والمراوح الصناعية ومعدات الشفط',
        'Boards' => 'الألواح',
        'Boats' => 'القوارب',
        'Books & Cultural Merchandise' => 'الكتب والمنتجات الثقافية',
        'Business Commuter Laptop Bag' => 'حقائب اللابتوب للأعمال',
        'Café Furniture' => 'أثاث المقاهي',
        'Car Lock System' => 'أنظمة أقفال السيارات',
        'Car Maintenance Tools' => 'أدوات صيانة السيارات',
        'Car Seats & Accessories' => 'مقاعد السيارات والإكسسوارات',
        'Car Services' => 'خدمات السيارات',
        'Chassis Parts' => 'قطع الهيكل',
        'Chemicals' => 'المواد الكيميائية',
        'Collar Stays' => 'دعامات الياقة',
        'Collectibles' => 'المقتنيات',
        'Commercial Appliances' => 'الأجهزة التجارية',
        'Commercial Equipment' => 'المعدات التجارية',
        'Commercial Furniture' => 'الأثاث التجاري',
        'Computer & Office Bundle' => 'حزمة الكمبيوتر والمكتب',
        'Construction Machinery & Accessories' => 'آلات البناء والإكسسوارات',
        'Cosplay Accessories' => 'إكسسوارات التنكر',
        'Cosplay Costumes' => 'أزياء التنكر',
        'Cummerbunds' => 'أحزمة الخصر',
        'Customized Blouses & Shirts' => 'بلوزات وقمصان مخصصة',
        'Customized Dresses' => 'فساتين مخصصة',
        'Customized Jewelry' => 'مجوهرات مخصصة',
        'Customized Skirts' => 'تنانير مخصصة',
        'Customized Watches' => 'ساعات مخصصة',
        'Dental Supplies' => 'مستلزمات طب الأسنان',
        'DIY Accessories' => 'إكسسوارات اصنعها بنفسك',
        'DIY Gaming Computer' => 'تجميع كمبيوتر الألعاب',
        'Doors, Gates & Windows' => 'الأبواب والبوابات والنوافذ',
        'Drafting Supplies' => 'مستلزمات الرسم الهندسي',
        'Dried Goods / Local Specialties' => 'الأطعمة المجففة والمنتجات المحلية',
        'Drill Bits, Saw Blades & Cutting Tools' => 'لقم الثقب وشفرات المناشير وأدوات القطع',
        'Educational Equipment & Supplies' => 'المعدات والمستلزمات التعليمية',
        'Electronic Cigarettes' => 'السجائر الإلكترونية',
        'Electronic Signs' => 'اللافتات الإلكترونية',
        'Electronics Production Machinery' => 'آلات إنتاج الإلكترونيات',
        'Engines & Engine Parts' => 'المحركات وقطع غيارها',
        'Entertainment' => 'الترفيه',
        'Exterior Parts' => 'القطع الخارجية',
        'Fabric & Textile Raw Material' => 'المواد الخام للأقمشة والمنسوجات',
        'Fashionable Canes' => 'العصي العصرية',
        'Faux Leather' => 'الجلد الصناعي',
        'Food Machine and Supporting Equipment' => 'آلات الطعام والمعدات المساندة',
        'Football' => 'كرة القدم',
        'Functional Material' => 'المواد الوظيفية',
        'Fur & Faux Fur' => 'الفراء والفراء الصناعي',
        'Furniture Accessories' => 'إكسسوارات الأثاث',
        'Furniture Parts' => 'قطع الأثاث',
        'Garden Landscaping & Decking' => 'تنسيق الحدائق والأرضيات الخشبية',
        'Genuine Leather' => 'الجلد الطبيعي',
        'Handkerchiefs' => 'المناديل',
        'Handling, Warehousing & Transportation Equipment' => 'معدات المناولة والتخزين والنقل',
        'Heating, Cooling & Vents' => 'التدفئة والتبريد والتهوية',
        'Holders & Stands' => 'الحوامل والركائز',
        'Industrial and Commercial Cleaning Equipment' => 'معدات التنظيف الصناعية والتجارية',
        'Industrial Automation Control & Accessories' => 'التحكم في الأتمتة الصناعية والإكسسوارات',
        'Industrial Spare Parts' => 'قطع الغيار الصناعية',
        'Industry Machinery & Equipment' => 'الآلات والمعدات الصناعية',
        'Interior Parts' => 'القطع الداخلية',
        'Journal, Periodical & Magazines' => 'الصحف والدوريات والمجلات',
        'Knee Sleeve & Leg Warmer' => 'دعامات الركبة ومدفئات الساق',
        'Laser Engraving Machine & Accessories' => 'آلات النقش بالليزر والإكسسوارات',
        'Luggage' => 'الأمتعة',
        'Maps & Atlases' => 'الخرائط والأطالس',
        'Mask' => 'الأقنعة',
        'Measurement & Analysis Instruments' => 'أدوات القياس والتحليل',
        'Medical Laboratory Equipment' => 'معدات المختبرات الطبية',
        'Men Socks' => 'جوارب رجالية',
        'Metal Building Materials' => 'مواد البناء المعدنية',
        'Metal Processing Equipment & Accessories' => 'معدات معالجة المعادن والإكسسوارات',
        'Metals & Alloys' => 'المعادن والسبائك',
        'Motorcycle Equipments' => 'معدات الدراجات النارية',
        'Music, CDs & Vinyl Records' => 'الموسيقى والأقراص والأسطوانات',
        'New Energy Vehicle Parts & Accessories' => 'قطع وإكسسوارات مركبات الطاقة الجديدة',
        'Optoelectronic Displays' => 'شاشات العرض الكهروضوئية',
        'Ornamental & Cleaning' => 'الزينة والتنظيف',
        'Other Electronic Components' => 'مكونات إلكترونية أخرى',
        'Other Furniture' => 'أثاث آخر',
        'Other Home Appliances' => 'أجهزة منزلية أخرى',
        'Other Home Improvement' => 'تحسينات منزلية أخرى',
        'Other Lights & Lighting Products' => 'منتجات إضاءة أخرى',
        'Other Sports & Entertainment Product' => 'منتجات رياضة وترفيه أخرى',
        'Other Tools' => 'أدوات أخرى',
        'Outdoor Furniture' => 'الأثاث الخارجي',
        'Oversleeve & Arm Warmer' => 'الأكمام الواقية ومدفئات الذراع',
        'Packaging, Printing & Supporting Equipment' => 'معدات التغليف والطباعة والمساندة',
        'Passive Components' => 'المكونات السلبية',
        'Pocket Squares' => 'مناديل الجيب',
        'Pools & Water Fun' => 'المسابح والألعاب المائية',
        'Presentation Supplies' => 'مستلزمات العروض التقديمية',
        'Printing Products' => 'منتجات الطباعة',
        'Professional Light' => 'الإضاءة الاحترافية',
        'Quarry Stone & Slabs' => 'أحجار المحاجر والألواح الحجرية',
        'Real Fur' => 'الفراء الطبيعي',
        'Rehabilitation Supplies' => 'مستلزمات إعادة التأهيل',
        'Riveter Guns' => 'مسدسات البرشام',
        'Rubbers & Plastics' => 'المطاط واللدائن',
        'Sanitary Paper' => 'الورق الصحي',
        'Second-Hand' => 'المستعمل',
        'Senior Furniture' => 'أثاث كبار السن',
        'Sex Products' => 'المنتجات الجنسية',
        'Smart Public Safety Systems' => 'أنظمة السلامة العامة الذكية',
        'Software & Games' => 'البرمجيات والألعاب',
        'Special Engineering Lighting' => 'إضاءة الهندسة الخاصة',
        'Sports Competitions' => 'المسابقات الرياضية',
        'Stationery Sticker' => 'ملصقات القرطاسية',
        'Suspenders/Braces & Posture Correctors' => 'حمالات البنطلون ومقومات الوضعية',
        'Tailor-made Hoodies & Sweatshirts' => 'هوديات وسويت شيرت حسب الطلب',
        'Tailor-made Shirts' => 'قمصان حسب الطلب',
        'Tapes, Adhesives & Fasteners' => 'الأشرطة واللواصق والمثبتات',
        'Tools Packaging' => 'تغليف الأدوات',
        'Used&Refurbished Phones' => 'الهواتف المستعملة والمجددة',
        'Walkie Talkie Accessories & Parts' => 'إكسسوارات وقطع أجهزة اللاسلكي',
        'Wallpaper Sample' => 'عينات ورق الجدران',
        'Wear Parts' => 'قطع التآكل',
        'Welding Equipment & Supplies' => 'معدات ومستلزمات اللحام',
        'Women\'s Socks & Hosiery' => 'الجوارب النسائية',
        'Woodworking Machinery & Accessories' => 'آلات النجارة والإكسسوارات',
        'Electronic Data Systems' => 'أنظمة البيانات الإلكترونية',

        // ── Service / virtual / promotional categories ──
        'ACG Goods' => 'منتجات الأنمي والألعاب',
        'Auto Sale' => 'بيع السيارات',
        'Checkout Link' => 'رابط الدفع',
        'Coupons' => 'الكوبونات',
        'Platform Coupon' => 'كوبون المنصة',
        'Cultural Derivatives(Office Supplies)' => 'المنتجات الثقافية (مستلزمات مكتبية)',
        'EL Products' => 'منتجات الإضاءة الكهربائية',
        'Electronics Stocks' => 'مخزون الإلكترونيات',
        'Event Ticket' => 'تذاكر الفعاليات',
        'Family Intelligence System' => 'نظام المنزل الذكي العائلي',
        'Flight Booking' => 'حجز الطيران',
        'Hotel Booking' => 'حجز الفنادق',
        'Giftcard' => 'بطاقة الهدايا',
        'Giftcard Commission Category' => 'فئة عمولة بطاقات الهدايا',
        'Giveaways' => 'الهدايا المجانية',
        'Installation Service' => 'خدمة التركيب',
        'Lottery Tickets' => 'تذاكر اليانصيب',
        'Mailing & Shipping' => 'البريد والشحن',
        'Mini App' => 'التطبيقات المصغّرة',
        'Mobile Phone Recharge' => 'شحن رصيد الهاتف',
        'Modification&Protection' => 'التعديل والحماية',
        'new Scarf &Wrap' => 'الأوشحة واللفحات',
        'Novelty Lighting' => 'الإضاءة المبتكرة',
        'Overseas Warehouse' => 'المستودع الخارجي',
        'Prepaid Digital Codes' => 'الأكواد الرقمية المدفوعة مسبقًا',
        'Software &Games (without VAT)' => 'البرمجيات والألعاب',
        'Topup Commission Category' => 'فئة عمولة الشحن',
        'Travel Vocation' => 'السفر والإجازات',
        'Upcoming Products' => 'المنتجات القادمة',
        'Additional Pay on Your Order' => 'دفعة إضافية على طلبك',
        'Custom-made Charge' => 'رسوم التصنيع حسب الطلب',
        'Food&Fresh' => 'الأطعمة والطازج',
    ];

    /**
     * Resolve the Arabic name for an English category name.
     *
     * Returns null when no entry exists, letting the caller keep the English
     * original (so unmapped names never block a sync).
     */
    public static function translate(string $english): ?string
    {
        $english = trim($english);

        if ($english === '') {
            return null;
        }

        $normalized = static::normalize($english);

        foreach (static::lookup() as $key => $arabic) {
            if ($key === $normalized) {
                return $arabic;
            }
        }

        return null;
    }

    /**
     * Translate a batch, returning [original => arabic-or-original].
     *
     * @param  string[]  $texts
     * @return array<string, string>
     */
    public static function translateBatch(array $texts): array
    {
        $result = [];

        foreach ($texts as $text) {
            $text = trim((string) $text);

            if ($text === '') {
                continue;
            }

            $result[$text] = static::translate($text) ?? $text;
        }

        return $result;
    }

    /**
     * Whether the dictionary has an Arabic entry for the given English name.
     */
    public static function has(string $english): bool
    {
        return static::translate($english) !== null;
    }

    /**
     * Build a normalised lookup map once per request (key => arabic).
     *
     * @return array<string, string>
     */
    protected static function lookup(): array
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];

            foreach (static::MAP as $english => $arabic) {
                $cache[static::normalize($english)] = $arabic;
            }
        }

        return $cache;
    }

    /**
     * Normalise a name for matching: lowercase, collapse whitespace, and drop
     * the decorative suffixes AliExpress appends (（New）, 1, (hidden), etc.).
     */
    protected static function normalize(string $name): string
    {
        $name = trim($name);

        // Drop full-width "（...）" and ASCII "(...)" decorations.
        $name = preg_replace('/（[^）]*）/u', '', $name);
        $name = preg_replace('/\([^)]*\)/u', '', $name);

        // Drop trailing digit decorations like "Coffee1", "Giftcard1".
        $name = preg_replace('/\d+$/', '', (string) $name);

        $name = preg_replace('/\s+/u', ' ', (string) $name);

        return Str::lower(trim((string) $name));
    }
}
