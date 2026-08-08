@props([
    'title' => 'عرض الصيف',
    'subtitle' => 'عروض محدودة لفترة قصيرة ..',
    'description' => 'اغتنم الفرصة قبل انتهاء الوقت!',
    'bannerImage' => null,
    'backgroundImage' => null,
    'accentColor' => '#FFC000',
    'secondaryColor' => '#001A54',
])

@php
    $formattedTitle = $title;
    if (! str_contains($title, '<span')) {
        $words = explode(' ', trim($title));
        if (count($words) >= 2) {
            $lastWord = array_pop($words);
            $formattedTitle = implode(' ', $words) . ' <span class="text-[#FFC000]">' . $lastWord . '</span>';
        } else {
            $formattedTitle = '<span class="text-[#FFC000]">' . $title . '</span>';
        }
    }
@endphp

<div 
    class="w-full p-4 sm:p-6 md:p-8 relative overflow-hidden shadow-2xl text-white flex flex-col sm:flex-row items-center justify-between min-h-[180px] sm:min-h-[200px] border border-white/10 rounded-3xl sm:rounded-[2.2rem] gap-4 sm:gap-6"
    style="background: linear-gradient(135deg, #001238 0%, #001A54 50%, #072870 100%);"
>
    <!-- Background Glow Blurs -->
    <div class="absolute -right-10 -top-10 w-44 h-44 bg-white opacity-5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -left-10 -bottom-10 w-44 h-44 bg-[#FFC000] opacity-10 rounded-full blur-3xl pointer-events-none"></div>

    @if ($backgroundImage)
        <div class="absolute inset-0 bg-cover bg-center opacity-20 mix-blend-overlay pointer-events-none" style="background-image: url('{{ $backgroundImage }}');"></div>
    @endif

    <div class="relative z-10 w-full flex flex-col-reverse sm:flex-row items-center justify-between gap-4 sm:gap-6">
        
        <!-- Text Side (RTL Start / Right) -->
        <div class="flex-1 text-center sm:text-right pr-0 sm:pr-2 md:pr-4">
            <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-extrabold mb-2 sm:mb-3 text-white drop-shadow-md leading-tight">
                {!! $formattedTitle !!}
            </h1>
            <p class="text-sm sm:text-base md:text-lg mb-1 text-gray-200 font-medium">
                {{ $subtitle }}
            </p>
            @if ($description)
                <p class="text-sm sm:text-base md:text-lg text-gray-300 font-medium">
                    {{ $description }}
                </p>
            @endif
        </div>

        <!-- Graphic Side (RTL End / Left): 3D Stop Clock + Yellow Bolt with Speed Lines -->
        <div class="shrink-0 relative flex items-center justify-center w-28 h-28 sm:w-36 sm:h-36 md:w-44 md:h-44">
            @if ($bannerImage)
                <img 
                    src="{{ $bannerImage }}" 
                    alt="{{ strip_tags($title) }}" 
                    class="max-h-28 sm:max-h-36 md:max-h-44 object-contain z-10 drop-shadow-2xl"
                    loading="lazy"
                />
            @else
                <!-- 3D Stop Clock & Yellow Bolt Graphic Matching Image Proposal -->
                <div class="relative w-28 h-28 sm:w-36 sm:h-36 md:w-44 md:h-44 flex items-center justify-center">
                    <!-- Clock Circle Base -->
                    <div class="w-20 h-20 sm:w-28 sm:h-28 md:w-36 md:h-36 rounded-full border-4 border-blue-300/40 bg-gradient-to-br from-blue-900 to-blue-950 flex items-center justify-center shadow-2xl relative">
                        <!-- Top Alarm Buttons -->
                        <div class="absolute -top-3 left-3 sm:left-4 w-3 sm:w-4 h-2.5 sm:h-3 bg-blue-300 rounded-t-sm transform -rotate-45"></div>
                        <div class="absolute -top-3 right-3 sm:right-4 w-3 sm:w-4 h-2.5 sm:h-3 bg-blue-300 rounded-t-sm transform rotate-45"></div>
                        <!-- Clock Dial & Red Hands -->
                        <div class="w-14 h-14 sm:w-20 sm:h-20 md:w-28 md:h-28 rounded-full bg-white flex items-center justify-center relative shadow-inner">
                            <div class="w-1.5 h-1.5 rounded-full bg-gray-900 z-20"></div>
                            <!-- Hour hand -->
                            <div class="absolute top-3 sm:top-4 w-1 h-4 sm:h-6 bg-red-600 rounded-full origin-bottom transform rotate-45 z-10"></div>
                            <!-- Minute hand -->
                            <div class="absolute top-1.5 sm:top-2 w-0.5 h-5 sm:h-8 bg-[#001A54] rounded-full origin-bottom transform -rotate-30 z-10"></div>
                            <!-- Ticks -->
                            <div class="absolute top-1 w-1 h-1 bg-gray-400"></div>
                            <div class="absolute bottom-1 w-1 h-1 bg-gray-400"></div>
                            <div class="absolute left-1 w-1 h-1 bg-gray-400"></div>
                            <div class="absolute right-1 w-1 h-1 bg-gray-400"></div>
                        </div>
                    </div>

                    <!-- Glowing 3D Yellow Lightning Bolt in front with Speed Lines -->
                    <div class="absolute -right-1 sm:-right-2 bottom-0 z-20 transform rotate-12 drop-shadow-[0_12px_24px_rgba(255,192,0,0.5)]">
                        <svg class="w-16 h-16 sm:w-24 sm:h-24 md:w-28 md:h-28 text-[#FFC000] fill-current" viewBox="0 0 24 24">
                            <path d="M7 2v11h3v9l7-12h-4l4-8z"/>
                        </svg>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
