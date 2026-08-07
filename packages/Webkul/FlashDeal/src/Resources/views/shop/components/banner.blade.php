@props([
    'title' => 'عروض سريعة حصريّة',
    'subtitle' => 'تخفيضات استثنائية لفترة محدودة جداً',
    'description' => 'اكتشف أرقى المنتجات بأسعار حصرية وحسومات فائقة قبل نفاد الكمية',
    'bannerImage' => null,
    'backgroundImage' => null,
    'accentColor' => '#FFC000',
    'secondaryColor' => '#002060',
])

<div 
    class="relative rounded-3xl p-6 md:p-8 text-white overflow-hidden shadow-xl flex flex-col justify-between min-h-[220px] transition-all duration-300 group"
    style="background: linear-gradient(135deg, {{ $secondaryColor }} 0%, #001238 60%, #051A4A 100%);"
>
    <!-- Background image overlay if provided -->
    @if ($backgroundImage)
        <div class="absolute inset-0 bg-cover bg-center opacity-25 mix-blend-overlay pointer-events-none" style="background-image: url('{{ $backgroundImage }}');"></div>
    @endif

    <!-- Subtle background glowing accents & decorative dots -->
    <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full blur-3xl opacity-30 pointer-events-none" style="background-color: {{ $accentColor }};"></div>
    <div class="absolute -bottom-10 -left-10 w-36 h-36 rounded-full blur-2xl opacity-20 pointer-events-none bg-blue-400"></div>
    <div class="absolute top-4 left-6 grid grid-cols-4 gap-1.5 opacity-20 pointer-events-none">
        @for ($i = 0; $i < 16; $i++)
            <div class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $accentColor }};"></div>
        @endfor
    </div>

    <!-- Content Row -->
    <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="flex-1 text-right">
            <!-- Dynamic Subtitle Badge -->
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black mb-3 border shadow-sm" style="background-color: rgba(255, 192, 0, 0.15); color: {{ $accentColor }}; border-color: rgba(255, 192, 0, 0.3);">
                <span class="animate-pulse">⚡</span>
                <span>{{ $subtitle }}</span>
            </div>

            <!-- Dynamic Banner Title -->
            <h2 class="text-2xl md:text-3xl lg:text-4xl font-extrabold tracking-wide text-white leading-tight mb-2">
                {!! $title !!}
            </h2>

            <!-- Dynamic Description -->
            <p class="text-xs md:text-sm text-gray-200 line-clamp-2 max-w-xl font-medium leading-relaxed">
                {{ $description }}
            </p>
        </div>

        <!-- Optional Banner Image or Glowing 3D Graphic Icon -->
        <div class="shrink-0 relative">
            @if ($bannerImage)
                <img 
                    src="{{ $bannerImage }}" 
                    alt="{{ strip_tags($title) }}" 
                    class="w-32 h-32 md:w-40 md:h-40 object-contain drop-shadow-2xl transform group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                />
            @else
                <div class="w-24 h-24 md:w-28 md:h-28 rounded-2xl flex items-center justify-center shadow-2xl transform -rotate-3 group-hover:rotate-0 transition-transform duration-300" style="background: linear-gradient(135deg, {{ $accentColor }} 0%, #D99B00 100%);">
                    <svg class="w-14 h-14 text-gray-950 fill-current drop-shadow" viewBox="0 0 24 24">
                        <path d="M7 2v11h3v9l7-12h-4l4-8z"/>
                    </svg>
                </div>
            @endif
        </div>
    </div>
</div>
