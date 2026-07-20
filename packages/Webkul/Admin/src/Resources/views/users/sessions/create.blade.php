<x-admin::layouts.anonymous>
    <x-slot:title>
        @lang('admin::app.users.sessions.title')
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
                background: radial-gradient(circle at center, #3C449A, #262F8F, #10143C);
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
                border-bottom: 3px solid #F2C216;
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
                color: #BABCDB;
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
                color: #F2C216;
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

            .admin-input-icon-clickable {
                pointer-events: auto;
                cursor: pointer;
            }

            .admin-form-utils {
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 0.8125rem;
                font-weight: 600;
                margin-bottom: 1.25rem;
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .admin-checkbox-label {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                cursor: pointer;
            }

            .admin-checkbox {
                width: 1.125rem;
                height: 1.125rem;
                border: 2px solid #c6c5d3;
                border-radius: 0;
            }

            .admin-checkbox:checked {
                background-color: #142277;
                border-color: #142277;
            }

            .admin-checkbox-text {
                color: #585c7e;
            }

            .admin-forgot-link {
                color: #735c00;
                text-decoration: none;
            }

            .admin-forgot-link:hover {
                color: #cca730;
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

                <h1 class="admin-visual-title">بوابة دخول الإدارة</h1>
                <p class="admin-visual-subtitle">لنظام هايست</p>

                <div class="admin-icons-grid">
                    <div class="admin-icon-item">
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 7h-4V4c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2zM10 4h4v3h-4V4zm10 16H4V9h16v11z"/>
                                <path d="M9 13h2v2H9zm4 0h2v2h-2zm4 0h2v2h-2zm-8 4h2v2H9zm4 0h2v2h-2zm4 0h2v2h-2z"/>
                            </svg>
                        </span>
                        <span class="admin-icon-label">إدارة المنتجات</span>
                    </div>
                    <div class="admin-icon-item">
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                            </svg>
                        </span>
                        <span class="admin-icon-label">إدارة العملاء</span>
                    </div>
                    <div class="admin-icon-item">
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm13.5-9l1.96 2.5H17V9h2.5zm-1.5 9c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                            </svg>
                        </span>
                        <span class="admin-icon-label">إدارة الشحن</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- قسم النموذج -->
        <section class="admin-form-section">
            <div class="admin-form-container">
                <div class="admin-form-accent"></div>

                <x-admin::form :action="route('admin.session.store')">
                    <div class="admin-form-group">
                        <label class="admin-form-label">اسم المستخدم / البريد الإلكتروني</label>
                        <div class="admin-input-wrapper">
                            <x-admin::form.control-group.control
                                type="email"
                                class="admin-input"
                                id="email"
                                name="email"
                                rules="required|email"
                                :label="trans('admin::app.users.sessions.email')"
                                placeholder="admin@highest.com"
                            />
                            <span class="admin-input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </span>
                        </div>
                        <x-admin::form.control-group.error control-name="email" />
                    </div>

                    <div class="admin-form-group">
                        <label class="admin-form-label">كلمة المرور</label>
                        <div class="admin-input-wrapper">
                            <x-admin::form.control-group.control
                                type="password"
                                class="admin-input"
                                id="password"
                                name="password"
                                rules="required|min:6"
                                :label="trans('admin::app.users.sessions.password')"
                                placeholder="••••••••••••"
                            />
                            <span class="admin-input-icon admin-input-icon-clickable" onclick="switchVisibility()" id="visibilityIcon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                                </svg>
                            </span>
                        </div>
                        <x-admin::form.control-group.error control-name="password" />
                    </div>

                    <div class="admin-form-utils">
                        <label class="admin-checkbox-label">
                            <input type="checkbox" class="admin-checkbox" name="remember">
                            <span class="admin-checkbox-text">تذكرني</span>
                        </label>

                        <a href="{{ route('admin.forget_password.create') }}" class="admin-forgot-link">
                            نسيت كلمة المرور؟
                        </a>
                    </div>

                    <button type="submit" class="admin-submit-btn">
                        <span>دخول النظام</span>
                        <span class="admin-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                                <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                            </svg>
                        </span>
                    </button>
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

    @push('scripts')
        <script>
            // تبديل رؤية كلمة المرور
            function switchVisibility() {
                let passwordField = document.getElementById("password");
                let visibilityIcon = document.getElementById("visibilityIcon");
                
                if (passwordField && visibilityIcon) {
                    if (passwordField.type === "password") {
                        passwordField.type = "text";
                        visibilityIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/></svg>';
                    } else {
                        passwordField.type = "password";
                        visibilityIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="20" height="20"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>';
                    }
                }
            }
        </script>
    @endpush
</x-admin::layouts.anonymous>
