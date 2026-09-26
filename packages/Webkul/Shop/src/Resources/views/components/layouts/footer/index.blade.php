{!! view_render_event('bagisto.shop.layout.footer.before') !!}

@inject('themeCustomizationRepository', 'Webkul\Theme\Repositories\ThemeCustomizationRepository')

@php
    $channel = core()->getCurrentChannel();
    $whatsappNumber = $channel->whatsapp_number ?? '967777000000';
    $cleanWhatsapp = preg_replace('/[^0-9]/', '', $whatsappNumber);

    $channelDescription = $channel?->description;

    if (empty($channelDescription) && $channel) {
        foreach ($channel->translations as $translation) {
            if (! empty($translation->description)) {
                $channelDescription = $translation->description;
                break;
            }
        }
    }

    if (empty($channelDescription)) {
        $channelDescription = 'تجربة تسوق إلكتروني تجمع بين التنوع والجودة وسهولة الشراء، نوفر لك أفضل المنتجات من أشهر العلامات التجارية.';
    }

    // Dynamic Footer Links from Theme Customization
    $footerCustomization = null;

    try {
        $footerCustomization = $themeCustomizationRepository->findOneWhere([
            'type'       => 'footer_links',
            'status'     => 1,
            'channel_id' => $channel->id,
        ]);

        if (! $footerCustomization) {
            $footerCustomization = $themeCustomizationRepository->findOneWhere([
                'type'   => 'footer_links',
                'status' => 1,
            ]);
        }

        if (! $footerCustomization) {
            $footerCustomization = $themeCustomizationRepository->findOneWhere([
                'type' => 'footer_links',
            ]);
        }
    } catch (\Throwable $e) {
    }

    $footerColumns = $footerCustomization?->options ?? [];

    if (empty($footerColumns) && $footerCustomization) {
        foreach ($footerCustomization->translations as $translation) {
            if (! empty($translation->options) && is_array($translation->options)) {
                $footerColumns = $translation->options;
                break;
            }
        }
    }

    $activeLinkColumns = [];

    if (! empty($footerColumns) && is_array($footerColumns)) {
        foreach ($footerColumns as $colKey => $links) {
            if (! empty($links) && is_array($links)) {
                usort($links, function ($a, $b) {
                    return ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0);
                });
                $activeLinkColumns[$colKey] = $links;
            }
        }
    }

    // Fallback default columns if Theme Customization has no links yet
    if (empty($activeLinkColumns)) {
        $activeLinkColumns = [
            'column_1' => [
                ['url' => route('shop.search.index'), 'title' => 'جميع المنتجات', 'sort_order' => 1],
                ['url' => route('shop.home.index'), 'title' => 'جميع الفئات', 'sort_order' => 2],
                ['url' => route('shop.home.index') . '#flash-deals', 'title' => 'العروض', 'sort_order' => 3],
                ['url' => route('shop.search.index', ['new' => 1]), 'title' => 'المنتجات الجديدة', 'sort_order' => 4],
                ['url' => route('shop.search.index', ['sort' => 'popularity-desc']), 'title' => 'الأكثر مبيعاً', 'sort_order' => 5],
            ],
            'column_2' => [
                ['url' => 'https://wa.me/' . $cleanWhatsapp, 'title' => 'تواصل معنا', 'sort_order' => 1],
                ['url' => route('shop.customers.account.profile.index'), 'title' => 'حسابي', 'sort_order' => 2],
                ['url' => route('shop.customers.account.orders.index'), 'title' => 'تتبع الطلب', 'sort_order' => 3],
                ['url' => route('shop.cms.page', 'customer-service'), 'title' => 'الأسئلة الشائعة', 'sort_order' => 4],
                ['url' => 'https://wa.me/' . $cleanWhatsapp, 'title' => 'الدعم والمساعدة', 'sort_order' => 5],
            ],
            'column_3' => [
                ['url' => route('shop.cms.page', 'about-us'), 'title' => 'معلومات عنا', 'sort_order' => 1],
                ['url' => route('shop.cms.page', 'privacy-policy'), 'title' => 'سياسة الخصوصية', 'sort_order' => 2],
                ['url' => route('shop.cms.page', 'payment-policy'), 'title' => 'سياسة الدفع', 'sort_order' => 3],
                ['url' => route('shop.cms.page', 'shipping-policy'), 'title' => 'سياسة الشحن', 'sort_order' => 4],
                ['url' => route('shop.cms.page', 'refund-policy'), 'title' => 'سياسة الاسترداد', 'sort_order' => 5],
                ['url' => route('shop.cms.page', 'return-policy'), 'title' => 'سياسة الإرجاع', 'sort_order' => 6],
                ['url' => route('shop.cms.page', 'terms-conditions'), 'title' => 'شروط الاستخدام والأحكام', 'sort_order' => 7],
            ],
        ];
    }

    $defaultTitles = [
        'column_1' => 'خدمة العملاء',
        'column_2' => 'المعلومات والسياسات',
        'column_3' => 'التسوق',
    ];

    $linkColCount = max(1, count($activeLinkColumns));
@endphp

<footer
    v-pre
    class="highest-footer-wrapper select-none"
    style="width: 100% !important; background-color: #001A54 !important; color: #ffffff !important; position: relative; overflow: hidden; display: block; margin-top: 2rem; direction: rtl; text-align: right;"
>
    <script>
        (function() {
            if (!document.getElementById('highest-footer-styles')) {
                var s = document.createElement('style');
                s.id = 'highest-footer-styles';
                s.textContent = `
                    .highest-footer-wrapper {
                        background-color: #001A54 !important;
                        color: #ffffff !important;
                    }
                    .highest-footer-main {
                        width: 100%;
                        padding-top: 3rem;
                        padding-bottom: 2.5rem;
                        position: relative;
                        z-index: 10;
                    }
                    /* Desktop Screens (min-width: 1025px): 4 Columns side-by-side */
                    .highest-footer-grid {
                        display: grid !important;
                        grid-template-columns: 1.8fr {{ str_repeat('1fr ', $linkColCount) }} 1.2fr !important;
                        gap: 2.25rem !important;
                        align-items: start !important;
                        width: 100% !important;
                    }
                    .highest-footer-section {
                        width: 100% !important;
                        max-width: 100% !important;
                    }
                    .highest-footer-brand-section {
                        max-width: 320px !important;
                    }
                    .highest-footer-link-section {
                        min-width: 120px !important;
                    }
                    .highest-footer-app-section {
                        max-width: 240px !important;
                    }
                    .highest-footer-title {
                        color: #ffffff !important;
                        font-size: 1rem !important;
                        font-weight: 700 !important;
                        margin-bottom: 0.75rem !important;
                        position: relative !important;
                        display: inline-block !important;
                    }
                    .highest-footer-title-bar {
                        display: block !important;
                        width: 2rem !important;
                        height: 2.5px !important;
                        background-color: #F5B800 !important;
                        border-radius: 9999px !important;
                        margin-top: 0.35rem !important;
                    }
                    .highest-footer-list {
                        list-style: none !important;
                        padding: 0 !important;
                        margin: 0 !important;
                        display: flex !important;
                        flex-direction: column !important;
                        gap: 0.65rem !important;
                    }
                    .highest-footer-link {
                        color: #cbd5e1 !important;
                        transition: all 0.2s ease !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 0.5rem !important;
                        font-size: 0.84rem !important;
                        text-decoration: none !important;
                    }
                    .highest-footer-link:hover {
                        color: #F5B800 !important;
                        transform: translateX(-3px) !important;
                    }
                    .highest-social-icon {
                        width: 32px !important;
                        height: 32px !important;
                        border-radius: 9999px !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        transition: transform 0.2s ease, opacity 0.2s ease !important;
                        text-decoration: none !important;
                    }
                    .highest-social-icon:hover {
                        transform: scale(1.1) !important;
                        opacity: 0.95 !important;
                    }
                    .highest-app-container {
                        display: flex !important;
                        flex-direction: column !important;
                        gap: 0.65rem !important;
                    }
                    .highest-app-badge {
                        background-color: #000000 !important;
                        color: #ffffff !important;
                        border: 1px solid rgba(255, 255, 255, 0.25) !important;
                        border-radius: 0.65rem !important;
                        padding: 0.4rem 0.85rem !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        gap: 0.65rem !important;
                        transition: all 0.2s ease !important;
                        width: 140px !important;
                        text-decoration: none !important;
                    }
                    .highest-app-badge:hover {
                        border-color: rgba(255, 255, 255, 0.55) !important;
                        transform: translateY(-1px) !important;
                    }
                    .highest-pay-badge {
                        background-color: #ffffff !important;
                        border-radius: 0.35rem !important;
                        height: 24px !important;
                        padding: 0 0.45rem !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
                    }
                    /* Tablet Landscape & Medium Desktops (881px - 1024px): 4 Columns with tighter gap */
                    @media (min-width: 881px) and (max-width: 1024px) {
                        .highest-footer-grid {
                            grid-template-columns: 1.6fr {{ str_repeat('1fr ', $linkColCount) }} 1.2fr !important;
                            gap: 1.5rem !important;
                        }
                    }
                    /* Tablet Portrait (641px - 880px, including 768px): 2 Columns side-by-side */
                    @media (min-width: 641px) and (max-width: 880px) {
                        .highest-footer-grid {
                            grid-template-columns: repeat(2, 1fr) !important;
                            gap: 2rem 1.5rem !important;
                        }
                        .highest-footer-brand-section {
                            max-width: 100% !important;
                        }
                        .highest-footer-app-section {
                            max-width: 100% !important;
                        }
                    }
                    /* Mobile: Convert columns to consecutive sections (<= 640px) */
                    @media (max-width: 640px) {
                        .highest-footer-main {
                            padding-top: 2rem !important;
                            padding-bottom: 1.5rem !important;
                        }
                        .highest-footer-grid {
                            display: flex !important;
                            flex-direction: column !important;
                            gap: 0 !important;
                            width: 100% !important;
                        }
                        .highest-footer-section {
                            width: 100% !important;
                            padding-bottom: 1.5rem !important;
                            margin-bottom: 1.5rem !important;
                            border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
                        }
                        .highest-footer-section:last-child {
                            border-bottom: none !important;
                            margin-bottom: 0 !important;
                            padding-bottom: 0.5rem !important;
                        }
                        .highest-footer-title {
                            font-size: 1.05rem !important;
                            margin-bottom: 0.85rem !important;
                        }
                        .highest-footer-desc {
                            max-width: 100% !important;
                        }
                        .highest-footer-list {
                            gap: 0.75rem !important;
                        }
                        .highest-footer-link {
                            font-size: 0.9rem !important;
                            padding: 0.25rem 0 !important;
                        }
                        .highest-app-container {
                            flex-direction: row !important;
                            flex-wrap: wrap !important;
                            gap: 0.75rem !important;
                        }
                        .highest-app-badge {
                            flex: 1 1 140px !important;
                            max-width: 180px !important;
                            justify-content: center !important;
                        }
                        .highest-footer-bottom {
                            flex-direction: column !important;
                            justify-content: center !important;
                            text-align: center !important;
                            gap: 1.25rem !important;
                        }
                        .highest-footer-bottom-legal {
                            justify-content: center !important;
                            text-align: center !important;
                        }
                        .highest-footer-bottom-payments {
                            justify-content: center !important;
                            width: 100% !important;
                        }
                        .highest-footer-bottom-badges {
                            justify-content: center !important;
                        }
                    }
                `;
                document.head.appendChild(s);
            }
        })();
    </script>
    <style>
        .highest-footer-wrapper {
            background-color: #001A54 !important;
            color: #ffffff !important;
        }
        .highest-footer-main {
            width: 100%;
            padding-top: 3rem;
            padding-bottom: 2.5rem;
            position: relative;
            z-index: 10;
        }
        /* Desktop Screens (min-width: 1025px): 4 Columns side-by-side */
        .highest-footer-grid {
            display: grid !important;
            grid-template-columns: 1.8fr {{ str_repeat('1fr ', $linkColCount) }} 1.2fr !important;
            gap: 2.25rem !important;
            align-items: start !important;
            width: 100% !important;
        }
        .highest-footer-section {
            width: 100% !important;
            max-width: 100% !important;
        }
        .highest-footer-brand-section {
            max-width: 320px !important;
        }
        .highest-footer-link-section {
            min-width: 120px !important;
        }
        .highest-footer-app-section {
            max-width: 240px !important;
        }
        .highest-footer-title {
            color: #ffffff !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
            margin-bottom: 0.75rem !important;
            position: relative !important;
            display: inline-block !important;
        }
        .highest-footer-title-bar {
            display: block !important;
            width: 2rem !important;
            height: 2.5px !important;
            background-color: #F5B800 !important;
            border-radius: 9999px !important;
            margin-top: 0.35rem !important;
        }
        .highest-footer-list {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 0.65rem !important;
        }
        .highest-footer-link {
            color: #cbd5e1 !important;
            transition: all 0.2s ease !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            font-size: 0.84rem !important;
            text-decoration: none !important;
        }
        .highest-footer-link:hover {
            color: #F5B800 !important;
            transform: translateX(-3px) !important;
        }
        .highest-social-icon {
            width: 32px !important;
            height: 32px !important;
            border-radius: 9999px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: transform 0.2s ease, opacity 0.2s ease !important;
            text-decoration: none !important;
        }
        .highest-social-icon:hover {
            transform: scale(1.1) !important;
            opacity: 0.95 !important;
        }
        .highest-app-container {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.65rem !important;
        }
        .highest-app-badge {
            background-color: #000000 !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            border-radius: 0.65rem !important;
            padding: 0.4rem 0.85rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.65rem !important;
            transition: all 0.2s ease !important;
            width: 140px !important;
            text-decoration: none !important;
        }
        .highest-app-badge:hover {
            border-color: rgba(255, 255, 255, 0.55) !important;
            transform: translateY(-1px) !important;
        }
        .highest-pay-badge {
            background-color: #ffffff !important;
            border-radius: 0.35rem !important;
            height: 24px !important;
            padding: 0 0.45rem !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1) !important;
        }
        /* Tablet Landscape & Medium Desktops (881px - 1024px): 4 Columns with tighter gap */
        @media (min-width: 881px) and (max-width: 1024px) {
            .highest-footer-grid {
                grid-template-columns: 1.6fr {{ str_repeat('1fr ', $linkColCount) }} 1.2fr !important;
                gap: 1.5rem !important;
            }
        }
        /* Tablet Portrait (641px - 880px, including 768px): 2 Columns side-by-side */
        @media (min-width: 641px) and (max-width: 880px) {
            .highest-footer-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 2rem 1.5rem !important;
            }
            .highest-footer-brand-section {
                max-width: 100% !important;
            }
            .highest-footer-app-section {
                max-width: 100% !important;
            }
        }
        /* Mobile: Convert columns to consecutive sections (<= 640px) */
        @media (max-width: 640px) {
            .highest-footer-main {
                padding-top: 2rem !important;
                padding-bottom: 1.5rem !important;
            }
            .highest-footer-grid {
                display: flex !important;
                flex-direction: column !important;
                gap: 0 !important;
                width: 100% !important;
            }
            .highest-footer-section {
                width: 100% !important;
                padding-bottom: 1.5rem !important;
                margin-bottom: 1.5rem !important;
                border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
            }
            .highest-footer-section:last-child {
                border-bottom: none !important;
                margin-bottom: 0 !important;
                padding-bottom: 0.5rem !important;
            }
            .highest-footer-title {
                font-size: 1.05rem !important;
                margin-bottom: 0.85rem !important;
            }
            .highest-footer-desc {
                max-width: 100% !important;
            }
            .highest-footer-list {
                gap: 0.75rem !important;
            }
            .highest-footer-link {
                font-size: 0.9rem !important;
                padding: 0.25rem 0 !important;
            }
            .highest-app-container {
                flex-direction: row !important;
                flex-wrap: wrap !important;
                gap: 0.75rem !important;
            }
            .highest-app-badge {
                flex: 1 1 140px !important;
                max-width: 180px !important;
                justify-content: center !important;
            }
            .highest-footer-bottom {
                flex-direction: column !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 1.25rem !important;
            }
            .highest-footer-bottom-legal {
                justify-content: center !important;
                text-align: center !important;
            }
            .highest-footer-bottom-payments {
                justify-content: center !important;
                width: 100% !important;
            }
            .highest-footer-bottom-badges {
                justify-content: center !important;
            }
        }
    </style>
    <!-- Faint Watermark Logo on Far-Left (Translucent Blue Outline) -->
    <div style="position: absolute; left: -20px; bottom: 10px; pointer-events: none; user-select: none; opacity: 0.05; z-index: 1;">
        <svg width="260" height="260" viewBox="0 0 200 200" fill="none" stroke="#ffffff">
            <path d="M60 70V45a40 40 0 0 1 80 0v25" stroke-width="16" stroke-linecap="round"/>
            <rect x="25" y="70" width="150" height="110" rx="24" stroke-width="16"/>
        </svg>
    </div>

    <!-- ========================================================== -->
    <!-- 1. Footer Main Area                                        -->
    <!-- ========================================================== -->
    <div class="highest-footer-main">
        <div style="width: 100%; max-width: 1400px; margin-left: auto; margin-right: auto; padding-left: 1.5rem; padding-right: 1.5rem;">
            <div class="highest-footer-grid">

                <!-- -------------------------------------------------- -->
                <!-- Section 1 (RTL: Rightmost): HIGHEST Brand & Social -->
                <!-- -------------------------------------------------- -->
                <div class="highest-footer-section highest-footer-brand-section" style="display: flex; flex-direction: column; align-items: flex-start;">
                    <!-- HIGHEST White/Yellow Logo directly on dark background (No white box) -->
                    <a
                        href="{{ route('shop.home.index') }}"
                        style="display: inline-block; text-decoration: none;"
                        aria-label="HIGHEST"
                    >
                        <img
                            src="{{ asset('images/footer-logo.png') }}"
                            alt="HIGHEST"
                            style="height: 44px; width: auto; max-width: 160px; object-fit: contain;"
                            width="160"
                            height="44"
                        >
                    </a>

                    <!-- Short Store Description -->
                    <p class="highest-footer-desc" style="color: #cbd5e1; font-size: 0.85rem; line-height: 1.6; margin-top: 0.85rem; margin-bottom: 0; max-width: 320px;">
                        {!! nl2br(e($channelDescription)) !!}
                    </p>

                    <!-- Social Media Links -->
                    @php
                        $socialLinks = $channel->social_links ?? [];
                        if (is_string($socialLinks)) {
                            $socialLinks = json_decode($socialLinks, true) ?: [];
                        }

                        $facebookUrl = trim($socialLinks['facebook'] ?? '');
                        $instagramUrl = trim($socialLinks['instagram'] ?? '');
                        $twitterUrl = trim($socialLinks['twitter'] ?? '');
                        $youtubeUrl = trim($socialLinks['youtube'] ?? '');
                        $tiktokUrl = trim($socialLinks['tiktok'] ?? '');
                        $snapchatUrl = trim($socialLinks['snapchat'] ?? '');
                        $telegramUrl = trim($socialLinks['telegram'] ?? '');

                        $hasCustomSocial = ! empty($facebookUrl) 
                            || ! empty($instagramUrl) 
                            || ! empty($twitterUrl) 
                            || ! empty($youtubeUrl) 
                            || ! empty($tiktokUrl) 
                            || ! empty($snapchatUrl) 
                            || ! empty($telegramUrl);

                        if (! $hasCustomSocial) {
                            $facebookUrl = 'https://facebook.com';
                            $instagramUrl = 'https://instagram.com';
                            $twitterUrl = 'https://twitter.com';
                            $youtubeUrl = 'https://youtube.com';
                        }
                    @endphp

                    <div style="margin-top: 1.25rem;">
                        <span style="display: block; color: #cbd5e1; font-size: 0.75rem; font-weight: 500; margin-bottom: 0.65rem;">
                            تابعنا على وسائل التواصل الاجتماعي
                        </span>
                        
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <!-- WhatsApp -->
                            @if (! empty($cleanWhatsapp))
                                <a
                                    href="https://wa.me/{{ $cleanWhatsapp }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #25D366; color: #ffffff;"
                                    aria-label="WhatsApp"
                                >
                                    <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- Instagram -->
                            @if (! empty($instagramUrl))
                                <a
                                    href="{{ $instagramUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%); color: #ffffff;"
                                    aria-label="Instagram"
                                >
                                    <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- Facebook -->
                            @if (! empty($facebookUrl))
                                <a
                                    href="{{ $facebookUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #1877F2; color: #ffffff;"
                                    aria-label="Facebook"
                                >
                                    <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- X (Twitter) -->
                            @if (! empty($twitterUrl))
                                <a
                                    href="{{ $twitterUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #000000; color: #ffffff; border: 1px solid rgba(255,255,255,0.25);"
                                    aria-label="X (Twitter)"
                                >
                                    <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- YouTube -->
                            @if (! empty($youtubeUrl))
                                <a
                                    href="{{ $youtubeUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #FF0000; color: #ffffff;"
                                    aria-label="YouTube"
                                >
                                    <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- TikTok -->
                            @if (! empty($tiktokUrl))
                                <a
                                    href="{{ $tiktokUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #000000; color: #ffffff; border: 1px solid rgba(255,255,255,0.25);"
                                    aria-label="TikTok"
                                >
                                    <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.298-.002.595.042.88.13V9.4a6.33 6.33 0 0 0-1-.08A6.34 6.34 0 0 0 3 15.66a6.34 6.34 0 0 0 10.82 4.47 6.27 6.27 0 0 0 1.95-4.47V8.08a8.16 8.16 0 0 0 4.82 1.58V6.69z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- Snapchat -->
                            @if (! empty($snapchatUrl))
                                <a
                                    href="{{ $snapchatUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #FFFC00; color: #000000;"
                                    aria-label="Snapchat"
                                >
                                    <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M12.002 2c-3.57 0-6.173 2.766-6.173 6.134 0 1.25.398 2.215.398 2.215s-.455.158-.797.35c-.347.195-.694.524-.694.978 0 .49.336.85.748 1.05.795.385 1.582-.047 1.582-.047s-.18 1.348-.38 2.247c-.203.905-.62 1.488-1.572 1.796-.948.307-1.748.88-1.748 1.838 0 1.34 1.848 1.87 3.328 1.938.835.038 1.73-.207 2.457-.597.55-.295 1.25-.845 2.65-.845 1.4 0 2.1.55 2.65.845.727.39 1.622.635 2.457.597 1.48-.068 3.328-.598 3.328-1.938 0-.958-.8-1.53-1.748-1.838-.952-.308-1.37-.89-1.572-1.796-.2-.9-.38-2.247-.38-2.247s.787.432 1.582.047c.412-.2.748-.56.748-1.05 0-.454-.347-.783-.694-.978-.342-.192-.797-.35-.797-.35s.398-.965.398-2.215C18.175 4.766 15.572 2 12.002 2z"/>
                                    </svg>
                                </a>
                            @endif

                            <!-- Telegram -->
                            @if (! empty($telegramUrl))
                                <a
                                    href="{{ $telegramUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="highest-social-icon"
                                    style="background-color: #229ED9; color: #ffffff;"
                                    aria-label="Telegram"
                                >
                                    <svg style="width: 15px; height: 15px; fill: currentColor;" viewBox="0 0 24 24">
                                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295l.213-3.054 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.87 4.326-2.96-.924c-.643-.204-.657-.643.136-.953l11.57-4.458c.538-.196 1.006.128.832.94z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- -------------------------------------------------- -->
                <!-- Dynamic Link Columns from Theme Customization      -->
                <!-- -------------------------------------------------- -->
                @foreach ($activeLinkColumns as $colKey => $links)
                    @php
                        $colTitle = $defaultTitles[$colKey] ?? ('روابط ' . $colKey);

                        foreach ($links as $idx => $link) {
                            if (trim($link['url'] ?? '') === '#' || trim($link['url'] ?? '') === '') {
                                $colTitle = $link['title'];
                                unset($links[$idx]);
                                break;
                            }
                        }
                    @endphp

                    <div class="highest-footer-section highest-footer-link-section">
                        <h3 class="highest-footer-title">
                            {{ $colTitle }}
                            <span class="highest-footer-title-bar"></span>
                        </h3>

                        <ul class="highest-footer-list">
                            @foreach ($links as $link)
                                @php
                                    $linkUrl = trim($link['url'] ?? '#');
                                    $resolvedUrl = (str_starts_with($linkUrl, 'http://') || str_starts_with($linkUrl, 'https://') || str_starts_with($linkUrl, 'mailto:') || str_starts_with($linkUrl, 'tel:') || str_starts_with($linkUrl, '#')) 
                                        ? $linkUrl 
                                        : url($linkUrl);
                                @endphp
                                <li>
                                    <a href="{{ $resolvedUrl }}" class="highest-footer-link">
                                        <span style="color: #94a3b8; font-size: 0.85rem;">›</span>
                                        {{ $link['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach

                <!-- -------------------------------------------------- -->
                <!-- Section 5 (RTL: Leftmost): حمل تطبيق HIGHEST       -->
                <!-- -------------------------------------------------- -->
                <div class="highest-footer-section highest-footer-app-section" style="display: flex; flex-direction: column; align-items: flex-start;">
                    <h3 class="highest-footer-title">
                        حمل تطبيق HIGHEST
                        <span class="highest-footer-title-bar"></span>
                    </h3>

                    <p style="color: #cbd5e1; font-size: 0.78rem; margin-top: 0; margin-bottom: 0.85rem; line-height: 1.4;">
                        تسوق من أي مكان وفي أي وقت
                    </p>

                    <div class="highest-app-container">
                        <!-- App Store -->
                        <a
                            href="https://apple.com/app-store"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="highest-app-badge"
                        >
                            <svg style="width: 20px; height: 20px; fill: #ffffff; flex-shrink: 0;" viewBox="0 0 170 170">
                                <path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.59-7.71-11.66-14.01-6.1-9.46-10.9-19.9-14.41-31.33-3.51-11.43-5.27-22.37-5.27-32.82 0-14.24 3.73-25.79 11.2-34.64 7.47-8.86 16.73-13.38 27.78-13.57 4.8 0 10.12 1.25 15.98 3.76 5.86 2.5 9.77 3.82 11.72 3.94 1.86-.12 5.86-1.47 12-4.06 6.14-2.58 11.45-3.82 15.93-3.7 11.31.5 20.6 4.78 27.87 12.84-9.9 5.99-14.73 14.28-14.49 24.87.26 8.35 3.52 15.28 9.79 20.8 6.27 5.51 13.72 8.78 22.35 9.8-2.3 6.94-5.35 14.18-9.15 21.72zM119.22 33.64c0-7.39 2.66-14.18 7.99-20.37 5.33-6.19 11.78-10.3 19.35-12.33.12 1.02.19 2.05.19 3.09 0 7.27-2.73 14.14-8.19 20.61-5.46 6.47-11.96 10.43-19.5 11.88a20.7 20.7 0 0 1 .16-2.88z"/>
                            </svg>
                            <div style="display: flex; flex-direction: column; text-align: right; line-height: 1.1;">
                                <span style="font-size: 9px; color: #cbd5e1;">تحميل من</span>
                                <span style="font-size: 12px; font-weight: 700; color: #ffffff; font-family: sans-serif;">App Store</span>
                            </div>
                        </a>

                        <!-- Google Play -->
                        <a
                            href="https://play.google.com"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="highest-app-badge"
                        >
                            <svg style="width: 20px; height: 20px; flex-shrink: 0;" viewBox="0 0 24 24" fill="none">
                                <path d="M3.609 1.814L13.792 12 3.61 22.186a1.94 1.94 0 0 1-.22-.387V2.201c.067-.14.14-.272.22-.387z" fill="#00E676"/>
                                <path d="M15.207 13.414l2.122 2.122-12.72 7.24a2.023 2.023 0 0 1-.61.168l11.208-9.53z" fill="#FF3D00"/>
                                <path d="M15.207 10.586L3.999 1.056c.197.037.404.095.61.168l12.72 7.24-2.122 2.122z" fill="#FFC107"/>
                                <path d="M17.329 12l3.535 2.013c.895.51.895 1.343 0 1.854L17.33 17.88 15.207 13.414 17.329 12z" fill="#1976D2"/>
                            </svg>
                            <div style="display: flex; flex-direction: column; text-align: right; line-height: 1.1;">
                                <span style="font-size: 9px; color: #cbd5e1;">احصل عليه من</span>
                                <span style="font-size: 12px; font-weight: 700; color: #ffffff; font-family: sans-serif;">Google Play</span>
                            </div>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- 2. Footer Bottom Bar                                       -->
    <!-- ========================================================== -->
    <div style="width: 100% !important; background-color: #00123d !important; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 0.85rem; padding-bottom: 0.85rem; position: relative; z-index: 10;">
        <div class="highest-footer-bottom" style="width: 100%; max-width: 1400px; margin-left: auto; margin-right: auto; padding-left: 1.5rem; padding-right: 1.5rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem;">
            
            <!-- Right (in RTL): Copyright & Legal Links -->
            <div class="highest-footer-bottom-legal" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem; font-size: 0.75rem; color: #94a3b8;">
                <span>© {{ date('Y') }} HIGHEST جميع الحقوق محفوظة</span>
                <span style="opacity: 0.3;">|</span>
                <a href="{{ route('shop.cms.page', 'privacy-policy') }}" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F5B800'" onmouseout="this.style.color='#94a3b8'">
                    سياسة الخصوصية
                </a>
                <span style="opacity: 0.3;">|</span>
                <a href="{{ route('shop.cms.page', 'terms-conditions') }}" style="color: #94a3b8; text-decoration: none;" onmouseover="this.style.color='#F5B800'" onmouseout="this.style.color='#94a3b8'">
                    شروط الاستخدام والأحكام
                </a>
            </div>

            @php
                $footerPaymentConfig = $channel?->payment_methods ?? [];
                $showPaymentTitle = isset($footerPaymentConfig['show_title']) ? (bool) $footerPaymentConfig['show_title'] : true;
                $savedPaymentMethods = $footerPaymentConfig['methods'] ?? null;
                $deletedPaymentKeys = $footerPaymentConfig['deleted_keys'] ?? [];
                $customLogos = $footerPaymentConfig['custom_logos'] ?? [];

                $isMethodActive = function($key, $default = false) use ($savedPaymentMethods, $deletedPaymentKeys) {
                    if (in_array($key, $deletedPaymentKeys)) {
                        return false;
                    }
                    if (is_null($savedPaymentMethods)) {
                        return $default;
                    }
                    return ! empty($savedPaymentMethods[$key]['enabled']);
                };
            @endphp

            <!-- Left (in RTL): Payment Methods (Only Logos or with Title) -->
            <div class="highest-footer-bottom-payments" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; padding-inline-end: 60px;">
                @if($showPaymentTitle)
                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">وسائل الدفع المقبولة</span>
                    <span style="opacity: 0.3; margin-left: 0.25rem;">|</span>
                @endif

                <div class="highest-footer-bottom-badges" style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                    @if($isMethodActive('bank_transfer', true))
                        <!-- تحويل بنكي -->
                        <span class="highest-pay-badge" style="gap: 0.25rem;" title="تحويل بنكي">
                            <svg style="width: 14px; height: 14px; color: #001A54; fill: currentColor;" viewBox="0 0 24 24">
                                <path d="M2 19h20v3H2v-3zm2-8h3v7H4v-7zm6 0h3v7h-3v-7zm6 0h3v7h-3v-7zm-4-9l10 5H2l10-5z"/>
                            </svg>
                            <span style="color: #001A54; font-weight: 700; font-size: 9px;">تحويل بنكي</span>
                        </span>
                    @endif

                    @if($isMethodActive('apple_pay', true))
                        <!-- Apple Pay -->
                        <span class="highest-pay-badge" style="gap: 0.2rem;" title="Apple Pay">
                            <svg style="height: 12px; width: auto; fill: #000000;" viewBox="0 0 170 170"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.7-3.04-7.59-7.71-11.66-14.01-6.1-9.46-10.9-19.9-14.41-31.33-3.51-11.43-5.27-22.37-5.27-32.82 0-14.24 3.73-25.79 11.2-34.64 7.47-8.86 16.73-13.38 27.78-13.57 4.8 0 10.12 1.25 15.98 3.76 5.86 2.5 9.77 3.82 11.72 3.94 1.86-.12 5.86-1.47 12-4.06 6.14-2.58 11.45-3.82 15.93-3.7 11.31.5 20.6 4.78 27.87 12.84-9.9 5.99-14.73 14.28-14.49 24.87.26 8.35 3.52 15.28 9.79 20.8 6.27 5.51 13.72 8.78 22.35 9.8-2.3 6.94-5.35 14.18-9.15 21.72zM119.22 33.64c0-7.39 2.66-14.18 7.99-20.37 5.33-6.19 11.78-10.3 19.35-12.33.12 1.02.19 2.05.19 3.09 0 7.27-2.73 14.14-8.19 20.61-5.46 6.47-11.96 10.43-19.5 11.88a20.7 20.7 0 0 1 .16-2.88z"/></svg>
                            <span style="font-family: sans-serif; font-weight: 700; color: #000000; font-size: 10px;">Pay</span>
                        </span>
                    @endif

                    @if($isMethodActive('mada', true))
                        <!-- مدى mada -->
                        <span class="highest-pay-badge" style="gap: 0.15rem;" title="مدى mada">
                            <span style="font-weight: 700; font-size: 9px; color: #00A551; font-family: sans-serif;">mada</span>
                            <span style="font-weight: 700; font-size: 9px; color: #005C8A;">مدى</span>
                        </span>
                    @endif

                    @if($isMethodActive('mastercard', true))
                        <!-- Mastercard -->
                        <span class="highest-pay-badge" title="Mastercard">
                            <svg style="height: 14px; width: 20px;" viewBox="0 0 32 20" fill="none">
                                <circle cx="10" cy="10" r="10" fill="#EB001B"/>
                                <circle cx="22" cy="10" r="10" fill="#F79E1B" fill-opacity="0.85"/>
                            </svg>
                        </span>
                    @endif

                    @if($isMethodActive('visa', true))
                        <!-- VISA -->
                        <span class="highest-pay-badge" title="VISA">
                            <span style="font-weight: 700; font-family: sans-serif; color: #1A1F71; font-size: 10px; letter-spacing: 0.05em;">VISA</span>
                        </span>
                    @endif

                    @if($isMethodActive('kuraimi'))
                        <!-- الكريمي جوال -->
                        <span class="highest-pay-badge" style="gap: 0.2rem; background: #fff;" title="الكريمي Kuraimi">
                            <span style="font-weight: 800; font-size: 9px; color: #004A8F;">الكريمي</span>
                        </span>
                    @endif

                    @if($isMethodActive('onecash'))
                        <!-- OneCash ون كاش -->
                        <span class="highest-pay-badge" style="gap: 0.2rem;" title="ون كاش OneCash">
                            <span style="font-weight: 800; font-size: 9px; color: #E30613;">One</span><span style="font-weight: 800; font-size: 9px; color: #002D62;">Cash</span>
                        </span>
                    @endif

                    @if($isMethodActive('jawali'))
                        <!-- جوالي Jawali -->
                        <span class="highest-pay-badge" style="gap: 0.2rem;" title="جوالي Jawali">
                            <span style="font-weight: 800; font-size: 9px; color: #008852;">جوالي</span>
                        </span>
                    @endif

                    @if($isMethodActive('paypal'))
                        <!-- PayPal -->
                        <span class="highest-pay-badge" style="gap: 0.15rem;" title="PayPal">
                            <span style="font-weight: 800; font-family: sans-serif; font-size: 10px; color: #003087;">Pay</span><span style="font-weight: 800; font-family: sans-serif; font-size: 10px; color: #0079C1;">Pal</span>
                        </span>
                    @endif

                    @if($isMethodActive('stc_pay'))
                        <!-- STC Pay -->
                        <span class="highest-pay-badge" style="gap: 0.2rem;" title="STC Pay">
                            <span style="font-weight: 800; font-family: sans-serif; font-size: 9px; color: #4F008C;">stc</span>
                            <span style="font-weight: 700; font-size: 9px; color: #FF375F;">pay</span>
                        </span>
                    @endif

                    @if($isMethodActive('cash_on_delivery'))
                        <!-- الدفع عند الاستلام -->
                        <span class="highest-pay-badge" style="gap: 0.25rem;" title="الدفع عند الاستلام">
                            <svg style="width: 13px; height: 13px; color: #059669; fill: currentColor;" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.39-2.1 1.39-1.6 0-2.23-.72-2.32-1.64H8.04c.1 1.7 1.36 2.66 2.86 2.97V19h2.34v-1.67c1.52-.29 2.72-1.16 2.73-2.77-.01-2.2-1.9-2.96-3.66-3.42z"/>
                            </svg>
                            <span style="color: #059669; font-weight: 700; font-size: 9px;">عند الاستلام</span>
                        </span>
                    @endif

                    @if($isMethodActive('tabby'))
                        <!-- Tabby -->
                        <span class="highest-pay-badge" title="تابي Tabby">
                            <span style="font-weight: 800; font-size: 9px; color: #29e79e; background: #000; padding: 1px 4px; border-radius: 2px;">tabby</span>
                        </span>
                    @endif

                    @if($isMethodActive('tamara'))
                        <!-- Tamara -->
                        <span class="highest-pay-badge" title="تمارا Tamara">
                            <span style="font-weight: 800; font-size: 9px; color: #FF6600;">tamara</span>
                        </span>
                    @endif

                    <!-- Custom Uploaded Payment Logos -->
                    @foreach($customLogos as $customItem)
                        @if(! empty($customItem['image']))
                            <span class="highest-pay-badge" title="{{ $customItem['title'] ?? '' }}">
                                <img src="{{ Storage::url($customItem['image']) }}" alt="{{ $customItem['title'] ?? 'Payment Logo' }}" style="height: 14px; width: auto; max-width: 45px; object-fit: contain;">
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</footer>

{!! view_render_event('bagisto.shop.layout.footer.after') !!}
