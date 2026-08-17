# وثيقة التسليم والاستئناف: إعادة بناء وحدة إدارة التسليم (لوحة الإدارة)
**Delivery Management Admin Module Rebuild — Handover & Continuation Guide**

---

## 1. بيانات الحالة والبيئة الحالية (Environment & Git State)

| البيان | القيمة / الحالة |
|---|---|
| **الفرع الحالي (Active Branch)** | `feat/delivery-admin-ui-rebuild` |
| **Commit الأساسي المشتق منه** | `7c8571657791defdca8ccd0ee7bdab5d7a86f60a` |
| **بيئة العمل الحالية** | Local Isolated Staging Simulation (`http://127.0.0.1:8000`) |
| **خادم الإنتاج (Strict Read-Only)** | `https://highest-ye.store` (لم يتم لمس الإنتاج أو إجراء أي تعديل) |
| **حالة قاعدة البيانات المحلية** | تم تطبيق ميجريشن سجل التدقيق `delivery_audit_logs` بنجاح على قاعدة `higest` المحلية. |

---

## 2. ما تم إنجازه بالكامل (Completed Work)

### أ. قاعدة البيانات ونموذج البيانات (Database & Models)
1. **جدول وسجل التدقيق (Audit Logs):**
   - إنشاء ميجريشن `packages/Webkul/DeliveryManagement/src/Database/Migrations/2026_08_17_000001_create_delivery_audit_logs_table.php`.
   - إنشاء العقد والنموذج والبروكسي:
     - `Contracts/DeliveryAuditLog.php`
     - `Models/DeliveryAuditLog.php` (مزود بدالة مساعدة `DeliveryAuditLog::log(...)` للتوثيق التلقائي).
     - `Models/DeliveryAuditLogProxy.php`
   - تسجيل الموديل في `ModuleServiceProvider.php`.

### ب. شجرة القوائم والصلاحيات (Menu & ACL Architecture)
1. **القائمة الجانبية (`Config/admin-menu.php`):**
   - تم بناء قسم رئيسي مستقل باسم **«إدارة التسليم»** (`delivery_management`) بالأيقونة المعتمدة `icon-shipment`.
   - يحتوي على 8 أقسام فرعية مرتبة:
     1. لوحة المتابعة (`dashboard`)
     2. طلبات التسليم (`assignments`)
     3. موظفو التوصيل (`couriers`)
     4. نقاط التسليم (`points`)
     5. مناطق وقواعد التسليم (`rules`)
     6. الفشل والإرجاع (`failures`)
     7. التحصيل والتسويات (`settlements`)
     8. التقارير وسجل التدقيق (`audit_logs`)
2. **شجرة الصلاحيات (`Config/acl.php`):**
   - شجرة صلاحيات كاملة متوافقة مع شجرة القوائم لتمكين الأدوار المختلفة (مدير النظام، مشرف العمليات، المحاسب).

### ج. اللغات والترجمة (Localization)
- ملف الترجمة العربي الشامل: `packages/Webkul/DeliveryManagement/src/Resources/lang/ar/app.php` (يشمل كافة التسميات، الأزرار، الحالات، والرسائل التوضيحية).
- ملف الترجمة الإنجليزي الاحتياطي: `packages/Webkul/DeliveryManagement/src/Resources/lang/en/app.php`.

### د. جداول البيانات الحقيقية (DataGrids)
تم إنشاء 8 DataGrids احترافية متوافقة مع معايير Bagisto 2.4.x:
1. `DeliveryAssignmentDataGrid.php`: قائمة طلبات التسليم مع وسوم الحالات، مبالغ COD بالريال اليمني YER، تفاصيل العميل، وروابط المعاينة.
2. `DeliveryCourierDataGrid.php`: قائمة موظفي التوصيل مع عدادات المهام النشطة والمكتملة والمتعثرة ورصيد العهدة.
3. `DeliveryPointDataGrid.php`: قائمة مراكز ونقاط التسليم مع السعة القصوى وعدد الشحنات الحالية والموظفين.
4. `DeliveryGovernorateRuleDataGrid.php`: مصفوفة قواعد المحافظات (التوصيل المنزلي، نقاط الاستلام، COD، ورسوم التوصيل).
5. `DeliveryAttemptLogDataGrid.php`: سجل المحاولات مع أسباب التعثر وجدولة الإعادة.
6. `DeliveryCashCollectionDataGrid.php`: سجل تحصيلات الدفع عند الاستلام (COD) بالريال اليمني.
7. `DeliverySettlementDataGrid.php`: سجل التسويات المالية للعهد النقدية مع توضيح الفوارق والعجز.
8. `DeliveryAuditLogDataGrid.php`: سجل التدقيق لكافة الإجراءات الإدارية وتغييرات الحالة والقواعد.

### هـ. وحدات التحكم والإدارة (Admin Controllers)
تم إنشاء وحدات التحكم التالية في `packages/Webkul/DeliveryManagement/src/Http/Controllers/Admin/`:
1. `DeliveryDashboardController.php`: حساب المؤشرات اللحظية وتوزيع الحالات وأحدث المهام.
2. `DeliveryAssignmentController.php`: استعراض المهام، صفحة التفاصيل، الإسناد، تسليم المخزون (Handoff)، واعتماد الإرجاع.
3. `DeliveryCourierController.php`: إدارة موظفي التوصيل (إضافة، تعديل، تفعيل/تعطيل، وسجل مهام المندوب).
4. `DeliveryPointController.php`: إدارة نقاط التسليم (إضافة، تعديل، تفعيل/تعطيل، وسجل شحنات النقطة).
5. `DeliveryGovernorateRuleController.php`: تعديل رسوم وقواعد المحافظات وتوثيق التغيير في سجل التدقيق.
6. `DeliveryFailureController.php`: متابعة الشحنات المستنفدة للمحاولات (3/3) وسجل التعثر.
7. `DeliverySettlementController.php`: تسوية العهد النقدية للمناديب ورصد الفروقات.
8. `DeliveryAuditLogController.php`: عرض سجل التدقيق الشامل.

### و. واجهات العرض (Blade Views)
تم بناء الواجهات في `packages/Webkul/DeliveryManagement/src/Resources/views/admin/`:
- `dashboard/index.blade.php`: لوحة إحصائيات ببطاقات تفاعلية ورسم بياني ونسب إنجاز.
- `assignments/index.blade.php`: DataGrid مع تابات فلترة سريعة لكافة الحالات التسع.
- `assignments/view.blade.php`: تفاصيل الطلب، العميل، المنتجات، المحاولات، ونوافذ الإسناد والصرف والإرجاع.
- `couriers/index.blade.php`, `create.blade.php`, `edit.blade.php`: شاشات إدارة المندوبين.
- `points/index.blade.php`, `create.blade.php`, `edit.blade.php`: شاشات إدارة نقاط التسليم.
- `rules/index.blade.php`, `edit.blade.php`: شاشات إدارة مصفوفة قواعد المحافظات مع سجل التعديل.
- `failures/index.blade.php`: صندوق مهام المراجعة العاجلة وسجل المحاولات.
- `settlements/index.blade.php`: كروت مالية وتبويب بين التسويات والتحصيلات ونافذة اعتماد التسوية.
- `audit-logs/index.blade.php`: سجل التدقيق العام.

### ز. الـ Routes المسجلة (31 Route)
تم تحديث `packages/Webkul/DeliveryManagement/src/Routes/delivery-routes.php` وتسجيل 31 route تحت بادئة `admin/delivery` وبوابة المندوب الميداني `/delivery`.

---

## 3. الملفات المعدلة والمنشأة (Modified & Created Files Map)

```
packages/Webkul/DeliveryManagement/
├── src/
│   ├── Config/
│   │   ├── acl.php
│   │   └── admin-menu.php
│   ├── Contracts/
│   │   └── DeliveryAuditLog.php
│   ├── Database/
│   │   └── Migrations/
│   │       └── 2026_08_17_000001_create_delivery_audit_logs_table.php
│   ├── DataGrids/
│   │   ├── DeliveryAssignmentDataGrid.php
│   │   ├── DeliveryAttemptLogDataGrid.php
│   │   ├── DeliveryAuditLogDataGrid.php
│   │   ├── DeliveryCashCollectionDataGrid.php
│   │   ├── DeliveryCourierDataGrid.php
│   │   ├── DeliveryGovernorateRuleDataGrid.php
│   │   ├── DeliveryPointDataGrid.php
│   │   └── DeliverySettlementDataGrid.php
│   ├── Http/
│   │   └── Controllers/
│   │       └── Admin/
│   │           ├── DeliveryAssignmentController.php
│   │           ├── DeliveryAuditLogController.php
│   │           ├── DeliveryCourierController.php
│   │           ├── DeliveryDashboardController.php
│   │           ├── DeliveryFailureController.php
│   │           ├── DeliveryGovernorateRuleController.php
│   │           ├── DeliveryPointController.php
│   │           └── DeliverySettlementController.php
│   ├── Models/
│   │   ├── DeliveryAuditLog.php
│   │   └── DeliveryAuditLogProxy.php
│   ├── Providers/
│   │   └── ModuleServiceProvider.php
│   ├── Resources/
│   │   ├── lang/
│   │   │   ├── ar/app.php
│   │   │   └── en/app.php
│   │   └── views/
│   │       └── admin/
│   │           ├── assignments/ (index.blade.php, view.blade.php)
│   │           ├── audit-logs/ (index.blade.php)
│   │           ├── couriers/ (index.blade.php, create.blade.php, edit.blade.php)
│   │           ├── dashboard/ (index.blade.php)
│   │           ├── failures/ (index.blade.php)
│   │           ├── points/ (index.blade.php, create.blade.php, edit.blade.php)
│   │           ├── rules/ (index.blade.php, edit.blade.php)
│   │           └── settlements/ (index.blade.php)
│   └── Routes/
│       └── delivery-routes.php
└── tests/
    └── Feature/
        └── AdminDeliveryModuleTest.php
```

---

## 4. خطوات الاستئناف المباشرة (Immediate Next Steps for Resuming)

عند استئناف العمل، اتبع الخطوات المباشرة التالية بالترتيب:

1. **تشغيل الاختبارات والتأكد من نجاحها:**
   ```bash
   php artisan test packages/Webkul/DeliveryManagement/tests/Feature/AdminDeliveryModuleTest.php
   ```
2. **فحص التنسيق الكودي (Pint):**
   ```bash
   vendor/bin/pint --dirty
   ```
3. **التحقق من الواجهات عبر المتصفح (Browser Inspection):**
   - فتح `http://127.0.0.1:8000/admin/delivery/dashboard`
   - فتح `http://127.0.0.1:8000/admin/delivery/assignments`
   - فحص استجابة الواجهات على قياسات الجوال (`390x844`) والشاشات المكتبية (`1280x800`).
4. **تجهيز تقرير التسليم النهائي (Final Delivery Report) وعمل Commit نظيف على الفرع.**
