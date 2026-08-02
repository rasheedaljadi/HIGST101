<v-gallery-zoomer {{ $attributes }}></v-gallery-zoomer>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-gallery-zoomer-template"
    >
        <teleport to="body">
            <transition
                name="modal-fade"
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <!-- Full Screen Overlay Backdrop -->
                <div
                    ref="parentContainer"
                    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/75 p-4 backdrop-blur-md transition-all max-sm:p-2"
                    v-if="isOpen"
                    @click.self="toggle"
                >
                    <!-- Modal Card Window (Red Frame Dimensions: w-[75vw] h-[58vh] max-w-4xl) -->
                    <div class="relative flex h-[58vh] min-h-[360px] max-h-[520px] w-[75vw] max-w-4xl max-sm:h-[80vh] max-sm:w-[92vw] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl border border-gray-200 md:flex-row transition-all duration-300">
                        
                        <!-- Top-Left Circular Close Button -->
                        <button
                            type="button"
                            class="absolute left-3 top-3 z-[100] flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-gray-100 text-gray-700 shadow hover:bg-gray-200 hover:text-black focus:outline-none transition-all"
                            @click="toggle"
                            title="إغلاق"
                            aria-label="إغلاق"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <!-- Main Media Center Area -->
                        <div 
                            ref="mediaContainer" 
                            class="relative flex flex-1 items-center justify-center overflow-hidden bg-white p-4 max-sm:p-2 h-full w-full"
                        >
                            <!-- Previous Arrow Button -->
                            <button
                                type="button"
                                class="absolute left-3 top-1/2 z-40 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/40 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-black/70 max-sm:left-2 max-sm:h-8 max-sm:w-8"
                                v-if="attachments && attachments.length >= 2"
                                @click="navigate(currentIndex - 1)"
                                title="السابق"
                            >
                                <svg class="h-5 w-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <!-- Next Arrow Button -->
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 z-40 flex h-10 w-10 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/40 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-black/70 max-sm:right-2 max-sm:h-8 max-sm:w-8"
                                v-if="attachments && attachments.length >= 2"
                                @click="navigate(currentIndex + 1)"
                                title="التالي"
                            >
                                <svg class="h-5 w-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>

                            <!-- Slides Container (Strictly Contained) -->
                            <div class="relative flex h-full w-full items-center justify-center overflow-hidden">
                                <template v-for="(attachment, index) in attachments" :key="index">
                                    <div
                                        v-show="currentIndex === index + 1"
                                        class="flex h-full w-full items-center justify-center overflow-hidden p-1"
                                    >
                                        <video 
                                            class="max-h-full max-w-full rounded-lg object-contain"
                                            style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                            controls 
                                            v-if="attachment.type === 'video'"
                                        >
                                            <source :src="attachment.url" type="video/mp4">
                                            <source :src="attachment.url" type="video/ogg">
                                            متصفحك لا يدعم تشغيل الفيديو.
                                        </video>

                                        <template v-if="attachment.type === 'image'">
                                            <img
                                                :src="attachment.url"
                                                class="max-h-full max-w-full select-none rounded-lg object-contain transition-transform duration-200 ease-out pointer-events-auto"
                                                style="max-height: 100%; max-width: 100%; object-fit: contain;"
                                                @click.stop="handleClick"
                                            />
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right Sidebar for Thumbnails (AliExpress Style) -->
                        <div class="flex shrink-0 flex-row gap-2 overflow-x-auto border-t border-gray-100 bg-gray-50/90 p-3 scrollbar-thin md:w-28 md:flex-col md:overflow-y-auto md:border-l md:border-t-0 lg:w-32">
                            <template v-for="(attachment, index) in attachments" :key="index">
                                <div
                                    class="relative h-14 w-14 shrink-0 cursor-pointer overflow-hidden rounded-xl border-2 transition-all duration-200 hover:opacity-100 md:h-16 md:w-16"
                                    :class="[
                                        currentIndex === index + 1
                                            ? 'border-navyBlue ring-2 ring-navyBlue/20 shadow opacity-100 scale-105'
                                            : 'border-gray-200 opacity-60 hover:border-gray-400'
                                    ]"
                                    @click="navigate(index + 1)"
                                >
                                    <img
                                        class="h-full w-full object-cover"
                                        :src="attachment.url"
                                        v-if="attachment.type === 'image'"
                                        alt="thumbnail"
                                    />

                                    <video
                                        class="h-full w-full object-cover"
                                        :src="attachment.url"
                                        v-if="attachment.type === 'video'"
                                    />

                                    <div v-if="attachment.type === 'video'" class="absolute inset-0 flex items-center justify-center bg-black/30 text-white">
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            </template>
                        </div>

                    </div>
                </div>
            </transition>
        </teleport>
    </script>

    <script type="module">
        app.component('v-gallery-zoomer', {
            template: '#v-gallery-zoomer-template',

            props: {
                attachments: {
                    type: Object,

                    required: true,

                    default: () => [],
                },

                isImageZooming: {
                    type: Boolean,

                    default: false,
                },

                initialIndex: {
                    type: String,
                    
                    default: 0,
                },
            },

            watch: {
                isImageZooming(newVal, oldVal) {  
                    this.currentIndex = parseInt(this.initialIndex.split('_').pop()) + 1;

                    if (isNaN(this.currentIndex) || this.currentIndex < 1) {
                        this.currentIndex = 1;
                    }

                    this.toggle();

                    this.navigate(this.currentIndex);
                },
            },
        
            data() {
                return {
                    isOpen: this.isImageZooming,

                    isDragging: false,

                    isZooming: false,

                    currentIndex: 1,
                };
            },

            methods: {
                toggle() {
                    this.isOpen = ! this.isOpen;

                    document.body.style.overflow = this.isOpen ? 'hidden' : '';
                },

                open() {
                    this.isOpen = true;

                    document.body.style.overflow = 'hidden';
                },

                navigate(index) {
                    if (index > this.attachments.length) {
                        this.currentIndex = 1;
                    } else if (index < 1) {
                        this.currentIndex = this.attachments.length;
                    } else {
                        this.currentIndex = index;
                    }
                },

                handleClick(event) {
                    // Toggle zoom state if needed
                    this.isZooming = ! this.isZooming;
                },
            },
        });
    </script>
@endpushonce