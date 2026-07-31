@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-form-template"
    >
        <div class="mt-2 max-md:mt-3">
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.id'"
                    ::value="address.id"
                />
            </x-shop::form.control-group>

            <!-- First Name & Last Name -->
            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <!-- First Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.first-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.first_name'"
                        ::value="address.first_name"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.first-name')"
                        :placeholder="trans('shop::app.checkout.onepage.address.first-name')"
                    />

                    <x-shop::form.control-group.error ::name="controlName + '.first_name'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.first_name.after') !!}

                <!-- Last Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.last-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        ::name="controlName + '.last_name'"
                        ::value="address.last_name"
                        rules="required"
                        :label="trans('shop::app.checkout.onepage.address.last-name')"
                        :placeholder="trans('shop::app.checkout.onepage.address.last-name')"
                    />

                    <x-shop::form.control-group.error ::name="controlName + '.last_name'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.last_name.after') !!}
            </div>

            <!-- Country (Hidden & Fixed to YE) -->
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.country'"
                    value="YE"
                    v-model="selectedCountry"
                />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.country.after') !!}

            <!-- State & City (Governorate & District Side-by-Side) -->
            <div class="grid grid-cols-2 gap-x-5 max-md:grid-cols-1">
                <!-- State / Governorate -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }} !mt-0">
                        @lang('shop::app.checkout.onepage.address.state')
                    </x-shop::form.control-group.label>

                    <template v-if="states">
                        <template v-if="haveStates">
                            <x-shop::form.control-group.control
                                type="select"
                                ::name="controlName + '.state'"
                                v-model="selectedState"
                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                ::value="address.state"
                                :label="trans('shop::app.checkout.onepage.address.state')"
                                :placeholder="trans('shop::app.checkout.onepage.address.state')"
                            >
                                <option value="">
                                    @lang('shop::app.checkout.onepage.address.select-state')
                                </option>

                                <option
                                    v-for='(state, index) in states[selectedCountry]'
                                    :value="state.code"
                                >
                                    @{{ state.default_name }}
                                </option>
                            </x-shop::form.control-group.control>
                        </template>

                        <template v-else>
                            <x-shop::form.control-group.control
                                type="text"
                                ::name="controlName + '.state'"
                                ::value="address.state"
                                v-model="selectedState"
                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                :label="trans('shop::app.checkout.onepage.address.state')"
                                :placeholder="trans('shop::app.checkout.onepage.address.state')"
                            />
                        </template>
                    </template>

                    <x-shop::form.control-group.error ::name="controlName + '.state'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.state.after') !!}

                <!-- City / District -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required !mt-0">
                        @lang('shop::app.checkout.onepage.address.city')
                    </x-shop::form.control-group.label>

                    <template v-if="selectedState && availableDistricts && availableDistricts.length">
                        <x-shop::form.control-group.control
                            type="select"
                            ::name="controlName + '.city'"
                            ::value="address.city"
                            v-model="selectedCity"
                            rules="required"
                            :label="trans('shop::app.checkout.onepage.address.city')"
                            :placeholder="trans('shop::app.checkout.onepage.address.city')"
                        >
                            <option value="">
                                @lang('shop::app.checkout.onepage.address.select-city')
                            </option>

                            <option
                                v-for="district in availableDistricts"
                                :value="district"
                            >
                                @{{ district }}
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <template v-else-if="!selectedState">
                        <x-shop::form.control-group.control
                            type="select"
                            ::name="controlName + '.city'"
                            ::value="address.city"
                            disabled
                            rules="required"
                            :label="trans('shop::app.checkout.onepage.address.city')"
                            :placeholder="trans('shop::app.checkout.onepage.address.city')"
                        >
                            <option value="">
                                @lang('shop::app.checkout.onepage.address.select-state-first')
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <template v-else>
                        <x-shop::form.control-group.control
                            type="text"
                            ::name="controlName + '.city'"
                            ::value="address.city"
                            v-model="selectedCity"
                            rules="required"
                            :label="trans('shop::app.checkout.onepage.address.city')"
                            :placeholder="trans('shop::app.checkout.onepage.address.city')"
                        />
                    </template>

                    <x-shop::form.control-group.error ::name="controlName + '.city'" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.checkout.onepage.address.form.city.after') !!}
            </div>

            <!-- Street Address -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.street-address')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address[0]"
                    rules="required|address"
                    :label="trans('shop::app.checkout.onepage.address.street-address')"
                    :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                />

                <x-shop::form.control-group.error
                    class="mb-2"
                    ::name="controlName + '.address.[0]'"
                />

                @if (core()->getConfigData('customer.address.information.street_lines') > 1)
                    @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                        <x-shop::form.control-group.control
                            type="text"
                            ::name="controlName + '.address.[{{ $i }}]'"
                            rules="address"
                            :label="trans('shop::app.checkout.onepage.address.street-address')"
                            :placeholder="trans('shop::app.checkout.onepage.address.street-address')"
                        />

                        <x-shop::form.control-group.error
                            class="mb-2"
                            ::name="controlName + '.address.[{{ $i }}]'"
                        />
                    @endfor
                @endif
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.address.after') !!}

            <!-- Phone Number -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.telephone')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="required|phone"
                    :label="trans('shop::app.checkout.onepage.address.telephone')"
                    :placeholder="trans('shop::app.checkout.onepage.address.telephone')"
                />

                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.phone.after') !!}

            <!-- Email (Optional) -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="!mt-0">
                    @lang('shop::app.checkout.onepage.address.email')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    rules="email"
                    :label="trans('shop::app.checkout.onepage.address.email')"
                    placeholder="email@example.com"
                />

                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.email.after') !!}

            <!-- Hidden Postcode (Fixed to 00000) -->
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.postcode'"
                    value="00000"
                />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.postcode.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: {
                    type: String,
                    required: true,
                },

                address: {
                    type: Object,

                    default: () => ({
                        id: 0,
                        company_name: '',
                        first_name: '',
                        last_name: '',
                        email: '',
                        address: [],
                        country: '',
                        state: '',
                        city: '',
                        postcode: '',
                        phone: '',
                    }),
                },
            },

            data() {
                return {
                    selectedCountry: this.address.country || 'YE',

                    selectedState: this.address.state || '',

                    selectedCity: this.address.city || '',

                    countries: [],

                    states: null,

                    yemenDistricts: {
                        'SA': ['الثورة', 'التحرير', 'الصافية', 'السبعين', 'شعوب', 'بني الحارث', 'الوحدة', 'صنعاء القديمة', 'معين'],
                        'SN': ['سنحان وبني بهلول', 'بني مطر', 'أرحب', 'همدان', 'بني حشيش', 'الحيمة الخارجية', 'الحيمة الداخلية', 'مناخة', 'صعفان', 'الطيال', 'جحانة', 'نهم', 'بلاد الروس', 'خولان'],
                        'AD': ['صيرة (كريتر)', 'خور مكسر', 'المعلا', 'التواهي', 'الشيخ عثمان', 'المنصورة', 'دار سعد', 'البريقة'],
                        'TA': ['القاهرة', 'المظفر', 'صالة', 'التعزية', 'شرعب الرونة', 'شرعب السلام', 'ماوية', 'المسراخ', 'جبل حبشي', 'مشرعة وحدنان', 'صبر الموادم', 'المخاء', 'ذباب (باب المندب)', 'موزع', 'الوازعية', 'الشمايتين (التربة)', 'المواسط', 'المعافر', 'سامع', 'الصلو', 'خدير', 'حيفان'],
                        'HU': ['الحوك', 'الحالي', 'المينا', 'باجل', 'الزيدية', 'اللحية', 'الضحي', 'القناوص', 'الزهرة', 'المنيرة', 'بيت الفقيه', 'المنصورية', 'السخنة', 'الدريهمي', 'التحيتا', 'زبيد', 'الجراحي', 'جبل رأس', 'الخوخة', 'حيس', 'برع'],
                        'IB': ['الظهار', 'المشنة', 'جبلة', 'السبرة', 'بعدان', 'الشعر', 'النادرة', 'السدة', 'يريم', 'الرضمة', 'المخادر', 'حبيش', 'القفر', 'حزم العدين', 'العدين', 'فرع العدين', 'مذيخرة', 'ذي السفال', 'السياني'],
                        'AB': ['زنجبار', 'خنفر (جَار)', 'سباح', 'رصد', 'سرار', 'أحور', 'لودر', 'الوضيع', 'مودية', 'جيشان', 'المحفد'],
                        'BA': ['مدينة البيضاء', 'البيضاء', 'رداع', 'مكيراس', 'الصومعة', 'الزاهر', 'ذي ناعم', 'الطفة', 'ملاجم', 'ناطع', 'نعمان', 'السوادية', 'الشرية', 'ولد ربيع', 'العرش', 'ردمان'],
                        'SH': ['عتق', 'نصاب', 'حبان', 'الصعيد', 'الروضة', 'ميفعة', 'رضوم', 'حطيب', 'مرخة السفلى', 'مرخة العليا', 'جردان', 'دهر', 'الطلح', 'عسيلان', 'عين', 'بيحان'],
                        'HD': ['مدينة المكلا', 'المكلا', 'غيل باوزير', 'الشحر', 'الديس الشرقية', 'الريدة وقصيعر', 'حجر', 'بروم ميفع', 'ساه', 'سيئون', 'تريم', 'شبام', 'القطن', 'حورة ووادى العين', 'حريضة', 'عمد', 'رخية', 'العبر', 'زمخ ومنخوب', 'حجر الصيعر', 'السوم'],
                        'MR': ['الغيضة', 'حوف', 'شحن', 'حات', 'قشن', 'سيحوت', 'المسيلة', 'منعر'],
                        'LA': ['الحوطة', 'تبن', 'القبيطة', 'المقاطرة', 'طور الباحة', 'المضاربة والعارة', 'المسيمير', 'الملاح', 'حبيل جبر', 'ردفان (الحبيلين)', 'حالمين', 'يافع لبعوس', 'يهر', 'المفلحي'],
                        'MA': ['مدينة مأرب', 'مأرب', 'صرواح', 'مجزر', 'رغوان', 'مدغل', 'بدبدة', 'حريب', 'الجوبة', 'رحبة', 'جبل مراد', 'العبدية', 'ماهلية'],
                        'JA': ['الحزم', 'المتون', 'المصلوب', 'الزاهر', 'الحميدات', 'الخلق', 'الغيل', 'خب والشعف', 'برط العنان', 'رجوزة', 'خراب المراشي'],
                        'HJ': ['مدينة حجة', 'حجة', 'عبس', 'حرض', 'ميدي', 'قفل شمر', 'كحلان عفار', 'كحلان الشرف', 'الشاهل', 'المحابشة', 'كعيدنة', 'خيران المحرق', 'أفلح الشام', 'أفلح اليمن', 'مستبأ', 'بكيل المير', 'وشحة', 'الجميمة', 'كشر', 'شرس', 'المغربة', 'بني قيس'],
                        'SD': ['مدينة صعدة', 'صعدة', 'سحار', 'مجز', 'باقم', 'قطابر', 'منبه', 'رازح', 'غمر', 'شدا', 'الظاهر', 'حيدان', 'ساقين', 'كتاف والبقع', 'الصفراء', 'الحشوة'],
                        'MW': ['مدينة المحويت', 'المحويت', 'شبام كوكبان', 'الطويلة', 'الرجم', 'الخبت', 'ملحان', 'حفاش', 'بني سعد'],
                        'DH': ['مدينة ذمار', 'عنس', 'الحداء', 'جهران (معبر)', 'ضوران أنس', 'جبل الشرق', 'المنار', 'عتمة', 'وصاب العالي', 'وصاب السافل'],
                        'AM': ['عمران', 'ريدة', 'عيال سريح', 'جبل عيال يزيد', 'خمر', 'حوث', 'العشة', 'قفلة عذر', 'حرف سفيان', 'شهارة', 'مسور', 'ثلاء', 'السودة', 'السود'],
                        'DL': ['الضالع', 'جحاف', 'الأزارق', 'الحصين', 'الشعيب', 'قعطبة', 'دمت', 'جبن', 'الحشاء'],
                        'RY': ['الجبين', 'كسمة', 'الجعفرية', 'السلفية', 'بلاد الطعام', 'مزهر'],
                        'SU': ['حديبو', 'قلنسية وعبد الكوري']
                    }
                }
            },

            computed: {
                haveStates() {
                    return !! this.states[this.selectedCountry]?.length;
                },

                availableDistricts() {
                    if (! this.selectedState) {
                        return [];
                    }

                    if (this.yemenDistricts[this.selectedState]) {
                        return this.yemenDistricts[this.selectedState];
                    }

                    const found = this.states?.['YE']?.find(s => s.code === this.selectedState || s.default_name === this.selectedState);
                    if (found && this.yemenDistricts[found.code]) {
                        return this.yemenDistricts[found.code];
                    }

                    return [];
                }
            },

            mounted() {
                if (! this.selectedCountry) {
                    this.selectedCountry = 'YE';
                }

                this.getCountries();

                this.getStates();
            },

            methods: {
                getCountries() {
                    this.$axios.get("{{ route('shop.api.core.countries') }}")
                        .then(response => {
                            this.countries = response.data.data;
                        })
                        .catch(() => {});
                },

                getStates() {
                    this.$axios.get("{{ route('shop.api.core.states') }}")
                        .then(response => {
                            this.states = response.data.data;
                        })
                        .catch(() => {});
                },
            }
        });
    </script>
@endpushonce
