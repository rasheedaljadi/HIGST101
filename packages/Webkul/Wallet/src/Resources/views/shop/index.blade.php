<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        @lang('wallet::app.shop.wallet.title')
    </x-slot>

    <!-- Navigation Menu (Desktop) -->
    <div class="max-md:hidden">
        <x-shop::layouts.account.navigation />
    </div>

    <!-- Main Content Area -->
    <div class="flex-auto mx-4 max-md:mx-6 max-sm:mx-4">
        {{-- Header Section with Action Buttons --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <!-- Back Button for Mobile -->
                <a
                    class="grid md:hidden"
                    href="{{ route('shop.customers.account.index') }}"
                >
                    <span class="text-2xl icon-arrow-left rtl:icon-arrow-right"></span>
                </a>

                <h2 class="text-2xl font-medium max-md:text-xl max-sm:text-base ltr:ml-2.5 md:ltr:ml-0 rtl:mr-2.5 md:rtl:mr-0">
                    @lang('wallet::app.shop.wallet.title')
                </h2>
            </div>

            <div class="flex items-center gap-3">
                <a
                    href="{{ route('shop.wallet.topup.create') }}"
                    class="primary-button border-zinc-200 px-5 py-2.5 font-normal max-md:rounded-lg max-md:py-2 max-sm:py-1.5 max-sm:text-sm"
                >
                    + إيداع رصيد
                </a>

                <a
                    href="{{ route('shop.wallet.withdraw.create') }}"
                    class="secondary-button border-zinc-200 px-5 py-2.5 font-normal max-md:rounded-lg max-md:py-2 max-sm:py-1.5 max-sm:text-sm"
                >
                    طلب سحب
                </a>
            </div>
        </div>

        {{-- Wallet Summary Main Card --}}
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 max-md:mt-5 max-md:p-4">
            <!-- Top Balance Section -->
            <div class="flex items-center justify-between border-b border-zinc-100 pb-6 dark:border-gray-800">
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-gray-400">إجمالي رصيد المحفظة</p>
                    <h3 class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white max-md:text-2xl">
                        {{ $balances['total'] }}
                    </h3>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    حساب نشط 100%
                </span>
            </div>

            <!-- Sub-balances Grid -->
            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Available Balance -->
                <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-emerald-900 dark:text-emerald-200">السيولة المتاحة (قابل للسحب)</span>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">جاهز للاستخدام</span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                        {{ $balances['available'] }}
                    </p>
                    <p class="mt-1 text-xs text-emerald-600/80 dark:text-emerald-400/80">متاح للمشتريات أو طلبات السحب البنكي</p>
                </div>

                <!-- Held Balance -->
                <div class="rounded-xl border border-amber-100 bg-amber-50/50 p-4 dark:border-amber-900/40 dark:bg-amber-950/30">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-amber-900 dark:text-amber-200">الرصيد المحجوز</span>
                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400">قيد المعالجة</span>
                    </div>
                    <p class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-300">
                        {{ $balances['held'] }}
                    </p>
                    <p class="mt-1 text-xs text-amber-600/80 dark:text-amber-400/80">مبالغ قيد مراجعة طلبات السحب</p>
                </div>
            </div>
        </div>

        {{-- Deposit Requests Section --}}
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 max-md:mt-5 max-md:p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">طلبات إيداع الرصيد الأخيرة</h3>
                @if (isset($topups) && $topups->count())
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $topups->count() }} طلبات
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs font-medium text-zinc-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="py-3 px-4">رقم الطلب</th>
                            <th class="py-3 px-4">طريقة الدفع</th>
                            <th class="py-3 px-4">التاريخ</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4 text-left">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-800">
                        @forelse ($topups as $topup)
                            <tr class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-gray-800/60">
                                <td class="py-4 px-4 font-mono font-bold text-zinc-900 dark:text-white">#{{ $topup->id }}</td>
                                <td class="py-4 px-4 font-medium text-zinc-700 dark:text-gray-300">{{ $topup->payment_method_title }}</td>
                                <td class="py-4 px-4 text-xs text-zinc-500 dark:text-gray-400">{{ $topup->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-4 px-4">
                                    @if (in_array($topup->status, ['pending', 'pending_payment', 'under_review']))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                                            قيد المراجعة والانتظار
                                        </span>
                                    @elseif (in_array($topup->status, ['completed', 'payment_received']))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            مكتمل ومضاف للمحفظة
                                        </span>
                                    @elseif ($topup->status === 'failed')
                                        <div>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                مرفوض
                                            </span>
                                            @if ($topup->admin_notes)
                                                <p class="mt-1 text-xs text-rose-600 font-medium dark:text-rose-400">سبب الرفض: {{ $topup->admin_notes }}</p>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-semibold text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $topup->status_label }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-left font-bold text-emerald-600 dark:text-emerald-400">
                                    +{{ core()->formatBasePrice($topup->amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400">لا توجد طلبات إيداع رصيد مسجلة حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Withdrawal Requests Section --}}
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 max-md:mt-5 max-md:p-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">طلبات السحب الأخيرة</h3>
                @if (isset($withdrawals) && $withdrawals->count())
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $withdrawals->count() }} طلبات
                    </span>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs font-medium text-zinc-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="py-3 px-4">رقم الطلب</th>
                            <th class="py-3 px-4">طريقة / حساب السحب</th>
                            <th class="py-3 px-4">التاريخ</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4 text-left">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-800">
                        @forelse ($withdrawals as $withdrawal)
                            @php
                                $bankDetails = $withdrawal->bank_details ?? [];
                                if (is_string($bankDetails)) {
                                    try {
                                        $decrypted = decrypt($bankDetails);
                                        $bankDetails = is_array($decrypted) ? $decrypted : (json_decode($decrypted, true) ?: []);
                                    } catch (\Throwable $e) {
                                        $bankDetails = json_decode($bankDetails, true) ?: [];
                                    }
                                }
                                $methodTitle = $bankDetails['bank_name'] ?? $bankDetails['method'] ?? '—';
                                $accountName = $bankDetails['account_name'] ?? '';
                            @endphp
                            <tr class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-gray-800/60">
                                <td class="py-4 px-4 font-mono font-bold text-zinc-900 dark:text-white">#WD-{{ $withdrawal->id }}</td>
                                <td class="py-4 px-4 font-medium text-zinc-700 dark:text-gray-300">
                                    {{ $methodTitle }}
                                    @if ($accountName)
                                        <span class="block text-xs text-zinc-400 font-normal">{{ $accountName }}</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-xs text-zinc-500 dark:text-gray-400">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-4 px-4">
                                    @if ($withdrawal->status === 'pending')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800">
                                            <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                                            قيد المراجعة والانتظار
                                        </span>
                                    @elseif ($withdrawal->status === 'completed')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            مكتمل وتحول المبلغ
                                        </span>
                                    @elseif ($withdrawal->status === 'rejected')
                                        <div>
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-semibold text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                                مرفوض
                                            </span>
                                            @if ($withdrawal->rejection_reason)
                                                <p class="mt-1 text-xs text-rose-600 font-medium dark:text-rose-400">سبب الرفض: {{ $withdrawal->rejection_reason }}</p>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-50 px-2.5 py-0.5 text-xs font-semibold text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $withdrawal->status }}
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-left font-bold text-rose-600 dark:text-rose-400">
                                    -{{ core()->formatBasePrice($withdrawal->amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400">لا توجد طلبات سحب مسجلة حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Transactions Section --}}
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 max-md:mt-5 max-md:p-4">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-4">أحدث الحركات المالية</h3>

            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 text-xs font-medium text-zinc-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="py-3 px-4">رقم الحركة</th>
                            <th class="py-3 px-4">النوع</th>
                            <th class="py-3 px-4">التاريخ</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4 text-left">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-gray-800">
                        @forelse ($transactions as $tx)
                            <tr class="transition-colors hover:bg-zinc-50/60 dark:hover:bg-gray-800/60">
                                <td class="py-4 px-4 font-mono font-bold text-zinc-900 dark:text-white">#{{ $tx->id }}</td>
                                <td class="py-4 px-4 font-medium text-zinc-700 dark:text-gray-300">{{ $tx->type_label }}</td>
                                <td class="py-4 px-4 text-xs text-zinc-500 dark:text-gray-400">{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">مكتمل</span>
                                </td>
                                <td class="py-4 px-4 text-left font-bold {{ $tx->direction == 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $tx->direction == 'credit' ? '+' : '-' }}{{ core()->formatBasePrice($tx->amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400">لا توجد حركات مالية مسجلة في المحفظة حتى الآن.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-shop::layouts.account>
