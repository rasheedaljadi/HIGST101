<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.account.addresses.edit.edit-address')
    </x-slot>

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="addresses.edit" :entity="$address" />
        @endSection
    @endif

    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <div class="mx-4 flex-auto max-md:mx-6 max-sm:mx-4">
        <div class="mb-8 flex items-center max-md:mb-5">
            <!-- Back Button -->
            <a
                class="grid md:hidden"
                href="{{ route('shop.customers.account.addresses.index') }}"
            >
                <span class="icon-arrow-left rtl:icon-arrow-right text-2xl"></span>
            </a>

            <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                @lang('shop::app.customers.account.addresses.edit.edit-address')
            </h2>
        </div>

        <v-edit-customer-address>
            <!-- Address Shimmer Effect -->
            <x-shop::shimmer.form.control-group :count="10" />
        </v-edit-customer-address>
    </div>

    @push('scripts')
        <script
            type="text/x-template"
            id="v-edit-customer-address-template"
        >
            <x-shop::form
                method="PUT"
                :action="route('shop.customers.account.addresses.update', $address->id)"
            >
                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.before', ['address' => $address]) !!}

                <!-- Company Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label>
                        @lang('shop::app.customers.account.addresses.edit.company-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="company_name"
                        :value="old('company_name') ?? $address->company_name"
                        :label="trans('shop::app.customers.account.addresses.edit.company-name')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.company-name')"
                    />

                    <x-shop::form.control-group.error control-name="company_name" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.company_name.after', ['address' => $address]) !!}

                <!-- First Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.first-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="first_name"
                        rules="required"
                        :value="old('first_name') ?? $address->first_name"
                        :label="trans('shop::app.customers.account.addresses.edit.first-name')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.first-name')"
                    />

                    <x-shop::form.control-group.error control-name="first_name" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.first_name.after', ['address' => $address]) !!}

                <!-- Last Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.last-name')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="last_name"
                        rules="required"
                        :value="old('last_name') ?? $address->last_name"
                        :label="trans('shop::app.customers.account.addresses.edit.last-name')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.last-name')"
                    />

                    <x-shop::form.control-group.error control-name="last_name" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.last_name.after', ['address' => $address]) !!}

                <!-- E-mail (Optional) -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label>
                        @lang('Email')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="email"
                        name="email"
                        rules="email"
                        :value="old('email') ?? $address->email"
                        :label="trans('Email')"
                        placeholder="email@example.com"
                    />

                    <x-shop::form.control-group.error control-name="email" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.email.after', ['address' => $address]) !!}

                <!-- Vat ID -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label>
                        @lang('shop::app.customers.account.addresses.edit.vat-id')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="vat_id"
                        :value="old('vat_id') ?? $address->vat_id"
                        :label="trans('shop::app.customers.account.addresses.edit.vat-id')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.vat-id')"
                    />

                    <x-shop::form.control-group.error control-name="vat_id" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.vat_id.after', ['address' => $address]) !!}

                @php
                    $addresses = explode(PHP_EOL, $address->address);
                @endphp

                <!-- Street Address -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.street-address')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="address[]"
                        :value="collect(old('address'))->first() ?? $addresses[0]"
                        rules="required|address"
                        :label="trans('shop::app.customers.account.addresses.edit.street-address')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.street-address')"
                    />

                    <x-shop::form.control-group.error control-name="address[]" />
                </x-shop::form.control-group>

                @if (
                    core()->getConfigData('customer.address.information.street_lines')
                    && core()->getConfigData('customer.address.information.street_lines') > 1
                )
                    @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                        <x-shop::form.control-group.control
                            type="text"
                            name="address[{{ $i }}]"
                            :value="old('address[{{$i}}]', $addresses[$i] ?? '')"
                            rules="address"
                            :label="trans('shop::app.customers.account.addresses.edit.street-address')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.street-address')"
                        />

                        <x-shop::form.control-group.error
                            class="mb-2"
                            name="address[{{ $i }}]"
                        />
                    @endfor
                @endif

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.street-addres.after', ['address' => $address]) !!}

                <!-- Country Name -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isCountryRequired() ? 'required' : '' }}">
                        @lang('shop::app.customers.account.addresses.edit.country')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="select"
                        name="country"
                        rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                        v-model="addressData.country"
                        :aria-label="trans('shop::app.customers.account.addresses.edit.country')"
                        :label="trans('shop::app.customers.account.addresses.edit.country')"
                    >
                        @foreach (core()->countries() as $country)
                            <option 
                                {{ $country->code === config('app.default_country') ? 'selected' : '' }}  
                                value="{{ $country->code }}"
                            >
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </x-shop::form.control-group.control>

                    <x-shop::form.control-group.error control-name="country" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.country.after', ['address' => $address]) !!}

                <!-- State Name (Governorate) -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }}">
                        @lang('shop::app.customers.account.addresses.edit.state')
                    </x-shop::form.control-group.label>
                    <template v-if="haveStates()">
                        <x-shop::form.control-group.control
                            type="select"
                            name="state"
                            id="state"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            v-model="addressData.state"
                            :label="trans('shop::app.customers.account.addresses.edit.state')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.state')"
                        >
                            <option value="">
                                @lang('shop::app.customers.account.addresses.edit.select-state')
                            </option>

                            <option 
                                v-for='(state, index) in countryStates[addressData.country]'
                                :value="state.code"
                            >
                                @{{ state.default_name }}
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <template v-else>
                        <x-shop::form.control-group.control
                            type="text"
                            name="state"
                            rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                            :value="old('state') ?? $address->state"
                            v-model="addressData.state"
                            :label="trans('shop::app.customers.account.addresses.edit.state')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.state')"
                        />
                    </template>

                    <x-shop::form.control-group.error control-name="state" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.state.after', ['address' => $address]) !!}

                <!-- City / District (Dropdown from Yemen Districts) -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.city')
                    </x-shop::form.control-group.label>

                    <template v-if="addressData.state && availableDistricts && availableDistricts.length">
                        <x-shop::form.control-group.control
                            type="select"
                            name="city"
                            v-model="addressData.city"
                            rules="required"
                            :label="trans('shop::app.customers.account.addresses.edit.city')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.city')"
                        >
                            <option value="">
                                اختر المديرية
                            </option>

                            <option
                                v-for="district in availableDistricts"
                                :value="district"
                            >
                                @{{ district }}
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <template v-else-if="!addressData.state && addressData.country === 'YE'">
                        <x-shop::form.control-group.control
                            type="select"
                            name="city"
                            disabled
                            rules="required"
                            :label="trans('shop::app.customers.account.addresses.edit.city')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.city')"
                        >
                            <option value="">
                                يرجى اختيار المحافظة أولاً
                            </option>
                        </x-shop::form.control-group.control>
                    </template>

                    <template v-else>
                        <x-shop::form.control-group.control
                            type="text"
                            name="city"
                            v-model="addressData.city"
                            rules="required"
                            :label="trans('shop::app.customers.account.addresses.edit.city')"
                            :placeholder="trans('shop::app.customers.account.addresses.edit.city')"
                        />
                    </template>

                    <x-shop::form.control-group.error control-name="city" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.city.after', ['address' => $address]) !!}

                <!-- Postcode -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="{{ core()->isPostCodeRequired() ? 'required' : '' }}">
                        @lang('shop::app.customers.account.addresses.edit.post-code')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="postcode"
                        rules="{{ core()->isPostCodeRequired() ? 'required' : '' }}|postcode"
                        :value="old('postal-code') ?? ($address->postcode ?: '00000')"
                        :label="trans('shop::app.customers.account.addresses.edit.post-code')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.post-code')"
                    />

                    <x-shop::form.control-group.error control-name="postcode" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.postcode.after', ['address' => $address]) !!}

                <!-- Phone -->
                <x-shop::form.control-group>
                    <x-shop::form.control-group.label class="required">
                        @lang('shop::app.customers.account.addresses.edit.phone')
                    </x-shop::form.control-group.label>

                    <x-shop::form.control-group.control
                        type="text"
                        name="phone"
                        rules="required|phone"
                        :value="old('phone') ?? $address->phone"
                        :label="trans('shop::app.customers.account.addresses.edit.phone')"
                        :placeholder="trans('shop::app.customers.account.addresses.edit.phone')"
                    />

                    <x-shop::form.control-group.error control-name="phone" />
                </x-shop::form.control-group>

                {!! view_render_event('bagisto.shop.customers.account.addresses.edit_form_controls.phone.after', ['address' => $address]) !!}

                <button
                    type="submit"
                    class="primary-button m-0 block rounded-2xl px-11 py-3 text-center text-base max-md:w-full max-md:max-w-full max-md:rounded-lg max-md:py-1.5"
                >
                    @lang('shop::app.customers.account.addresses.edit.update-btn')
                </button>
                
                {!! view_render_event('bagisto.shop.customers.account.address.edit_form_controls.after', ['address' => $address]) !!}

            </x-shop::form>
        </script>

        <script type="module">
            app.component('v-edit-customer-address', {
                template: '#v-edit-customer-address-template',

                data() {
                    return {
                        addressData: {
                            country: "{{ old('country') ?? $address->country }}",

                            state: "{{ old('state') ?? $address->state }}",

                            city: "{{ old('city') ?? $address->city }}",
                        },

                        countryStates: @json(core()->groupedStatesByCountries()),

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
                    };
                },
    
                computed: {
                    availableDistricts() {
                        if (! this.addressData.state) {
                            return [];
                        }

                        if (this.yemenDistricts[this.addressData.state]) {
                            return this.yemenDistricts[this.addressData.state];
                        }

                        const found = this.countryStates?.['YE']?.find(s => s.code === this.addressData.state || s.default_name === this.addressData.state);
                        if (found && this.yemenDistricts[found.code]) {
                            return this.yemenDistricts[found.code];
                        }

                        return [];
                    }
                },

                methods: {
                    haveStates() {
                        return !!this.countryStates[this.addressData.country]?.length;
                    },
                },
            });
        </script>
    @endpush

</x-shop::layouts.account>
