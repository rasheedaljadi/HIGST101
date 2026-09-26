{!! view_render_event('bagisto.shop.components.layouts.header.desktop.top.before') !!}

<div class="w-full highest-navy text-white text-xs select-none border-b border-[#001440]" style="background-color: #001A54 !important;">
    <div class="flex h-[40px] min-h-[40px] w-full items-center justify-between px-4 sm:px-8 lg:px-10 xl:px-14 font-medium">
        
        <!-- Right Section (in RTL): Services & App Download -->
        <div class="flex items-center gap-2.5 xl:gap-3.5 text-xs text-white">
            <!-- App Download with Popover -->
            <div class="relative group flex items-center">
                <div
                    class="flex items-center gap-1.5 cursor-pointer py-1 text-white hover:text-yellow-300 transition-colors select-none"
                    role="button"
                    tabindex="0"
                    aria-haspopup="true"
                >
                    <svg class="w-3.5 h-3.5 highest-yellow-text shrink-0" style="color: #F5B800 !important;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
                        <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    </svg>
                    <span>تحميل تطبيق HIGHEST</span>
                </div>

                <!-- Hover Popover Card -->
                <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform translate-y-2 group-hover:translate-y-0 absolute top-full rtl:right-0 ltr:left-0 pt-2 z-[1000] pointer-events-none group-hover:pointer-events-auto">
                    <div class="w-[360px] bg-white text-gray-900 rounded-2xl shadow-[0_15px_50px_rgba(0,0,0,0.25)] border border-gray-100 p-4">
                        @include('shop::components.layouts.header.desktop.app-download-card')
                    </div>
                </div>
            </div>

            <span class="h-3 w-px bg-white/20"></span>

            <!-- Fast Shipping -->
            <div class="flex items-center gap-1.5 py-1">
                <svg class="w-3.5 h-3.5 highest-yellow-text shrink-0" style="color: #F5B800 !important;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="1" y="3" width="15" height="13"></rect>
                    <polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon>
                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                </svg>
                <span>شحن سريع إلى جميع المحافظات</span>
            </div>

            <span class="h-3 w-px bg-white/20"></span>

            <!-- 100% Original Products -->
            <div class="flex items-center gap-1.5 py-1">
                <svg class="w-3.5 h-3.5 highest-yellow-text shrink-0" style="color: #F5B800 !important;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <polyline points="9 12 11 14 15 10"></polyline>
                </svg>
                <span>منتجات أصلية 100%</span>
            </div>

            <span class="h-3 w-px bg-white/20"></span>

            <!-- 24/7 Technical Support -->
            <div class="flex items-center gap-1.5 py-1">
                <svg class="w-3.5 h-3.5 highest-yellow-text shrink-0" style="color: #F5B800 !important;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
                </svg>
                <span>دعم فني متواصل</span>
            </div>
        </div>

        <!-- Left Section (in RTL): Currency & Language Switchers -->
        <div class="flex items-center gap-3 text-xs text-white">
            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.top.currency_switcher.before') !!}

            <!-- Currency Switcher -->
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'left' : 'right' }}">
                <x-slot:toggle>
                    <div class="flex cursor-pointer items-center gap-1.5 py-1 text-white hover:text-yellow-300 transition-colors" role="button">
                        <span class="text-xs">🇺🇸</span>
                        <span class="font-semibold">{{ core()->getCurrentCurrencyCode() . ' ' . core()->getCurrentCurrency()->symbol }}</span>
                        <svg class="w-3 h-3 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </x-slot>

                <x-slot:content class="journal-scroll max-h-[400px] !p-0 text-gray-800 shadow-xl rounded-lg">
                    <div class="my-1 grid gap-0.5 overflow-auto max-md:my-0 sm:max-h-[400px]">
                        @foreach (core()->getCurrentChannel()->currencies as $currency)
                            <a
                                class="cursor-pointer px-4 py-2 text-sm hover:bg-gray-100 flex items-center justify-between {{ $currency->code == core()->getCurrentCurrencyCode() ? 'bg-gray-100 font-bold highest-navy-text' : '' }}"
                                href="?currency={{ $currency->code }}"
                            >
                                <span>{{ $currency->name ?? $currency->code }}</span>
                                <span class="text-xs text-gray-500">{{ $currency->symbol . ' ' . $currency->code }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-slot>
            </x-shop::dropdown>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.top.currency_switcher.after') !!}

            <span class="h-3 w-px bg-white/20"></span>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.top.locale_switcher.before') !!}

            <!-- Language Switcher -->
            <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
                <x-slot:toggle>
                    <div class="flex cursor-pointer items-center gap-1.5 py-1 text-white hover:text-yellow-300 transition-colors" role="button">
                        <svg class="w-3.5 h-3.5 highest-yellow-text shrink-0" style="color: #F5B800 !important;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                        </svg>
                        <span class="font-semibold">{{ core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name') }}</span>
                        <svg class="w-3 h-3 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </x-slot>

                <x-slot:content class="journal-scroll max-h-[400px] !p-0 text-gray-800 shadow-xl rounded-lg">
                    <div class="my-1 grid gap-0.5 overflow-auto max-md:my-0 sm:max-h-[400px]">
                        @foreach (core()->getCurrentChannel()->locales()->orderBy('name')->get() as $locale)
                            <a
                                class="flex cursor-pointer items-center gap-2.5 px-4 py-2 text-sm hover:bg-gray-100 {{ $locale->code == app()->getLocale() ? 'bg-gray-100 font-bold highest-navy-text' : '' }}"
                                href="?locale={{ $locale->code }}"
                            >
                                <img
                                    src="{{ $locale->logo_url ?: bagisto_asset('images/default-language.svg') }}"
                                    width="20"
                                    height="14"
                                    class="rounded-sm"
                                    alt="{{ $locale->name }}"
                                />
                                <span>{{ $locale->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </x-slot>
            </x-shop::dropdown>

            {!! view_render_event('bagisto.shop.components.layouts.header.desktop.top.locale_switcher.after') !!}
        </div>
    </div>
</div>

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.top.after') !!}