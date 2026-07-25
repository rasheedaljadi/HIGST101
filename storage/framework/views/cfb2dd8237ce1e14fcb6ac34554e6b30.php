<?php if (isset($component)) { $__componentOriginal2643b7d197f48caff2f606750db81304 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2643b7d197f48caff2f606750db81304 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'shop::components.layouts.index','data' => ['hasHeader' => false,'hasFeature' => false,'hasFooter' => false]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shop::layouts'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['has-header' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'has-feature' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'has-footer' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false)]); ?>
    <!-- Page Title -->
     <?php $__env->slot('title', null, []); ?> 
        <?php echo app('translator')->get("shop::app.errors.{$errorCode}.title"); ?>
     <?php $__env->endSlot(); ?>

    <!-- Error page Information -->
	<div class="container absolute left-1/2 top-0 -translate-x-1/2 px-[60px] max-lg:px-8 max-sm:px-4">
		<div class="grid h-[100vh] w-full">
			<div class="wrapper-404 max-868:!text-[294px] max-md:!text-[140px]">
				<div class="glow-404">
                    <?php echo e($errorCode); ?>

                </div>

				<div class="glow-shadow-404">
                    <?php echo e($errorCode); ?>

                </div>
			</div>

            <div class="absolute left-1/2 top-[74%] mt-10 -translate-x-1/2 -translate-y-1/2 text-center max-868:w-full max-md:top-[60%]">
                <h1 class="text-3xl font-semibold max-md:text-xl">
                    <?php echo app('translator')->get("shop::app.errors.{$errorCode}.title"); ?>
                </h1>

                <p class="mt-4 text-lg text-zinc-500 max-md:text-sm">
                    <?php echo e($errorCode === 503 && core()->getCurrentChannel()->maintenance_mode_text != ""
                        ? core()->getCurrentChannel()->maintenance_mode_text : trans("shop::app.errors.{$errorCode}.description")); ?>

                </p>

                <a 
                    href="<?php echo e(route('shop.home.index')); ?>"
                    class="m-auto mt-8 block w-max cursor-pointer rounded-[45px] bg-navyBlue px-10 py-4 text-center text-base font-medium text-white max-sm:mb-10 max-sm:px-6 max-sm:text-sm"
                >
                    <?php echo app('translator')->get('shop::app.errors.go-to-home'); ?> 
                </a>
            </div>
		</div>
	</div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2643b7d197f48caff2f606750db81304)): ?>
<?php $attributes = $__attributesOriginal2643b7d197f48caff2f606750db81304; ?>
<?php unset($__attributesOriginal2643b7d197f48caff2f606750db81304); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2643b7d197f48caff2f606750db81304)): ?>
<?php $component = $__componentOriginal2643b7d197f48caff2f606750db81304; ?>
<?php unset($__componentOriginal2643b7d197f48caff2f606750db81304); ?>
<?php endif; ?><?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Shop\src/resources/views/errors/index.blade.php ENDPATH**/ ?>