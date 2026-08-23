# تقرير اختبار قبول المستخدم المحلي المضبوط — Procurement V2
**Controlled Local User Acceptance Testing (UAT) Report**

- **تاريخ تنفيذ الاختبار**: 22 أغسطس 2026
- **الـ Commit الخاضع للاختبار (Target SHA)**: `4c3b867dc6374eff7b62bdb6535ed7af823504d5`
- **رسالة الـ Commit**: `fix(procurement): enforce authorization and concurrency safeguards`
- **البيئة المحلية (Isolated Local Environment)**: بيئة اختبار محلية مغلقة
- **قاعدة بيانات الاختبار (Test Database Alias)**: `higest_procurement_v2_integrity_test` (MySQL 8.0.30 InnoDB)
- **Feature Flag Runtime Override**: `procurement.v2_enabled = true` (تفعيل مؤقت محلي أثناء الجلسة الاختبارية فقط دون تعديل الكود المصدري أو ملفات البيئة).

---

## 1. حسابات وأدوار UAT المستخدمة (UAT Roles & Access Matrix)

تم إنشاء وضبط 5 حسابات اختبارية معزولة بأدوار وصلاحيات دنيا محددة للتحقق من الفصل الصارم للمهام ومنع أي تجاوز:

| الحساب الاختباري | الصلاحيات الدقيقة الممنوحة | الغرض التشغيلي | نتيجة التحقق من الأمان والـ ACL |
| --- | --- | --- | --- |
| `uat_procurement_operator@test.local` | `view`, `batch_create`, `submit` | مراجعة الطلبات، إنشاء الدفعات (Batches)، وإرسالها للتنفيذ | **مطابق**: ينشئ ويرسل بنجاح، ويُمنع من الاعتماد والدفع اليدوي بـ `403 Forbidden`. |
| `uat_procurement_approver@test.local` | `view`, `cost_view`, `batch_approve`, `variance_approve` | اعتماد ورفض الدفعات وفروقات التكلفة (Cost Variances) | **مطابق**: يعتمد الدفعات وفروقات التكلفة، ويُمنع من إرسال الدفعات أو الدفع اليدوي. |
| `uat_procurement_finance@test.local` | `view`, `cost_view`, `payment_confirm`, `reports_view` | استعراض التكاليف وهوامش الربح، إقرار الدفع اليدوي، والتقارير المالية | **مطابق**: يقر الدفع ويستعرض الأرقام المالية كاملة، ويُمنع من إنشاء أو اعتماد الدفعات. |
| `uat_procurement_receiver@test.local` | `view`, `exception_handle` | معالجة استلام الشحنات والاستثناءات | **مطابق**: يسجل الاستلام المرحلي ومعالجة الاستثناءات، ويُمنع من أي إجراء مالي. |
| `uat_procurement_viewer@test.local` | `view` فقط | مستخدم استعراض فقط للتحقق من الحجب والأمان | **مطابق**: يُمنع من كافة الـ Mutations بـ `403`، وتُحجب عنه الأرقام المالية في التقارير. |

---

## 2. جدول نتائج سيناريوهات القبول التشغيلي الشامل (Scenarios A – D)

تم تنفيذ كافة السيناريوهات الإحدى عشرة عبر دورة اختبارية مؤتمتة متكاملة:

| رقم السيناريو والمعرف | الوصف التشغيلي | المدخلات والمخزون الأولي | السلوك الفعلي المحقق | نتيجة الفحص |
| --- | --- | --- | --- | --- |
| **Scenario 1**<br>`UAT-POV2-01-INTERNAL` | طلب داخلي كامل | منتج محلي مخزنه في `hayest_internal_ye` فقط (الكمية المطلوبة: 2) | تم استيفاء الطلب محليًا بالكامل وتوليد **0 طلبات مشتريات خارجية (0 Demands)**. | **PASSED** |
| **Scenario 2**<br>`UAT-POV2-02-LOCAL-COVERED` | طلب مستورد مغطى محليًا | منتج مستورد برصيد مملوك في `hayest_dropship_ye` = 5 (الكمية المطلوبة: 2) | تم حجز الكمية محليًا بـ `OrderAllocation` متين، وتحويل الـ Demand إلى `locally_covered` (العجز الخارجي = 0). | **PASSED** |
| **Scenario 3**<br>`UAT-POV2-03-DEFICIT` | طلب مستورد بعجز | منتج مستورد برصيد في `hayest_dropship_ye` = 1 (الكمية المطلوبة: 3) | تغطية 1 محليًا وحجزها، وتوليد Demand خارجي للعجز المتبقي = 2 بحالة `open_for_batching`. | **PASSED** |
| **Scenario 4**<br>`UAT-POV2-04-SAME-STORE` | طلبان مستوردان من نفس المتجر | طلبان من `store_uat_alpha` (الكميات: 2 و 1) | تم تجميعهما في دفعة Batch واحدة وتوليد أمر شراء مورد واحد (`SupplierPurchaseOrder`) لنفس المتجر. | **PASSED** |
| **Scenario 5**<br>`UAT-POV2-05-DIFF-STORE` | طلب مستورد من متجر ثانٍ | طلب من `store_uat_beta` مضاف لنفس الـ Batch | تم فصل المتجر الثاني آليًا في `SupplierPurchaseOrder` و `PlatformOrder` مستقل تمامًا عن المتجر الأول. | **PASSED** |
| **Scenario 6**<br>`UAT-POV2-06-MIXED` | طلب مختلط (داخلي + مستورد) | بند داخلي (كمية 1) + بند مستورد بعجز (كمية 2) | تم فصل البند الداخلي محليًا وتوليد Demand للبند المستورد فقط بالعجز الفعلي. | **PASSED** |
| **Scenario 7**<br>`UAT-POV2-07-MISSING-STORE` | بيانات متجر مفقودة | منتج مستورد بدون معرف متجر (`store_id = null`) | تحويل الـ Demand فورًا إلى `supplier_exception` برمز `MISSING_SUPPLIER_STORE_METADATA` واستبعاده من التجميع التلقائي. | **PASSED** |
| **Scenario 8**<br>`UAT-POV2-08-CONFLICT-STORE` | تعارض بيانات المتجر | متجر `store_alpha` في الاستيراد و `store_beta` في الطلب | تحويل الـ Demand إلى `supplier_exception` برمز `CONFLICTING_SUPPLIER_METADATA` واستبعاده من الـ SPOs. | **PASSED** |
| **Scenario 9**<br>`UAT-POV2-09-COST-VARIANCE` | حدوث فرق تكلفة واستقرار الـ Snapshot | التكلفة المتوقعة $20.00، التكلفة الفعلية عند المورد $25.00 | بقاء الـ Snapshot الأولي ثابتًا ($20.00)، إنشاء طلب مراجعة انحراف $5.00، وقصره على اعتماد Approver فقط. | **PASSED** |
| **Scenario 10**<br>`UAT-POV2-10-RECEIPT-FLOW` | استلام مرحلي وتالف ومفقود | أمر شراء بـ 10 وحدات: 8 سليم، 1 تالف، 1 مفقود | دخول السليم (8) إلى `hayest_dropship_sa`، التالف إلى الحجر، نقل 8 إلى اليمن، ورفض Handoff المبكر حتى استلام اليمن. | **PASSED** |
| **Scenario 11**<br>`UAT-POV2-11-COD-FLOW` | محاسبة الدفع عند الاستلام COD | طلب COD بقيمة $40.00 | قيد الشحن كالتزام غير محقق (حساب `2210`)، وترحيله لإيراد المبيعات (حساب `4010`) فقط بعد إثبات التحصيل. | **PASSED** |

---

## 3. الفحص البصري والتنقل الإداري (Visual Inspection & Admin Navigation)

تم التحقق من جاهزية وسلامة الواجهات الإدارية لشاشات وحدة Procurement V2 (10 شاشات رئيسية) بدقة عرض 1280×720 و 1440×900:

1. **مدخل القائمة الجانبية (Sidebar Navigation)**:
   - ظهور تبويب "المشتريات المجمعة V2" تحت قسم الدروب شيبينغ باللغة العربية مع مراعاة اتجاه الـ RTL.
2. **شاشة الطلبات (Procurement Demands)**:
   - استعراض بطاقات الإحصائيات (مفتوح للتجميع، مجمع، مغطى محليًا، مكتمل)، ظهور جدول الـ DataGrid، وتمييز حالات الاستثناء باللون التحذيري.
3. **شاشة إنشاء وتجميع الدفعات (Batch Creation)**:
   - نموذج اختيار الطلبات المفتوحة بنظام Checkboxes وحساب إجمالي التكاليف بالدولار USD، مع إمكانية التجميع والتقسيم التلقائي حسب المتاجر.
4. **صفحة أمر شراء المورد (Supplier PO)**:
   - عرض تفاصيل المورد، المخصصات (Allocations)، روابط الطلبات الأصلية للعملاء، وتتبع حالة الدفع اليدوي.
5. **شاشة الطلبات الخارجية (Platform Orders)**:
   - عرض الحالة `awaiting_manual_payment` / `WAIT_BUYER_PAY` دون وجود أزرار دفع خارجي حقيقية.
6. **شاشة إقرار الدفع اليدوي (Manual Payment Confirmation)**:
   - نموذج إدخال المرجع البنكي والمبلغ المدفوع وإرفاق الإيصال، محمي بصلاحية `dropshipping.procurement_v2.payment_confirm`.
7. **شاشة فروقات التكلفة (Cost Variances)**:
   - مقارنة التكلفة المتوقعة مقابل الفعلية وسجل التدقيق (Audit Trail)، وأزرار الاعتماد/الرفض للمعتمدين فقط.
8. **شاشات الاستلام المرحلي والنقل (Inbound Receipt & Transit)**:
   - استلام مركز السعودية (`hayest_dropship_sa`)، ترحيل شحنات النقل لليمن، والاستلام النهائي في مركز اليمن (`hayest_dropship_ye`).
9. **التقارير المالية وحجب البيانات (Financial Reports & Data Masking)**:
   - عرض المؤشرات المالية لمن يملك `cost_view`، وإخفاء التكاليف وهوامش الربح وتحويلها إلى `null` للمستخدمين الآخرين.
10. **شاشة الاستثناءات (Procurement Exceptions)**:
    - فلترة واستعراض الحالات ذات البيانات الناقصة أو المتعارضة وتحديد أسباب الاستثناء بدقة.

---

## 4. التحقق المحاسبي والمطابقة المالية (Financial & Cost Verification)

- **استقرار الـ Snapshot (Snapshot Immutability)**:
  - التكلفة المتوقعة قبل الإرسال: **$20.00** (مجمدة وغير قابلة للتعديل).
  - التكلفة الفعلية عند المورد: **$25.00**.
  - فرق التكلفة المرصود: **+$5.00** (تم توثيقه كسجل انحراف مالي منفصل وتطلب اعتماد `uat_procurement_approver`).
- **محاسبة الدفع عند الاستلام (COD Recognition)**:
  - مرحلة الشحن: تسجيل المبلغ كالتزام غير محقق (Unearned Revenue / Liability Account `2210`).
  - مرحلة التسليم والتحصيل: ترحيل الإيراد المحقق إلى حساب مبيعات المتجر (Realized Revenue Account `4010`).
  - حالات الإلغاء/الاسترجاع: عكس القيد المحاسبي بأمان دون تكرار.

---

## 5. ملخص نتائج الاختبارات الآلية لحزمة المشتريات

```
✓ ProcurementAclAndAuthorizationSecurityTest ........ 4 passed (18 assertions)
✓ ProcurementCanonicalInventoryLifecycleTest ........ 7 passed (19 assertions)
✓ ProcurementFeatureFlagAndCODIntegrityTest ......... 6 passed (11 assertions)
✓ ProcurementInventoryConcurrencySafeguardTest ...... 3 passed (17 assertions)
✓ ProcurementPollingSchedulerFeatureFlagTest ........ 3 passed (8 assertions)
✓ ProcurementRealUpgradePathVerificationTest ........ 2 passed, 1 skipped (5 assertions)
✓ ProcurementStoreIsolationAndExceptionTest ......... 3 passed (15 assertions)
✓ ProcurementV2ControlledLocalUatTest ............... 6 passed (83 assertions)
✓ ProcurementV2RebuildFullWorkflowTest .............. 17 passed (64 assertions)

Total: 51 Passed, 1 Skipped, 0 Failed (240 assertions)
Execution Time: 67.02s
Pint Code Style: 0 violations
```

---

## 6. تقييم نقاط التوقف والموانع (Checkpoints Evaluation)

- [x] **لا اختراق للصلاحيات**: جميع محاولات تنفيذ العمليات الحساسة دون الصلاحية المناسبة أرجعت `403 Forbidden` وثبت عدم حدوث أي أثر جانبي (Zero Side-Effects).
- [x] **عزل المتاجر**: لم يدخل أي طلب ذي متجر مجهول أو متعارض في أوامر الشراء التلقائية.
- [x] **عزل المخازن المحظورة**: لم يُستخدم مخزن `default` أو `hayest_central` أو `aliexpress_source` كرصيد مملوك أو مصدر تسليم.
- [x] **انضباط رصيد اليمن**: لم يرتفع رصيد `hayest_dropship_ye` إلا بعد تسجيل الاستلام الفعلي في اليمن.
- [x] **حماية التكاليف**: ثبتت متانة الـ Snapshots وعدم إمكانية التلاعب بها.
- [x] **خلو البيئة من الاتصالات الخارجية**: تم الاختبار محليًا بنسبة 100% دون أي اتصال بـ AliExpress Live أو حسابات دفع حقيقية.

---

## 7. الحكم النهائي للاختبار (Final UAT Verdict)

```
LOCAL UAT PASSED — READY FOR CONTROLLED STAGING DEPLOYMENT REVIEW
```

*(تنويه تنظيمي: هذه النتيجة تعتمد نجاح اختبارات القبول الميدانية المحلية المقيدة وتسمح فقط بالانتقال إلى مراجعة إجراءات النشر التدريجي على بيئة الاختبار المتطابقة Staging، ولا تخول النشر التلقائي للإنتاج أو تفعيل الميزة على نطاق عام).*
