<?php if (isset($component)) { $__componentOriginal3ad46025fb3e01e4c2eb9d1732f00674 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ad46025fb3e01e4c2eb9d1732f00674 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.layouts.anonymous','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::layouts.anonymous'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
    <!-- Page Title -->
     <?php $__env->slot('title', null, []); ?> 
        <?php echo app('translator')->get("admin::app.errors.{$errorCode}.title"); ?>
     <?php $__env->endSlot(); ?>

    <!-- Error page Information -->
	<div class="flex h-[100vh] items-center justify-center bg-white dark:bg-gray-900">
        <div class="flex max-w-[745px] items-center gap-5">
            <div class="w-full">
                <?php
                    $logoUrl = core()->getConfigData('general.design.admin_logo.logo_image') 
                                ? Storage::url(core()->getConfigData('general.design.admin_logo.logo_image')) 
                                : bagisto_asset('images/logo.svg');
                ?>

                <img
                    class="mb-6 h-10"
                    src="<?php echo e($logoUrl); ?>"
                    id="logo-image"
                    alt="<?php echo e(config('app.name')); ?>"
                />

				<div class="text-[38px] font-bold text-gray-800 dark:text-white">
                    <?php echo e($errorCode); ?>

                </div>

                <p class="mb-6 text-sm text-gray-800">
                    <?php echo app('translator')->get("admin::app.errors.{$errorCode}.description"); ?>
                </p>

                <div class="mb-6">
                    <div class="flex items-center gap-2.5">
                        <a
                            onclick="history.back()"
                            class="text-sm font-semibold text-blue-600 transition-all hover:underline"
                        >
                            <?php echo app('translator')->get('admin::app.errors.go-back'); ?>
                        </a>

                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="6" height="7" viewBox="0 0 6 7" fill="none">
                                <circle cx="3" cy="3.5" r="3" fill="#9CA3AF"/>
                            </svg>
                        </span>

                        <a
                            href="<?php echo e(route('admin.dashboard.index')); ?>"
                            class="text-sm font-semibold text-blue-600 transition-all hover:underline"
                        >
                            <?php echo app('translator')->get('admin::app.errors.dashboard'); ?>
                        </a>
                    </div>
                </div>

                <p class="text-sm text-gray-800">
                    <?php echo app('translator')->get('admin::app.errors.support', [
                        'link'  => 'mailto:' . (core()->getAdminEmailDetails()['email'] ?? 'support@example.com'),
                        'email' => core()->getAdminEmailDetails()['email'] ?? 'support@example.com',
                        'class' => 'font-semibold text-blue-600 transition-all hover:underline',
                    ]); ?>
                </p>
            </div>

            <div class="w-full">
                <img src="<?php echo e(bagisto_asset('images/error.svg')); ?>" />
            </div>
        </div>
	</div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3ad46025fb3e01e4c2eb9d1732f00674)): ?>
<?php $attributes = $__attributesOriginal3ad46025fb3e01e4c2eb9d1732f00674; ?>
<?php unset($__attributesOriginal3ad46025fb3e01e4c2eb9d1732f00674); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3ad46025fb3e01e4c2eb9d1732f00674)): ?>
<?php $component = $__componentOriginal3ad46025fb3e01e4c2eb9d1732f00674; ?>
<?php unset($__componentOriginal3ad46025fb3e01e4c2eb9d1732f00674); ?>
<?php endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Admin\src/resources/views/errors/index.blade.php ENDPATH**/ ?>