<x-admin::layouts>
    <x-slot:title>
        {{ __('wallet::app.admin.wallet.withdrawals.title') ?? 'Wallet Withdrawals' }}
    </x-slot:title>

    <div class="p-6 pb-0">
        @include('wallet::admin.layouts.tabs')
    </div>

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
                                                @click="action.index === 'reject' ? openRejectModal(action) : (action.index === 'complete' ? openApproveModal(action) : performAction(action))"
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

                <!-- Withdrawal Approval Modal Form -->
                <x-admin::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, submitApproveForm)">
                        <x-admin::modal ref="approveModal">
                            <x-slot:header>
                                <p class="text-lg font-bold text-gray-800 dark:text-white">
                                    ✓ تأكيد إتمام وتنفيذ طلب السحب
                                </p>
                            </x-slot:header>

                            <x-slot:content>
                                <div class="px-4 py-2 flex flex-col gap-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-300">
                                        يرجى إدخال رقم العملية المرجعية للحوالة وإرفاق صورة الإشعار (إن وجدت) لإبلاغ العميل وإتمام العملية:
                                    </p>

                                    {{-- Mandatory Transaction Reference Field --}}
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label class="required">
                                            رقم مرجع التحويل / رقم العملية
                                        </x-admin::form.control-group.label>

                                        <x-admin::form.control-group.control
                                            type="text"
                                            name="bank_transaction_reference"
                                            rules="required"
                                            v-model="bankTransactionReference"
                                            placeholder="مثال: TR-998124589"
                                        />

                                        <x-admin::form.control-group.error control-name="bank_transaction_reference" />
                                    </x-admin::form.control-group>

                                    {{-- Optional Receipt Image Field --}}
                                    <x-admin::form.control-group>
                                        <x-admin::form.control-group.label>
                                            صورة إشعار التحويل / لقطة الشاشة (اختياري)
                                        </x-admin::form.control-group.label>

                                        <input
                                            type="file"
                                            name="receipt"
                                            accept="image/*,.pdf"
                                            class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:text-gray-300 dark:file:bg-emerald-950 dark:file:text-emerald-300"
                                            @change="onReceiptFileChange"
                                        />
                                    </x-admin::form.control-group>
                                </div>
                            </x-slot:content>

                            <x-slot:footer>
                                <div class="flex items-center gap-2.5">
                                    <button
                                        type="submit"
                                        class="primary-button bg-emerald-600 border-emerald-600 hover:bg-emerald-700 text-white"
                                        :disabled="isSubmitting"
                                    >
                                        تأكيد وإتمام السحب
                                    </button>

                                    <button
                                        type="button"
                                        class="transparent-button"
                                        @click="closeApproveModal"
                                    >
                                        {{ __('wallet::app.admin.wallet.withdrawals.cancel') ?? 'إلغاء' }}
                                    </button>
                                </div>
                            </x-slot:footer>
                        </x-admin::modal>
                    </form>
                </x-admin::form>

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

                        selectedApproveAction: null,
                        bankTransactionReference: '',
                        approveReceiptFile: null,

                        isSubmitting: false,
                    };
                },

                methods: {
                    openApproveModal(action) {
                        this.selectedApproveAction = action;
                        this.bankTransactionReference = '';
                        this.approveReceiptFile = null;

                        let modal = this.$refs.approveModal;
                        if (Array.isArray(modal)) modal = modal[0];
                        if (modal && typeof modal.open === 'function') modal.open();
                        else if (modal && typeof modal.toggle === 'function') modal.toggle();
                    },

                    closeApproveModal() {
                        let modal = this.$refs.approveModal;
                        if (Array.isArray(modal)) modal = modal[0];
                        if (modal && typeof modal.close === 'function') modal.close();
                        else if (modal && typeof modal.toggle === 'function') modal.toggle();
                    },

                    onReceiptFileChange(event) {
                        if (event.target.files && event.target.files.length) {
                            this.approveReceiptFile = event.target.files[0];
                        } else {
                            this.approveReceiptFile = null;
                        }
                    },

                    submitApproveForm(params, { resetForm }) {
                        if (! this.selectedApproveAction) return;

                        this.isSubmitting = true;

                        let formData = new FormData();
                        formData.append('bank_transaction_reference', this.bankTransactionReference);
                        formData.append('bank_reference_id', this.bankTransactionReference);

                        if (this.approveReceiptFile) {
                            formData.append('receipt', this.approveReceiptFile);
                            formData.append('proof', this.approveReceiptFile);
                        }

                        this.$axios.post(this.selectedApproveAction.url, formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        })
                        .then(response => {
                            this.isSubmitting = false;
                            this.closeApproveModal();
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });

                            let datagrid = this.$refs.datagrid;
                            if (Array.isArray(datagrid)) datagrid = datagrid[0];
                            if (datagrid && typeof datagrid.get === 'function') datagrid.get();

                            if (typeof resetForm === 'function') resetForm();
                        })
                        .catch(error => {
                            this.isSubmitting = false;
                            const msg = error.response?.data?.message || 'حدث خطأ أثناء إتمام عملية السحب.';
                            this.$emitter.emit('add-flash', { type: 'error', message: msg });
                        });
                    },

                    openRejectModal(action) {
                        this.selectedRejectAction = action;
                        this.rejectReason = '';

                        let modal = this.$refs.rejectModal;
                        if (Array.isArray(modal)) modal = modal[0];
                        if (modal && typeof modal.open === 'function') modal.open();
                        else if (modal && typeof modal.toggle === 'function') modal.toggle();
                    },

                    closeRejectModal() {
                        let modal = this.$refs.rejectModal;
                        if (Array.isArray(modal)) modal = modal[0];
                        if (modal && typeof modal.close === 'function') modal.close();
                        else if (modal && typeof modal.toggle === 'function') modal.toggle();
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
                            if (Array.isArray(datagrid)) datagrid = datagrid[0];
                            if (datagrid && typeof datagrid.get === 'function') datagrid.get();

                            if (typeof resetForm === 'function') resetForm();
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
