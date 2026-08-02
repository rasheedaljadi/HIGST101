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
                    class="fixed inset-0 z-[99999] flex items-center justify-center backdrop-blur-md transition-all"
                    style="background: rgba(0,0,0,0.7); padding: 40px;"
                    v-if="isOpen"
                    @click.self="toggle"
                >
                    <!-- Modal Card Window — fixed inline dimensions so it works without Tailwind rebuild -->
                    <div
                        style="position: relative; display: flex; flex-direction: row; width: 72vw; height: 55vh; max-width: 900px; max-height: 500px; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 1px solid #e5e7eb;"
                    >
                        
                        <!-- Close Button (top-left) -->
                        <button
                            type="button"
                            style="position: absolute; top: 12px; left: 12px; z-index: 100; display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: rgba(243,244,246,0.95); color: #374151; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.12); transition: all 0.2s;"
                            @click="toggle"
                            title="إغلاق"
                            aria-label="إغلاق"
                        >
                            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>

                        <!-- Main Media Center Area -->
                        <div 
                            ref="mediaContainer" 
                            style="position: relative; flex: 1; display: flex; align-items: center; justify-content: center; overflow: hidden; background: #fff; padding: 16px; min-width: 0;"
                        >
                            <!-- Previous Arrow Button -->
                            <button
                                type="button"
                                style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); z-index: 40; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: rgba(0,0,0,0.4); color: #fff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.2s;"
                                v-if="attachments && attachments.length >= 2"
                                @click="navigate(currentIndex - 1)"
                                title="السابق"
                            >
                                <svg style="width:20px;height:20px;" class="rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>

                            <!-- Next Arrow Button -->
                            <button
                                type="button"
                                style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); z-index: 40; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: rgba(0,0,0,0.4); color: #fff; border: none; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.2s;"
                                v-if="attachments && attachments.length >= 2"
                                @click="navigate(currentIndex + 1)"
                                title="التالي"
                            >
                                <svg style="width:20px;height:20px;" class="rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>

                            <!-- Slides Container -->
                            <div style="position: relative; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; overflow: hidden;">
                                <template v-for="(attachment, index) in attachments" :key="index">
                                    <div
                                        v-show="currentIndex === index + 1"
                                        style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; overflow: hidden;"
                                    >
                                        <video 
                                            style="max-height: 100%; max-width: 100%; object-fit: contain; border-radius: 8px;"
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
                                                style="max-height: 100%; max-width: 100%; object-fit: contain; border-radius: 8px; user-select: none; cursor: pointer;"
                                                @click.stop="handleClick"
                                            />
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Right Sidebar for Thumbnails -->
                        <div style="flex-shrink: 0; width: 110px; display: flex; flex-direction: column; gap: 8px; overflow-y: auto; overflow-x: hidden; border-left: 1px solid #f3f4f6; background: rgba(249,250,251,0.9); padding: 12px 8px;">
                            <template v-for="(attachment, index) in attachments" :key="index">
                                <div
                                    style="position: relative; width: 90px; height: 90px; flex-shrink: 0; cursor: pointer; overflow: hidden; border-radius: 12px; transition: all 0.2s;"
                                    :style="{
                                        border: currentIndex === index + 1 ? '3px solid #060C3B' : '2px solid #e5e7eb',
                                        opacity: currentIndex === index + 1 ? '1' : '0.6',
                                        transform: currentIndex === index + 1 ? 'scale(1.05)' : 'scale(1)',
                                        boxShadow: currentIndex === index + 1 ? '0 2px 8px rgba(6,12,59,0.2)' : 'none',
                                    }"
                                    @click="navigate(index + 1)"
                                >
                                    <img
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        :src="attachment.url"
                                        v-if="attachment.type === 'image'"
                                        alt="thumbnail"
                                    />

                                    <video
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        :src="attachment.url"
                                        v-if="attachment.type === 'video'"
                                    />

                                    <div v-if="attachment.type === 'video'" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.3); color: #fff;">
                                        <svg style="width:20px;height:20px;" fill="currentColor" viewBox="0 0 24 24">
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
                    this.isZooming = ! this.isZooming;
                },
            },
        });
    </script>
@endpushonce