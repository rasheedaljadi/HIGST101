@php
    $locale = core()->getRequestedLocaleCode();

    $seo = $channel->translate($locale)['home_seo'] ?? $channel->home_seo;
@endphp

<x-admin::layouts>
    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.settings.channels.edit.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.settings.channels.edit.before', ['channel' => $channel]) !!}

    <!-- Channel Id Edit Form -->
    <x-admin::form  
        :action="route('admin.settings.channels.update', ['id' => $channel->id, 'locale' => $locale])"
        enctype="multipart/form-data"
    >
        @method('PUT')

        {!! view_render_event('bagisto.admin.settings.channels.edit.edit_form_controls.before', ['channel' => $channel]) !!}

        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.channels.edit.title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.settings.channels.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('admin::app.settings.channels.edit.back-btn')
                </a>

                <button 
                    type="submit" 
                    class="primary-button"
                    aria-label="Submit"
                >
                    @lang('admin::app.settings.channels.edit.save-btn')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left Component -->
            <div class="flex flex-1 flex-col gap-2 max-xl:flex-auto">

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.general.before', ['channel' => $channel]) !!}

                <!-- General Information -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.channels.edit.general')
                    </p>

                    <!-- Code -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.code')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="code"
                            name="code"
                            rules="required"
                            :value="old('code') ?? $channel->code"
                            :label="trans('admin::app.settings.channels.edit.code')"
                            :placeholder="trans('admin::app.settings.channels.edit.code')"
                            disabled="disabled"
                        />

                        <input
                            type="hidden"
                            name="code"
                            value="{{ $channel->code }}"
                        />
                    
                        <x-admin::form.control-group.error control-name="code" />
                    </x-admin::form.control-group>

                    <!-- Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            :id="$locale . '[name]'"
                            :name="$locale . '[name]'"
                            rules="required"
                            :value="old('name') ?? $channel->name"
                            :label="trans('admin::app.settings.channels.edit.name')"
                            :placeholder="trans('admin::app.settings.channels.edit.name')"
                        />

                        <x-admin::form.control-group.error :control-name="$locale . '[name]'" />
                    </x-admin::form.control-group>

                    <!-- Description -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.edit.description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            :id="$locale . '[description]'"
                            :name="$locale . '[description]'"
                            :value="old('description') ?? $channel->description"
                            :label="trans('admin::app.settings.channels.edit.description')"
                            :placeholder="trans('admin::app.settings.channels.edit.description')"
                        />

                        <x-admin::form.control-group.error control-name="$locale . '[description]'" />
                    </x-admin::form.control-group>

                    <!-- Inventory Sources -->
                    <div class="mb-4">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.inventory-sources')
                        </x-admin::form.control-group.label>
                
                        @foreach (app('Webkul\Inventory\Repositories\InventorySourceRepository')->findWhere(['status' => 1]) as $inventorySource)
                            <x-admin::form.control-group class="!mb-2 flex items-center gap-2.5">
                                <x-admin::form.control-group.control
                                    type="checkbox"
                                    :id="'inventory_sources_' . $inventorySource->id"
                                    name="inventory_sources[]"
                                    rules="required"
                                    :value="$inventorySource->id" 
                                    :for="'inventory_sources_' . $inventorySource->id"
                                    :label="trans('admin::app.settings.channels.edit.inventory-sources')"
                                    :checked="in_array($inventorySource->id, old('inventory_sources') ?? $channel->inventory_sources->pluck('id')->toArray())"
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
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.edit.root-category')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="root_category_id"
                            name="root_category_id"
                            rules="required"
                            :value="old('root_category_id') ?? $channel->root_category_id"
                            :label="trans('admin::app.settings.channels.edit.root-category')"
                        >
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
                            @lang('admin::app.settings.channels.edit.hostname')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="hostname"
                            name="hostname"
                            :value="old('hostname') ?? $channel->hostname"
                            :label="trans('admin::app.settings.channels.edit.hostname')"
                            :placeholder="trans('admin::app.settings.channels.edit.hostname-placeholder')"
                        />

                        <x-admin::form.control-group.error control-name="hostname" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.general.after', ['channel' => $channel]) !!}

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.design.before', ['channel' => $channel]) !!}

                <!-- Logo and Design -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.channels.edit.design')
                    </p>

                    <!-- Theme Selector -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.edit.theme')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="theme"
                            name="theme"
                            :value="old('theme') ?? $channel->theme"
                            :label="trans('admin::app.settings.channels.edit.theme')"
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
                                    @lang('admin::app.settings.channels.edit.logo')
                                </x-admin::form.control-group.label>

                                <x-admin::media.images
                                    name="logo"
                                    width="110px"
                                    height="110px"
                                    :uploaded-images="$channel->logo ? [['id' => 'logo_path', 'url' => $channel->logo_url]] : []"
                                />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.channels.edit.logo-size')
                            </p>
                        </div>

                        <!-- Favicon -->
                        <div class="flex w-2/5 flex-col">
                            <x-admin::form.control-group>
                                <x-admin::form.control-group.label>
                                    @lang('admin::app.settings.channels.edit.favicon')
                                </x-admin::form.control-group.label>

                                @php
                                    $faviconImages = $channel->favicon ? [['id' => 'logo_path', 'url' => $channel->favicon_url]] : [];
                                @endphp

                                <x-admin::media.images
                                    name="favicon"
                                    width="110px"
                                    height="110px"
                                    :uploaded-images="$channel->favicon ? [['id' => 'logo_path', 'url' => $channel->favicon_url]] : []"
                                />
                            </x-admin::form.control-group>

                            <p class="text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.channels.edit.favicon-size')
                            </p>
                        </div>
                    </div>
                </div>

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.design.after', ['channel' => $channel]) !!}

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.seo.before', ['channel' => $channel]) !!}

                <!-- Home Page SEO -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.channels.edit.seo')
                    </p>

                    <!-- SEO Title & Description Blade Componnet -->
                    <x-admin::seo
                        meta-title-field="meta_title"
                        url-key-field="hostname"
                        meta-description-field="meta_description"
                        url-type="host"
                    />

                    <!-- Meta Title -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.seo-title')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            :name="$locale . '[seo_title]'"
                            :value="old($locale)['seo_title'] ?? $seo['meta_title']"
                            id="meta_title"
                            rules="required"
                            :label="trans('admin::app.settings.channels.edit.seo-title')"
                            :placeholder="trans('admin::app.settings.channels.edit.seo-title')"
                        />

                        <x-admin::form.control-group.error :control-name="$locale . '[seo_title]'" />
                    </x-admin::form.control-group>

                    <!-- Meta Keywords -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            @lang('admin::app.settings.channels.edit.seo-keywords')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="seo_keywords"
                            :name="$locale . '[seo_keywords]'"
                            :value="old($locale)['seo_keywords'] ?? $seo['meta_keywords']"
                            :label="trans('admin::app.settings.channels.edit.seo-keywords')"
                            :placeholder="trans('admin::app.settings.channels.edit.seo-keywords')"
                        />
                    </x-admin::form.control-group>

                    <!-- Meta Description -->
                    <x-admin::form.control-group class="!mb-0">
                        <x-admin::form.control-group.label class="required">
                            @lang('admin::app.settings.channels.edit.seo-description')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="textarea"
                            id="meta_description"
                            :name="$locale . '[seo_description]'"
                            rules="required"
                            :value="old($locale)['seo_description'] ?? $seo['meta_description']"
                            :label="trans('admin::app.settings.channels.edit.seo-description')"
                            :placeholder="trans('admin::app.settings.channels.edit.seo-description')"
                        />

                        <x-admin::form.control-group.error :control-name="$locale . '[seo_description]'" />
                    </x-admin::form.control-group>
                </div>

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.seo.after', ['channel' => $channel]) !!}

            </div>

            <!-- Right Component -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.accordion.currencies_and_locales.before', ['channel' => $channel]) !!}

                <!-- Currencies and Locale -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.channels.edit.currencies-and-locales')
                            </p>
                        </div>
                    </x-slot>
            
                    <x-slot:content>
                        <!-- Locales Checkboxes -->
                        <div class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.edit.locales') 
                            </x-admin::form.control-group.label>

                            @php $selectedLocalesId = old('locales') ?? $channel->locales->pluck('id')->toArray(); @endphp
                            
                            @foreach (core()->getAllLocales() as $locale)
                                <x-admin::form.control-group class="!mb-2 flex items-center gap-2.5">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        :id="'locales_' . $locale->id" 
                                        name="locales[]"
                                        rules="required"
                                        :value="$locale->id"
                                        :for="'locales_' . $locale->id" 
                                        :label="trans('admin::app.settings.channels.edit.locales')"
                                        :checked="in_array($locale->id, $selectedLocalesId)"
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
                        <x-admin::form.control-group class="mb-4">
                            <x-admin::form.control-group.label class="required">
                                @lang('admin::app.settings.channels.edit.default-locale')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                id="default_locale_id"
                                name="default_locale_id"
                                rules="required"
                                :value="old('default_locale_id') ?? $channel->default_locale_id"
                                :label="trans('admin::app.settings.channels.edit.default-locale')"
                            >
                                @foreach (core()->getAllLocales() as $locale)
                                    <option
                                        value="{{ $locale->id }}"
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
                                @lang('admin::app.settings.channels.edit.currencies')
                            </x-admin::form.control-group.label>
                        
                            @php $selectedCurrenciesId = old('currencies') ?: $channel->currencies->pluck('id')->toArray(); @endphp

                            @foreach (core()->getAllCurrencies() as $currency)
                                <x-admin::form.control-group class="!mb-2 flex items-center gap-2.5">
                                    <x-admin::form.control-group.control
                                        type="checkbox"
                                        :id="'currencies_' . $currency->id"
                                        name="currencies[]"
                                        rules="required"
                                        :value="$currency->id" 
                                        :for="'currencies_' . $currency->id"
                                        :label="trans('admin::app.settings.channels.edit.currencies')"
                                        :checked="in_array($currency->id, $selectedCurrenciesId)"
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
                                @lang('admin::app.settings.channels.edit.default-currency')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="select"
                                id="base_currency_id"
                                name="base_currency_id"
                                rules="required"
                                :value="old('base_currency_id') ?? $channel->base_currency_id"
                                :label="trans('admin::app.settings.channels.edit.default-currency')"
                            >
                                @foreach (core()->getAllCurrencies() as $currency)
                                    <option
                                        value="{{ $currency->id }}"
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

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.accordion.currencies_and_locales.after', ['channel' => $channel]) !!}
                
                {!! view_render_event('bagisto.admin.settings.channels.edit.card.accordion.settings.before', ['channel' => $channel]) !!}
                
                <!-- Maintenance Mode -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.channels.edit.maintenance-mode')
                            </p>
                        </div>
                    </x-slot>
            
                    <x-slot:content>
                        <!-- Maintenance Mode Text -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.edit.maintenance-mode-text')
                            </x-admin::form.control-group.label>
                            
                            <x-admin::form.control-group.control
                                type="text"
                                id="maintenance-mode-text"
                                name="{{ $locale->code }}[maintenance_mode_text]"
                                :value="old('maintenance_mode_text') ?? ($channel->translate($locale)['maintenance_mode_text'] ?? $channel->maintenance_mode_text)"
                                :label="trans('admin::app.settings.channels.edit.maintenance-mode-text')"
                                :placeholder="trans('admin::app.settings.channels.edit.maintenance-mode-text')"
                            />
                        
                            <x-admin::form.control-group.error control-name="maintenance_mode_text" />
                        </x-admin::form.control-group>

                        <!-- Allowed API's -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="!text-gray-800 dark:!text-white">
                                @lang('admin::app.settings.channels.edit.allowed-ips')
                            </x-admin::form.control-group.label>
                            
                            <x-admin::form.control-group.control
                                type="text"
                                id="allowed-ips"
                                name="allowed_ips"
                                :value="old('allowed_ips') ?? $channel->allowed_ips"
                                :label="trans('admin::app.settings.channels.edit.allowed-ips')"
                                :placeholder="trans('admin::app.settings.channels.edit.allowed-ips')"
                            />
                            
                            <x-admin::form.control-group.error control-name="allowed_ips" />
                        </x-admin::form.control-group>

                        <!-- Maintenance Mode Switcher -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.edit.status')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="switch"
                                name="is_maintenance_on"
                                :value="1"
                                :label="trans('admin::app.settings.channels.edit.status')"
                                :checked="(boolean) $channel->is_maintenance_on"
                            />

                            <x-admin::form.control-group.error control-name="is_maintenance_on" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>

                {!! view_render_event('bagisto.admin.settings.channels.edit.card.accordion.settings.after', ['channel' => $channel]) !!}

                <!-- WhatsApp Support -->
                <x-admin::accordion>
                    <x-slot:header>
                        <div class="flex items-center justify-between">
                            <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                                @lang('admin::app.settings.channels.edit.whatsapp-support')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <!-- WhatsApp Phone Number -->
                        <x-admin::form.control-group class="!mb-0">
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.edit.whatsapp-number')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                id="whatsapp_number"
                                name="whatsapp_number"
                                :value="old('whatsapp_number') ?? $channel->whatsapp_number"
                                :label="trans('admin::app.settings.channels.edit.whatsapp-number')"
                                :placeholder="trans('admin::app.settings.channels.edit.whatsapp-number-placeholder')"
                            />

                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                @lang('admin::app.settings.channels.edit.whatsapp-number-tip')
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
                                @lang('admin::app.settings.channels.edit.social-links')
                            </p>
                        </div>
                    </x-slot>

                    <x-slot:content>
                        <p class="mb-4 text-xs text-gray-600 dark:text-gray-300">
                            @lang('admin::app.settings.channels.edit.social-links-tip')
                        </p>

                        @php
                            $socialLinks = old('social_links') ?? ($channel->social_links ?? []);
                        @endphp

                        <!-- Facebook -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('admin::app.settings.channels.edit.facebook')
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
                                @lang('admin::app.settings.channels.edit.instagram')
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
                                @lang('admin::app.settings.channels.edit.twitter')
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
                                @lang('admin::app.settings.channels.edit.youtube')
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
                                @lang('admin::app.settings.channels.edit.tiktok')
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
                                @lang('admin::app.settings.channels.edit.snapchat')
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
                                @lang('admin::app.settings.channels.edit.telegram')
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
                            $paymentMethods = old('payment_methods') ?? ($channel->payment_methods ?? []);
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

                        @if(! empty($deletedKeys))
                            <!-- Restore Deleted Methods Section -->
                            <div class="mb-5 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-amber-800 dark:text-amber-300 font-medium">وسائل دفع تم حذفها:</span>
                                    <select id="restore-payment-select" class="text-xs rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 py-1 px-2">
                                        @foreach($deletedKeys as $delKey)
                                            <option value="{{ $delKey }}">{{ $availableMethods[$delKey]['title'] ?? $delKey }} ({{ $delKey }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button
                                    type="button"
                                    onclick="window.restorePaymentMethodItem()"
                                    class="secondary-button text-xs py-1 px-3"
                                >
                                    + استعادة وسيلة الدفع
                                </button>
                            </div>
                        @endif

                        <!-- Custom Payment Logos -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-800">
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-1">إضافة شعار وسيلة دفع مخصصة (صورة)</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">يمكنك رفع صورة شعار إضافي لوسيلة دفع محلية أو مخصصة (PNG, SVG, WebP).</p>

                            @if(! empty($paymentMethods['custom_logos']))
                                <div class="space-y-2 mb-4">
                                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">الشعارات المخصصة المرفوعة حالياً:</p>
                                    @foreach($paymentMethods['custom_logos'] as $cIdx => $cLogo)
                                        <div id="custom-logo-card-{{ $cIdx }}" class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ Storage::url($cLogo['image']) }}" alt="{{ $cLogo['title'] ?? 'شعار' }}" class="h-6 w-auto object-contain">
                                                <span class="text-xs font-medium text-gray-800 dark:text-white">{{ $cLogo['title'] ?? 'وسيلة دفع مخصصة' }}</span>
                                            </div>
                                            <button
                                                type="button"
                                                data-action="delete-custom-logo"
                                                data-index="{{ $cIdx }}"
                                                onclick="window.removeCustomPaymentLogo({{ $cIdx }})"
                                                title="حذف هذا الشعار المخصص"
                                                class="w-7 h-7 flex items-center justify-center rounded text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                            >
                                                <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                            <input type="hidden" name="payment_methods[custom_logos][{{ $cIdx }}][title]" value="{{ $cLogo['title'] }}">
                                            <input type="hidden" name="payment_methods[custom_logos][{{ $cIdx }}][image]" value="{{ $cLogo['image'] }}">
                                        </div>
                                    @endforeach
                                </div>
                            @endif

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

        {!! view_render_event('bagisto.admin.settings.channels.edit.edit_form_controls.after', ['channel' => $channel]) !!}

    </x-admin::form> 

    {!! view_render_event('bagisto.admin.settings.channels.edit.after', ['channel' => $channel]) !!}

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

        window.restorePaymentMethodItem = function() {
            var select = document.getElementById('restore-payment-select');
            if (select && select.value) {
                var restoreKey = select.value;
                var container = document.getElementById('deleted-payment-methods-container');
                if (container) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'payment_methods[restore_key]';
                    input.value = restoreKey;
                    container.appendChild(input);
                }
                var form = select.closest('form');
                if (form) {
                    form.submit();
                }
            }
        };

        window.removeCustomPaymentLogo = function(index) {
            if (confirm('هل أنت متأكد من حذف هذا الشعار المخصص؟')) {
                var el = document.getElementById('custom-logo-card-' + index);
                if (el) {
                    el.remove();
                }
                var container = document.getElementById('deleted-payment-methods-container');
                if (container) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'payment_methods[remove_custom_logos][' + index + ']';
                    input.value = '1';
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

            var customBtn = e.target.closest('[data-action="delete-custom-logo"]');
            if (customBtn) {
                e.preventDefault();
                e.stopPropagation();
                var index = customBtn.getAttribute('data-index');
                window.removeCustomPaymentLogo(index);
                return;
            }
        });
    </script>
@endPush
