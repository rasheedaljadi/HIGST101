@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<div class="flex flex-wrap items-center gap-2 border-b border-gray-200 pb-3 mb-6 dark:border-gray-800">
    {{-- 1. Financial Governance --}}
    <a 
        href="{{ route('admin.wallet.dashboard.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.wallet.dashboard') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-dashboard text-lg"></span>
        <span>الرقابة المالية</span>
    </a>

    {{-- 2. Customer Accounts --}}
    <a 
        href="{{ route('admin.wallet.accounts.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.wallet.accounts') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-customer text-lg"></span>
        <span>محافظ العملاء</span>
    </a>

    {{-- 3. Deposits --}}
    <a 
        href="{{ route('admin.wallet.deposits.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.wallet.deposits') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-down text-lg"></span>
        <span>طلبات الشحن</span>
    </a>

    {{-- 4. Withdrawals --}}
    <a 
        href="{{ route('admin.wallet.withdrawals.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.wallet.withdrawals') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-up text-lg"></span>
        <span>طلبات السحب</span>
    </a>

    {{-- 5. Promotions & Rewards --}}
    <a 
        href="{{ route('admin.wallet.promotions.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.wallet.promotions.index') || str_contains($currentRoute, 'admin.wallet.promotions.create') || str_contains($currentRoute, 'admin.wallet.promotions.edit') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-cart text-lg"></span>
        <span>العروض والمكافآت (Promotions)</span>
    </a>

    {{-- 6. Monitoring & Governance --}}
    <a 
        href="{{ route('admin.wallet.promotions.monitoring.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.wallet.promotions.monitoring') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-setting text-lg"></span>
        <span>الرقابة والتدقيق الداخلي</span>
    </a>
</div>
