<x-admin::layouts>
    <x-slot:title>
        تفاصيل محفظة العميل: {{ $customer['name'] }}
    </x-slot:title>

    <div class="flex flex-col gap-6 p-6">
        {{-- Header Section with Customer Info & Quick Actions --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-800 pb-5">
            <div class="flex items-center gap-3">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                            تفاصيل محفظة العميل: {{ $customer['name'] }}
                        </h1>

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
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 font-mono">
                        {{ $customer['email'] }}
                    </p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Adjustment Modal Button --}}
                <button
                    type="button"
                    style="background-color: #0b2545 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 13px !important; padding: 10px 18px !important; border-radius: 12px !important; border: 1px solid #134074 !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 2px 5px rgba(11,37,69,0.3) !important;"
                    @click="$refs['adjustBalanceModal'].open()"
                >
                    <span style="color: #ffffff !important; font-weight: 700 !important;">تعديل الرصيد يدويًا +</span>
                </button>

                @if (strtolower($customer['status']) === 'active')
                    {{-- Suspend Modal Button --}}
                    <button
                        type="button"
                        style="background-color: #dc2626 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 13px !important; padding: 10px 18px !important; border-radius: 12px !important; border: 1px solid #b91c1c !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 2px 5px rgba(220,38,38,0.3) !important;"
                        @click="$refs['suspendWalletModal'].open()"
                    >
                        <span style="color: #ffffff !important; font-weight: 700 !important;">تجميد المحفظة ❄️</span>
                    </button>
                @else
                    {{-- Reactivate Modal Button --}}
                    <button
                        type="button"
                        style="background-color: #10b981 !important; color: #ffffff !important; font-weight: 700 !important; font-size: 13px !important; padding: 10px 18px !important; border-radius: 12px !important; border: 1px solid #059669 !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 8px !important; box-shadow: 0 2px 5px rgba(16,185,129,0.3) !important;"
                        @click="$refs['reactivateWalletModal'].open()"
                    >
                        <span style="color: #ffffff !important; font-weight: 700 !important;">إعادة تنشيط المحفظة ⚡</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- 3-Column Balance Cards Grid --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-wallet::stat-card
                :title="trans('إجمالي الرصيد')"
                :value="core()->formatBasePrice($balances['total'])"
                icon="icon-dollar"
                colorClass="text-blue-600 dark:text-blue-400"
            />

            <x-wallet::stat-card
                :title="trans('الرصيد المتاح')"
                :value="core()->formatBasePrice($balances['available'])"
                icon="icon-wallet"
                colorClass="text-emerald-600 dark:text-emerald-400"
            />

            <x-wallet::stat-card
                :title="trans('الرصيد المحجوز')"
                :value="core()->formatBasePrice($balances['held'])"
                icon="icon-lock"
                colorClass="text-amber-600 dark:text-amber-400"
            />
        </div>

        {{-- Main Narrative Timeline Section --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        سجل الحركات والعمليات المالية (التاريخ المالي)
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        عرض تسلسلي لجميع الحركات والعمليات المالية الخاصة بمحفظة العميل تدقيقاً ومحاسبة.
                    </p>
                </div>
            </div>

            <x-wallet::timeline :events="$timeline" />
        </div>

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
