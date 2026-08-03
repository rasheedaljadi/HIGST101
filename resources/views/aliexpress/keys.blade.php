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

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="flex flex-col gap-1">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                هامش الشحن (مبلغ ثابت بالدولار)
                            </label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="shipping_margin"
                                value="{{ old('shipping_margin', $settings->shipping_margin) }}"
                                class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                                placeholder="0.00"
                            />
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                يُضاف مرة واحدة على كل طلب فوق تكلفة شحن AliExpress، ويغطّي النقل الداخلي (السعودية → اليمن) وربح المتجر.
                            </p>
                        </div>

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

                    <div class="flex items-center gap-3 py-1">
                        <input
                            type="hidden"
                            name="shipping_enabled"
                            value="0"
                        />
                        <input
                            type="checkbox"
                            id="shipping_enabled"
                            name="shipping_enabled"
                            value="1"
                            {{ old('shipping_enabled', $settings->shipping_enabled) ? 'checked' : '' }}
                            class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <label for="shipping_enabled" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                            تفعيل خيار شحن AliExpress في صفحة الدفع
                        </label>
                    </div>

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
                        <h2 class="text-base font-bold text-gray-800 dark:text-white font-sans">محرك التسعير والهوامش الربحية (Pricing Engine V1.1)</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-sans">
                            إدارة قواعد هامش الربح المستقلة، التحديث الآلي لأسعار البيع، وسجل حركات التسعير.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form action="{{ route('admin.dropshipping.pricing.recalculate') }}" method="POST">
                            @csrf
                            <button type="submit" class="transparent-button text-sm font-semibold border px-3 py-1.5 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 flex items-center gap-1 font-sans">
                                ⚡ إعادة حساب الأسعار الآن
                            </button>
                        </form>

                        <a href="{{ route('admin.audit-logs.pricing.index') }}" class="secondary-button text-sm font-semibold border px-3 py-1.5 rounded-md flex items-center gap-1 font-sans">
                            📜 سجل تغيرات الأسعار
                        </a>
                    </div>
                </div>

                {{-- Rule Creation Form --}}
                <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-bold mb-3 text-gray-800 dark:text-white font-sans">➕ إضافة قاعدة تسعير جديدة</h3>

                    <form action="{{ route('admin.dropshipping.pricing.rules.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3 items-end">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">اسم القاعدة</label>
                            <input type="text" name="name" required placeholder="مثال: هامش العام 30%" class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">النطاق (Scope)</label>
                            <select id="create_rule_scope" name="scope" required onchange="toggleCreateScopeField()" class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="global">عام (Global)</option>
                                <option value="category" disabled class="text-gray-400">فئة معينة (Category) — (ميزة مستقبلية ⏳)</option>
                            </select>
                        </div>

                        <div id="create_scope_id_wrapper" class="hidden">
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">الفئة المعنية</label>
                            <select id="create_rule_scope_id" name="scope_id" class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="">-- اختر الفئة --</option>
                                @foreach($pricingCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} (ID: {{ $category->id }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">نوع الهامش</label>
                            <select name="type" required class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="percentage">نسبة مئوية (%)</option>
                                <option value="fixed">مبلغ ثابت ($)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">القيمة</label>
                            <input type="number" step="0.01" name="value" required placeholder="30.00" class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">سياسة خصم المصدر</label>
                            <select name="source_discount_policy" required class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="PASS_TO_CUSTOMER">تمرير للعميل (عرض التخفيض)</option>
                                <option value="ABSORB_BY_HIGEST">امتصاص HIGEST (سعر صافي)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">الأولوية</label>
                            <input type="number" name="priority" value="0" min="0" placeholder="0" class="w-full border rounded-md px-3 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans" />
                        </div>

                        <div>
                            <button type="submit" class="w-full primary-button py-1.5 px-4 font-semibold text-sm rounded-md transition-all font-sans">
                                حفظ القاعدة
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Rules Table --}}
                <div class="border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden">
                    <div class="p-3 bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                        <h3 class="text-sm font-bold dark:text-white font-sans">قواعد التسعير المفعلة</h3>
                        <span class="text-xs text-gray-500 font-sans">الأولوية الأعلى تُنفذ أولاً</span>
                    </div>

                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-xs font-semibold">
                                <th class="p-3 border-b">الرقم</th>
                                <th class="p-3 border-b">اسم القاعدة</th>
                                <th class="p-3 border-b">النطاق</th>
                                <th class="p-3 border-b">النوع</th>
                                <th class="p-3 border-b">القيمة</th>
                                <th class="p-3 border-b">سياسة خصم المصدر</th>
                                <th class="p-3 border-b">الأولوية</th>
                                <th class="p-3 border-b">النسخة</th>
                                <th class="p-3 border-b">الحالة</th>
                                <th class="p-3 border-b">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                            @forelse($pricingRules as $rule)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="p-3 font-mono">#{{ $rule->id }}</td>
                                    <td class="p-3 font-bold dark:text-white">{{ $rule->name }}</td>
                                    <td class="p-3">
                                        <span class="px-2 py-0.5 text-xs rounded bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200 font-mono">
                                            {{ strtoupper($rule->scope) }} {{ $rule->scope_id ? "(#{$rule->scope_id})" : '' }}
                                        </span>
                                    </td>
                                    <td class="p-3">{{ $rule->type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}</td>
                                    <td class="p-3 font-bold text-emerald-600 dark:text-emerald-400">
                                        {{ $rule->type === 'percentage' ? $rule->value . '%' : '$' . number_format($rule->value, 2) }}
                                    </td>
                                    <td class="p-3">
                                        @php
                                            $policyVal = is_object($rule->source_discount_policy) ? $rule->source_discount_policy->value : $rule->source_discount_policy;
                                        @endphp
                                        @if($policyVal === 'ABSORB_BY_HIGEST')
                                            <span class="px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 font-semibold" title="امتصاص خصم المصدر لـ HIGEST">امتصاص HIGEST</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300 font-semibold" title="تمرير الخصم للعميل">تمرير للعميل</span>
                                        @endif
                                    </td>
                                    <td class="p-3">{{ $rule->priority }}</td>
                                    <td class="p-3 font-mono text-xs">v{{ $rule->version }}</td>
                                    <td class="p-3">
                                        @if($rule->status)
                                            <span class="px-2 py-0.5 text-xs rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400 font-semibold">نشط</span>
                                        @else
                                            <span class="px-2 py-0.5 text-xs rounded bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400 font-semibold">معطل</span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <div class="flex items-center gap-3">
                                            <button type="button" onclick="openEditRuleModal({{ json_encode($rule) }})" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-bold transition-all cursor-pointer">
                                                تعديل
                                            </button>

                                            <form action="{{ route('admin.dropshipping.pricing.rules.destroy', $rule->id) }}" method="POST" onsubmit="return confirm('هل أنت تأكد من حذف قاعدة التسعير هذه؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-bold cursor-pointer">
                                                    حذف
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm font-sans">
                                        لا توجد قواعد تسعير معرفة حالياً (الهامش الافتراضي عند عدم وجود قواعد هو 0%).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Edit Rule Modal --}}
        <div id="editRuleModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-xl border border-gray-200 dark:border-gray-800 w-full max-w-lg overflow-hidden transition-all transform">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-800 dark:text-white font-sans">✏️ تعديل قاعدة التسعير</h3>
                    <button type="button" onclick="closeEditRuleModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold">&times;</button>
                </div>

                <form id="editRuleForm" method="POST" action="" class="p-6 flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">اسم القاعدة</label>
                        <input type="text" id="edit_rule_name" name="name" required class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">النطاق (Scope)</label>
                            <select id="edit_rule_scope" name="scope" required onchange="toggleEditScopeField()" class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="global">عام (Global)</option>
                                <option value="category" disabled class="text-gray-400">فئة معينة (Category) — (ميزة مستقبلية ⏳)</option>
                            </select>
                        </div>

                        <div id="edit_scope_id_wrapper" class="hidden">
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">الفئة المعنية</label>
                            <select id="edit_rule_scope_id" name="scope_id" class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="">-- اختر الفئة --</option>
                                @foreach($pricingCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} (ID: {{ $category->id }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">نوع الهامش</label>
                            <select id="edit_rule_type" name="type" required class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="percentage">نسبة مئوية (%)</option>
                                <option value="fixed">مبلغ ثابت ($)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">القيمة</label>
                            <input type="number" step="0.01" id="edit_rule_value" name="value" required class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">سياسة خصم المصدر (AliExpress Source Discount Policy)</label>
                        <select id="edit_rule_source_discount_policy" name="source_discount_policy" required class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                            <option value="PASS_TO_CUSTOMER">تمرير الخصم للعميل (عرض التخفيض PASS_TO_CUSTOMER)</option>
                            <option value="ABSORB_BY_HIGEST">امتصاص الخصم لـ HIGEST (سعر صافي ABSORB_BY_HIGEST)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">الأولوية</label>
                            <input type="number" id="edit_rule_priority" name="priority" value="0" class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans" />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1 text-gray-700 dark:text-gray-300 font-sans">الحالة</label>
                            <select id="edit_rule_status" name="status" required class="w-full border rounded-md px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 font-sans">
                                <option value="1">نشط</option>
                                <option value="0">معطل</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800 mt-2">
                        <button type="button" onclick="closeEditRuleModal()" class="px-4 py-2 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-md font-sans">
                            إلغاء
                        </button>
                        <button type="submit" class="primary-button px-5 py-2 text-sm font-semibold rounded-md font-sans">
                            حفظ التغييرات
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
                toggleCreateScopeField();
            });

            function toggleCreateScopeField() {
                const scopeSelect = document.getElementById('create_rule_scope');
                const container = document.getElementById('create_scope_id_wrapper');
                const scopeIdSelect = document.getElementById('create_rule_scope_id');
                
                if (scopeSelect && container) {
                    if (scopeSelect.value === 'category') {
                        container.classList.remove('hidden');
                    } else {
                        container.classList.add('hidden');
                        if (scopeIdSelect) scopeIdSelect.value = '';
                    }
                }
            }

            function toggleEditScopeField() {
                const scopeSelect = document.getElementById('edit_rule_scope');
                const container = document.getElementById('edit_scope_id_wrapper');
                const scopeIdSelect = document.getElementById('edit_rule_scope_id');
                
                if (scopeSelect && container) {
                    if (scopeSelect.value === 'category') {
                        container.classList.remove('hidden');
                    } else {
                        container.classList.add('hidden');
                        if (scopeIdSelect) scopeIdSelect.value = '';
                    }
                }
            }

            function openEditRuleModal(rule) {
                const modal = document.getElementById('editRuleModal');
                const form = document.getElementById('editRuleForm');
                
                form.action = "{{ url('admin/dropshipping/pricing/rules') }}/" + rule.id;
                
                document.getElementById('edit_rule_name').value = rule.name;
                document.getElementById('edit_rule_scope').value = rule.scope;
                document.getElementById('edit_rule_scope_id').value = rule.scope_id || '';
                document.getElementById('edit_rule_type').value = rule.type;
                document.getElementById('edit_rule_value').value = rule.value;
                const policyVal = typeof rule.source_discount_policy === 'object' && rule.source_discount_policy !== null ? rule.source_discount_policy.value : (rule.source_discount_policy || 'PASS_TO_CUSTOMER');
                document.getElementById('edit_rule_source_discount_policy').value = policyVal;
                document.getElementById('edit_rule_priority').value = rule.priority;
                document.getElementById('edit_rule_status').value = rule.status ? '1' : '0';
                
                toggleEditScopeField();
                
                modal.classList.remove('hidden');
            }

            function closeEditRuleModal() {
                document.getElementById('editRuleModal').classList.add('hidden');
            }
        </script>
    </div>
</x-admin::layouts>
