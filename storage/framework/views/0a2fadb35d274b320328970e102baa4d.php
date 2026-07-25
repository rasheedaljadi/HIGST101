<!--
    This code needs to be refactored to reduce the amount of PHP in the Blade
    template as much as possible.
-->
<?php
    $showCompare = (bool) core()->getConfigData('catalog.products.settings.compare_option');

    $showWishlist = (bool) core()->getConfigData('customer.settings.wishlist.wishlist_option');
?>

<div class="flex flex-wrap gap-4 px-4 pt-6 pb-4 shadow-sm lg:hidden">
    <div class="flex items-center justify-between w-full">
        <!-- Left Navigation -->
        <div class="flex items-center gap-x-1.5">
            <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.before'); ?>


            <!-- Drawer -->
            <v-mobile-drawer></v-mobile-drawer>

            <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.after'); ?>


            <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.logo.before'); ?>


            <a
                href="<?php echo e(route('shop.home.index')); ?>"
                class="max-h-[30px]"
                aria-label="<?php echo app('translator')->get('shop::app.components.layouts.header.mobile.bagisto'); ?>"
            >
                <img
                    src="<?php echo e(core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg')); ?>"
                    alt="<?php echo e(config('app.name')); ?>"
                    width="131"
                    height="29"
                >
            </a>

            <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.logo.after'); ?>

        </div>

        <!-- Right Navigation -->
        <div>
            <div class="flex items-center gap-x-5 max-md:gap-x-4">
                <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.compare.before'); ?>


                <?php if($showCompare): ?>
                    <a
                        href="<?php echo e(route('shop.compare.index')); ?>"
                        aria-label="<?php echo app('translator')->get('shop::app.components.layouts.header.mobile.compare'); ?>"
                    >
                        <span class="text-2xl cursor-pointer icon-compare"></span>
                    </a>
                <?php endif; ?>

                <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.compare.after'); ?>


                <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.before'); ?>


                <?php if(core()->getConfigData('sales.checkout.shopping_cart.cart_page')): ?>
                    <?php echo $__env->make('shop::checkout.cart.mini-cart', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>

                <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.mini_cart.after'); ?>


                <!-- For Large screens -->
                <div class="max-md:hidden">
                    <?php if (isset($component)) { $__componentOriginal6eb652d0a4a36e6466d8d4f363feb553 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6eb652d0a4a36e6466d8d4f363feb553 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'shop::components.dropdown.index','data' => ['position' => 'bottom-'.e(core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shop::dropdown'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom-'.e(core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left').'']); ?>
                         <?php $__env->slot('toggle', null, []); ?> 
                            <span class="text-2xl cursor-pointer icon-users"></span>
                             <?php $__env->endSlot(); ?>

                            <!-- Guest Dropdown -->
                            <?php if(auth()->guard('customer')->guest()): ?>
                                 <?php $__env->slot('content', null, []); ?> 
                                    <div class="grid gap-2.5">
                                        <p class="text-xl font-dmserif">
                                            <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.welcome-guest'); ?>
                                        </p>

                                        <p class="text-sm">
                                            <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.dropdown-text'); ?>
                                        </p>
                                    </div>

                                    <p class="w-full mt-3 border border-zinc-200"></p>

                                    <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.before'); ?>


                                    <div class="flex gap-4 mt-6">
                                        <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.before'); ?>


                                    <a
                                        href="<?php echo e(route('shop.customer.session.create')); ?>"
                                        class="block py-4 m-0 mx-auto text-base font-medium text-center text-white cursor-pointer w-max rounded-2xl bg-navyBlue px-7 ltr:ml-0 rtl:mr-0"
                                    >
                                            <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.sign-in'); ?>
                                        </a>

                                    <a
                                        href="<?php echo e(route('shop.customers.register.index')); ?>"
                                        class="m-0 mx-auto block w-max cursor-pointer rounded-2xl border-2 border-navyBlue bg-white px-7 py-3.5 text-center text-base font-medium text-navyBlue ltr:ml-0 rtl:mr-0"
                                    >
                                            <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.sign-up'); ?>
                                        </a>

                                        <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.index.sign_in_button.after'); ?>

                                    </div>

                                    <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.index.customers_action.after'); ?>

                                     <?php $__env->endSlot(); ?>
                            <?php endif; ?>

                                <!-- Customers Dropdown -->
                                <?php if(auth()->guard('customer')->check()): ?>
                                     <?php $__env->slot('content', null, ['class' => '!p-0']); ?> 
                                        <div class="grid gap-2.5 p-5 pb-0">
                                            <p class="text-xl font-dmserif" v-pre>
                                        <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.welcome'); ?>’
                                                <?php echo e(auth()->guard('customer')->user()->first_name); ?>

                                            </p>

                                            <p class="text-sm">
                                                <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.dropdown-text'); ?>
                                            </p>
                                        </div>

                                        <p class="w-full mt-3 border border-zinc-200"></p>

                                        <div class="mt-2.5 grid gap-1 pb-2.5">
                                            <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.before'); ?>


                                    <a
                                        class="px-5 py-2 text-base cursor-pointer"
                                        href="<?php echo e(route('shop.customers.account.profile.index')); ?>"
                                    >
                                                <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.profile'); ?>
                                            </a>

                                    <a
                                        class="px-5 py-2 text-base cursor-pointer"
                                        href="<?php echo e(route('shop.customers.account.orders.index')); ?>"
                                    >
                                                <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.orders'); ?>
                                            </a>

                                            <?php if($showWishlist): ?>
                                        <a
                                            class="px-5 py-2 text-base cursor-pointer"
                                            href="<?php echo e(route('shop.customers.account.wishlist.index')); ?>"
                                        >
                                                    <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.wishlist'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <!--Customers logout-->
                                            <?php if(auth()->guard('customer')->check()): ?>
                                        <?php if (isset($component)) { $__componentOriginal4d3fcee3e355fb6c8889181b04f357cc = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4d3fcee3e355fb6c8889181b04f357cc = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'shop::components.form.index','data' => ['method' => 'DELETE','action' => ''.e(route('shop.customer.session.destroy')).'','id' => 'customerLogout']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shop::form'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['method' => 'DELETE','action' => ''.e(route('shop.customer.session.destroy')).'','id' => 'customerLogout']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4d3fcee3e355fb6c8889181b04f357cc)): ?>
<?php $attributes = $__attributesOriginal4d3fcee3e355fb6c8889181b04f357cc; ?>
<?php unset($__attributesOriginal4d3fcee3e355fb6c8889181b04f357cc); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4d3fcee3e355fb6c8889181b04f357cc)): ?>
<?php $component = $__componentOriginal4d3fcee3e355fb6c8889181b04f357cc; ?>
<?php unset($__componentOriginal4d3fcee3e355fb6c8889181b04f357cc); ?>
<?php endif; ?>

                                        <a
                                            class="px-5 py-2 text-base cursor-pointer"
                                                    href="<?php echo e(route('shop.customer.session.destroy')); ?>"
                                            onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                                        >
                                                    <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.logout'); ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.index.profile_dropdown.links.after'); ?>

                                        </div>
                                         <?php $__env->endSlot(); ?>
                                <?php endif; ?>
                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6eb652d0a4a36e6466d8d4f363feb553)): ?>
<?php $attributes = $__attributesOriginal6eb652d0a4a36e6466d8d4f363feb553; ?>
<?php unset($__attributesOriginal6eb652d0a4a36e6466d8d4f363feb553); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6eb652d0a4a36e6466d8d4f363feb553)): ?>
<?php $component = $__componentOriginal6eb652d0a4a36e6466d8d4f363feb553; ?>
<?php unset($__componentOriginal6eb652d0a4a36e6466d8d4f363feb553); ?>
<?php endif; ?>
                </div>

                <!-- For Medium and small screen -->
                <div class="md:hidden">
                    <?php if(auth()->guard('customer')->guest()): ?>
                        <a
                            href="<?php echo e(route('shop.customer.session.create')); ?>"
                            aria-label="<?php echo app('translator')->get('shop::app.components.layouts.header.mobile.account'); ?>"
                        >
                            <span class="text-2xl cursor-pointer icon-users"></span>
                        </a>
                    <?php endif; ?>

                    <!-- Customers Dropdown -->
                    <?php if(auth()->guard('customer')->check()): ?>
                        <a
                            href="<?php echo e(route('shop.customers.account.index')); ?>"
                            aria-label="<?php echo app('translator')->get('shop::app.components.layouts.header.mobile.account'); ?>"
                        >
                            <span class="text-2xl cursor-pointer icon-users"></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.search.before'); ?>


    <!-- Serach Catalog Form -->
    <form action="<?php echo e(route('shop.search.index')); ?>" class="flex items-center w-full">
        <label
            for="organic-search"
            class="sr-only"
        >
            <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.search'); ?>
        </label>

        <div class="relative w-full">
            <div class="icon-search pointer-events-none absolute top-3 flex items-center text-2xl max-md:text-xl max-sm:top-2.5 ltr:left-3 rtl:right-3"></div>

            <input
                type="text"
                class="block w-full rounded-xl border border-['#E3E3E3'] px-11 py-3.5 text-sm font-medium text-gray-900 max-md:rounded-lg max-md:px-10 max-md:py-3 max-md:font-normal max-sm:text-xs"
                name="query"
                value="<?php echo e(request('query')); ?>"
                placeholder="<?php echo app('translator')->get('shop::app.components.layouts.header.mobile.search-text'); ?>"
                required
            >

            <?php if(core()->getConfigData('catalog.products.settings.image_search')): ?>
                <?php echo $__env->make('shop::search.images.index', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endif; ?>
        </div>
    </form>

    <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.search.after'); ?>

</div>

<?php if (! $__env->hasRenderedOnce('90d7c46e-1e46-41ef-a174-a787127a2b66')): $__env->markAsRenderedOnce('90d7c46e-1e46-41ef-a174-a787127a2b66');
$__env->startPush('scripts'); ?>
    <script type="text/x-template" id="v-mobile-drawer-template">
            <?php if (isset($component)) { $__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'shop::components.drawer.index','data' => ['position' => 'left','width' => '100%','@close' => 'onDrawerClose']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shop::drawer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'left','width' => '100%','@close' => 'onDrawerClose']); ?>
                 <?php $__env->slot('toggle', null, []); ?> 
                    <span class="text-2xl cursor-pointer icon-hamburger"></span>
                 <?php $__env->endSlot(); ?>

                 <?php $__env->slot('header', null, []); ?> 
                    <div class="flex items-center justify-between">
                        <a href="<?php echo e(route('shop.home.index')); ?>">
                            <img
                                src="<?php echo e(core()->getCurrentChannel()->logo_url ?? bagisto_asset('images/logo.svg')); ?>"
                                alt="<?php echo e(config('app.name')); ?>"
                                width="131"
                                height="29"
                            >
                        </a>
                    </div>
                 <?php $__env->endSlot(); ?>

                 <?php $__env->slot('content', null, ['class' => '!p-0']); ?> 
                    <!-- Account Profile Hero Section -->
                    <div class="p-4 border-b border-zinc-200">
                        <div class="grid grid-cols-[auto_1fr] items-center gap-4 rounded-xl border border-zinc-200 p-2.5">
                            <div>
                                <img
                                src="<?php echo e(auth()->user()?->image_url ??  bagisto_asset('images/user-placeholder.png')); ?>"
                                    class="h-[60px] w-[60px] rounded-full max-md:rounded-full"
                                >
                            </div>

                            <?php if(auth()->guard('customer')->guest()): ?>
                                <a
                                    href="<?php echo e(route('shop.customer.session.create')); ?>"
                                    class="flex text-base font-medium"
                                >
                                    <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.login'); ?>

                                    <i class="icon-double-arrow text-2xl ltr:ml-2.5 rtl:mr-2.5"></i>
                                </a>
                            <?php endif; ?>

                            <?php if(auth()->guard('customer')->check()): ?>
                                <div
                                    class="flex flex-col justify-between gap-2.5 max-md:gap-0"
                                    v-pre
                                >
                                    <p class="text-2xl break-all font-mediums max-md:text-xl">Hello! <?php echo e(auth()->user()?->first_name); ?></p>

                                    <p class="no-underline text-zinc-500 max-md:text-sm"><?php echo e(auth()->user()?->email); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.before'); ?>


                    <!-- Mobile category view -->
                    <v-mobile-category ref="mobileCategory"></v-mobile-category>

                    <?php echo view_render_event('bagisto.shop.components.layouts.header.mobile.drawer.categories.after'); ?>

                 <?php $__env->endSlot(); ?>

                 <?php $__env->slot('footer', null, []); ?> 
                    <!-- Localization & Currency Section -->
                <?php if(core()->getCurrentChannel()->locales()->count() > 1 || core()->getCurrentChannel()->currencies()->count() > 1 ): ?>
                                    <div class="fixed bottom-0 z-10 grid w-full max-w-full grid-cols-[1fr_auto_1fr] items-center justify-items-center border-t border-zinc-200 bg-white px-5 ltr:left-0 rtl:right-0">
                                        <!-- Filter Drawer -->
                                        <?php if (isset($component)) { $__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'shop::components.drawer.index','data' => ['position' => 'bottom','width' => '100%']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shop::drawer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom','width' => '100%']); ?>
                                            <!-- Drawer Toggler -->
                                             <?php $__env->slot('toggle', null, []); ?> 
                                                <div
                                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                                    role="button"
                                                    v-pre
                                                >
                                                    <?php echo e(core()->getCurrentCurrency()->symbol . ' ' . core()->getCurrentCurrencyCode()); ?>

                                                </div>
                                             <?php $__env->endSlot(); ?>

                                            <!-- Drawer Header -->
                                             <?php $__env->slot('header', null, []); ?> 
                                                <div class="flex items-center justify-between">
                                                    <p class="text-lg font-semibold">
                                                        <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.currencies'); ?>
                                                    </p>
                                                </div>
                                             <?php $__env->endSlot(); ?>

                                            <!-- Drawer Content -->
                                             <?php $__env->slot('content', null, ['class' => '!px-0']); ?> 
                                                <div
                                                    class="overflow-auto"
                                                    :style="{ height: getCurrentScreenHeight }"
                                                >
                                                    <v-currency-switcher></v-currency-switcher>
                                                </div>
                                             <?php $__env->endSlot(); ?>
                                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8)): ?>
<?php $attributes = $__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8; ?>
<?php unset($__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8)): ?>
<?php $component = $__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8; ?>
<?php unset($__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8); ?>
<?php endif; ?>

                                        <!-- Seperator -->
                                        <span class="h-5 w-0.5 bg-zinc-200"></span>

                                        <!-- Sort Drawer -->
                                        <?php if (isset($component)) { $__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'shop::components.drawer.index','data' => ['position' => 'bottom','width' => '100%']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('shop::drawer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom','width' => '100%']); ?>
                                            <!-- Drawer Toggler -->
                                             <?php $__env->slot('toggle', null, []); ?> 
                                                <div
                                                    class="flex cursor-pointer items-center gap-x-2.5 px-2.5 py-3.5 text-lg font-medium uppercase max-md:py-3 max-sm:text-base"
                                                    role="button"
                                                    v-pre
                                                >
                                                    <img
                                        src="<?php echo e(! empty(core()->getCurrentLocale()->logo_url)
                        ? core()->getCurrentLocale()->logo_url
                        : bagisto_asset('images/default-language.svg')); ?>"
                                                        class="h-full"
                                                        alt="Default locale"
                                                        width="24"
                                                        height="16"
                                                    />

                                                    <?php echo e(core()->getCurrentChannel()->locales()->orderBy('name')->where('code', app()->getLocale())->value('name')); ?>

                                                </div>
                                             <?php $__env->endSlot(); ?>

                                            <!-- Drawer Header -->
                                             <?php $__env->slot('header', null, []); ?> 
                                                <div class="flex items-center justify-between">
                                                    <p class="text-lg font-semibold">
                                                        <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.locales'); ?>
                                                    </p>
                                                </div>
                                             <?php $__env->endSlot(); ?>

                                            <!-- Drawer Content -->
                                             <?php $__env->slot('content', null, ['class' => '!px-0']); ?> 
                                                <div
                                                    class="overflow-auto"
                                                    :style="{ height: getCurrentScreenHeight }"
                                                >
                                                    <v-locale-switcher></v-locale-switcher>
                                                </div>
                                             <?php $__env->endSlot(); ?>
                                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8)): ?>
<?php $attributes = $__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8; ?>
<?php unset($__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8)): ?>
<?php $component = $__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8; ?>
<?php unset($__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8); ?>
<?php endif; ?>
                                    </div>
                    <?php endif; ?>
                 <?php $__env->endSlot(); ?>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8)): ?>
<?php $attributes = $__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8; ?>
<?php unset($__attributesOriginal2b3e2da8ab003ef79d854b1862e64fc8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8)): ?>
<?php $component = $__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8; ?>
<?php unset($__componentOriginal2b3e2da8ab003ef79d854b1862e64fc8); ?>
<?php endif; ?>
        </script>

    <script
        type="text/x-template"
        id="v-mobile-category-template"
    >
            <!-- Wrapper with transition effects -->
    <div class="relative h-full overflow-hidden">
        <!-- Sliding container -->
            <div
                class="flex h-full transition-transform duration-300"
                :class="{
                        'ltr:translate-x-0 rtl:translate-x-0': currentViewLevel !== 'third',
                        'ltr:-translate-x-full rtl:translate-x-full': currentViewLevel === 'third'
                }"
            >
            <!-- First level view -->
            <div class="flex-shrink-0 w-full h-full px-6 overflow-auto">
                <div class="py-4">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            :class="{'mb-2': category.children && category.children.length}"
                        >
                        <div class="flex items-center justify-between py-2 transition-colors duration-200 cursor-pointer">
                            <a :href="category.url" class="text-base font-medium text-black">
                                {{ category.name }}
                            </a>
                        </div>

                        <!-- Second Level Categories -->
                            <div v-if="category.children && category.children.length" >
                                <div
                                    v-for="secondLevelCategory in category.children"
                                    :key="secondLevelCategory.id"
                                >
                                    <div
                                        class="flex items-center justify-between py-2 transition-colors duration-200 cursor-pointer"
                                        @click="showThirdLevel(secondLevelCategory, category, $event)"
                                    >
                                    <a :href="secondLevelCategory.url" class="text-sm font-normal">
                                        {{ secondLevelCategory.name }}
                                    </a>

                                        <span
                                            v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                            class="icon-arrow-right rtl:icon-arrow-left"
                                        ></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third level view -->
                <div
                    class="flex-shrink-0 w-full h-full"
                    v-if="currentViewLevel === 'third'"
                >
                <div class="px-6 py-4 border-b border-gray-200">
                        <button
                            @click="goBackToMainView"
                            class="flex items-center justify-center gap-2 focus:outline-none"
                            aria-label="Go back"
                        >
                        <span class="text-lg icon-arrow-left rtl:icon-arrow-right"></span>
                        <div class="text-base font-medium text-black">
                            <?php echo app('translator')->get('shop::app.components.layouts.header.mobile.back-button'); ?>
                        </div>
                    </button>
                </div>

                <!-- Third Level Content -->
                <div class="px-6 py-4">
                        <div
                            v-for="thirdLevelCategory in currentSecondLevelCategory?.children"
                            :key="thirdLevelCategory.id"
                            class="mb-2"
                        >
                            <a
                                :href="thirdLevelCategory.url"
                                class="block py-2 text-sm transition-colors duration-200"
                            >
                            {{ thirdLevelCategory.name }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </script>

    <script type="module">
        app.component('v-mobile-category', {
            template: '#v-mobile-category-template',

            data() {
                return  {
                    categories: [],
                    currentViewLevel: 'main',
                    currentSecondLevelCategory: null,
                    currentParentCategory: null
                }
            },

            mounted() {
                this.initCategories();
            },

            computed: {
                getCurrentScreenHeight() {
                    return window.innerHeight - (window.innerWidth < 920 ? 61 : 0) + 'px';
                },
            },

            methods: {
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories');

                        if (stored) {
                            this.categories = JSON.parse(stored);
                            this.isLoading = false;
                            return;
                        }

                    } catch (e) {}

                    this.getCategories();
                },
                getCategories() {
                    this.$axios.get("<?php echo e(route('shop.api.categories.tree')); ?>")
                        .then(response => {
                            this.categories = response.data.data;
                            localStorage.setItem('categories', JSON.stringify(this.categories));
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                showThirdLevel(secondLevelCategory, parentCategory, event) {
                    if (secondLevelCategory.children && secondLevelCategory.children.length) {
                        this.currentSecondLevelCategory = secondLevelCategory;
                        this.currentParentCategory = parentCategory;
                        this.currentViewLevel = 'third';

                        if (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }
                },

                goBackToMainView() {
                    this.currentViewLevel = 'main';
                }
            },
        });

        app.component('v-mobile-drawer', {
            template: '#v-mobile-drawer-template',

            methods: {
                onDrawerClose() {
                    this.$refs.mobileCategory.currentViewLevel = 'main';
                }
            },
        });
    </script>
<?php $__env->stopPush(); endif; ?>
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Shop\src/resources/views/components/layouts/header/mobile/index.blade.php ENDPATH**/ ?>