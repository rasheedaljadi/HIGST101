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
                                <option value="hourly" {{ old('sync_schedule', $settings->sync_schedule) === 'hourly' ? 'selected' : '' }}>كل ساعة (Hourly)</option>
                                <option value="twice-daily" {{ old('sync_schedule', $settings->sync_schedule) === 'twice-daily' ? 'selected' : '' }}>مرتين يومياً (Twice Daily)</option>
                                <option value="daily" {{ old('sync_schedule', $settings->sync_schedule) === 'daily' ? 'selected' : '' }}>يومياً (Daily)</option>
                            </select>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                اختر معدل تكرار تشغيل المزامنة التلقائية لأسعار ومخزون المنتجات.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 py-1">
                        <input
                            type="hidden"
                            name="sync_enabled"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            id="sync_enabled"
                            name="sync_enabled"
                            value="1"
                            {{ old('sync_enabled', $settings->sync_enabled) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <label for="sync_enabled" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                            تفعيل المزامنة المجدولة التلقائية لجميع المنتجات
                        </label>
                    </div>

                    <div class="flex items-center pt-2">
                        <button
                            type="submit"
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm"
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
                                value="{{ old('shipping_extra_days', $settings->shipping_extra_days) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="0"
                            />
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                تُضاف إلى مدة شحن AliExpress لتعكس مدة النقل الداخلي (مثال: +7 أيام).
                            </p>
                        </div>
                    </div>

                    {{-- Include AliExpress Shipping in Product Price Toggle Card --}}
                    <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-5 dark:border-blue-900/50 dark:bg-blue-950/20 flex flex-col gap-4 transition-all">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400">
                                    <span class="icon-shipping text-2xl"></span>
                                </div>
                                <div>
                                    <label for="include_shipping_in_price" class="text-sm font-bold text-gray-800 dark:text-white cursor-pointer font-sans">
                                        دمج تكلفة شحن AliExpress تلقائياً في سعر بيع المنتج (Free Shipping Model)
                                    </label>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 font-sans">
                                        عند التفعيل: يقوم محرك التسعير بجلب تكلفة شحن المورد وإضافتها إلى تكلفة المنتج قبل تطبيق هامش الربح، ليظهر السعر النهائي للعميل في المتجر شاملاً رسوم الشحن بالكامل.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <input
                                    type="hidden"
                                    name="include_shipping_in_price"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    id="include_shipping_in_price"
                                    name="include_shipping_in_price"
                                    value="1"
                                    {{ old('include_shipping_in_price', $settings->include_shipping_in_price) ? 'checked' : '' }}
                                    class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 cursor-pointer"
                                />
                            </div>
                        </div>

                        {{-- Exception for Choice / AliExpress Commitment --}}
                        <div class="border-t border-blue-200/80 dark:border-blue-900/40 pt-3.5 flex items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 mt-0.5">
                                    <span class="icon-check text-lg"></span>
                                </div>
                                <div>
                                    <label for="exclude_choice_from_shipping_price" class="text-xs font-bold text-gray-800 dark:text-white cursor-pointer font-sans">
                                        استثناء منتجات Choice (التزام AliExpress) من دمج تكلفة الشحن
                                    </label>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 font-sans leading-relaxed">
                                        عند التفعيل: المنتجات التي تحمل علامة Choice وشحن التزام AliExpress لن يتم إضافة تكلفة شحنها إلى سعر بيع المنتج، وستبقى معتمدة على سعر التكلفة المباشر فقط.
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <input
                                    type="hidden"
                                    name="exclude_choice_from_shipping_price"
                                    value="0"
                                />
                                <input
                                    type="checkbox"
                                    id="exclude_choice_from_shipping_price"
                                    name="exclude_choice_from_shipping_price"
                                    value="1"
                                    {{ old('exclude_choice_from_shipping_price', $settings->exclude_choice_from_shipping_price ?? true) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800 cursor-pointer"
                                />
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
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm"
                        >
                            حفظ خيارات الشحن
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Panel 4: Warehouse Address --}}
        <div id="tab-panel-warehouse" class="tab-panel hidden">
            <form method="POST" action="{{ route('admin.dropshipping.keys.store') }}">
                @csrf
                <input type="hidden" name="section" value="warehouse" />

                <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                    <h2 class="text-base font-bold text-gray-800 dark:text-white">عنوان شحن مستودع هايست (AliExpress Delivery Address)</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 -mt-4 font-sans">
                        هذا هو عنوان المستودع الذي سيتم شحن كافة طلبات AliExpress إليه تلقائياً من الموردين.
                    </p>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {{-- Contact Name --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                اسم مسؤول المستودع (Contact Name)
                            </label>
                            <input
                                type="text"
                                name="warehouse_contact_name"
                                value="{{ old('warehouse_contact_name', $warehouse?->contact_name) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="Al-Miftah Transport Office"
                                required
                            />
                        </div>

                        {{-- Contact Number --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                رقم هاتف المستودع (Phone Number)
                            </label>
                            <input
                                type="text"
                                name="warehouse_contact_number"
                                value="{{ old('warehouse_contact_number', $warehouse?->contact_number) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="0500000000"
                                required
                            />
                        </div>

                        {{-- Contact Email --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                البريد الإلكتروني للمستودع (Email)
                            </label>
                            <input
                                type="email"
                                name="warehouse_contact_email"
                                value="{{ old('warehouse_contact_email', $warehouse?->contact_email) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="warehouse@hayest.com"
                                required
                            />
                        </div>

                        {{-- Street Address --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                عنوان الشارع / الحي (Street Address)
                            </label>
                            <input
                                type="text"
                                name="warehouse_street"
                                value="{{ old('warehouse_street', $warehouse?->street) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="حي العزيزية, شارع الدايري الجنوبي"
                                required
                            />
                        </div>

                        {{-- City --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                المدينة (City)
                            </label>
                            <input
                                type="text"
                                name="warehouse_city"
                                value="{{ old('warehouse_city', $warehouse?->city) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="Riyadh"
                                required
                            />
                        </div>

                        {{-- State / Province --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                المنطقة / المقاطعة (State / Province)
                            </label>
                            <input
                                type="text"
                                name="warehouse_state"
                                value="{{ old('warehouse_state', $warehouse?->state) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="Riyadh"
                                required
                            />
                        </div>

                        {{-- Country Code --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                رمز الدولة (Country Code)
                            </label>
                            <select
                                name="warehouse_country"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                required
                            >
                                <option value="SA" {{ old('warehouse_country', $warehouse?->country) === 'SA' ? 'selected' : '' }}>المملكة العربية السعودية (SA)</option>
                                <option value="YE" {{ old('warehouse_country', $warehouse?->country) === 'YE' ? 'selected' : '' }}>اليمن (YE)</option>
                                <option value="US" {{ old('warehouse_country', $warehouse?->country) === 'US' ? 'selected' : '' }}>الولايات المتحدة (US)</option>
                            </select>
                        </div>

                        {{-- Postcode / ZIP / National Address --}}
                        <div class="flex flex-col gap-1">
                            <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300 font-sans">
                                الرمز البريدي / العنوان الوطني المختصر (Postcode / Short Address)
                            </label>
                            <input
                                type="text"
                                name="warehouse_postcode"
                                value="{{ old('warehouse_postcode', $warehouse?->postcode) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="ABCD1234"
                                required
                            />
                            <p class="text-xs text-amber-600 dark:text-amber-500 mt-1 font-sans">
                                هام جداً للسعودية: يجب كتابة العنوان الوطني المختصر المكون من 8 خانات (مثال: ABCD1234) ليقبل خادم AliExpress الطلب.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center pt-2">
                        <button
                            type="submit"
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm"
                        >
                            حفظ عنوان الشحن
                        </button>
                    </div>
                </div>
            </form>
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
                // Priority 2: URL Hash (e.g. #pricing)
                // Priority 3: Default 'keys' (First Tab)
                let activeTab = '{{ old('section') }}';
                
                if (!activeTab) {
                    const hash = window.location.hash.replace('#', '');
                    if (hash && ['keys', 'sync', 'shipping', 'warehouse', 'pricing'].includes(hash)) {
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
