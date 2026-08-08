<v-product-gallery ref="gallery">
    <x-shop::shimmer.products.gallery />
</v-product-gallery>

@pushonce('scripts')
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
        app.component('v-product-gallery', {
            template: '#v-product-gallery-template',

            data() {
                return {
                    isImageZooming: false,

                    isMediaLoading: true,

                    media: {
                        images: @json((function() use ($product) {
                            $images = product_image()->getGalleryImages($product);
                            if (! empty($images) && isset($images[0]['large_image_url'])) {
                                $helper = app(\Webkul\FlashDeal\Helpers\SmartThumbnailHelper::class);
                                $images[0]['large_image_url'] = $helper->getProductDetailThumbnailUrl($product, $images[0]['large_image_url']);
                            }
                            return $images;
                        })()),

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
@endpushonce
