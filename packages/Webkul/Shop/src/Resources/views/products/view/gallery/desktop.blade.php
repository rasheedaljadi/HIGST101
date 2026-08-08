<!-- For large screens greater than 1180px. -->
<div class="sticky top-20 flex h-max gap-6 max-1180:hidden">
    <!-- Product Image and Videos Slider (Thumbnails) -->
    <div class="flex-24 h-[500px] flex min-w-[90px] max-w-[90px] flex-wrap place-content-start justify-center gap-2.5 overflow-y-auto overflow-x-hidden">
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
            class="flex flex-col max-h-[440px] gap-2.5 [&>*]:flex-[0] overflow-auto scroll-smooth scrollbar-hide"
        >
            <template v-for="(media, index) in [...media.images, ...media.videos]">
                <video
                    v-if="media.type == 'videos'"
                    :class="`transparent aspect-[4/5] max-h-[112px] min-w-[90px] w-[90px] cursor-pointer rounded-xl object-contain border bg-[#f8f8f8] ${isActiveMedia(index) ? 'pointer-events-none border-2 border-navyBlue' : 'border-gray-200'}`"
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
                    :class="`transparent aspect-[4/5] max-h-[112px] min-w-[90px] w-[90px] cursor-pointer rounded-xl object-contain border bg-[#f8f8f8] ${isActiveMedia(index) ? 'pointer-events-none border-2 border-navyBlue' : 'border-gray-200'}`"
                    :src="media.small_image_url"
                    alt="{{ $product->name }}"
                    width="90"
                    height="112"
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
        class="aspect-[4/5] w-[400px] max-w-[480px] rounded-[20px] bg-[#f8f8f8] flex items-center justify-center overflow-hidden"
        v-show="isMediaLoading"
    >
        <div class="shimmer aspect-[4/5] w-full h-full rounded-[20px] bg-zinc-200"></div>
    </div>

    <div
        class="relative aspect-[4/5] w-[400px] max-w-[480px] rounded-[20px] bg-[#f8f8f8] flex items-center justify-center overflow-hidden"
        v-show="! isMediaLoading"
    >
        <img
            class="h-full w-full object-contain object-center cursor-pointer block rounded-[20px]"
            :src="baseFile.path"
            v-if="baseFile.type == 'image'"
            alt="{{ $product->name }}"
            width="400"
            height="500"
            loading="eager"
            fetchpriority="high"
            decoding="sync"
            tabindex="0"
            @click="isImageZooming = !isImageZooming"
            @load="onMediaLoad()"
            v-on:error="onMediaLoad(); $event.target.src = baseFile.fallback_path || media.images[activeIndex]?.original_image_url || '{{ bagisto_asset('images/large-product-placeholder.webp', 'shop') }}'"
        />

        <div
            class="w-full h-full cursor-pointer rounded-[20px] flex items-center justify-center"
            tabindex="0"
            v-if="baseFile.type == 'video'"
        >
            <video
                controls
                width="400"
                class="h-full w-full object-contain rounded-[20px]"
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
