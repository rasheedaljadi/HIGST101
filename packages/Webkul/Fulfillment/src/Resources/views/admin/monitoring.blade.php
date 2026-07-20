<x-admin::layouts>
    <x-slot:title>
        مراقبة العمليات والتحمل
    </x-slot>

    <div class="flex flex-col gap-6 pt-3 px-2 sm:px-4 lg:pt-3 lg:px-4">
        {{-- Header Section --}}
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white font-sans">
                    مراقبة العمليات والتحمل (Resilience Monitoring)
                </h1>
                <p class="text-sm text-gray-550 dark:text-gray-400 mt-1 font-sans">
                    تتبع حالة قواطع الاتصال، معدلات الاستهلاك للـ API، وسجلات الأخطاء والاتصالات الحية مع AliExpress.
                </p>
            </div>
        </div>

        {{-- Inline Success Alert --}}
        @if(session()->has('success'))
            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-250 text-emerald-600 dark:text-emerald-405 rounded-lg text-sm font-semibold font-sans" id="inline-success-alert">
                {{ session('success') }}
            </div>
        @endif

        {{-- Resilience Dashboard Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Circuit Breaker Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 font-sans">قاطع دورة الاتصالات (Circuit Breaker)</span>
                        <span class="text-xs text-gray-400 mt-1 font-sans">منع الانهيار التتابعي وتجنب حظر الحساب</span>
                    </div>
                    @if($circuitState === 'OPEN')
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 animate-pulse font-sans">مفتوح (OPEN)</span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 font-sans">مغلق (CLOSED)</span>
                    @endif
                </div>

                <div class="flex flex-col gap-2 mt-2">
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 font-sans">
                        <span>معدل الأخطاء المتتالية:</span>
                        <span class="font-bold font-mono">{{ $failures }} / 5</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300 {{ $circuitState === 'OPEN' ? 'bg-rose-600' : 'bg-amber-500' }}" style="width: {{ min(($failures / 5) * 100, 100) }}%"></div>
                    </div>
                </div>

                <div class="border-t dark:border-gray-800 pt-4 flex items-center justify-between mt-auto">
                    <span class="text-[11px] text-gray-400 font-sans">
                        @if($circuitState === 'OPEN')
                            <span class="text-rose-600 font-semibold">حركة المرور متوقفة مؤقتاً لتجنب الحظر</span>
                        @else
                            <span class="text-emerald-600 font-semibold">الاتصال سليم ويعمل بكفاءة</span>
                        @endif
                    </span>
                    
                    <form action="{{ route('admin.dropshipping.monitoring.reset-circuit') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" id="btn-reset-circuit" class="px-3 py-1.5 bg-gray-150 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-750 text-gray-800 dark:text-white rounded text-xs font-bold font-sans transition">
                            إعادة ضبط القاطع
                        </button>
                    </form>
                </div>
            </div>

            {{-- API Rate Limiting Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 font-sans">معدل استهلاك الـ API (Rate Limits)</span>
                        <span class="text-xs text-gray-400 mt-1 font-sans">الاستهلاك اللحظي خلال الدقيقة الحالية</span>
                    </div>
                    @php
                        $ratePercent = min(($apiCalls / 1000) * 100, 100);
                        $rateBadgeClass = $apiCalls > 800 ? 'bg-rose-100 text-rose-800 border-rose-200' : ($apiCalls > 500 ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200');
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border font-sans {{ $rateBadgeClass }}">
                        {{ $apiCalls > 800 ? 'ضغط مرتفع' : 'مستقر' }}
                    </span>
                </div>

                <div class="flex flex-col gap-2 mt-2">
                    <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 font-sans">
                        <span>الاستدعاءات الحالية:</span>
                        <span class="font-bold font-mono">{{ $apiCalls }} / 1000 req/min</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300 {{ $apiCalls > 800 ? 'bg-rose-600' : ($apiCalls > 500 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $ratePercent }}%"></div>
                    </div>
                </div>

                <div class="border-t dark:border-gray-800 pt-4 mt-auto text-[11px] text-gray-400 font-sans">
                    يتوفر حد أقصى بمقدار <strong>1000 طلب في الدقيقة</strong> كحماية ذاتية.
                </div>
            </div>

            {{-- Queue Backlog Card --}}
            <div class="p-6 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm flex flex-col gap-4">
                <div class="flex justify-between items-start">
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 font-sans">طابور مهام التوريد المعلقة (Queue Backlog)</span>
                        <span class="text-xs text-gray-400 mt-1 font-sans">عدد المهام قيد المعالجة في الخلفية</span>
                    </div>
                    @php
                        $backlogBadgeClass = $queueBacklog > 50 ? 'bg-rose-100 text-rose-800 border-rose-200' : ($queueBacklog > 10 ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200');
                    @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border font-sans {{ $backlogBadgeClass }}">
                        @if($queueBacklog > 50) متأخر @elseif($queueBacklog > 10) ضغط متوسط @else سليم @endif
                    </span>
                </div>

                <div class="flex flex-col gap-1 mt-2">
                    <span class="text-3xl font-bold text-gray-800 dark:text-white font-mono">{{ $queueBacklog }}</span>
                    <span class="text-[11px] text-gray-400 font-sans">وظائف (Jobs) معلقة للتنفيذ بانتظار العمال (Workers)</span>
                </div>

                <div class="border-t dark:border-gray-800 pt-4 mt-auto text-[11px] text-gray-400 font-sans">
                    يتم تنشيط عمال الخلفية عبر cron لتصفية طابور المهام بانتظام.
                </div>
            </div>
        </div>

        {{-- Live API Call Logs --}}
        <div class="flex flex-col gap-4">
            <h2 class="text-lg font-bold text-gray-800 dark:text-white font-sans">سجل استدعاءات الـ API الخارجية (External API Logs)</h2>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-850 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-right text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-800 text-gray-650 dark:text-gray-400 font-bold font-sans">
                                <th class="p-4 w-16">المعرف</th>
                                <th class="p-4">الموفر</th>
                                <th class="p-4">النهاية (Endpoint)</th>
                                <th class="p-4 text-center">الطريقة</th>
                                <th class="p-4 text-center">حالة الاستجابة</th>
                                <th class="p-4 text-center">زمن الاستجابة</th>
                                <th class="p-4 text-center">التاريخ</th>
                                <th class="p-4 text-center">التفاصيل</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-850">
                            @forelse($apiLogs as $log)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-850/50 transition-all duration-200 font-sans">
                                    <td class="p-4 font-mono text-xs text-gray-500">{{ $log->id }}</td>
                                    <td class="p-4 font-semibold capitalize">{{ $log->provider }}</td>
                                    <td class="p-4 font-mono text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate" title="{{ $log->endpoint }}">{{ $log->endpoint }}</td>
                                    <td class="p-4 text-center font-mono text-xs font-bold">{{ $log->method }}</td>
                                    <td class="p-4 text-center">
                                        @if($log->status_code >= 200 && $log->status_code < 300)
                                            <span class="inline-flex items-center rounded bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ $log->status_code }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600 dark:text-rose-400 font-mono">{{ $log->status_code ?: 'Error' }}</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-center font-mono text-xs text-gray-700 dark:text-gray-300">
                                        {{ $log->latency_ms ? number_format($log->latency_ms, 1) . ' ms' : '-' }}
                                    </td>
                                    <td class="p-4 text-center text-xs text-gray-500 font-mono">{{ $log->created_at }}</td>
                                    <td class="p-4 text-center">
                                        <button 
                                            type="button" 
                                            class="btn-view-payload text-blue-600 hover:underline text-xs font-bold font-sans"
                                            data-request="{{ $log->request_payload }}"
                                            data-response="{{ $log->response_payload }}"
                                            data-error="{{ $log->error_message }}"
                                            data-id="{{ $log->id }}"
                                        >
                                            عرض الحمولة (Payload)
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-12 text-center text-gray-500 dark:text-gray-400 font-sans">
                                        لا توجد سجلات استدعاء للـ API حالياً.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($apiLogs->hasPages())
                    <div class="p-4 border-t border-gray-200 dark:border-gray-850">
                        {!! $apiLogs->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Payload Details Modal --}}
    <div id="payload-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-lg shadow-xl w-full max-w-4xl p-6 flex flex-col gap-4">
            <div class="flex items-center justify-between border-b dark:border-gray-800 pb-3">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white font-sans">تفاصيل حمولة الـ API (استدعاء #<span id="modal-log-id"></span>)</h3>
                <span class="icon-cancel text-xl cursor-pointer text-gray-400 hover:text-gray-600" id="close-modal-btn"></span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[500px] overflow-y-auto pr-1">
                {{-- Request Payload --}}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 font-sans">حملة الطلب المرسل (Request Payload)</span>
                    <pre class="bg-gray-50 dark:bg-gray-850 border dark:border-gray-800/80 rounded p-4 text-xs font-mono overflow-x-auto text-gray-800 dark:text-gray-200" id="modal-request-payload"></pre>
                </div>

                {{-- Response Payload --}}
                <div class="flex flex-col gap-2">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400 font-sans">حملة الاستجابة المستلمة (Response Payload)</span>
                    <pre class="bg-gray-50 dark:bg-gray-850 border dark:border-gray-800/80 rounded p-4 text-xs font-mono overflow-x-auto text-gray-800 dark:text-gray-200" id="modal-response-payload"></pre>
                </div>
            </div>

            {{-- Error Section --}}
            <div id="modal-error-section" class="hidden flex-col gap-2 border-t dark:border-gray-800 pt-3">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-450 font-sans">تفاصيل الاستثناء والخطأ (Error Stack / Message)</span>
                <p class="bg-rose-50 dark:bg-rose-950/20 text-rose-800 dark:text-rose-400 border border-rose-200 dark:border-rose-900 rounded p-3 text-xs font-mono whitespace-pre-wrap" id="modal-error-message"></p>
            </div>

            <div class="flex justify-end gap-3 border-t dark:border-gray-800 pt-4">
                <button type="button" id="close-modal-footer-btn" class="px-4 py-2 bg-gray-150 dark:bg-gray-800 text-gray-800 dark:text-white rounded text-sm transition font-sans">إغلاق</button>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                // Modal handlers
                const modal = document.getElementById('payload-modal');
                const closeBtn = document.getElementById('close-modal-btn');
                const closeFooterBtn = document.getElementById('close-modal-footer-btn');

                document.addEventListener('click', function(e) {
                    const btn = e.target.closest('.btn-view-payload');
                    if (btn) {
                        e.preventDefault();
                        const id = btn.getAttribute('data-id');
                        const reqRaw = btn.getAttribute('data-request');
                        const resRaw = btn.getAttribute('data-response');
                        const errorMsg = btn.getAttribute('data-error');

                        document.getElementById('modal-log-id').innerText = id;

                        // Formatting JSON
                        try {
                            const reqObj = JSON.parse(reqRaw);
                            document.getElementById('modal-request-payload').innerText = JSON.stringify(reqObj, null, 2);
                        } catch(e) {
                            document.getElementById('modal-request-payload').innerText = reqRaw || 'Empty';
                        }

                        try {
                            const resObj = JSON.parse(resRaw);
                            document.getElementById('modal-response-payload').innerText = JSON.stringify(resObj, null, 2);
                        } catch(e) {
                            document.getElementById('modal-response-payload').innerText = resRaw || 'Empty';
                        }

                        // Error section display toggle
                        const errSec = document.getElementById('modal-error-section');
                        if (errorMsg && errorMsg.trim() !== '') {
                            errSec.classList.remove('hidden');
                            errSec.classList.add('flex');
                            document.getElementById('modal-error-message').innerText = errorMsg;
                        } else {
                            errSec.classList.remove('flex');
                            errSec.classList.add('hidden');
                        }

                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                    }
                });

                function closeModal() {
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }

                closeBtn.addEventListener('click', closeModal);
                closeFooterBtn.addEventListener('click', closeModal);
            })();
        </script>
    @endpush
</x-admin::layouts>
