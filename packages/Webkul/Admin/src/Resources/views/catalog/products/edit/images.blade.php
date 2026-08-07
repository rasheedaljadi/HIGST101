{!! view_render_event('bagisto.admin.catalog.product.edit.form.images.before', ['product' => $product]) !!}

<div class="box-shadow relative rounded bg-white p-4 dark:bg-gray-900">
    <!-- Panel Header -->
    <div class="mb-4 flex justify-between gap-5">
        <div class="flex flex-col gap-2">
            <p class="text-base font-semibold text-gray-800 dark:text-white">
                @lang('admin::app.catalog.products.edit.images.title')
            </p>

            <p class="text-xs font-medium text-gray-500 dark:text-gray-300">
                @lang('admin::app.catalog.products.edit.images.info')
            </p>
            <p class="text-xs font-bold text-amber-600 dark:text-amber-400 mt-1 flex items-center gap-1">
                <span>⭐</span>
                <span>تنبيه: الصورة الأولى في القائمة تمثل "الصورة المصغرة الرئيسية" التي تظهر على بطاقة المنتج في المتجر. يمكنك سحب وإفلات أي صورة لوضعها في البداية لتصبح هي الصورة المصغرة.</span>
            </p>
        </div>
    </div>

    <!-- Image Blade Component -->
    <x-admin::media.images
        name="images[files]"
        allow-multiple="true"
        show-placeholders="true"
        :uploaded-images="$product->images"
    />

    <x-admin::form.control-group.error control-name='images.files[0]' />
</div>

{!! view_render_event('bagisto.admin.catalog.product.edit.form.images.after', ['product' => $product]) !!}