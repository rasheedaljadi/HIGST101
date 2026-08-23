<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['count' => 30]));

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

foreach (array_filter((['count' => 30]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="grid w-full gap-4 border-b px-4 py-2 dark:border-gray-800">
    <!-- Total Sales -->
    <div class="flex h-[38px] w-full justify-between gap-2">
        <div class="flex flex-col justify-between gap-1">
            <div class="shimmer h-[17px] w-[85px]"></div>

            <!-- Total Sales Amount -->
            <div class="shimmer h-[17px] w-[85px]"></div>
        </div>

        <div class="flex flex-col justify-between gap-1">
            <!-- Date -->
            <div class="shimmer h-[17px] w-[83px]"></div>

            <!-- Total Orders -->
            <div class="shimmer h-[17px] w-14 self-end"></div>
        </div>
    </div>

    <!-- Graph Chart -->

    <div class="flex gap-1.5">
        <div class="grid">
            <?php $__currentLoopData = range(1, 10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="shimmer h-2.5 w-[34px]">
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="grid w-full gap-1.5">
            <div class="flex aspect-[2] h-[180px] w-[285px] items-end border-b border-l pl-2.5 dark:border-gray-800">
                <div class="flex aspect-[2] w-full items-end justify-between gap-2.5">
                    <?php $__currentLoopData = range(1, 14); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="shimmer flex w-full" style="height: <?php echo e(rand(10, 100)); ?>%"></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="flex justify-between gap-5 pl-2.5 max-lg:gap-4 max-sm:gap-2.5">
                <?php $__currentLoopData = range(1, 10); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="shimmer mt-1 flex h-[42px] w-full rotate-45"></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</div><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/components/shimmer/dashboard/total-sales.blade.php ENDPATH**/ ?>