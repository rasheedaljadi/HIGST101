<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.channels.create.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.settings.channels.create.before') !!}

    <x-admin::form
        action="{{ route('admin.settings.channels.store') }}"
        enctype="multipart/form-data"
    >

        {!! view_render_event('admin.settings.channels.create.create_form_controls.before') !!}

        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.channels.create.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <!-- Back Button -->
                <a
                    href="{{ route('admin.settings.channels.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.settings.channels.create.cancel')
                </a>

                <!-- Save Button -->
                <button 
                    type="submit" 
                    class="primary-button"
                >
                    @lang('admin::app.settings.channels.create.save-btn')
                </button>
            </div>
        </div>

        <!-- body content -->
        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left sub-component -->
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">

                {!! view_render_event('bagisto.admin.settings.channels.create.card.general.before') !!}

                <!-- General Information -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.channels.create.general')
                    </p>

                    <!-- Code -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="code"
                            name="code"
                            rules="required"
                            :value="old('code')"
                            :label="trans('admin::app.settings.channels.create.code')"
                            :placeholder="trans('admin::app.settings.channels.create.code')"
                        />

                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <!-- Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="name"
                            name="name"
                            rules="required"
                            :value="old('name')"
                            :label="trans('admin::app.settings.channels.create.name')"
                            :placeholder="trans('admin::app.settings.channels.create.name')"
                        />

                        <x-admin::form.control-group.error control-name="name" />
                    </x-admin::form.control-group>

                    <!-- Description -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.create.description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="description"
                            name="description"
                            :value="old('description')"
                            :label="trans('admin::app.settings.channels.create.description')"
                            :placeholder="trans('admin::app.settings.channels.create.description')"
                        />

                        <x-admin::form.control-group.error control-name="description" />
                    </x-admin::form.control-group>

                    <!-- Inventory Sources -->
                    <div class="mb-4">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.inventory-sources')
                        </x-admin::form.control-group.label>

                        @foreach (app('Webkul\Inventory\Repositories\InventorySourceRepository')->findWhere(['status' => 1]) as $inventorySource)
                            <x-admin::form.control-group class="!mb-2 flex items-center gap-2.5">
                                <x-admin::form.control-group.control
                                    type="checkbox"
                                    :id="'inventory_sources_' . $inventorySource->id"
                                    name="inventory_sources[]"
                                    rules="required"
                                    :value="$inventorySource->id "
                                    :for="'inventory_sources_' . $inventorySource->id"
                                    :label="trans('admin::app.settings.channels.create.inventory-sources')"
                                />
                                    
                                <label
                                    class="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300"
                                    for="inventory_sources_{{ $inventorySource->id }}"
                                    v-pre
                                >
                                    {{ $inventorySource->name }}
                                </label>

                            </x-admin::form.control-group>
                        @endforeach 

                        <x-admin::form.control-group.error control-name="inventory_sources[]" />
                    </div>

                    <!-- Root Category -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.root-category')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="root_category_id"
                            name="root_category_id"
                            rules="required"
                            :value="old('root_category_id')"
                            :label="trans('admin::app.settings.channels.create.root-category')"
                        >
                            <!-- Default Option -->
                            <option value="">
                                @lang('admin::app.settings.channels.create.select-root-category')
                            </option>

                            @foreach (app('Webkul\Category\Repositories\CategoryRepository')->getRootCategories() as $category)
                                <option 
                                    value="{{ $category->id }}"
                                    {{ old('root_category_id') == $category->id ? 'selected' : '' }}
                                    v-pre
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="root_category_id" />
                    </x-admin::form.control-group>

                    <!-- Host Name -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.create.hostname')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="hostname"
                            name="hostname"
                            :value="old('hostname')"
                            :label="trans('admin::app.settings.channels.create.hostname')"
                            :placeholder="trans('admin::app.settings.channels.create.hostname-placeholder')"
                        />

                        <x-admin::form.control-group.error control-name="hostname" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('bagisto.admin.settings.channels.create.card.general.after') !!}

                {!! view_render_event('bagisto.admin.settings.channels.create.card.design.before') !!}

                <!-- Logo and Design -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.channels.create.design')
                    </p>

                    <!-- Theme Selector -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.create.theme')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="theme"
                            name="theme"
                            :value="config('themes.admin-default')"
                            :label="trans('admin::app.settings.channels.create.theme')"
                        >
                            @foreach (config('themes.shop') as $themeCode => $theme)
                                <option
                                    value="{{ $themeCode }}"
                                    {{ old('theme') == $themeCode ? 'selected' : '' }}
                                    v-pre
                                >
                                    {{ $theme['name'] }}
                                </option>
                            @endforeach
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="theme" />
                    </x-admin::form.control-group>

                    <div class="flex justify-between">
                        <!-- Logo -->
                        <div class="flex w-2/5 flex-col">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.channels.create.logo')
                                </x-admin::form.control-group.label>

                                <x-admin::media.images
                                    name="logo"
                                    width="110px"
                                    height="110px"
                                />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.channels.create.logo-size')
                            </p>
                        </div>


                        <!-- Favicon -->
                        <div class="flex w-2/5 flex-col">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.channels.create.favicon')
                                </x-admin::form.control-group.label>

                                <x-admin::media.images
                                    name="favicon"
                                    width="110px"
                                    height="110px"
                                />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.channels.create.favicon-size')
                            </p>
                        </div>
                    </div>
                </div>

                {!! view_render_event('bagisto.admin.settings.channels.create.card.design.after') !!}

                {!! view_render_event('bagisto.admin.settings.channels.create.card.seo.before') !!}

                <!-- Home Page SEO -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.channels.create.seo')
                    </p>

                    <!-- SEO Title & Description Blade Component -->
                    <x-admin::seo
                        meta-title-field="meta_title"
                        url-key-field="hostname"
                        meta-description-field="meta_description"
                        url-type="host"
                    />

                    <!-- SEO Title -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.seo-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="meta_title"
                            name="seo_title" 
                            rules="required"
                            :value="old('seo_title')"
                            :label="trans('admin::app.settings.channels.create.seo-title')"
                            :placeholder="trans('admin::app.settings.channels.create.seo-title')"
                        />

                        <x-admin::form.control-group.error control-name="seo_title" />
                    </x-admin::form.control-group>

                    <!-- SEO Keywords -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.seo-keywords')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="seo_keywords"
                            name="seo_keywords"
                            rules="required"
                            :value="old('seo_keywords') "
                            :label="trans('admin::app.settings.channels.create.seo-keywords')"
                            :placeholder="trans('admin::app.settings.channels.create.seo-keywords')"
                        />

                        <x-admin::form.control-group.error control-name="seo_keywords" />
                    </x-admin::form.control-group>

                    <!-- SEO Description -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.create.seo-description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="meta_description"
                            name="seo_description"
                            rules="required"
                            :value="old('seo_description')"
                            :label="trans('admin::app.settings.channels.create.seo-description')"
                            :placeholder="trans('admin::app.settings.channels.create.seo-description')"
                        />

                        <x-admin::form.control-group.error control-name="seo_description" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('bagisto.admin.settings.channels.create.card.seo.after') !!}

            </div>

            <!-- Right sub-component -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">

                {!! view_render_event('bagisto.admin.settings.channels.create.card.accordion.currencies_and_locales.before') !!}

                <!-- Currencies and Locales -->
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('admin::app.settings.channels.create.currencies-and-locales')
                        </p>
                    </x-slot>
            
                    <x-slot:content>
                        <!-- Locale Checkboxes  -->
                        <div class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.create.locales')
                            </x-admin::form.control-group.label>
                        
                            @foreach (core()->getAllLocales() as $locale)
                                <x-admin::form.control-group class="!mb-2 flex items-center gap-2.5">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        :id="'locales_' . $locale->id"
                                        name="locales[]"
                                        rules="required"
                                        :value="$locale->id"
                                        :for="'locales_' . $locale->id"
                                        :label="trans('admin::app.settings.channels.create.locales')"
                                    />

                                    <label
                                        class="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300"
                                        for="locales_{{ $locale->id }}"
                                        v-pre
                                    >
                                        {{ $locale->name }} 
                                    </label>
                                </x-admin::form.control-group>
                            @endforeach

                            <x-admin::form.control-group.error control-name="locales[]" />
                        </div>

                        <!-- Default Locale Selector -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.create.default-locale')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                id="default_locale_id"
                                name="default_locale_id"
                                rules="required"
                                :value="old('default_locale_id')"
                                :label="trans('admin::app.settings.channels.create.default-locale')"
                            >
                                <!-- Default Option -->
                                <option value="">
                                    @lang('admin::app.settings.channels.create.select-default-locale')
                                </option>

                                @foreach (core()->getAllLocales() as $locale)
                                    <option 
                                        value="{{ $locale->id }}" 
                                        {{ old('default_locale_id') == $locale->id ? 'selected' : '' }}
                                        v-pre
                                    >
                                        {{ $locale->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="default_locale_id" />
                        </x-admin::form.control-group>

                        <!-- Currencies Checkboxes -->
                        <div class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.create.currencies')
                            </x-admin::form.control-group.label>
                        
                            @foreach (core()->getAllCurrencies() as $currency)
                                <x-admin::form.control-group class="!mb-2 flex items-center gap-2.5">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        :id="'currencies_' . $currency->id"
                                        name="currencies[]" 
                                        rules="required"
                                        :value="$currency->id"
                                        :for="'currencies_' . $currency->id"
                                        :label="trans('admin::app.settings.channels.create.currencies')"
                                    />

                                    <label
                                        class="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300"
                                        for="currencies_{{ $currency->id }}"
                                        v-pre
                                    >
                                        {{ $currency->name }} 
                                    </label>
                                </x-admin::form.control-group>
                            @endforeach 
                            
                            <x-admin::form.control-group.error control-name="currencies[]" />
                        </div>

                        <!-- Default Currency Selector -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.create.default-currency')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                id="base_currency_id"
                                name="base_currency_id"
                                rules="required"
                                :value="old('base_currency_id')"
                                :label="trans('admin::app.settings.channels.create.default-currency')"
                            >
                                <!-- Default Option -->
                                <option value="">
                                    @lang('admin::app.settings.channels.create.select-default-currency')
                                </option>

                                @foreach (core()->getAllCurrencies() as $currency)
                                    <option
                                        value="{{ $currency->id }}"
                                        {{ old('base_currency_id') == $currency->id ? 'selected' : '' }}
                                        v-pre
                                    >
                                        {{ $currency->name }}
                                    </option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="base_currency_id" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('bagisto.admin.settings.channels.create.card.accordion.currencies_and_locales.after') !!}

                {!! view_render_event('bagisto.admin.settings.channels.create.card.accordion.settings.before') !!}

                <!-- settings -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.channels.create.settings')
                            </p>
                        </div>
                    </x-slot>
            
                    <x-slot:content>
                        <!-- Maintenance Mode Text  -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.maintenance-mode-text')
                            </x-admin::form.control-group.label>
                            
                            <x-admin::form.control-group.control
                                type="text"
                                id="maintenance-mode-text"
                                name="maintenance_mode_text"
                                :value="old('maintenance_mode_text')"
                                :label="trans('admin::app.settings.channels.create.maintenance-mode-text')"
                                :placeholder="trans('admin::app.settings.channels.create.maintenance-mode-text')"
                            />
                        
                            <x-admin::form.control-group.error control-name="maintenance_mode_text" />
                        </x-admin::form.control-group>

                        <!-- Allowed API's  -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="!text-gray-800 dark:!text-white">
                                @lang('admin::app.settings.channels.create.allowed-ips')
                            </x-admin::form.control-group.label>
                            
                            <x-admin::form.control-group.control
                                type="text"
                                id="allowed-ips"
                                name="allowed_ips"
                                :value="old('allowed_ips')"
                                :label="trans('admin::app.settings.channels.create.allowed-ips')"
                                :placeholder="trans('admin::app.settings.channels.create.allowed-ips')"
                            />
                            
                            <x-admin::form.control-group.error control-name="allowed_ips" />
                        </x-admin::form.control-group>

                        <!-- Maintenance Mode Switcher -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.status')
                            </x-admin::form.control-group.label>
                            <x-admin::form.control-group.control
                                type="switch"
                                id="maintenance-mode-status"
                                name="is_maintenance_on"
                                :value="1"
                                :checked="false"
                            />

                            <x-admin::form.control-group.error control-name="is_maintenance_on" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('bagisto.admin.settings.channels.create.card.accordion.settings.after') !!}

                <!-- WhatsApp Support -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.channels.create.whatsapp-support')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <!-- WhatsApp Phone Number -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.whatsapp-number')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="whatsapp_number"
                                name="whatsapp_number"
                                :value="old('whatsapp_number')"
                                :label="trans('admin::app.settings.channels.create.whatsapp-number')"
                                :placeholder="trans('admin::app.settings.channels.create.whatsapp-number-placeholder')"
                            />

                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.channels.create.whatsapp-number-tip')
                            </p>

                            <x-admin::form.control-group.error control-name="whatsapp_number" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

                <!-- Social Media Links -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.channels.create.social-links')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                            @lang('admin::app.settings.channels.create.social-links-tip')
                        </p>

                        @php
                            $socialLinks = old('social_links') ?? [];
                        @endphp

                        <!-- Facebook -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.facebook')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_facebook"
                                name="social_links[facebook]"
                                :value="$socialLinks['facebook'] ?? ''"
                                placeholder="https://facebook.com/your-page"
                            />

                            <x-admin::form.control-group.error control-name="social_links[facebook]" />
                        </x-admin::form.control-group>

                        <!-- Instagram -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.instagram')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_instagram"
                                name="social_links[instagram]"
                                :value="$socialLinks['instagram'] ?? ''"
                                placeholder="https://instagram.com/your-profile"
                            />

                            <x-admin::form.control-group.error control-name="social_links[instagram]" />
                        </x-admin::form.control-group>

                        <!-- X / Twitter -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.twitter')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_twitter"
                                name="social_links[twitter]"
                                :value="$socialLinks['twitter'] ?? ''"
                                placeholder="https://x.com/your-handle"
                            />

                            <x-admin::form.control-group.error control-name="social_links[twitter]" />
                        </x-admin::form.control-group>

                        <!-- YouTube -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.youtube')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_youtube"
                                name="social_links[youtube]"
                                :value="$socialLinks['youtube'] ?? ''"
                                placeholder="https://youtube.com/@your-channel"
                            />

                            <x-admin::form.control-group.error control-name="social_links[youtube]" />
                        </x-admin::form.control-group>

                        <!-- TikTok -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.tiktok')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_tiktok"
                                name="social_links[tiktok]"
                                :value="$socialLinks['tiktok'] ?? ''"
                                placeholder="https://tiktok.com/@your-account"
                            />

                            <x-admin::form.control-group.error control-name="social_links[tiktok]" />
                        </x-admin::form.control-group>

                        <!-- Snapchat -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.snapchat')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_snapchat"
                                name="social_links[snapchat]"
                                :value="$socialLinks['snapchat'] ?? ''"
                                placeholder="https://snapchat.com/add/your-username"
                            />

                            <x-admin::form.control-group.error control-name="social_links[snapchat]" />
                        </x-admin::form.control-group>

                        <!-- Telegram -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.create.telegram')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="social_links_telegram"
                                name="social_links[telegram]"
                                :value="$socialLinks['telegram'] ?? ''"
                                placeholder="https://t.me/your-channel"
                            />

                            <x-admin::form.control-group.error control-name="social_links[telegram]" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

                <!-- Accepted Payment Methods in Footer -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                وسائل الدفع المقبولة في الفوتر (اللوجو)
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                            حدد شعارات وسائل الدفع المعتمدة التي ترغب بظهورها في أسفل صفحة المتجر (الفوتر).
                        </p>

                        @php
                            $paymentMethods = old('payment_methods') ?? [];
                            $showTitle = isset($paymentMethods['show_title']) ? (bool) $paymentMethods['show_title'] : true;
                            $savedMethods = $paymentMethods['methods'] ?? null;
                            $deletedKeys = $paymentMethods['deleted_keys'] ?? [];

                            $availableMethods = [
                                'bank_transfer'    => ['title' => 'تحويل بنكي', 'default' => true],
                                'apple_pay'        => ['title' => 'Apple Pay', 'default' => true],
                                'mada'             => ['title' => 'مدى (mada)', 'default' => true],
                                'mastercard'       => ['title' => 'Mastercard', 'default' => true],
                                'visa'             => ['title' => 'VISA', 'default' => true],
                                'kuraimi'          => ['title' => 'الكريمي (Kuraimi)', 'default' => false],
                                'onecash'          => ['title' => 'ون كاش (OneCash)', 'default' => false],
                                'jawali'           => ['title' => 'جوالي (Jawali)', 'default' => false],
                                'paypal'           => ['title' => 'PayPal', 'default' => false],
                                'stc_pay'          => ['title' => 'STC Pay', 'default' => false],
                                'cash_on_delivery' => ['title' => 'الدفع عند الاستلام', 'default' => false],
                                'tabby'            => ['title' => 'تابي (Tabby)', 'default' => false],
                                'tamara'           => ['title' => 'تمارا (Tamara)', 'default' => false],
                            ];
                        @endphp

                        <!-- Hidden Container for Deleted Keys -->
                        <div id="deleted-payment-methods-container">
                            @foreach($deletedKeys as $delKey)
                                <input type="hidden" name="payment_methods[deleted_keys][]" value="{{ $delKey }}">
                            @endforeach
                        </div>

                        <!-- Show/Hide Title Toggle -->
                        <div class="mb-5 pb-4 border-b border-gray-200 dark:border-gray-800">
                            <input type="hidden" name="payment_methods[show_title]" value="0">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="payment_methods[show_title]"
                                    value="1"
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 w-4 h-4 cursor-pointer"
                                    {{ $showTitle ? 'checked' : '' }}
                                >
                                <div>
                                    <span class="text-sm font-semibold text-gray-800 dark:text-white">إظهار عبارة "وسائل الدفع المقبولة" بجانب الشعارات</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">عند إلغاء التحديد، سيتم عرض شعارات وسائل الدفع فقط (فقط اللوجو) بدون نص العبارة.</p>
                                </div>
                            </label>
                        </div>

                        <!-- Payment Methods Checkboxes Grid with Delete Action -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6" id="payment-methods-grid">
                            @foreach($availableMethods as $methodKey => $methodInfo)
                                @if(! in_array($methodKey, $deletedKeys))
                                    @php
                                        $isChecked = is_null($savedMethods) 
                                            ? $methodInfo['default'] 
                                            : ! empty($savedMethods[$methodKey]['enabled']);
                                    @endphp
                                    <div id="payment-method-card-{{ $methodKey }}" class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-900 transition-colors">
                                        <label class="flex items-center gap-3 cursor-pointer flex-1 select-none">
                                            <input
                                                type="checkbox"
                                                name="payment_methods[methods][{{ $methodKey }}][enabled]"
                                                value="1"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 w-4 h-4 cursor-pointer"
                                                {{ $isChecked ? 'checked' : '' }}
                                            >
                                            <span class="text-sm font-medium text-gray-800 dark:text-white">{{ $methodInfo['title'] }}</span>
                                        </label>

                                        <div class="flex items-center gap-2">
                                            <span class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 font-mono">
                                                {{ $methodKey }}
                                            </span>

                                            <!-- Delete Button for Each Item -->
                                            <button
                                                type="button"
                                                data-action="delete-payment-item"
                                                data-key="{{ $methodKey }}"
                                                data-title="{{ $methodInfo['title'] }}"
                                                onclick="window.removePaymentMethodItem('{{ $methodKey }}', '{{ $methodInfo['title'] }}')"
                                                title="حذف {{ $methodInfo['title'] }} من القائمة"
                                                class="w-7 h-7 flex items-center justify-center rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                            >
                                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <!-- Custom Payment Logos -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-800">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-1">إضافة شعار وسيلة دفع مخصصة (صورة)</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">يمكنك رفع صورة شعار إضافي لوسيلة دفع محلية أو مخصصة (PNG, SVG, WebP).</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">اسم وسيلة الدفع</label>
                                    <input type="text" name="custom_payment_logo_titles[0]" placeholder="مثال: ون كاش أو فلوسك" class="w-full text-xs rounded border border-gray-300 dark:border-gray-700 p-2 dark:bg-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">ملف الشعار</label>
                                    <input type="file" name="custom_payment_logo_files[0]" accept="image/*" class="w-full text-xs border border-gray-300 dark:border-gray-700 rounded p-1 dark:bg-gray-900 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::accordion>

            </div>
        </div>

        {!! view_render_event('admin.settings.channels.create.create_form_controls.after') !!}

    </x-admin::form> 

    {!! view_render_event('bagisto.admin.settings.channels.create.after') !!}
</x-admin::layouts>

@push('scripts')
    <script>
        window.removePaymentMethodItem = function(key, title) {
            if (confirm('هل أنت متأكد من حذف "' + title + '" من القائمة؟ لن تظهر في الفوتر بعد الحفظ.')) {
                var el = document.getElementById('payment-method-card-' + key);
                if (el) {
                    el.style.opacity = '0';
                    el.style.transform = 'scale(0.95)';
                    el.style.transition = 'all 0.2s ease';
                    setTimeout(function() { el.remove(); }, 200);
                }
                var container = document.getElementById('deleted-payment-methods-container');
                if (container) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'payment_methods[deleted_keys][]';
                    input.value = key;
                    container.appendChild(input);
                }
            }
        };

        document.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-action="delete-payment-item"]');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                var key = btn.getAttribute('data-key');
                var title = btn.getAttribute('data-title');
                window.removePaymentMethodItem(key, title);
                return;
            }
        });
    </script>
@endPush
