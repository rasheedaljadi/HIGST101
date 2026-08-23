<?php
    $tabs = menu()->getCurrentActiveMenu('admin')?->getChildren();
?>

<?php if(
    $tabs
    && $tabs->isNotEmpty()
): ?>
    <div class="tabs">
        <div class="mb-4 flex gap-4 border-b-2 pt-2 dark:border-gray-800 max-sm:hidden">
            <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e($tab->getUrl()); ?>">
                    <div class="<?php echo e($tab->isActive() ? "-mb-px border-blue-600 border-b-2 transition" : ''); ?> pb-3.5 px-2.5 text-base font-medium text-gray-600 dark:text-gray-300 cursor-pointer">
                        <?php echo e($tab->getName()); ?>

                    </div>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/components/layouts/tabs.blade.php ENDPATH**/ ?>