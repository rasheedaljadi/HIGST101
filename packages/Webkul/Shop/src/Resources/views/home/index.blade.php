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
@endpush

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



                <!-- Flash Deals Section -->
                @include('flash_deal::shop.components.flash-deals', [
                    'deal' => app(\Webkul\FlashDeal\Repositories\FlashDealRepository::class)->getActiveDeals()->first()
                ])

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
                @if (($data['display_mode'] ?? '') === 'grid' || ($data['mode'] ?? '') === 'grid' || ($data['card_style'] ?? '') === 'grid')
                    <!-- Products Grid Section (قسم المنتجات بتنسيق شبكي رسمياً من السمات) -->
                    <div class="container px-4 py-8 mx-auto max-w-[1440px]">
                        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-100 dark:border-gray-800">
                            <h2 class="text-xl sm:text-2xl font-bold text-[#001A54] dark:text-white">
                                {{ $customization->name ?? ($data['title'] ?? 'المنتجات') }}
                            </h2>
                            <a 
                                href="{{ route('shop.search.index', $data['filters'] ?? []) }}"
                                class="text-xs sm:text-sm font-bold text-[#001A54] hover:opacity-80 border border-gray-200 dark:border-gray-700 px-4 py-1.5 rounded-full transition-opacity flex items-center gap-1"
                            >
                                @lang('shop::app.home.index.view-all')
                                <span class="icon-arrow-right rtl:rotate-180 text-xs"></span>
                            </a>
                        </div>

                        <!-- Vue Products Grid Component -->
                        <v-products-grid
                            src="{{ route('shop.api.products.index', array_merge(['limit' => 12], $data['filters'] ?? [])) }}"
                        ></v-products-grid>
                    </div>
                @else
                    <!-- Product Carousel -->
                    <x-shop::products.carousel
                        :title="$data['title'] ?? ''"
                        :src="route('shop.api.products.index', $data['filters'] ?? [])"
                        :navigation-link="route('shop.search.index', $data['filters'] ?? [])"
                        card-style="{{ $data['card_style'] ?? 'standard' }}"
                        aria-label="{{ trans('shop::app.home.index.product-carousel') }}"
                    />
                @endif

                @break
        @endswitch
    @endforeach

    @pushonce('scripts')
        <x-shop::products.card class="hidden" />

        <script type="text/x-template" id="v-products-grid-template">
            <div v-if="isLoading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                <div v-for="n in 8" :key="n" class="h-[380px] bg-gray-100 dark:bg-gray-800 rounded-2xl animate-pulse"></div>
            </div>
            <div v-else-if="products.length" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
                <v-product-card
                    :mode="'grid'"
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                ></v-product-card>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
                لا توجد منتجات متاحة حالياً
            </div>
        </script>

        <script type="module">
            app.component('v-products-grid', {
                template: '#v-products-grid-template',

                props: ['src'],

                data() {
                    return {
                        isLoading: true,
                        products: [],
                    };
                },

                mounted() {
                    this.getProducts();
                },

                methods: {
                    getProducts() {
                        this.$axios.get(this.src)
                            .then(response => {
                                this.isLoading = false;
                                this.products = response.data.data;
                            })
                            .catch(error => {
                                this.isLoading = false;
                                console.error(error);
                            });
                    },
                },
            });
        </script>
    @endpushonce
</x-shop::layouts>
