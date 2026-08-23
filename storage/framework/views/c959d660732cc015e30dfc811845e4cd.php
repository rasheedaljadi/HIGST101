<!-- Total Sales Vue Component -->
<v-dashboard-total-sales>
    <!-- Shimmer -->
    <?php if (isset($component)) { $__componentOriginal7bd0bcdc7cd42992220b0dde933083fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.total-sales','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.total-sales'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc)): ?>
<?php $attributes = $__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc; ?>
<?php unset($__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7bd0bcdc7cd42992220b0dde933083fc)): ?>
<?php $component = $__componentOriginal7bd0bcdc7cd42992220b0dde933083fc; ?>
<?php unset($__componentOriginal7bd0bcdc7cd42992220b0dde933083fc); ?>
<?php endif; ?>
</v-dashboard-total-sales>

<?php if (! $__env->hasRenderedOnce('5159c0e2-8234-442f-ad2f-a83594a3de1d')): $__env->markAsRenderedOnce('5159c0e2-8234-442f-ad2f-a83594a3de1d');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-dashboard-total-sales-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <?php if (isset($component)) { $__componentOriginal7bd0bcdc7cd42992220b0dde933083fc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.total-sales','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.total-sales'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc)): ?>
<?php $attributes = $__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc; ?>
<?php unset($__attributesOriginal7bd0bcdc7cd42992220b0dde933083fc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7bd0bcdc7cd42992220b0dde933083fc)): ?>
<?php $component = $__componentOriginal7bd0bcdc7cd42992220b0dde933083fc; ?>
<?php unset($__componentOriginal7bd0bcdc7cd42992220b0dde933083fc); ?>
<?php endif; ?>
        </template>

        <!-- Total Sales Section -->
        <template v-else>
            <div class="grid gap-4 border-b px-4 py-2 dark:border-gray-800">
                <div class="flex justify-between gap-2">
                    <div class="flex flex-col justify-between gap-1">
                        <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            <?php echo app('translator')->get('admin::app.dashboard.index.total-sales'); ?>
                        </p>

                        <!-- Total Order Revenue -->
                        <p class="text-lg font-bold leading-none text-gray-800 dark:text-white">
                            {{ report.statistics.total_sales.formatted_total }}
                        </p>
                    </div>

                    <div class="flex flex-col justify-between gap-1">
                        <!-- Orders Time Duration -->
                        <p class="text-right text-xs font-semibold text-gray-400 dark:text-white">
                            {{ report.date_range }}
                        </p>

                        <!-- Total Orders -->
                        <p class="text-right text-xs font-semibold text-gray-400 dark:text-white">
                            {{ "<?php echo app('translator')->get('admin::app.dashboard.index.order'); ?>".replace(':total_orders', report.statistics.total_orders.current ?? 0) }}
                        </p>
                    </div>
                </div>

                <!-- Bar Chart -->
                <?php if (isset($component)) { $__componentOriginalf196fc0ab37fc50ba89798b7f8a09a8b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf196fc0ab37fc50ba89798b7f8a09a8b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.charts.bar','data' => [':labels' => 'chartLabels',':datasets' => 'chartDatasets',':aspectRatio' => '1.41']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::charts.bar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([':labels' => 'chartLabels',':datasets' => 'chartDatasets',':aspect-ratio' => '1.41']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf196fc0ab37fc50ba89798b7f8a09a8b)): ?>
<?php $attributes = $__attributesOriginalf196fc0ab37fc50ba89798b7f8a09a8b; ?>
<?php unset($__attributesOriginalf196fc0ab37fc50ba89798b7f8a09a8b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf196fc0ab37fc50ba89798b7f8a09a8b)): ?>
<?php $component = $__componentOriginalf196fc0ab37fc50ba89798b7f8a09a8b; ?>
<?php unset($__componentOriginalf196fc0ab37fc50ba89798b7f8a09a8b); ?>
<?php endif; ?>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-total-sales', {
            template: '#v-dashboard-total-sales-template',

            data() {
                return {
                    report: [],

                    isLoading: true,
                }
            },

            computed: {
                chartLabels() {
                    return this.report.statistics.over_time.map(({ label }) => label);
                },

                chartDatasets() {
                    return [{
                        data: this.report.statistics.over_time.map(({ total }) => total),
                        barThickness: 6,
                        backgroundColor: '#262F8F',
                    }];
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

                    filters.type = 'total-sales';

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
<?php $__env->stopPush(); endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/dashboard/total-sales.blade.php ENDPATH**/ ?>