<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('admin::app.users.forget-password.create.page-title')
    </x-slot>

    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&family=Space+Grotesk:wght@700;900&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

        <style>
            /* أيقونات SVG */
            .admin-icon {
                display: inline-block;
                line-height: 1;
            }

            .admin-icon svg {
                width: 100%;
                height: 100%;
                fill: currentColor;
            }

            /* حل المشكلة 1: التجاوب الكامل */
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

            .admin-login-container {
                display: flex;
                width: 100vw;
                height: 100vh;
                overflow: hidden;
            }

            /* القسم المرئي */
            .admin-visual-section {
                display: none;
                width: 50%;
                background: radial-gradient(circle at center, #2e3b8e, #142277, #0a113a);
                position: relative;
                overflow: hidden;
            }

            @media (min-width: 1024px) {
                .admin-visual-section {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 2rem;
                }
            }

            .admin-logo-box {
                background: white;
                padding: 1.5rem;
                border-radius: 0.75rem;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
                border-bottom: 2px solid #cca730;
                margin-bottom: 2rem;
            }

            .admin-logo-box img {
                width: 200px;
                height: auto;
                object-fit: contain;
            }

            .admin-visual-title {
                color: white;
                font-size: 2rem;
                font-weight: 900;
                text-align: center;
                margin-bottom: 0.5rem;
            }

            .admin-visual-subtitle {
                color: #bbc3ff;
                font-size: 1rem;
                text-align: center;
            }

            .admin-icons-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
                margin-top: 2rem;
                max-width: 500px;
            }

            .admin-icon-item {
                background: rgba(255, 255, 255, 0.05);
                padding: 1.5rem 1rem;
                border-radius: 0.5rem;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
            }

            .admin-icon-item .admin-icon {
                color: #e9c349;
                font-size: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
            }

            .admin-icon-label {
                color: rgba(255, 255, 255, 0.9);
                font-size: 0.75rem;
                font-weight: 600;
                line-height: 1.3;
                display: block;
            }

            /* قسم النموذج - متجاوب 100% */
            .admin-form-section {
                width: 100%;
                height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                background: #f3f3f6;
                padding: 1rem;
                overflow-y: auto;
            }

            @media (min-width: 1024px) {
                .admin-form-section {
                    width: 50%;
                }
            }

            .admin-form-container {
                width: 100%;
                max-width: 450px;
                background: white;
                padding: 1.5rem;
                box-shadow: 0 10px 30px rgba(20, 34, 119, 0.08);
                position: relative;
            }

            @media (min-width: 768px) {
                .admin-form-container {
                    padding: 2.5rem;
                }
            }

            .admin-form-accent {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, #142277 0%, #cca730 100%);
            }

            .admin-form-title {
                font-size: 1.5rem;
                font-weight: 900;
                color: #142277;
                margin-bottom: 0.5rem;
                text-align: center;
            }

            .admin-form-description {
                font-size: 0.875rem;
                color: #767683;
                margin-bottom: 2rem;
                text-align: center;
                line-height: 1.6;
            }

            .admin-form-group {
                margin-bottom: 1.25rem;
                margin-top: 1.5rem;
            }

            .admin-form-label {
                display: block;
                font-size: 0.625rem;
                font-weight: 700;
                color: #585c7e;
                text-transform: uppercase;
                letter-spacing: 0.1em;
                margin-bottom: 0.5rem;
            }

            .admin-input-wrapper {
                position: relative;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            v-field {
                display: block;
                flex: 1;
                width: 100%;
            }

            .admin-input {
                width: 100%;
                background: #e8e8ea;
                border: none;
                padding: 0.875rem;
                color: #1a1c1e;
                font-family: 'Inter', 'Cairo', sans-serif;
                font-size: 0.9375rem;
                direction: ltr;
                text-align: left;
                flex: 1;
            }

            .admin-input:focus {
                outline: none;
                box-shadow: 0 0 0 2px #142277;
            }

            .admin-input-icon {
                color: #767683;
                font-size: 20px;
                flex-shrink: 0;
                width: 24px;
                height: 24px;
                text-align: center;
                order: -1;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .admin-submit-btn {
                width: 100%;
                background: #142277 !important;
                color: white !important;
                padding: 1rem 2rem;
                display: flex !important;
                align-items: center;
                justify-content: center;
                gap: 0.75rem;
                font-weight: 700;
                font-size: 1rem;
                border: none !important;
                cursor: pointer;
                margin-top: 0.5rem;
            }

            .admin-submit-btn:hover {
                background: #2e3b8e !important;
            }

            .admin-submit-btn .admin-icon {
                color: white;
                font-size: 20px;
            }

            .admin-back-link {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                color: #142277;
                text-decoration: none;
                font-size: 0.875rem;
                font-weight: 600;
                margin-top: 1.5rem;
            }

            .admin-back-link:hover {
                color: #2e3b8e;
            }

            .admin-back-link .admin-icon {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }

            .admin-secure-badge {
                margin-top: 1.5rem;
                padding-top: 1.5rem;
                border-top: 1px solid rgba(198, 197, 211, 0.15);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                color: #767683;
                opacity: 0.6;
            }

            .admin-secure-icon {
                color: #767683;
                font-size: 16px;
                width: 16px;
                height: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .admin-secure-text {
                font-size: 0.5625rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.1em;
            }

            .admin-bottom-links {
                margin-top: 1.5rem;
                display: flex;
                justify-content: center;
                gap: 1rem;
                font-size: 0.625rem;
                font-weight: 700;
                color: #767683;
                text-transform: uppercase;
                flex-wrap: wrap;
            }

            .admin-bottom-links a {
                color: #767683;
                text-decoration: none;
            }

            .admin-bottom-links a:hover {
                color: #142277;
            }
        </style>
    @endpush

    <div class="admin-login-container">
        <!-- القسم المرئي -->
        <section class="admin-visual-section">
            <div style="position: relative; z-index: 10; display: flex; flex-direction: column; align-items: center;">
                <div class="admin-logo-box">
                    @if ($logo = core()->getConfigData('general.design.admin_logo.logo_image'))
                        <img 
                            src="{{ Storage::url($logo) }}" 
                            alt="{{ config('app.name') }}"
                        >
                    @else
                        <img 
                            src="{{ bagisto_asset('images/logo.svg') }}" 
                            alt="{{ config('app.name') }}"
                        >
                    @endif
                </div>

                <h1 class="admin-visual-title">استرداد كلمة المرور</h1>
                <p class="admin-visual-subtitle">لنظام هايست</p>

                <div class="admin-icons-grid">
                    <div class="admin-icon-item">
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                            </svg>
                        </span>
                        <span class="admin-icon-label">حماية متقدمة</span>
                    </div>
                    <div class="admin-icon-item">
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                            </svg>
                        </span>
                        <span class="admin-icon-label">استرداد آمن</span>
                    </div>
                    <div class="admin-icon-item">
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </span>
                        <span class="admin-icon-label">تأكيد البريد</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- قسم النموذج -->
        <section class="admin-form-section">
            <div class="admin-form-container">
                <div class="admin-form-accent"></div>

                <h2 class="admin-form-title">نسيت كلمة المرور؟</h2>
                <p class="admin-form-description">أدخل بريدك الإلكتروني المسجل وسنرسل لك رابط استرداد كلمة المرور</p>

                <x-admin::form :action="route('admin.forget_password.store')">
                    <div class="admin-form-group">
                        <label class="admin-form-label">البريد الإلكتروني المسجل</label>
                        <div class="admin-input-wrapper">
                            <x-admin::form.control-group.control
                                type="email"
                                class="admin-input"
                                id="email"
                                name="email"
                                rules="required|email"
                                :value="old('email')"
                                :label="trans('admin::app.users.forget-password.create.email')"
                                placeholder="admin@highest.com"
                            />
                            <span class="admin-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                                </svg>
                            </span>
                        </div>
                        <x-admin::form.control-group.error control-name="email" />
                    </div>

                    <button type="submit" class="admin-submit-btn">
                        <span>إرسال رابط الاسترداد</span>
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </span>
                    </button>

                    <div style="text-align: center;">
                        <a href="{{ route('admin.session.create') }}" class="admin-back-link">
                            <span class="admin-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                                    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
                                </svg>
                            </span>
                            <span>العودة لتسجيل الدخول</span>
                        </a>
                    </div>
                </x-admin::form>

                <div class="admin-secure-badge">
                    <span class="admin-secure-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                        </svg>
                    </span>
                    <span class="admin-secure-text">نظام تقني آمن وفق ارقى معايير الامان</span>
                </div>

                <div class="admin-bottom-links">
                    <a href="#">الشروط والأحكام</a>
                    <a href="#">سياسة الخصوصية</a>
                    <a href="#">متجر هايست</a>
                </div>
            </div>
        </section>
    </div>
</x-admin::layouts.anonymous>
