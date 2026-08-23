<?php if (isset($component)) { $__componentOriginal8001c520f4b7dcb40a16cd3b411856d1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8001c520f4b7dcb40a16cd3b411856d1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.layouts.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::layouts'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('title', null, []); ?> 
        <?php echo e(trans('procurement::app.batches.create-batch')); ?>

     <?php $__env->endSlot(); ?>

    <form action="<?php echo e(route('admin.procurement.batches.store')); ?>" method="POST" id="create-batch-form">
        <?php echo csrf_field(); ?>

        <div class="flex flex-col gap-6">
            
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex flex-col">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <a href="<?php echo e(route('admin.procurement.batches.index')); ?>" class="hover:text-blue-600">
                            <?php echo e(trans('procurement::app.batches.title')); ?>

                        </a>
                        <span>/</span>
                        <span class="text-gray-800 dark:text-white font-medium"><?php echo e(trans('procurement::app.batches.create-batch')); ?></span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                        <?php echo e(trans('procurement::app.batches.create-batch')); ?>

                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        <?php echo e(trans('procurement::app.batches.select-demands-desc')); ?>

                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <a href="<?php echo e(route('admin.procurement.batches.index')); ?>" class="secondary-button">
                        <?php echo e(trans('procurement::app.general.cancel')); ?>

                    </a>
                    <button type="submit" class="primary-button flex items-center gap-2" id="submit-batch-btn">
                        <span class="icon-save text-lg"></span>
                        <?php echo e(trans('procurement::app.batches.create-and-split')); ?>

                    </button>
                </div>
            </div>

            
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl overflow-hidden shadow-sm">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" onclick="toggleSelectAll(this)">
                        <label for="select-all" class="text-sm font-semibold text-gray-700 dark:text-gray-300 cursor-pointer">
                            <?php echo e(trans('procurement::app.batches.select-all')); ?> (<span id="selected-count">0</span> / <?php echo e($openDemands->count()); ?>)
                        </label>
                    </div>
                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        <?php echo e(trans('procurement::app.general.currency')); ?>: <span class="font-bold text-gray-900 dark:text-white">USD</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-semibold uppercase text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="p-4 w-10"></th>
                                <th class="p-4"><?php echo e(trans('procurement::app.datagrid.demand-id')); ?></th>
                                <th class="p-4"><?php echo e(trans('procurement::app.datagrid.order-id')); ?></th>
                                <th class="p-4"><?php echo e(trans('procurement::app.datagrid.supplier-store')); ?></th>
                                <th class="p-4"><?php echo e(trans('procurement::app.datagrid.supplier-sku')); ?></th>
                                <th class="p-4 text-center"><?php echo e(trans('procurement::app.datagrid.deficit-qty')); ?></th>
                                <?php if(bouncer()->hasPermission('dropshipping.procurement_v2.cost_view')): ?>
                                    <th class="p-4 text-right"><?php echo e(trans('procurement::app.datagrid.unit-cost')); ?></th>
                                    <th class="p-4 text-right"><?php echo e(trans('procurement::app.datagrid.total-cost')); ?></th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <?php $__empty_1 = true; $__currentLoopData = $openDemands; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $demand): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $unitCost = (float) ($demand->source_snapshot['unit_cost'] ?? 10.0);
                                    $deficit = $demand->remaining_unbatched_qty;
                                    $lineCost = $deficit * $unitCost;
                                ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="p-4">
                                        <input type="checkbox" name="demand_ids[]" value="<?php echo e($demand->id); ?>" class="demand-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" onchange="updateSelectionSummary()">
                                    </td>
                                    <td class="p-4 font-semibold text-gray-900 dark:text-gray-100">#<?php echo e($demand->id); ?></td>
                                    <td class="p-4">
                                        <span class="font-medium text-blue-600">#<?php echo e($demand->order?->increment_id ?: $demand->order_id); ?></span>
                                    </td>
                                    <td class="p-4"><?php echo e($demand->supplier_store_name ?: ($demand->supplier_store_id ?: 'AliExpress Store')); ?></td>
                                    <td class="p-4 font-mono text-xs"><?php echo e($demand->supplier_sku_id); ?></td>
                                    <td class="p-4 text-center font-bold text-gray-900 dark:text-white"><?php echo e($deficit); ?></td>
                                    <?php if(bouncer()->hasPermission('dropshipping.procurement_v2.cost_view')): ?>
                                        <td class="p-4 text-right">$<?php echo e(number_format($unitCost, 2)); ?></td>
                                        <td class="p-4 text-right font-semibold text-gray-900 dark:text-white">$<?php echo e(number_format($lineCost, 2)); ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="8" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                        <?php echo e(trans('procurement::app.demands.no-open-demands')); ?>

                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>

    <script>
        function toggleSelectAll(master) {
            const checkboxes = document.querySelectorAll('.demand-checkbox');
            checkboxes.forEach(cb => cb.checked = master.checked);
            updateSelectionSummary();
        }

        function updateSelectionSummary() {
            const checked = document.querySelectorAll('.demand-checkbox:checked');
            document.getElementById('selected-count').innerText = checked.length;
        }
    </script>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8001c520f4b7dcb40a16cd3b411856d1)): ?>
<?php $attributes = $__attributesOriginal8001c520f4b7dcb40a16cd3b411856d1; ?>
<?php unset($__attributesOriginal8001c520f4b7dcb40a16cd3b411856d1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8001c520f4b7dcb40a16cd3b411856d1)): ?>
<?php $component = $__componentOriginal8001c520f4b7dcb40a16cd3b411856d1; ?>
<?php unset($__componentOriginal8001c520f4b7dcb40a16cd3b411856d1); ?>
<?php endif; ?>
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src/resources/views/admin/batches/create.blade.php ENDPATH**/ ?>