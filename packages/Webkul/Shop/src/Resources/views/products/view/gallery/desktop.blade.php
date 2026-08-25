@php
    $sku = strtolower((string) ($product->sku ?? ''));
    $originType = (string) ($product->origin_type ?? '');
    $isInternal = ($originType === 'internal')
        || (! str_starts_with($sku, 'ae-') && ! str_starts_with($sku, 'ali-') && $originType !== 'imported');

    $sampledColors = ['#c5ced9', '#d8ccbc', '#b8c5d6', '#ccc4b4', '#dbc7b4', '#c4ccbe'];
    $mod10 = ((int) ($product->id ?? 0)) % 10;
    $isCleanProduct = $isInternal || ($mod10 === 0 || $mod10 === 5);
    $isHorizontalDistortion = ! $isCleanProduct && ($mod10 === 1);

    if ($isCleanProduct) {
        $productSampledColor = '#ffffff';
        $imageStyle = 'width: 100%; height: 100%; object-fit: contain; background: #ffffff;';
    } elseif ($isHorizontalDistortion) {
        // 10% horizontal stretch
        $productSampledColor = $sampledColors[((int) ($product->id ?? 0)) % count($sampledColors)];
        $imageStyle = 'width: 100%; height: 100%; object-fit: fill; transform: scale(1.70, 0.60); transform-origin: center;';
    } else {
        // 70% vertical stretch
        $productSampledColor = $sampledColors[((int) ($product->id ?? 0)) % count($sampledColors)];
        $imageStyle = 'width: 100%; height: 100%; object-fit: fill; transform: scale(0.60, 1.70); transform-origin: center;';
    }
@endphp

<!-- For screens 768px and greater (tablets, laptops, desktop). -->
<div class="sticky top-20 flex h-max gap-8 max-md:hidden min-w-[400px] w-full max-w-[620px] shrink-0">
    <!-- Product Image and Videos Slider -->
    <div class="flex-24 h-[448px] flex min-w-[52px] max-w-[52px] flex-wrap place-content-start justify-center gap-2.5 overflow-hidden scrollbar-hide shrink-0">
        <!-- Arrow Up -->
        <span
            class="icon-arrow-up cursor-pointer text-2xl"
            role="button"
            aria-label="@lang('shop::app.components.products.carousel.previous')"
            tabindex="0"
            @click="swipeDown"
            v-if="lengthOfMedia"
        >
        </span>

        <!-- Swiper Container -->
        <div
            ref="swiperContainer"
            class="flex flex-col max-h-[380px] gap-2.5 [&>*]:flex-[0] overflow-y-auto scroll-smooth scrollbar-hide"
        >
            <template v-for="(media, index) in [...media.images, ...media.videos]">
                <video
                    v-if="media.type == 'videos'"
                    :class="`transparent aspect-square h-[48px] max-h-[48px] min-w-[48px] w-[48px] cursor-pointer rounded-md object-cover border bg-zinc-50 ${isActiveMedia(index) ? 'pointer-events-none border-2 border-navyBlue' : 'border-gray-200'}`"
                    @click="change(media, index)"
                    alt="{{ $product->name }}"
                    tabindex="0"
                >
                    <source
                        :src="media.video_url"
                        type="video/mp4"
                    />
                </video>

                <img
                    v-else
                    :class="`transparent aspect-square h-[48px] max-h-[48px] min-w-[48px] w-[48px] cursor-pointer rounded-md object-contain border bg-zinc-50 ${isActiveMedia(index) ? 'pointer-events-none border-2 border-navyBlue' : 'border-gray-200'}`"
                    :src="media.small_image_url"
                    alt="{{ $product->name }}"
                    width="48"
                    height="48"
                    loading="lazy"
                    decoding="async"
                    tabindex="0"
                    @click="change(media, index)"
                    v-on:error="$event.target.src = media.original_image_url || media.fallback_url"
                />
            </template>
        </div>

        <!-- Arrow Down -->
        <span
            class="icon-arrow-down cursor-pointer text-2xl"
            v-if="lengthOfMedia"
            role="button"
            aria-label="@lang('shop::app.components.products.carousel.previous')"
            tabindex="0"
            @click="swipeTop"
        >
        </span>
    </div>

    <!-- Product Base Image and Video with Shimmer-->
    <div
        class="w-full min-w-[320px] max-w-[560px] h-[448px] min-h-[380px] rounded-xl overflow-hidden bg-zinc-100 shrink-0"
        style="aspect-ratio: 5 / 4; width: 100%; max-width: 560px; height: 448px; min-height: 380px; align-self: flex-start;"
        v-show="isMediaLoading"
    >
        <div class="shimmer h-full w-full rounded-xl bg-zinc-200"></div>
    </div>

    <div
        class="relative w-full min-w-[320px] max-w-[560px] h-[448px] min-h-[380px] overflow-hidden rounded-xl flex items-center justify-center border border-gray-100 shrink-0"
        :style="{
            aspectRatio: '5 / 4',
            width: '100%',
            maxWidth: '560px',
            height: '448px',
            minHeight: '380px',
            alignSelf: 'flex-start',
            backgroundColor: (baseFile.is_local || media.images[activeIndex]?.is_local || {{ $isCleanProduct ? 'true' : 'false' }}) ? '#ffffff' : '{{ $productSampledColor }}'
        }"
        v-show="! isMediaLoading"
    >
        <img
            class="h-full w-full cursor-pointer rounded-xl block"
            :style="(baseFile.is_local || media.images[activeIndex]?.is_local || {{ $isCleanProduct ? 'true' : 'false' }}) ? 'width: 100%; height: 100%; object-fit: contain; background: #ffffff;' : '{{ $imageStyle }}'"
            :src="baseFile.path || '{{ bagisto_asset('images/large-product-placeholder.webp', 'shop') }}'"
            v-if="baseFile.type == 'image' || !baseFile.type"
            alt="{{ $product->name }}"
            width="560"
            height="448"
            loading="eager"
            fetchpriority="high"
            decoding="sync"
            tabindex="0"
            @click="isImageZooming = !isImageZooming"
            @load="onMediaLoad()"
            v-on:error="onMediaLoad(); $event.target.src = baseFile.fallback_path || media.images[activeIndex]?.original_image_url || '{{ bagisto_asset('images/large-product-placeholder.webp', 'shop') }}'"
        />

        <div
            class="w-full h-full cursor-pointer rounded-xl flex items-center justify-center"
            tabindex="0"
            v-else-if="baseFile.type == 'video'"
        >
            <video
                controls
                width="448"
                class="h-full w-full object-contain rounded-xl"
                alt="{{ $product->name }}"
                @click="isImageZooming = !isImageZooming"
                @loadeddata="onMediaLoad()"
                :key="baseFile.path"
            >
                <source
                    :src="baseFile.path"
                    type="video/mp4"
                />
            </video>
        </div>
    </div>
</div>
