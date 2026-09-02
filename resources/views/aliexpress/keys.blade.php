<x-admin::layouts>
    <x-slot:title>
        إدارة المفاتيح
    </x-slot>

    <div class="flex flex-col gap-6 pt-3 px-2 sm:px-4 lg:pt-3 lg:px-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white font-sans">
                إدارة مفاتيح AliExpress
            </h1>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 p-4 text-emerald-600 dark:text-emerald-400 font-sans shadow-sm">
                {!! session('success') !!}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20 p-4 text-red-800 dark:text-red-400 font-sans shadow-sm">
                {!! session('error') !!}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20 p-4 text-red-800 dark:text-red-400 font-sans shadow-sm">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Connection status --}}
        <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-bold text-gray-700 dark:text-gray-300">حالة الاتصال:</span>

                    @if ($connected)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 px-3.5 py-1 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> متصل
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-900/50 px-3.5 py-1 text-sm font-semibold text-red-800 dark:text-red-400">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span> غير متصل
                        </span>
                    @endif

                    @if ($connected && $tokenExpiresAt)
                        <span class="text-sm text-gray-500 dark:text-gray-400 font-sans">
                            {{ $tokenAccount ? 'الحساب: '.$tokenAccount.' — ' : '' }}
                            صالح حتى: {{ $tokenExpiresAt->format('Y-m-d H:i') }}
                        </span>
                    @endif
                </div>

                <a
                    href="{{ route('aliexpress.oauth.connect') }}"
                    class="primary-button py-2 px-4 focus:ring-1 focus:ring-amber-500 focus:outline-none transition-all"
                >
                    {{ $connected ? 'إعادة المصادقة' : 'مصادقة الحساب' }}
                </a>
            </div>

            <p class="text-sm text-gray-500 dark:text-gray-400 font-sans">
                احفظ مفتاح التطبيق والسر أولاً، ثم اضغط "مصادقة الحساب" لربط متجرك بحساب AliExpress.
            </p>
        </div>

        {{-- Tabs Navigation --}}
        <div class="flex border-b border-gray-200 dark:border-gray-800 gap-2 mb-4">
            <button
                type="button"
                id="tab-btn-keys"
                onclick="switchTab('keys')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none"
            >
                إدارة المفاتيح
            </button>
            <button
                type="button"
                id="tab-btn-sync"
                onclick="switchTab('sync')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none"
            >
                إدارة المزامنة
            </button>
            <button
                type="button"
                id="tab-btn-shipping"
                onclick="switchTab('shipping')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none"
            >
                خيارات الشحن
            </button>
            <button
                type="button"
                id="tab-btn-warehouse"
                onclick="switchTab('warehouse')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none"
            >
                عناوين الشحن
            </button>
            <button
                type="button"
                id="tab-btn-pricing"
                onclick="switchTab('pricing')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none"
            >
                محرك التسعير
            </button>
            <button
                type="button"
                id="tab-btn-cost-variance"
                onclick="switchTab('cost-variance')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none"
            >
                فروق التكلفة
            </button>
            <button
                type="button"
                id="tab-btn-communications"
                onclick="switchTab('communications')"
                class="tab-btn py-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-all font-sans cursor-pointer focus:outline-none flex items-center gap-1.5"
            >
                <span>الاتصالات</span>
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                    {{ number_format($todayTotalCalls ?? 0) }}
                </span>
            </button>
        </div>

        {{-- Panel 1: Keys --}}
        <div id="tab-panel-keys" class="tab-panel hidden">
            <form method="POST" action="{{ route('admin.dropshipping.keys.store') }}">
                @csrf
                <input type="hidden" name="section" value="keys" />

                <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white">إعدادات مفاتيح AliExpress</h2>

                    {{-- App Key --}}
                    <div class="flex flex-col gap-1">
                        <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            مفتاح التطبيق (App Key)
                        </label>
                        <input
                            type="text"
                            name="app_key"
                            value="{{ old('app_key', $settings->app_key) }}"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            placeholder="App Key"
                        />
                    </div>

                    {{-- App Secret (masked; blank keeps existing) --}}
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            السر (App Secret)
                        </label>
                        <input
                            type="password"
                            name="app_secret"
                            autocomplete="new-password"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            placeholder="{{ $settings->app_secret ? '•••••••• (محفوظ — اتركه فارغاً للإبقاء عليه)' : 'App Secret' }}"
                        />
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                            السر مخزّن مشفّراً. اتركه فارغاً للإبقاء على القيمة الحالية.
                        </p>
                    </div>

                    {{-- Callback URL (read-only, auto-derived) --}}
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            رابط الكول باك (Callback URL)
                        </label>
                        <div class="mt-1 flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="{{ $callbackUrl }}"
                                class="w-full cursor-not-allowed rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-850 dark:text-gray-400"
                            />
                        </div>
                        <p class="text-xs text-gray-455 dark:text-gray-500 mt-0.5">
                            يُشتق تلقائياً، وغير قابل للتعديل. سجّل هذا الرابط حرفياً في لوحة AliExpress Open Platform.
                        </p>
                    </div>

                    {{-- Authorize URL --}}
                    <div class="flex flex-col gap-1">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            رابط المصادقة (Authorize URL)
                        </label>
                        <input
                            type="text"
                            name="authorize_url"
                            value="{{ old('authorize_url', $settings->authorize_url ?: config('aliexpress.authorize_url')) }}"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            placeholder="https://api-sg.aliexpress.com/oauth/authorize"
                        />
                    </div>

                    <div class="flex items-center pt-2">
                        <button
                            type="submit"
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm"
                        >
                            حفظ مفاتيح التطبيق
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Panel 2: Sync --}}
        <div id="tab-panel-sync" class="tab-panel hidden">
            <form method="POST" action="{{ route('admin.dropshipping.keys.store') }}">
                @csrf
                <input type="hidden" name="section" value="sync" />

                <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white">إعدادات المزامنة المجدولة</h2>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                تكرار المزامنة التلقائية
                            </label>
                            <select
                                name="sync_schedule"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            >
                                <option value="twice-daily" {{ old('sync_schedule', $settings->sync_schedule) === 'twice-daily' ? 'selected' : '' }}>مرتين يومياً (03:00 ص و 03:00 م)</option>
                                <option value="daily" {{ old('sync_schedule', $settings->sync_schedule) === 'daily' || empty($settings->sync_schedule) || old('sync_schedule', $settings->sync_schedule) === 'hourly' ? 'selected' : '' }}>مرة واحدة يومياً (03:30 فجراً - موصى به)</option>
                            </select>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                اختر معدل تكرار تشغيل المزامنة التلقائية لأسعار ومخزون المنتجات.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 py-2 border-t border-gray-100 dark:border-gray-800">
                        <div>
                            <label for="sync_enabled" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer block select-none">
                                تفعيل المزامنة المجدولة التلقائية لجميع المنتجات
                            </label>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 select-none">
                                مزامنة مستمرة للأسعار ومستويات المخزون تلقائياً بالخلفية.
                            </p>
                        </div>
                        <div class="flex items-center shrink-0">
                            <input
                                type="hidden"
                                name="sync_enabled"
                                value="0"
                            />
                            <label for="sync_enabled" class="relative inline-flex cursor-pointer select-none items-center p-1.5 hover:bg-amber-50 dark:hover:bg-amber-950/30 rounded-lg transition-all">
                                <input
                                    type="checkbox"
                                    id="sync_enabled"
                                    name="sync_enabled"
                                    value="1"
                                    {{ old('sync_enabled', $settings->sync_enabled) ? 'checked' : '' }}
                                    class="peer hidden"
                                />
                                <span class="icon-uncheckbox peer-checked:icon-checked text-3xl text-gray-400 peer-checked:text-amber-600 transition-colors"></span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center pt-2">
                        <button
                            type="submit"
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm cursor-pointer"
                        >
                            حفظ إعدادات المزامنة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Panel 3: Shipping Options --}}
        <div id="tab-panel-shipping" class="tab-panel hidden">
            <form method="POST" action="{{ route('admin.dropshipping.keys.store') }}">
                @csrf
                <input type="hidden" name="section" value="shipping" />

                <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white">خيارات الشحن</h2>

                    <div class="grid grid-cols-1 gap-6">
                        <div class="flex flex-col gap-1">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                أيام التوصيل الإضافية
                            </label>
                            <input
                                type="number"
                                min="0"
                                max="365"
                                name="shipping_extra_days"
                                value="{{ old('shipping_extra_days', $settings->shipping_extra_days ?? 0) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="0"
                            />
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                تُضاف إلى مدة شحن AliExpress لتعكس مدة النقل الداخلي (مثال: +7 أيام).
                            </p>
                        </div>
                    </div>

                    {{-- Include AliExpress Shipping in Product Price Toggle Card --}}
                    <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-5 dark:border-blue-900/50 dark:bg-blue-950/20 flex flex-col gap-4 transition-all">
                        {{-- Row 1: Include shipping in price --}}
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h2m-8 0a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                </div>
                                <label for="include_shipping_in_price" class="cursor-pointer select-none">
                                    <span class="block text-sm font-bold text-gray-800 dark:text-white font-sans">
                                        دمج تكلفة شحن AliExpress تلقائياً في سعر بيع المنتج (Free Shipping Model)
                                    </span>
                                    <span class="block text-xs text-gray-600 dark:text-gray-400 mt-0.5 font-sans leading-relaxed">
                                        عند التفعيل: يقوم محرك التسعير بجلب تكلفة شحن المورد وإضافتها إلى تكلفة المنتج قبل تطبيق هامش الربح، ليظهر السعر النهائي للعميل في المتجر شاملاً رسوم الشحن بالكامل.
                                    </span>
                                </label>
                            </div>
                            <div class="flex items-center shrink-0">
                                <input
                                    type="hidden"
                                    name="include_shipping_in_price"
                                    value="0"
                                />
                                <label for="include_shipping_in_price" class="relative inline-flex cursor-pointer select-none items-center p-1.5 hover:bg-blue-100/60 dark:hover:bg-blue-900/40 rounded-lg transition-all">
                                    <input
                                        type="checkbox"
                                        id="include_shipping_in_price"
                                        name="include_shipping_in_price"
                                        value="1"
                                        {{ old('include_shipping_in_price', $settings->include_shipping_in_price) ? 'checked' : '' }}
                                        class="peer hidden"
                                    />
                                    <span class="icon-uncheckbox peer-checked:icon-checked text-3xl text-gray-400 peer-checked:text-blue-600 transition-colors"></span>
                                </label>
                            </div>
                        </div>

                        {{-- Row 2: Choice Exemption --}}
                        <div class="border-t border-blue-200/80 dark:border-blue-900/40 pt-3.5 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <label for="exclude_choice_from_shipping_price" class="cursor-pointer select-none">
                                    <span class="block text-xs font-bold text-gray-800 dark:text-white font-sans">
                                        استثناء منتجات Choice (التزام AliExpress) من دمج تكلفة الشحن
                                    </span>
                                    <span class="block text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 font-sans leading-relaxed">
                                        عند التفعيل: المنتجات التي تحمل علامة Choice وشحن التزام AliExpress لن يتم إضافة تكلفة شحنها إلى سعر بيع المنتج، وستبقى معتمدة على سعر التكلفة المباشر فقط.
                                    </span>
                                </label>
                            </div>
                            <div class="flex items-center shrink-0">
                                <input
                                    type="hidden"
                                    name="exclude_choice_from_shipping_price"
                                    value="0"
                                />
                                <label for="exclude_choice_from_shipping_price" class="relative inline-flex cursor-pointer select-none items-center p-1.5 hover:bg-amber-100/60 dark:hover:bg-amber-900/40 rounded-lg transition-all">
                                    <input
                                        type="checkbox"
                                        id="exclude_choice_from_shipping_price"
                                        name="exclude_choice_from_shipping_price"
                                        value="1"
                                        {{ old('exclude_choice_from_shipping_price', $settings->exclude_choice_from_shipping_price ?? true) ? 'checked' : '' }}
                                        class="peer hidden"
                                    />
                                    <span class="icon-uncheckbox peer-checked:icon-checked text-3xl text-gray-400 peer-checked:text-amber-600 transition-colors"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <input
                        type="hidden"
                        name="shipping_enabled"
                        value="{{ old('shipping_enabled', $settings->shipping_enabled ? '1' : '0') }}"
                    />

                    <div class="flex items-center pt-2">
                        <button
                            type="submit"
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm cursor-pointer"
                        >
                            حفظ خيارات الشحن
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Panel 4: Warehouse Address --}}
        <div id="tab-panel-warehouse" class="tab-panel hidden flex flex-col gap-6">
            <form method="POST" action="{{ route('admin.dropshipping.keys.store') }}">
                @csrf
                <input type="hidden" name="section" value="warehouse" />

                <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                    <div>
                        <h2 class="text-base font-bold text-gray-800 dark:text-white">عنوان شحن مستودع هايست (AliExpress Delivery Address)</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans">
                            هذا هو عنوان المستودع المعتمد الذي سيتم شحن كافة طلبات الشراء من الموردين في AliExpress إليه تلقائياً.
                        </p>
                    </div>

                    {{-- Section 1: Identification & Contact --}}
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4 flex flex-col gap-4">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 font-sans flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-blue-600"></span>
                            بيانات التعريف والمسؤول والاتصال
                        </h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {{-- Company / Warehouse Name --}}
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    اسم المستودع / الشركة التجارية (Company / Warehouse Name)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_company_name"
                                    value="{{ old('warehouse_company_name', $warehouseMeta['company_name'] ?? $warehouse?->name) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="Al-Miftah Transport Office / Higest Warehouse"
                                />
                                <p class="text-[11px] text-gray-400 font-sans">الاسم الرسمي للمنشأة/المستودع الذي يظهر في بوليصة شحن المورد.</p>
                            </div>

                            {{-- Contact Person Name --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    اسم مسؤول الاستلام (Contact Person Name)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_contact_name"
                                    value="{{ old('warehouse_contact_name', $warehouse?->contact_name) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="Mostafa Bamashmous"
                                    required
                                />
                                <p class="text-[11px] text-gray-400 font-sans">اسم الشخص المخول باستلام الطرود والتوقيع عليها.</p>
                            </div>

                            {{-- Contact Email --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    البريد الإلكتروني للمستودع (Email)
                                </label>
                                <input
                                    type="email"
                                    name="warehouse_contact_email"
                                    value="{{ old('warehouse_contact_email', $warehouse?->contact_email) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="warehouse@hayest.com"
                                    required
                                />
                            </div>

                            {{-- Phone Country & Number --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    رقم هاتف المستودع ومفتاح الاتصال (Phone & Dial Code)
                                </label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input
                                        type="text"
                                        name="warehouse_phone_country"
                                        value="{{ old('warehouse_phone_country', $warehouseMeta['phone_country'] ?? '966') }}"
                                        class="w-24 rounded-md border border-gray-300 px-2.5 py-2 text-sm text-center dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                        placeholder="966"
                                        title="رمز النداء الدولي (مثال: 966 أو 967)"
                                    />
                                    <input
                                        type="text"
                                        name="warehouse_contact_number"
                                        value="{{ old('warehouse_contact_number', $warehouse?->contact_number) }}"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                        placeholder="572124578"
                                        required
                                    />
                                </div>
                                <p class="text-[11px] text-gray-400 font-sans">المفتاح الدولي (مثال: 966) يليه رقم الهاتف المباشر.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Address & Location Details --}}
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4 flex flex-col gap-4">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 font-sans flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-emerald-600"></span>
                            العنوان الجغرافي وتفاصيل الشارع والمبنى
                        </h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {{-- Street Address 1 --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    عنوان الشارع الرئيسي (Street Address 1)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_street"
                                    value="{{ old('warehouse_street', $warehouse?->street) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="3455 Ahmad Bin Rushd St"
                                    required
                                />
                            </div>

                            {{-- District --}}
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    الحي / المنطقة الفرعية (District)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_district"
                                    value="{{ old('warehouse_district', $warehouseMeta['district'] ?? 'Al Aziziyah') }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="Al Aziziyah / حي العزيزية"
                                />
                            </div>

                            {{-- Address Line 2 / Building Details --}}
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    سطر العنوان 2 / رقم المبنى الإضافي (Address Line 2)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_address2"
                                    value="{{ old('warehouse_address2', $warehouseMeta['address2'] ?? '') }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="Building 7664 / Southern Ring Rd"
                                />
                                <p class="text-[11px] text-gray-400 font-sans">تفاصيل المبنى الإضافية أو المعالم القريبة (اختياري).</p>
                            </div>

                            {{-- City --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    المدينة (City)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_city"
                                    value="{{ old('warehouse_city', $warehouse?->city) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="Riyadh"
                                    required
                                />
                            </div>

                            {{-- State / Province --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    المنطقة / المقاطعة (State / Province)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_state"
                                    value="{{ old('warehouse_state', $warehouse?->state) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="Riyadh"
                                    required
                                />
                            </div>

                            {{-- Country Code --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    دولة المستودع (Country Code)
                                </label>
                                <select
                                    name="warehouse_country"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    required
                                >
                                    <option value="SA" {{ old('warehouse_country', $warehouse?->country) === 'SA' ? 'selected' : '' }}>المملكة العربية السعودية (SA)</option>
                                    <option value="YE" {{ old('warehouse_country', $warehouse?->country) === 'YE' ? 'selected' : '' }}>اليمن (YE)</option>
                                    <option value="US" {{ old('warehouse_country', $warehouse?->country) === 'US' ? 'selected' : '' }}>الولايات المتحدة (US)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Postal Code & National Address --}}
                    <div class="border-t border-gray-100 dark:border-gray-800 pt-4 flex flex-col gap-4">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 font-sans flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                            الرمز البريدي والعنوان الوطني السعودي (SPL)
                        </h3>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            {{-- Standard Postal Code --}}
                            <div class="flex flex-col gap-1">
                                <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    الرمز البريدي القياسي للمنطقة (Postal Code / ZIP)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_postcode"
                                    value="{{ old('warehouse_postcode', $warehouse?->postcode) }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                                    placeholder="14512"
                                    required
                                />
                                <p class="text-[11px] text-gray-400 font-sans">الرمز البريدي الرسمي المكون من 5 أرقام (مثال: 14512 للعزيزية بالرياض).</p>
                            </div>

                            {{-- Saudi Short National Address --}}
                            <div class="flex flex-col gap-1">
                                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                    العنوان الوطني السعودي المختصر (Short National Address)
                                </label>
                                <input
                                    type="text"
                                    name="warehouse_short_address"
                                    value="{{ old('warehouse_short_address', $warehouseMeta['short_address'] ?? 'RMAD3455') }}"
                                    class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans uppercase font-mono tracking-wide"
                                    placeholder="RMAD3455"
                                    maxlength="8"
                                />
                                <p class="text-[11px] text-amber-600 dark:text-amber-500 font-sans">
                                    كود سبل SPL المختصر المكون من 8 خانات (4 أحرف و 4 أرقام مثل RMAD3455). يُحقن تلقائياً في nat_addr لدى AliExpress.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center pt-2">
                        <button
                            type="submit"
                            class="primary-button py-2.5 px-7 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm cursor-pointer shadow-sm"
                        >
                            حفظ وتحديث عنوان الشحن
                        </button>
                    </div>
                </div>
            </form>

            {{-- Live Injected Payload Preview Card --}}
            <div class="p-6 border border-blue-200 dark:border-blue-900/60 rounded-lg shadow-sm bg-gradient-to-b from-blue-50/40 to-white dark:from-gray-900 dark:to-gray-900 flex flex-col gap-4">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-blue-100 dark:border-blue-900/40 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-800 dark:text-white font-sans">
                                المحتوى المحقون المرسل إلى AliExpress API (Live Injected Payload)
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">
                                هذه هي بنية البيانات اللوجستية التي يولدها النظام ويحقنها حرفياً في طلبات الشراء الخارجية (<code class="text-blue-600 dark:text-blue-400 font-mono text-[11px]">aliexpress.ds.order.create</code>).
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if ($injectedStatus === 'valid')
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 px-3 py-1 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                مطابقة العنوان الوطني: نشطة ومعتمدة (Guard Active)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 px-3 py-1 text-xs font-bold text-red-700 dark:text-red-400">
                                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                غير مكتمل: {{ $injectedError }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    {{-- Logistics Address Payload --}}
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 font-sans flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                كائن العنوان اللوجستي الرئيسي (logistics_address)
                            </span>
                            <span class="text-[11px] text-gray-400 font-mono">JSON Payload</span>
                        </div>
                        <div class="rounded-lg bg-gray-900 border border-gray-800 p-3.5 overflow-x-auto shadow-inner text-xs font-mono text-emerald-400 leading-relaxed max-h-72">
                            <pre><code>{{ json_encode($injectedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>

                    {{-- Saudi Trade Extra Param Payload --}}
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 font-sans flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                المعامل التجاري الإضافي للسعودية (ds_extend_request)
                            </span>
                            <span class="text-[11px] text-gray-400 font-mono">Saudi National Model</span>
                        </div>
                        <div class="rounded-lg bg-gray-900 border border-gray-800 p-3.5 overflow-x-auto shadow-inner text-xs font-mono text-amber-300 leading-relaxed max-h-72">
                            <pre><code>{{ json_encode($injectedTradeExtra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel 5: Pricing Engine --}}
        <div id="tab-panel-pricing" class="tab-panel hidden flex flex-col gap-6">
            <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-gray-800 dark:text-white font-sans">محرك التسعير وهامش الربح</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans">
                            ضبط وتعديل قاعدة تسعير منتجات الدروب شوبينج، التحديث الآلي لأسعار البيع، وسجل حركات التسعير.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form action="{{ route('admin.dropshipping.pricing.recalculate') }}" method="POST">
                            @csrf
                            <button type="submit" class="transparent-button text-sm font-semibold border px-3 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center gap-1 font-sans cursor-pointer">
                                ⚡ إعادة حساب الأسعار الآن
                            </button>
                        </form>

                        <a href="{{ route('admin.audit-logs.pricing.index') }}" class="secondary-button text-sm font-semibold border px-3 py-1.5 rounded-md flex items-center gap-1 font-sans">
                            📜 سجل تغيرات الأسعار
                        </a>
                    </div>
                </div>

                {{-- Single Pricing Rule Edit Form --}}
                <form method="POST" action="{{ route('admin.dropshipping.pricing.rules.update', $pricingRule->id) }}" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="scope" value="{{ $pricingRule->scope ?? 'global' }}" />
                    <input type="hidden" name="priority" value="{{ $pricingRule->priority ?? 0 }}" />

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {{-- Rule Name --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="required text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                اسم قاعدة التسعير
                            </label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $pricingRule->name) }}"
                                required
                                placeholder="مثال: قاعدة التسعير العامة"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                            />
                        </div>

                        {{-- Margin Type --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="required text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                نوع الهامش الربحي
                            </label>
                            <select
                                name="type"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                            >
                                <option value="percentage" {{ old('type', $pricingRule->type) === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                                <option value="fixed" {{ old('type', $pricingRule->type) === 'fixed' ? 'selected' : '' }}>مبلغ ثابت ($)</option>
                            </select>
                        </div>

                        {{-- Margin Value --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="required text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                قيمة الهامش
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="value"
                                value="{{ old('value', $pricingRule->value) }}"
                                required
                                placeholder="30.00"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                            />
                        </div>

                        {{-- Source Discount Policy --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="required text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                سياسة خصم المصدر (AliExpress Source Discount)
                            </label>
                            @php
                                $currentPolicy = is_object($pricingRule->source_discount_policy) ? $pricingRule->source_discount_policy->value : $pricingRule->source_discount_policy;
                            @endphp
                            <select
                                name="source_discount_policy"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                            >
                                <option value="PASS_TO_CUSTOMER" {{ old('source_discount_policy', $currentPolicy) === 'PASS_TO_CUSTOMER' ? 'selected' : '' }}>
                                    تمرير للعميل (عرض التخفيض الأصلي للمنتج)
                                </option>
                                <option value="ABSORB_BY_HIGEST" {{ old('source_discount_policy', $currentPolicy) === 'ABSORB_BY_HIGEST' ? 'selected' : '' }}>
                                    امتصاص HIGEST (احتساب السعر الصافي بدون إظهار التخفيض)
                                </option>
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="required text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                حالة القاعدة
                            </label>
                            <select
                                name="status"
                                required
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none font-sans"
                            >
                                <option value="1" {{ old('status', $pricingRule->status) ? 'selected' : '' }}>نشط (مفعلة)</option>
                                <option value="0" {{ !old('status', $pricingRule->status) ? 'selected' : '' }}>معطل (غير مفعلة)</option>
                            </select>
                        </div>

                        {{-- Version & Meta Info --}}
                        <div class="flex flex-col justify-end">
                            <div class="rounded-md bg-gray-50 dark:bg-gray-800/80 p-2.5 border border-gray-200 dark:border-gray-700 text-xs text-gray-600 dark:text-gray-300 font-sans">
                                <span class="font-bold">رقم النسخة:</span> v{{ $pricingRule->version }} &nbsp;|&nbsp;
                                <span class="font-bold">آخر تحديث:</span> {{ $pricingRule->updated_at ? $pricingRule->updated_at->diffForHumans() : 'الآن' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-800 pt-4 mt-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-sans">
                            💡 سيتم تطبيق التعديلات وإعادة احتساب أسعار منتجات المتجر تلقائياً وتحديث الكاش عند الحفظ.
                        </p>

                        <button
                            type="submit"
                            class="primary-button py-2 px-6 font-bold text-sm rounded-md transition-all font-sans cursor-pointer"
                        >
                            حفظ وتطبيق قاعدة التسعير
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Panel 6: Cost Variance (فروق التكلفة) --}}
        <div id="tab-panel-cost-variance" class="tab-panel hidden">
            <form method="POST" action="{{ route('admin.dropshipping.keys.store') }}">
                @csrf
                <input type="hidden" name="section" value="cost_variance" />

                <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6 font-sans">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800 pb-4">
                        <div>
                            <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                                <span class="text-xl">⚖️</span>
                                إدارة حدود التسامح لفروق التكلفة (Cost Variance Guard)
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                ضبط الحدود المسموح بها لتغيرات أسعار المنتجات ورسوم الشحن لتمرير أوامر الشراء تلقائياً إلى علي إكسبرس دون تعطيل.
                            </p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                            محرك التسامح الذكي
                        </span>
                    </div>

                    {{-- 2 Grid Columns: Product Variance & Shipping Variance --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- 1. Product Price Variance --}}
                        <div class="p-5 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-gray-800 dark:text-white flex items-center gap-2">
                                    📦 حد التسامح في سعر المنتج
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono">
                                    Product Limit
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                أقصى تغير مسموح به في سعر شراء المنتج الأساسي من المورد قبل إيقاف الأمر.
                            </p>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                        نوع الحد
                                    </label>
                                    <select
                                        name="variance_product_type"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                    >
                                        <option value="percentage" {{ old('variance_product_type', $settings->variance_product_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                                        <option value="fixed" {{ old('variance_product_type', $settings->variance_product_type ?? 'percentage') === 'fixed' ? 'selected' : '' }}>مبلغ مقطوع ($ USD)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                        قيمة الحد المسموح
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="variance_product_limit"
                                        value="{{ old('variance_product_limit', (float) ($settings->variance_product_limit ?? 10.00)) }}"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                        placeholder="10.00"
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- 2. Shipping Cost Variance --}}
                        <div class="p-5 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50 flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-gray-800 dark:text-white flex items-center gap-2">
                                    🚚 حد التسامح في رسوم الشحن
                                </span>
                                <span class="text-xs px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono">
                                    Shipping Limit
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                أقصى زيادة مسموح بها في رسوم الشحن الفعلية مقارنة بالشحن المتوقع للمتجر.
                            </p>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                        نوع الحد
                                    </label>
                                    <select
                                        name="variance_shipping_type"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                    >
                                        <option value="percentage" {{ old('variance_shipping_type', $settings->variance_shipping_type ?? 'percentage') === 'percentage' ? 'selected' : '' }}>نسبة مئوية (%)</option>
                                        <option value="fixed" {{ old('variance_shipping_type', $settings->variance_shipping_type ?? 'percentage') === 'fixed' ? 'selected' : '' }}>مبلغ مقطوع ($ USD)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                        قيمة الحد المسموح
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        name="variance_shipping_limit"
                                        value="{{ old('variance_shipping_limit', (float) ($settings->variance_shipping_limit ?? 15.00)) }}"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                        placeholder="15.00"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3. Profit Margin Safe Guard --}}
                    <div class="p-5 rounded-lg border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50/40 dark:bg-emerald-950/20 flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <label class="flex items-start gap-3 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="variance_profit_guard_enabled"
                                    value="1"
                                    {{ old('variance_profit_guard_enabled', $settings->variance_profit_guard_enabled ?? true) ? 'checked' : '' }}
                                    class="h-4 w-4 mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <div>
                                    <span class="font-bold text-sm text-gray-800 dark:text-white flex items-center gap-1.5">
                                        🛡️ درع حماية هامش الربح الأدنى (Profit Margin Safe-Guard)
                                    </span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        تمرير الطلب واعتماده تلقائياً إذا كانت التكلفة الفعلية بعد الزيادة لا تزال تحقق هامش ربح أعلى من الحد الأدنى المقبول.
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="w-full sm:w-1/3 pt-1">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                الحد الأدنى لهامش الربح المطلوب للمرور التلقائي (%)
                            </label>
                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    name="variance_min_profit_margin"
                                    value="{{ old('variance_min_profit_margin', (float) ($settings->variance_min_profit_margin ?? 5.0)) }}"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-emerald-500 focus:outline-none font-mono"
                                    placeholder="5.0"
                                />
                                <span class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400 pointer-events-none">%</span>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Auto-Approval Toggle --}}
                    <div class="p-5 rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex items-center justify-between">
                        <label class="flex items-start gap-3 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="variance_auto_approve"
                                value="1"
                                {{ old('variance_auto_approve', $settings->variance_auto_approve ?? true) ? 'checked' : '' }}
                                class="h-4 w-4 mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <span class="font-bold text-sm text-gray-800 dark:text-white">
                                    ⚡ اعتماد وتمرير الفروقات التلقائي المقبولة (Auto-Approve Within Tolerance)
                                </span>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    عند تفعيل هذا الخيار، لن يتم تعليق الطلبات التي تطابق حدود التسامح وستُرسل مباشرة إلى المورد مع تدوين ذلك في سجل التدقيق.
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                        <button
                            type="submit"
                            class="primary-button py-2.5 px-6 font-bold text-sm rounded-md transition-all font-sans cursor-pointer"
                        >
                            حفظ إعدادات فروق التكلفة
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Panel 7: Communications / API Traffic Stats --}}
        <div id="tab-panel-communications" class="tab-panel hidden flex flex-col gap-6">
            {{-- Top KPI Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Card 1: Today Total Calls --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">الاتصالات اليومية</span>
                        <span class="p-1 px-2 rounded-md bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 font-bold text-xs">اليوم</span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white font-mono">{{ number_format($todayTotalCalls ?? 0) }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">إجمالي كلي: {{ number_format($totalAllTimeCalls ?? 0) }}</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">ناجح: {{ number_format($todaySuccessCalls ?? 0) }}</span>
                        @if (($todayFailedCalls ?? 0) > 0)
                            <span class="text-rose-600 dark:text-rose-400 font-semibold">فشل: {{ number_format($todayFailedCalls) }}</span>
                        @else
                            <span class="text-gray-400">فشل: 0</span>
                        @endif
                    </div>
                </div>

                {{-- Card 2: Quota Consumption --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">استهلاك الحد اليومي</span>
                        <span class="p-1 px-2 rounded-md bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-bold text-xs">
                            آمن جداً ✅
                        </span>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-baseline justify-between">
                            <span class="text-3xl font-extrabold text-gray-900 dark:text-white font-mono">{{ $quotaUsedPercent ?? 0 }}%</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">الحد: {{ number_format($dailyQuotaLimit ?? 50000) }} / يوم</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 mt-2.5 overflow-hidden">
                            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, max(1, $quotaUsedPercent ?? 0)) }}%"></div>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                        متبقي {{ number_format(($dailyQuotaLimit ?? 50000) - ($todayTotalCalls ?? 0)) }} استدعاء متاح اليوم
                    </div>
                </div>

                {{-- Card 3: Success Rate & Latency --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">معدل النجاح والسرعة</span>
                        <span class="p-1 px-2 rounded-md bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 font-bold text-xs">
                            {{ $todaySuccessRate ?? 100 }}%
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-extrabold text-gray-900 dark:text-white font-mono">{{ $todayAvgLatency ?? 0 }} <span class="text-base font-normal text-gray-500">ms</span></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">متوسط زمن الاستجابة</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block"></span> سرعة استجابة عالية ومستقرة
                    </div>
                </div>

                {{-- Card 4: Circuit Breaker & Key Health --}}
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">حالة المفتاح وقاطع الدائرة</span>
                        @if ($circuitBreakerActive ?? false)
                            <span class="p-1 px-2 rounded-md bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 font-bold text-xs">
                                حظر مؤقت 🛑
                            </span>
                        @else
                            <span class="p-1 px-2 rounded-md bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 font-bold text-xs">
                                نشط وسليم ✅
                            </span>
                        @endif
                    </div>
                    <div class="mt-4">
                        @if ($circuitBreakerActive ?? false)
                            <div class="text-base font-bold text-rose-600 dark:text-rose-400">متبقي {{ $circuitBanRemaining ?? 0 }} ثانية لفك الحظر</div>
                        @else
                            <div class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                لا يوجد أي حظر أو قيود
                            </div>
                        @endif
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                        مفتاح التطبيق: <span class="font-mono font-bold">{{ $settings->app_key ? substr($settings->app_key, 0, 4) . '***' : 'غير معين' }}</span>
                    </div>
                </div>
            </div>

            {{-- Recent Live API Stream --}}
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm p-5 flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-gray-800 dark:text-white">سجل أحدث الاتصالات المباشرة (Live Stream)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">آخر 25 استدعاء تم تنفيذه عبر مفتاح AliExpress</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-right">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/40">
                                <th class="p-3"># المعرف</th>
                                <th class="p-3">الوقت والتاريخ</th>
                                <th class="p-3">الخدمة / الواجهة</th>
                                <th class="p-3 text-center">رمز الحالة (Status)</th>
                                <th class="p-3 text-center">زمن التنفيذ</th>
                                <th class="p-3">تفاصيل وملاحظات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-sans">
                            @forelse ($recentApiCalls ?? [] as $log)
                                @php
                                    $isSuccess = ($log->status_code == 200);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition text-xs">
                                    <td class="p-3 font-mono font-medium text-gray-500">#{{ $log->id }}</td>
                                    <td class="p-3 text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $log->created_at }}</td>
                                    <td class="p-3 font-mono font-semibold text-gray-900 dark:text-white">{{ $log->endpoint }}</td>
                                    <td class="p-3 text-center">
                                        @if ($isSuccess)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-800 font-mono">
                                                200 OK
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:border-rose-800 font-mono">
                                                {{ $log->status_code ?: 'ERR' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-center font-mono text-gray-600 dark:text-gray-300">{{ $log->latency_ms ? $log->latency_ms . ' ms' : '-' }}</td>
                                    <td class="p-3 text-gray-500 max-w-xs truncate" title="{{ $log->error_message }}">
                                        {{ $log->error_message ?: '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-4 text-center text-gray-500">لا توجد سجلات اتصالات حالية.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            function switchTab(tabId) {
                // Hide all tab panels
                document.querySelectorAll('.tab-panel').forEach(panel => {
                    panel.classList.add('hidden');
                });

                // Show active tab panel
                const activePanel = document.getElementById('tab-panel-' + tabId);
                if (activePanel) {
                    activePanel.classList.remove('hidden');
                }

                // Reset tab button states
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('border-amber-600', 'text-amber-600', 'dark:text-amber-500', 'border-b-2');
                    btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                });

                // Set active tab button state
                const activeBtn = document.getElementById('tab-btn-' + tabId);
                if (activeBtn) {
                    activeBtn.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
                    activeBtn.classList.add('border-amber-600', 'text-amber-600', 'dark:text-amber-500', 'border-b-2');
                }

                // Save active tab in location hash
                if (tabId === 'keys') {
                    if (window.location.hash && history.replaceState) {
                        history.replaceState(null, null, window.location.pathname);
                    }
                } else {
                    window.location.hash = tabId;
                }
            }

            // On page load, restore active tab
            document.addEventListener('DOMContentLoaded', () => {
                // Priority 1: Old section from validation errors
                // Priority 2: URL Hash (e.g. #cost-variance or #pricing or #communications)
                // Priority 3: Default 'keys' (First Tab)
                let activeTab = '{{ old('section') }}';
                
                if (!activeTab) {
                    const hash = window.location.hash.replace('#', '');
                    if (hash && ['keys', 'sync', 'shipping', 'warehouse', 'pricing', 'cost-variance', 'communications'].includes(hash)) {
                        activeTab = hash;
                    } else {
                        activeTab = 'keys';
                    }
                }

                switchTab(activeTab);
            });
        </script>
    </div>
</x-admin::layouts>
