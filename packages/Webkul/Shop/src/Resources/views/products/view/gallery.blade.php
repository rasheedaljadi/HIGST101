<v-product-gallery ref="gallery">
    <x-shop::shimmer.products.gallery />
</v-product-gallery>

@push('scripts')
    <script
        type="text/x-template"
        id="v-product-gallery-template"
    >
        <div>
            <!-- Desktop Gallery -->
            @include('shop::products.view.gallery.desktop')

            <!-- Mobile Gallery -->
            @include('shop::products.view.gallery.mobile')
            
            <!-- Gallery Images Zoomer -->
            <x-shop::image-zoomer
                ::attachments="attachments"
                ::is-image-zooming="isImageZooming"
                ::initial-index="`media_${activeIndex}`"
            />
        </div>
    </script>

    <script type="module">
        <?php
            $galleryImages = product_image()->getGalleryImages($product);
            $helper = app(\Webkul\FlashDeal\Helpers\SmartThumbnailHelper::class);
            if (! empty($galleryImages) && $helper->isProductPageActive()) {
                $productImageModels = $product->images;
                foreach ($galleryImages as $index => &$imageItem) {
                    $imageModel = $productImageModels[$index] ?? null;
                    if ($imageModel && isset($imageItem['large_image_url'])) {
                        $smartUrl = $helper->getProductDetailTest5x4ThumbnailForImage($product, $imageModel, $imageItem['large_image_url']);
                        $imageItem['large_image_url'] = $smartUrl;
                        $imageItem['medium_image_url'] = $smartUrl;
                        $imageItem['small_image_url'] = $smartUrl;
                    } elseif ($index === 0 && isset($imageItem['large_image_url'])) {
                        $smartUrl = $helper->getProductDetailTest5x4ThumbnailUrl($product, $imageItem['large_image_url']);
                        $imageItem['large_image_url'] = $smartUrl;
                        $imageItem['medium_image_url'] = $smartUrl;
                        $imageItem['small_image_url'] = $smartUrl;
                    }
                }
            }
        ?>

        app.component('v-product-gallery', {
            template: '#v-product-gallery-template',

            data() {
                return {
                    isImageZooming: false,

                    isMediaLoading: true,

                    media: {
                        images: @json($galleryImages),

                        videos: @json(product_video()->getVideos($product)),
                    },

                    baseFile: {
                        type: '',

                        path: ''
                    },

                    activeIndex: 0,

                    containerOffset: 110,
                };
            },

            watch: {
                'media.images': {
                    deep: true,

                    handler(newImages, oldImages) {
                        let selectedImage = newImages?.[this.activeIndex];

                        if (JSON.stringify(newImages) !== JSON.stringify(oldImages) && selectedImage?.large_image_url) {
                            this.baseFile.path = selectedImage.large_image_url;
                        }
                    },
                },
            },
        
            mounted() {
                if (this.media.images.length) {
                    this.baseFile.type = 'image';
                    this.baseFile.path = this.media.images[0].large_image_url;
                } else if (this.media.videos.length) {
                    this.baseFile.type = 'video';
                    this.baseFile.path = this.media.videos[0].video_url;
                } else {
                    this.baseFile.type = 'image';
                    this.baseFile.path = '{{ bagisto_asset("images/large-product-placeholder.webp", "shop") }}';
                    this.isMediaLoading = false;
                }
            },

            computed: {
                lengthOfMedia() {
                    if (this.media.images.length) {
                        return [...this.media.images, ...this.media.videos].length > 5;
                    }
                },

                attachments() {
                    let seen = new Set();
                    let items = [];

                    (this.media.images || []).forEach(img => {
                        let src = img.large_image_url || img.original_image_url || img.medium_image_url || img.small_image_url;
                        if (! src) return;

                        let cleanKey = src.split('?')[0].split('/').pop() || src;

                        if (! seen.has(cleanKey)) {
                            seen.add(cleanKey);
                            items.push({
                                url: src,
                                type: 'image',
                            });
                        }
                    });

                    (this.media.videos || []).forEach(vid => {
                        let src = vid.video_url;
                        if (! src) return;

                        if (! seen.has(src)) {
                            seen.add(src);
                            items.push({
                                url: src,
                                type: 'video',
                            });
                        }
                    });

                    return items;
                },
            },

            methods: {
                isActiveMedia(index) {
                    return index === this.activeIndex;
                },
                
                onMediaLoad() {
                    this.isMediaLoading = false;
                },

                change(media, index) {
                    this.isMediaLoading = true;

                    if (media.type == 'videos') {
                        this.baseFile.type = 'video';

                        this.baseFile.path = media.video_url;

                        this.onMediaLoad();
                    } else {
                        this.baseFile.type = 'image';

                        this.baseFile.path = media.large_image_url;
                    }

                    if (index > this.activeIndex) {
                        this.swipeDown();
                    } else if (index < this.activeIndex) {
                        this.swipeTop();
                    }

                    this.activeIndex = index;
                },

                swipeTop() {
                    const container = this.$refs.swiperContainer;

                    container.scrollTop -= this.containerOffset;
                },

                swipeDown() {
                    const container = this.$refs.swiperContainer;

                    container.scrollTop += this.containerOffset;
                },
            },
        });
    </script>
@endpush
