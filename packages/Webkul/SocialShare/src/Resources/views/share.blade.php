@if (core()->getConfigData('catalog.products.social_share.enabled'))
    @php
        $message    = core()->getConfigData('catalog.products.social_share.share_message');
        $productUrl = route('shop.product_or_category.index', [$product->url_key]);
        $productName = $product->name;
    @endphp

    {!! view_render_event('bagisto.shop.products.view.share.before', ['product' => $product]) !!}

    {{--
        NOTE: The modal is injected via @push('scripts') — OUTSIDE the Vue #app container —
        to avoid Vue compiler-23 errors caused by SVG elements (circle, line, path, rect)
        with explicit closing tags inside a Vue template context.
    --}}

    {!! view_render_event('bagisto.shop.products.view.share.after', ['product' => $product]) !!}

    @push('styles')
        <style>
            /* ===== Modal Overlay ===== */
            .higst-share-modal {
                display: none;
                position: fixed;
                inset: 0;
                z-index: 99999;
                align-items: flex-end;
                justify-content: center;
            }

            .higst-share-modal.is-open {
                display: flex;
            }

            @media (min-width: 640px) {
                .higst-share-modal {
                    align-items: center;
                    justify-content: center;
                }
            }

            /* ===== Backdrop ===== */
            .higst-share-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                animation: higst-fade-in 0.25s ease forwards;
            }

            @keyframes higst-fade-in {
                from { opacity: 0; }
                to   { opacity: 1; }
            }

            /* ===== Panel ===== */
            .higst-share-panel {
                position: relative;
                z-index: 1;
                background: #ffffff;
                border-radius: 24px 24px 0 0;
                padding: 24px 20px 32px;
                width: 100%;
                max-width: 100%;
                box-shadow: 0 -8px 40px rgba(0,0,0,0.15);
                animation: higst-slide-up 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            }

            @media (min-width: 640px) {
                .higst-share-panel {
                    border-radius: 20px;
                    max-width: 420px;
                    width: 100%;
                    padding: 28px 24px 28px;
                    animation: higst-scale-in 0.28s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
                }
            }

            @keyframes higst-slide-up {
                from { transform: translateY(100%); opacity: 0; }
                to   { transform: translateY(0);    opacity: 1; }
            }

            @keyframes higst-scale-in {
                from { transform: scale(0.88); opacity: 0; }
                to   { transform: scale(1);    opacity: 1; }
            }

            .higst-share-panel::before {
                content: '';
                display: block;
                width: 40px;
                height: 4px;
                background: #e5e7eb;
                border-radius: 2px;
                margin: 0 auto 20px;
            }

            @media (min-width: 640px) {
                .higst-share-panel::before { display: none; }
            }

            /* ===== Header ===== */
            .higst-share-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .higst-share-header-left {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 15px;
                font-weight: 600;
                color: #111827;
            }

            .higst-share-header-icon {
                width: 32px;
                height: 32px;
                background: #f3f4f6;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #374151;
            }

            .higst-share-close {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: #f3f4f6;
                border: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #6b7280;
                transition: all 0.18s ease;
                flex-shrink: 0;
                font-family: inherit;
            }

            .higst-share-close:hover {
                background: #e5e7eb;
                color: #111827;
                transform: rotate(90deg);
            }

            /* ===== Product Preview ===== */
            .higst-share-product-preview {
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 12px 14px;
                margin-bottom: 14px;
            }

            .higst-share-product-name {
                font-size: 13px;
                font-weight: 600;
                color: #111827;
                margin-bottom: 3px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .higst-share-product-url {
                font-size: 11px;
                color: #9ca3af;
            }

            /* ===== Copy Row ===== */
            .higst-share-copy-row {
                display: flex;
                align-items: center;
                gap: 8px;
                background: #f9fafb;
                border: 1.5px solid #e5e7eb;
                border-radius: 10px;
                padding: 8px 10px;
                margin-bottom: 18px;
                transition: border-color 0.2s ease;
            }

            .higst-share-url-display {
                flex: 1;
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 11.5px;
                color: #6b7280;
                overflow: hidden;
                min-width: 0;
            }

            .higst-share-url-display span {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .higst-copy-btn {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 6px 12px;
                background: #111827;
                color: #ffffff;
                border: none;
                border-radius: 7px;
                cursor: pointer;
                font-size: 11.5px;
                font-weight: 500;
                white-space: nowrap;
                transition: all 0.18s ease;
                flex-shrink: 0;
                font-family: inherit;
            }

            .higst-copy-btn:hover { background: #1f2937; transform: scale(1.03); }
            .higst-copy-btn.copied { background: #059669; }

            /* ===== Divider ===== */
            .higst-share-divider {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 18px;
                color: #9ca3af;
                font-size: 12px;
            }

            .higst-share-divider::before,
            .higst-share-divider::after {
                content: '';
                flex: 1;
                height: 1px;
                background: #e5e7eb;
            }

            /* ===== Social Grid ===== */
            .higst-social-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 14px 10px;
            }

            /* ===== Social Item ===== */
            .higst-social-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 7px;
                text-decoration: none;
                cursor: pointer;
            }

            .higst-social-item span {
                font-size: 11px;
                color: #4b5563;
                font-weight: 500;
                text-align: center;
                line-height: 1.3;
            }

            .higst-social-icon-wrap {
                width: 52px;
                height: 52px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease;
                position: relative;
                overflow: hidden;
            }

            .higst-social-item:hover .higst-social-icon-wrap {
                transform: scale(1.1) translateY(-3px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            }

            .higst-social-icon-wrap svg {
                width: 28px !important;
                height: 28px !important;
            }

            /* Staggered entry */
            .higst-social-item:nth-child(1) { animation: higst-item-pop 0.3s 0.05s both; }
            .higst-social-item:nth-child(2) { animation: higst-item-pop 0.3s 0.10s both; }
            .higst-social-item:nth-child(3) { animation: higst-item-pop 0.3s 0.15s both; }
            .higst-social-item:nth-child(4) { animation: higst-item-pop 0.3s 0.20s both; }
            .higst-social-item:nth-child(5) { animation: higst-item-pop 0.3s 0.25s both; }
            .higst-social-item:nth-child(6) { animation: higst-item-pop 0.3s 0.30s both; }

            @keyframes higst-item-pop {
                from { opacity: 0; transform: scale(0.7) translateY(10px); }
                to   { opacity: 1; transform: scale(1) translateY(0); }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            /* ============================================================
             * HIGST Share Modal — injected OUTSIDE #app to avoid Vue compiler-23
             * All SVG closing tags (circle, line, path, rect) are safe here
             * ============================================================ */
            (function () {
                var modalHTML = '<div id="higst-share-modal" class="higst-share-modal" role="dialog" aria-modal="true" aria-label="Share this product">'
                    + '<div class="higst-share-backdrop" onclick="higstCloseShareModal()"></div>'
                    + '<div class="higst-share-panel">'

                    /* Header */
                    + '<div class="higst-share-header">'
                    +   '<div class="higst-share-header-left">'
                    +     '<div class="higst-share-header-icon">'
                    +       '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                    +         '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>'
                    +         '<line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>'
                    +       '</svg>'
                    +     '</div>'
                    +     '<span>مشاركة المنتج</span>'
                    +   '</div>'
                    +   '<button class="higst-share-close" onclick="higstCloseShareModal()" aria-label="Close" type="button">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">'
                    +       '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>'
                    +     '</svg>'
                    +   '</button>'
                    + '</div>'

                    /* Product Preview */
                    + '<div class="higst-share-product-preview">'
                    +   '<div class="higst-share-product-name">{{ addslashes(Str::limit($productName, 60)) }}</div>'
                    +   '<div class="higst-share-product-url">{{ parse_url($productUrl, PHP_URL_HOST) }}</div>'
                    + '</div>'

                    /* Copy Link */
                    + '<div class="higst-share-copy-row">'
                    +   '<div class="higst-share-url-display">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                    +       '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>'
                    +       '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
                    +     '</svg>'
                    +     '<span>{{ addslashes(Str::limit($productUrl, 38)) }}</span>'
                    +   '</div>'
                    +   '<button class="higst-copy-btn" id="higst-copy-btn" onclick="higstCopyLink(\'{{ addslashes($productUrl) }}\')" type="button">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
                    +       '<rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>'
                    +       '<path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>'
                    +     '</svg>'
                    +     '<span>نسخ الرابط</span>'
                    +   '</button>'
                    + '</div>'

                    /* Divider */
                    + '<div class="higst-share-divider"><span>أو شارك عبر</span></div>'

                    /* Social Grid */
                    + '<div class="higst-social-grid">'
                    @if (core()->getConfigData('catalog.products.social_share.whatsapp'))
                    @php $waText = ['text' => $message . ' ' . $productUrl]; $waURL = 'whatsapp://send?' . http_build_query($waText); @endphp
                    + '<a href="{{ addslashes($waURL) }}" class="higst-social-item" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">'
                    +   '<div class="higst-social-icon-wrap" style="background:linear-gradient(135deg,#57D163,#23B33A);">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="20" fill="url(#wa)"/><path d="M7 33l1.836-6.677A12.887 12.887 0 0 1 7.109 19.882C7.112 12.78 12.919 7 20.054 7a12.935 12.935 0 0 1 9.158 3.779A12.818 12.818 0 0 1 33 19.894C32.997 26.997 27.19 32.777 20.054 32.777a13.04 13.04 0 0 1-6.191-1.569L7 33zm7.181-4.124a10.83 10.83 0 0 0 5.873 1.727c5.93 0 10.761-4.803 10.764-10.708.002-5.917-4.806-10.723-10.756-10.725-5.934 0-10.762 4.803-10.764 10.707a10.73 10.73 0 0 0 1.898 6.103l-1.088 3.952 4.073-1.059zm12.394-6.044c-.08-.133-.295-.213-.62-.373-.325-.16-1.916-.938-2.213-1.045-.297-.107-.512-.16-.728.16-.216.32-.836.768-1.024.982-.188.214-.377.24-.7.08-.324-.16-1.367-.5-2.603-1.597-.962-.853-1.612-1.907-1.8-2.23-.188-.32-.02-.494.141-.653.146-.144.324-.374.486-.562.163-.188.217-.32.325-.534.108-.214.054-.4-.027-.56-.08-.16-.727-1.746-1-2.39-.263-.628-.53-.543-.728-.552l-.62-.011c-.215 0-.566.08-.862.4-.297.32-1.133 1.098-1.133 2.683 0 1.585 1.16 3.116 1.321 3.33.162.214 2.28 3.467 5.525 4.861.771.332 1.374.53 1.843.678.775.245 1.48.21 2.037.128.621-.092 1.913-.779 2.183-1.531.27-.752.27-1.397.188-1.53z" fill="#fff"/><defs><linearGradient id="wa" x1="19.593" y1="2.4" x2="19.796" y2="36.583" gradientUnits="userSpaceOnUse"><stop stop-color="#57D163"/><stop offset="1" stop-color="#23B33A"/></linearGradient></defs></svg>'
                    +   '</div>'
                    +   '<span>واتساب</span>'
                    + '</a>'
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.facebook'))
                    @php $fbURL = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($productUrl); @endphp
                    + '<a href="{{ addslashes($fbURL) }}" class="higst-social-item higst-popup-share" data-url="{{ addslashes($fbURL) }}" rel="noopener noreferrer" aria-label="Facebook">'
                    +   '<div class="higst-social-icon-wrap" style="background:#1877F2;">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="20" fill="#1877F2"/><path d="M21.499 29V20.418h2.939l.444-3.416H21.499v-2.168c-.033-.438.09-.875.346-1.233a1.6 1.6 0 0 1 1.349-.458L25 13.175V10.137A15.27 15.27 0 0 0 22.371 10a4.08 4.08 0 0 0-3.213 1.179 4.602 4.602 0 0 0-1.203 3.319v2.504H15v3.416h2.954V28.99L21.499 29z" fill="#fff"/></svg>'
                    +   '</div>'
                    +   '<span>فيسبوك</span>'
                    + '</a>'
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.twitter'))
                    @php $twURL = 'https://twitter.com/intent/tweet?' . http_build_query(['url' => $productUrl, 'text' => $message]); @endphp
                    + '<a href="{{ addslashes($twURL) }}" class="higst-social-item higst-popup-share" data-url="{{ addslashes($twURL) }}" rel="noopener noreferrer" aria-label="X">'
                    +   '<div class="higst-social-icon-wrap" style="background:#1A1A1A;">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="20" fill="#1A1A1A"/><path d="M10.048 11l7.722 9.928L10 29h1.748l6.804-7.067L24.049 29H30l-8.157-10.486L28.077 11h-1.75l-6.264 6.508L16 11h-5.952zm2.572 1.239h2.735l12.073 16.522h-2.735L12.62 12.239z" fill="#fff" stroke="#fff" stroke-miterlimit="10"/></svg>'
                    +   '</div>'
                    +   '<span>X (تويتر)</span>'
                    + '</a>'
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.linkedin'))
                    @php $liDetails = ['mini' => 'true', 'url' => $productUrl, 'title' => $productName, 'summary' => $message]; $liURL = 'https://www.linkedin.com/shareArticle?' . http_build_query($liDetails); @endphp
                    + '<a href="{{ addslashes($liURL) }}" class="higst-social-item higst-popup-share" data-url="{{ addslashes($liURL) }}" rel="noopener noreferrer" aria-label="LinkedIn">'
                    +   '<div class="higst-social-icon-wrap" style="background:#0A66C2;">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="20" fill="#0A66C2"/><path d="M13.508 16.8H10V30h3.508V16.8zM11.754 15.3a2.1 2.1 0 1 0 0-4.2 2.1 2.1 0 0 0 0 4.2zM30 23.07c0-3.464-1.867-5.07-4.356-5.07-2.003 0-2.9 1.1-3.4 1.872V16.8H18.74V30h3.504v-7.13c0-1.455.693-2.87 2.25-2.87 1.558 0 2.002 1.414 2.002 2.87V30H30v-6.93z" fill="#fff"/></svg>'
                    +   '</div>'
                    +   '<span>لينكدإن</span>'
                    + '</a>'
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.pinterest'))
                    @php $productBaseImage = product_image()->getProductBaseImage($product); $pinDetails = ['url' => $productUrl, 'media' => $productBaseImage['medium_image_url'] ?: asset('vendor/webkul/ui/assets/images/product/meduim-product-placeholder.png'), 'description' => $message]; $pinURL = 'https://pinterest.com/pin/create/button/?' . http_build_query($pinDetails); @endphp
                    + '<a href="{{ addslashes($pinURL) }}" class="higst-social-item higst-popup-share" data-url="{{ addslashes($pinURL) }}" rel="noopener noreferrer" aria-label="Pinterest">'
                    +   '<div class="higst-social-icon-wrap" style="background:#E60023;">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="20" fill="#E60023"/><path d="M20 8C13.373 8 8 13.373 8 20c0 5.085 3.163 9.426 7.627 11.174-.105-.95-.2-2.409.042-3.447.218-.938 1.462-6.194 1.462-6.194s-.373-.747-.373-1.852c0-1.737.007-2.409.007-3.146 0-.939.45-1.64 1.011-2.131.558-.49 1.216-.717 1.765-.717.838 0 1.505.353 1.879.94.374.586.467 1.408.467 2.187 0 .894-.2 1.894-.45 2.882-.248.982-.55 1.9-.55 2.7 0 1.18.937 2.26 2.323 2.26 2.8 0 4.681-2.956 4.681-7.237 0-3.783-2.72-6.43-6.602-6.43-4.497 0-7.137 3.373-7.137 6.863 0 1.36.522 2.814 1.175 3.612.13.157.148.295.11.457-.12.494-.385 1.553-.437 1.77-.069.286-.232.347-.535.208-1.986-.926-3.227-3.836-3.227-6.174 0-5.019 3.647-9.634 10.516-9.634 5.52 0 9.812 3.935 9.812 9.191 0 5.484-3.456 9.895-8.252 9.895-1.612 0-3.129-.839-3.649-1.826l-.992 3.703c-.36 1.382-1.33 3.112-1.98 4.163.493.152 1.014.234 1.552.234C26.627 32 32 26.627 32 20S26.627 8 20 8z" fill="#fff"/></svg>'
                    +   '</div>'
                    +   '<span>بينتريست</span>'
                    + '</a>'
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.email'))
                    @php $emailURL = 'mailto:?subject=' . rawurlencode($productName) . '&body=' . rawurlencode($message . ' ' . $productUrl); @endphp
                    + '<a href="{{ addslashes($emailURL) }}" class="higst-social-item" aria-label="Email">'
                    +   '<div class="higst-social-icon-wrap" style="background:#1a1a2e;">'
                    +     '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 40 40" fill="none"><rect width="40" height="40" rx="20" fill="#1a1a2e"/><path d="M12 15.7l5.535 4.359M12 15.7V14.98C12 13.887 12.895 13 14 13h6 6c1.105 0 2 .887 2 1.98v.72M12 15.7V25c0 .593.263 1.131.681 1.5M17.535 20.059L12.68 26.5M17.535 20.059c.723.57 1.594.856 2.465.858.871-.002 1.742-.288 2.465-.858M12.68 26.5C13.033 26.811 13.495 27 14 27h6 6c.505 0 .967-.189 1.319-.5M22.465 20.059L28 15.7M22.465 20.059L27.319 26.5M28 15.7V25c0 .593-.263 1.131-.681 1.5" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
                    +   '</div>'
                    +   '<span>بريد إلكتروني</span>'
                    + '</a>'
                    @endif

                    + '</div>'   /* end social-grid */
                    + '</div>'   /* end panel */
                    + '</div>';  /* end modal */

                document.addEventListener('DOMContentLoaded', function () {
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                });
            })();

            /* ===== Modal Functions ===== */
            function higstOpenShareModal() {
                var modal = document.getElementById('higst-share-modal');
                if (!modal) return;
                modal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                document.querySelectorAll('.higst-social-item').forEach(function (el) {
                    el.style.animation = 'none';
                    el.offsetHeight;
                    el.style.animation = '';
                });
            }

            function higstCloseShareModal() {
                var modal = document.getElementById('higst-share-modal');
                if (!modal) return;
                modal.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            function higstCopyLink(url) {
                var btn = document.getElementById('higst-copy-btn');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        higstShowCopied(btn);
                    }).catch(function () { higstFallbackCopy(url, btn); });
                } else {
                    higstFallbackCopy(url, btn);
                }
            }

            function higstFallbackCopy(url, btn) {
                var ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try { document.execCommand('copy'); higstShowCopied(btn); } catch (e) {}
                document.body.removeChild(ta);
            }

            function higstShowCopied(btn) {
                if (!btn) return;
                btn.classList.add('copied');
                var span = btn.querySelector('span');
                var orig = span.textContent;
                span.textContent = 'تم النسخ ✓';
                setTimeout(function () {
                    btn.classList.remove('copied');
                    span.textContent = orig;
                }, 2200);
            }

            /* Popup share handler */
            document.addEventListener('click', function (e) {
                var item = e.target.closest('.higst-popup-share');
                if (item) {
                    e.preventDefault();
                    window.open(item.getAttribute('data-url') || item.href, '_blank', 'resizable=yes,top=200,left=300,width=580,height=500');
                }
            });

            /* Escape key to close */
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') higstCloseShareModal();
            });
        </script>
    @endpush

@endif
