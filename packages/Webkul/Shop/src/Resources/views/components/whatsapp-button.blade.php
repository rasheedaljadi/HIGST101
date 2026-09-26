@php
    $channel = core()->getCurrentChannel();
    $rawNumber = $channel?->whatsapp_number;
    $cleanNumber = $rawNumber ? preg_replace('/[^0-9]/', '', $rawNumber) : null;
    $isRtl = core()->getCurrentLocale()?->direction === 'rtl';
@endphp

@if (! empty($cleanNumber))
    <style>
        #whatsapp-floating-widget {
            position: fixed !important;
            bottom: 24px !important;
            {{ $isRtl ? 'left: 24px !important; right: auto !important;' : 'right: 24px !important; left: auto !important;' }}
            z-index: 99999 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        @media (max-width: 768px) {
            #whatsapp-floating-widget {
                bottom: 80px !important;
                {{ $isRtl ? 'left: 16px !important;' : 'right: 16px !important;' }}
            }
        }

        .wa-float-btn {
            position: relative !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 58px !important;
            height: 58px !important;
            border-radius: 50% !important;
            background-color: #25D366 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 18px rgba(37, 211, 102, 0.45), 0 2px 6px rgba(0, 0, 0, 0.15) !important;
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease !important;
            text-decoration: none !important;
            cursor: pointer !important;
        }

        @media (max-width: 768px) {
            .wa-float-btn {
                width: 50px !important;
                height: 50px !important;
            }
        }

        .wa-float-btn:hover {
            transform: scale(1.1) !important;
            background-color: #20ba56 !important;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.6), 0 4px 10px rgba(0, 0, 0, 0.2) !important;
        }

        .wa-float-btn:active {
            transform: scale(0.95) !important;
        }

        .wa-pulse-ring {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            border-radius: 50% !important;
            background-color: #25D366 !important;
            opacity: 0.5 !important;
            animation: wa-pulse 2.2s cubic-bezier(0.24, 0, 0.38, 1) infinite !important;
            pointer-events: none !important;
        }

        @keyframes wa-pulse {
            0% {
                transform: scale(1);
                opacity: 0.55;
            }
            70% {
                transform: scale(1.55);
                opacity: 0;
            }
            100% {
                transform: scale(1.55);
                opacity: 0;
            }
        }

        .wa-tooltip-text {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            {{ $isRtl ? 'left: calc(100% + 12px) !important;' : 'right: calc(100% + 12px) !important;' }}
            white-space: nowrap !important;
            background: #1f2937 !important;
            color: #ffffff !important;
            padding: 6px 14px !important;
            border-radius: 8px !important;
            font-size: 13px !important;
            font-weight: 500 !important;
            pointer-events: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.2s ease, visibility 0.2s ease !important;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2) !important;
        }

        .wa-float-btn:hover .wa-tooltip-text {
            opacity: 1 !important;
            visibility: visible !important;
        }
    </style>

    <div id="whatsapp-floating-widget" v-pre>
        <a
            href="https://wa.me/{{ $cleanNumber }}?text={{ rawurlencode('مرحباً هايست، لدي استفسار') }}"
            target="_blank"
            rel="noopener noreferrer"
            aria-label="WhatsApp"
            class="wa-float-btn"
        >
            <!-- Pulsing outer ring -->
            <span class="wa-pulse-ring"></span>

            <!-- Official WhatsApp SVG Icon -->
            <svg 
                style="width: 32px; height: 32px; fill: #ffffff !important; position: relative; z-index: 2;" 
                viewBox="0 0 24 24" 
                xmlns="http://www.w3.org/2000/svg"
            >
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/>
            </svg>

            <!-- Tooltip on hover -->
            <span class="wa-tooltip-text">
                {{ core()->getCurrentLocale()?->code === 'ar' ? 'تواصل معنا عبر واتساب' : 'Chat with us on WhatsApp' }}
            </span>
        </a>
    </div>
@endif
