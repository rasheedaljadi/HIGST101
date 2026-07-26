<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'type' => 'text',
    'name' => '',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'type' => 'text',
    'name' => '',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php switch($type):
    case ('hidden'): ?>
    <?php case ('text'): ?>
    <?php case ('email'): ?>
    <?php case ('password'): ?>
    <?php case ('number'): ?>
        <v-field
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <input
                type="<?php echo e($type); ?>"
                name="<?php echo e($name); ?>"
                v-bind="field"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400'])); ?>

            />
        </v-field>

        <?php break; ?>

    <?php case ('price'): ?>
        <v-field
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <div
                class="flex w-full items-center overflow-hidden rounded-md border text-sm text-gray-600 transition-all focus-within:border-gray-400 hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
            >
                <?php if(isset($currency)): ?>
                    <span <?php echo e($currency->attributes->merge(['class' => 'py-2.5 text-gray-500 ltr:pl-4 rtl:pr-4'])); ?>>
                        <?php echo e($currency); ?>

                    </span>
                <?php else: ?>
                    <span class="py-2.5 text-gray-500 ltr:pl-4 rtl:pr-4">
                        <?php echo e(core()->currencySymbol(core()->getBaseCurrencyCode())); ?>

                    </span>
                <?php endif; ?>

                <input
                    type="text"
                    name="<?php echo e($name); ?>"
                    v-bind="field"
                    <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full p-2.5 text-sm text-gray-600 dark:bg-gray-900 dark:text-gray-300'])); ?>

                />
            </div>
        </v-field>

        <?php break; ?>

    <?php case ('file'): ?>
        <v-field
            v-slot="{ field, errors, handleChange, handleBlur }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <input
                type="<?php echo e($type); ?>"
                v-bind="{ name: field.name }"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:file:bg-gray-800 dark:file:dark:text-white dark:hover:border-gray-400 dark:focus:border-gray-400'])); ?>

                @change="handleChange"
                @blur="handleBlur"
            />
        </v-field>

        <?php break; ?>

    <?php case ('color'): ?>
        <v-field
            name="<?php echo e($name); ?>"
            v-slot="{ field, errors }"
            <?php echo e($attributes->except('class')); ?>

        >
            <input
                type="<?php echo e($type); ?>"
                :class="[errors.length ? 'border border-red-500' : '']"
                v-bind="field"
                <?php echo e($attributes->except(['value'])->merge(['class' => 'w-full appearance-none rounded-md border text-sm text-gray-600 transition-all hover:border-gray-400 dark:text-gray-300 dark:hover:border-gray-400'])); ?>

            >
        </v-field>
        <?php break; ?>

    <?php case ('textarea'): ?>
        <v-field
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <textarea
                type="<?php echo e($type); ?>"
                name="<?php echo e($name); ?>"
                v-bind="field"
                :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400'])); ?>

            >
            </textarea>

            <?php if($attributes->get('tinymce', false) || $attributes->get(':tinymce', false)): ?>
                <?php if (isset($component)) { $__componentOriginaldb934b2de52c88901e70622af3007667 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldb934b2de52c88901e70622af3007667 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.tinymce.index','data' => ['selector' => 'textarea#' . $attributes->get('id'),'prompt' => stripcslashes($attributes->get('prompt', '')),':field' => 'field']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::tinymce'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['selector' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute('textarea#' . $attributes->get('id')),'prompt' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(stripcslashes($attributes->get('prompt', ''))),':field' => 'field']); ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldb934b2de52c88901e70622af3007667)): ?>
<?php $attributes = $__attributesOriginaldb934b2de52c88901e70622af3007667; ?>
<?php unset($__attributesOriginaldb934b2de52c88901e70622af3007667); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldb934b2de52c88901e70622af3007667)): ?>
<?php $component = $__componentOriginaldb934b2de52c88901e70622af3007667; ?>
<?php unset($__componentOriginaldb934b2de52c88901e70622af3007667); ?>
<?php endif; ?>
            <?php endif; ?>
        </v-field>

        <?php break; ?>

    <?php case ('date'): ?>
        <v-field
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <?php if (isset($component)) { $__componentOriginalfb6be9e824dd35fb24e37e299d255b9b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfb6be9e824dd35fb24e37e299d255b9b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.flat-picker.date','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::flat-picker.date'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                <input
                    name="<?php echo e($name); ?>"
                    v-bind="field"
                    :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                    <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400'])); ?>

                    autocomplete="off"
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
        </v-field>

        <?php break; ?>

    <?php case ('datetime'): ?>
        <v-field
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <?php if (isset($component)) { $__componentOriginal2199caa745350ee1c77f1b71e4499f91 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2199caa745350ee1c77f1b71e4499f91 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.flat-picker.datetime','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::flat-picker.datetime'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                <input
                    name="<?php echo e($name); ?>"
                    v-bind="field"
                    :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                    <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'w-full rounded-md border px-3 py-2.5 text-sm text-gray-600 transition-all hover:border-gray-400 focus:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400 dark:focus:border-gray-400'])); ?>

                    autocomplete="off"
                >
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2199caa745350ee1c77f1b71e4499f91)): ?>
<?php $attributes = $__attributesOriginal2199caa745350ee1c77f1b71e4499f91; ?>
<?php unset($__attributesOriginal2199caa745350ee1c77f1b71e4499f91); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2199caa745350ee1c77f1b71e4499f91)): ?>
<?php $component = $__componentOriginal2199caa745350ee1c77f1b71e4499f91; ?>
<?php unset($__componentOriginal2199caa745350ee1c77f1b71e4499f91); ?>
<?php endif; ?>
        </v-field>
        <?php break; ?>

    <?php case ('time'): ?>
        <v-field
            name="<?php echo e($name); ?>"
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

        >
            <?php if (isset($component)) { $__componentOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.flat-picker.time','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::flat-picker.time'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                <input
                    type="time"
                    name="<?php echo e($name); ?>"
                    v-bind="field"
                    :class="[errors.length ? 'border !border-red-600 hover:border-red-600' : '']"
                    <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'flex w-full min-h-[39px] py-2.5 px-3 border rounded-md text-sm text-gray-600 dark:text-gray-300 transition-all hover:border-gray-400 dark:hover:border-gray-400 focus:border-gray-400 dark:focus:border-gray-400 dark:bg-gray-900 dark:border-gray-800'])); ?>

                    autocomplete="off"
                >
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2)): ?>
<?php $attributes = $__attributesOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2; ?>
<?php unset($__attributesOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2)): ?>
<?php $component = $__componentOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2; ?>
<?php unset($__componentOriginal4a45f9c7cd14134f7a3b185c8d5c2cf2); ?>
<?php endif; ?>
        </v-field>
        <?php break; ?>

    <?php case ('select'): ?>
        <v-field
            v-slot="{ field, errors }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])); ?>

            name="<?php echo e($name); ?>"
        >
            <select
                name="<?php echo e($name); ?>"
                v-bind="field"
                :class="[errors.length ? 'border border-red-500' : '']"
                <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label'])->merge(['class' => 'custom-select w-full rounded-md border bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400'])); ?>

            >
                <?php echo e($slot); ?>

            </select>
        </v-field>

        <?php break; ?>

    <?php case ('multiselect'): ?>
        <v-field
            as="select"
            v-slot="{ value }"
            :class="[errors && errors['<?php echo e($name); ?>'] ? 'border !border-red-600 hover:border-red-600' : '']"
            <?php echo e($attributes->except([])->merge(['class' => 'flex w-full flex-col rounded-md border bg-white px-3 py-2.5 text-sm font-normal text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-gray-400'])); ?>

            name="<?php echo e($name); ?>"
            multiple
        >
            <?php echo e($slot); ?>

        </v-field>

        <?php break; ?>

    <?php case ('checkbox'): ?>
        <v-field
            v-slot="{ field }"
            type="checkbox"
            class="hidden"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label', 'key', ':key'])); ?>

            name="<?php echo e($name); ?>"
        >
            <input
                type="checkbox"
                name="<?php echo e($name); ?>"
                v-bind="field"
                class="peer sr-only"
                <?php echo e($attributes->except(['rules', 'label', ':label', 'key', ':key'])); ?>

            />

            <v-checked-handler
                :field="field"
                checked="<?php echo e($attributes->get('checked')); ?>"
            >
            </v-checked-handler>
        </v-field>

        <label
             <?php echo e($attributes
                    ->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label', 'key', ':key'])
                    ->merge(['class' => 'icon-uncheckbox peer-checked:icon-checked text-2xl peer-checked:text-blue-600'])
                    ->merge(['class' => $attributes->get('disabled') ? 'cursor-not-allowed opacity-70' : 'cursor-pointer'])); ?>

        >
        </label>

        <?php break; ?>

    <?php case ('radio'): ?>
        <v-field
            type="radio"
            class="hidden"
            v-slot="{ field }"
            <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label', 'key', ':key'])); ?>

            name="<?php echo e($name); ?>"
        >
            <input
                type="radio"
                name="<?php echo e($name); ?>"
                v-bind="field"
                class="peer sr-only"
                <?php echo e($attributes->except(['rules', 'label', ':label', 'key', ':key'])); ?>

            />

            <v-checked-handler
                class="hidden"
                :field="field"
                checked="<?php echo e($attributes->get('checked')); ?>"
            >
            </v-checked-handler>
        </v-field>

        <label
            class="icon-radio-normal peer-checked:icon-radio-selected cursor-pointer text-2xl peer-checked:text-blue-600"
            <?php echo e($attributes->except(['value', ':value', 'v-model', 'rules', ':rules', 'label', ':label', 'key', ':key'])); ?>

        >
        </label>

        <?php break; ?>

    <?php case ('switch'): ?>
        <label class="relative inline-flex cursor-pointer items-center">
            <v-field
                type="checkbox"
                class="hidden"
                v-slot="{ field }"
                <?php echo e($attributes->only(['name', ':name', 'value', ':value', 'v-model', 'rules', ':rules', 'label', ':label', 'key', ':key'])); ?>

                name="<?php echo e($name); ?>"
            >
                <input
                    type="checkbox"
                    name="<?php echo e($name); ?>"
                    id="<?php echo e($name); ?>"
                    class="peer sr-only"
                    v-bind="field"
                    <?php echo e($attributes->except(['v-model', 'rules', ':rules', 'label', ':label', 'key', ':key'])); ?>

                />

                <v-checked-handler
                    class="hidden"
                    :field="field"
                    checked="<?php echo e($attributes->get('checked')); ?>"
                >
                </v-checked-handler>
            </v-field>

            <label
                class="peer h-5 w-9 cursor-pointer rounded-full bg-gray-200 after:absolute after:top-0.5 after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-blue-300 dark:bg-gray-800 dark:after:border-white dark:after:bg-white dark:peer-checked:bg-gray-950 after:ltr:left-0.5 peer-checked:after:ltr:translate-x-full after:rtl:right-0.5 peer-checked:after:rtl:-translate-x-full"
                for="<?php echo e($name); ?>"
            ></label>
        </label>

        <?php break; ?>

    <?php case ('image'): ?>
        <?php if (isset($component)) { $__componentOriginal0d8f93bc2c41813121bcd9fcb7359a7d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0d8f93bc2c41813121bcd9fcb7359a7d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.media.images','data' => ['name' => ''.e($name).'',':class' => '[errors && errors[\''.e($name).'\'] ? \'border !border-red-600 hover:border-red-600\' : \'\']','attributes' => $attributes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::media.images'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => ''.e($name).'',':class' => '[errors && errors[\''.e($name).'\'] ? \'border !border-red-600 hover:border-red-600\' : \'\']','attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0d8f93bc2c41813121bcd9fcb7359a7d)): ?>
<?php $attributes = $__attributesOriginal0d8f93bc2c41813121bcd9fcb7359a7d; ?>
<?php unset($__attributesOriginal0d8f93bc2c41813121bcd9fcb7359a7d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0d8f93bc2c41813121bcd9fcb7359a7d)): ?>
<?php $component = $__componentOriginal0d8f93bc2c41813121bcd9fcb7359a7d; ?>
<?php unset($__componentOriginal0d8f93bc2c41813121bcd9fcb7359a7d); ?>
<?php endif; ?>

        <?php break; ?>

    <?php case ('custom'): ?>
        <v-field <?php echo e($attributes); ?>>
            <?php echo e($slot); ?>

        </v-field>
<?php endswitch; ?>

<?php if (! $__env->hasRenderedOnce('b6e9a336-df78-4341-b683-d7d743c3061e')): $__env->markAsRenderedOnce('b6e9a336-df78-4341-b683-d7d743c3061e');
$__env->startPush('scripts'); ?>
    <script
        type="text/x-template"
        id="v-checked-handler-template"
    >
    </script>

    <script type="module">
        app.component('v-checked-handler', {
            template: '#v-checked-handler-template',

            props: ['field', 'checked'],

            mounted() {
                if (this.checked == '') {
                    return;
                }

                this.field.checked = true;

                this.field.onChange();
            },
        });
    </script>
<?php $__env->stopPush(); endif; ?>
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/components/form/control-group/control.blade.php ENDPATH**/ ?>