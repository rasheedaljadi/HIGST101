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
        <?php echo e(trans('fulfillment::app.admin.menu.fulfillment')); ?>

     <?php $__env->endSlot(); ?>

    <?php
        $activeTab = request()->query('tab', 'purchase_orders');
    ?>

    <div class="flex flex-col gap-6">
        
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex flex-col">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                    <?php echo e(trans('fulfillment::app.admin.menu.fulfillment')); ?>

                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    مراقبة وإدارة أوامر الشراء الخارجية للموردين وطلبات الموافقات الإدارية.
                </p>
            </div>
        </div>

        
        <?php if(!empty($alerts)): ?>
            <div class="flex flex-col gap-3">
                <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="p-4 rounded-lg border flex items-start justify-between gap-4 <?php echo e($alert['severity'] === 'critical' ? 'bg-red-50 dark:bg-red-950/20 text-red-800 dark:text-red-400 border-red-200 dark:border-red-900/50 border-r-4 border-r-red-600' : 'bg-rose-50 dark:bg-rose-950/10 text-rose-800 dark:text-rose-400 border-rose-100 dark:border-rose-900/30 border-r-4 border-r-rose-500'); ?>">
                        <div class="flex items-start gap-3">
                            <span class="text-2xl mt-0.5 <?php echo e($alert['severity'] === 'critical' ? 'icon-cancel' : 'icon-settings'); ?>"></span>
                            <div class="flex flex-col">
                                <span class="font-bold text-sm capitalize"><?php echo e($alert['severity']); ?> Alert</span>
                                <p class="text-xs mt-0.5 leading-relaxed"><?php echo e($alert['message']); ?></p>
                                <span class="text-[10px] text-gray-400 mt-1">وقت التنبيه: <?php echo e($alert['timestamp']); ?></span>
                            </div>
                        </div>

                        <form action="<?php echo e(route('admin.dropshipping.fulfillment.clear-alert', $alert['id'])); ?>" method="POST" class="inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs underline">
                                تجاهل التنبيه
                            </button>
                        </form>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        
        <div class="grid grid-cols-4 gap-6 max-xl:grid-cols-2 max-sm:grid-cols-1">
            
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-emerald-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.success-rate')); ?>

                    </span>
                    <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-2">
                        <?php echo e($kpis['successRate']); ?>%
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.success-rate-desc')); ?>

                    </span>
                </div>
                <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/30 rounded-full flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                    <span class="icon-toast-done text-2xl"></span>
                </div>
            </div>

            
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-amber-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.retry-rate')); ?>

                    </span>
                    <span class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-2">
                        <?php echo e($kpis['retryRate']); ?>%
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.retry-rate-desc')); ?>

                    </span>
                </div>
                <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/30 rounded-full flex items-center justify-center text-amber-600 dark:text-amber-400">
                    <span class="icon-settings text-2xl"></span>
                </div>
            </div>

            
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-blue-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.avg-fulfillment-time')); ?>

                    </span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-2">
                        <?php echo e($kpis['avgTime']); ?> <?php echo e($kpis['avgTime'] > 60 ? 'hrs' : 'mins'); ?>

                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.avg-fulfillment-desc')); ?>

                    </span>
                </div>
                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-950/30 rounded-full flex items-center justify-center text-blue-600 dark:text-blue-400">
                    <span class="icon-dashboard text-2xl"></span>
                </div>
            </div>

            
            <div class="p-5 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between border-t-4 border-t-indigo-500">
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.provider-health')); ?>

                    </span>
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-2">
                        <?php echo e($kpis['health']); ?>%
                    </span>
                    <span class="text-[10px] text-gray-400 mt-1">
                        <?php echo e(trans('fulfillment::app.admin.dashboard.provider-health-desc')); ?>

                    </span>
                </div>
                <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-950/30 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <span class="icon-sales text-2xl"></span>
                </div>
            </div>
        </div>

        
        <div class="grid grid-cols-3 gap-6 max-md:grid-cols-1">
            <div class="flex items-center gap-4 p-4 bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-100 dark:border-yellow-900/50 rounded-lg">
                <span class="icon-settings text-3xl text-yellow-600 dark:text-yellow-400"></span>
                <div class="flex flex-col">
                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo e(trans('fulfillment::app.admin.dashboard.waiting-orders')); ?></span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white"><?php echo e($kpis['waiting']); ?></span>
                </div>
            </div>

            <div class="flex items-center gap-4 p-4 bg-red-50 dark:bg-red-950/20 border border-red-100 dark:border-red-900/50 rounded-lg">
                <span class="icon-cancel text-3xl text-red-600 dark:text-red-400"></span>
                <div class="flex flex-col">
                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo e(trans('fulfillment::app.admin.dashboard.manual-review-orders')); ?></span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white"><?php echo e($kpis['needsReview']); ?></span>
                </div>
            </div>

            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg">
                <span class="icon-dashboard text-3xl text-gray-600 dark:text-gray-400"></span>
                <div class="flex flex-col">
                    <span class="text-sm text-gray-500 dark:text-gray-400"><?php echo e(trans('fulfillment::app.admin.dashboard.queue-backlog')); ?></span>
                    <span class="text-xl font-bold text-gray-800 dark:text-white"><?php echo e($kpis['backlog']); ?></span>
                </div>
            </div>
        </div>

        
        <div class="border-b border-gray-200 dark:border-gray-800">
            <div class="flex gap-6">
                <a
                    href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => request()->query('po_state', 'all')])); ?>"
                    class="pb-4 text-sm font-semibold transition-all <?php echo e($activeTab === 'purchase_orders' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'); ?>"
                >
                    أوامر الشراء (Purchase Orders)
                </a>

                <?php if(config('fulfillment.approval_workflow.enabled', false)): ?>
                    <a
                        href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'approval_requests'])); ?>"
                        class="pb-4 text-sm font-semibold transition-all <?php echo e($activeTab === 'approval_requests' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400 dark:border-blue-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'); ?>"
                    >
                        طلبات الموافقات (Approval Requests)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        
        <?php if($activeTab === 'purchase_orders'): ?>
            <?php
                $activePoState = request()->query('po_state', 'all');
            ?>
            <div class="flex flex-wrap gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-800">
                <a
                    href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'all'])); ?>"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 <?php echo e($activePoState === 'all' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900'); ?>"
                >
                    عرض الكل (<?php echo e($poCounts['all']); ?>)
                </a>
                <a
                    href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'awaiting_payment_to_supplier'])); ?>"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 <?php echo e($activePoState === 'awaiting_payment_to_supplier' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900'); ?>"
                >
                    قيد انتظار الدفع (<?php echo e($poCounts['awaiting_payment']); ?>)
                </a>
                <a
                    href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'in_progress'])); ?>"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 <?php echo e($activePoState === 'in_progress' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900'); ?>"
                >
                    جاري الإجراء (<?php echo e($poCounts['in_progress']); ?>)
                </a>
                <a
                    href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'submitted'])); ?>"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 <?php echo e($activePoState === 'submitted' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900'); ?>"
                >
                    تم الإجراء (<?php echo e($poCounts['submitted']); ?>)
                </a>
                <a
                    href="<?php echo e(route('admin.dropshipping.fulfillment.index', ['tab' => 'purchase_orders', 'po_state' => 'completed'])); ?>"
                    class="px-4 py-2 text-xs font-semibold rounded-full border transition-all duration-300 <?php echo e($activePoState === 'completed' ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-gray-950 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900'); ?>"
                >
                    تم الاكمال (<?php echo e($poCounts['completed']); ?>)
                </a>
            </div>
        <?php endif; ?>

        
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-800 shadow-sm p-2">
            <?php if($activeTab === 'purchase_orders'): ?>
                <?php if (isset($component)) { $__componentOriginal3bea17ac3f7235e71a823454ccb74424 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3bea17ac3f7235e71a823454ccb74424 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.datagrid.index','data' => ['src' => route('admin.dropshipping.fulfillment.index', ['po_state' => request()->query('po_state', 'all')])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::datagrid'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.dropshipping.fulfillment.index', ['po_state' => request()->query('po_state', 'all')]))]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3bea17ac3f7235e71a823454ccb74424)): ?>
<?php $attributes = $__attributesOriginal3bea17ac3f7235e71a823454ccb74424; ?>
<?php unset($__attributesOriginal3bea17ac3f7235e71a823454ccb74424); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3bea17ac3f7235e71a823454ccb74424)): ?>
<?php $component = $__componentOriginal3bea17ac3f7235e71a823454ccb74424; ?>
<?php unset($__componentOriginal3bea17ac3f7235e71a823454ccb74424); ?>
<?php endif; ?>
            <?php elseif($activeTab === 'approval_requests'): ?>
                <?php if (isset($component)) { $__componentOriginal3bea17ac3f7235e71a823454ccb74424 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3bea17ac3f7235e71a823454ccb74424 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.datagrid.index','data' => ['src' => route('admin.dropshipping.fulfillment.index', ['grid' => 'approvals'])]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::datagrid'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['src' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.dropshipping.fulfillment.index', ['grid' => 'approvals']))]); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3bea17ac3f7235e71a823454ccb74424)): ?>
<?php $attributes = $__attributesOriginal3bea17ac3f7235e71a823454ccb74424; ?>
<?php unset($__attributesOriginal3bea17ac3f7235e71a823454ccb74424); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3bea17ac3f7235e71a823454ccb74424)): ?>
<?php $component = $__componentOriginal3bea17ac3f7235e71a823454ccb74424; ?>
<?php unset($__componentOriginal3bea17ac3f7235e71a823454ccb74424); ?>
<?php endif; ?>
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
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Fulfillment\src/resources/views/admin/index.blade.php ENDPATH**/ ?>