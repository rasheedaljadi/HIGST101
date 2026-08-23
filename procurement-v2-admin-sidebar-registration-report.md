# تقرير تسجيل «إدارة الشراء» في القائمة الجانبية للوحة الإدارة — Procurement V2

## 1. ملخص التنفيذ

تم بنجاح تنفيذ وتثبيت القائمة الجانبية لوحدة **إدارة الشراء (Procurement V2)** ضمن القائمة الرئيسية للوحة تحكم Bagisto 2.4.x تحت قسم **الدروبشوبنج**، مع حماية وصول صارمة (RBAC/ACL) وترجمة كاملة لـ 21 لغة معتمدة في النظام، وفق القواعد المحددة.

---

## 2. جدول العناصر المسجلة في القائمة الجانبية

| # | الاسم المعروض (العربية) | English Name | Key | Route | Sort | Icon | ACL المطلوب للظهور | حالة المسار |
|---|---|---|---|---|---|---|---|---|
| **P** | **إدارة الشراء** | **Purchase Management** | `dropshipping.procurement_v2` | `admin.procurement.demands.index` | 5 | `icon-cart` | `dropshipping.procurement_v2.view` | **موجود (Active)** |
| 1 | لوحة إدارة الشراء | Purchase Overview | `dropshipping.procurement_v2.overview` | `admin.procurement.demands.index` | 1 | — | `dropshipping.procurement_v2.view` | **موجود (Active)** |
| 2 | احتياجات الشراء | Purchase Demands | `dropshipping.procurement_v2.demands` | `admin.procurement.demands.index` | 2 | — | `dropshipping.procurement_v2.view` | **موجود (Active)** |
| 3 | دفعات الشراء | Purchase Batches | `dropshipping.procurement_v2.batches` | `admin.procurement.batches.index` | 3 | — | `dropshipping.procurement_v2.view` | **موجود (Active)** |
| 4 | أوامر المورد | Supplier Orders | `dropshipping.procurement_v2.supplier_orders` | `admin.procurement.supplier_orders.index` | 4 | — | `dropshipping.procurement_v2.view` | **موجود (Active)** |
| 5 | طلبات المنصة | Platform Orders | `dropshipping.procurement_v2.platform_orders` | `admin.procurement.platform_orders.index` | 5 | — | `dropshipping.procurement_v2.view` | **موجود (Active)** |
| 6 | تأكيدات الدفع اليدوي | Manual Payment Confirmations | `dropshipping.procurement_v2.manual_payments` | `admin.procurement.manual_payments.index` | 6 | — | `dropshipping.procurement_v2.payment_confirm` | **موجود (Active)** |
| 7 | فروقات التكلفة | Cost Variances | `dropshipping.procurement_v2.cost_variances` | `admin.procurement.cost_variances.index` | 7 | — | `dropshipping.procurement_v2.cost_view` / `variance_approve` | **موجود (Active)** |
| 8 | الاستثناءات | Exceptions | `dropshipping.procurement_v2.exceptions` | `admin.procurement.exceptions.index` | 8 | — | `dropshipping.procurement_v2.exception_handle` | **موجود (Active)** |
| 9 | التقارير | Reports | `dropshipping.procurement_v2.reports` | `admin.procurement.reports.index` | 9 | — | `dropshipping.procurement_v2.reports_view` | **موجود (Active)** |

---

## 3. جدول العناصر المستبعدة (Non-Implemented / Virtual)

تم فحص جميع المسارات الفعلية لـ Procurement V2 عبر `route:list`، واستبعاد أي عنصر لا يملك مسار `GET Index` مستقل معتمد لتجنب إنشاء مسارات وهمية (`#` أو Dummy Routes):

| العنصر المقترح | الحالة | السبب الفني للاستبعاد |
|---|---|---|
| **الاستلام والنقل** | `NOT IMPLEMENTED` | عمليات الاستلام السعودي والنقل تتم حالياً كإجراءات (`POST receive` / `POST transition`) على أوامر المورد والمستودعات في `SupplierPurchaseOrder`، ولا يوجد مسار `Index` مستقل لبيانات الاستلام في حزمة Procurement V2. |
| **سجل التدقيق المستقل** | `NOT IMPLEMENTED` | قرارات التدقيق والاعتماد مسجلة كحقول وسجلات داخل نماذج الدفعات والأوامر والفروقات، ولا توجد واجهة `admin.procurement.audit_logs.index` مستقلة. |

---

## 4. نتائج اختبار الصلاحيات وعزل القوائم (Role-Based Access Control)

تم اختبار القوائم المعروضة برمجياً وعبر محرك قائمة Bagisto `Webkul\Core\Menu` لجميع الأدوار:

| الدور (Role) | الصلاحيات الممنوحة | العناصر الظاهرة في القائمة | العناصر المحجوبة بالكامل |
|---|---|---|---|
| **مشغل المشتريات (Operator)** | `view`, `batch_create`, `submit` | <ul><li>لوحة إدارة الشراء</li><li>احتياجات الشراء</li><li>دفعات الشراء</li><li>أوامر المورد</li><li>طلبات المنصة</li></ul> | <ul><li>❌ تأكيدات الدفع اليدوي</li><li>❌ فروقات التكلفة</li><li>❌ الاستثناءات</li><li>❌ التقارير</li></ul> |
| **معتمد المشتريات (Approver)** | `view`, `cost_view`, `batch_approve`, `variance_approve` | <ul><li>لوحة إدارة الشراء</li><li>احتياجات الشراء</li><li>دفعات الشراء</li><li>أوامر المورد</li><li>طلبات المنصة</li><li>فروقات التكلفة</li></ul> | <ul><li>❌ تأكيدات الدفع اليدوي</li><li>❌ الاستثناءات</li><li>❌ التقارير</li></ul> |
| **المشرف المالي (Finance)** | `view`, `cost_view`, `payment_confirm`, `reports_view` | <ul><li>لوحة إدارة الشراء</li><li>احتياجات الشراء</li><li>دفعات الشراء</li><li>أوامر المورد</li><li>طلبات المنصة</li><li>تأكيدات الدفع اليدوي</li><li>فروقات التكلفة</li><li>التقارير</li></ul> | <ul><li>❌ الاستثناءات</li></ul> |
| **أمين المستودع (Receiver)** | `view`, `exception_handle` | <ul><li>لوحة إدارة الشراء</li><li>احتياجات الشراء</li><li>دفعات الشراء</li><li>أوامر المورد</li><li>طلبات المنصة</li><li>الاستثناءات</li></ul> | <ul><li>❌ تأكيدات الدفع اليدوي</li><li>❌ فروقات التكلفة</li><li>❌ التقارير</li></ul> |
| **المشاهد (Viewer)** | `view` فقط | <ul><li>لوحة إدارة الشراء</li><li>احتياجات الشراء</li><li>دفعات الشراء</li><li>أوامر المورد</li><li>طلبات المنصة</li></ul> | <ul><li>❌ تأكيدات الدفع اليدوي</li><li>❌ فروقات التكلفة</li><li>❌ الاستثناءات</li><li>❌ التقارير</li></ul> |
| **مستخدم غير مخول (مثل Sales Only)** | `sales`, `sales.orders` | **لا شيء** (القائمة مخفية بالكامل) | <ul><li>❌ قسم الدروبشوبنج بالكامل</li><li>❌ إدارة الشراء بجميع عناصرها</li></ul> |

---

## 5. أدلة الاختبار والتحقق الآلي (Automated & Visual Verification)

1. **اختبارات Pest الآلية:**
   - تم إنشاء جناح اختبارات متكامل `ProcurementAdminNavigationSidebarTest.php`.
   - إجمالي الاختبارات المجتازة: **54 اختباراً ناجحاً بنسبة 100% (266 تأكيداً)** واختبار واحد متخطى بشرط Upgrade Path مقصود.
   - زمن التنفيذ: 62.12 ثانية.
2. **فحص التنسيق البرمجي (Pint):**
   - نجح فحص `vendor/bin/pint --dirty` دون أي مخالفات.
3. **فحص اللغات الـ 21 (Localization Consistency):**
   - تم التحقق من وجود جميع المفاتيح الـ 10 في جميع ملفات اللغات الـ 21 (`ar`, `en`, `bn`, `ca`, `de`, `es`, `fa`, `fr`, `he`, `hi_IN`, `id`, `it`, `ja`, `nl`, `pl`, `pt_BR`, `ru`, `sin`, `tr`, `uk`, `zh_CN`).

---

## 6. القرار النهائي

```
PROCUREMENT ADMIN NAVIGATION VISIBLE — READY TO RESUME BROWSER UAT
```
