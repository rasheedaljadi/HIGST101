# تقرير التحقق المستقل لإصلاحات Procurement V2 قبل UAT المحلي
**Independent Verification Report — Procurement V2 Code Review Remediation**

- **تاريخ المراجعة**: 22 أغسطس 2026
- **نقطة البداية الأساسية (Baseline SHA B)**: `c3501525c500858ee7493ea19904beb97bfd6a94`
- **الـ Commit الخاضع للتحقق المستقل (Target SHA)**: `4c3b867dc6374eff7b62bdb6535ed7af823504d5`
- **رسالة الـ Commit**: `fix(procurement): enforce authorization and concurrency safeguards`
- **نوع المراجعة**: قراءة فقط، تدقيق كود مستقل، فحص ثابت، وتشغيل اختبارات على بيئة معزولة.

---

## 1. Git وسلامة النطاق (Git & Scope Integrity)

### مخرجات أوامر التحقق:
```bash
$ git rev-parse HEAD
4c3b867dc6374eff7b62bdb6535ed7af823504d5

$ git diff --name-status c3501525c500858ee7493ea19904beb97bfd6a94..4c3b867dc6374eff7b62bdb6535ed7af823504d5
M	packages/Webkul/Procurement/src/Config/acl.php
M	packages/Webkul/Procurement/src/Console/Commands/PollAliExpressOrdersCommand.php
A	packages/Webkul/Procurement/src/Http/Controllers/Admin/Concerns/AuthorizesProcurementActions.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/CostVarianceController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/ExceptionController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/ExternalPlatformOrderController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/ManualPaymentController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementBatchController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementDemandController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/ProcurementReportController.php
M	packages/Webkul/Procurement/src/Http/Controllers/Admin/SupplierOrderController.php
M	packages/Webkul/Procurement/src/Providers/ProcurementServiceProvider.php
A	packages/Webkul/Procurement/src/Security/ProcurementAcl.php
M	packages/Webkul/Procurement/src/Services/ProcurementBatchService.php
M	packages/Webkul/Procurement/src/Services/ProcurementDemandService.php
M	packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php
M	packages/Webkul/Procurement/src/Services/ProcurementInboundReceiptService.php
M	packages/Webkul/Procurement/src/Services/ProcurementManualPaymentService.php
M	packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php
M	packages/Webkul/Procurement/src/Services/ProcurementVarianceApprovalService.php
A	packages/Webkul/Procurement/tests/Feature/ProcurementAclAndAuthorizationSecurityTest.php
A	packages/Webkul/Procurement/tests/Feature/ProcurementInventoryConcurrencySafeguardTest.php
A	packages/Webkul/Procurement/tests/Feature/ProcurementPollingSchedulerFeatureFlagTest.php
A	packages/Webkul/Procurement/tests/Feature/ProcurementStoreIsolationAndExceptionTest.php
M	packages/Webkul/Procurement/tests/Feature/ProcurementV2RebuildFullWorkflowTest.php

$ git diff --check c3501525c500858ee7493ea19904beb97bfd6a94..4c3b867dc6374eff7b62bdb6535ed7af823504d5
# Exit 0 — Zero whitespace or syntax check issues
```

### نتيجة تدقيق النطاق:
- **الملفات المتأثرة**: 25 ملفًا بالتمام، تقع جميعها حصريًا داخل مسار حزمة المشتريات `packages/Webkul/Procurement/`.
- **الملفات المحمية**: لم يتم المساس بـ `vendor/`, `node_modules/`, `composer.lock`, `.env`, أو ملفات الـ migrations السابقة.
- **تاريخ Git**: الـ Commit مستقل ومبني مباشرة فوق SHA الأساسي `c3501525c500858ee7493ea19904beb97bfd6a94` دون إعادة كتابة التاريخ.

---

## 2. التحقق المستقل من ACL — HIGH (Finding 1)

### أ. مصفوفة التحقق من الصلاحيات للمسارات والأفعال الحساسة (ACL Matrix)

| Action / HTTP Route | Permission المطلوبة | Controller Guard | Domain Service Guard | تغطية اختبار HTTP حقيقي | التحقق من عدم حدوث Side-Effect عند 403 | الحكم |
| --- | --- | --- | --- | --- | --- | --- |
| `POST admin.procurement.batches.store` (Batch Create) | `dropshipping.procurement_v2.batch_create` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementBatchService::createBatch()` (يفحص Actor أو System) | نعم (مستخدم مصادق بصلاحية View فقط) | تم التحقق: `ProcurementBatch::count()` لم يتغير، وسجلات Audit لم تُنشأ | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.batches.approve` (Batch Approve) | `dropshipping.procurement_v2.batch_approve` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementBatchService::approveBatch()` (`ProcurementAcl::authorizeActor`) | نعم (استدعاء Route حقيقي) | تم التحقق: حالة الـ Batch بقيت `draft` ولم تتغير | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.batches.reject` (Batch Reject) | `dropshipping.procurement_v2.batch_approve` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementBatchService::rejectBatch()` (`ProcurementAcl::authorizeActor`) | نعم (استدعاء Route حقيقي) | تم التحقق: حالة الـ Batch بقيت `draft` ولم تتغير | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.batches.submit` (Batch Submit) | `dropshipping.procurement_v2.submit` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementSubmitService::submitBatch()` (`ProcurementAcl::authorizeActor`) | نعم (استدعاء Route حقيقي) | تم التحقق: حالة الـ Batch بقيت `approved` ولم تتغير | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.manual_payments.store` (Declare Payment) | `dropshipping.procurement_v2.payment_confirm` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementManualPaymentService::declarePayment()` (`ProcurementAcl::authorizeActor`) | نعم (استدعاء Route حقيقي) | تم التحقق: `ProcurementManualPaymentConfirmation::count()` لم يتغير | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.cost_variances.approve` (Approve Variance) | `dropshipping.procurement_v2.variance_approve` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementVarianceApprovalService::approveVariance()` (`ProcurementAcl::authorizeActor`) | نعم (اختبار صلاحيات مباشر) | تم التحقق: لا تغيير على حالة الانحراف المالي | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.cost_variances.reject` (Reject Variance) | `dropshipping.procurement_v2.variance_approve` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementVarianceApprovalService::rejectVariance()` (`ProcurementAcl::authorizeActor`) | نعم (اختبار صلاحيات مباشر) | تم التحقق: لا تغيير على حالة الانحراف المالي | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.supplier_orders.receive` (Inbound Receive) | `dropshipping.procurement_v2.exception_handle` | `authorizeProcurementAction()` -> `abort(403)` | `ProcurementInboundReceiptService` (`ProcurementAcl::authorizeActor`) | نعم (فحص المسار والخدمة) | تم التحقق: المخزون لا يتأثر بأي طلب غير مخول | **مغلق ومحكم (PASSED)** |
| `POST admin.procurement.platform_orders.sync` (Platform Sync) | `dropshipping.procurement_v2.submit` | `authorizeProcurementAction()` -> `abort(403)` | يفحص صلاحية الـ Admin | نعم (فحص المسار) | تم التحقق: لا يحدث طلب خارجي | **مغلق ومحكم (PASSED)** |
| `GET admin.procurement.reports.index` (Financial Reports) | `dropshipping.procurement_v2.cost_view` | فحص الصلاحية في المتحكم وحجب القيم | إخفاء مصفوفة الأرقام المالية عند غياب الصلاحية | نعم (اختبار فحص الـ View Response) | تم التحقق: `total_expected_cost`, `total_actual_cost`, `total_cost_variance`, `uncollected_cod_total` تصبح `null` | **مغلق ومحكم (PASSED)** |

### ب. فحص تدقيق أمان الـ Actor:
- لا يمكن لـ Actor ذي قيمة `null` أو مفقودة أو غير رقمية تجاوز الفحص؛ خدمة `ProcurementAcl::authorizeActor` تطلق `DomainException` فورًا.
- استدعاء الـ System Actor محصور حصريًا في العمليات الخلفية التلقائية عبر وسيط صريح `$allowSystem = true` في الـ Service، ولا يمكن تمريره أو انتحاله عبر HTTP Payload.

---

## 3. التحقق من عزل المتاجر و Store ID الغامض — MEDIUM (Finding 2)

1. **خلو الكود من Fallbacks الافتراضية**:
   - البحث الشامل في كامل الحزمة عن `ae_store_default`, `default_store`, `store_default` أظهر **صفر نتائج** (`0 matches`).
2. **موثوقية وتتبع مصدر الـ Metadata**:
   - خدمة `ProcurementEligibilityService` تستخرج `supplier_store_id` من `AliExpressProductImport::payload_snapshot` أو `OrderItem::additional`.
   - يتم تسجيل مصفوفة الـ Provenance كاملة (`source`, `payload_store_id`, `additional_store_id`, `resolved_store_id`).
3. **معالجة التعارض والنقص**:
   - في حال وجود تعارض بين بيانات الاستيراد والطلب، أو غياب معرف المتجر بالكامل، يتم وسم الطلب تلقائيًا بـ `metadata_status = conflicting_metadata` أو `missing_store` وتحويله مباشرة إلى حالة `STATE_SUPPLIER_EXCEPTION` برمز صريح (`CONFLICTING_SUPPLIER_METADATA` أو `MISSING_SUPPLIER_STORE_METADATA`).
4. **عزل التجميع التلقائي (Batching Isolation)**:
   - الاستعلام `ProcurementBatchService::getOpenDemandsQuery()` يفرض صراحة:
     `whereNotNull('supplier_store_id')->where('supplier_store_id', '!=', '')`.
   - يتم استبعاد أي Demand في حالة الاستثناء، ولا يتم إنشاء أي أمر شراء مورد (SPO) لمتجر مجهول.
5. **نتائج الاختبارات**:
   - اختبار `ProcurementStoreIsolationAndExceptionTest` أثبت نجاح السيناريوهات الثلاثة (عزل المتاجر المتعددة في أوامر منفصلة، توجيه المتجر المفقود للاستثناء، وتوجيه التعارض للاستثناء).

---

## 4. التحقق من التزامن وحجز المخزون — MEDIUM (Finding 3)

### أ. التحليل الساكن لحدود المعاملات والـ Invariants (Static Transaction Inspection)
- **وحدة المعاملة (Single Atomic Transaction)**: في `ProcurementDemandService::processOrderDemands()`، تتم جميع الخطوات داخل `DB::transaction(...)` موحدة.
- **آلية القفل**:
  1. قفل صف الـ Demand عبر `lockForUpdate()`.
  2. قفل صف `ProductInventory` لمصدر `hayest_dropship_ye` عبر `lockForUpdate()` مع معالجة آمنة لسباق الإنشاء الأولي (`firstOrCreate` retry).
  3. قفل وتجميع الحجوزات النشطة لـ `OrderAllocation` في نفس الـ Transaction:
     ```php
     $activeReservations = (int) OrderAllocation::join('order_items', 'order_allocations.order_item_id', '=', 'order_items.id')
         ->where('order_items.product_id', $item->product_id)
         ->where('order_allocations.source_code', $yeDestinationCode)
         ->where('order_allocations.state', 'reserved')
         ->lockForUpdate()
         ->sum('order_allocations.reserved_qty');
     ```
  4. حساب الرصيد المتاح: `availableYeStock = max(0, physical - activeReservations)`.
  5. إنشاء `OrderAllocation` متين للكمية المتوفرة محليًا فقط، وتوليد `ProcurementDemand` خارجي للكمية المتبقية (العجز).
- **الـ Idempotency**: التحقق من بصمة الطلب `active_fingerprint` يمنع التكرار عند إعادة التنفيذ.

### ب. إثبات اختبار MySQL الحقيقي والتزامن
- **قاعدة البيانات ومحرك الاختبار**:
  - المحرك: **MySQL 8.0.30** (InnoDB Engine).
  - قاعدة بيانات الاختبار: `higest_procurement_v2_integrity_test` (معرفة في `phpunit.xml`).
  - السائق: `pdo_mysql` (ليس SQLite).
- **نتائج اختبارات التزامن المخبرية (`ProcurementInventoryConcurrencySafeguardTest`)**:
  - `test_two_orders_competing_for_single_stock_unit`: مخزون محلي = 1 لطلبين متنافسين؛ الطلب الأول حجز الوحدة المحلية (Covered=1, External=0)، والطلب الثاني رأى الحجز واشترى من المورد الخارجي (Covered=0, External=1). **إجمالي المحجوز محليًا = 1 بالضبط دون أي overselling**.
  - `test_zero_stock_routes_all_orders_to_external_demand`: مخزون = 0؛ جميع الطلبات وُجهت خارجيًا دون إنشاء أي حجز وهمي محلي.
  - `test_repeated_processing_is_idempotent`: تكرار المعالجة لنفس الطلب لم ينشئ حجوزات مكررة.

---

## 5. التحقق من مجدول المهام والأوامر — LOW (Finding 4)

1. **شرط التسجيل في مزود الخدمة (`ProcurementServiceProvider`)**:
   ```php
   $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
       if (config('procurement.v2_enabled', false) && config('procurement.polling.enabled', true)) {
           $schedule->command('procurement:poll-aliexpress')->everyFifteenMinutes()->withoutOverlapping();
       }
   });
   ```
2. **الحاجز المبكر داخل الأمر (`PollAliExpressOrdersCommand`)**:
   - يتحقق الأمر في أول سطر في دالة `handle()` من `config('procurement.v2_enabled', false)` ويخرج فورًا بقيمة `SUCCESS` ورسالة تحذيرية دون إجراء أي استعلام أو استدعاء لعميل AliExpress.
3. **سلوك الـ Config Cache**:
   - في حال تفعيل `config:cache` في بيئة الإنتاج والقيمة الافتراضية للـ Flag هي `false`، فإن الـ Scheduler لن يسجل الأمر، وحتى لو استُدعي الأمر يدويًا من Cron فإن الحاجز المبكر داخل الـ Command سيمنع أي تنفيذ.
4. **نتائج الاختبارات (`ProcurementPollingSchedulerFeatureFlagTest`)**:
   - تم التحقق من الحالات الثلاث: V2 معطل (خروج مبكر)، Polling معطل (خروج مبكر)، كلاهما مفعل (تنفيذ دورة الاستعلام بنجاح).

---

## 6. تدقيق الاختبار المتخطى (Skipped Test Audit)

### الفحص:
- **الملف**: `packages/Webkul/Procurement/tests/Feature/ProcurementRealUpgradePathVerificationTest.php`
- **اسم الاختبار**: `test_pre_v2_order_has_zero_v2_demands_and_zero_v2_batches`
- **السطر**: 38
- **نص التخطي**: `$this->markTestSkipped('Pre-V2 fixture not found in current test DB.');`

### سبب التخطي والتقييم:
1. الاختبار يتحقق من وجود طلب تاريخي قديم تم إنشاؤه قبل الترقية `ORD-PRE-V2-001` في جدول الطلبات.
2. في بيئة اختبارات Feature النقية (`higest_procurement_v2_integrity_test`)، هذا الـ Fixture التاريخي غير موجود افتراضيًا، ولذلك تم وضع شرط صريح `if ($preV2Order) ... else markTestSkipped`.
3. في المقابل، فإن اختبار الترقية وعزل الـ Schema الفعلي (`test_post_upgrade_new_order_with_v2_creates_v2_spo_and_no_v1_po`) داخل نفس الـ Suite قد **نُفّذ واجتاز بنجاح تام** وأثبت أن الطلبات الجديدة في V2 لا تكتب إطلاقًا في جدول V1 التاريخي `purchase_orders`.
4. **القرار حسب جدول المعايير**: التخطي مشروط وموثق وسببه غياب Fixture تاريخي في قاعدة الاختبار المؤقتة، مع وجود إثبات كامل ومجتاز لعدم تداخل مسار V2 مع V1. **(مقبول ولا يعد Blocker)**.

---

## 7. ملخص تشغيل حزمة الاختبارات الكاملة (Test Suite Run Summary)

```
Test Suites Executed on MySQL 8.0 (higest_procurement_v2_integrity_test):
 ✓ ProcurementAclAndAuthorizationSecurityTest ........ 4 passed (18 assertions)
 ✓ ProcurementStoreIsolationAndExceptionTest ......... 3 passed (15 assertions)
 ✓ ProcurementInventoryConcurrencySafeguardTest ...... 3 passed (17 assertions)
 ✓ ProcurementPollingSchedulerFeatureFlagTest ........ 3 passed (8 assertions)
 ✓ ProcurementCanonicalInventoryLifecycleTest ........ 7 passed (19 assertions)
 ✓ ProcurementFeatureFlagAndCODIntegrityTest ......... 6 passed (11 assertions)
 ✓ ProcurementRealUpgradePathVerificationTest ........ 2 passed, 1 skipped (5 assertions)
 ✓ ProcurementV2RebuildFullWorkflowTest .............. 17 passed (64 assertions)

Summary: 45 passed, 1 skipped, 0 failed (157 assertions)
Pint Code Style: Passed (0 violations)
Translations: Passed (20 locales verified)
```

---

## 8. الحكم النهائي المستقل (Final Independent Decision)

بناءً على فحص الـ Commit `4c3b867dc6374eff7b62bdb6535ed7af823504d5` ومطابقته لـ Baseline SHA B `c3501525c500858ee7493ea19904beb97bfd6a94`، والتحقق المستقل الشامل من إغلاق الملاحظات الأربع (HIGH, MEDIUM, MEDIUM, LOW) بالبراهين البرمجية والاختبارات الآلية على MySQL:

```
REMEDIATION VERIFIED — READY FOR CONTROLLED LOCAL UAT
```

*(تنويه تنظيمي: هذا الحكم يعلن الجاهزية لاختبارات القبول الميدانية المحلية المقيدة Local UAT، ولا يعني بأي حال من الأحوال الجاهزية للنشر الفوري أو تفعيل الـ Feature Flag في الإنتاج).*
