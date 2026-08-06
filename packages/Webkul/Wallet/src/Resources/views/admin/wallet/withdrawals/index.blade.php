<x-admin::layouts>
    <x-slot:title>
        {{ __('wallet::app.admin.wallet.withdrawals.title') ?? 'Wallet Withdrawals' }}
    </x-slot:title>

    <v-wallet-withdrawals></v-wallet-withdrawals>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-wallet-withdrawals-template"
        >
            <div class="flex flex-col gap-4 p-6">
                <div class="flex items-center justify-between">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        {{ __('wallet::app.admin.wallet.withdrawals.title') ?? 'Wallet Withdrawals' }}
                    </p>
                </div>

                <x-admin::datagrid
                    :src="route('admin.wallet.withdrawals.index')"
                    ref="datagrid"
                >
                    <template #body="{ isLoading, available, performAction }">
                        <template v-if="isLoading">
                            <x-admin::shimmer.datagrid.table.body />
                        </template>

                        <template v-else>
                            <template v-if="available.records.length">
                                <div
                                    class="row grid items-center gap-2.5 border-b px-4 py-4 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950"
                                    v-for="record in available.records"
                                    :key="record.id"
                                    :style="`grid-template-columns: repeat(${available.columns.length + (available.actions.length ? 1 : 0)}, minmax(150px, 1fr))`"
                                >
                                    <template v-for="column in available.columns">
                                        <p
                                            class="break-words"
                                            v-html="record[column.index]"
                                            v-if="column.visibility"
                                        >
                                        </p>
                                    </template>

                                    <p
                                        class="place-self-end flex items-center gap-1"
                                        v-if="available.actions.length"
                                    >
                                        <template v-for="action in record.actions" :key="action.index">
                                            <a
                                                v-if="action.method === 'GET'"
                                                :href="action.url"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                :class="action.icon"
                                                :title="action.title"
                                            >
                                            </a>
                                            <span
                                                v-else
                                                class="cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800 max-sm:place-self-center"
                                                :class="action.icon"
                                                :title="action.title"
                                                v-text="! action.icon ? action.title : ''"
                                                @click="action.index === 'reject' ? openRejectModal(action) : performAction(action)"
                                            >
                                            </span>
                                        </template>
                                    </p>
                                </div>
                            </template>

                            <template v-else>
                                <div class="row grid border-b px-4 py-4 text-center text-gray-600 dark:border-gray-800 dark:text-gray-300">
                                    <p>@lang('admin::app.components.datagrid.table.no-records-available')</p>
                                </div>
                            </template>
                        </template>
                    </template>
                </x-admin::datagrid>

                <!-- Rejection Reason Modal Form -->
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, submitRejectForm)">
                        <x-admin::modal ref="rejectModal">
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    {{ __('wallet::app.admin.wallet.withdrawals.reject-title') ?? 'رفض طلب سحب الرصيد' }}
                                </p>
                            </x-slot:header>

                            <x-slot:content>
                                <div class="px-4 py-2 flex flex-col gap-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ __('wallet::app.admin.wallet.withdrawals.reject-confirm') ?? 'هل أنت متأكد من رغبتك في رفض طلب السحب هذا؟ يرجى إدخال سبب الرفض لتوضيحه للعميل وسيتم فك الحجز عن المبلغ وإعادته للمحفظة:' }}
                                    </p>

                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            {{ __('wallet::app.admin.wallet.withdrawals.reject-reason') ?? 'سبب الرفض' }}
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="textarea"
                                            name="rejection_reason"
                                            rules="required"
                                            v-model="rejectReason"
                                            placeholder="{{ __('wallet::app.admin.wallet.withdrawals.reject-reason-placeholder') ?? 'يرجى توضيح سبب الرفض...' }}"
                                            rows="4"
                                        />

                                        <x-admin::form.control-group.error control-name="rejection_reason" />
                                    </x-admin::form.control-group>
                                </div>
                            </x-slot:content>

                            <x-slot:footer>
                                <div class="flex items-center gap-2.5">
                                    <button
                                        type="submit"
                                        class="primary-button bg-red-600 border-red-600 hover:bg-red-700 text-white"
                                        :disabled="isSubmitting"
                                    >
                                        {{ __('wallet::app.admin.wallet.withdrawals.confirm-reject') ?? 'تأكيد الرفض' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="transparent-button"
                                        @click="closeRejectModal"
                                    >
                                        {{ __('wallet::app.admin.wallet.withdrawals.cancel') ?? 'إلغاء' }}
                                    </button>
                                </div>
                            </x-slot:footer>
                        </x-admin::modal>
                    </form>
                </x-admin::form>
            </div>
        </script>

        <script type="module">
            app.component('v-wallet-withdrawals', {
                template: '#v-wallet-withdrawals-template',

                data() {
                    return {
                        selectedRejectAction: null,
                        rejectReason: '',
                        isSubmitting: false,
                    };
                },

                methods: {
                    openRejectModal(action) {
                        this.selectedRejectAction = action;
                        this.rejectReason = '';

                        let modal = this.$refs.rejectModal;
                        if (Array.isArray(modal)) {
                            modal = modal[0];
                        }
                        if (modal && typeof modal.open === 'function') {
                            modal.open();
                        } else if (modal && typeof modal.toggle === 'function') {
                            modal.toggle();
                        }
                    },

                    closeRejectModal() {
                        let modal = this.$refs.rejectModal;
                        if (Array.isArray(modal)) {
                            modal = modal[0];
                        }
                        if (modal && typeof modal.close === 'function') {
                            modal.close();
                        } else if (modal && typeof modal.toggle === 'function') {
                            modal.toggle();
                        }
                    },

                    submitRejectForm(params, { resetForm }) {
                        if (! this.selectedRejectAction) return;

                        this.isSubmitting = true;

                        this.$axios.post(this.selectedRejectAction.url, {
                            rejection_reason: this.rejectReason,
                            admin_notes: this.rejectReason
                        })
                        .then(response => {
                            this.isSubmitting = false;
                            this.closeRejectModal();
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                            let datagrid = this.$refs.datagrid;
                            if (Array.isArray(datagrid)) {
                                datagrid = datagrid[0];
                            }
                            if (datagrid && typeof datagrid.get === 'function') {
                                datagrid.get();
                            }

                            if (typeof resetForm === 'function') {
                                resetForm();
                            }
                        })
                        .catch(error => {
                            this.isSubmitting = false;
                            const msg = error.response?.data?.message || 'حدث خطأ أثناء رفض الطلب.';
                            this.$emitter.emit('add-flash', { type: 'error', message: msg });
                        });
                    }
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>
