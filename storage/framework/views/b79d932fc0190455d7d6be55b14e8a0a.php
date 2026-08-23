<!-- Top Selling Products Vue Component -->
<v-dashboard-top-selling-products>
    <!-- Shimmer -->
    <?php if (isset($component)) { $__componentOriginale52ab9a40953f35fb5ba617812c11e56 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale52ab9a40953f35fb5ba617812c11e56 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.top-selling-products','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.top-selling-products'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale52ab9a40953f35fb5ba617812c11e56)): ?>
<?php $attributes = $__attributesOriginale52ab9a40953f35fb5ba617812c11e56; ?>
<?php unset($__attributesOriginale52ab9a40953f35fb5ba617812c11e56); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale52ab9a40953f35fb5ba617812c11e56)): ?>
<?php $component = $__componentOriginale52ab9a40953f35fb5ba617812c11e56; ?>
<?php unset($__componentOriginale52ab9a40953f35fb5ba617812c11e56); ?>
<?php endif; ?>
</v-dashboard-top-selling-products>

<?php if (! $__env->hasRenderedOnce('7c402229-0a8c-47be-9723-f8801e298659')): $__env->markAsRenderedOnce('7c402229-0a8c-47be-9723-f8801e298659');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-dashboard-top-selling-products-template"
    >
        <!-- Shimmer -->
        <template v-if="isLoading">
            <?php if (isset($component)) { $__componentOriginale52ab9a40953f35fb5ba617812c11e56 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale52ab9a40953f35fb5ba617812c11e56 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.dashboard.top-selling-products','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.dashboard.top-selling-products'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale52ab9a40953f35fb5ba617812c11e56)): ?>
<?php $attributes = $__attributesOriginale52ab9a40953f35fb5ba617812c11e56; ?>
<?php unset($__attributesOriginale52ab9a40953f35fb5ba617812c11e56); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale52ab9a40953f35fb5ba617812c11e56)): ?>
<?php $component = $__componentOriginale52ab9a40953f35fb5ba617812c11e56; ?>
<?php unset($__componentOriginale52ab9a40953f35fb5ba617812c11e56); ?>
<?php endif; ?>
        </template>

        <!-- Total Sales Section -->
        <template v-else>
            <div class="border-b dark:border-gray-800">
                <div class="flex items-center justify-between p-4">
                    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                        <?php echo app('translator')->get('admin::app.dashboard.index.top-selling-products'); ?>
                    </p>

                    <p class="text-xs font-semibold text-gray-400">
                        {{ report.date_range }}
                    </p>
                </div>

                <!-- Top Selling Products Details -->
                <div
                    class="flex flex-col"
                    v-if="report.statistics.length"
                >
                    <a
                        :href="'<?php echo e(route('admin.catalog.products.edit', ':id')); ?>'.replace(':id', item.id)"
                        class="flex gap-2.5 border-b p-4 transition-all last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                        v-for="item in report.statistics"
                    >
                        <!-- Product Item -->
                        <img
                            v-if="item.images?.length"
                            class="relative h-[65px] max-h-[65px] w-full max-w-[65px] overflow-hidden rounded"
                            :src="item.images[0]?.url"
                        />

                        <div
                            v-else
                            class="relative h-[65px] max-h-[65px] w-full max-w-[65px] overflow-hidden rounded border border-dashed border-gray-300 dark:border-gray-800 dark:mix-blend-exclusion dark:invert"
                        >
                            <img src="<?php echo e(bagisto_asset('images/product-placeholders/front.svg')); ?>">
                            
                            <p class="absolute bottom-1.5 w-full text-center text-[6px] font-semibold text-gray-400">
                                <?php echo app('translator')->get('admin::app.dashboard.index.product-image'); ?>
                            </p>
                        </div>

                        <!-- Product Details -->
                        <div class="flex w-full flex-col gap-1.5">
                            <p
                                class="text-gray-600 dark:text-gray-300"
                                v-text="item.name"
                            >
                            </p>

                            <div class="flex justify-between">
                                <p class="font-semibold text-gray-600 dark:text-gray-300">
                                    {{ item.formatted_price }}
                                </p>

                                <p class="text-base font-semibold text-gray-800 dark:text-white">
                                    {{ item.formatted_revenue }}
                                </p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Empty Product Design -->
                <div
                    class="flex flex-col gap-8 p-4"
                    v-else
                >
                    <div class="grid justify-center justify-items-center gap-3.5 py-2.5">
                        <!-- Placeholder Image -->
                        <img
                            src="<?php echo e(bagisto_asset('images/icon-add-product.svg')); ?>"
                            class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                        >

                        <!-- Add Variants Information -->
                        <div class="flex flex-col items-center">
                            <p class="text-base font-semibold text-gray-400">
                                <?php echo app('translator')->get('admin::app.dashboard.index.add-product'); ?>
                            </p>

                            <p class="text-gray-400">
                                <?php echo app('translator')->get('admin::app.dashboard.index.product-info'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </script>

    <script type="module">
        app.component('v-dashboard-top-selling-products', {
            template: '#v-dashboard-top-selling-products-template',

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

                    filters.type = 'top-selling-products';

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
<?php $__env->stopPush(); endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/dashboard/top-selling-products.blade.php ENDPATH**/ ?>