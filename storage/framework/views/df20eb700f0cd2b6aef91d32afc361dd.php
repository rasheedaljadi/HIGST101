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
        <?php echo app('translator')->get('admin::app.dashboard.index.title'); ?>
     <?php $__env->endSlot(); ?>

    <!-- User Details & View Switcher Header -->
    <div class="flex items-center justify-between gap-4 mb-5 max-sm:flex-wrap">
        <div class="grid gap-1.5">
            <div class="flex items-center gap-3">
                <p class="text-xl font-bold !leading-normal text-gray-800 dark:text-white" v-pre>
                    <?php echo app('translator')->get('admin::app.dashboard.index.user-name', ['user_name' => auth()->guard('admin')->user()->name]); ?>
                </p>

                <!-- Interactive View Mode Toggle Switch [بسيط] [متقدم] -->
                <div class="inline-flex items-center p-1 bg-gray-100 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-inner">
                    <a
                        href="<?php echo e(route('admin.dashboard.index', ['view' => 'simple'])); ?>"
                        class="px-3 py-1 text-xs font-bold rounded-md transition-all <?php echo e(($viewMode ?? 'simple') === 'simple' ? 'bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400'); ?>"
                        onclick="event.preventDefault(); window.toggleDashboardView('simple')"
                    >
                        بسيط (Simple)
                    </a>
                    <a
                        href="<?php echo e(route('admin.dashboard.index', ['view' => 'advanced'])); ?>"
                        class="px-3 py-1 text-xs font-bold rounded-md transition-all <?php echo e(($viewMode ?? 'simple') === 'advanced' ? 'bg-blue-600 text-white shadow-sm font-extrabold' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400'); ?>"
                        onclick="event.preventDefault(); window.toggleDashboardView('advanced')"
                    >
                        متقدم (Advanced)
                    </a>
                </div>
            </div>

            <p class="!leading-normal text-gray-600 dark:text-gray-300">
                <?php echo app('translator')->get('admin::app.dashboard.index.user-info'); ?>
                <?php if(($viewMode ?? 'simple') === 'advanced'): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 mr-2">
                        العرض المتقدم الشامل (Read-Only Layer)
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 mr-2">
                        العرض البسيط القياسي (Default)
                    </span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Actions & Date Filters -->
        <v-dashboard-filters>
            <!-- Shimmer -->
            <div class="flex gap-1.5">
                <div class="shimmer h-[39px] w-[132px] rounded-md"></div>
                <div class="shimmer h-[39px] w-[140px] rounded-md"></div>
                <div class="shimmer h-[39px] w-[140px] rounded-md"></div>
            </div>
        </v-dashboard-filters>
    </div>

    <!-- Conditional Body Rendering -->
    <?php if(($viewMode ?? 'simple') === 'advanced'): ?>
        <!-- ADVANCED DASHBOARD VIEW LAYER -->
        <div class="mt-3.5 w-full">
            <?php echo $__env->make('admin::dashboard.advanced.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    <?php else: ?>
        <!-- ORIGINAL SIMPLE DASHBOARD VIEW (100% PRESERVED) -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left Section -->
            <div class="flex flex-col flex-1 gap-8 max-xl:flex-auto">
                <?php echo view_render_event('bagisto.admin.dashboard.overall_details.before'); ?>


                <!-- Overall Details -->
                <div class="flex flex-col gap-2">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        <?php echo app('translator')->get('admin::app.dashboard.index.overall-details'); ?>
                    </p>

                    <!-- Over All Details Section -->
                    <?php echo $__env->make('admin::dashboard.over-all-details', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <?php echo view_render_event('bagisto.admin.dashboard.overall_details.after'); ?>


                <?php echo view_render_event('bagisto.admin.dashboard.todays_details.before'); ?>


                <!-- Todays Details -->
                <div class="flex flex-col gap-2">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        <?php echo app('translator')->get('admin::app.dashboard.index.today-details'); ?>
                    </p>

                    <!-- Todays Details Section -->
                    <?php echo $__env->make('admin::dashboard.todays-details', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <?php echo view_render_event('bagisto.admin.dashboard.todays_details.after'); ?>


                <?php echo view_render_event('bagisto.admin.dashboard.stock_threshold.before'); ?>


                <!-- Stock Threshold -->
                <div class="flex flex-col gap-2">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        <?php echo app('translator')->get('admin::app.dashboard.index.stock-threshold'); ?>
                    </p>

                    <!-- Products List -->
                    <?php echo $__env->make('admin::dashboard.stock-threshold-products', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <?php echo view_render_event('bagisto.admin.dashboard.stock_threshold.after'); ?>

            </div>

            <!-- Right Section -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                <!-- First Component -->
                <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                    <?php echo app('translator')->get('admin::app.dashboard.index.store-stats'); ?>
                </p>

                <?php echo view_render_event('bagisto.admin.dashboard.store_stats.before'); ?>


                <!-- Store Stats -->
                <div class="bg-white rounded box-shadow dark:bg-gray-900">
                    <!-- Total Sales Details -->
                    <?php echo $__env->make('admin::dashboard.total-sales', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    <!-- Top Selling Products -->
                    <?php echo $__env->make('admin::dashboard.top-selling-products', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                    <!-- Top Customers -->
                    <?php echo $__env->make('admin::dashboard.top-customers', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                </div>

                <?php echo view_render_event('bagisto.admin.dashboard.store_stats.after'); ?>

            </div>
        </div>
    <?php endif; ?>

    <?php if (! $__env->hasRenderedOnce('60440ae4-1528-4122-9586-6f4d991fe514')): $__env->markAsRenderedOnce('60440ae4-1528-4122-9586-6f4d991fe514');
$__env->startPush('scripts'); ?>
        <script>
            window.toggleDashboardView = function(mode) {
                fetch("<?php echo e(route('admin.dashboard.toggle_view')); ?>", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>"
                    },
                    body: JSON.stringify({ view: mode })
                })
                .then(res => res.json())
                .then(data => {
                    window.location.href = "<?php echo e(route('admin.dashboard.index')); ?>?view=" + mode;
                })
                .catch(err => {
                    window.location.href = "<?php echo e(route('admin.dashboard.index')); ?>?view=" + mode;
                });
            };
        </script>

        <script
            type="module"
            src="<?php echo e(bagisto_asset('js/chart.js')); ?>"
        >
        </script>

        <script
            type="text/x-template"
            id="v-dashboard-filters-template"
        >
            <div class="flex gap-1.5">
                <template v-if="channels.length > 2">
                    <?php if (isset($component)) { $__componentOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.dropdown.index','data' => ['position' => 'bottom-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom-right']); ?>
                         <?php $__env->slot('toggle', null, []); ?> 
                            <button
                                type="button"
                                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-2 rounded-md border bg-white px-2.5 py-1.5 text-center text-sm leading-6 text-gray-600 transition-all marker:shadow hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                            >
                                {{ channels.find(channel => channel && channel.code == filters.channel)?.name || '' }}

                                <span class="text-2xl icon-sort-down"></span>
                            </button>
                         <?php $__env->endSlot(); ?>

                         <?php $__env->slot('menu', null, ['class' => '!p-0 shadow-[0_5px_20px_rgba(0,0,0,0.15)] dark:border-gray-800']); ?> 
                            <?php if (isset($component)) { $__componentOriginal0223c8534d6a243be608c3a65289c4d0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0223c8534d6a243be608c3a65289c4d0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.dropdown.menu.item','data' => ['vFor' => 'channel in channels',':class' => '{\'bg-gray-100 dark:bg-gray-950\': channel && channel.code == filters.channel}','@click' => 'filters.channel = channel ? channel.code : \'\'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::dropdown.menu.item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['v-for' => 'channel in channels',':class' => '{\'bg-gray-100 dark:bg-gray-950\': channel && channel.code == filters.channel}','@click' => 'filters.channel = channel ? channel.code : \'\'']); ?>
                                {{ channel.name }}
                             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0223c8534d6a243be608c3a65289c4d0)): ?>
<?php $attributes = $__attributesOriginal0223c8534d6a243be608c3a65289c4d0; ?>
<?php unset($__attributesOriginal0223c8534d6a243be608c3a65289c4d0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0223c8534d6a243be608c3a65289c4d0)): ?>
<?php $component = $__componentOriginal0223c8534d6a243be608c3a65289c4d0; ?>
<?php unset($__componentOriginal0223c8534d6a243be608c3a65289c4d0); ?>
<?php endif; ?>
                         <?php $__env->endSlot(); ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2)): ?>
<?php $attributes = $__attributesOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2; ?>
<?php unset($__attributesOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2)): ?>
<?php $component = $__componentOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2; ?>
<?php unset($__componentOriginalaf937e0ec72fa678d3a0c6dc6c0ac5f2); ?>
<?php endif; ?>
                </template>

                <?php if (isset($component)) { $__componentOriginalfb6be9e824dd35fb24e37e299d255b9b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.flat-picker.date','data' => ['class' => '!w-[140px]',':allowInput' => 'false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::flat-picker.date'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => '!w-[140px]',':allow-input' => 'false']); ?>
                    <input
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                        v-model="filters.start"
                        placeholder="<?php echo app('translator')->get('admin::app.dashboard.index.start-date'); ?>"
                    />
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b)): ?>
<?php $attributes = $__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b; ?>
<?php unset($__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfb6be9e824dd35fb24e37e299d255b9b)): ?>
<?php $component = $__componentOriginalfb6be9e824dd35fb24e37e299d255b9b; ?>
<?php unset($__componentOriginalfb6be9e824dd35fb24e37e299d255b9b); ?>
<?php endif; ?>

                <?php if (isset($component)) { $__componentOriginalfb6be9e824dd35fb24e37e299d255b9b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.flat-picker.date','data' => ['class' => '!w-[140px]',':allowInput' => 'false']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::flat-picker.date'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => '!w-[140px]',':allow-input' => 'false']); ?>
                    <input
                        class="flex min-h-[39px] w-full rounded-md border px-3 py-2 text-sm text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400"
                        v-model="filters.end"
                        placeholder="<?php echo app('translator')->get('admin::app.dashboard.index.end-date'); ?>"
                    />
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b)): ?>
<?php $attributes = $__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b; ?>
<?php unset($__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfb6be9e824dd35fb24e37e299d255b9b)): ?>
<?php $component = $__componentOriginalfb6be9e824dd35fb24e37e299d255b9b; ?>
<?php unset($__componentOriginalfb6be9e824dd35fb24e37e299d255b9b); ?>
<?php endif; ?>
            </div>
        </script>

        <script type="module">
            app.component('v-dashboard-filters', {
                template: '#v-dashboard-filters-template',

                data() {
                    return {
                        channels: [
                            {
                                name: "<?php echo app('translator')->get('admin::app.dashboard.index.all-channels'); ?>",
                                code: ''
                            },
                            ...<?php echo json_encode(core()->getAllChannels(), 15, 512) ?>,
                        ],

                        filters: {
                            channel: '',

                            start: "<?php echo e($startDate->format('Y-m-d')); ?>",

                            end: "<?php echo e($endDate->format('Y-m-d')); ?>",
                        }
                    }
                },

                watch: {
                    filters: {
                        handler() {
                            this.$emitter.emit('reporting-filter-updated', this.filters);
                        },

                        deep: true
                    }
                },
            });
        </script>
    <?php $__env->stopPush(); endif; ?>
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
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/dashboard/index.blade.php ENDPATH**/ ?>