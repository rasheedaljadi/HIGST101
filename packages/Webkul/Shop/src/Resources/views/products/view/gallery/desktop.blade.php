<!-- For large screens greater than 1180px. -->
<div class="sticky top-20 flex h-max gap-6 max-1180:hidden">
    <!-- Product Image and Videos Slider -->
    <div class="flex-24 h-[500px] max-h-[500px] flex min-w-[100px] max-w-[100px] flex-wrap place-content-start justify-center gap-2.5 overflow-y-auto overflow-x-hidden">
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
                    :class="`transparent max-h-[100px] min-w-[100px] cursor-pointer rounded-xl border ${isActiveMedia(index) ? 'pointer-events-none border-navyBlue' : 'border-white'}`"
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
                    :class="`transparent max-h-[100px] min-w-[100px] cursor-pointer rounded-xl border ${isActiveMedia(index) ? 'pointer-events-none border border-navyBlue' : 'border-white'}`"
                    :src="media.small_image_url"
                    alt="{{ $product->name }}"
                    width="100"
                    height="100"
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
            v-if= "lengthOfMedia"
            role="button"
            aria-label="@lang('shop::app.components.products.carousel.previous')"
            tabindex="0"
            @click="swipeTop"
        >
        </span>
    </div>

    <!-- Product Base Image and Video with Shimmer-->
    <div
        class="aspect-[4/5] w-[400px] h-[500px] max-h-[500px] max-w-[400px]"
        v-show="isMediaLoading"
    >
        <div class="shimmer aspect-[4/5] h-[500px] w-[400px] min-h-[500px] min-w-[400px] rounded-2xl border border-gray-200 bg-zinc-200"></div>
    </div>

    <!-- Defined 4:5 Bounding Frame (400x500px) -->
    <div
        class="relative aspect-[4/5] w-[400px] h-[500px] max-h-[500px] max-w-[400px] overflow-hidden rounded-2xl border-2 border-gray-200 shadow-sm bg-white p-2 flex items-center justify-center"
        v-show="! isMediaLoading"
    >
        <img
            class="max-h-full max-w-full h-auto w-auto object-contain cursor-pointer rounded-xl m-auto"
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
            class="w-full h-full cursor-pointer rounded-xl flex items-center justify-center"
            tabindex="0"
            v-if="baseFile.type == 'video'"
        >
            <video
                controls
                width="380"
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
