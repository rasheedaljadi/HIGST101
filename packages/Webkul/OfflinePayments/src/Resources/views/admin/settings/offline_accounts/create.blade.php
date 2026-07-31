<x-admin::layouts>
    <x-slot:title>
        @lang('offline_payments::app.admin.form.create-title')
    </x-slot>

    <x-admin::form
        :action="route('admin.settings.offline_accounts.store')"
        enctype="multipart/form-data"
    >
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('offline_payments::app.admin.form.create-title')
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('admin.settings.offline_accounts.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    @lang('offline_payments::app.admin.form.cancel')
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    @lang('offline_payments::app.admin.form.save')
                </button>
            </div>
        </div>

        <div class="mt-3.5 flex gap-2.5 max-xl:flex-wrap">
            <!-- Left Panel: General Info & Destinations Repeater -->
            <div class="flex flex-1 flex-col gap-4 max-xl:flex-auto">
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('offline_payments::app.admin.form.general')
                    </p>

                    <!-- Display Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('offline_payments::app.admin.form.display-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="display_name"
                            rules="required"
                            value="{{ old('display_name') }}"
                            :label="trans('offline_payments::app.admin.form.display-name')"
                            :placeholder="trans('offline_payments::app.admin.form.display-name')"
                        />

                        <x-admin::form.control-group.error control-name="display_name" />
                    </x-admin::form.control-group>

                    <!-- Provider Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('offline_payments::app.admin.form.provider-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="provider_name"
                            rules="required"
                            value="{{ old('provider_name') }}"
                            :label="trans('offline_payments::app.admin.form.provider-name')"
                            :placeholder="trans('offline_payments::app.admin.form.provider-name')"
                        />

                        <x-admin::form.control-group.error control-name="provider_name" />
                    </x-admin::form.control-group>

                    <!-- Recipient Name -->
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            @lang('offline_payments::app.admin.form.recipient-name')
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            name="recipient_name"
                            rules="required"
                            value="{{ old('recipient_name') }}"
                            :label="trans('offline_payments::app.admin.form.recipient-name')"
                            :placeholder="trans('offline_payments::app.admin.form.recipient-name')"
                        />

                        <x-admin::form.control-group.error control-name="recipient_name" />
                    </x-admin::form.control-group>
                </div>

                <!-- Dynamic Payment Destinations Panel -->
                <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <p class="text-base font-semibold text-gray-800 dark:text-white">
                            @lang('offline_payments::app.admin.destinations.title')
                        </p>
                    </div>

                    <v-offline-destinations-repeater
                        :currencies="{{ json_encode($currencies) }}"
                    ></v-offline-destinations-repeater>
                </div>
            </div>

            <!-- Right Panel: Status, Channels, Sort Order & Logo -->
            <div class="flex w-[360px] max-w-full flex-col gap-2 max-sm:w-full">
                <x-admin::accordion>
                    <x-slot:header>
                        <p class="p-2.5 text-base font-semibold text-gray-800 dark:text-white">
                            @lang('offline_payments::app.admin.form.general')
                        </p>
                    </x-slot>

                    <x-slot:content>
                        <!-- Status -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('offline_payments::app.admin.form.status')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="switch"
                                name="is_active"
                                value="1"
                                :checked="old('is_active', true)"
                            />
                        </x-admin::form.control-group>

                        <!-- Channels -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('offline_payments::app.admin.form.channels')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="multiselect"
                                name="channel_ids[]"
                                rules="required"
                                :label="trans('offline_payments::app.admin.form.channels')"
                            >
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                                @endforeach
                            </x-admin::form.control-group.control>

                            <x-admin::form.control-group.error control-name="channel_ids[]" />
                        </x-admin::form.control-group>

                        <!-- Sort Order -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('offline_payments::app.admin.form.sort-order')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="text"
                                name="sort_order"
                                value="{{ old('sort_order', 0) }}"
                                :label="trans('offline_payments::app.admin.form.sort-order')"
                            />

                            <x-admin::form.control-group.error control-name="sort_order" />
                        </x-admin::form.control-group>

                        <!-- Logo Path -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('offline_payments::app.admin.form.logo')
                            </x-admin::form.control-group.label>

                            <x-admin::form.control-group.control
                                type="file"
                                name="logo_path"
                                :label="trans('offline_payments::app.admin.form.logo')"
                            />

                            <x-admin::form.control-group.error control-name="logo_path" />
                        </x-admin::form.control-group>
                    </x-slot>
                </x-admin::accordion>
            </div>
        </div>
    </x-admin::form>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-offline-destinations-repeater-template"
        >
            <div>
                <div
                    v-for="(dest, index) in destinations"
                    :key="index"
                    class="mb-4 p-4 border rounded border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800 relative"
                >
                    <div class="flex items-center justify-between mb-3 border-b pb-2 border-gray-200 dark:border-gray-700">
                        <span class="font-semibold text-sm text-gray-800 dark:text-white">
                            @lang('offline_payments::app.admin.destinations.destination-row') #@{{ index + 1 }}
                        </span>

                        <button
                            type="button"
                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                            @click="removeDestination(index)"
                            v-if="destinations.length > 1"
                        >
                            @lang('offline_payments::app.admin.destinations.remove')
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Currency Selection -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('offline_payments::app.admin.form.currency')
                            </x-admin::form.control-group.label>

                            <select
                                :name="'destinations[' + index + '][currency_id]'"
                                v-model="dest.currency_id"
                                class="w-full rounded border px-3 py-2 text-sm text-gray-800 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                required
                            >
                                <option value="" disabled>@lang('offline_payments::app.admin.destinations.select-currency')</option>
                                <option v-for="curr in currencies" :key="curr.id" :value="curr.id">
                                    @{{ curr.code }} (@{{ curr.name }})
                                </option>
                            </select>
                        </x-admin::form.control-group>

                        <!-- Account Identifier -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label class="required">
                                @lang('offline_payments::app.admin.form.account-identifier')
                            </x-admin::form.control-group.label>

                            <input
                                type="text"
                                :name="'destinations[' + index + '][account_identifier]'"
                                v-model="dest.account_identifier"
                                class="w-full rounded border px-3 py-2 text-sm text-gray-800 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="@lang('offline_payments::app.admin.form.account-identifier')"
                                required
                            />
                        </x-admin::form.control-group>

                        <!-- SWIFT Code -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('offline_payments::app.admin.destinations.swift-code')
                            </x-admin::form.control-group.label>

                            <input
                                type="text"
                                :name="'destinations[' + index + '][swift_code]'"
                                v-model="dest.swift_code"
                                class="w-full rounded border px-3 py-2 text-sm text-gray-800 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                placeholder="e.g. RJHISARI"
                            />
                        </x-admin::form.control-group>

                        <!-- Sort Order -->
                        <x-admin::form.control-group>
                            <x-admin::form.control-group.label>
                                @lang('offline_payments::app.admin.form.sort-order')
                            </x-admin::form.control-group.label>

                            <input
                                type="number"
                                :name="'destinations[' + index + '][sort_order]'"
                                v-model="dest.sort_order"
                                class="w-full rounded border px-3 py-2 text-sm text-gray-800 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                        </x-admin::form.control-group>
                    </div>

                    <!-- Transfer Instructions -->
                    <x-admin::form.control-group class="mt-2">
                        <x-admin::form.control-group.label>
                            @lang('offline_payments::app.admin.form.transfer-instructions')
                        </x-admin::form.control-group.label>

                        <textarea
                            :name="'destinations[' + index + '][transfer_instructions]'"
                            v-model="dest.transfer_instructions"
                            rows="2"
                            class="w-full rounded border px-3 py-2 text-sm text-gray-800 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            placeholder="@lang('offline_payments::app.admin.form.transfer-instructions')"
                        ></textarea>
                    </x-admin::form.control-group>
                </div>

                <button
                    type="button"
                    class="secondary-button"
                    @click="addDestination"
                >
                    + @lang('offline_payments::app.admin.destinations.add-destination')
                </button>
            </div>
        </script>

        <script type="module">
            app.component('v-offline-destinations-repeater', {
                template: '#v-offline-destinations-repeater-template',
                props: {
                    currencies: {
                        type: Array,
                        required: true,
                    }
                },
                data() {
                    return {
                        destinations: [
                            {
                                currency_id: '',
                                account_identifier: '',
                                swift_code: '',
                                transfer_instructions: '',
                                sort_order: 0,
                            }
                        ]
                    };
                },
                methods: {
                    addDestination() {
                        this.destinations.push({
                            currency_id: '',
                            account_identifier: '',
                            swift_code: '',
                            transfer_instructions: '',
                            sort_order: this.destinations.length,
                        });
                    },
                    removeDestination(index) {
                        if (this.destinations.length > 1) {
                            this.destinations.splice(index, 1);
                        }
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
