<x-admin::layouts>
    <x-slot:title>
        معالجة طلب السحب {{ $withdrawal['id'] }}
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        {{-- Header Section --}}
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    معالجة طلب السحب {{ $withdrawal['id'] }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    مراجعة بيانات الحساب والتحقق من تفاصيل مستلم المبلغ وإتمام عملية السحب أو رفضها.
                </p>
            </div>
            <a
                href="{{ route('admin.wallet.withdrawals.index') }}"
                class="px-4 py-2 text-sm font-bold rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                ← العودة لطلبات السحب
            </a>
        </div>

        {{-- 2-Column Grid Layout --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Left Column: Information --}}
            <div class="flex flex-col gap-6">

                {{-- Request & Bank Details Card --}}
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
                        💳 بيانات العميل وحساب التحويل
                    </h2>

                    <div class="mt-4 divide-y divide-gray-100 text-sm dark:divide-gray-800">
                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">اسم العميل</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $withdrawal['customer'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">المبلغ المطلوب للسحب</span>
                            <span class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">{{ $withdrawal['amount'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">طريقة السحب والتحويل</span>
                            <span class="font-bold px-2.5 py-1 rounded bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200/60 text-xs">{{ $withdrawal['method'] }}</span>
                        </div>

                        <div class="flex justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">اسم صاحب الحساب / المستلم</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $withdrawal['account_name'] }}</span>
                        </div>

                        <div class="flex items-center justify-between py-3">
                            <span class="font-medium text-gray-500 dark:text-gray-400">رقم الحساب / المحفظة</span>
                            <div class="flex items-center gap-2">
                                <span class="rounded-lg bg-gray-100 px-3 py-1.5 font-mono text-sm font-extrabold tracking-widest text-gray-900 dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-700 select-all">
                                    {{ $withdrawal['account_number'] ?? $withdrawal['masked_iban'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Execution Forms --}}
            <div class="flex flex-col gap-6">

                {{-- Complete Withdrawal Card --}}
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-6 shadow-sm dark:border-emerald-800/40 dark:bg-emerald-950/20">
                    <h2 class="text-lg font-bold text-emerald-900 dark:text-emerald-300">
                        ✓ تأكيد تنفيذ السحب وتحويل المبلغ
                    </h2>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-400">
                        قم بإدخال رقم مرجع الحوالة البنكية / إشعار التحويل بعد إرسال المبلغ للعميل.
                    </p>

                    <form action="{{ route('admin.wallet.withdrawals.complete', $withdrawal['raw_id']) }}" method="POST" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
                                رقم مرجع التحويل / الإشعار <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="bank_reference_id"
                                required
                                placeholder="مثال: TR-998124589"
                                class="w-full rounded-lg border border-emerald-300 bg-white p-2.5 text-sm text-gray-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-emerald-700 dark:bg-gray-900 dark:text-white"
                            />
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-emerald-600 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 cursor-pointer"
                        >
                            تأكيد وإتمام السحب
                        </button>
                    </form>
                </div>

                {{-- Reject Withdrawal Card --}}
                <div class="rounded-xl border border-red-200 bg-red-50/50 p-6 shadow-sm dark:border-red-800/40 dark:bg-red-950/20">
                    <h2 class="text-lg font-bold text-red-900 dark:text-red-300">
                        ✕ رفض طلب السحب
                    </h2>
                    <p class="mt-1 text-xs text-red-700 dark:text-red-400">
                        قم بتوضيح سبب الرفض للعميل. سيتم إلغاء حجز المبلغ وإعادته تلقائياً لرصيد محفظة العميل المتاح.
                    </p>

                    <form action="{{ route('admin.wallet.withdrawals.reject', $withdrawal['raw_id']) }}" method="POST" class="mt-4 flex flex-col gap-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-red-800 dark:text-red-300">
                                سبب الرفض <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                name="rejection_reason"
                                required
                                rows="3"
                                placeholder="اكتب سبب رفض طلب السحب لتوضيحه للعميل..."
                                class="w-full rounded-lg border border-red-300 bg-white p-2.5 text-sm text-gray-800 focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-500/30 dark:border-red-700 dark:bg-gray-900 dark:text-white"
                            ></textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-lg bg-red-600 py-3 text-sm font-bold text-white shadow-sm transition-all hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/50 cursor-pointer"
                        >
                            تأكيد رفض طلب السحب
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-admin::layouts>
