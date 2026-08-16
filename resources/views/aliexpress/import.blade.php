<x-admin::layouts>
    {{-- Page heading (Req 1.3) --}}
    <x-slot:title>
        استيراد المنتجات
    </x-slot>

    <div class="flex flex-col gap-6 pt-3 px-2 sm:px-4 lg:pt-3 lg:px-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white font-sans">
                استيراد منتج من AliExpress
            </h1>
        </div>

        {{-- Vue Component for AliExpress product importer with reactive subcategories --}}
        <v-aliexpress-import
            :initial-categories='@json($formattedCategories ?? [])'
            stream-url="{{ route('admin.dropshipping.import.stream') }}"
        >
        </v-aliexpress-import>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-aliexpress-import-template"
        >
            <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
                <div class="flex flex-col gap-5">
                    {{-- Identifier Input --}}
                    <div class="flex flex-col gap-1">
                        <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            معرّف منتج AliExpress أو رابط المنتج
                        </label>

                        <input
                            type="text"
                            id="ae-identifier"
                            v-model="identifier"
                            @keydown.enter.prevent="startImport"
                            class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                            placeholder="مثال: 1005006789012345 أو https://www.aliexpress.com/item/1005006789012345.html"
                            :disabled="isBusy"
                        />

                        <p id="ae-input-error" class="mt-1.5 text-sm font-semibold text-rose-500 dark:text-rose-450" v-if="inputError" v-text="inputError"></p>
                    </div>

                    {{-- Category Pre-Selection (Main Category & Subcategory) --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800">
                        {{-- Main Category --}}
                        <div class="flex flex-col gap-1.5">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                الفئة الرئيسية المستهدفة (اختياري)
                            </label>
                            <select
                                id="ae-main-category"
                                v-model="selectedMainCategoryId"
                                @change="onMainCategoryChange"
                                :disabled="isBusy"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none cursor-pointer bg-white"
                            >
                                <option value="">— تحديد تلقائي ذكي (موصى به) —</option>
                                <option v-for="mainCat in initialCategories" :key="'main_cat_' + mainCat.id" :value="mainCat.id">
                                    @{{ mainCat.name }}
                                </option>
                            </select>
                        </div>

                        {{-- Subcategory --}}
                        <div class="flex flex-col gap-1.5" id="ae-sub-category-wrapper">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                                الفئة الفرعية (اختياري)
                            </label>
                            <select
                                id="ae-sub-category"
                                v-model="selectedSubCategoryId"
                                :disabled="isBusy || !availableSubcategories.length"
                                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none cursor-pointer bg-white disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed"
                            >
                                <option value="">— كافة الفئات الفرعية التابعة —</option>
                                <option v-for="sub in availableSubcategories" :key="'sub_cat_' + sub.id" :value="sub.id">
                                    @{{ sub.name }}
                                </option>
                            </select>
                        </div>

                        <div class="md:col-span-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 pt-1">
                            <span class="inline-block text-amber-500">💡</span>
                            <span>
                                <strong>ملاحظة:</strong> يمكنك ترك تحديد الفئة فارغاً، وسيقوم النظام بتصنيف المنتج تلقائياً بناءً على مواصفاته وبياناته.
                            </span>
                        </div>
                    </div>

                    {{-- Import Button --}}
                    <div class="flex items-center">
                        <button
                            type="button"
                            id="ae-import-btn"
                            @click="startImport"
                            :disabled="isBusy"
                            class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm"
                            :class="{'opacity-50 cursor-not-allowed': isBusy}"
                        >
                            <span v-if="!isBusy">استيراد المنتج</span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                جارٍ الاستيراد...
                            </span>
                        </button>
                    </div>

                    {{-- Progress Panel --}}
                    <div id="ae-progress-panel" v-if="showProgress" class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-gray-950 border border-gray-150 dark:border-gray-850 rounded-lg" :class="{'animate-pulse': isBusy}">
                        <div class="h-3.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-850 border border-gray-300/30">
                            <div
                                id="ae-progress-bar"
                                class="h-3.5 rounded-full transition-all duration-500 ease-out bg-gradient-to-r from-amber-500 to-yellow-600"
                                :style="{ width: progressPercent + '%' }"
                            ></div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span id="ae-progress-message" class="text-sm font-medium text-gray-700 dark:text-gray-300 font-sans" v-text="progressMessage"></span>
                            <span id="ae-progress-percent" class="text-sm font-bold text-amber-600 dark:text-amber-500 font-sans">@{{ progressPercent }}%</span>
                        </div>

                        {{-- Step Log --}}
                        <ul id="ae-step-log" class="flex flex-col gap-1.5 text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800 pt-3 mt-1" v-if="stepLogs.length">
                            <li v-for="(log, idx) in stepLogs" :key="'log_' + idx" class="flex items-center gap-2">
                                <span class="text-green-600 dark:text-green-400 font-semibold">✓</span>
                                <span v-text="log"></span>
                            </li>
                        </ul>
                    </div>

                    {{-- Success Message --}}
                    <div id="ae-success" v-if="successData" class="rounded-lg border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 p-4 text-emerald-600 dark:text-emerald-400 font-sans shadow-sm">
                        <span>تم الاستيراد بنجاح.</span>
                        <a v-if="successData.edit_url" :href="successData.edit_url" class="underline font-semibold ml-1">
                            عرض المنتج (#@{{ successData.product_id }})
                        </a>
                    </div>

                    {{-- Error Message --}}
                    <div id="ae-error" v-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20 p-4 text-red-800 dark:text-red-400 font-sans shadow-sm" v-text="errorMessage"></div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-aliexpress-import', {
                template: '#v-aliexpress-import-template',

                props: {
                    initialCategories: {
                        type: Array,
                        default: () => [],
                    },
                    streamUrl: {
                        type: String,
                        required: true,
                    },
                },

                data() {
                    return {
                        identifier: '',
                        selectedMainCategoryId: '',
                        selectedSubCategoryId: '',
                        inputError: '',
                        isBusy: false,
                        showProgress: false,
                        progressPercent: 0,
                        progressMessage: 'جارٍ التحضير...',
                        stepLogs: [],
                        successData: null,
                        errorMessage: '',
                        eventSource: null,
                    };
                },

                computed: {
                    availableSubcategories() {
                        if (! this.selectedMainCategoryId) {
                            return [];
                        }

                        const mainCat = this.initialCategories.find(c => String(c.id) === String(this.selectedMainCategoryId));
                        return (mainCat && Array.isArray(mainCat.children)) ? mainCat.children : [];
                    },
                },

                methods: {
                    onMainCategoryChange() {
                        this.selectedSubCategoryId = '';
                    },

                    getTargetCategoryId() {
                        if (this.selectedSubCategoryId) {
                            return this.selectedSubCategoryId;
                        }

                        if (this.selectedMainCategoryId) {
                            return this.selectedMainCategoryId;
                        }

                        return '';
                    },

                    startImport() {
                        const trimmedId = (this.identifier || '').trim();

                        if (! trimmedId) {
                            this.inputError = 'الرجاء إدخال معرف منتج AliExpress أو رابط المنتج.';
                            return;
                        }

                        this.inputError = '';
                        this.successData = null;
                        this.errorMessage = '';
                        this.stepLogs = [];
                        this.progressPercent = 0;
                        this.progressMessage = 'جارٍ التحضير...';
                        this.showProgress = true;
                        this.isBusy = true;

                        if (this.eventSource) {
                            this.eventSource.close();
                            this.eventSource = null;
                        }

                        const targetCatId = this.getTargetCategoryId();
                        let url = this.streamUrl + '?identifier=' + encodeURIComponent(trimmedId);
                        if (targetCatId) {
                            url += '&category_id=' + encodeURIComponent(targetCatId);
                        }

                        this.eventSource = new EventSource(url);

                        this.eventSource.addEventListener('progress', (e) => {
                            try {
                                const data = JSON.parse(e.data);
                                this.progressPercent = data.percent || 0;
                                this.progressMessage = data.message || '';
                                if (data.message && ! this.stepLogs.includes(data.message)) {
                                    this.stepLogs.push(data.message);
                                }
                            } catch (err) {
                                console.error('SSE progress parse error:', err);
                            }
                        });

                        this.eventSource.addEventListener('done', (e) => {
                            try {
                                const data = JSON.parse(e.data);
                                this.progressPercent = 100;
                                this.progressMessage = 'اكتمل الاستيراد';
                                this.successData = data;
                                this.identifier = '';
                            } catch (err) {
                                console.error('SSE done parse error:', err);
                            }
                            this.finish();
                        });

                        this.eventSource.addEventListener('error', (e) => {
                            try {
                                if (e.data) {
                                    const data = JSON.parse(e.data);
                                    this.errorMessage = data.message || 'حدث خطأ أثناء الاستيراد.';
                                } else if (this.eventSource && this.eventSource.readyState === EventSource.CLOSED) {
                                    this.errorMessage = 'انقطع الاتصال أثناء الاستيراد.';
                                }
                            } catch (err) {
                                this.errorMessage = 'حدث خطأ غير متوقع أثناء الاستيراد.';
                            }
                            this.finish();
                        });
                    },

                    finish() {
                        if (this.eventSource) {
                            this.eventSource.close();
                            this.eventSource = null;
                        }
                        this.isBusy = false;
                    },
                },

                beforeUnmount() {
                    this.finish();
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>
