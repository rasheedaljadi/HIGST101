<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.customers.signup-form.page-title')"/>
    <meta name="keywords" content="@lang('shop::app.customers.signup-form.page-title')"/>
@endPush

<!-- Google Fonts -->
@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        /* إعادة تعيين كامل */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: 'Cairo', sans-serif;
        }

        /* الحاوية الرئيسية */
        .shop-signup-container {
            display: flex;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        /* القسم المرئي - مخفي في الموبايل */
        .shop-signup-visual-section {
            display: none;
            width: 50%;
            background: radial-gradient(circle at center, #2e3b8e, #142277, #0a113a);
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .shop-signup-visual-section {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 3rem;
            }
        }

        /* صندوق اللوجو */
        .shop-signup-logo-box {
            background: white;
            padding: 2rem 3rem;
            border-radius: 1rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            border-bottom: 3px solid #cca730;
            margin-bottom: 3rem;
        }

        .shop-signup-logo-box img {
            width: 240px;
            height: auto;
            object-fit: contain;
        }

        /* العناوين */
        .shop-signup-visual-title {
            color: white;
            font-size: 2.5rem;
            font-weight: 900;
            text-align: center;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .shop-signup-visual-subtitle {
            color: #e0e7ff;
            font-size: 1.25rem;
            text-align: center;
            font-weight: 600;
            margin-bottom: 3rem;
        }

        /* شبكة الأيقونات */
        .shop-signup-icons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            max-width: 600px;
            width: 100%;
        }

        .shop-signup-icon-item {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            padding: 2rem 1.5rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .shop-signup-icon-item:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(204, 167, 48, 0.4);
            transform: translateY(-5px);
        }

        .shop-signup-icon-circle {
            width: 64px;
            height: 64px;
            background: #cca730;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(204, 167, 48, 0.3);
        }

        .shop-signup-icon-circle svg {
            width: 32px;
            height: 32px;
            fill: #142277;
        }

        .shop-signup-icon-label {
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
            line-height: 1.4;
        }

        /* بانر الموبايل */
        .mobile-signup-banner {
            display: block;
            background: linear-gradient(135deg, #142277 0%, #2e3b8e 100%);
            padding: 2rem 1.5rem;
            position: relative;
            overflow: hidden;
        }

        @media (min-width: 1024px) {
            .mobile-signup-banner {
                display: none !important;
            }
        }

        .mobile-signup-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(204, 167, 48, 0.2) 0%, transparent 70%);
            border-radius: 50%;
        }

        .mobile-signup-banner-content {
            position: relative;
            z-index: 10;
            text-align: center;
        }

        .mobile-signup-banner-logo {
            background: white;
            display: inline-block;
            padding: 1rem 2.5rem;
            border-radius: 0.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 1.5rem;
        }

        .mobile-signup-banner-logo img {
            height: 40px;
            width: auto;
        }

        .mobile-signup-banner-title {
            color: white;
            font-size: 1.5rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
        }

        .mobile-signup-banner-subtitle {
            color: #e0e7ff;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* قسم النموذج */
        .shop-signup-form-section {
            width: 100%;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #f8f9fa;
            padding: 1.5rem;
            overflow-y: auto;
        }

        @media (min-width: 1024px) {
            .shop-signup-form-section {
                width: 50%;
            }
        }

        .shop-signup-form-container {
            width: 100%;
            max-width: 480px;
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 10px 30px rgba(20, 34, 119, 0.08);
            position: relative;
        }

        @media (min-width: 768px) {
            .shop-signup-form-container {
                padding: 3rem;
            }
        }

        .shop-signup-form-accent {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #142277 0%, #cca730 100%);
        }

        .shop-signup-form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .shop-signup-form-title {
            font-size: 2rem;
            font-weight: 900;
            color: #1a1c1e;
            margin-bottom: 0.5rem;
        }

        .shop-signup-form-subtitle {
            font-size: 1rem;
            color: #767683;
            font-weight: 600;
        }

        .shop-signup-form-group {
            margin-bottom: 1.5rem;
        }

        .shop-signup-form-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #585c7e;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .shop-signup-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .shop-signup-input {
            width: 100%;
            background: #e8e8ea;
            border: none;
            padding: 1rem;
            color: #1a1c1e;
            font-family: 'Inter', 'Cairo', sans-serif;
            font-size: 1rem;
            flex: 1;
            border-radius: 0.25rem;
        }

        .shop-signup-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px #142277;
        }

        .shop-signup-input-icon {
            color: #767683;
            font-size: 20px;
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            order: -1;
        }

        .shop-signup-input-icon svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .shop-signup-submit-btn {
            width: 100%;
            background: #142277;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .shop-signup-submit-btn:hover {
            background: #2e3b8e;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(20, 34, 119, 0.3);
        }

        .shop-signup-submit-btn .shop-signup-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .shop-signup-submit-btn .shop-signup-icon svg {
            width: 100%;
            height: 100%;
            fill: white;
        }

        .shop-signup-checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .shop-signup-checkbox {
            width: 1.125rem;
            height: 1.125rem;
            border: 2px solid #c6c5d3;
            border-radius: 0;
        }

        .shop-signup-checkbox:checked {
            background-color: #142277;
            border-color: #142277;
        }

        .shop-signup-checkbox-text {
            color: #585c7e;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .shop-signup-login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .shop-signup-login-text {
            color: #767683;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .shop-signup-login-text a {
            color: #142277;
            font-weight: 700;
            text-decoration: none;
        }

        .shop-signup-login-text a:hover {
            color: #cca730;
        }

        .shop-signup-footer {
            margin-top: 1.5rem;
            text-align: center;
        }

        .shop-signup-footer-text {
            color: #767683;
            font-size: 0.75rem;
        }

        /* تحسين قراءة النصوص */
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        @media (max-width: 1023px) {
            html, body {
                overflow: auto;
                height: auto;
            }
            
            .shop-signup-container {
                flex-direction: column;
                height: auto;
            }
        }
    </style>
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.customers.signup-form.page-title')
    </x-slot>

	<div class="container mt-20 max-1180:px-5 max-md:mt-12">
        {!! view_render_event('bagisto.shop.customers.sign-up.logo.before') !!}

        <!-- Company Logo -->
        <div class="flex items-center gap-x-14 max-[1180px]:gap-x-9">
            <a
                href="{{ route('shop.home.index') }}"
                class="m-[0_auto_20px_auto]"
                aria-label="@lang('shop::app.customers.signup-form.bagisto')"
            >
                <img
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}"
                    alt="{{ config('app.name') }}"
                    width="131"
                    height="29"
                >
            </a>
        </div>

        {!! view_render_event('bagisto.shop.customers.sign-up.logo.before') !!}

        <!-- Form Container -->
		<div class="m-auto w-full max-w-[870px] rounded-xl border border-zinc-200 p-16 px-[90px] max-md:px-8 max-md:py-8 max-sm:border-none max-sm:p-0">
			<h1 class="font-dmserif text-4xl max-md:text-3xl max-sm:text-xl">
                @lang('shop::app.customers.signup-form.page-title')
            </h1>

			<p class="mt-4 text-xl text-zinc-500 max-sm:mt-0 max-sm:text-sm">
                @lang('shop::app.customers.signup-form.form-signup-text')
            </p>

            <div class="mt-14 rounded max-sm:mt-8">
                <x-shop::form :action="route('shop.customers.register.store')">
                    {!! view_render_event('bagisto.shop.customers.signup_form_controls.before') !!}

                    <!-- First Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.signup-form.first-name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="first_name"
                            rules="required"
                            :value="old('first_name')"
                            :label="trans('shop::app.customers.signup-form.first-name')"
                            :placeholder="trans('shop::app.customers.signup-form.first-name')"
                            :aria-label="trans('shop::app.customers.signup-form.first-name')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="first_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.first_name.after') !!}

                    <!-- Last Name -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.signup-form.last-name')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="text"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="last_name"
                            rules="required"
                            :value="old('last_name')"
                            :label="trans('shop::app.customers.signup-form.last-name')"
                            :placeholder="trans('shop::app.customers.signup-form.last-name')"
                            :aria-label="trans('shop::app.customers.signup-form.last-name')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="last_name" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.last_name.after') !!}

                    <!-- Email -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.signup-form.email')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="email"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="email"
                            rules="required|email"
                            :value="old('email')"
                            :label="trans('shop::app.customers.signup-form.email')"
                            placeholder="email@example.com"
                            :aria-label="trans('shop::app.customers.signup-form.email')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="email" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.email.after') !!}

                    <!-- Password -->
                    <x-shop::form.control-group class="mb-6">
                        <x-shop::form.control-group.label class="required">
                            @lang('shop::app.customers.signup-form.password')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="password"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="password"
                            rules="required|min:6"
                            :value="old('password')"
                            :label="trans('shop::app.customers.signup-form.password')"
                            :placeholder="trans('shop::app.customers.signup-form.password')"
                            ref="password"
                            :aria-label="trans('shop::app.customers.signup-form.password')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="password" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.password.after') !!}

                    <!-- Confirm Password -->
                    <x-shop::form.control-group>
                        <x-shop::form.control-group.label>
                            @lang('shop::app.customers.signup-form.confirm-pass')
                        </x-shop::form.control-group.label>

                        <x-shop::form.control-group.control
                            type="password"
                            class="px-6 py-4 max-md:py-3 max-sm:py-2"
                            name="password_confirmation"
                            rules="confirmed:@password"
                            value=""
                            :label="trans('shop::app.customers.signup-form.password')"
                            :placeholder="trans('shop::app.customers.signup-form.confirm-pass')"
                            :aria-label="trans('shop::app.customers.signup-form.confirm-pass')"
                            aria-required="true"
                        />

                        <x-shop::form.control-group.error control-name="password_confirmation" />
                    </x-shop::form.control-group>

                    {!! view_render_event('bagisto.shop.customers.signup_form.password_confirmation.after') !!}

                    <!-- Captcha -->
                    @if (core()->getConfigData('customer.captcha.credentials.status'))
                        <x-shop::form.control-group>
                            {!! \Webkul\Customer\Facades\Captcha::render() !!}

                            <x-shop::form.control-group.error control-name="recaptcha_token" />
                        </x-shop::form.control-group>
                    @endif

                    <!-- Subscribed Button -->
                    @if (core()->getConfigData('customer.settings.create_new_account_options.news_letter'))
                        <div class="mb-5 flex select-none items-center gap-1.5">
                            <input
                                type="checkbox"
                                name="is_subscribed"
                                id="is-subscribed"
                                class="peer hidden"
                            />

                            <label
                                class="icon-uncheck peer-checked:icon-check-box cursor-pointer text-2xl text-navyBlue peer-checked:text-navyBlue"
                                for="is-subscribed"
                            ></label>

                            <label
                                class="cursor-pointer select-none text-base text-zinc-500 max-sm:text-sm ltr:pl-0 rtl:pr-0"
                                for="is-subscribed"
                            >
                                @lang('shop::app.customers.signup-form.subscribe-to-newsletter')
                            </label>
                        </div>
                    @endif

                    {!! view_render_event('bagisto.shop.customers.signup_form.newsletter_subscription.after') !!}

                    @if(
                        core()->getConfigData('general.gdpr.settings.enabled')
                        && core()->getConfigData('general.gdpr.agreement.enabled')
                    )
                        <div class="mb-2 flex select-none items-center gap-1.5">
                            <x-shop::form.control-group.control
                                type="checkbox"
                                name="agreement"
                                id="agreement"
                                value="0"
                                rules="required"
                                for="agreement"
                            />

                            <label
                                class="cursor-pointer select-none text-base text-zinc-500 max-sm:text-sm"
                                for="agreement"
                                v-pre
                            >
                                {{ core()->getConfigData('general.gdpr.agreement.agreement_label') }}
                            </label>

                            @if (core()->getConfigData('general.gdpr.agreement.agreement_content'))
                                <span
                                    class="cursor-pointer text-base text-navyBlue max-sm:text-sm"
                                    @click="$refs.termsModal.open()"
                                >
                                    @lang('shop::app.customers.signup-form.click-here')
                                </span>
                            @endif
                        </div>

                        <x-shop::form.control-group.error control-name="agreement" />
                    @endif

                    <div class="mt-8 flex flex-wrap items-center gap-9 max-sm:justify-center max-sm:gap-5">
                        <!-- Save Button -->
                        <button
                            class="primary-button m-0 mx-auto block w-full max-w-[374px] rounded-2xl px-11 py-4 text-center text-base max-md:max-w-full max-md:rounded-lg max-md:py-3 max-sm:py-1.5 ltr:ml-0 rtl:mr-0"
                            type="submit"
                        >
                            @lang('shop::app.customers.signup-form.button-title')
                        </button>

                        <div class="flex flex-wrap gap-4">
                            {!! view_render_event('bagisto.shop.customers.login_form_controls.after') !!}
                        </div>
                    </div>

                    {!! view_render_event('bagisto.shop.customers.signup_form_controls.after') !!}

                </x-shop::form>
            </div>

			<p class="mt-5 font-medium text-zinc-500 max-sm:text-center max-sm:text-sm">
                @lang('shop::app.customers.signup-form.account-exists')

                <a class="text-navyBlue"
                    href="{{ route('shop.customer.session.index') }}"
                >
                    @lang('shop::app.customers.signup-form.sign-in-button')
                </a>
            </p>
		</div>

        <p class="mb-4 mt-8 text-center text-xs text-zinc-500">
            @lang('shop::app.customers.signup-form.footer', ['current_year'=> date('Y') ])
        </p>
	</div>

    @push('scripts')
        {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}
    @endpush

    <!-- Terms & Conditions Modal -->
    <x-shop::modal ref="termsModal">
        <x-slot:toggle></x-slot>

        <x-slot:header class="!p-5">
            <p>@lang('shop::app.customers.signup-form.terms-conditions')</p>
        </x-slot>

        <x-slot:content class="!p-5">
            <div class="max-h-[500px] overflow-auto">
                {!! core()->getConfigData('general.gdpr.agreement.agreement_content') !!}
            </div>
        </x-slot>
    </x-shop::modal>
</x-shop::layouts>
