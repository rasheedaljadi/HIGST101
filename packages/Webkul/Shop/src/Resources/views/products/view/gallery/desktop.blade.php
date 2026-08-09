<!-- For screens 768px and greater (tablets, laptops, desktop). -->
<div class="sticky top-20 flex h-max gap-8 max-md:hidden">
    <!-- Product Image and Videos Slider -->
    <div class="flex-24 h-[448px] flex min-w-[60px] max-w-[60px] flex-wrap place-content-start justify-center gap-2.5 overflow-y-auto overflow-x-hidden">
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
            class="flex flex-col max-h-[500px] gap-2.5 [&>*]:flex-[0] overflow-auto scroll-smooth scrollbar-hide"
        >
            <template v-for="(media, index) in [...media.images, ...media.videos]">
                <video
                    v-if="media.type == 'videos'"
                    :class="`transparent aspect-square h-[60px] max-h-[60px] min-w-[60px] w-[60px] cursor-pointer rounded-md object-cover border bg-zinc-50 ${isActiveMedia(index) ? 'pointer-events-none border-2 border-navyBlue' : 'border-gray-200'}`"
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
                    :class="`transparent aspect-square h-[60px] max-h-[60px] min-w-[60px] w-[60px] cursor-pointer rounded-md object-cover border bg-zinc-50 ${isActiveMedia(index) ? 'pointer-events-none border-2 border-navyBlue' : 'border-gray-200'}`"
                    :src="media.small_image_url"
                    alt="{{ $product->name }}"
                    width="60"
                    height="60"
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
        class="w-full"
        style="aspect-ratio: 5 / 4; max-width: 560px; max-height: 448px; align-self: flex-start;"
        v-show="isMediaLoading"
    >
        <div class="shimmer h-full w-full rounded-xl bg-zinc-200"></div>
    </div>

    <div
        class="relative w-full overflow-hidden rounded-xl bg-white flex items-center justify-center"
        style="aspect-ratio: 5 / 4; max-width: 560px; max-height: 448px; align-self: flex-start;"
        v-show="! isMediaLoading"
    >
        <img
            class="h-full w-full cursor-pointer rounded-xl block"
            style="width: 100%; height: 100%; object-fit: fill;"
            :src="baseFile.path"
            v-if="baseFile.type == 'image'"
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
            v-if="baseFile.type == 'video'"
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
