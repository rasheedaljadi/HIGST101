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

        {{-- Import form: streams live progress via SSE (no full page reload) --}}
        <div class="p-6 border border-gray-200 dark:border-gray-800 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 bg-white dark:bg-gray-900 flex flex-col gap-6">
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-1">
                    <label class="required block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        معرّف منتج AliExpress أو رابط المنتج
                    </label>

                    <input
                        type="text"
                        id="ae-identifier"
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none"
                        placeholder="مثال: 1005006789012345 أو https://www.aliexpress.com/item/1005006789012345.html"
                    />

                    <p id="ae-input-error" class="mt-1.5 hidden text-sm font-semibold text-rose-500 dark:text-rose-450"></p>
                </div>

                {{-- Optional Category Pre-Selection (Level 1 Main Category & Level 2 Subcategory) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-950 border border-gray-200 dark:border-gray-800">
                    <div class="flex flex-col gap-1.5">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            الفئة الرئيسية المستهدفة (اختياري)
                        </label>
                        <select
                            id="ae-main-category"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none cursor-pointer bg-white"
                        >
                            <option value="">— تحديد تلقائي ذكي (موصى به) —</option>
                            @if(isset($categories))
                                @foreach ($categories as $mainCat)
                                    <option value="{{ $mainCat->id }}">
                                        {{ $mainCat->translate(app()->getLocale())?->name ?? $mainCat->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5" id="ae-sub-category-wrapper">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            الفئة الفرعية (اختياري)
                        </label>
                        <select
                            id="ae-sub-category"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-1 focus:ring-amber-500 focus:outline-none cursor-pointer bg-white disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed"
                            disabled
                        >
                            <option value="">— كافة الفئات الفرعية التابعة —</option>
                        </select>
                    </div>

                    <div class="md:col-span-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 pt-1">
                        <span class="inline-block text-amber-500">💡</span>
                        <span>
                            <strong>ملاحظة:</strong> يمكنك ترك تحديد الفئة فارغاً، وسيقوم النظام بتصنيف المنتج تلقائياً بناءً على مواصفاته وبياناته.
                        </span>
                    </div>
                </div>

                <div class="flex items-center">
                    <button
                        type="button"
                        id="ae-import-btn"
                        class="primary-button py-2 px-6 focus:ring-1 focus:ring-amber-500 focus:outline-none hover:bg-amber-600 transition-all font-sans font-semibold text-sm"
                    >
                        استيراد المنتج
                    </button>
                </div>

                {{-- Progress panel (hidden until import starts) --}}
                <div id="ae-progress-panel" class="hidden flex-col gap-3 p-4 bg-gray-50 dark:bg-gray-950 border border-gray-150 dark:border-gray-850 rounded-lg animate-pulse">
                    <div class="h-3.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-850 border border-gray-300/30">
                        <div
                            id="ae-progress-bar"
                            class="h-3.5 rounded-full transition-all duration-500 ease-out bg-gradient-to-r from-amber-500 to-yellow-600"
                            style="width: 0%;"
                        ></div>
                    </div>

                    <div class="flex items-center justify-between">
                        <span id="ae-progress-message" class="text-sm font-medium text-gray-700 dark:text-gray-300 font-sans">
                            جارٍ التحضير...
                        </span>
                        <span id="ae-progress-percent" class="text-sm font-bold text-amber-600 dark:text-amber-500 font-sans">0%</span>
                    </div>

                    {{-- Step log --}}
                    <ul id="ae-step-log" class="flex flex-col gap-1.5 text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-800 pt-3 mt-1"></ul>
                </div>

                {{-- Result messages --}}
                <div id="ae-success" class="hidden rounded-lg border border-emerald-200 bg-emerald-50 dark:bg-emerald-950/30 p-4 text-emerald-600 dark:text-emerald-400 font-sans shadow-sm"></div>
                <div id="ae-error" class="hidden rounded-lg border border-red-200 bg-red-50 dark:bg-red-950/20 p-4 text-red-800 dark:text-red-400 font-sans shadow-sm"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const streamUrl = "{{ route('admin.dropshipping.import.stream') }}";
                let source = null;

                // Category children mapping
                const categoriesData = @json(
                    (isset($categories) ? $categories : collect())->mapWithKeys(function ($cat) {
                        return [$cat->id => $cat->children->map(function ($sub) {
                            return [
                                'id' => $sub->id,
                                'name' => $sub->translate(app()->getLocale())?->name ?? $sub->name,
                            ];
                        })];
                    })
                );

                function $(id) {
                    return document.getElementById(id);
                }

                function setupCategoryListeners() {
                    const mainSelect = $('ae-main-category');
                    const subSelect = $('ae-sub-category');

                    if (! mainSelect || ! subSelect) return;

                    mainSelect.addEventListener('change', function () {
                        const selectedMainId = this.value;
                        subSelect.innerHTML = '<option value="">— كافة الفئات الفرعية التابعة —</option>';

                        if (selectedMainId && categoriesData[selectedMainId] && categoriesData[selectedMainId].length > 0) {
                            categoriesData[selectedMainId].forEach(function (sub) {
                                const opt = document.createElement('option');
                                opt.value = sub.id;
                                opt.textContent = sub.name;
                                subSelect.appendChild(opt);
                            });
                            subSelect.disabled = false;
                        } else {
                            subSelect.disabled = true;
                        }
                    });
                }

                function getSelectedCategoryId() {
                    const subSelect = $('ae-sub-category');
                    const mainSelect = $('ae-main-category');

                    if (subSelect && subSelect.value) {
                        return subSelect.value;
                    }

                    if (mainSelect && mainSelect.value) {
                        return mainSelect.value;
                    }

                    return '';
                }

                function setProgress(percent, msg) {
                    const bar = $('ae-progress-bar');
                    const percentEl = $('ae-progress-percent');
                    const message = $('ae-progress-message');
                    if (bar) bar.style.width = percent + '%';
                    if (percentEl) percentEl.textContent = percent + '%';
                    if (msg && message) message.textContent = msg;
                }

                function addStep(msg) {
                    const stepLog = $('ae-step-log');
                    if (! stepLog) return;
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-2';
                    const tick = document.createElement('span');
                    tick.className = 'text-green-600 dark:text-green-400 font-semibold';
                    tick.textContent = '✓';
                    const txt = document.createElement('span');
                    txt.textContent = msg;
                    li.appendChild(tick);
                    li.appendChild(txt);
                    stepLog.appendChild(li);
                }

                function resetUi() {
                    const inputError = $('ae-input-error');
                    const successBox = $('ae-success');
                    const errorBox = $('ae-error');
                    const stepLog = $('ae-step-log');
                    const panel = $('ae-progress-panel');
                    if (inputError) { inputError.classList.add('hidden'); inputError.textContent = ''; }
                    if (successBox) { successBox.classList.add('hidden'); successBox.innerHTML = ''; }
                    if (errorBox) { errorBox.classList.add('hidden'); errorBox.textContent = ''; }
                    if (stepLog) stepLog.innerHTML = '';
                    if (panel) { panel.classList.remove('hidden'); panel.classList.add('flex'); }
                    setProgress(0, 'جارٍ التحضير...');
                }

                function setButtonBusy(busy) {
                    const btn = $('ae-import-btn');
                    const panel = $('ae-progress-panel');
                    if (! btn) return;
                    btn.disabled = busy;
                    btn.classList.toggle('opacity-50', busy);
                    btn.classList.toggle('cursor-not-allowed', busy);
                    if (panel) {
                        if (busy) {
                            panel.classList.add('animate-pulse');
                        } else {
                            panel.classList.remove('animate-pulse');
                        }
                    }
                }

                function finish() {
                    if (source) { source.close(); source = null; }
                    setButtonBusy(false);
                }

                function startImport() {
                    const input = $('ae-identifier');
                    const inputError = $('ae-input-error');
                    const identifier = input ? (input.value || '').trim() : '';

                    if (identifier === '') {
                        if (inputError) {
                            inputError.textContent = 'الرجاء إدخال معرف منتج AliExpress أو رابط المنتج.';
                            inputError.classList.remove('hidden');
                        }
                        return;
                    }

                    resetUi();
                    setButtonBusy(true);

                    const targetCategoryId = getSelectedCategoryId();
                    let url = streamUrl + '?identifier=' + encodeURIComponent(identifier);
                    if (targetCategoryId) {
                        url += '&category_id=' + encodeURIComponent(targetCategoryId);
                    }

                    source = new EventSource(url);

                    source.addEventListener('progress', function (e) {
                        const data = JSON.parse(e.data);
                        setProgress(data.percent, data.message);
                        if (data.message) addStep(data.message);
                    });

                    source.addEventListener('done', function (e) {
                        const data = JSON.parse(e.data);
                        setProgress(100, 'اكتمل الاستيراد');

                        // Clear the input field and reset focus so the user can easily paste a new URL/ID
                        const input = $('ae-identifier');
                        if (input) {
                            input.value = '';
                            input.focus();
                        }

                        const successBox = $('ae-success');
                        if (successBox) {
                            let html = 'تم الاستيراد بنجاح.';
                            if (data.edit_url) {
                                html += ' <a href="' + data.edit_url + '" class="underline font-semibold ml-1">عرض المنتج (#' + data.product_id + ')</a>';
                            }
                            successBox.innerHTML = html;
                            successBox.classList.remove('hidden');
                        }
                        finish();
                    });

                    source.addEventListener('error', function (e) {
                        const errorBox = $('ae-error');
                        if (e.data) {
                            const data = JSON.parse(e.data);
                            if (errorBox) errorBox.textContent = data.message || 'حدث خطأ أثناء الاستيراد.';
                        } else if (source && source.readyState === EventSource.CLOSED) {
                            if (errorBox) errorBox.textContent = 'انقطع الاتصال أثناء الاستيراد.';
                        } else {
                            return; // transient, allow retry
                        }
                        if (errorBox) errorBox.classList.remove('hidden');
                        finish();
                    });
                }

                // Initialize category listeners on page load
                setupCategoryListeners();

                // Event delegation on document survives Vue re-rendering the
                // #app subtree (which would drop element-bound listeners).
                document.addEventListener('click', function (e) {
                    const btn = e.target.closest && e.target.closest('#ae-import-btn');
                    if (btn) {
                        e.preventDefault();
                        startImport();
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' && e.target && e.target.id === 'ae-identifier') {
                        e.preventDefault();
                        startImport();
                    }
                });
            })();
        </script>
    @endpush
</x-admin::layouts>
