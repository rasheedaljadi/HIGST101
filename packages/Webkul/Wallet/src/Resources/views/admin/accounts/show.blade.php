<x-admin::layouts>
    <x-slot:title>
        تفاصيل محفظة العميل: {{ $customer['name'] }}
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        {{-- Header Section (Centered Clean Proposed Layout) --}}
        <div class="flex flex-col items-center justify-center text-center gap-3 py-6 px-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-sm relative overflow-hidden">
            {{-- Status Badge (Top Corner) --}}
            <div class="absolute top-4 right-4 sm:right-6">
                @if (strtolower($customer['status']) === 'active')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        نشط
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                        مجمد
                    </span>
                @endif
            </div>

            {{-- Title & Email --}}
            <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-2">
                تفاصيل محفظة العميل: {{ $customer['name'] }}
            </h1>

            <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400 font-mono">
                <span>{{ $customer['email'] }}</span>
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText('{{ $customer['email'] }}'); alert('تم نسخ البريد الإلكتروني!')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-1"
                    title="نسخ البريد الإلكتروني"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 012-2v-8a2 2 0 01-2-2h-8a2 2 0 01-2 2v8a2 2 0 012 2z"></path></svg>
                </button>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center justify-center gap-3.5 mt-2 flex-wrap">
                {{-- Adjustment Modal Button --}}
                <button
                    type="button"
                    style="background-color: #1d4ed8 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 14px !important; padding: 10px 22px !important; border-radius: 12px !important; border: 1px solid #1e40af !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 4px 6px -1px rgba(29,78,216,0.2) !important;"
                    @click="$refs['adjustBalanceModal'].open()"
                >
                    <span style="color: #ffffff !important; font-weight: 700 !important;">تعديل الرصيد يدويًا +</span>
                </button>

                @if (strtolower($customer['status']) === 'active')
                    {{-- Suspend Modal Button --}}
                    <button
                        type="button"
                        style="background-color: #dc2626 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 14px !important; padding: 10px 22px !important; border-radius: 12px !important; border: 1px solid #b91c1c !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 4px 6px -1px rgba(220,38,38,0.2) !important;"
                        @click="$refs['suspendWalletModal'].open()"
                    >
                        <span style="color: #ffffff !important; font-weight: 700 !important;">تجميد المحفظة ❄️</span>
                    </button>
                @else
                    {{-- Reactivate Modal Button --}}
                    <button
                        type="button"
                        style="background-color: #10b981 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 14px !important; padding: 10px 22px !important; border-radius: 12px !important; border: 1px solid #059669 !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 4px 6px -1px rgba(16,185,129,0.2) !important;"
                        @click="$refs['reactivateWalletModal'].open()"
                    >
                        <span style="color: #ffffff !important; font-weight: 700 !important;">إعادة تنشيط المحفظة ⚡</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 3-Column Balance Cards Grid (Proposed Wave Design) --}}
        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <x-wallet::stat-card
                :title="trans('إجمالي الرصيد')"
                subtitle="إجمالي الأموال في المحفظة"
                :value="core()->formatBasePrice($balances['total'])"
                icon="wallet-blue"
                colorClass="text-blue-600 dark:text-blue-400"
                bgCircleClass="bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400"
                waveColor="#3b82f6"
            />

            <x-wallet::stat-card
                :title="trans('الرصيد المتاح')"
                subtitle="المبلغ المتاح للاستخدام"
                :value="core()->formatBasePrice($balances['available'])"
                icon="wallet-green"
                colorClass="text-emerald-600 dark:text-emerald-400"
                bgCircleClass="bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400"
                waveColor="#10b981"
            />

            <x-wallet::stat-card
                :title="trans('الرصيد المحجوز')"
                subtitle="المبلغ المحجوز مؤقتًا"
                :value="core()->formatBasePrice($balances['held'])"
                icon="lock-orange"
                colorClass="text-amber-600 dark:text-amber-400"
                bgCircleClass="bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400"
                waveColor="#f59e0b"
            />
        </div>

        {{-- Interactive Filterable Transactions Table Section --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        سجل الحركات والعمليات المالية (التاريخ المالي)
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        جدول تفصيلي شامل يغطي كل بيانات الحركات المالية لمهمة التدقيق والمحاسبة.
                    </p>
                </div>

                {{-- Filter & Search Controls --}}
                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Search Input --}}
                    <div class="relative min-w-[240px]">
                        <input
                            type="text"
                            id="walletTxSearchInput"
                            oninput="filterWalletTxTable()"
                            placeholder="بحث (رقم الحركة، نوع، تفاصيل)..."
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 py-2 px-3 text-xs focus:border-blue-500 focus:outline-none dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    {{-- Filter Dropdown --}}
                    <select
                        id="walletTxFilterSelect"
                        onchange="filterWalletTxTable()"
                        class="rounded-xl border border-gray-300 dark:border-gray-700 py-2 px-3 text-xs focus:border-blue-500 focus:outline-none dark:bg-gray-800 dark:text-white font-bold"
                    >
                        <option value="all">جميع الحركات</option>
                        <option value="credit">الإيداعات فقط (+)</option>
                        <option value="debit">الخصومات فقط (-)</option>
                    </select>

                    <span class="rounded-lg bg-gray-100 dark:bg-gray-800 px-3 py-2 text-xs font-bold text-gray-600 dark:text-gray-300">
                        العدد: <span id="walletTxCountDisplay">{{ count($transactions) }}</span>
                    </span>
                </div>
            </div>

            {{-- Transactions Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/80 text-gray-700 dark:border-gray-800 dark:bg-gray-800/60 dark:text-gray-200 font-bold">
                            <th class="py-3.5 px-4 rounded-r-xl">رقم الحركة</th>
                            <th class="py-3.5 px-4">نوع الحركة</th>
                            <th class="py-3.5 px-4">التفاصيل والبيان المالي</th>
                            <th class="py-3.5 px-4">الرصيد التراكمي</th>
                            <th class="py-3.5 px-4">التاريخ والوقت</th>
                            <th class="py-3.5 px-4">الاتجاه</th>
                            <th class="py-3.5 px-4 text-left rounded-l-xl">المبلغ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($transactions as $tx)
                            <tr
                                class="wallet-tx-row transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/60"
                                data-tx-direction="{{ $tx['direction'] }}"
                                data-tx-search="{{ mb_strtolower($tx['id'].' '.$tx['type'].' '.$tx['desc'].' '.$tx['date']) }}"
                            >
                                <td class="py-4 px-4 font-mono font-bold text-gray-900 dark:text-white">
                                    #{{ $tx['id'] }}
                                </td>
                                <td class="py-4 px-4 font-bold text-gray-800 dark:text-gray-200">
                                    {{ $tx['type'] }}
                                </td>
                                <td class="py-4 px-4 text-gray-600 dark:text-gray-300 font-medium">
                                    {{ $tx['desc'] }}
                                </td>
                                <td class="py-4 px-4 font-mono font-bold text-gray-700 dark:text-gray-300">
                                    {{ $tx['running_balance_formatted'] }}
                                </td>
                                <td class="py-4 px-4 text-gray-500 dark:text-gray-400 font-mono text-[11px]">
                                    {{ $tx['date'] }}
                                </td>
                                <td class="py-4 px-4">
                                    @if ($tx['direction'] === 'credit')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            إيداع (+)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-0.5 text-[11px] font-bold text-rose-700 border border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            خصم (-)
                                        </span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-left font-mono font-extrabold text-sm {{ $tx['direction'] === 'credit' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $tx['amount_formatted'] }}
                                </td>
                            </tr>
                        @empty
                            <tr id="walletTxNoRecords">
                                <td colspan="7" class="py-10 text-center text-gray-400 dark:text-gray-500 font-medium">
                                    لا توجد حركات مالية مسجلة في المحفظة حتى الآن.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <script>
            function filterWalletTxTable() {
                const q = (document.getElementById('walletTxSearchInput')?.value || '').toLowerCase().trim();
                const type = document.getElementById('walletTxFilterSelect')?.value || 'all';
                const rows = document.querySelectorAll('.wallet-tx-row');
                let visibleCount = 0;

                rows.forEach(row => {
                    const searchData = row.getAttribute('data-tx-search') || '';
                    const direction = row.getAttribute('data-tx-direction') || '';
                    
                    const matchesSearch = !q || searchData.includes(q);
                    const matchesFilter = type === 'all' || (type === 'credit' && direction === 'credit') || (type === 'debit' && direction === 'debit');

                    if (matchesSearch && matchesFilter) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                const countEl = document.getElementById('walletTxCountDisplay');
                if (countEl) countEl.textContent = visibleCount;
            }
        </script>

        {{-- Modal 1: Adjust Balance Modal --}}
        <x-admin::modal ref="adjustBalanceModal">
            <x-slot:header>
                <p class="text-lg font-bold text-gray-800 dark:text-white">
                    تعديل رصيد المحفظة يدويًا
                </p>
            </x-slot:header>

            <x-slot:content>
                <form
                    method="POST"
                    action="{{ route('admin.wallet.accounts.adjust', $wallet->id) }}"
                    id="adjustBalanceForm_{{ $wallet->id }}"
                    class="px-4 py-2 flex flex-col gap-4"
                >
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                            نوع التعديل *
                        </label>
                        <select
                            name="direction"
                            required
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-2.5 text-sm dark:bg-gray-800 dark:text-white"
                        >
                            <option value="credit">إضافة رصيد (إيداع +)</option>
                            <option value="debit">خصم رصيد (سحب -)</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                            المبلغ *
                        </label>
                        <input
                            type="number"
                            name="amount"
                            step="0.01"
                            min="0.01"
                            required
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-2.5 text-sm dark:bg-gray-800 dark:text-white"
                            placeholder="أدخل المبلغ بالدولار..."
                        />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                            سبب التعديل / ملاحظة الإدارة *
                        </label>
                        <textarea
                            name="reason"
                            required
                            rows="3"
                            class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-2.5 text-sm dark:bg-gray-800 dark:text-white"
                            placeholder="توضيح سبب تعديل الرصيد..."
                        ></textarea>
                    </div>
                </form>
            </x-slot:content>

            <x-slot:footer>
                <div class="flex items-center gap-2.5">
                    <button
                        type="submit"
                        form="adjustBalanceForm_{{ $wallet->id }}"
                        style="background-color: #0b2545 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 13px !important; padding: 10px 18px !important; border-radius: 12px !important; border: 1px solid #134074 !important; cursor: pointer !important;"
                    >
                        <span style="color: #ffffff !important; font-weight: 700 !important;">حفظ التعديل</span>
                    </button>

                    <button
                        type="button"
                        class="transparent-button"
                        @click="$refs['adjustBalanceModal'].close()"
                    >
                        إلغاء
                    </button>
                </div>
            </x-slot:footer>
        </x-admin::modal>

        {{-- Modal 2: Suspend Wallet Modal --}}
        @if (strtolower($customer['status']) === 'active')
            <x-admin::modal ref="suspendWalletModal">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        تجميد محفظة العميل
                    </p>
                </x-slot:header>

                <x-slot:content>
                    <form
                        method="POST"
                        action="{{ route('admin.wallet.accounts.suspend', $wallet->id) }}"
                        id="suspendWalletForm_{{ $wallet->id }}"
                        class="px-4 py-2 flex flex-col gap-4"
                    >
                        @csrf

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            هل أنت متأكد من رغبتك في تجميد هذه المحفظة؟ سيتم منع العميل من إجراء الشراء أو طلبات السحب حتى يتم إلغاء التجميد.
                        </p>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                                سبب التجميد *
                            </label>
                            <textarea
                                name="reason"
                                required
                                rows="3"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-2.5 text-sm dark:bg-gray-800 dark:text-white"
                                placeholder="أدخل سبب تجميد الحساب..."
                            ></textarea>
                        </div>
                    </form>
                </x-slot:content>

                <x-slot:footer>
                    <div class="flex items-center gap-2.5">
                        <button
                            type="submit"
                            form="suspendWalletForm_{{ $wallet->id }}"
                            style="background-color: #dc2626 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 13px !important; padding: 10px 18px !important; border-radius: 12px !important; border: 1px solid #b91c1c !important; cursor: pointer !important;"
                        >
                            <span style="color: #ffffff !important; font-weight: 700 !important;">تأكيد التجميد</span>
                        </button>

                        <button
                            type="button"
                            class="transparent-button"
                            @click="$refs['suspendWalletModal'].close()"
                        >
                            إلغاء
                        </button>
                    </div>
                </x-slot:footer>
            </x-admin::modal>
        @else
            {{-- Modal 3: Reactivate Wallet Modal --}}
            <x-admin::modal ref="reactivateWalletModal">
                <x-slot:header>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        إعادة تنشيط محفظة العميل
                    </p>
                </x-slot:header>

                <x-slot:content>
                    <form
                        method="POST"
                        action="{{ route('admin.wallet.accounts.reactivate', $wallet->id) }}"
                        id="reactivateWalletForm_{{ $wallet->id }}"
                        class="px-4 py-2 flex flex-col gap-4"
                    >
                        @csrf

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            هل أنت متأكد من رغبتك في إلغاء التجميد وإعادة تنشيط هذه المحفظة؟
                        </p>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300 required">
                                سبب إعادة التنشيط *
                            </label>
                            <textarea
                                name="reason"
                                required
                                rows="3"
                                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 p-2.5 text-sm dark:bg-gray-800 dark:text-white"
                                placeholder="أدخل ملاحظة إعادة التنشيط..."
                            ></textarea>
                        </div>
                    </form>
                </x-slot:content>

                <x-slot:footer>
                    <div class="flex items-center gap-2.5">
                        <button
                            type="submit"
                            form="reactivateWalletForm_{{ $wallet->id }}"
                            style="background-color: #10b981 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 13px !important; padding: 10px 18px !important; border-radius: 12px !important; border: 1px solid #059669 !important; cursor: pointer !important;"
                        >
                            <span style="color: #ffffff !important; font-weight: 700 !important;">تأكيد التنشيط</span>
                        </button>

                        <button
                            type="button"
                            class="transparent-button"
                            @click="$refs['reactivateWalletModal'].close()"
                        >
                            إلغاء
                        </button>
                    </div>
                </x-slot:footer>
            </x-admin::modal>
        @endif
    </div>
</x-admin::layouts>
