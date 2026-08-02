<v-gallery-zoomer {{ $attributes }}></v-gallery-zoomer>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-gallery-zoomer-template"
    >
        <transition
            name="modal-fade"
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <!-- Overlay Backdrop -->
            <div
                ref="parentContainer"
                class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/75 p-4 backdrop-blur-sm transition-all max-sm:p-2"
                v-show="isOpen"
                @click.self="toggle"
            >
                <!-- Modal Card Window (AliExpress Style) -->
                <div class="relative flex h-[88vh] max-h-[850px] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl md:flex-row">
                    
                    <!-- Top-Left Circular Close Button -->
                    <button
                        type="button"
                        class="absolute left-4 top-4 z-50 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-gray-100/90 text-gray-700 shadow-md backdrop-blur-sm transition-all hover:bg-gray-200 hover:text-black focus:outline-none max-sm:left-3 max-sm:top-3 max-sm:h-8 max-sm:w-8"
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
                        class="relative flex flex-1 items-center justify-center overflow-hidden bg-white p-6 max-sm:p-3"
                    >
                        <!-- Previous Arrow Button -->
                        <button
                            type="button"
                            class="absolute left-4 top-1/2 z-40 flex h-11 w-11 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/40 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-black/70 max-sm:left-2 max-sm:h-9 max-sm:w-9"
                            v-if="attachments.length >= 2"
                            @click="navigate(currentIndex - 1)"
                            title="السابق"
                        >
                            <svg class="h-6 w-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>

                        <!-- Next Arrow Button -->
                        <button
                            type="button"
                            class="absolute right-4 top-1/2 z-40 flex h-11 w-11 -translate-y-1/2 cursor-pointer items-center justify-center rounded-full bg-black/40 text-white shadow-lg backdrop-blur-sm transition-all hover:bg-black/70 max-sm:right-2 max-sm:h-9 max-sm:w-9"
                            v-if="attachments.length >= 2"
                            @click="navigate(currentIndex + 1)"
                            title="التالي"
                        >
                            <svg class="h-6 w-6 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>

                        <!-- Slides Container -->
                        <div
                            class="relative flex h-full w-full items-center justify-center"
                            :class="{
                                'h-full': ! isZooming,
                                'h-auto': isZooming
                            }"
                        >
                            <div
                                v-for="(attachment, index) in attachments"
                                class="flex h-full w-full items-center justify-center"
                                ref="slides"
                                :key="index"
                            >
                                <video 
                                    class="max-h-full max-w-full rounded-lg object-contain transition-transform duration-300 ease-out"
                                    controls 
                                    v-if="attachment.type == 'video'"
                                >
                                    <source :src="attachment.url" type="video/mp4">
                                    <source :src="attachment.url" type="video/ogg">
                                    متصفحك لا يدعم تشغيل الفيديو.
                                </video>

                                <template v-if="attachment.type === 'image'">
                                    <!-- Desktop Image -->
                                    <img
                                        :src="attachment.url"
                                        class="max-h-full max-w-full select-none rounded-lg object-contain transition-transform duration-300 ease-out max-md:hidden"
                                        :class="{
                                            'cursor-zoom-in': ! isZooming,
                                            'cursor-grab': ! isDragging && isZooming,
                                            'cursor-grabbing': isDragging && isZooming,
                                        }"
                                        :style="{transform: `translate(${translateX}px, ${translateY}px)`}"
                                        @click.stop="handleClick"
                                        @mousedown.prevent="handleMouseDown"
                                        @mousemove.prevent="handleMouseMove"
                                        @mouseleave.prevent="resetImagePosition"
                                        @mouseup.prevent="resetImagePosition"
                                        @mousewheel="handleMouseWheel"
                                    />

                                    <!-- Mobile Image -->
                                    <img
                                        :src="attachment.url"
                                        class="max-h-full max-w-full select-none rounded-lg object-contain transition-transform duration-300 ease-out md:hidden"
                                        :class="{
                                            'cursor-zoom-in': ! isZooming,
                                            'cursor-grab': ! isDragging && isZooming,
                                            'cursor-grabbing': isDragging && isZooming,
                                        }"
                                        :style="{transform: `translate(${translateX}px, ${translateY}px)`}"
                                    />    
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sidebar for Thumbnails List (AliExpress Style) -->
                    <div class="flex shrink-0 flex-row gap-3 overflow-x-auto border-t border-gray-100 bg-gray-50/70 p-4 scrollbar-thin md:w-32 md:flex-col md:overflow-y-auto md:border-l md:border-t-0 lg:w-36">
                        <template v-for="(attachment, index) in attachments">
                            <div
                                class="relative h-16 w-16 shrink-0 cursor-pointer overflow-hidden rounded-xl border-2 transition-all duration-200 hover:opacity-100 md:h-20 md:w-20"
                                :class="[
                                    currentIndex === index + 1
                                        ? 'border-navyBlue ring-4 ring-navyBlue/20 shadow-md opacity-100 scale-105'
                                        : 'border-gray-200 opacity-60 hover:border-gray-400'
                                ]"
                                :key="index"
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
                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>
            </div>
        </transition>
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

                    this.toggle();

                    this.$nextTick(() => {
                        this.navigate(this.currentIndex);
                    });
                },
            },
        
            data() {
                return {
                    isOpen: this.isImageZooming,

                    isDragging: false,

                    isZooming: false,

                    currentIndex: 1,

                    startDragX: 0,

                    startDragY: 0,

                    translateX: 0,

                    translateY: 0,

                    isMouseMoveTriggered: false,

                    isMouseDownTriggered: false,
                };
            },

            methods: {
                toggle() {
                    this.isOpen = ! this.isOpen;

                    document.body.style.overflow = this.isOpen ? 'hidden' : '';

                    if (this.isOpen) {
                        this.$nextTick(() => {
                            this.navigate(this.currentIndex);
                        });
                    }
                },

                open() {
                    this.isOpen = true;

                    document.body.style.overflow = 'hidden';

                    this.$nextTick(() => {
                        this.navigate(this.currentIndex);
                    });
                },

                navigate(index) {
                    if (index > this.attachments.length) {
                        this.currentIndex = 1;
                    } else if (index < 1) {
                        this.currentIndex = this.attachments.length;
                    } else {
                        this.currentIndex = index;
                    }

                    this.$nextTick(() => {
                        let slides = this.$refs.slides;

                        if (slides) {
                            for (let i = 0; i < slides.length; i++) {
                                if (i == this.currentIndex - 1) {
                                    slides[i].style.display = 'flex';
                                } else {
                                    slides[i].style.display = 'none';
                                }
                            }
                        }
                    });

                    this.isZooming = false;

                    this.resetDrag();
                },

                handleClick(event) {
                    if (
                        this.isMouseMoveTriggered
                        && ! this.isMouseDownTriggered
                    ) {
                        return;
                    }

                    this.resetDrag();

                    this.isZooming = ! this.isZooming;
                },

                handleOuterClick() {
                    if (! this.isZooming) {
                        return;
                    }

                    this.isZooming = false;

                    this.resetDrag();
                },

                handleMouseDown(event) {
                    this.isMouseDownTriggered = true;

                    this.isDragging = true;

                    this.startDragX = event.clientX;

                    this.startDragY = event.clientY;
                },

                handleMouseMove(event) {
                    this.isMouseMoveTriggered = true;
                    
                    this.isMouseDownTriggered = false;

                    if (! this.isDragging) {
                        return;
                    }

                    const deltaX = event.clientX - this.startDragX;
                    
                    const deltaY = event.clientY - this.startDragY;
                    
                    const newTranslateY = this.translateY + deltaY;

                    const remainingHeight = this.$refs.parentContainer.clientHeight - this.$refs.mediaContainer.clientHeight;

                    const maxTranslateY = Math.min(0, window.innerHeight - (event.srcElement.height + remainingHeight));

                    const clampedTranslateY = Math.max(maxTranslateY, Math.min(newTranslateY, 0));

                    this.translateY = clampedTranslateY;
                    
                    this.startDragY = event.clientY;
                    
                    this.startDragX = event.clientX;

                    this.translateX += deltaX;
                },

                handleMouseWheel(event) {
                    const deltaY = event.clientY - this.startDragY;

                    let newTranslateY = this.translateY - event.deltaY / Math.abs(event.deltaY) * 100;
                    
                    const remainingHeight = this.$refs.parentContainer.clientHeight - this.$refs.mediaContainer.clientHeight;

                    const maxTranslateY = Math.min(0, window.innerHeight - (event.srcElement.height + remainingHeight));

                    this.translateY = Math.max(maxTranslateY, Math.min(newTranslateY, 0));
                },

                resetImagePosition() {
                    this.isDragging = false;

                    this.translateX  = 0;

                    this.startDragX = 0;
                },

                resetDrag() {
                    this.isDragging = false;

                    this.startDragX = 0;

                    this.startDragY = 0;

                    this.translateX = 0;

                    this.translateY = 0;
                },
            },
        });
    </script>
@endpushonce