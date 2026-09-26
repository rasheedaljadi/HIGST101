@php
    $channel = core()->getCurrentChannel();
    $channelName = $channel?->name ?? 'HIGEST';
    $logoUrl = $channel?->logo_url ?? bagisto_asset('images/logo.svg');
    $maintenanceText = $channel?->maintenance_mode_text;
    $rawWhatsApp = $channel?->whatsapp_number;
    $isRtl = core()->getCurrentLocale()?->direction === 'rtl' || app()->getLocale() === 'ar';

    // Clean WhatsApp number if exists
    $cleanWhatsApp = $rawWhatsApp ? preg_replace('/[^0-9]/', '', $rawWhatsApp) : null;
@endphp

<x-shop::layouts
    :has-header="false"
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        {{ $channelName }} | {{ $maintenanceText ?: ($isRtl ? 'المتجر في وضع الصيانة والتطوير' : 'Store Under Maintenance') }}
    </x-slot>

    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <style>
            .maintenance-page-root {
                font-family: 'Tajawal', 'Outfit', system-ui, -apple-system, sans-serif;
                min-height: 100vh;
                background: linear-gradient(145deg, #f8faff 0%, #edf2f7 50%, #f1f5f9 100%);
                position: relative;
                overflow-x: hidden;
            }

            /* Ambient background glowing orbs */
            .bg-orb {
                position: absolute;
                border-radius: 50%;
                filter: blur(80px);
                pointer-events: none;
                opacity: 0.6;
                z-index: 0;
            }
            .bg-orb-1 {
                top: -10%;
                right: -5%;
                width: 480px;
                height: 480px;
                background: radial-gradient(circle, rgba(14, 116, 144, 0.18) 0%, rgba(59, 130, 246, 0.08) 70%, transparent 100%);
                animation: orbFloat 14s ease-in-out infinite alternate;
            }
            .bg-orb-2 {
                bottom: -15%;
                left: -8%;
                width: 520px;
                height: 520px;
                background: radial-gradient(circle, rgba(234, 179, 8, 0.15) 0%, rgba(249, 115, 22, 0.06) 70%, transparent 100%);
                animation: orbFloat 18s ease-in-out infinite alternate-reverse;
            }
            .bg-orb-3 {
                top: 40%;
                left: 20%;
                width: 380px;
                height: 380px;
                background: radial-gradient(circle, rgba(99, 102, 241, 0.12) 0%, transparent 80%);
                animation: orbFloat 22s ease-in-out infinite alternate;
            }

            @keyframes orbFloat {
                0% { transform: translate(0, 0) scale(1); }
                50% { transform: translate(30px, -25px) scale(1.08); }
                100% { transform: translate(-20px, 35px) scale(0.95); }
            }

            /* Main Glass Card */
            .glass-maintenance-card {
                background: rgba(255, 255, 255, 0.88);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.8);
                box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.6);
                border-radius: 32px;
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }

            /* E-commerce Float Keyframes */
            @keyframes floatHero {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-12px) rotate(0.8deg); }
            }
            @keyframes floatGentleItem1 {
                0%, 100% { transform: translateY(0px) rotate(-3deg) scale(1); }
                50% { transform: translateY(-14px) rotate(3deg) scale(1.04); }
            }
            @keyframes floatGentleItem2 {
                0%, 100% { transform: translateY(0px) rotate(4deg) scale(1); }
                50% { transform: translateY(-18px) rotate(-2deg) scale(1.03); }
            }
            @keyframes floatGentleItem3 {
                0%, 100% { transform: translateY(0px) rotate(-2deg); }
                50% { transform: translateY(-10px) rotate(2deg); }
            }
            @keyframes sparkleTwinkle {
                0%, 100% { opacity: 0.3; transform: scale(0.8) rotate(0deg); }
                50% { opacity: 1; transform: scale(1.2) rotate(45deg); }
            }
            @keyframes pulseGlow {
                0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
                50% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
            }
            @keyframes wheelRotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            @keyframes gearRotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            @keyframes gearRotateReverse {
                from { transform: rotate(360deg); }
                to { transform: rotate(0deg); }
            }

            .anim-hero-container {
                animation: floatHero 6s ease-in-out infinite;
            }
            .anim-item-1 {
                animation: floatGentleItem1 4.5s ease-in-out infinite;
            }
            .anim-item-2 {
                animation: floatGentleItem2 5.2s ease-in-out infinite 0.6s;
            }
            .anim-item-3 {
                animation: floatGentleItem3 4.8s ease-in-out infinite 1.2s;
            }
            .anim-sparkle {
                animation: sparkleTwinkle 2.5s ease-in-out infinite;
            }
            .anim-gear {
                animation: gearRotate 16s linear infinite;
                transform-origin: center;
            }
            .anim-gear-rev {
                animation: gearRotateReverse 12s linear infinite;
                transform-origin: center;
            }

            /* Live status pulse */
            .pulse-dot {
                animation: pulseGlow 2s infinite;
            }

            /* Primary action button */
            .btn-refresh {
                background: linear-gradient(135deg, #0b1a30 0%, #1e3a8a 100%);
                box-shadow: 0 10px 25px -5px rgba(14, 30, 70, 0.3);
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .btn-refresh:hover {
                transform: translateY(-2px);
                box-shadow: 0 16px 30px -5px rgba(14, 30, 70, 0.45);
                background: linear-gradient(135deg, #071324 0%, #1e40af 100%);
            }
            .btn-refresh:active {
                transform: translateY(0);
            }

            /* WhatsApp button */
            .btn-wa {
                background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
                box-shadow: 0 10px 25px -5px rgba(37, 211, 102, 0.35);
                transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            }
            .btn-wa:hover {
                transform: translateY(-2px);
                box-shadow: 0 16px 30px -5px rgba(37, 211, 102, 0.5);
                background: linear-gradient(135deg, #22c35e 0%, #0d7367 100%);
            }

            /* Progress Bar */
            .auto-check-progress {
                height: 4px;
                background: #e2e8f0;
                border-radius: 999px;
                overflow: hidden;
            }
            .auto-check-bar {
                height: 100%;
                background: linear-gradient(90deg, #3b82f6, #10b981);
                width: 0%;
                animation: progressCycle 60s linear infinite;
            }
            @keyframes progressCycle {
                0% { width: 0%; }
                100% { width: 100%; }
            }
        </style>
    @endpush

    <!-- Maintenance Page Root Container -->
    <div class="maintenance-page-root flex min-h-screen items-center justify-center px-4 py-10 sm:px-6 lg:px-8">
        <!-- Ambient Glowing Background Elements -->
        <div class="bg-orb bg-orb-1"></div>
        <div class="bg-orb bg-orb-2"></div>
        <div class="bg-orb bg-orb-3"></div>

        <!-- Main Content Wrapper -->
        <div class="relative z-10 w-full max-w-4xl">
            <div class="glass-maintenance-card p-6 sm:p-10 md:p-12 text-center">

                <!-- 1. Store Brand / Logo Header -->
                <div class="flex flex-col items-center justify-center">
                    <div class="group relative inline-flex items-center justify-center rounded-2xl bg-white/90 p-4 shadow-sm ring-1 ring-slate-900/5 transition-all duration-300 hover:shadow-md">
                        <img
                            src="{{ $logoUrl }}"
                            alt="{{ $channelName }}"
                            class="h-12 w-auto max-w-[220px] object-contain sm:h-14 md:h-16"
                            onerror="this.style.display='none'; document.getElementById('logo-text-fallback').style.display='block';"
                        />
                        <div id="logo-text-fallback" style="display: none;" class="text-2xl font-black tracking-wider text-navyBlue">
                            {{ $channelName }}
                        </div>
                    </div>

                    <!-- 2. Live Status Pill -->
                    <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-emerald-200/80 bg-emerald-50/90 px-4 py-1.5 text-xs sm:text-sm font-semibold text-emerald-800 backdrop-blur-sm shadow-sm">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="pulse-dot absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <span>
                            @if ($isRtl)
                                أعمال التطوير والتحسين جارية الآن
                            @else
                                System Upgrade & Maintenance in Progress
                            @endif
                        </span>
                    </div>
                </div>

                <!-- 3. Animated E-Commerce Visual Illustration -->
                <div class="relative mx-auto my-8 flex h-52 sm:h-64 w-full max-w-lg items-center justify-center">
                    <!-- Ambient Glow Behind Illustration -->
                    <div class="absolute h-40 w-40 sm:h-48 sm:w-48 rounded-full bg-gradient-to-tr from-amber-200/40 via-sky-200/50 to-indigo-200/30 blur-2xl"></div>

                    <!-- Subtle Animated Tech Gears in Background -->
                    <svg class="anim-gear absolute -top-2 right-12 h-12 w-12 text-slate-300/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>

                    <svg class="anim-gear-rev absolute bottom-2 left-10 h-10 w-10 text-amber-300/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>

                    <!-- Floating E-Commerce Item 1: Luxury Gift Box (Left) -->
                    <div class="anim-item-1 absolute -left-2 sm:left-4 top-6 z-20">
                        <div class="flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 p-2.5 shadow-lg shadow-amber-500/25 ring-2 ring-white">
                            <!-- Gift Box SVG -->
                            <svg class="h-full w-full text-white" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2a3 3 0 00-2.83 2H5a2 2 0 00-2 2v2a1 1 0 001 1h1v10a3 3 0 003 3h8a3 3 0 003-3V9h1a1 1 0 001-1V6a2 2 0 00-2-2h-4.17A3 3 0 0012 2zm-1 3a1 1 0 011-1 1 1 0 011 1v1h-2V5zm-6 3V6h6v2H5zm8 0V6h6v2h-6zm-6 2h5v10H8a1 1 0 01-1-1V10zm7 10V10h5v9a1 1 0 01-1 1h-4z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Floating E-Commerce Item 2: Shopping Tag / Coupon (Right) -->
                    <div class="anim-item-2 absolute -right-2 sm:right-6 top-8 z-20">
                        <div class="flex h-14 w-14 sm:h-16 sm:w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 p-2.5 shadow-lg shadow-blue-500/25 ring-2 ring-white">
                            <!-- Tag / Coupon SVG -->
                            <svg class="h-full w-full text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V5a2 2 0 012-2z" />
                                <circle cx="7.5" cy="7.5" r="1.5" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Floating E-Commerce Item 3: Fast Delivery Box (Bottom) -->
                    <div class="anim-item-3 absolute right-16 -bottom-3 sm:right-20 z-20">
                        <div class="flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-2 shadow-lg shadow-emerald-500/25 ring-2 ring-white">
                            <!-- Package SVG -->
                            <svg class="h-full w-full text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Sparkles (Stars) -->
                    <div class="anim-sparkle absolute left-14 top-2 text-amber-400">
                        <svg class="h-6 w-6 fill-current" viewBox="0 0 24 24"><path d="M12 2l2.4 7.2h7.6l-6.2 4.5 2.4 7.3-6.2-4.5-6.2 4.5 2.4-7.3-6.2-4.5h7.6z"/></svg>
                    </div>
                    <div class="anim-sparkle absolute right-24 top-2 text-sky-400" style="animation-delay: 1.2s;">
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24"><path d="M12 2l2.4 7.2h7.6l-6.2 4.5 2.4 7.3-6.2-4.5-6.2 4.5 2.4-7.3-6.2-4.5h7.6z"/></svg>
                    </div>

                    <!-- Center Main Hero: Modern Floating Shopping Cart with Bags -->
                    <div class="anim-hero-container relative z-10 flex flex-col items-center justify-center">
                        <div class="relative flex h-32 w-32 sm:h-36 sm:w-36 items-center justify-center rounded-3xl bg-gradient-to-tr from-[#0b1a30] via-[#1e3a8a] to-[#2563eb] p-6 shadow-2xl shadow-blue-900/35 ring-4 ring-white/90">
                            <!-- Shopping Cart SVG with modern gradients -->
                            <svg class="h-full w-full text-white drop-shadow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="21" r="1.5" fill="currentColor"/>
                                <circle cx="20" cy="21" r="1.5" fill="currentColor"/>
                                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                                <path d="M12 9v4m-2-2h4" stroke="#fde047" stroke-width="2.5" />
                            </svg>
                        </div>
                        <!-- Soft Reflection Ground Shadow -->
                        <div class="mt-4 h-3 w-28 sm:w-32 rounded-full bg-slate-400/20 blur-sm"></div>
                    </div>
                </div>

                <!-- 4. Headline & Friendly Reassurance Copy -->
                <div class="mx-auto max-w-2xl">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold tracking-tight text-slate-900 leading-snug">
                        @if ($isRtl)
                            نعمل على تطوير المتجر لخدمتكم بشكل أفضل
                        @else
                            We're Upgrading the Store to Serve You Better
                        @endif
                    </h1>

                    <!-- Custom Maintenance Text from Channel Settings -->
                    @if (! empty($maintenanceText))
                        <div class="my-5 inline-flex items-center gap-3 rounded-2xl border border-blue-100 bg-blue-50/80 px-5 py-3 text-sm sm:text-base font-medium text-blue-900 shadow-sm">
                            <svg class="h-5 w-5 shrink-0 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $maintenanceText }}</span>
                        </div>
                    @endif

                    <p class="mt-3 text-base sm:text-lg text-slate-600 leading-relaxed">
                        @if ($isRtl)
                            المتجر يخضع حالياً لبعض التحديثات المجدولة وإضافة ميزات جديدة لضمان سرعة التصفح وتوفير تجربة تسوق وشراء أكثر سلاسة وأماناً. سنعود إليكم بكامل طاقتنا في أقرب وقت!
                        @else
                            Our store is currently undergoing scheduled improvements to bring you a faster, smoother, and more delightful shopping experience. We will be back online shortly!
                        @endif
                    </p>
                </div>

                <!-- 5. Reassurance Feature Badges (3 Pillars) -->
                <div class="my-8 grid grid-cols-1 gap-3.5 sm:grid-cols-3 sm:gap-4 text-start">
                    <!-- Feature 1 -->
                    <div class="flex items-center gap-3.5 rounded-2xl border border-slate-100 bg-white/70 p-3.5 sm:p-4 shadow-sm backdrop-blur-sm transition hover:bg-white">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-800">
                                {{ $isRtl ? 'أحدث المنتجات والعروض' : 'Latest Products & Deals' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $isRtl ? 'تحديث المخزون وقوائم الأسعار' : 'Stock & offers refreshing' }}
                            </div>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="flex items-center gap-3.5 rounded-2xl border border-slate-100 bg-white/70 p-3.5 sm:p-4 shadow-sm backdrop-blur-sm transition hover:bg-white">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-800">
                                {{ $isRtl ? 'تجربة تصفح أسرع' : 'Faster Shopping Flow' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $isRtl ? 'ترقية استجابة وسرعة الخوادم' : 'Enhanced system speed' }}
                            </div>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="flex items-center gap-3.5 rounded-2xl border border-slate-100 bg-white/70 p-3.5 sm:p-4 shadow-sm backdrop-blur-sm transition hover:bg-white">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-800">
                                {{ $isRtl ? 'أعلى معايير الأمان' : 'Secured Transactions' }}
                            </div>
                            <div class="text-xs text-slate-500">
                                {{ $isRtl ? 'حماية مشددة لبيانات العملاء' : 'Protected checkout & data' }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Action Buttons (Refresh / WhatsApp Support) -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-3.5 sm:gap-4">
                    <!-- Refresh Button -->
                    <button
                        type="button"
                        onclick="handleRefreshStore(this)"
                        class="btn-refresh inline-flex items-center justify-center gap-2.5 rounded-full px-8 py-3.5 text-sm sm:text-base font-bold text-white cursor-pointer"
                    >
                        <svg id="refresh-icon" class="h-5 w-5 transition-transform duration-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>
                            {{ $isRtl ? 'فحص حالة المتجر الآن' : 'Check Store Status Now' }}
                        </span>
                    </button>

                    <!-- WhatsApp Support Button (If configured) -->
                    @if ($cleanWhatsApp)
                        @php
                            $waMessage = $isRtl
                                ? 'مرحباً، أود الاستفسار بخصوص المتجر خلال فترة الصيانة'
                                : 'Hello, I would like to inquire about the store during maintenance.';
                            $waUrl = "https://wa.me/{$cleanWhatsApp}?" . http_build_query(['text' => $waMessage]);
                        @endphp
                        <a
                            href="{{ $waUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-wa inline-flex items-center justify-center gap-2.5 rounded-full px-7 py-3.5 text-sm sm:text-base font-bold text-white transition hover:opacity-95"
                        >
                            <!-- WhatsApp Icon -->
                            <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                            </svg>
                            <span>
                                {{ $isRtl ? 'تواصل معنا عبر واتساب' : 'Chat via WhatsApp' }}
                            </span>
                        </a>
                    @endif
                </div>

                <!-- 7. Auto-Check Progress Indicator -->
                <div class="mx-auto mt-8 max-w-xs text-center">
                    <div class="auto-check-progress">
                        <div class="auto-check-bar"></div>
                    </div>
                    <span class="mt-2 block text-xs text-slate-400">
                        {{ $isRtl ? 'يتم التحقق تلقائياً من عودة المتجر كل 60 ثانية' : 'Auto-checking store status every 60s' }}
                    </span>
                </div>

            </div>

            <!-- Footer Minimal Note -->
            <div class="mt-6 text-center text-xs text-slate-500 font-medium">
                &copy; {{ date('Y') }} {{ $channelName }}. {{ $isRtl ? 'جميع الحقوق محفوظة.' : 'All rights reserved.' }}
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function handleRefreshStore(btn) {
                const icon = document.getElementById('refresh-icon');
                if (icon) {
                    icon.classList.add('animate-spin');
                }
                btn.classList.add('opacity-80', 'pointer-events-none');
                setTimeout(function() {
                    window.location.reload();
                }, 400);
            }

            // Auto-refresh periodic checker (every 60 seconds)
            setTimeout(function() {
                window.location.reload();
            }, 60000);
        </script>
    @endpush
</x-shop::layouts>
