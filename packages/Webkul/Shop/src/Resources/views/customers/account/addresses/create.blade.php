<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.account.addresses.create.add-address')
    </x-slot>

    <!-- Breadcrumbs -->
    @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="addresses.create" />
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
                @lang('shop::app.customers.account.addresses.create.add-address')
            </h2>
        </div>

        <v-create-customer-address>
            <!--Address Shimmer-->
            <x-shop::shimmer.form.control-group :count="10" />
        </v-create-customer-address>

    </div>

    @push('scripts')
        <script
            type="text/x-template"
            id="v-create-customer-address-template"
        >
            <div>
                <x-shop::form :action="route('shop.customers.account.addresses.store')">
                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.before') !!}

                    <!--Company Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.account.addresses.create.company-name')
                        </x-shop::form.control-group.label>
            
                        <x-shop::form.control-group.control
                            type="text"
                            name="company_name"
                            :value="old('company_name')"
                            :label="trans('shop::app.customers.account.addresses.create.company-name')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.company-name')"
                        />
            
                        <x-shop::form.control-group.error control-name="company_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.company_name.after') !!}

                    <!-- First Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.first-name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="first_name"
                            rules="required"
                            :value="old('first_name')"
                            :label="trans('shop::app.customers.account.addresses.create.first-name')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.first-name')"
                        />

                        <x-shop::form.control-group.error control-name="first_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.first_name.after') !!}

                    <!-- Last Name  -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.last-name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="last_name"
                            rules="required"
                            :value="old('last_name')"
                            :label="trans('shop::app.customers.account.addresses.create.last-name')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.last-name')"
                        />

                        <x-shop::form.control-group.error control-name="last_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.last_name.after') !!}

                    <!-- E-mail (Optional) -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.account.addresses.create.email')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="email"
                            name="email"
                            rules="email"
                            :value="old('email')"
                            :label="trans('shop::app.customers.account.addresses.create.email')"
                            placeholder="email@example.com"
                        />

                        <x-shop::form.control-group.error control-name="email" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.email.after') !!}

                    <!-- Vat Id -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.account.addresses.create.vat-id')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="vat_id"
                            :value="old('vat_id')"
                            :label="trans('shop::app.customers.account.addresses.create.vat-id')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.vat-id')"
                        />

                        <x-shop::form.control-group.error control-name="vat_id" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.vat_id.after') !!}

                    <!-- Street Address -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.street-address')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="address[]"
                            rules="required|address"
                            :value="collect(old('address'))->first()"
                            :label="trans('shop::app.customers.account.addresses.create.street-address')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.street-address')"
                        />

                        <x-shop::form.control-group.error control-name="address[]" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.street_address.after') !!}

                    @if (
                        core()->getConfigData('customer.address.information.street_lines')
                        && core()->getConfigData('customer.address.information.street_lines') > 1
                    )
                        @for ($i = 1; $i < core()->getConfigData('customer.address.information.street_lines'); $i++)
                            <x-shop::form.control-group.control
                                type="text"
                                name="address[{{ $i }}]"
                                :value="old('address[{{ $i }}]')"
                                rules="address"
                                :label="trans('shop::app.customers.account.addresses.create.street-address')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.street-address')"
                            />

                            <x-shop::form.control-group.error
                                class="mb-2"
                                name="address[{{ $i }}]"
                            />
                        @endfor
                    @endif

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.street_address.after') !!}

                    <!-- Country List (Default to YE) -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ core()->isCountryRequired() ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.country')
                        </x-shop::form.control-group.label>
            
                        <x-shop::form.control-group.control
                            type="select"
                            name="country"
                            rules="{{ core()->isCountryRequired() ? 'required' : '' }}"
                            v-model="country"
                            :aria-label="trans('shop::app.customers.account.addresses.create.country')"
                            :label="trans('shop::app.customers.account.addresses.create.country')"
                        >
                            <option value="">
                                @lang('shop::app.customers.account.addresses.create.select-country')
                            </option>
            
                            @foreach (core()->countries() as $country)
                                <option value="{{ $country->code }}">{{ $country->name }}</option>
                            @endforeach
                        </x-shop::form.control-group.control>
            
                        <x-shop::form.control-group.error control-name="country" />
                    </x-shop::form.control-group>
        
                    <!-- State Name (Governorate) -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ core()->isStateRequired() ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.state')
                        </x-shop::form.control-group.label>
        
                        <template v-if="haveStates()">
                            <x-shop::form.control-group.control
                                type="select"
                                id="state"
                                name="state"
                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                v-model="state"
                                :label="trans('shop::app.customers.account.addresses.create.state')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.state')"
                            >
                                <option value="">
                                    @lang('shop::app.customers.account.addresses.create.select-state')
                                </option>

                                <option 
                                    v-for='(state, index) in countryStates[country]'
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
                                :value="old('state')"
                                rules="{{ core()->isStateRequired() ? 'required' : '' }}"
                                v-model="state"
                                :label="trans('shop::app.customers.account.addresses.create.state')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.state')"
                            />
                        </template>
        
                        <x-shop::form.control-group.error control-name="state" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.state.after') !!}

                    <!-- City / District (Dropdown from Yemen Districts) -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.city')
                        </x-shop::form.control-group.label>

                        <template v-if="state && availableDistricts && availableDistricts.length">
                            <x-shop::form.control-group.control
                                type="select"
                                name="city"
                                v-model="city"
                                rules="required"
                                :label="trans('shop::app.customers.account.addresses.create.city')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.city')"
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

                        <template v-else-if="!state && country === 'YE'">
                            <x-shop::form.control-group.control
                                type="select"
                                name="city"
                                disabled
                                rules="required"
                                :label="trans('shop::app.customers.account.addresses.create.city')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.city')"
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
                                v-model="city"
                                rules="required"
                                :label="trans('shop::app.customers.account.addresses.create.city')"
                                :placeholder="trans('shop::app.customers.account.addresses.create.city')"
                            />
                        </template>

                        <x-shop::form.control-group.error control-name="city" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.city.after') !!}

                    <!-- Post Code -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="{{ core()->isPostCodeRequired() ? 'required' : '' }}">
                            @lang('shop::app.customers.account.addresses.create.post-code')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="postcode"
                            rules="{{ core()->isPostCodeRequired() ? 'required' : '' }}|postcode"
                            :value="old('postcode') ?? '00000'"
                            :label="trans('shop::app.customers.account.addresses.create.post-code')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.post-code')"
                        />

                        <x-shop::form.control-group.error control-name="postcode" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.postcode.after') !!}

                    <!-- Contact -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.account.addresses.create.phone')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            name="phone"
                            rules="required|phone"
                            :value="old('phone')"
                            :label="trans('shop::app.customers.account.addresses.create.phone')"
                            :placeholder="trans('shop::app.customers.account.addresses.create.phone')"
                        />

                        <x-shop::form.control-group.error control-name="phone" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.phone.after') !!}

                    <!-- Set As Default -->
                    <div class="text-md mb-4 flex select-none items-center gap-x-1.5 text-zinc-500">
                        <input
                            type="checkbox"
                            name="default_address"
                            value="1"
                            id="default_address"
                            class="peer hidden cursor-pointer"
                        >

                        <label
                            class="icon-uncheck peer-checked:icon-check-box cursor-pointer text-2xl text-navyBlue peer-checked:text-navyBlue"
                            for="default_address"
                        >
                        </label>

                        <label 
                            class="block cursor-pointer text-base max-md:text-sm"
                            for="default_address"
                        >
                            @lang('shop::app.customers.account.addresses.create.set-as-default')
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="primary-button m-0 block rounded-2xl px-11 py-3 text-center text-base max-md:w-full max-md:max-w-full max-md:rounded-lg max-md:py-2 max-sm:py-1.5"
                    >
                        @lang('shop::app.customers.account.addresses.create.save')
                    </button>

                    {!! view_render_event('bagisto.shop.customers.account.addresses.create_form_controls.after') !!}
                </x-shop::form>
                {!! view_render_event('bagisto.shop.customers.account.address.create.after') !!}
            </div>
        </script>
    
        <script type="module">
            app.component('v-create-customer-address', {
                template: '#v-create-customer-address-template',
    
                data() {
                    return {
                        country: "{{ old('country') ?? 'YE' }}",

                        state: "{{ old('state') }}",

                        city: "{{ old('city') }}",

                        countryStates: @json(core()->groupedStatesByCountries()),

                        yemenDistricts: {
                            'SAN': ['معين', 'الثورة', 'شعوب', 'السبعين', 'الصافية', 'الوحدة', 'التحرير', 'صنعاء القديمة', 'بني الحارث', 'آزال'],
                            'SA': ['معين', 'الثورة', 'شعوب', 'السبعين', 'الصافية', 'الوحدة', 'التحرير', 'صنعاء القديمة', 'بني الحارث', 'آزال'],
                            'أمانة العاصمة': ['معين', 'الثورة', 'شعوب', 'السبعين', 'الصافية', 'الوحدة', 'التحرير', 'صنعاء القديمة', 'بني الحارث', 'آزال'],
                            'SN': ['سنحان وبني بهلول', 'بني مطر', 'أرحب', 'همدان', 'بني حشيش', 'الحيمة الخارجية', 'الحيمة الداخلية', 'مناخة', 'صعفان', 'الطيال', 'جحانة', 'نهم', 'بلاد الروس', 'خولان'],
                            'محافظة صنعاء': ['سنحان وبني بهلول', 'بني مطر', 'أرحب', 'همدان', 'بني حشيش', 'الحيمة الخارجية', 'الحيمة الداخلية', 'مناخة', 'صعفان', 'الطيال', 'جحانة', 'نهم', 'بلاد الروس', 'خولان'],
                            'AD': ['صيرة (كريتر)', 'خور مكسر', 'المعلا', 'التواهي', 'الشيخ عثمان', 'المنصورة', 'دار سعد', 'البريقة'],
                            'عدن': ['صيرة (كريتر)', 'خور مكسر', 'المعلا', 'التواهي', 'الشيخ عثمان', 'المنصورة', 'دار سعد', 'البريقة'],
                            'TZ': ['القاهرة', 'المظفر', 'صالة', 'التعزية', 'شرعب الرونة', 'شرعب السلام', 'ماوية', 'المسراخ', 'جبل حبشي', 'مشرعة وحدنان', 'صبر الموادم', 'المخاء', 'ذباب (باب المندب)', 'موزع', 'الوازعية', 'الشمايتين (التربة)', 'المواسط', 'المعافر', 'سامع', 'الصلو', 'خدير', 'حيفان'],
                            'TA': ['القاهرة', 'المظفر', 'صالة', 'التعزية', 'شرعب الرونة', 'شرعب السلام', 'ماوية', 'المسراخ', 'جبل حبشي', 'مشرعة وحدنان', 'صبر الموادم', 'المخاء', 'ذباب (باب المندب)', 'موزع', 'الوازعية', 'الشمايتين (التربة)', 'المواسط', 'المعافر', 'سامع', 'الصلو', 'خدير', 'حيفان'],
                            'تعز': ['القاهرة', 'المظفر', 'صالة', 'التعزية', 'شرعب الرونة', 'شرعب السلام', 'ماوية', 'المسراخ', 'جبل حبشي', 'مشرعة وحدنان', 'صبر الموادم', 'المخاء', 'ذباب (باب المندب)', 'موزع', 'الوازعية', 'الشمايتين (التربة)', 'المواسط', 'المعافر', 'سامع', 'الصلو', 'خدير', 'حيفان'],
                            'HU': ['الحوك', 'الحالي', 'المينا', 'باجل', 'الزيدية', 'اللحية', 'الضحي', 'القناوص', 'الزهرة', 'المنيرة', 'بيت الفقيه', 'المنصورية', 'السخنة', 'الدريهمي', 'التحيتا', 'زبيد', 'الجراحي', 'جبل رأس', 'الخوخة', 'حيس', 'برع'],
                            'الحديدة': ['الحوك', 'الحالي', 'المينا', 'باجل', 'الزيدية', 'اللحية', 'الضحي', 'القناوص', 'الزهرة', 'المنيرة', 'بيت الفقيه', 'المنصورية', 'السخنة', 'الدريهمي', 'التحيتا', 'زبيد', 'الجراحي', 'جبل رأس', 'الخوخة', 'حيس', 'برع'],
                            'IB': ['الظهار', 'المشنة', 'جبلة', 'السبرة', 'بعدان', 'الشعر', 'النادرة', 'السدة', 'يريم', 'الرضمة', 'المخادر', 'حبيش', 'القفر', 'حزم العدين', 'العدين', 'فرع العدين', 'مذيخرة', 'ذي السفال', 'السياني'],
                            'إب': ['الظهار', 'المشنة', 'جبلة', 'السبرة', 'بعدان', 'الشعر', 'النادرة', 'السدة', 'يريم', 'الرضمة', 'المخادر', 'حبيش', 'القفر', 'حزم العدين', 'العدين', 'فرع العدين', 'مذيخرة', 'ذي السفال', 'السياني'],
                            'AB': ['زنجبار', 'خنفر (جَار)', 'سباح', 'رصد', 'سرار', 'أحور', 'لودر', 'الوضيع', 'مودية', 'جيشان', 'المحفد'],
                            'أبين': ['زنجبار', 'خنفر (جَار)', 'سباح', 'رصد', 'سرار', 'أحور', 'لودر', 'الوضيع', 'مودية', 'جيشان', 'المحفد'],
                            'BA': ['مدينة البيضاء', 'البيضاء', 'رداع', 'مكيراس', 'الصومعة', 'الزاهر', 'ذي ناعم', 'الطفة', 'ملاجم', 'ناطع', 'نعمان', 'السوادية', 'الشرية', 'ولد ربيع', 'العرش', 'ردمان'],
                            'البيضاء': ['مدينة البيضاء', 'البيضاء', 'رداع', 'مكيراس', 'الصومعة', 'الزاهر', 'ذي ناعم', 'الطفة', 'ملاجم', 'ناطع', 'نعمان', 'السوادية', 'الشرية', 'ولد ربيع', 'العرش', 'ردمان'],
                            'SH': ['عتق', 'نصاب', 'حبان', 'الصعيد', 'الروضة', 'ميفعة', 'رضوم', 'حطيب', 'مرخة السفلى', 'مرخة العليا', 'جردان', 'دهر', 'الطلح', 'عسيلان', 'عين', 'بيحان'],
                            'شبوة': ['عتق', 'نصاب', 'حبان', 'الصعيد', 'الروضة', 'ميفعة', 'رضوم', 'حطيب', 'مرخة السفلى', 'مرخة العليا', 'جردان', 'دهر', 'الطلح', 'عسيلان', 'عين', 'بيحان'],
                            'HD': ['مدينة المكلا', 'المكلا', 'غيل باوزير', 'الشحر', 'الديس الشرقية', 'الريدة وقصيعر', 'حجر', 'بروم ميفع', 'ساه', 'سيئون', 'تريم', 'شبام', 'القطن', 'حورة ووادى العين', 'حريضة', 'عمد', 'رخية', 'العبر', 'زمخ ومنخوب', 'حجر الصيعر', 'السوم'],
                            'حضرموت': ['مدينة المكلا', 'المكلا', 'غيل باوزير', 'الشحر', 'الديس الشرقية', 'الريدة وقصيعر', 'حجر', 'بروم ميفع', 'ساه', 'سيئون', 'تريم', 'شبام', 'القطن', 'حورة ووادى العين', 'حريضة', 'عمد', 'رخية', 'العبر', 'زمخ ومنخوب', 'حجر الصيعر', 'السوم'],
                            'MR': ['الغيضة', 'حوف', 'شحن', 'حات', 'قشن', 'سيحوت', 'المسيلة', 'منعر'],
                            'المهرة': ['الغيضة', 'حوف', 'شحن', 'حات', 'قشن', 'سيحوت', 'المسيلة', 'منعر'],
                            'LA': ['الحوطة', 'تبن', 'القبيطة', 'المقاطرة', 'طور الباحة', 'المضاربة والعارة', 'المسيمير', 'الملاح', 'حبيل جبر', 'ردفان (الحبيلين)', 'حالمين', 'يافع لبعوس', 'يهر', 'المفلحي'],
                            'لحج': ['الحوطة', 'تبن', 'القبيطة', 'المقاطرة', 'طور الباحة', 'المضاربة والعارة', 'المسيمير', 'الملاح', 'حبيل جبر', 'ردفان (الحبيلين)', 'حالمين', 'يافع لبعوس', 'يهر', 'المفلحي'],
                            'MA': ['مدينة مأرب', 'مأرب', 'صرواح', 'مجزر', 'رغوان', 'مدغل', 'بدبدة', 'حريب', 'الجوبة', 'رحبة', 'جبل مراد', 'العبدية', 'ماهلية'],
                            'مأرب': ['مدينة مأرب', 'مأرب', 'صرواح', 'مجزر', 'رغوان', 'مدغل', 'بدبدة', 'حريب', 'الجوبة', 'رحبة', 'جبل مراد', 'العبدية', 'ماهلية'],
                            'JA': ['الحزم', 'المتون', 'المصلوب', 'الزاهر', 'الحميدات', 'الخلق', 'الغيل', 'خب والشعف', 'برط العنان', 'رجوزة', 'خراب المراشي'],
                            'الجوف': ['الحزم', 'المتون', 'المصلوب', 'الزاهر', 'الحميدات', 'الخلق', 'الغيل', 'خب والشعف', 'برط العنان', 'رجوزة', 'خراب المراشي'],
                            'HJ': ['مدينة حجة', 'حجة', 'عبس', 'حرض', 'ميدي', 'قفل شمر', 'كحلان عفار', 'كحلان الشرف', 'الشاهل', 'المحابشة', 'كعيدنة', 'خيران المحرق', 'أفلح الشام', 'أفلح اليمن', 'مستبأ', 'بكيل المير', 'وشحة', 'الجميمة', 'كشر', 'شرس', 'المغربة', 'بني قيس'],
                            'حجة': ['مدينة حجة', 'حجة', 'عبس', 'حرض', 'ميدي', 'قفل شمر', 'كحلان عفار', 'كحلان الشرف', 'الشاهل', 'المحابشة', 'كعيدنة', 'خيران المحرق', 'أفلح الشام', 'أفلح اليمن', 'مستبأ', 'بكيل المير', 'وشحة', 'الجميمة', 'كشر', 'شرس', 'المغربة', 'بني قيس'],
                            'SD': ['مدينة صعدة', 'صعدة', 'سحار', 'مجز', 'باقم', 'قطابر', 'منبه', 'رازح', 'غمر', 'شدا', 'الظاهر', 'حيدان', 'ساقين', 'كتاف والبقع', 'الصفراء', 'الحشوة'],
                            'صعدة': ['مدينة صعدة', 'صعدة', 'سحار', 'مجز', 'باقم', 'قطابر', 'منبه', 'رازح', 'غمر', 'شدا', 'الظاهر', 'حيدان', 'ساقين', 'كتاف والبقع', 'الصفراء', 'الحشوة'],
                            'MW': ['مدينة المحويت', 'المحويت', 'شبام كوكبان', 'الطويلة', 'الرجم', 'الخبت', 'ملحان', 'حفاش', 'بني سعد'],
                            'المحويت': ['مدينة المحويت', 'المحويت', 'شبام كوكبان', 'الطويلة', 'الرجم', 'الخبت', 'ملحان', 'حفاش', 'بني سعد'],
                            'DH': ['مدينة ذمار', 'عنس', 'الحداء', 'جهران (معبر)', 'ضوران أنس', 'جبل الشرق', 'المنار', 'عتمة', 'وصاب العالي', 'وصاب السافل'],
                            'ذمار': ['مدينة ذمار', 'عنس', 'الحداء', 'جهران (معبر)', 'ضوران أنس', 'جبل الشرق', 'المنار', 'عتمة', 'وصاب العالي', 'وصاب السافل'],
                            'AM': ['عمران', 'ريدة', 'عيال سريح', 'جبل عيال يزيد', 'خمر', 'حوث', 'العشة', 'قفلة عذر', 'حرف سفيان', 'شهارة', 'مسور', 'ثلاء', 'السودة', 'السود'],
                            'عمران': ['عمران', 'ريدة', 'عيال سريح', 'جبل عيال يزيد', 'خمر', 'حوث', 'العشة', 'قفلة عذر', 'حرف سفيان', 'شهارة', 'مسور', 'ثلاء', 'السودة', 'السود'],
                            'DL': ['الضالع', 'جحاف', 'الأزارق', 'الحصين', 'الشعيب', 'قعطبة', 'دمت', 'جبن', 'الحشاء'],
                            'الضالع': ['الضالع', 'جحاف', 'الأزارق', 'الحصين', 'الشعيب', 'قعطبة', 'دمت', 'جبن', 'الحشاء'],
                            'RY': ['الجبين', 'كسمة', 'الجعفرية', 'السلفية', 'بلاد الطعام', 'مزهر'],
                            'ريمة': ['الجبين', 'كسمة', 'الجعفرية', 'السلفية', 'بلاد الطعام', 'مزهر'],
                            'SU': ['حديبو', 'قلنسية وعبد الكوري'],
                            'أرخبيل سقطرى': ['حديبو', 'قلنسية وعبد الكوري']
                        }
                    }
                },
    
                computed: {
                    availableDistricts() {
                        if (! this.state) {
                            return [];
                        }

                        const st = String(this.state).trim();

                        if (this.yemenDistricts[st]) {
                            return this.yemenDistricts[st];
                        }

                        const statesList = this.countryStates?.['YE'] || [];
                        const found = statesList.find(s => 
                            s.code === st 
                            || s.default_name === st 
                            || (s.id && String(s.id) === st)
                            || (s.default_name && (st.includes(s.default_name) || s.default_name.includes(st)))
                        );

                        if (found) {
                            if (this.yemenDistricts[found.code]) {
                                return this.yemenDistricts[found.code];
                            }
                            if (this.yemenDistricts[found.default_name]) {
                                return this.yemenDistricts[found.default_name];
                            }
                        }

                        for (const key in this.yemenDistricts) {
                            if (key.toLowerCase() === st.toLowerCase() || st.includes(key) || key.includes(st)) {
                                return this.yemenDistricts[key];
                            }
                        }

                        return [];
                    }
                },

                methods: {
                    haveStates() {
                        return !!this.countryStates[this.country]?.length;
                    },
                }
            });
        </script>
    @endpush

</x-shop::layouts.account>
