@props([
    'title' => 'العروض السريعة',
    'subtitle' => 'عروض محدودة لفترة قصيرة ..',
    'description' => 'اغتنم الفرصة قبل انتهاء الوقت!',
    'bannerImage' => null,
    'backgroundImage' => null,
    'accentColor' => '#fbbf24',
    'secondaryColor' => '#1e3a8a',
])

<div 
    class="w-full p-6 md:p-8 relative overflow-hidden shadow-xl text-white flex items-center min-h-[190px] border border-white/10"
    style="background: linear-gradient(135deg, #1e3a8a 0%, #172554 100%); border-radius: 1rem 4rem 4rem 1rem;"
>
    <!-- Background Glow Blurs -->
    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl pointer-events-none"></div>
    <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-[#fbbf24] opacity-10 rounded-full blur-2xl pointer-events-none"></div>

    @if ($backgroundImage)
        <div class="absolute inset-0 bg-cover bg-center opacity-20 mix-blend-overlay pointer-events-none" style="background-image: url('{{ $backgroundImage }}');"></div>
    @endif

    <div class="flex-1 text-right pr-2 md:pr-6 z-10">
        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-3 text-[#fbbf24] drop-shadow-md leading-tight">
            {!! $title !!}
        </h1>
        <p class="text-lg md:text-xl mb-1 text-gray-200 font-medium">
            {{ $subtitle }}
        </p>
        @if ($description)
            <p class="text-base md:text-lg text-gray-300 font-normal">
                {{ $description }}
            </p>
        @endif
    </div>

    <div class="w-1/3 relative flex justify-center items-center h-40 md:h-48 shrink-0">
        @if ($bannerImage)
            <img 
                src="{{ $bannerImage }}" 
                alt="{{ strip_tags($title) }}" 
                class="max-h-36 md:max-h-44 object-contain z-10 drop-shadow-2xl"
                loading="lazy"
            />
        @else
            <div class="text-[#fbbf24] text-7xl md:text-8xl drop-shadow-lg z-10 absolute animate-pulse">
                ⚡
            </div>
            <div class="text-white text-5xl md:text-6xl opacity-80 absolute right-2 top-2 md:right-4 md:top-4 drop-shadow-md z-0">
                🕒
            </div>
        @endif
    </div>
</div>
