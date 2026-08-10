@if (core()->getConfigData('catalog.products.social_share.enabled'))
    @php
        $message = core()->getConfigData('catalog.products.social_share.share_message');
        $productUrl = route('shop.product_or_category.index', [$product->url_key]);
        $productName = $product->name;
    @endphp

    {{-- Collect enabled socials --}}
    @php
        $enabledSocials = [];
        foreach(['email', 'whatsapp', 'facebook', 'linkedin', 'twitter', 'pinterest'] as $social) {
            if (core()->getConfigData('catalog.products.social_share.' . $social)) {
                $enabledSocials[] = $social;
            }
        }
    @endphp

    {!! view_render_event('bagisto.shop.products.view.share.before', ['product' => $product]) !!}

    {{-- Single Share Button --}}
    <div class="higst-share-wrapper">
        <button
            id="higst-share-btn"
            class="higst-share-trigger"
            onclick="higstToggleShareModal(event)"
            aria-label="@lang('admin::app.configuration.index.catalog.products.social-share.share')"
            aria-haspopup="true"
            aria-expanded="false"
            type="button"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="18" cy="5" r="3"></circle>
                <circle cx="6" cy="12" r="3"></circle>
                <circle cx="18" cy="19" r="3"></circle>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
            </svg>
            <span class="higst-share-label">@lang('admin::app.configuration.index.catalog.products.social-share.share')</span>
        </button>

        {{-- Share Modal Popup --}}
        <div
            id="higst-share-modal"
            class="higst-share-modal"
            role="dialog"
            aria-modal="true"
            aria-label="Share this product"
        >
            {{-- Backdrop --}}
            <div class="higst-share-backdrop" onclick="higstCloseShareModal()"></div>

            {{-- Panel --}}
            <div class="higst-share-panel">
                {{-- Header --}}
                <div class="higst-share-header">
                    <div class="higst-share-header-left">
                        <div class="higst-share-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="18" cy="5" r="3"></circle>
                                <circle cx="6" cy="12" r="3"></circle>
                                <circle cx="18" cy="19" r="3"></circle>
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                            </svg>
                        </div>
                        <span>مشاركة المنتج</span>
                    </div>
                    <button class="higst-share-close" onclick="higstCloseShareModal()" aria-label="Close" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                {{-- Product Preview --}}
                <div class="higst-share-product-preview">
                    <div class="higst-share-product-info">
                        <div class="higst-share-product-name">{{ $productName }}</div>
                        <div class="higst-share-product-url">{{ parse_url($productUrl, PHP_URL_HOST) }}</div>
                    </div>
                </div>

                {{-- Copy Link Row --}}
                <div class="higst-share-copy-row">
                    <div class="higst-share-url-display">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path>
                        </svg>
                        <span id="higst-url-text">{{ Str::limit($productUrl, 38) }}</span>
                    </div>
                    <button class="higst-copy-btn" id="higst-copy-btn" onclick="higstCopyLink('{{ $productUrl }}')" type="button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span>نسخ الرابط</span>
                    </button>
                </div>

                {{-- Divider --}}
                <div class="higst-share-divider">
                    <span>أو شارك عبر</span>
                </div>

                {{-- Social Icons Grid --}}
                <div class="higst-social-grid">
                    @if (core()->getConfigData('catalog.products.social_share.whatsapp'))
                        @php
                            $waText = ['text' => $message . ' ' . $productUrl];
                            $waURL = 'whatsapp://send?' . http_build_query($waText);
                        @endphp
                        <a
                            href="{{ $waURL }}"
                            class="higst-social-item"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="WhatsApp"
                            data-platform="WhatsApp"
                        >
                            <div class="higst-social-icon-wrap" style="background: linear-gradient(135deg, #57D163, #23B33A);">
                                @include('social_share::icons.whatsapp')
                            </div>
                            <span>واتساب</span>
                        </a>
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.facebook'))
                        @php
                            $fbURL = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($productUrl);
                        @endphp
                        <a
                            href="{{ $fbURL }}"
                            class="higst-social-item higst-popup-share"
                            data-url="{{ $fbURL }}"
                            rel="noopener noreferrer"
                            aria-label="Facebook"
                            data-platform="Facebook"
                        >
                            <div class="higst-social-icon-wrap" style="background: #1877F2;">
                                @include('social_share::icons.facebook')
                            </div>
                            <span>فيسبوك</span>
                        </a>
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.twitter'))
                        @php
                            $twURL = 'https://twitter.com/intent/tweet?' . http_build_query(['url' => $productUrl, 'text' => $message]);
                        @endphp
                        <a
                            href="{{ $twURL }}"
                            class="higst-social-item higst-popup-share"
                            data-url="{{ $twURL }}"
                            rel="noopener noreferrer"
                            aria-label="X (Twitter)"
                            data-platform="X"
                        >
                            <div class="higst-social-icon-wrap" style="background: #1A1A1A;">
                                @include('social_share::icons.twitter')
                            </div>
                            <span>X (تويتر)</span>
                        </a>
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.linkedin'))
                        @php
                            $liDetails = ['mini' => 'true', 'url' => $productUrl, 'title' => $productName, 'summary' => $message];
                            $liURL = 'https://www.linkedin.com/shareArticle?' . http_build_query($liDetails);
                        @endphp
                        <a
                            href="{{ $liURL }}"
                            class="higst-social-item higst-popup-share"
                            data-url="{{ $liURL }}"
                            rel="noopener noreferrer"
                            aria-label="LinkedIn"
                            data-platform="LinkedIn"
                        >
                            <div class="higst-social-icon-wrap" style="background: #0A66C2;">
                                @include('social_share::icons.linkedin')
                            </div>
                            <span>لينكدإن</span>
                        </a>
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.pinterest'))
                        @php
                            $productBaseImage = product_image()->getProductBaseImage($product);
                            $pinDetails = [
                                'url'         => $productUrl,
                                'media'       => $productBaseImage['medium_image_url'] ?: asset('vendor/webkul/ui/assets/images/product/meduim-product-placeholder.png'),
                                'description' => $message,
                            ];
                            $pinURL = 'https://pinterest.com/pin/create/button/?' . http_build_query($pinDetails);
                        @endphp
                        <a
                            href="{{ $pinURL }}"
                            class="higst-social-item higst-popup-share"
                            data-url="{{ $pinURL }}"
                            rel="noopener noreferrer"
                            aria-label="Pinterest"
                            data-platform="Pinterest"
                        >
                            <div class="higst-social-icon-wrap" style="background: #E60023;">
                                @include('social_share::icons.pinterest')
                            </div>
                            <span>بينتريست</span>
                        </a>
                    @endif

                    @if (core()->getConfigData('catalog.products.social_share.email'))
                        @php
                            $emailURL = 'mailto:?subject=' . rawurlencode($productName) . '&body=' . rawurlencode($message . ' ' . $productUrl);
                        @endphp
                        <a
                            href="{{ $emailURL }}"
                            class="higst-social-item"
                            aria-label="Email"
                            data-platform="Email"
                        >
                            <div class="higst-social-icon-wrap" style="background: #1a1a2e;">
                                @include('social_share::icons.email')
                            </div>
                            <span>بريد إلكتروني</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {!! view_render_event('bagisto.shop.products.view.share.after', ['product' => $product]) !!}

    @push('styles')
        <style>
            /* ===== Share Wrapper ===== */
            .higst-share-wrapper {
                position: relative;
                display: inline-flex;
                align-items: center;
            }

            /* ===== Trigger Button ===== */
            .higst-share-trigger {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 9px 18px 9px 14px;
                background: #ffffff;
                border: 1.5px solid #e5e7eb;
                border-radius: 50px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #374151;
                transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 1px 4px rgba(0,0,0,0.06);
                white-space: nowrap;
                font-family: inherit;
            }

            .higst-share-trigger:hover {
                background: #f9fafb;
                border-color: #9ca3af;
                box-shadow: 0 4px 14px rgba(0,0,0,0.10);
                transform: translateY(-1px);
                color: #111827;
            }

            .higst-share-trigger:active {
                transform: translateY(0);
                box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            }

            .higst-share-trigger svg {
                flex-shrink: 0;
                transition: transform 0.22s ease;
            }

            .higst-share-trigger:hover svg {
                transform: rotate(15deg);
            }

            .higst-share-label {
                font-size: 13.5px;
            }

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

            /* Desktop: center the panel */
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

            /* Handle bar for mobile */
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
                .higst-share-panel::before {
                    display: none;
                }
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

            .higst-share-copy-row:hover {
                border-color: #d1d5db;
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

            .higst-share-url-display svg {
                flex-shrink: 0;
                color: #9ca3af;
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

            .higst-copy-btn:hover {
                background: #1f2937;
                transform: scale(1.03);
            }

            .higst-copy-btn.copied {
                background: #059669;
            }

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
                gap: 12px 8px;
            }

            @media (min-width: 400px) {
                .higst-social-grid {
                    grid-template-columns: repeat(4, 1fr);
                    gap: 14px 10px;
                }
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

            .higst-social-icon-wrap::after {
                content: '';
                position: absolute;
                inset: 0;
                background: rgba(255,255,255,0);
                transition: background 0.15s ease;
                border-radius: inherit;
            }

            .higst-social-item:hover .higst-social-icon-wrap {
                transform: scale(1.1) translateY(-3px);
                box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            }

            .higst-social-item:hover .higst-social-icon-wrap::after {
                background: rgba(255,255,255,0.08);
            }

            .higst-social-icon-wrap svg {
                width: 28px !important;
                height: 28px !important;
            }

            /* Staggered entry animation */
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
            function higstToggleShareModal(event) {
                event.stopPropagation();
                const modal = document.getElementById('higst-share-modal');
                const btn   = document.getElementById('higst-share-btn');
                const isOpen = modal.classList.contains('is-open');
                if (isOpen) {
                    higstCloseShareModal();
                } else {
                    higstOpenShareModal();
                }
            }

            function higstOpenShareModal() {
                const modal = document.getElementById('higst-share-modal');
                const btn   = document.getElementById('higst-share-btn');
                modal.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';

                // Re-trigger stagger animations on each open
                document.querySelectorAll('.higst-social-item').forEach(function(el, i) {
                    el.style.animation = 'none';
                    el.offsetHeight; // reflow
                    el.style.animation = '';
                });
            }

            function higstCloseShareModal() {
                const modal = document.getElementById('higst-share-modal');
                const btn   = document.getElementById('higst-share-btn');
                modal.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }

            function higstCopyLink(url) {
                const btn = document.getElementById('higst-copy-btn');
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function() {
                        higstShowCopied(btn);
                    }).catch(function() {
                        higstFallbackCopy(url, btn);
                    });
                } else {
                    higstFallbackCopy(url, btn);
                }
            }

            function higstFallbackCopy(url, btn) {
                const ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.focus();
                ta.select();
                try { document.execCommand('copy'); higstShowCopied(btn); } catch(e) {}
                document.body.removeChild(ta);
            }

            function higstShowCopied(btn) {
                btn.classList.add('copied');
                const span = btn.querySelector('span');
                const origText = span.textContent;
                span.textContent = 'تم النسخ ✓';
                setTimeout(function() {
                    btn.classList.remove('copied');
                    span.textContent = origText;
                }, 2200);
            }

            // Popup share handler for desktop platforms
            document.addEventListener('click', function(e) {
                const item = e.target.closest('.higst-popup-share');
                if (item) {
                    e.preventDefault();
                    const url = item.getAttribute('data-url') || item.href;
                    window.open(url, '_blank', 'resizable=yes,top=200,left=300,width=580,height=500');
                }
            });

            // Close modal on Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') higstCloseShareModal();
            });
        </script>
    @endpush

@endif
