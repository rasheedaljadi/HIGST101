<v-button <?php echo e($attributes); ?>></v-button>

<?php if (! $__env->hasRenderedOnce('910eb7f3-0629-41e6-8a4d-9efae020cb49')): $__env->markAsRenderedOnce('910eb7f3-0629-41e6-8a4d-9efae020cb49');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-button-template"
    >
        <button
            v-if="! loading"
            :class="[buttonClass, '']"
        >
            {{ title }}
        </button>

        <button
            v-else
            :class="[buttonClass, '']"
        >
            <!-- Spinner -->
            <svg
                class="text-blue absolute h-5 w-5 animate-spin"
                xmlns="http://www.w3.org/2000/svg"
                fill="none" 
                aria-hidden="true"
                viewBox="0 0 24 24"
            >
                <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                >
                </circle>

                <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                >
                </path>
            </svg>

            <span class="relative h-full w-full opacity-0">
                {{ title }}
            </span>
        </button>
    </script>

    <script type="module">
        app.component('v-button', {
            template: '#v-button-template',

            props: {
                loading: Boolean,
                buttonType: String,
                title: String,
                buttonClass: String,
            },
        });
    </script>
<?php $__env->stopPush(); endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Shop\src/resources/views/components/button/index.blade.php ENDPATH**/ ?>