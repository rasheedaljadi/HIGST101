# تقرير إصلاح ملاحظات مراجعة الكود — Procurement V2

**التاريخ**: 22 أغسطس 2026  
**نقطة البداية (Baseline SHA B)**: `c3501525c500858ee7493ea19904beb97bfd6a94`  
**الـ Commit النهائي للإصلاح (Remediation SHA)**: `4c3b867dc6374eff7b62bdb6535ed7af823504d5`  
**رسالة الـ Commit**: `fix(procurement): enforce authorization and concurrency safeguards`  
**الحالة العامة**: تم إصلاح الملاحظات الأربع بالكامل واختبارها محليًا بنجاح (45 Tests Passed, 1 Skipped, 0 Failed, 157 Assertions).

---

## 1. جدول ربط الملاحظات بالإصلاحات والاختبارات

| Finding ID & Severity | المشكلة المرصودة | تفاصيل الإصلاح البرمجي المطبق | مسارات الملفات المعدلة / المضافة | حالة الاختبارات (Tests) |
| --- | --- | --- | --- | --- |
| **FINDING 1 (HIGH)**<br>فرض صلاحيات ACL على الأفعال الحساسة | قوالب Blade كانت تخفي الأزرار فقط، بينما مسارات POST تقبل الاستدعاء المباشر دون فحص الصلاحية الدقيقة، مع تسريب البيانات المالية. | 1. إنشاء Trait مركزي `AuthorizesProcurementActions` وخدمة أمان `ProcurementAcl` بثوابت موحدة.<br>2. تطبيق التحقق بـ `abort(403)` على كافة الـ Mutating Endpoints في 8 Controllers.<br>3. تطبيق `ProcurementAcl::authorizeActor()` داخل Domain Services لرفض أي استدعاء بلا Actor مخول.<br>4. حجب التكاليف وهوامش الربح في التقارير وسجلات الدفع اليدوي لمن لا يملك `cost_view`. | • `src/Security/ProcurementAcl.php`<br>• `src/Http/Controllers/Admin/Concerns/AuthorizesProcurementActions.php`<br>• `src/Config/acl.php`<br>• `src/Http/Controllers/Admin/ProcurementBatchController.php`<br>• `src/Http/Controllers/Admin/ManualPaymentController.php`<br>• `src/Http/Controllers/Admin/CostVarianceController.php`<br>• `src/Http/Controllers/Admin/SupplierOrderController.php`<br>• `src/Http/Controllers/Admin/ExternalPlatformOrderController.php`<br>• `src/Http/Controllers/Admin/ProcurementReportController.php`<br>• `src/Services/ProcurementVarianceApprovalService.php`<br>• `src/Services/ProcurementManualPaymentService.php`<br>• `src/Services/ProcurementSubmitService.php`<br>• `src/Services/ProcurementInboundReceiptService.php` | `ProcurementAclAndAuthorizationSecurityTest`<br>• كانت: غير موجودة / 401/403 mismatch<br>• أصبحت: **✓ 4 Passed (18 Assertions)** |
| **FINDING 2 (MEDIUM)**<br>منع دمج متاجر AliExpress مجهولة | وجود Fallback ثابت يجمع منتجات من متاجر وموردين مختلفين في Supplier PO واحد عند غياب المتجر. | 1. إزالة أي Fallback ثابت لمعرف المتجر.<br>2. قبول `supplier_store_id` فقط من المصادر الموثوقة مع تتبع الـ Provenance.<br>3. إحالة أي نقص أو تعارض في البيانات مباشرة إلى حالة `supplier_exception` برمز صريح (`MISSING_SUPPLIER_STORE_METADATA` أو `CONFLICTING_SUPPLIER_METADATA`).<br>4. استبعادها من الـ Automatic Batching وحجب إنشاء Supplier PO تلقائي لها. | • `src/Services/ProcurementEligibilityService.php`<br>• `src/Services/ProcurementDemandService.php`<br>• `src/Services/ProcurementBatchService.php` | `ProcurementStoreIsolationAndExceptionTest`<br>• كانت: غير موجودة<br>• أصبحت: **✓ 3 Passed (15 Assertions)** |
| **FINDING 3 (MEDIUM)**<br>سباق التزامن في حجز مخزون اليمن | قراءة ProductInventory بلا قفل مع عدم احتساب الحجوزات النشطة تسبب بيع الكمية المحلية مرتين لطلبين متزامنين. | 1. استخدام `lockForUpdate()` على صف `ProductInventory` لمصدر `hayest_dropship_ye` داخل Transaction ذرية.<br>2. معالجة إنشاء الصف ذريًا لمنع تصادم المفاتيح الفريدة.<br>3. قفل واحتساب الحجوزات المحلية النشطة `OrderAllocation`.<br>4. حساب المتاح الفعلي: `available = max(0, physical - active_reservations)`.<br>5. إنشاء حجز محلي متين `OrderAllocation` للكمية المغطاة وإنشاء الطلب الخارجي للفرق فقط.<br>6. ضمان الـ Idempotency عبر `active_fingerprint`. | • `src/Services/ProcurementDemandService.php` | `ProcurementInventoryConcurrencySafeguardTest`<br>• كانت: غير موجودة<br>• أصبحت: **✓ 3 Passed (17 Assertions)** |
| **FINDING 4 (LOW)**<br>ربط الجدولة والـ Command بـ Feature Flag | جدولة الـ Polling كانت تسجل وتعمل حتى لو كانت ميزة V2 معطلة. | 1. تعديل `ProcurementServiceProvider::boot()` لعدم تسجيل `procurement:poll-aliexpress` إلا إذا كان `procurement.v2_enabled` و `procurement.polling.enabled` مفعلين معًا.<br>2. إضافة Guard مبكر في بداية `PollAliExpressOrdersCommand::handle()` للخروج فورًا إذا كانت الميزة معطلة دون تنفيذ أي استعلام. | • `src/Providers/ProcurementServiceProvider.php`<br>• `src/Console/Commands/PollAliExpressOrdersCommand.php` | `ProcurementPollingSchedulerFeatureFlagTest`<br>• كانت: غير موجودة<br>• أصبحت: **✓ 3 Passed (8 Assertions)** |

---

## 2. ملخص نتائج الاختبارات الآلية الشاملة

تم تنفيذ كامل حزم الاختبارات على بيئة الاختبار وقاعدة بيانات `higest_procurement_v2_integrity_test` بنجاح تام:

```
Test Suites:
 ✓ ProcurementAclAndAuthorizationSecurityTest ........ 4 passed (18 assertions)
 ✓ ProcurementStoreIsolationAndExceptionTest ......... 3 passed (15 assertions)
 ✓ ProcurementInventoryConcurrencySafeguardTest ...... 3 passed (17 assertions)
 ✓ ProcurementPollingSchedulerFeatureFlagTest ........ 3 passed (8 assertions)
 ✓ ProcurementCanonicalInventoryLifecycleTest ........ 7 passed (19 assertions)
 ✓ ProcurementFeatureFlagAndCODIntegrityTest ......... 6 passed (11 assertions)
 ✓ ProcurementRealUpgradePathVerificationTest ........ 2 passed, 1 skipped (5 assertions)
 ✓ ProcurementV2RebuildFullWorkflowTest .............. 17 passed (64 assertions)

Dependent Suites Verified:
 ✓ Fulfillment Feature Tests ......................... 10 passed (38 assertions)
 ✓ Inventory Feature Tests ........................... 13 passed (42 assertions)

Total: 45 Passed, 1 Skipped, 0 Failed (157 assertions)
Pint Code Style Check: PASSED (Zero violations)
Translations Check: PASSED (All 20 locales in sync)
```

---

## 3. الالتزام بالحدود وما لم يُنفّذ (Strict Non-Execution Checklist)

- [x] **لا نشر (No Deployment)**: لم يتم نشر أي كود إلى أي خادم بعيد أو بيئة إنتاجية.
- [x] **لا تفعيل للـ Feature Flag**: المتغير `procurement.v2_enabled` بقي معطلاً افتراضيًا (`false`).
- [x] **لا اتصال بـ AliExpress Live**: جميع الاختبارات والعمليات تمت عبر Stubs و Mocked Payloads دون أي اتصال حقيقي بـ API خارجي.
- [x] **لا Migrations جديدة**: لم يتم تعديل الـ Migrations العشر لـ V2 أو إضافة جداول جديدة؛ تم استغلال الـ Schema الحالي المتين (`OrderAllocation` و `ProductInventory`).
- [x] **عدم المساس بسجلات V1**: تم عزل جداول V1 التاريخية وسجلات الترقية تمامًا.

---

> [!NOTE]
> هذا التقرير يوثق اكتمال الإصلاحات البرمجية والتحقق المخبري المحلي بنجاح. لا يدعي التقرير الجاهزية للنشر الفوري؛ يُطلب إجراء مراجعة تحقق مستقلة للملاحظات الأربع (Independent Verification Review) قبل الانتقال إلى اختبارات القبول الميدانية (Local UAT).
