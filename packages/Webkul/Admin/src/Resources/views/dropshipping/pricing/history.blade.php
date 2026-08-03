<x-admin::layouts>
    <x-slot:title>
        سجل عمليات التسعير — HIGEST Pricing Engine
    </x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-col mb-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-bold dark:text-white">
                سجل أسعار البيع والتكلفة (Calculated Price History)
            </h1>
            <p class="text-gray-600 dark:text-gray-300">
                تدقيق تفصيلي لجميع عمليات إعادة حساب الأسعار، مصادر التكلفة، وقواعد الهامش المطبقة.
            </p>
        </div>

        <a href="{{ route('admin.dropshipping.keys.index') }}#pricing" class="secondary-button font-semibold border p-2 rounded-md">
            ⬅️ العودة لقواعد التسعير
        </a>
    </div>

    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
        <table class="w-full text-right border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 text-sm">
                    <th class="p-3 border-b">#</th>
                    <th class="p-3 border-b">المنتج / الـ Variant</th>
                    <th class="p-3 border-b">التكلفة القديمة</th>
                    <th class="p-3 border-b">التكلفة الجديدة</th>
                    <th class="p-3 border-b">سعر البيع القديم</th>
                    <th class="p-3 border-b">سعر البيع الجديد</th>
                    <th class="p-3 border-b">الربح المحسوب</th>
                    <th class="p-3 border-b">القاعدة والمشغل</th>
                    <th class="p-3 border-b">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                @forelse($histories as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="p-3 font-mono">#{{ $log->id }}</td>
                        <td class="p-3">
                            <div class="font-bold dark:text-white">
                                {{ $log->variant?->name ?? $log->parentProduct?->name ?? "Variant #{$log->variant_id}" }}
                            </div>
                            <div class="text-xs text-gray-500 font-mono">
                                Variant ID: {{ $log->variant_id }} | Parent ID: {{ $log->product_id }}
                            </div>
                        </td>
                        <td class="p-3 font-mono text-gray-500">
                            {{ $log->old_acquisition_cost ? '$' . number_format($log->old_acquisition_cost, 2) : '-' }}
                        </td>
                        <td class="p-3 font-mono font-bold text-blue-600">
                            ${{ number_format($log->new_acquisition_cost, 2) }}
                        </td>
                        <td class="p-3 font-mono text-gray-500">
                            {{ $log->old_selling_price ? '$' . number_format($log->old_selling_price, 2) : '-' }}
                        </td>
                        <td class="p-3 font-mono font-bold text-green-600">
                            ${{ number_format($log->new_selling_price, 2) }}
                        </td>
                        <td class="p-3 font-mono text-emerald-600 font-bold">
                            +${{ number_format($log->new_selling_price - $log->new_acquisition_cost, 2) }}
                        </td>
                        <td class="p-3">
                            <div class="text-xs font-bold">
                                {{ $log->rule_snapshot['name'] ?? 'قاعدة افتراضية' }} (v{{ $log->rule_version ?? 1 }})
                            </div>
                            <span class="px-2 py-0.5 text-xs rounded bg-purple-100 text-purple-800">
                                {{ strtoupper($log->trigger) }}
                            </span>
                        </td>
                        <td class="p-3 text-xs text-gray-500 font-mono">
                            {{ $log->created_at?->format('Y-m-d H:i:s') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="p-6 text-center text-gray-500">
                            لا توجد سجلات تسعير مسجلة حتى الآن.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
            {{ $histories->links() }}
        </div>
    </div>
</x-admin::layouts>
