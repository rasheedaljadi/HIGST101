<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.notifications.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.marketing.notifications.create.before') !!}

    <!-- Vue Component -->
    <v-notification-list>
        <!-- Shimmer Effect -->
        <x-admin::shimmer.notifications />
    </v-notification-list>

    {!! view_render_event('bagisto.admin.marketing.notifications.create.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-notification-list-template"
        >
            <template v-if="isLoading">
                <!-- Shimmer Effect -->
                <x-admin::shimmer.notifications />
            </template>

            <template v-else>
                <!-- Page Header -->
                <div class="mb-6 flex items-center justify-between gap-4 max-sm:flex-wrap">
                    <div class="grid gap-1">
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white sm:text-2xl">
                                @lang('admin::app.notifications.title')
                            </h1>
                            <span 
                                class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300"
                                v-if="totalUnRead"
                            >
                                @{{ totalUnRead }} غير مقروء
                            </span>
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @lang('admin::app.notifications.description-text')
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="secondary-button flex items-center gap-1.5"
                            v-if="notifications.length && totalUnRead > 0"
                            @click="readAll()"
                        >
                            <i class="icon-done text-base"></i>
                            <span>@lang('admin::app.notifications.read-all')</span>
                        </button>
                    </div>
                </div>

                <!-- Main Notifications Container -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <!-- Category Tabs Header -->
                    <div class="journal-scroll flex overflow-x-auto border-b border-gray-200 dark:border-gray-800 px-2">
                        <button
                            type="button"
                            class="flex items-center gap-2 border-b-2 px-4 py-3.5 text-sm font-semibold transition-all whitespace-nowrap"
                            :class="category === catKey 
                                ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400' 
                                : 'border-transparent text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                            v-for="(tab, catKey) in categoryTabs"
                            :key="catKey"
                            @click="changeCategory(catKey)"
                        >
                            <span :class="tab.icon"></span>
                            <span>@{{ tab.label }}</span>
                            <span 
                                class="rounded-full px-2 py-0.5 text-xs font-bold"
                                :class="category === catKey 
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' 
                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                            >
                                @{{ categoryCounts[catKey] ?? 0 }}
                            </span>
                        </button>
                    </div>

                    <!-- Notifications List -->
                    <div
                        class="divide-y divide-gray-100 dark:divide-gray-800"
                        v-if="notifications.length"
                    >
                        <div
                            class="flex items-start justify-between gap-4 p-4 sm:p-5 transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-950/50"
                            :class="{'bg-blue-50/25 dark:bg-blue-950/15': !notification.read}"
                            v-for="notification in notifications"
                            :key="notification.id"
                        >
                            <div class="flex items-start gap-3.5 flex-1 min-w-0">
                                <!-- Type Icon Badge -->
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xl font-bold shadow-sm"
                                    :class="notification.badge_class || 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400'"
                                >
                                    <span :class="notification.icon_class || 'icon-information'"></span>
                                </div>

                                <!-- Text Content -->
                                <div class="grid flex-1 gap-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a 
                                            :href="notification.action_url"
                                            class="text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 leading-snug"
                                        >
                                            @{{ notification.display_title || notification.title || ('إشعار #' + notification.id) }}
                                        </a>

                                        <span 
                                            class="inline-flex items-center gap-1 rounded-md bg-blue-100 px-1.5 py-0.5 text-[10px] font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300"
                                            v-if="!notification.read"
                                        >
                                            جديد
                                        </span>
                                    </div>

                                    <p 
                                        class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed break-words"
                                        v-if="notification.display_message || notification.message"
                                    >
                                        @{{ notification.display_message || notification.message }}
                                    </p>

                                    <div class="flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        <span class="flex items-center gap-1">
                                            <i class="icon-clock text-xs"></i>
                                            @{{ notification.time_ago || notification.created_at }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Link Button -->
                            <div class="flex items-center gap-2 shrink-0">
                                <a
                                    :href="notification.action_url"
                                    class="secondary-button !py-1.5 !px-3 text-xs font-semibold"
                                >
                                    <span>عرض التفاصيل</span>
                                    <i class="icon-sort-left rtl:icon-sort-left ltr:icon-sort-right text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        class="p-12 text-center"
                        v-else
                    >
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-3xl text-gray-400 mb-3">
                            <i class="icon-notification"></i>
                        </div>
                        <h3 class="text-base font-bold text-gray-800 dark:text-white">
                            @lang('admin::app.notifications.no-record')
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            لا توجد تنبيهات جديدة في هذا التصنيف حالياً.
                        </p>
                    </div>

                    <!-- Pagination Footer -->
                    <div 
                        class="flex items-center justify-between border-t border-gray-200 p-4 dark:border-gray-800 max-sm:flex-wrap gap-3"
                        v-if="pagination.total > pagination.per_page"
                    >
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            عرض 
                            <span class="font-semibold text-gray-800 dark:text-gray-200">@{{ pagination.from ?? 0 }}</span>
                            إلى 
                            <span class="font-semibold text-gray-800 dark:text-gray-200">@{{ pagination.to ?? 0 }}</span>
                            من إجمالي 
                            <span class="font-semibold text-gray-800 dark:text-gray-200">@{{ pagination.total ?? 0 }}</span>
                            إشعار
                        </div>

                        <!-- Prev & Next Buttons -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="secondary-button !p-2 disabled:opacity-50"
                                :disabled="!pagination.prev_page_url"
                                @click="getResults(pagination.prev_page_url)"
                            >
                                <span class="icon-sort-right rtl:icon-sort-right ltr:icon-sort-left text-lg"></span>
                            </button>

                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 px-2">
                                صفحة @{{ pagination.current_page }} من @{{ pagination.last_page }}
                            </span>

                            <button
                                type="button"
                                class="secondary-button !p-2 disabled:opacity-50"
                                :disabled="!pagination.next_page_url"
                                @click="getResults(pagination.next_page_url)"
                            >
                                <span class="icon-sort-left rtl:icon-sort-left ltr:icon-sort-right text-lg"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </script>

        <script type="module">
            app.component('v-notification-list', {
                template: '#v-notification-list-template',

                data() {
                    return {
                        notifications: [],
                        pagination: {},
                        category: 'all',
                        totalUnRead: 0,
                        isLoading: true,

                        categoryCounts: {
                            all: 0,
                            orders: 0,
                            inventory: 0,
                            sync: 0,
                            finance: 0,
                        },

                        categoryTabs: {
                            all: {
                                label: "@lang('admin::app.notifications.categories.all')",
                                icon: 'icon-notification text-base'
                            },
                            orders: {
                                label: "@lang('admin::app.notifications.categories.orders')",
                                icon: 'icon-cart text-base'
                            },
                            inventory: {
                                label: "@lang('admin::app.notifications.categories.inventory')",
                                icon: 'icon-product text-base'
                            },
                            sync: {
                                label: "@lang('admin::app.notifications.categories.sync')",
                                icon: 'icon-processing text-base'
                            },
                            finance: {
                                label: "@lang('admin::app.notifications.categories.finance')",
                                icon: 'icon-dollar-circle text-base'
                            },
                        },
                    };
                },

                mounted() {
                    this.getNotification();
                },

                methods: {
                    getNotification(pageUrl = null) {
                        this.isLoading = !this.notifications.length;

                        const params = {
                            limit: 15,
                        };

                        if (this.category !== 'all') {
                            params.category = this.category;
                        }

                        const url = pageUrl || "{{ route('admin.notification.get_notification') }}";

                        this.$axios.get(url, { params: pageUrl ? {} : params })
                            .then((response) => {
                                this.notifications = response.data.search_results.data || [];
                                this.pagination = response.data.search_results || {};
                                this.totalUnRead = response.data.total_unread || 0;

                                if (response.data.category_counts) {
                                    this.categoryCounts = response.data.category_counts;
                                }

                                this.isLoading = false;
                            })
                            .catch(error => {
                                console.log(error);
                                this.isLoading = false;
                            });
                    },

                    changeCategory(catKey) {
                        this.category = catKey;
                        this.getNotification();
                    },

                    getResults(url) {
                        if (url) {
                            this.getNotification(url);
                        }
                    },

                    readAll() {
                        this.$axios.post('{{ route('admin.notification.read_all') }}')
                            .then((response) => {
                                this.totalUnRead = response.data.total_unread || 0;
                                this.$emitter.emit('add-flash', { 
                                    type: 'success', 
                                    message: response.data.success_message 
                                });
                                this.getNotification();
                            })
                            .catch(error => console.log(error));
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
