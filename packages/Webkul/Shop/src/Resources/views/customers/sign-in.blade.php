<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.customers.login-form.page-title')"/>
    <meta name="keywords" content="@lang('shop::app.customers.login-form.page-title')"/>
@endPush

<!-- Google Fonts -->
@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: #f3f4f6;
        }

        @media (min-width: 768px) {
            .login-wrapper {
                flex-direction: row;
            }
        }

        /* قسم النموذج - الأيسر */
        .form-section {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: white;
        }

        @media (min-width: 768px) {
            .form-section {
                width: 50%;
            }
        }

        .form-container {
            width: 100%;
            max-width: 28rem;
        }

        .form-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            text-align: right;
        }

        .form-subtitle {
            color: #6b7280;
            margin-bottom: 1.5rem;
            text-align: right;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: right;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            outline: none;
            transition: all 0.2s;
            font-family: 'Cairo', sans-serif;
            font-weight: 400;
        }

        .form-input:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 0;
            border-color: transparent;
        }

        .password-toggle {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            width: 20px;
            height: 20px;
        }

        .password-toggle svg {
            width: 100%;
            height: 100%;
            fill: currentColor;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4b5563;
            font-weight: 400;
        }

        .checkbox-label input {
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
        }

        .forgot-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            background: #1e40af;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-family: 'Cairo', sans-serif;
            letter-spacing: 0.3px;
        }

        .submit-btn:hover {
            background: #1e3a8a;
        }

        .divider {
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #9ca3af;
            font-size: 0.875rem;
            font-weight: 400;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }

        .social-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .social-btn {
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 0.75rem;
            background: white;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            color: #374151;
        }

        .social-btn:hover {
            background: #f9fafb;
        }

        .social-btn svg {
            width: 20px;
            height: 20px;
        }

        /* قسم العلامة التجارية - الأيمن */
        .brand-section {
            display: none;
            width: 50%;
            background: linear-gradient(135deg, #0f1f6b 0%, #1e2f8a 100%);
            color: white;
            padding: 2.5rem;
            flex-direction: column;
            justify-content: center;
        }

        @media (min-width: 768px) {
            .brand-section {
                display: flex;
            }
        }

        .brand-logo {
            margin-bottom: 2.5rem;
            background: white;
            padding: 1.5rem 2.5rem;
            border-radius: 1rem;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .brand-logo img {
            height: 50px;
            width: auto;
        }

        .brand-title {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .brand-subtitle {
            color: #d1d5db;
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 1.25rem;
            border-radius: 1rem;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-5px);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: #fbbf24;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            box-shadow: 0 8px 20px rgba(251, 191, 36, 0.3);
        }

        .feature-icon svg {
            width: 30px;
            height: 30px;
            fill: #1e40af;
        }

        .feature-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.875rem;
        }

        .feature-desc {
            font-size: 0.875rem;
            color: #d1d5db;
            font-weight: 400;
        }

        .signup-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 400;
        }

        .signup-text a {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-text a:hover {
            text-decoration: underline;
        }
    </style>
@endPush

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <x-slot:title>
        @lang('shop::app.customers.login-form.page-title')
    </x-slot>

    <div class="login-wrapper">
        <!-- قسم النموذج - الأيسر -->
        <div class="form-section">
            <div class="form-container">
                <!-- العنوان -->
                <h1 class="form-title">مرحباً بك في هايست</h1>
                <p class="form-subtitle">من متاجر العالم إلى باب منزلك</p>

                <x-shop::form :action="route('shop.customer.session.create')">
                    <!-- البريد الإلكتروني -->
                    <div class="form-group">
                        <label class="form-label">البريد الإلكتروني</label>
                        <x-shop::form.control-group.control
                            type="email"
                            name="email"
                            class="form-input"
                            rules="required|email"
                            :label="trans('shop::app.customers.login-form.email')"
                            placeholder="example@highest.com"
                            style="direction: ltr; text-align: left;"
                            aria-required="true"
                        />
                        <x-shop::form.control-group.error control-name="email" />
                    </div>

                    <!-- كلمة المرور -->
                    <div class="form-group">
                        <label class="form-label">كلمة المرور</label>
                        <div class="input-wrapper">
                            <x-shop::form.control-group.control
                                type="password"
                                name="password"
                                id="password"
                                class="form-input"
                                rules="required|min:6"
                                :label="trans('shop::app.customers.login-form.password')"
                                placeholder="••••••••"
                                style="direction: ltr; text-align: left; padding-left: 3rem;"
                                aria-required="true"
                            />
                            <span class="password-toggle" onclick="togglePassword()" id="passwordToggle">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </span>
                        </div>
                        <x-shop::form.control-group.error control-name="password" />
                    </div>

                    <!-- الخيارات -->
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            تذكرني
                        </label>
                        <a href="{{ route('shop.customers.forgot_password.create') }}" class="forgot-link">
                            نسيت كلمة المرور؟
                        </a>
                    </div>

                    <!-- Captcha -->
                    @if (core()->getConfigData('customer.captcha.credentials.status'))
                        <div class="form-group">
                            {!! \Webkul\Customer\Facades\Captcha::render() !!}
                            <x-shop::form.control-group.error control-name="recaptcha_token" />
                        </div>
                    @endif

                    <!-- زر تسجيل الدخول -->
                    <button type="submit" class="submit-btn">
                        <span>تسجيل الدخول</span>
                        <span>←</span>
                    </button>
                </x-shop::form>

                <!-- الفاصل -->
                <div class="divider">
                    <div class="divider-line"></div>
                    أو سجل الدخول بواسطة
                    <div class="divider-line"></div>
                </div>

                <!-- أزرار التواصل الاجتماعي -->
                @php
                    $socialEnabled = collect([
                        'google' => core()->getConfigData('customer.settings.social_login.enable_google'),
                        'facebook' => core()->getConfigData('customer.settings.social_login.enable_facebook'),
                        'twitter' => core()->getConfigData('customer.settings.social_login.enable_twitter'),
                    ])->filter()->count();
                @endphp

                @if($socialEnabled > 0)
                    <div class="social-buttons">
                        @if (core()->getConfigData('customer.settings.social_login.enable_twitter'))
                            <a href="{{ route('customer.social-login.index', 'twitter') }}" class="social-btn">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932 6.064-6.932zm-1.292 19.49h2.039L6.486 3.24H4.298l13.311 17.403z"/>
                                </svg>
                            </a>
                        @endif

                        @if (core()->getConfigData('customer.settings.social_login.enable_facebook'))
                            <a href="{{ route('customer.social-login.index', 'facebook') }}" class="social-btn">
                                <svg viewBox="0 0 24 24" fill="#1877F2">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                        @endif

                        @if (core()->getConfigData('customer.settings.social_login.enable_google'))
                            <a href="{{ route('customer.social-login.index', 'google') }}" class="social-btn">
                                <svg viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                @endif

                <!-- رابط التسجيل -->
                <p class="signup-text">
                    ليس لديك حساب؟ 
                    <a href="{{ route('shop.customers.register.index') }}">إنشاء حساب جديد</a>
                </p>
            </div>
        </div>

        <!-- قسم العلامة التجارية - الأيمن -->
        <div class="brand-section">
            <!-- اللوجو -->
            <div class="brand-logo">
                <img 
                    src="{{ core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg') }}" 
                    alt="HIGHEST Logo"
                />
            </div>

            <!-- العنوان -->
            <h1 class="brand-title">مرحباً بك في هايست</h1>
            <p class="brand-subtitle">من متاجر العالم إلى باب منزلك</p>

            <!-- المميزات -->
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm13.5-9l1.96 2.5H17V9h2.5zm-1.5 9c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">شحن سريع</h3>
                    <p class="feature-desc">نوصل طلبك بسرعة وأمان</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M20 4H4c-1.11 0-1.99.89-1.99 2L2 18c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V6c0-1.11-.89-2-2-2zm0 14H4v-6h16v6zm0-10H4V6h16v2z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">دفع محلي آمن</h3>
                    <p class="feature-desc">وسائل دفع محلية متعددة</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 11 8.76l1-1.36 1 1.36L15.38 12 17 10.83 14.92 8H20v6z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">منتجات عالمية</h3>
                    <p class="feature-desc">تسوق من أفضل المتاجر</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        {!! \Webkul\Customer\Facades\Captcha::renderJS() !!}

        <script>
            function togglePassword() {
                const passwordField = document.getElementById('password');
                const toggleIcon = document.getElementById('passwordToggle');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    toggleIcon.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                        </svg>
                    `;
                } else {
                    passwordField.type = 'password';
                    toggleIcon.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    `;
                }
            }

            // Form submission feedback
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        const submitBtn = form.querySelector('.submit-btn');
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span>جارِ التحقق...</span>';
                        }
                    });
                }
            });
        </script>
    @endpush
</x-shop::layouts>
