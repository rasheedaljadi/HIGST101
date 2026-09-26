{!! view_render_event('bagisto.shop.layout.features.before') !!}

@inject('themeCustomizationRepository', 'Webkul\Theme\Repositories\ThemeCustomizationRepository')

@php
    $channel = core()->getCurrentChannel();
    $customization = null;
    try {
        $customization = $themeCustomizationRepository->findOneWhere(['type' => 'services_content', 'status' => 1, 'channel_id' => $channel->id]);
        if (! $customization) $customization = $themeCustomizationRepository->findOneWhere(['type' => 'services_content', 'status' => 1]);
        if (! $customization) $customization = $themeCustomizationRepository->findOneWhere(['type' => 'services_content']);
    } catch (\Throwable $e) {}
    $services = $customization?->options['services'] ?? [];
    if (empty($services) && $customization) {
        foreach ($customization->translations as $translation) {
            if (! empty($translation->options['services']) && is_array($translation->options['services'])) {
                $services = $translation->options['services'];
                break;
            }
        }
    }
    if (empty($services) || ! is_array($services)) {
        $services = [
            ['title' => 'الشحن السريع',           'description' => 'استمتع بالشحن السريع إلى جميع المحافظات والمدن', 'service_icon' => 'icon-truck'],
            ['title' => 'استبدال المنتج',          'description' => 'يمكنك إرجاع المنتج أو استبداله بسهولة تامة',     'service_icon' => 'icon-product'],
            ['title' => 'طرق دفع',                 'description' => 'نوفر طرق دفع متعددة ومتنوعة لتلبية احتياجاتك',   'service_icon' => 'icon-dollar-sign'],
            ['title' => 'الدعم على مدار الساعة',   'description' => 'دعم متخصص عبر الدردشة والبريد الإلكتروني',       'service_icon' => 'icon-support'],
        ];
    }
@endphp

@if (! empty($services))
    <script>
        (function(){
            if(document.getElementById('hgsvc-injected'))return;
            var s=document.createElement('style');
            s.id='hgsvc-injected';
            s.textContent=
                '.hgsvc-section{width:100%;margin-top:2.75rem;margin-bottom:2.5rem;user-select:none}'+
                '.hgsvc-inner{max-width:1320px;margin-left:auto;margin-right:auto;padding-left:1.25rem;padding-right:1.25rem}'+
                '.hgsvc-wrapper{background:linear-gradient(160deg,#ffffff 60%,#EEF4FF 100%);border:1px solid #E4EAF5;border-radius:1.75rem;padding:2rem 1.75rem 2.25rem;position:relative;overflow:hidden;box-shadow:0 8px 32px -8px rgba(0,26,84,.06),0 2px 6px -2px rgba(0,26,84,.03)}'+
                '.hgsvc-blob-tl{position:absolute;top:-3rem;right:-3rem;width:180px;height:180px;border-radius:50%;background:radial-gradient(circle,rgba(251,191,36,.15) 0%,transparent 70%);pointer-events:none}'+
                '.hgsvc-blob-br{position:absolute;bottom:-3rem;left:-3rem;width:200px;height:200px;border-radius:50%;background:radial-gradient(circle,rgba(59,130,246,.10) 0%,transparent 70%);pointer-events:none}'+
                '.hgsvc-header{text-align:center;position:relative;z-index:1;margin-bottom:1.75rem}'+
                '.hgsvc-badge{display:inline-block;background:#FBBF24;color:#1E293B;font-weight:800;font-size:.78rem;padding:.3rem 1rem;border-radius:9999px;letter-spacing:.02em;margin-bottom:.65rem;box-shadow:0 1px 4px rgba(245,158,11,.20)}'+
                '.hgsvc-title{color:#001A54;font-weight:900;font-size:1.7rem;line-height:1.2;letter-spacing:-.025em;margin:0 0 .3rem}'+
                '.hgsvc-title-accent{display:block;width:3.5rem;height:3px;background:#F5B800;border-radius:9999px;margin:.5rem auto .75rem}'+
                '.hgsvc-subtitle{color:#64748B;font-size:.9rem;line-height:1.6;max-width:480px;margin:0 auto}'+
                '.hgsvc-grid{display:grid !important;grid-template-columns:repeat(4,minmax(0,1fr)) !important;gap:1rem;position:relative;z-index:1}'+
                '.hgsvc-card{background:#F8FAFD;border:1px solid #E8EEF8;border-radius:1.25rem;padding:1.35rem 1.1rem 1.4rem;display:flex !important;flex-direction:column !important;align-items:center;text-align:center;transition:transform .22s ease,box-shadow .22s ease,background .22s ease;box-shadow:0 2px 8px -3px rgba(0,26,84,.05)}'+
                '.hgsvc-card:hover{transform:translateY(-5px);background:#F0F6FF;border-color:#CCDEFF;box-shadow:0 12px 28px -6px rgba(0,26,84,.10)}'+
                '.hgsvc-illus{width:100% !important;height:118px !important;background:#FFFFFF;border:1px solid #EDF1F9;border-radius:1rem;display:flex !important;align-items:center;justify-content:center;margin-bottom:1rem;overflow:hidden;position:relative;transition:background .22s ease,border-color .22s ease}'+
                '.hgsvc-card:hover .hgsvc-illus{background:#EEF5FF;border-color:#C5D9FF}'+
                '.hgsvc-illus-accent{position:absolute;top:.55rem;inset-inline-end:.55rem;width:8px;height:8px;background:#F5B800;border-radius:50%;opacity:.7}'+
                '.hgsvc-card-title{color:#001A54;font-weight:800;font-size:1rem;line-height:1.3;margin-bottom:.4rem}'+
                '.hgsvc-card-desc{color:#64748B;font-size:.8rem;line-height:1.55;max-width:200px;margin:0 auto}'+
                '@media(max-width:767px){'+
                    '.hgsvc-wrapper{padding:1.5rem 1rem 1.75rem;border-radius:1.25rem}'+
                    '.hgsvc-title{font-size:1.45rem !important}'+
                    '.hgsvc-grid{grid-template-columns:1fr !important;gap:.85rem}'+
                    '.hgsvc-card{flex-direction:column !important;text-align:center !important;padding:1.25rem 1rem;gap:0}'+
                    '.hgsvc-illus{width:100% !important;height:110px !important;margin-bottom:.85rem !important}'+
                    '.hgsvc-card-desc{max-width:90%;margin:0 auto}'+
                '}';
            document.head.appendChild(s);
        })();
    </script>


    <div class="hgsvc-section" v-pre>
        <div class="hgsvc-inner">
            <div class="hgsvc-wrapper">
                <div class="hgsvc-blob-tl"></div>
                <div class="hgsvc-blob-br"></div>

                <div class="hgsvc-header">
                    <span class="hgsvc-badge">مزايا التسوق مع HIGEST</span>
                    <h2 class="hgsvc-title">تسوق بثقة وراحة من أي مكان</h2>
                    <span class="hgsvc-title-accent"></span>
                    <p class="hgsvc-subtitle">نوفر لك تجربة تسوق متكاملة وآمنة مع أفضل الخدمات لتلبية احتياجاتك</p>
                </div>

                <div class="hgsvc-grid">
                    @foreach ($services as $index => $service)
                        @php
                            $icon  = trim($service['service_icon'] ?? '');
                            $title = trim($service['title'] ?? '');
                            $desc  = trim($service['description'] ?? '');
                            $illus = 'truck';
                            if (str_contains($icon,'truck')||str_contains($title,'شحن')||str_contains($title,'توصيل')||$index===0) $illus='truck';
                            elseif (str_contains($icon,'product')||str_contains($icon,'box')||str_contains($title,'استبدال')||str_contains($title,'إرجاع')||str_contains($title,'ارجاع')||$index===1) $illus='return';
                            elseif (str_contains($icon,'dollar')||str_contains($icon,'card')||str_contains($title,'دفع')||str_contains($title,'EMI')||str_contains($title,'بطاق')||$index===2) $illus='payment';
                            elseif (str_contains($icon,'support')||str_contains($icon,'headset')||str_contains($title,'دعم')||str_contains($title,'ساعة')||$index===3) $illus='support';
                        @endphp

                        <div class="hgsvc-card">
                            <div class="hgsvc-illus">
                                <div class="hgsvc-illus-accent"></div>

                                @if (str_starts_with($icon,'<svg')||str_starts_with($icon,'<img'))
                                    <div style="width:90px;height:70px;display:flex;align-items:center;justify-content:center">{!! $icon !!}</div>
                                @elseif (str_starts_with($icon,'http')||str_starts_with($icon,'/storage'))
                                    <img src="{{ $icon }}" style="width:90px;height:70px;object-fit:contain" alt="{{ $title }}">
                                @elseif ($illus==='truck')
                                    <svg width="130" height="80" viewBox="0 0 170 105" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="4" y="52" width="26" height="4.5" rx="2.25" fill="#FBBF24" opacity="0.9"/>
                                        <rect x="10" y="42" width="18" height="4.5" rx="2.25" fill="#FBBF24" opacity="0.7"/>
                                        <rect x="2" y="62" width="22" height="4.5" rx="2.25" fill="#FBBF24" opacity="0.7"/>
                                        <ellipse cx="94" cy="92" rx="64" ry="7" fill="#001A54" fill-opacity="0.07"/>
                                        <rect x="32" y="26" width="82" height="56" rx="9" fill="url(#sv2_cargo)"/>
                                        <path d="M32 35C32 30.03 36.03 26 41 26H105C109.97 26 114 30.03 114 35V40H32V35Z" fill="#3B82F6" opacity="0.22"/>
                                        <rect x="54" y="13" width="38" height="15" rx="3.5" fill="#F59E0B"/>
                                        <rect x="70" y="13" width="7" height="15" fill="#D97706"/>
                                        <path d="M112 38H138C144.08 38 149 42.92 149 49V78C149 80.21 147.21 82 145 82H112V38Z" fill="url(#sv2_cab)"/>
                                        <path d="M116 44H134C136.5 44 138.5 46 138.5 48.5V57H116V44Z" fill="#E0F2FE"/>
                                        <path d="M132 44L123 57H127L136 44H132Z" fill="#BAE6FD" opacity="0.8"/>
                                        <rect x="147" y="66" width="5.5" height="5.5" rx="2" fill="#FEF08A"/>
                                        <rect x="145" y="75" width="9" height="5" rx="2" fill="#94A3B8"/>
                                        <circle cx="54" cy="82" r="13" fill="#1E293B"/><circle cx="54" cy="82" r="6.5" fill="#E2E8F0"/><circle cx="54" cy="82" r="2.5" fill="#64748B"/>
                                        <circle cx="86" cy="82" r="13" fill="#1E293B"/><circle cx="86" cy="82" r="6.5" fill="#E2E8F0"/><circle cx="86" cy="82" r="2.5" fill="#64748B"/>
                                        <circle cx="131" cy="82" r="13" fill="#1E293B"/><circle cx="131" cy="82" r="6.5" fill="#E2E8F0"/><circle cx="131" cy="82" r="2.5" fill="#64748B"/>
                                        <defs>
                                            <linearGradient id="sv2_cargo" x1="32" y1="26" x2="114" y2="82" gradientUnits="userSpaceOnUse"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1D4ED8"/></linearGradient>
                                            <linearGradient id="sv2_cab" x1="112" y1="38" x2="149" y2="82" gradientUnits="userSpaceOnUse"><stop stop-color="#FBBF24"/><stop offset="1" stop-color="#F59E0B"/></linearGradient>
                                        </defs>
                                    </svg>
                                @elseif ($illus==='return')
                                    <svg width="130" height="80" viewBox="0 0 170 105" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <ellipse cx="85" cy="91" rx="55" ry="7" fill="#001A54" fill-opacity="0.07"/>
                                        <rect x="38" y="29" width="74" height="57" rx="11" fill="url(#sv2_box)"/>
                                        <path d="M38 40C38 33.92 42.92 29 49 29H101C107.08 29 112 33.92 112 40V44H38V40Z" fill="#3B82F6"/>
                                        <rect x="69" y="29" width="13" height="57" fill="#FBBF24"/>
                                        <rect x="38" y="53" width="74" height="9" fill="#F59E0B" opacity="0.9"/>
                                        <g filter="url(#sv2_fex)">
                                            <circle cx="118" cy="65" r="23" fill="#FFFFFF"/>
                                            <circle cx="118" cy="65" r="21" fill="url(#sv2_exbg)"/>
                                            <path d="M111 56C114 54 117.5 53.2 121 54C124.5 54.8 127.5 57.2 128.5 60.5M128.5 60.5V55M128.5 60.5H123" stroke="#2563EB" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M125 74C122 76 118.5 76.8 115 76C111.5 75.2 108.5 72.8 107.5 69.5M107.5 69.5V75M107.5 69.5H113" stroke="#2563EB" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </g>
                                        <defs>
                                            <filter id="sv2_fex" x="90" y="39" width="56" height="56" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feDropShadow dx="0" dy="3" stdDeviation="3" flood-opacity="0.14"/></filter>
                                            <linearGradient id="sv2_box" x1="38" y1="29" x2="112" y2="86" gradientUnits="userSpaceOnUse"><stop stop-color="#2563EB"/><stop offset="1" stop-color="#1E40AF"/></linearGradient>
                                            <linearGradient id="sv2_exbg" x1="97" y1="44" x2="139" y2="86" gradientUnits="userSpaceOnUse"><stop stop-color="#EFF6FF"/><stop offset="1" stop-color="#DBEAFE"/></linearGradient>
                                        </defs>
                                    </svg>
                                @elseif ($illus==='payment')
                                    <svg width="130" height="80" viewBox="0 0 170 105" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <ellipse cx="85" cy="91" rx="56" ry="7" fill="#001A54" fill-opacity="0.07"/>
                                        <rect x="38" y="23" width="75" height="48" rx="9" transform="rotate(-6 38 23)" fill="url(#sv2_cy)"/>
                                        <rect x="45" y="29" width="76" height="49" rx="9" fill="url(#sv2_cb)"/>
                                        <rect x="52" y="36" width="34" height="18" rx="4.5" fill="#FFFFFF"/>
                                        <text x="69" y="49.5" font-family="Arial,sans-serif" font-weight="900" font-size="11" fill="#2563EB" text-anchor="middle">EMI</text>
                                        <rect x="52" y="62" width="26" height="4" rx="2" fill="#93C5FD" opacity="0.85"/>
                                        <rect x="52" y="69.5" width="44" height="3" rx="1.5" fill="#BFDBFE" opacity="0.65"/>
                                        <g filter="url(#sv2_fcoin)">
                                            <circle cx="123" cy="65" r="19" fill="url(#sv2_coin)"/>
                                            <circle cx="123" cy="65" r="15.5" stroke="#FEF08A" stroke-width="1.75" fill="none"/>
                                            <text x="123" y="73" font-family="Arial,sans-serif" font-weight="900" font-size="20" fill="#92400E" text-anchor="middle">$</text>
                                        </g>
                                        <defs>
                                            <filter id="sv2_fcoin" x="99" y="43" width="48" height="48" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feDropShadow dx="0" dy="3" stdDeviation="2.5" flood-opacity="0.20"/></filter>
                                            <linearGradient id="sv2_cy" x1="38" y1="23" x2="113" y2="71" gradientUnits="userSpaceOnUse"><stop stop-color="#FBBF24"/><stop offset="1" stop-color="#D97706"/></linearGradient>
                                            <linearGradient id="sv2_cb" x1="45" y1="29" x2="121" y2="78" gradientUnits="userSpaceOnUse"><stop stop-color="#3B82F6"/><stop offset="1" stop-color="#1D4ED8"/></linearGradient>
                                            <linearGradient id="sv2_coin" x1="104" y1="46" x2="142" y2="84" gradientUnits="userSpaceOnUse"><stop stop-color="#FDE047"/><stop offset="0.5" stop-color="#F59E0B"/><stop offset="1" stop-color="#D97706"/></linearGradient>
                                        </defs>
                                    </svg>
                                @else
                                    <svg width="130" height="80" viewBox="0 0 170 105" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <ellipse cx="80" cy="91" rx="50" ry="7" fill="#001A54" fill-opacity="0.07"/>
                                        <path d="M44 60C44 38 60 23 81 23C102 23 118 38 118 60" stroke="url(#sv2_hb)" stroke-width="8" stroke-linecap="round"/>
                                        <rect x="35" y="53" width="16" height="29" rx="8" fill="#2563EB"/>
                                        <rect x="44" y="56" width="9" height="23" rx="4.5" fill="#FBBF24"/>
                                        <rect x="111" y="53" width="16" height="29" rx="8" fill="#2563EB"/>
                                        <rect x="109" y="56" width="9" height="23" rx="4.5" fill="#FBBF24"/>
                                        <path d="M44 71C44 82 59 88 71 88" stroke="#1E293B" stroke-width="3.5" stroke-linecap="round"/>
                                        <rect x="69" y="84" width="9" height="8" rx="4" fill="#0F172A"/>
                                        <g filter="url(#sv2_fchat)">
                                            <path d="M104 27C104 20.92 108.92 16 115 16H137C143.08 16 148 20.92 148 27V38C148 44.08 143.08 49 137 49H124L113 57V49H115C108.92 49 104 44.08 104 38V27Z" fill="#FFFFFF"/>
                                            <circle cx="119" cy="33" r="2.75" fill="#2563EB"/>
                                            <circle cx="126" cy="33" r="2.75" fill="#2563EB"/>
                                            <circle cx="133" cy="33" r="2.75" fill="#2563EB"/>
                                        </g>
                                        <defs>
                                            <filter id="sv2_fchat" x="99" y="13" width="54" height="50" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feDropShadow dx="0" dy="3" stdDeviation="2.5" flood-opacity="0.13"/></filter>
                                            <linearGradient id="sv2_hb" x1="44" y1="23" x2="118" y2="60" gradientUnits="userSpaceOnUse"><stop stop-color="#1D4ED8"/><stop offset="0.5" stop-color="#3B82F6"/><stop offset="1" stop-color="#1D4ED8"/></linearGradient>
                                        </defs>
                                    </svg>
                                @endif
                            </div>
                            <div class="hgsvc-card-content" style="width:100%">
                                <h3 class="hgsvc-card-title">{{ $title }}</h3>
                                <p class="hgsvc-card-desc">{{ $desc }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif

{!! view_render_event('bagisto.shop.layout.features.after') !!}
