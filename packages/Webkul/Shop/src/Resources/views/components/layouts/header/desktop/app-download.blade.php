@php
    $isEnabled = core()->getConfigData('general.content.app_download.enabled');
    $isEnabled = is_null($isEnabled) ? true : (bool) $isEnabled;
@endphp

@if ($isEnabled)
    @php
        $title = core()->getConfigData('general.content.app_download.title') ?: 'حمل تطبيق HIGHEST';
        $subtitle = core()->getConfigData('general.content.app_download.subtitle') ?: 'امسح QR code للتحميل';
        $googlePlayLink = core()->getConfigData('general.content.app_download.google_play_link') ?: 'https://play.google.com/store/apps';
        $appStoreLink = core()->getConfigData('general.content.app_download.app_store_link') ?: 'https://www.apple.com/app-store/';
        $qrCodeImage = core()->getConfigData('general.content.app_download.qr_code_image');
        $qrTarget = $googlePlayLink ?: url('/');
    @endphp

    <div class="relative group flex items-center">
        <!-- Trigger button (Hover to show dropdown) -->
        <div 
            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-transparent hover:border-gray-200 hover:bg-gray-50/80 cursor-pointer transition-all duration-200 select-none"
            role="button"
            tabindex="0"
            aria-haspopup="true"
            aria-label="{{ $title }}"
        >
            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-zinc-100 text-zinc-700 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="2" width="14" height="20" rx="3" ry="3"></rect>
                    <line x1="12" y1="18" x2="12.01" y2="18"></line>
                    <polyline points="9 11 12 14 15 11"></polyline>
                    <line x1="12" y1="7" x2="12" y2="14"></line>
                </svg>
            </div>

            <div class="flex flex-col text-start leading-tight">
                <span class="text-[10px] text-zinc-500 font-medium">حمل تطبيق</span>
                <span class="text-xs font-bold text-zinc-800 group-hover:text-blue-600 transition-colors">HIGHEST</span>
            </div>
        </div>

        <!-- Popover Card on Hover -->
        <div class="invisible opacity-0 group-hover:visible group-hover:opacity-100 transition-all duration-200 transform translate-y-2 group-hover:translate-y-0 absolute top-full ltr:left-0 rtl:right-0 pt-2 z-50 pointer-events-none group-hover:pointer-events-auto">
            <div class="w-[370px] bg-white rounded-2xl shadow-[0_15px_50px_rgba(0,0,0,0.15)] border border-gray-100 p-5">
                <div class="flex items-center justify-between gap-4">
                    <!-- Right/Info Column: Titles & Store Badges -->
                    <div class="flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 leading-snug">
                                {{ $title }}
                            </h4>
                            <p class="text-[11px] text-gray-500 mt-0.5 mb-3">
                                {{ $subtitle }}
                            </p>
                        </div>

                        <!-- Store Links Buttons -->
                        <div class="flex flex-col gap-2">
                            <!-- Google Play -->
                            <a 
                                href="{{ $googlePlayLink }}" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl transition-all shadow-sm group/btn cursor-pointer hover:opacity-90"
                                style="background-color: #0F172A !important; color: #FFFFFF !important; border: 1px solid #1E293B !important;"
                            >
                                <svg class="w-5 h-5 flex-shrink-0" style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none">
                                    <path d="M3.6 1.7C3.3 2 3.2 2.5 3.2 3.1v17.8c0 .6.1 1.1.4 1.4l9.4-9.5L3.6 1.7z" fill="#00C3FF"/>
                                    <path d="M16.5 16.3l-3.5-3.5-9.4 9.5c.3.3.8.4 1.4.1l11.5-6.1z" fill="#FF3333"/>
                                    <path d="M16.5 7.7L5 1.5c-.6-.3-1.1-.2-1.4.2l9.4 9.5 3.5-3.5z" fill="#00E676"/>
                                    <path d="M20.8 10.1l-4.3-2.4-3.5 3.5 3.5 3.5 4.3-2.4c.8-.5.8-1.7 0-2.2z" fill="#FFD400"/>
                                </svg>

                                <div class="flex flex-col text-start leading-none">
                                    <span class="text-[8px] uppercase tracking-wider font-medium" style="color: #94A3B8 !important;">متوفر على</span>
                                    <span class="text-[11px] font-bold mt-0.5" style="color: #FFFFFF !important;">Google Play</span>
                                </div>
                            </a>

                            <!-- Apple App Store -->
                            <a 
                                href="{{ $appStoreLink }}" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl transition-all shadow-sm group/btn cursor-pointer hover:opacity-90"
                                style="background-color: #0F172A !important; color: #FFFFFF !important; border: 1px solid #1E293B !important;"
                            >
                                <svg class="w-5 h-5 flex-shrink-0" style="width: 20px; height: 20px; fill: #FFFFFF !important; color: #FFFFFF !important;" viewBox="0 0 24 24" fill="currentColor">
                                    <path fill="#FFFFFF" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 0.92-2.85-.9.04-1.99.6-2.63 1.35-.57.65-1.06 1.72-.93 2.74 1.01.08 2.03-.5 2.64-1.24z"/>
                                </svg>

                                <div class="flex flex-col text-start leading-none">
                                    <span class="text-[8px] uppercase tracking-wider font-medium" style="color: #94A3B8 !important;">حمل من</span>
                                    <span class="text-[11px] font-bold mt-0.5" style="color: #FFFFFF !important;">App Store</span>
                                </div>
                            </a>
                        </div>
                    </div>

                    <!-- Left Column: QR Code -->
                    <div class="flex flex-col items-center justify-center p-2 rounded-xl bg-gray-50 border border-gray-100 shadow-inner flex-shrink-0">
                        <div class="w-[105px] h-[105px] bg-white rounded-lg flex items-center justify-center p-1 overflow-hidden border border-gray-100">
                            @if ($qrCodeImage)
                                <img 
                                    src="{{ Storage::url($qrCodeImage) }}" 
                                    alt="QR Code" 
                                    class="w-full h-full object-contain"
                                />
                            @else
                                @php
                                    $svgQr = (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->margin(0)->generate($qrTarget);
                                    $qrSrc = 'data:image/svg+xml;base64,' . base64_encode($svgQr);
                                @endphp
                                <img 
                                    src="{{ $qrSrc }}" 
                                    alt="QR Code" 
                                    class="w-full h-full object-contain"
                                />
                            @endif
                        </div>

                        <span class="mt-1.5 text-[9px] font-bold text-gray-500 uppercase tracking-wider">
                            امسح بكاميرا الهاتف
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
