@php
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp

<div class="flex flex-wrap items-center gap-2 border-b border-gray-200 pb-3 mb-6 dark:border-gray-800">
    {{-- 1. Pricing Audit Log --}}
    <a 
        href="{{ route('admin.audit-logs.pricing.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.audit-logs.pricing') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-dashboard text-lg"></span>
        <span>سجل تدقيق الأسعار والتكلفة</span>
    </a>

    {{-- 2. Products Import Audit Log --}}
    <a 
        href="{{ route('admin.audit-logs.products-import.index') }}" 
        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition {{ str_contains($currentRoute, 'admin.audit-logs.products-import') ? 'bg-blue-900 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
    >
        <span class="icon-products text-lg"></span>
        <span>سجل تدقيق استيراد المنتجات</span>
    </a>
</div>
