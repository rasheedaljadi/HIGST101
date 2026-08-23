<!-- Top Selling Products Vue Component -->
<v-dashboard-top-customers>
    <!-- Shimmer -->
    <?php if (isset($component)) { $__componentOriginald15ce918ccec4cf312d8a1e760b9aed0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.top-customers','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.top-customers'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0)): ?>
<?php $attributes = $__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0; ?>
<?php unset($__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald15ce918ccec4cf312d8a1e760b9aed0)): ?>
<?php $component = $__componentOriginald15ce918ccec4cf312d8a1e760b9aed0; ?>
<?php unset($__componentOriginald15ce918ccec4cf312d8a1e760b9aed0); ?>
<?php endif; ?>
</v-dashboard-top-customers>

<?php if (! $__env->hasRenderedOnce('392c3286-3b62-4eba-8b22-0752b1b558cf')): $__env->markAsRenderedOnce('392c3286-3b62-4eba-8b22-0752b1b558cf');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-dashboard-top-customers-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <?php if (isset($component)) { $__componentOriginald15ce918ccec4cf312d8a1e760b9aed0 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.top-customers','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.top-customers'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0)): ?>
<?php $attributes = $__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0; ?>
<?php unset($__attributesOriginald15ce918ccec4cf312d8a1e760b9aed0); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald15ce918ccec4cf312d8a1e760b9aed0)): ?>
<?php $component = $__componentOriginald15ce918ccec4cf312d8a1e760b9aed0; ?>
<?php unset($__componentOriginald15ce918ccec4cf312d8a1e760b9aed0); ?>
<?php endif; ?>
        </template>

        <!-- Total Sales Section -->
        <template v-else>
            <div class="border-b dark:border-gray-800">
                <div class="flex items-center justify-between p-4">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        <?php echo app('translator')->get('admin::app.dashboard.index.customer-with-most-sales'); ?>
                    </p>

                    <p class="text-xs font-semibold text-gray-400">
                        {{ report.date_range }}
                    </p>
                </div>

                <div
                    class="flex flex-col gap-8 border-b p-4 transition-all last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                    v-if="report.statistics.length"
                    v-for="customer in report.statistics"
                >
                    <a :href="customer.id ? '<?php echo e(route('admin.customers.customers.view', ':id')); ?>'.replace(':id', customer.id) : '#'">
                        <div class="flex justify-between gap-1.5">
                            <div class="flex flex-col">
                                <p class="font-semibold text-gray-600 dark:text-gray-300">
                                    {{ customer.full_name }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300">
                                    {{ customer.email }}
                                </p>
                            </div>

                            <div class="flex flex-col">
                                <p class="font-semibold text-gray-800 dark:text-white">
                                    {{ customer.formatted_total }}
                                </p>

                                <p class="text-gray-600 dark:text-gray-300" v-if="customer.orders">
                                    {{ "<?php echo app('translator')->get('admin::app.dashboard.index.order-count'); ?>".replace(':count', customer.orders) }}
                                </p>
                            </div>
                        </div>
                    </a>
                </div>

                <div
                    class="flex flex-col gap-8 p-4"
                    v-else
                >
                    <div class="grid justify-center justify-items-center gap-3.5 py-2.5">
                        <!-- Placeholder Image -->
                        <img
                            src="<?php echo e(bagisto_asset('images/empty-placeholders/customers.svg')); ?>"
                            class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                        />

                        <!-- Add Variants Information -->
                        <div class="flex flex-col items-center">
                            <p class="text-base font-semibold text-gray-400">
                                <?php echo app('translator')->get('admin::app.dashboard.index.add-customer'); ?>
                            </p>

                            <p class="text-gray-400">
                                <?php echo app('translator')->get('admin::app.dashboard.index.customer-info'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-customers', {
            template: '#v-dashboard-top-customers-template',

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

                    filters.type = 'top-customers';

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
<?php $__env->stopPush(); endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/dashboard/top-customers.blade.php ENDPATH**/ ?>