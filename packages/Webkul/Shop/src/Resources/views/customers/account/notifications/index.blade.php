<x-shop::layouts.account>
    <!-- Page Title -->
    <x-slot:title>
        الإشعارات
    </x-slot>

    <!-- Breadcrumbs -->
    @if (core()->getConfigData('general.general.breadcrumbs.shop'))
        @section('breadcrumbs')
            <x-shop::breadcrumbs name="notifications" />
        @endSection
    @endif

    <div class="flex-1">
        <v-customer-notifications-page></v-customer-notifications-page>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-customer-notifications-page-template">
            <div>
                <!-- Header Title & Read All Action -->
                <div class="flex items-center justify-between pb-6 border-b border-zinc-200">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-navyBlue dark:text-white">
                            الإشعارات
                        </h1>

                        <span
                            v-if="totalUnread > 0"
                            class="px-2.5 py-0.5 text-xs font-semibold text-white bg-red-500 rounded-full"
                        >
                            @{{ totalUnread }} غير مقروء
                        </span>
                    </div>

                    <button
                        v-if="notifications.length && totalUnread > 0"
                        @click="markAllAsRead"
                        class="text-sm font-medium text-navyBlue hover:underline transition-all cursor-pointer"
                        :disabled="isProcessing"
                    >
                        تعليم الكل كمقروء
                    </button>
                </div>

                <!-- Loading State -->
                <div v-if="isLoading" class="py-10 space-y-4">
                    <div v-for="n in 5" :key="n" class="p-4 rounded-xl border border-zinc-200 shimmer h-20"></div>
                </div>

                <!-- Empty State -->
                <div v-else-if="!notifications.length" class="py-16 text-center">
                    <span class="block mx-auto text-5xl text-zinc-400 icon-notification mb-4"></span>
                    <p class="text-xl font-medium text-zinc-600 dark:text-zinc-300">
                        لا توجد إشعارات حالياً
                    </p>
                    <p class="mt-1 text-sm text-zinc-400">
                        ستظهر جميع التحديثات المتعلقة بطلباتك ومحفظتك هنا.
                    </p>
                </div>

                <!-- Notifications List -->
                <div v-else class="divide-y divide-zinc-200">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        class="flex items-start justify-between p-5 transition-all cursor-pointer hover:bg-zinc-50 rounded-xl my-2"
                        :class="{'bg-blue-50/50 border-s-4 border-navyBlue': !notification.read, 'bg-white': notification.read}"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Status Indicator Dot -->
                            <span
                                class="mt-1.5 h-3 w-3 rounded-full flex-shrink-0"
                                :class="!notification.read ? 'bg-navyBlue' : 'bg-zinc-300'"
                            ></span>

                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    @{{ notification.title || 'إشعار جديد' }}
                                </h3>

                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                    @{{ notification.message }}
                                </p>

                                <span class="block mt-2 text-xs text-zinc-400">
                                    @{{ notification.created_at }}
                                </span>
                            </div>
                        </div>

                        <!-- Read Status Badge -->
                        <div class="flex items-center gap-2">
                            <span
                                v-if="!notification.read"
                                class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-md font-medium"
                            >
                                جديد
                            </span>
                            <span class="text-xl icon-arrow-right ltr:rotate-180 text-zinc-400"></span>
                        </div>
                    </div>
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-customer-notifications-page', {
                template: '#v-customer-notifications-page-template',

                data() {
                    return {
                        isLoading: true,
                        isProcessing: false,
                        notifications: [],
                        totalUnread: 0,
                    }
                },

                mounted() {
                    this.getNotifications();
                },

                methods: {
                    getNotifications() {
                        this.isLoading = true;
                        this.$axios.get("{{ route('shop.customers.account.notifications.get') }}")
                            .then(response => {
                                this.isLoading = false;
                                this.notifications = response.data.notifications.data || [];
                                this.totalUnread = response.data.total_unread || 0;
                            })
                            .catch(error => {
                                this.isLoading = false;
                                console.error(error);
                            });
                    },

                    handleNotificationClick(notification) {
                        if (!notification.read) {
                            this.$axios.post(`{{ url('customer/account/notifications/mark-as-read') }}/${notification.id}`)
                                .then(response => {
                                    window.location.href = response.data.redirect_url;
                                })
                                .catch(() => {
                                    window.location.href = notification.action_url || "{{ route('shop.customers.account.notifications.index') }}";
                                });
                        } else {
                            window.location.href = notification.action_url || "{{ route('shop.customers.account.notifications.index') }}";
                        }
                    },

                    markAllAsRead() {
                        this.isProcessing = true;
                        this.$axios.post("{{ route('shop.customers.account.notifications.mark_all_as_read') }}")
                            .then(response => {
                                this.isProcessing = false;
                                this.totalUnread = 0;
                                this.notifications.forEach(n => n.read = 1);
                            })
                            .catch(error => {
                                this.isProcessing = false;
                                console.error(error);
                            });
                    }
                }
            });
        </script>
    @endpushOnce
</x-shop::layouts.account>
