<x-admin::layouts>
    <x-slot:title>
        @lang('mobile_api::app.admin.keys.title')
    </x-slot>

    <div class="flex items-center justify-between">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('mobile_api::app.admin.keys.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <button 
                type="button" 
                onclick="document.getElementById('create-key-modal').classList.remove('hidden')"
                class="primary-button"
            >
                @lang('mobile_api::app.admin.keys.create-btn')
            </button>
        </div>
    </div>

    <!-- Create Key Modal -->
    <div id="create-key-modal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-gray-800 pb-3">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    @lang('mobile_api::app.admin.keys.create-modal-title')
                </h3>
                <button type="button" onclick="document.getElementById('create-key-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xl font-bold">✕</button>
            </div>

            <form action="{{ route('admin.settings.mobile_api_keys.store') }}" method="POST" class="flex flex-col gap-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">
                        @lang('mobile_api::app.admin.keys.key-name')
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        required 
                        placeholder="HIGEST Mobile App (Production)" 
                        class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white p-2 text-sm"
                    >
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-gray-600 dark:text-gray-400 mb-1">
                        @lang('mobile_api::app.admin.keys.channel')
                    </label>
                    <select name="channel_id" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white p-2 text-sm">
                        <option value="">@lang('mobile_api::app.admin.keys.all-channels')</option>
                        @foreach ($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }} ({{ $channel->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="document.getElementById('create-key-modal').classList.add('hidden')" class="secondary-button">
                        @lang('mobile_api::app.admin.keys.cancel')
                    </button>
                    <button type="submit" class="primary-button">
                        @lang('mobile_api::app.admin.keys.generate')
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-4">
        <x-admin::datagrid :src="route('admin.settings.mobile_api_keys.index')" />
    </div>
</x-admin::layouts>
