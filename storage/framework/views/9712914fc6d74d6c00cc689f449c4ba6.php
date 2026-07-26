<v-datagrid-search
    :is-loading="isLoading"
    :available="available"
    :applied="applied"
    @search="search"
>
    <?php echo e($slot); ?>

</v-datagrid-search>

<?php if (! $__env->hasRenderedOnce('c72c8adb-3880-473f-b736-5e4b6cf94026')): $__env->markAsRenderedOnce('c72c8adb-3880-473f-b736-5e4b6cf94026');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-datagrid-search-template"
    >
        <!-- Empty slot for left toolbar before -->
        <slot name="left-toolbar-left-before"></slot>
        
        <slot
            name="search"
            :available="available"
            :applied="applied"
            :search="search"
            :get-searched-values="getSearchedValues"
        >
            <template v-if="isLoading">
                <?php if (isset($component)) { $__componentOriginal8ce9e918ea587709cbb1111cee6c8506 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8ce9e918ea587709cbb1111cee6c8506 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.shimmer.datagrid.toolbar.search','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::shimmer.datagrid.toolbar.search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8ce9e918ea587709cbb1111cee6c8506)): ?>
<?php $attributes = $__attributesOriginal8ce9e918ea587709cbb1111cee6c8506; ?>
<?php unset($__attributesOriginal8ce9e918ea587709cbb1111cee6c8506); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8ce9e918ea587709cbb1111cee6c8506)): ?>
<?php $component = $__componentOriginal8ce9e918ea587709cbb1111cee6c8506; ?>
<?php unset($__componentOriginal8ce9e918ea587709cbb1111cee6c8506); ?>
<?php endif; ?>
            </template>

            <template v-else>
                <div class="flex w-full items-center gap-x-1">
                    <!-- Search Panel -->
                    <div class="flex max-w-[445px] items-center max-sm:w-full max-sm:max-w-full">
                        <div class="relative w-full">
                            <input
                                type="text"
                                name="search"
                                :value="getSearchedValues('all')"
                                class="block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                                :class="getSearchedValues('all')?.length ? 'border-blue-600' : ''"
                                placeholder="<?php echo app('translator')->get('admin::app.components.datagrid.toolbar.search.title'); ?>"
                                autocomplete="off"
                                @keyup.enter="search"
                            >

                            <div class="icon-search pointer-events-none absolute top-2 flex items-center text-2xl ltr:right-2.5 rtl:left-2.5">
                            </div>
                        </div>
                    </div>

                    <!-- Information Panel -->
                    <div class="ltr:pl-2.5 rtl:pr-2.5">
                        <p class="text-sm font-light text-gray-800 dark:text-white">
                            {{ "<?php echo app('translator')->get('admin::app.components.datagrid.toolbar.results'); ?>".replace(':total', available.meta.total) }}
                        </p>
                    </div>
                </div>
            </template>
        </slot>

        <!-- Empty slot for left toolbar after -->
        <slot name="left-toolbar-left-after"></slot>
    </script>

    <script type="module">
        app.component('v-datagrid-search', {
            template: '#v-datagrid-search-template',

            props: ['isLoading', 'available', 'applied'],

            emits: ['search'],

            data() {
                return {
                    filters: {
                        columns: [],
                    },
                };
            },

            mounted() {
                this.filters.columns = this.applied.filters.columns.filter((column) => column.index === 'all');
            },

            methods: {
                /**
                 * Perform a search operation based on the input value.
                 *
                 * @param {Event} $event
                 * @returns {void}
                 */
                search($event) {
                    let requestedValue = $event.target.value;

                    let appliedColumn = this.filters.columns.find(column => column.index === 'all');

                    if (! requestedValue) {
                        appliedColumn.value = [];

                        this.$emit('search', this.filters);

                        return;
                    }

                    if (appliedColumn) {
                        appliedColumn.value = [requestedValue];
                    } else {
                        this.filters.columns.push({
                            index: 'all',
                            value: [requestedValue]
                        });
                    }

                    this.$emit('search', this.filters);
                },

                /**
                 * Get the searched values for a specific column.
                 *
                 * @param {string} columnIndex
                 * @returns {Array}
                 */
                getSearchedValues(columnIndex) {
                    let appliedColumn = this.filters.columns.find(column => column.index === 'all');

                    return appliedColumn?.value ?? [];
                },
            },
        });
    </script>
<?php $__env->stopPush(); endif; ?>
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/components/datagrid/toolbar/search.blade.php ENDPATH**/ ?>