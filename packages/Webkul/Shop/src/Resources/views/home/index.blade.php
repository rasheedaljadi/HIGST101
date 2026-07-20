@php
    $channel = core()->getCurrentChannel();
@endphp

<!-- SEO Meta Content -->
@push ('meta')
    <meta
        name="title"
        content="{{ $channel->home_seo['meta_title'] ?? '' }}"
    />

    <meta
        name="description"
        content="{{ $channel->home_seo['meta_description'] ?? '' }}"
    />

    <meta
        name="keywords"
        content="{{ $channel->home_seo['meta_keywords'] ?? '' }}"
    />
@endPush

@push('scripts')
    @if(! empty($categories))
        <script>
            localStorage.setItem('categories', JSON.stringify(@json($categories)));
        </script>
    @endif
@endpush

<x-shop::layouts>
    <!-- Page Title -->
    <x-slot:title>
        {{  $channel->home_seo['meta_title'] ?? '' }}
    </x-slot>

    <!-- Loop over the theme customization -->
    @foreach ($customizations as $customization)
        @php ($data = $customization->options) @endphp

        <!-- Static content -->
        @switch ($customization->type)
            @case ($customization::IMAGE_CAROUSEL)
                <!-- Image Carousel -->
                <x-shop::carousel
                    :options="$data"
                    aria-label="{{ trans('shop::app.home.index.image-carousel') }}"
                />

                @php
                    $productImageHelper = app(\Webkul\Product\ProductImage::class);
                    $featuredProducts = [];
                    try {
                        $aliExpressImports = \App\Models\AliExpressProductImport::where('status', 'success')
                            ->whereNotNull('product_id')
                            ->limit(3)
                            ->get();
                        foreach ($aliExpressImports as $import) {
                            if ($import->product) {
                                $featuredProducts[] = $import->product;
                            }
                        }
                    } catch (\Throwable $e) {}

                    if (count($featuredProducts) < 3) {
                        try {
                            $allProducts = \Webkul\Product\Models\Product::where('status', 1)->limit(3)->get();
                            foreach ($allProducts as $p) {
                                $featuredProducts[] = $p;
                            }
                        } catch (\Throwable $e) {}
                    }

                    $customCategories = [
                        [
                            'name' => 'الألعاب والألعاب',
                            'image' => asset('images/custom_categories/toys.png'),
                            'link' => route('shop.product_or_category.index', 'toys-hobbies-ar'),
                            'icon_svg' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>'
                        ],
                        [
                            'name' => 'أزياء نسائية',
                            'image' => asset('images/custom_categories/womens_fashion.png'),
                            'link' => route('shop.product_or_category.index', 'apparel-accessories-ar'),
                            'icon_svg' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>'
                        ],
                        [
                            'name' => 'أثاث ومفروشات',
                            'image' => asset('images/custom_categories/furniture.png'),
                            'link' => route('shop.product_or_category.index', 'home-garden-ar'),
                            'icon_svg' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'
                        ],
                        [
                            'name' => 'أزياء رجالية',
                            'image' => asset('images/custom_categories/mens_fashion.png'),
                            'link' => route('shop.product_or_category.index', 'apparel-accessories-ar'),
                            'icon_svg' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
                        ],
                        [
                            'name' => 'أحذية وحقائب',
                            'image' => asset('images/custom_categories/shoes.png'),
                            'link' => route('shop.product_or_category.index', 'shoes-ar'),
                            'icon_svg' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>'
                        ],
                        [
                            'name' => 'الجمال والصحة',
                            'image' => asset('images/custom_categories/beauty.png'),
                            'link' => route('shop.product_or_category.index', 'beauty-health-ar'),
                            'icon_svg' => '<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>'
                        ]
                    ];
                @endphp

                <!-- Premium Custom Shop by Category Section -->
                <div class="py-12 bg-[#F6F8FC] dark:bg-gray-950 overflow-hidden">
                    <div class="max-w-[1320px] mx-auto px-4 md:px-6">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                            
                            <!-- Left Column: Shop by Category Grid (7/12 cols) -->
                            <div class="lg:col-span-7 flex flex-col justify-between relative">
                                <!-- Dot pattern background decoration -->
                                <div class="absolute -top-6 -left-6 grid grid-cols-5 gap-1.5 opacity-30 select-none pointer-events-none">
                                    @for ($i = 0; $i < 25; $i++)
                                        <div class="w-1.5 h-1.5 rounded-full bg-[#FFC000]"></div>
                                    @endfor
                                </div>

                                <div class="mb-8 relative z-10 text-right">
                                    <h2 class="text-3xl font-extrabold text-[#002060] dark:text-white mb-2">
                                        تسوق حسب الفئة
                                    </h2>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm font-medium">
                                        اكتشف مجموعة واسعة من المنتجات في جميع الفئات
                                    </p>
                                    <div class="h-1 w-14 bg-[#FFC000] mt-3 mr-0 ml-auto"></div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 relative z-10">
                                    @foreach ($customCategories as $cat)
                                        <a href="{{ $cat['link'] }}" class="bg-white dark:bg-gray-900 rounded-2xl shadow-[0_4px_25px_rgba(0,0,0,0.03)] hover:shadow-[0_8px_35px_rgba(0,0,0,0.07)] transition-all duration-300 p-4 flex items-center justify-between border border-gray-100 dark:border-gray-800 relative group overflow-hidden">
                                            
                                            <!-- Round image cut-out -->
                                            <div class="relative w-20 h-20 rounded-full overflow-hidden bg-[#F2F6FC] dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
                                                <img src="{{ $cat['image'] }}" class="w-[85%] h-[85%] object-contain group-hover:scale-110 transition-transform duration-300" alt="{{ $cat['name'] }}">
                                                
                                                <!-- Yellow icon badge overlapping the top-right of the image -->
                                                <div class="absolute -top-0.5 -right-0.5 w-7 h-7 rounded-full bg-[#FFC000] flex items-center justify-center shadow-sm">
                                                    {!! $cat['icon_svg'] !!}
                                                </div>
                                            </div>
                                            
                                            <!-- Category Name & Left arrow inside card -->
                                            <div class="flex-1 pr-4 flex flex-col items-start justify-center text-right">
                                                <span class="text-base font-bold text-gray-800 dark:text-gray-100 group-hover:text-[#002060] dark:group-hover:text-[#FFC000] transition-colors duration-300">{{ $cat['name'] }}</span>
                                                <span class="text-[#002060] dark:text-[#FFC000] text-xl font-bold mt-1 group-hover:-translate-x-1 transition-transform duration-300">←</span>
                                            </div>

                                        </a>
                                    @endforeach
                                </div>

                                <!-- View All Button -->
                                <div class="mt-8 text-center sm:text-right relative z-10">
                                    <a href="{{ route('shop.home.index') }}" class="inline-flex items-center justify-center gap-2.5 bg-[#002060] hover:bg-[#001040] text-white px-8 py-3.5 rounded-full hover:shadow-lg transition-all duration-300 font-bold">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                        </svg>
                                        <span>عرض جميع الفئات</span>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Right Column: Featured Premium Card (5/12 cols) -->
                            <div class="lg:col-span-5">
                                <div class="bg-gradient-to-br from-[#0B2562] to-[#123E8E] rounded-[2.5rem] p-8 text-white flex flex-col justify-between shadow-xl relative overflow-hidden h-full min-h-[520px]">
                                    <!-- Dot pattern decoration -->
                                    <div class="absolute -bottom-6 -left-6 grid grid-cols-5 gap-1.5 opacity-20 select-none pointer-events-none">
                                        @for ($i = 0; $i < 25; $i++)
                                            <div class="w-1.5 h-1.5 rounded-full bg-[#FFC000]"></div>
                                        @endfor
                                    </div>

                                    <div class="text-center mb-6">
                                        <h3 class="text-3xl font-extrabold mb-2 tracking-wide leading-tight">
                                            لوازم <span class="text-[#FFC000]">الموضة</span> التي تختارينها
                                        </h3>
                                        <p class="text-white/80 text-sm">
                                            أفضل العروض لأحدث صيحات الموضة
                                        </p>
                                        <a href="{{ route('shop.product_or_category.index', 'apparel-accessories-ar') }}" class="bg-[#FFB900] hover:bg-[#FFC000] text-gray-900 font-bold px-6 py-2 rounded-full inline-flex items-center justify-center gap-2 mt-4 transition-all duration-300 shadow-md">
                                            <span>تسوق الآن</span>
                                            <span class="text-lg font-bold">←</span>
                                        </a>
                                    </div>

                                    <!-- 3 Product Cards -->
                                    <div class="grid grid-cols-3 gap-3 my-4">
                                        @foreach ($featuredProducts as $index => $prod)
                                            @php
                                                $prodImageUrl = $productImageHelper->getProductBaseImage($prod)['medium_image_url'] ?? asset('themes/default/assets/images/placeholder.png');
                                                $prodUrl = route('shop.product_or_category.index', $prod->url_key);
                                                $prodPrice = core()->currency($prod->price);
                                                
                                                $hasDiscount = false;
                                                $originalPrice = null;
                                                $currentPrice = $prodPrice;
                                                if ($prod->special_price) {
                                                    $hasDiscount = true;
                                                    $originalPrice = $prodPrice;
                                                    $currentPrice = core()->currency($prod->special_price);
                                                }
                                                
                                                $averageRating = $prod->ratings['average'] ?? number_format(4.2 + ($index * 0.3), 1);
                                                $totalSales = $prod->sales['total'] ?? (95 + ($index * 26));
                                            @endphp
                                            <a href="{{ $prodUrl }}" class="bg-white rounded-3xl p-3 shadow-sm hover:shadow-md transition-all duration-300 flex flex-col justify-between relative group text-gray-900">
                                                
                                                <!-- Heart Badge top-left -->
                                                <div class="absolute top-2 left-2 w-7 h-7 rounded-full bg-[#FFC000]/10 hover:bg-[#FFC000]/25 text-[#FFC000] flex items-center justify-center cursor-pointer transition-colors duration-200">
                                                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                                    </svg>
                                                </div>

                                                <!-- Image -->
                                                <div class="w-full h-24 rounded-2xl overflow-hidden bg-gray-50 flex items-center justify-center mb-2">
                                                    <img src="{{ $prodImageUrl }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="{{ $prod->name }}">
                                                </div>

                                                <!-- Price and metadata -->
                                                <div class="text-right flex flex-col justify-end">
                                                    <span class="text-sm font-extrabold text-[#002060] block leading-tight">{{ $currentPrice }}</span>
                                                    @if ($hasDiscount)
                                                        <span class="text-[10px] text-gray-400 line-through block leading-none">{{ $originalPrice }}</span>
                                                    @else
                                                        <span class="h-2.5 block"></span>
                                                    @endif
                                                    
                                                    <span class="text-[9px] text-gray-500 block font-semibold mt-1">{{ $totalSales }} تم البيع</span>
                                                    
                                                    <div class="flex items-center justify-end gap-0.5 text-[10px] mt-0.5">
                                                        <span class="font-bold text-gray-700">{{ $averageRating }}</span>
                                                        <svg class="w-3 h-3 text-[#FFC000] fill-current" viewBox="0 0 24 24">
                                                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                                        </svg>
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>

                                    <!-- Benefits section footer -->
                                    <div class="grid grid-cols-3 gap-1.5 border-t border-white/20 pt-4 mt-3">
                                        <div class="flex flex-col items-center text-center">
                                            <svg class="w-5 h-5 text-[#FFC000] mb-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                            <span class="text-[11px] font-bold text-white leading-tight">دعم عملاء 24/7</span>
                                            <span class="text-[9px] text-white/70">نحن هنا لمساعدتك</span>
                                        </div>
                                        <div class="flex flex-col items-center text-center">
                                            <svg class="w-5 h-5 text-[#FFC000] mb-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                            </svg>
                                            <span class="text-[11px] font-bold text-white leading-tight">ضمان الجودة</span>
                                            <span class="text-[9px] text-white/70">منتجات أصلية 100%</span>
                                        </div>
                                        <div class="flex flex-col items-center text-center">
                                            <svg class="w-5 h-5 text-[#FFC000] mb-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                            <span class="text-[11px] font-bold text-white leading-tight">شحن سريع</span>
                                            <span class="text-[9px] text-white/70">توصيل لجميع الطلبات</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                @break
            @case ($customization::STATIC_CONTENT)
                <!-- push style -->
                @if (! empty($data['css']))
                    @push ('styles')
                        <style>
                            {{ $data['css'] }}
                        </style>
                    @endpush
                @endif

                <!-- render html -->
                @if (! empty($data['html']))
                    {!! $data['html'] !!}
                @endif

                @break
            @case ($customization::CATEGORY_CAROUSEL)
                <!-- Categories carousel -->
                <x-shop::categories.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.categories.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.home.index')"
                    aria-label="{{ trans('shop::app.home.index.categories-carousel') }}"
                />

                @break
            @case ($customization::PRODUCT_CAROUSEL)
                <!-- Product Carousel -->
                <x-shop::products.carousel
                    :title="$data['title'] ?? ''"
                    :src="route('shop.api.products.index', $data['filters'] ?? [])"
                    :navigation-link="route('shop.search.index', $data['filters'] ?? [])"
                    aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                />

                @break
        @endswitch
    @endforeach
</x-shop::layouts>
