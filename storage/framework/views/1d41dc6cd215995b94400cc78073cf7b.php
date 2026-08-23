<!-- Over Details Vue Component -->
<v-dashboard-overall-details>
    <!-- Shimmer -->
    <?php if (isset($component)) { $__componentOriginal74b61c8560303c04536646bbf543a1ef = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b61c8560303c04536646bbf543a1ef = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.over-all-details','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.over-all-details'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b61c8560303c04536646bbf543a1ef)): ?>
<?php $attributes = $__attributesOriginal74b61c8560303c04536646bbf543a1ef; ?>
<?php unset($__attributesOriginal74b61c8560303c04536646bbf543a1ef); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b61c8560303c04536646bbf543a1ef)): ?>
<?php $component = $__componentOriginal74b61c8560303c04536646bbf543a1ef; ?>
<?php unset($__componentOriginal74b61c8560303c04536646bbf543a1ef); ?>
<?php endif; ?>
</v-dashboard-overall-details>

<?php if (! $__env->hasRenderedOnce('91dffbda-29eb-4cf1-8a66-7e4597d94b3b')): $__env->markAsRenderedOnce('91dffbda-29eb-4cf1-8a66-7e4597d94b3b');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-dashboard-overall-details-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <?php if (isset($component)) { $__componentOriginal74b61c8560303c04536646bbf543a1ef = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal74b61c8560303c04536646bbf543a1ef = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.over-all-details','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.over-all-details'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal74b61c8560303c04536646bbf543a1ef)): ?>
<?php $attributes = $__attributesOriginal74b61c8560303c04536646bbf543a1ef; ?>
<?php unset($__attributesOriginal74b61c8560303c04536646bbf543a1ef); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal74b61c8560303c04536646bbf543a1ef)): ?>
<?php $component = $__componentOriginal74b61c8560303c04536646bbf543a1ef; ?>
<?php unset($__componentOriginal74b61c8560303c04536646bbf543a1ef); ?>
<?php endif; ?>
        </template>

        <!-- Total Sales Section -->
        <template v-else>
            <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                <div class="flex flex-wrap gap-4">
                    <!-- Total Sales -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="<?php echo e(bagisto_asset('images/total-sales.svg')); ?>"
                                title="<?php echo app('translator')->get('admin::app.dashboard.index.total-sales'); ?>"
                            >
                        </div>

                        <!-- Sales Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                {{ report.statistics.total_sales.formatted_total }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <?php echo app('translator')->get('admin::app.dashboard.index.total-sales'); ?>
                            </p>

                            <!-- Sales Percentage -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.total_sales.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold text-emerald-500"
                                    :class="[report.statistics.total_sales.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    {{ Math.abs(report.statistics.total_sales.progress.toFixed(2)) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Orders -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="<?php echo e(bagisto_asset('images/total-orders.svg')); ?>"
                                title="<?php echo app('translator')->get('admin::app.dashboard.index.total-orders'); ?>"
                            >
                        </div>

                        <!-- Orders Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                {{ report.statistics.total_orders.current }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <?php echo app('translator')->get('admin::app.dashboard.index.total-orders'); ?>
                            </p>

                            <!-- Order Percentage -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.total_orders.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold text-emerald-500"
                                    :class="[report.statistics.total_orders.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    {{ Math.abs(report.statistics.total_orders.progress.toFixed(2)) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Customers -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="<?php echo e(bagisto_asset('images/customers.svg')); ?>"
                                title="<?php echo app('translator')->get('admin::app.dashboard.index.total-customers'); ?>"
                            >
                        </div>

                        <!-- Customers Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                {{ report.statistics.total_customers.current }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <?php echo app('translator')->get('admin::app.dashboard.index.total-customers'); ?>
                            </p>

                            <!-- Customers Percentage -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.total_customers.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold text-emerald-500"
                                    :class="[report.statistics.total_customers.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    {{ Math.abs(report.statistics.total_customers.progress.toFixed(2)) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Average sales -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="<?php echo e(bagisto_asset('images/average-orders.svg')); ?>"
                                title="<?php echo app('translator')->get('admin::app.dashboard.index.average-sale'); ?>"
                            >
                        </div>

                        <!-- Sales Stats -->
                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                {{ report.statistics.avg_sales.formatted_total }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <?php echo app('translator')->get('admin::app.dashboard.index.average-sale'); ?>
                            </p>

                            <!-- Sales Percentage -->
                            <div class="flex items-center gap-0.5">
                                <span
                                    class="text-base text-emerald-500"
                                    :class="[report.statistics.avg_sales.progress < 0 ? 'icon-down-stat text-red-500 dark:!text-red-500' : 'icon-up-stat text-emerald-500 dark:!text-emerald-500']"
                                ></span>

                                <p
                                    class="text-xs font-semibold"
                                    :class="[report.statistics.avg_sales.progress < 0 ?  'text-red-500' : 'text-emerald-500']"
                                >
                                    {{ Math.abs(report.statistics.avg_sales.progress).toFixed(2) }}%
                                </p>

                            </div>
                        </div>
                    </div>

                    <!-- Unpaid Invoices -->
                    <div class="flex min-w-[200px] flex-1 gap-2.5">
                        <div class="h-[60px] max-h-[60px] w-full max-w-[60px] dark:mix-blend-exclusion dark:invert">
                            <img
                                src="<?php echo e(bagisto_asset('images/unpaid-invoices.svg')); ?>"
                                title="<?php echo app('translator')->get('admin::app.dashboard.index.total-unpaid-invoices'); ?>"
                            >
                        </div>

                        <div class="grid place-content-start gap-1">
                            <p class="text-base font-semibold leading-none text-gray-800 dark:text-white">
                                {{ report.statistics.total_unpaid_invoices.formatted_total }}
                            </p>

                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <?php echo app('translator')->get('admin::app.dashboard.index.total-unpaid-invoices'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-overall-details', {
            template: '#v-dashboard-overall-details-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            mounted() {
                this.getStats({});

                this.$emitter.on('reporting-filter-updated', this.getStats);
            },

            methods: {
                getStats(filters) {
                    this.isLoading = true;

                    var filters = Object.assign({}, filters);

                    filters.type = 'over-all';

                    this.$axios.get("<?php echo e(route('admin.dashboard.stats')); ?>", {
                            params: filters
                        })
                        .then(response => {
                            this.report = response.data;

                            this.isLoading = false;
                        })
                        .catch(error => {});
                }
            }
        });
    </script>
<?php $__env->stopPush(); endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/dashboard/over-all-details.blade.php ENDPATH**/ ?>