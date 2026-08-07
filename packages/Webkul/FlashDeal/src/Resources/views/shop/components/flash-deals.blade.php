@if (! empty($deal) && $deal->products->count() > 0)
    @php
        $productImageHelper = app(\Webkul\Product\ProductImage::class);
        $endsAtIso = $deal->ends_at?->toISOString() ?? now()->addHours(24)->toISOString();
    @endphp

    <div 
        x-data="{
            endsAt: new Date('{{ $endsAtIso }}').getTime(),
            hours: '00',
            minutes: '00',
            seconds: '00',
            timer: null,
            init() {
                this.updateTimer();
                this.timer = setInterval(() => this.updateTimer(), 1000);
            },
            updateTimer() {
                const now = new Date().getTime();
                const distance = this.endsAt - now;
                
                if (distance < 0) {
                    this.hours = '00';
                    this.minutes = '00';
                    this.seconds = '00';
                    if (this.timer) clearInterval(this.timer);
                    return;
                }

                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                this.hours = h < 10 ? '0' + h : '' + h;
                this.minutes = m < 10 ? '0' + m : '' + m;
                this.seconds = s < 10 ? '0' + s : '' + s;
            }
        }"
        x-init="init()"
        class="py-10 bg-gradient-to-r from-[#001845] via-[#002060] to-[#0B2562] text-white relative overflow-hidden my-8 rounded-3xl shadow-2xl mx-4 md:mx-6"
    >
        <!-- Background Decorative Element -->
        <div class="absolute -top-12 -right-12 w-64 h-64 bg-[#FFC000]/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-[#FFB900]/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-[1320px] mx-auto px-4 md:px-8 relative z-10">
            <!-- Section Header: Title & Live Countdown Timer -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-8 border-b border-white/10 pb-6">
                
                <!-- Right: Section Title & Flash Icon -->
                <div class="flex items-center gap-3 text-right">
                    <div class="w-12 h-12 rounded-2xl bg-[#FFC000] text-[#002060] flex items-center justify-center shadow-lg shadow-[#FFC000]/30 animate-pulse">
                        <svg class="w-7 h-7 fill-current" viewBox="0 0 24 24">
                            <path d="M7 2v11h3v9l7-12h-4l4-8z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl md:text-3xl font-black tracking-wide text-white">
                            {{ $deal->title ?? 'العروض السريعة' }}
                        </h2>
                        <p class="text-xs md:text-sm text-gray-300 font-medium mt-0.5">
                            خصومات استثنائية لفترة محدودة جداً - سارع بالشراء قبل انتهاء الكمية!
                        </p>
                    </div>
                </div>

                <!-- Left: Live Countdown Timer Widget -->
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-md px-5 py-2.5 rounded-2xl border border-white/15 shadow-inner">
                    <span class="text-xs font-extrabold text-[#FFC000] ml-2">ينتهي العرض خلال:</span>
                    
                    <!-- Hours -->
                    <div class="flex flex-col items-center">
                        <span class="text-xl font-black text-white bg-[#001040] px-2.5 py-1 rounded-lg min-w-[38px] text-center shadow" x-text="hours">00</span>
                        <span class="text-[10px] text-gray-300 font-bold mt-0.5">ساعة</span>
                    </div>
                    <span class="text-xl font-bold text-[#FFC000] mb-3">:</span>

                    <!-- Minutes -->
                    <div class="flex flex-col items-center">
                        <span class="text-xl font-black text-white bg-[#001040] px-2.5 py-1 rounded-lg min-w-[38px] text-center shadow" x-text="minutes">00</span>
                        <span class="text-[10px] text-gray-300 font-bold mt-0.5">دقيقة</span>
                    </div>
                    <span class="text-xl font-bold text-[#FFC000] mb-3">:</span>

                    <!-- Seconds -->
                    <div class="flex flex-col items-center">
                        <span class="text-xl font-black text-white bg-[#001040] px-2.5 py-1 rounded-lg min-w-[38px] text-center shadow text-[#FFC000]" x-text="seconds">00</span>
                        <span class="text-[10px] text-gray-300 font-bold mt-0.5">ثانية</span>
                    </div>
                </div>

            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
                @foreach ($deal->products as $dealProduct)
                    @php
                        $product = $dealProduct->product;
                        if (! $product) continue;

                        $imageUrl = $productImageHelper->getProductBaseImage($product)['medium_image_url'] ?? asset('themes/default/assets/images/placeholder.png');
                        $productUrl = route('shop.product_or_category.index', $product->url_key);
                        
                        $originalPrice = $product->price;
                        $flashPrice = $dealProduct->flash_price;

                        $discountPercent = 0;
                        if ($originalPrice > $flashPrice && $originalPrice > 0) {
                            $discountPercent = round((($originalPrice - $flashPrice) / $originalPrice) * 100);
                        }

                        $allocationQty = max(1, (int) $dealProduct->allocation_qty);
                        $soldQty = (int) $dealProduct->sold_qty;
                        $soldPercent = min(100, round(($soldQty / $allocationQty) * 100));
                    @endphp

                    <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 shadow-xl hover:shadow-2xl transition-all duration-300 flex flex-col justify-between relative group text-gray-900 dark:text-white border border-gray-100 dark:border-gray-800">
                        
                        <!-- Discount Badge (Top Right) -->
                        @if ($discountPercent > 0)
                            <div class="absolute top-3 right-3 bg-red-600 text-white text-xs font-black px-2.5 py-1 rounded-full shadow-md z-10 flex items-center gap-0.5">
                                <span>خصم</span>
                                <span>{{ $discountPercent }}%</span>
                            </div>
                        @endif

                        <!-- Product Image Container -->
                        <a href="{{ $productUrl }}" class="block w-full h-44 rounded-xl overflow-hidden bg-gray-50 dark:bg-gray-800 relative mb-3 group-hover:scale-[1.02] transition-transform duration-300">
                            <img 
                                src="{{ $imageUrl }}" 
                                alt="{{ $product->name }}"
                                class="w-full h-full object-contain p-2"
                                loading="lazy"
                            />
                        </a>

                        <!-- Product Name -->
                        <a href="{{ $productUrl }}" class="block mb-2">
                            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 line-clamp-2 hover:text-[#002060] dark:hover:text-[#FFC000] transition-colors leading-snug">
                                {{ $product->name }}
                            </h3>
                        </a>

                        <!-- Pricing Container -->
                        <div class="flex items-baseline gap-2 mb-3">
                            <span class="text-lg font-black text-red-600 dark:text-red-400">
                                {{ core()->currency($flashPrice) }}
                            </span>

                            @if ($originalPrice > $flashPrice)
                                <span class="text-xs text-gray-400 line-through font-semibold">
                                    {{ core()->currency($originalPrice) }}
                                </span>
                            @endif
                        </div>

                        <!-- FOMO Progress Bar -->
                        <div class="mt-auto">
                            <div class="flex items-center justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-1">
                                <span>مباع: {{ $soldQty }} من {{ $allocationQty }}</span>
                                <span class="text-amber-600 dark:text-amber-400 font-extrabold">{{ $soldPercent }}%</span>
                            </div>

                            <div class="w-full h-2.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden p-0.5 border border-gray-200 dark:border-gray-700">
                                <div 
                                    class="h-full bg-gradient-to-r from-[#FFC000] to-amber-500 rounded-full transition-all duration-500"
                                    style="width: {{ $soldPercent }}%"
                                ></div>
                            </div>
                        </div>

                        <!-- Buy Button -->
                        <a 
                            href="{{ $productUrl }}" 
                            class="mt-4 w-full bg-[#002060] hover:bg-[#001440] text-white font-bold py-2.5 px-4 rounded-xl text-center text-xs transition-colors duration-200 flex items-center justify-center gap-1.5 shadow-md"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>اشتري الآن قبل نفاد الكمية</span>
                        </a>

                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
