<?php
    $initialMethods = app(\Webkul\Wallet\Repositories\WalletWithdrawalMethodRepository::class)
        ->orderBy('sort_order', 'asc')
        ->orderBy('id', 'asc')
        ->get();
?>

<div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-800" id="wallet-withdrawal-methods-container">
    <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-800 dark:text-white mb-1">
            طرق السحب المتاحة (تخصيص النظام)
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            أضف وعدّل وطعّل أو احذف طرق السحب المتاحة للعملاء عند تقديم طلب سحب الرصيد.
        </p>
    </div>

    <!-- Input Field to Add New Method -->
    <div class="mb-4 flex gap-2">
        <input
            type="text"
            id="wm-new-name"
            placeholder="أدخل اسم طريقة السحب (مثال: مصرف الكريمي، محفظة فلوسك...)"
            class="flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:border-blue-600 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        />
        <button
            type="button"
            onclick="addWithdrawalMethod()"
            id="wm-add-btn"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm rounded-md transition-all flex items-center gap-1.5 shadow-sm"
        >
            + إضافة طريقة سحب
        </button>
    </div>

    <!-- Alert / Flash Message Banner -->
    <div id="wm-alert" class="hidden mb-3 p-3 rounded-md text-xs font-semibold"></div>

    <!-- Methods List Table / Container (Pre-rendered server-side) -->
    <div class="border border-gray-200 dark:border-gray-800 rounded-md overflow-hidden bg-white dark:bg-gray-900">
        <div id="wm-loading" class="hidden p-4 text-center text-xs text-gray-500">
            جاري تحميل طرق السحب...
        </div>
        <div id="wm-list" class="divide-y divide-gray-100 dark:divide-gray-800">
            <?php $__empty_1 = true; $__currentLoopData = $initialMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="p-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors" id="wm-row-<?php echo e($m->id); ?>">
                    <div class="flex items-center gap-2 flex-1" id="wm-content-<?php echo e($m->id); ?>">
                        <span class="text-sm font-medium text-gray-800 dark:text-white"><?php echo e($m->name); ?></span>
                        <?php if($m->status): ?>
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">مفعل</span>
                        <?php else: ?>
                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">معطل</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-1" id="wm-actions-<?php echo e($m->id); ?>">
                        <button type="button" onclick="startEditWm(<?php echo e($m->id); ?>, '<?php echo e(addslashes($m->name)); ?>')" class="px-2.5 py-1 text-xs font-bold text-blue-600 hover:bg-blue-50 dark:text-blue-400 rounded">
                            تعديل
                        </button>
                        <button type="button" onclick="toggleWm(<?php echo e($m->id); ?>)" class="px-2.5 py-1 text-xs font-bold <?php echo e($m->status ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400' : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400'); ?> rounded">
                            <?php echo e($m->status ? 'إيقاف' : 'تفعيل'); ?>

                        </button>
                        <button type="button" onclick="openWmDeleteModal(<?php echo e($m->id); ?>, '<?php echo e(addslashes($m->name)); ?>')" class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 rounded">
                            حذف
                        </button>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="p-4 text-center text-xs text-gray-400">لا توجد طرق سحب مضافة. استخدم الحقل أعلاه لإضافة طريقة سحب جديدة.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Custom System Design Delete Confirmation Modal -->
<div id="wm-delete-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop Overlay -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" onclick="closeWmDeleteModal()"></div>

    <!-- Modal Dialog Center Container -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-900 text-right shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200 dark:border-gray-800">
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <!-- Warning Trash Icon -->
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-rose-100 dark:bg-rose-950/80 text-rose-600 dark:text-rose-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </div>

                    <!-- Modal Text Content -->
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white" id="modal-title">
                            تأكيد حذف طريقة السحب
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                هل أنت تأكد من رغبتك في حذف طريقة السحب "<span id="wm-modal-method-name" class="font-bold text-gray-900 dark:text-white"></span>"؟
                            </p>
                            <p class="mt-2 text-xs text-rose-500 font-semibold">
                                ⚠️ لن تكون هذه الطريقة متاحة للعملاء عند طلب سحب رصيد المحفظة.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Action Buttons Footer -->
            <div class="bg-gray-50 dark:bg-gray-800/60 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    id="wm-confirm-delete-btn"
                    onclick="confirmDeleteWm()"
                    style="background-color: #dc2626 !important; color: #ffffff !important; border: 1px solid #dc2626 !important;"
                    class="inline-flex justify-center items-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-md hover:opacity-90 transition-all focus:outline-none cursor-pointer"
                >
                    حذف طريقة السحب
                </button>
                <button
                    type="button"
                    onclick="closeWmDeleteModal()"
                    style="background-color: #f3f4f6 !important; color: #1f2937 !important; border: 1px solid #d1d5db !important;"
                    class="inline-flex justify-center items-center rounded-xl px-5 py-2.5 text-sm font-bold shadow-xs hover:bg-gray-200 transition-all focus:outline-none cursor-pointer"
                >
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let pendingDeleteId = null;

    (function initWm() {
        const input = document.getElementById('wm-new-name');
        if (input) {
            input.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    addWithdrawalMethod();
                }
            });
        }
    })();

    function showWmAlert(type, message) {
        const alertBox = document.getElementById('wm-alert');
        if (!alertBox) return;

        alertBox.classList.remove('hidden', 'bg-emerald-100', 'text-emerald-800', 'bg-rose-100', 'text-rose-800');
        if (type === 'success') {
            alertBox.classList.add('bg-emerald-100', 'text-emerald-800');
        } else {
            alertBox.classList.add('bg-rose-100', 'text-rose-800');
        }
        alertBox.innerText = message;
        setTimeout(() => {
            alertBox.classList.add('hidden');
        }, 4000);
    }

    function addWithdrawalMethod() {
        const input = document.getElementById('wm-new-name');
        const btn = document.getElementById('wm-add-btn');
        const name = input ? input.value.trim() : '';

        if (!name) return;

        btn.disabled = true;
        btn.innerText = 'جاري الإضافة...';

        fetch("<?php echo e(route('admin.wallet.withdrawal_methods.store')); ?>", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: name })
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.innerText = '+ إضافة طريقة سحب';
            if (data.method) {
                input.value = '';
                showWmAlert('success', data.message || 'تمت إضافة طريقة السحب بنجاح.');
                
                const m = data.method;
                const list = document.getElementById('wm-list');
                if (list) {
                    if (list.querySelector('.text-gray-400')) {
                        list.innerHTML = '';
                    }
                    const row = document.createElement('div');
                    row.className = 'p-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors';
                    row.id = `wm-row-${m.id}`;

                    const statusBadge = '<span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">مفعل</span>';

                    row.innerHTML = `
                        <div class="flex items-center gap-2 flex-1" id="wm-content-${m.id}">
                            <span class="text-sm font-medium text-gray-800 dark:text-white">${escapeHtml(m.name)}</span>
                            ${statusBadge}
                        </div>
                        <div class="flex items-center gap-1" id="wm-actions-${m.id}">
                            <button type="button" onclick="startEditWm(${m.id}, '${escapeJs(m.name)}')" class="px-2.5 py-1 text-xs font-bold text-blue-600 hover:bg-blue-50 dark:text-blue-400 rounded">
                                تعديل
                            </button>
                            <button type="button" onclick="toggleWm(${m.id})" class="px-2.5 py-1 text-xs font-bold text-amber-600 hover:bg-amber-50 dark:text-amber-400 rounded">
                                إيقاف
                            </button>
                            <button type="button" onclick="openWmDeleteModal(${m.id}, '${escapeJs(m.name)}')" class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 rounded">
                                حذف
                            </button>
                        </div>
                    `;
                    list.appendChild(row);
                }
            } else {
                showWmAlert('error', data.message || 'فشل في إضافة طريقة السحب.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = '+ إضافة طريقة سحب';
            showWmAlert('error', 'حدث خطأ أثناء الإضافة.');
        });
    }

    function startEditWm(id, currentName) {
        const content = document.getElementById(`wm-content-${id}`);
        const actions = document.getElementById(`wm-actions-${id}`);
        if (!content || !actions) return;

        content.innerHTML = `
            <input type="text" id="wm-edit-input-${id}" value="${escapeHtml(currentName)}" class="flex-1 rounded border border-blue-500 bg-white px-2 py-1 text-xs text-gray-800 dark:bg-gray-900 dark:text-white" />
        `;
        actions.innerHTML = `
            <button type="button" onclick="saveEditWm(${id})" class="px-2.5 py-1 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded">حفظ</button>
            <button type="button" onclick="cancelEditWm(${id}, '${escapeJs(currentName)}')" class="px-2.5 py-1 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 rounded">إلغاء</button>
        `;

        const editInput = document.getElementById(`wm-edit-input-${id}`);
        if (editInput) {
            editInput.focus();
            editInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEditWm(id);
                }
            });
        }
    }

    function cancelEditWm(id, name) {
        const content = document.getElementById(`wm-content-${id}`);
        const actions = document.getElementById(`wm-actions-${id}`);
        if (!content || !actions) return;

        const row = document.getElementById(`wm-row-${id}`);
        const isStatusActive = row ? !row.innerHTML.includes('معطل') : true;

        const statusBadge = isStatusActive
            ? '<span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">مفعل</span>'
            : '<span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">معطل</span>';

        content.innerHTML = `
            <span class="text-sm font-medium text-gray-800 dark:text-white">${escapeHtml(name)}</span>
            ${statusBadge}
        `;

        const toggleBtnText = isStatusActive ? 'إيقاف' : 'تفعيل';
        const toggleBtnClass = isStatusActive
            ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400'
            : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400';

        actions.innerHTML = `
            <button type="button" onclick="startEditWm(${id}, '${escapeJs(name)}')" class="px-2.5 py-1 text-xs font-bold text-blue-600 hover:bg-blue-50 dark:text-blue-400 rounded">
                تعديل
            </button>
            <button type="button" onclick="toggleWm(${id})" class="px-2.5 py-1 text-xs font-bold ${toggleBtnClass} rounded">
                ${toggleBtnText}
            </button>
            <button type="button" onclick="openWmDeleteModal(${id}, '${escapeJs(name)}')" class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 rounded">
                حذف
            </button>
        `;
    }

    function saveEditWm(id) {
        const input = document.getElementById(`wm-edit-input-${id}`);
        const name = input ? input.value.trim() : '';

        if (!name) return;

        fetch(`<?php echo e(route('admin.wallet.withdrawal_methods.index')); ?>/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ name: name })
        })
        .then(res => res.json())
        .then(data => {
            showWmAlert('success', data.message || 'تم التعديل بنجاح.');
            if (data.method) {
                const m = data.method;
                cancelEditWm(m.id, m.name);
            }
        })
        .catch(err => {
            showWmAlert('error', 'فشل التعديل.');
        });
    }

    function toggleWm(id) {
        fetch(`<?php echo e(route('admin.wallet.withdrawal_methods.index')); ?>/${id}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            showWmAlert('success', data.message || 'تم تغيير الحالة بنجاح.');
            if (data.method) {
                const m = data.method;
                const content = document.getElementById(`wm-content-${id}`);
                const actions = document.getElementById(`wm-actions-${id}`);

                if (content) {
                    const statusBadge = m.status
                        ? '<span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">مفعل</span>'
                        : '<span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300">معطل</span>';

                    content.innerHTML = `
                        <span class="text-sm font-medium text-gray-800 dark:text-white">${escapeHtml(m.name)}</span>
                        ${statusBadge}
                    `;
                }

                if (actions) {
                    const toggleBtnText = m.status ? 'إيقاف' : 'تفعيل';
                    const toggleBtnClass = m.status
                        ? 'text-amber-600 hover:bg-amber-50 dark:text-amber-400'
                        : 'text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400';

                    actions.innerHTML = `
                        <button type="button" onclick="startEditWm(${m.id}, '${escapeJs(m.name)}')" class="px-2.5 py-1 text-xs font-bold text-blue-600 hover:bg-blue-50 dark:text-blue-400 rounded">
                            تعديل
                        </button>
                        <button type="button" onclick="toggleWm(${m.id})" class="px-2.5 py-1 text-xs font-bold ${toggleBtnClass} rounded">
                            ${toggleBtnText}
                        </button>
                        <button type="button" onclick="openWmDeleteModal(${m.id}, '${escapeJs(m.name)}')" class="px-2.5 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 rounded">
                            حذف
                        </button>
                    `;
                }
            }
        })
        .catch(err => {
            showWmAlert('error', 'فشل في تغيير الحالة.');
        });
    }

    function openWmDeleteModal(id, name) {
        pendingDeleteId = id;
        const modal = document.getElementById('wm-delete-modal');
        const nameSpan = document.getElementById('wm-modal-method-name');

        if (nameSpan) nameSpan.innerText = name;
        if (modal) modal.classList.remove('hidden');
    }

    function closeWmDeleteModal() {
        pendingDeleteId = null;
        const modal = document.getElementById('wm-delete-modal');
        if (modal) modal.classList.add('hidden');
    }

    function confirmDeleteWm() {
        if (!pendingDeleteId) return;

        const deleteId = pendingDeleteId;
        const btn = document.getElementById('wm-confirm-delete-btn');
        if (btn) {
            btn.disabled = true;
            btn.innerText = 'جاري الحذف...';
        }

        fetch(`<?php echo e(route('admin.wallet.withdrawal_methods.index')); ?>/${deleteId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerText = 'حذف طريقة السحب';
            }
            closeWmDeleteModal();

            // INSTANT DOM REMOVAL
            const row = document.getElementById(`wm-row-${deleteId}`);
            if (row) {
                row.remove();
            }

            showWmAlert('success', data.message || 'تم الحذف بنجاح.');

            const list = document.getElementById('wm-list');
            if (list && list.children.length === 0) {
                list.innerHTML = '<div class="p-4 text-center text-xs text-gray-400">لا توجد طرق سحب مضافة. استخدم الحقل أعلاه لإضافة طريقة سحب جديدة.</div>';
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerText = 'حذف طريقة السحب';
            }
            closeWmDeleteModal();
            showWmAlert('error', 'فشل الحذف.');
        });
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escapeJs(str) {
        return String(str).replace(/'/g, "\\'").replace(/"/g, '\\"');
    }
</script>
<?php /**PATH E:\HIGESTO NEW1\higest\higest101\packages\Webkul\Wallet\src/resources/views/admin/configuration/withdrawal-methods.blade.php ENDPATH**/ ?>