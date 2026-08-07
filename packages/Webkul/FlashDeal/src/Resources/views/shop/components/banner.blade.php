@props([
    'title' => 'العروض السريعة',
    'subtitle' => 'عروض محدودة لفترة قصيرة .. اغتنم الفرصة قبل انتهاء الوقت!',
    'description' => null,
    'bannerImage' => null,
    'backgroundImage' => null,
    'accentColor' => '#FFC000',
    'secondaryColor' => '#001D56',
])

@php
    $formattedTitle = $title;
    if (str_contains($title, 'العروض') && ! str_contains($title, 'span')) {
        $formattedTitle = str_replace('السريعة', '<span class="text-[#FFC000]">السريعة</span>', $title);
        if ($formattedTitle === $title) {
            $formattedTitle = 'العروض <span class="text-[#FFC000]">السريعة</span>';
        }
    }
@endphp

<div 
    class="w-full relative rounded-[2.2rem] p-6 md:p-8 text-white overflow-hidden shadow-2xl flex items-center justify-between min-h-[170px] transition-all duration-300 group border border-white/10"
    style="background: linear-gradient(135deg, #00123A 0%, {{ $secondaryColor }} 50%, #082970 100%);"
>
    <!-- Background subtle image if provided -->
    @if ($backgroundImage)
        <div class="absolute inset-0 bg-cover bg-center opacity-20 mix-blend-overlay pointer-events-none" style="background-image: url('{{ $backgroundImage }}');"></div>
    @endif

    <!-- Radial Glow Behind 3D Lightning Graphic -->
    <div class="absolute right-4 top-1/2 -translate-y-1/2 w-56 h-56 rounded-full bg-[#FFC000]/15 blur-3xl pointer-events-none"></div>

    <div class="relative z-10 w-full flex items-center justify-between gap-6">
        
        <!-- Right side (RTL): 3D Glowing Lightning Bolt & Speed Clock Graphic -->
        <div class="shrink-0 relative flex items-center justify-center">
            @if ($bannerImage)
                <img 
                    src="{{ $bannerImage }}" 
                    alt="{{ strip_tags($title) }}" 
                    class="w-28 h-28 md:w-36 md:h-36 object-contain drop-shadow-[0_10px_25px_rgba(255,192,0,0.3)] transform group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                />
            @else
                <!-- 3D Lightning Bolt & Speed Clock Graphic Matching Image 0 -->
                <div class="relative w-28 h-28 md:w-36 md:h-36 flex items-center justify-center">
                    <!-- Speed Clock Outer Ring -->
                    <div class="absolute inset-0 rounded-full border-4 border-blue-400/20 bg-blue-950/40 backdrop-blur-sm flex items-center justify-center shadow-2xl">
                        <!-- Clock Dial Marks & Hands -->
                        <div class="w-16 h-16 md:w-20 md:h-20 rounded-full border-2 border-blue-300/30 relative flex items-center justify-center">
                            <div class="absolute top-2 w-0.5 h-6 bg-blue-200/80 rounded-full origin-bottom transform rotate-45"></div>
                            <div class="w-2 h-2 rounded-full bg-[#FFC000] shadow-md"></div>
                        </div>
                    </div>

                    <!-- Glowing 3D Yellow Lightning Bolt Overlay -->
                    <div class="relative z-10 transform -rotate-12 group-hover:rotate-0 transition-transform duration-300 drop-shadow-[0_12px_24px_rgba(255,192,0,0.4)]">
                        <svg class="w-20 h-20 md:w-24 md:h-24 text-[#FFC000] fill-current" viewBox="0 0 24 24">
                            <path d="M7 2v11h3v9l7-12h-4l4-8z"/>
                        </svg>
                    </div>
                </div>
            @endif
        </div>

        <!-- Left side (RTL Text): Main Heading & Subtitle -->
        <div class="flex-1 text-right">
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight mb-2">
                {!! $formattedTitle !!}
            </h2>

            <p class="text-xs md:text-sm text-gray-200 font-bold leading-relaxed opacity-95">
                {{ $subtitle }}
            </p>

            @if ($description)
                <p class="text-[11px] md:text-xs text-gray-300/80 font-medium mt-1">
                    {{ $description }}
                </p>
            @endif
        </div>

    </div>
</div>
