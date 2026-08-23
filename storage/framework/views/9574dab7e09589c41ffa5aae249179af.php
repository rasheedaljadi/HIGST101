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
        <?php echo e(trans('procurement::app.reports.title')); ?>

     <?php $__env->endSlot(); ?>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span><?php echo e(trans('procurement::app.admin.menu.procurement-v2')); ?></span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium"><?php echo e(trans('procurement::app.reports.title')); ?></span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    <?php echo e(trans('procurement::app.reports.title')); ?>

                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    <?php echo e(trans('procurement::app.reports.description')); ?>

                </p>
            </div>
        </div>

        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo e(trans('procurement::app.reports.open-demands-qty')); ?></span>
                <div class="text-3xl font-bold text-amber-600 dark:text-amber-400 mt-2"><?php echo e($metrics['open_demands_qty']); ?></div>
                <span class="text-xs text-gray-400 mt-1 block"><?php echo e($metrics['open_demands_count']); ?> orders awaiting batching</span>
            </div>

            <?php if(bouncer()->hasPermission('dropshipping.procurement_v2.cost_view')): ?>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo e(trans('procurement::app.reports.total-expected-cost')); ?></span>
                    <div class="text-3xl font-bold text-gray-900 dark:text-white mt-2">$<?php echo e(number_format($metrics['total_expected_cost'], 2)); ?></div>
                    <span class="text-xs text-gray-400 mt-1 block">Expected USD Procurement Cost</span>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo e(trans('procurement::app.reports.total-actual-cost')); ?></span>
                    <div class="text-3xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">$<?php echo e(number_format($metrics['total_actual_cost'], 2)); ?></div>
                    <span class="text-xs text-gray-400 mt-1 block">Actual USD Confirmed Paid</span>
                </div>

                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider"><?php echo e(trans('procurement::app.reports.net-cost-variance')); ?></span>
                    <div class="text-3xl font-bold <?php echo e($metrics['total_cost_variance'] > 0 ? 'text-rose-600' : 'text-emerald-600'); ?> mt-2">
                        <?php echo e($metrics['total_cost_variance'] > 0 ? '+' : ''); ?>$<?php echo e(number_format($metrics['total_cost_variance'], 2)); ?>

                    </div>
                    <span class="text-xs text-gray-400 mt-1 block">Variance Discrepancy Amount</span>
                </div>
            <?php endif; ?>
        </div>

        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3"><?php echo e(trans('procurement::app.reports.uncollected-cod-revenue')); ?></h3>
                <div class="text-2xl font-bold text-amber-600">$<?php echo e(number_format($metrics['uncollected_cod_total'], 2)); ?></div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    <?php echo e(trans('procurement::app.reports.uncollected-cod-desc')); ?>

                </p>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3"><?php echo e(trans('procurement::app.reports.delayed-platform-orders')); ?></h3>
                <div class="text-2xl font-bold <?php echo e($metrics['delayed_orders_count'] > 0 ? 'text-rose-600' : 'text-emerald-600'); ?>">
                    <?php echo e($metrics['delayed_orders_count']); ?>

                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    <?php echo e(trans('procurement::app.reports.delayed-platform-desc')); ?>

                </p>
            </div>
        </div>
    </div>
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
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src/resources/views/admin/reports/index.blade.php ENDPATH**/ ?>