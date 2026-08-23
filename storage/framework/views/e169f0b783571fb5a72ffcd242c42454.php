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
        <?php echo e(trans('procurement::app.demands.title')); ?>

     <?php $__env->endSlot(); ?>

    <div class="flex flex-col gap-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span><?php echo e(trans('procurement::app.admin.menu.procurement-v2')); ?></span>
                    <span>/</span>
                    <span class="text-gray-800 dark:text-white font-medium"><?php echo e(trans('procurement::app.demands.title')); ?></span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white mt-1">
                    <?php echo e(trans('procurement::app.demands.title')); ?>

                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    <?php echo e(trans('procurement::app.demands.description')); ?>

                </p>
            </div>

            <div class="flex items-center gap-2">
                <?php if(bouncer()->hasPermission('dropshipping.procurement_v2.batch_create')): ?>
                    <a href="<?php echo e(route('admin.procurement.batches.create')); ?>" class="primary-button flex items-center gap-2">
                        <span class="icon-plus text-lg"></span>
                        <?php echo e(trans('procurement::app.batches.create-batch')); ?>

                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?php echo e(trans('procurement::app.demands.open-for-batching')); ?></span>
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2"><?php echo e($counts['open_for_batching'] ?? 0); ?></div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?php echo e(trans('procurement::app.demands.batched')); ?></span>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2"><?php echo e($counts['batched'] ?? 0); ?></div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?php echo e(trans('procurement::app.demands.locally-covered')); ?></span>
                <div class="text-2xl font-bold text-teal-600 dark:text-teal-400 mt-2"><?php echo e($counts['locally_covered'] ?? 0); ?></div>
            </div>
            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 flex flex-col justify-between shadow-sm">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?php echo e(trans('procurement::app.demands.fulfilled')); ?></span>
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2"><?php echo e($counts['fulfilled'] ?? 0); ?></div>
            </div>
        </div>

        
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
            <?php if (isset($component)) { $__componentOriginal3bea17ac3f7235e71a823454ccb74424 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3bea17ac3f7235e71a823454ccb74424 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.datagrid.index','data' => ['src' => route('admin.procurement.demands.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::datagrid'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.procurement.demands.index'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3bea17ac3f7235e71a823454ccb74424)): ?>
<?php $attributes = $__attributesOriginal3bea17ac3f7235e71a823454ccb74424; ?>
<?php unset($__attributesOriginal3bea17ac3f7235e71a823454ccb74424); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3bea17ac3f7235e71a823454ccb74424)): ?>
<?php $component = $__componentOriginal3bea17ac3f7235e71a823454ccb74424; ?>
<?php unset($__componentOriginal3bea17ac3f7235e71a823454ccb74424); ?>
<?php endif; ?>
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
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Procurement\src/resources/views/admin/demands/index.blade.php ENDPATH**/ ?>