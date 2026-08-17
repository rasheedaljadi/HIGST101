# دليل الروابط الشامل لوحدة «إدارة التسليم» (Delivery Management Route Catalog)

**رقم المراجعة:** Revision 1.0.2  
**تاريخ التحديث:** 2026-08-17  
**الغرض:** دليل تفتيش ومراجعة تقنية موحد للروابط والواجهات والصلاحيات لفريق الفحص والمراجعة.

---

## 1. بيانات الإصدار والبيئات وتصنيف الحالات (Environment & Version Metadata)

> [!IMPORTANT]
> **دليل تصنيفات الحالة المعيارية (Status Classification Tags):**
> - **`[verified-live]`**: تم التحقق منه وفتحه فعلياً على الموقع الحي للإنتاج `https://highest-ye.store` عبر استعلامات قراءة آمنة (`GET`).
> - **`[verified-local]`**: تم اختباره واجتياز كافة فحوصاته الآلية والواجهات على بيئة المحاكاة المحلية المعزولة `http://127.0.0.1:8000`.
> - **`[code-only]`**: المنطق البرمجي (Model / Service / Migration / Policy) مكتمل ومفحوص برمجياً، لكن **لا توجد شاشة إدارية مستقلة مخصصة له بعد**.
> - **`[not-implemented]`**: وظيفة غير مبنية برمجياً ولا توجد لها مسارات أو شاشات.

| البند | بيئة المحاكاة المحلية (Local Simulation) | الموقع الحي (Live Production Site) |
|---|---|---|
| **الرابط الأساسي (Base URL)** | `http://127.0.0.1:8000` | `https://highest-ye.store` |
| **Commit SHA المثبت محلياً** | `7c8571657791defdca8ccd0ee7bdab5d7a86f60a` | `7c8571657791defdca8ccd0ee7bdab5d7a86f60a` |
| **Release SHA المرجعي للإنتاج** | `7c85716` (إصدار الإسناد والتسليم) | `7c85716` *(يتم التحقق الآمن عبر الخادم)* |
| **حالة تحقق الموقع الحي** | غير منطبق | `verified-live` (لمسارات القراءة المفحوصة) |
| **بيئة التشغيل (APP_ENV)** | `local` | `production` |
| **وضع التشخيص (APP_DEBUG)** | `false` | `false` |
| **معرّف المستودع المركزي (`hayest_central`)** | `ID: 7` | `ID: 2` *(معرّف ديناميكي يختلف باختلاف البيئة)* |
| **حالة المستودع البرمجي (`git status --short`)** | `Clean` (نظيف 100%) | `Clean` (نظيف 100%) |

---

## 2. دليل روابط لوحة إدارة التسليم والعمليات (بيئة المحاكاة المحلية - Local Simulation)

> [!NOTE]
> **ملاحظة حول المعرّفات الديناميكية:** الروابط التي تحوي `{id}` تمثل معرّفات ديناميكية مستمدة من قاعدة البيانات الحالية (كالمهمة أو المندوب أو أمر الشراء)، ولا يجوز تثبيت أرقام بيئية ثابتة في الدليل.

| القسم | اسم الوظيفة | HTTP Method | الرابط الكامل (Local Simulation) | اسم Route | Controller / Action | الصلاحية المطلوبة (ACL) | دور المستخدم المسموح | التصنيف | النتيجة المتوقعة |
|---|---|:---:|---|---|---|---|---|:---:|---|
| **أ. لوحة المتابعة** | لوحة مؤشرات وعمليات إدارة التسليم | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | عرض جدول التكليفات الشامل والعدادات وفلاتر الحالة والنوع |
| **أ. لوحة المتابعة** | الطلبات الجاهزة للإسناد | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=ready_for_assignment` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية وعرض الطلبات المستلمة والجاهزة لتعيين مندوب أو نقطة |
| **أ. لوحة المتابعة** | الطلبات المسندة للمناديب/النقاط | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=assigned` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الطلبات المسندة وبانتظار التسليم المركزي (Handoff) |
| **أ. لوحة المتابعة** | الطلبات المستلمة من المستودع | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=picked_up` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الطلبات التي خُصم مخزونها وأُنشئت شحنتها لدى المندوب |
| **أ. لوحة المتابعة** | الطلبات قيد مسار التوصيل الميداني | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=out_for_delivery` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الشحنات الجارية حالياً في الميدان مع المناديب |
| **أ. لوحة المتابعة** | الشحنات الواصلة لنقاط التوزيع | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=arrived_at_point` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الطلبات المتواجدة حالياً داخل مراكز الاستلام |
| **أ. لوحة المتابعة** | الطلبات المسلمة بنجاح | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=delivered` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الشحنات المكتملة والمحصلة مع الفواتير المسددة |
| **أ. لوحة المتابعة** | الطلبات المتعثرة (فشل التوصيل) | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=delivery_failed` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الطلبات التي استنفدت 3 محاولات وتتطلب قرار المشرف |
| **أ. لوحة المتابعة** | الطلبات المجدولة لإعادة المحاولة | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=retry_scheduled` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الطلبات التي تعثرت مؤقتاً ومجدولة للمحاولة التالية |
| **أ. لوحة المتابعة** | الطلبات المرتجعة للمستودع المركزي | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?status=returned_to_hayest` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تصفية الطلبات المعتمدة للإرجاع والتي استُعيد مخزونها المركزي |
| **ب. طلبات التسليم** | تصفية طلبات التوصيل المنزلي | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?delivery_type=home_delivery` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | حصر وتصفية مهام التوصيل الموجهة لعناوين العملاء المنزلية |
| **ب. طلبات التسليم** | تصفية طلبات الاستلام من النقطة | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?delivery_type=delivery_point` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | حصر وتصفية مهام التوصيل الموجهة لنقاط ومراكز الاستلام |
| **ب. طلبات التسليم** | إسناد مهمة لمندوب أو نقطة | `POST` | `http://127.0.0.1:8000/admin/delivery/assignments/{id}/assign` | `admin.delivery.assignments.assign` | `AdminDeliveryController@assign` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | تحديث المندوب/النقطة ونقل الحالة إلى `assigned` |
| **ب. طلبات التسليم** | تسليم المخزون المركزي (Handoff) | `POST` | `http://127.0.0.1:8000/admin/delivery/assignments/{id}/handoff` | `admin.delivery.assignments.handoff` | `AdminDeliveryController@handoff` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | خصم المخزون من `hayest_central`، إنشاء Shipment، ونقل الحالة لـ `picked_up` |
| **ب. طلبات التسليم** | اعتماد إرجاع الشحنة للمركزي | `POST` | `http://127.0.0.1:8000/admin/delivery/assignments/{id}/return` | `admin.delivery.assignments.return` | `AdminDeliveryController@returnToHayest` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | استعادة المخزون إلى `hayest_central`، تسجيل حركة المخزون، ونقل الحالة لـ `returned_to_hayest` |
| **ب. بوابة المندوب** | قائمة مهام المندوب الميدانية | `GET` | `http://127.0.0.1:8000/delivery` | `delivery.index` | `DeliveryAgentController@index` | جلسة مندوب أو موظف نقطة | المندوب / موظف النقطة | `[verified-local]` | عرض واجهة الهاتف (390px) لمهام المندوب المسندة له حصراً |
| **ب. بوابة المندوب** | تفاصيل مهمة التوصيل للعميل | `GET` | `http://127.0.0.1:8000/delivery/assignments/{id}` | `delivery.show` | `DeliveryAgentController@show` | صلاحية المهمة (Policy) | المندوب المسند / موظف النقطة | `[verified-local]` | عرض بيانات العميل، الاتصال، العنوان، البنود، وزر الإجراء |
| **ب. بوابة المندوب** | بدء مسار الرحلة الميداني | `POST` | `http://127.0.0.1:8000/delivery/assignments/{id}/start` | `delivery.start` | `DeliveryAgentController@startDelivery` | صلاحية المهمة (Policy) | المندوب المسند حصراً | `[verified-local]` | نقل حالة المهمة إلى `out_for_delivery` |
| **ب. بوابة المندوب** | تسجيل تعذر/فشل التوصيل | `POST` | `http://127.0.0.1:8000/delivery/assignments/{id}/fail` | `delivery.fail` | `DeliveryAgentController@recordFailure` | صلاحية المهمة (Policy) | المندوب المسند حصراً | `[verified-local]` | توثيق المحاولة والسبب الإلزامي وزيادة العداد وجدولة الإعادة |
| **ب. بوابة المندوب** | تأكيد التسليم والتحصيل النقدي | `POST` | `http://127.0.0.1:8000/delivery/assignments/{id}/delivered` | `delivery.delivered` | `DeliveryAgentController@confirmCustomerDelivery` | صلاحية المهمة (Policy) | المندوب المسند / موظف النقطة | `[verified-local]` | إتمام التسليم، تسجيل تحصيل COD (YER)، وإنشاء فاتورة مسددة |
| **ب. بوابة النقطة** | تأكيد وصول الشحنة لنقطة التوزيع | `POST` | `http://127.0.0.1:8000/delivery/assignments/{id}/arrived-point` | `delivery.arrived_point` | `DeliveryAgentController@confirmArrivalAtPoint` | صلاحية المهمة (Policy) | موظف النقطة / المشرف | `[verified-local]` | نقل حالة المهمة إلى `arrived_at_point` مع توثيق الفاعل |
| **ج. إدارة المندوبين** | قائمة مستخدمي النظام والمناديب | `GET` | `http://127.0.0.1:8000/admin/settings/users` | `admin.settings.users.index` | `UserController@index` | `settings.users` | مدير النظام (Administrator) | `[verified-local]` | استعراض حسابات المناديب والمشرفين وحالة تفعيلهم |
| **ج. إدارة المندوبين** | إنشاء حساب مندوب جديد | `GET` | `http://127.0.0.1:8000/admin/settings/users/create` | `admin.settings.users.create` | `UserController@create` | `settings.users.create` | مدير النظام (Administrator) | `[verified-local]` | نموذج إضافة مندوب وتعيين الدور وكلمة المرور |
| **ج. إدارة المندوبين** | تعديل بيانات/حالة مندوب | `GET` | `http://127.0.0.1:8000/admin/settings/users/edit/{id}` | `admin.settings.users.edit` | `UserController@edit` | `settings.users.edit` | مدير النظام (Administrator) | `[verified-local]` | تعديل بيانات المندوب، تفعيل أو تعطيل حسابه |
| **ج. إدارة المندوبين** | تصفية مهام مندوب محدد | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?delivery_boy_id={id}` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | عرض كافة المهام المرتبطة بالمندوب المختار |
| **د. نقاط التسليم** | تصفية مهام نقطة استلام محددة | `GET` | `http://127.0.0.1:8000/admin/delivery/assignments?delivery_point_id={id}` | `admin.delivery.assignments.index` | `AdminDeliveryController@index` | `sales.delivery_assignments` | مشرف العمليات / Administrator | `[verified-local]` | عرض كافة المهام المرتبطة بنقطة الاستلام المحددة |
| **هـ. مخزون ودروبشيبينغ** | لوحة متابعة طلبات AliExpress | `GET` | `http://127.0.0.1:8000/admin/dropshipping/fulfillment` | `admin.dropshipping.fulfillment.index` | `FulfillmentController@index` | `dropshipping.fulfillment` | مشرف الدروبشيبينغ / Admin | `[verified-local]` | متابعة تدفق طلبات الشراء الخارجية ومزامنتها |
| **هـ. مخزون ودروبشيبينغ** | تفاصيل أمر الشراء الخارجي (PO) | `GET` | `http://127.0.0.1:8000/admin/dropshipping/fulfillment/view/{id}` | `admin.dropshipping.fulfillment.view` | `FulfillmentController@view` | `dropshipping.fulfillment` | مشرف الدروبشيبينغ / Admin | `[verified-local]` | فحص تفاصيل التوريد الخارجي ومحاولات المزامنة وجلسات الشراء |
| **هـ. مخزون ودروبشيبينغ** | مصادر المخزون ومستودع هايست | `GET` | `http://127.0.0.1:8000/admin/settings/inventory-sources` | `admin.settings.inventory_sources.index` | `InventorySourceController@index` | `settings.inventory_sources` | مدير النظام (Administrator) | `[verified-local]` | فحص مصدر `hayest_central` والمستودعات الأخرى بالرابط المصحح |
| **هـ. مخزون ودروبشيبينغ** | تعديل مستودع المخزون | `GET` | `http://127.0.0.1:8000/admin/settings/inventory-sources/edit/{id}` | `admin.settings.inventory_sources.edit` | `InventorySourceController@edit` | `settings.inventory_sources.edit` | مدير النظام (Administrator) | `[verified-local]` | تعديل بيانات المستودع (مثال: `ID: 7` محلياً / `ID: 2` إنتاجياً) |
| **ز. التحصيل والمبيعات** | قائمة الفواتير ومبالغ COD المسددة | `GET` | `http://127.0.0.1:8000/admin/sales/invoices` | `admin.sales.invoices.index` | `InvoiceController@index` | `sales.invoices` | المشرف / المحاسب / Admin | `[verified-local]` | استعراض الفواتير الصادرة تلقائياً عند التسليم والتحصيل |
| **ز. التحصيل والمبيعات** | قائمة الشحنات المركزية الصادرة | `GET` | `http://127.0.0.1:8000/admin/sales/shipments` | `admin.sales.shipments.index` | `ShipmentController@index` | `sales.shipments` | المشرف / أمين المخزن / Admin | `[verified-local]` | استعراض بوالص الشحن المنشأة من المستودع المركزي |
| **ز. التحصيل والمبيعات** | قائمة الطلبات الرئيسية | `GET` | `http://127.0.0.1:8000/admin/sales/orders` | `admin.sales.orders.index` | `OrderController@index` | `sales.orders` | المشرف / Admin | `[verified-local]` | استعراض كافة طلبات العملاء وتفاصيل الدفع والشحن |

---

## 3. دليل روابط الموقع الحي (Live Site - Production Catalog)

> [!CAUTION]
> **سياسة الفحص على الإنتاج:** تم فحص مسارات القراءة (`GET`) فقط عبر جلسات استعلامية آمنة. يمنع منعاً باتاً تنفيذ أي طلبات تعديل أو حذف أو إرسال نماذج (`POST`/`PUT`/`DELETE`) على الإنتاج ضمن أعمال الفحص والمراجعة.

| اسم الوظيفة | الرابط على الموقع الحي (Live URL) | الحالة (Status) | HTTP Status (ضيف غير مسجل) | HTTP Status (مستخدم مصرح) |
|---|---|:---:|:---:|:---:|
| **لوحة إدارة وعمليات التوصيل** | `https://highest-ye.store/admin/delivery/assignments` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |
| **بوابة المندوب الميدانية** | `https://highest-ye.store/delivery` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |
| **لوحة متابعة الـ Fulfillment** | `https://highest-ye.store/admin/dropshipping/fulfillment` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |
| **إدارة مصادر المخزون** | `https://highest-ye.store/admin/settings/inventory-sources` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |
| **إدارة المستخدمين والمناديب** | `https://highest-ye.store/admin/settings/users` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |
| **قائمة فواتير المبيعات** | `https://highest-ye.store/admin/sales/invoices` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |
| **قائمة الشحنات** | `https://highest-ye.store/admin/sales/shipments` | `[verified-live]` | `302 Found` (إلى login) | `200 OK` |

---

## 4. مسار وأوامر «بحاجة لمراجعة» (Needs Manual Review Purchase Orders)

### 📌 توحيد الحالة المعيارية:
القيمة المعيارية المعتمدة لحالة أوامر الشراء التي تتطلب تدخلاً يدوياً هي:
`PurchaseOrder::STATE_NEEDS_MANUAL_REVIEW = 'needs_manual_review'`

### 🔍 استعلام العداد المعياري:
```sql
SELECT COUNT(*) FROM purchase_orders WHERE state = 'needs_manual_review';
```

### 🔗 روابط الفلترة والاستعراض:
- **عبر بارامتر الـ State المباشر:**
  `http://127.0.0.1:8000/admin/dropshipping/fulfillment?po_state=needs_manual_review`
- **عبر DataGrid Column Filter:**
  `http://127.0.0.1:8000/admin/dropshipping/fulfillment?state[eq]=needs_manual_review`
- **رابط شاشة التفاصيل الكلية لأمر الشراء:**
  `http://127.0.0.1:8000/admin/dropshipping/fulfillment/view/{id}`

### 🧪 التحقق من استقرار الجلسة:
- تم اختبار فتح الرابط مباشرة عبر المتصفح، وعبر الضغط على البطاقة في اللوحة، وبعد إعادة تحميل الصفحة (`F5`)، وثبت عدم حدوث أي تحويل غير متوقع لصفحة الدخول للمستخدم المصرح له.

---

## 5. مخرجات `php artisan route:list` لأوامر الشراء وإدارة التسليم

```text
+-----------------------------------------------------------------------------------------------------------------------------------+
| HTTP Method | URI                                             | Route Name                                | Controller / Action   |
+-----------------------------------------------------------------------------------------------------------------------------------+
| GET|HEAD    | admin/delivery/assignments                      | admin.delivery.assignments.index          | AdminDeliveryController@index
| POST        | admin/delivery/assignments/{id}/assign          | admin.delivery.assignments.assign         | AdminDeliveryController@assign
| POST        | admin/delivery/assignments/{id}/handoff         | admin.delivery.assignments.handoff        | AdminDeliveryController@handoff
| POST        | admin/delivery/assignments/{id}/return          | admin.delivery.assignments.return         | AdminDeliveryController@returnToHayest
| GET|HEAD    | delivery                                        | delivery.index                            | DeliveryAgentController@index
| GET|HEAD    | delivery/assignments/{id}                       | delivery.show                             | DeliveryAgentController@show
| POST        | delivery/assignments/{id}/arrived-point         | delivery.arrived_point                    | DeliveryAgentController@confirmArrivalAtPoint
| POST        | delivery/assignments/{id}/delivered             | delivery.delivered                        | DeliveryAgentController@confirmCustomerDelivery
| POST        | delivery/assignments/{id}/fail                  | delivery.fail                             | DeliveryAgentController@recordFailure
| POST        | delivery/assignments/{id}/start                 | delivery.start                            | DeliveryAgentController@startDelivery
| GET|HEAD    | admin/dropshipping/fulfillment                  | admin.dropshipping.fulfillment.index      | FulfillmentController@index
| GET|HEAD    | admin/dropshipping/fulfillment/view/{id}         | admin.dropshipping.fulfillment.view       | FulfillmentController@view
| POST        | admin/dropshipping/fulfillment/retry/{id}       | admin.dropshipping.fulfillment.retry      | FulfillmentController@retry
| POST        | admin/dropshipping/fulfillment/cancel/{id}      | admin.dropshipping.fulfillment.cancel     | FulfillmentController@cancel
| POST        | admin/dropshipping/fulfillment/override/{id}    | admin.dropshipping.fulfillment.override   | FulfillmentController@overrideState
| POST        | admin/dropshipping/fulfillment/edit/{id}        | admin.dropshipping.fulfillment.edit       | FulfillmentController@editPo
| POST        | admin/dropshipping/fulfillment/refresh/{id}     | admin.dropshipping.fulfillment.refresh    | FulfillmentController@refreshStatus
| POST        | admin/dropshipping/fulfillment/clear-alert/{id} | admin.dropshipping.fulfillment.clear-alert| FulfillmentController@dismissAlert
| POST        | admin/dropshipping/fulfillment/approve/{id}     | admin.dropshipping.fulfillment.approve    | FulfillmentController@approveRequest
| POST        | admin/dropshipping/fulfillment/reject/{id}      | admin.dropshipping.fulfillment.reject     | FulfillmentController@rejectRequest
+-----------------------------------------------------------------------------------------------------------------------------------+
```

---

## 6. جدول الوظائف المصنفة `[code-only]` (لا تملك شاشات إدارية مستقلة)

> [!WARNING]
> **تنبيه منهجي:** وجود Migration أو Model أو Service في الشيفرة المصدرية لا يعني وجود شاشة إدارية للمستخدم. الوظائف التالية مصنفة بصرامة `[code-only]` وتتطلب بناء شاشات وDataGrids مستقلة في مراحل قادمة:

| الوظيفة المطلوبة | التصنيف الفعلي | أقرب ملف / خدمة مرتبطة | ما يلزم لإتاحتها كشاشة إدارية مستقلة |
|---|:---:|---|---|
| **CRUD قواعد المحافظات (44 قاعدة)** | `[code-only]` | `DeliveryGovernorateRulesSeeder.php` و `PaymentEligibilityChecker.php` | بناء Controller + DataGrid لإضافة وتعديل مصفوفة قواعد المحافظات عبر الواجهة |
| **CRUD نقاط التسليم ومراكز الاستلام** | `[code-only]` | `DeliveryPoint.php` و `create_delivery_points_table.php` | بناء DataGrid وشاشات إضافة/تعديل وتحديد إحداثيات ومواعيد عمل نقاط الاستلام |
| **لوحة التسويات المالية للمناديب** | `[code-only]` | `create_delivery_settlements_table.php` و `DeliveryCashCollection.php` | بناء شاشة تقارير مالية وتوريد عهد المناديب اليومية ومطابقة العجز والفروقات |
| **سجل حركات المخزون المعزول** | `[code-only]` | `InventoryMovement.php` و `create_inventory_movements_table.php` | إضافة تبويب مخصص في شاشة المستودع المركزي لعرض جدول `inventory_movements` |
| **عارض محاولات التوصيل المعزول** | `[code-only]` | `DeliveryAttemptLog.php` و `DeliveryAssignment.php` | تخصيص DataGrid منفصل لاستعراض محاولات الفشل وأسبابها لكل مندوب على حدة |

---

## 7. مصفوفة الصلاحيات والأدوار واختبارات الوصول الفعلية (Permissions & Actual Access Tests)

| البيئة (Environment) | الرابط (URL) | الدور (Role) | الوصول المتوقع (Expected) | الوصول الفعلي (Actual) | كود الاستجابة (HTTP Status) |
|---|---|---|---|---|:---:|
| **Local / Live** | `/admin/delivery/assignments` | مدير النظام (Administrator) | مسموح بالكامل | مسموح بالكامل | `200 OK` |
| **Local / Live** | `/admin/delivery/assignments` | مشرف العمليات (Supervisor) | مسموح بالكامل | مسموح بالكامل | `200 OK` |
| **Local / Live** | `/admin/delivery/assignments` | مندوب توصيل (Courier) | محظور | محظور | `403 Forbidden` |
| **Local / Live** | `/admin/delivery/assignments` | موظف نقطة توزيع (Point Agent) | محظور | محظور | `403 Forbidden` |
| **Local / Live** | `/admin/delivery/assignments` | زائر غير مسجل (Guest) | إعادة توجيه للدخول | تم التحويل لـ login | `302 Found` |
| **Local / Live** | `/delivery` | مندوب توصيل (Courier) | مهامه المسندة فقط | مهامه المسندة فقط | `200 OK` |
| **Local / Live** | `/delivery` | موظف نقطة توزيع (Point Agent) | مهام نقطته فقط | مهام نقطته فقط | `200 OK` |
| **Local / Live** | `/delivery/assignments/{id}` | مندوب آخر غير مسند له | محظور | محظور | `403 Forbidden` |
| **Local / Live** | `/delivery` | زائر غير مسجل (Guest) | إعادة توجيه للدخول | تم التحويل لـ login | `302 Found` |
| **Local / Live** | `/admin/settings/inventory-sources` | مدير النظام (Administrator) | مسموح | مسموح | `200 OK` |
| **Local / Live** | `/admin/settings/inventory-sources` | مندوب أو موظف نقطة | محظور | محظور | `403 Forbidden` |

---

## 8. الأمان وتأمين بيانات الاعتماد (Security & Credentials Redaction)

> [!CAUTION]
> **إشعار أمان وتدوير كلمات المرور:**
> - تم شطب وحذف كافة كلمات المرور ورموز الجلسات من كافة وثائق المستودع والتقارير.
> - يوصى فوراً بتدوير وتغيير كلمات المرور للحساب الإداري الرئيسي وكافة الحسابات التجريبية على الخادم.
> - تُسلّم بيانات حسابات التفتيش والمراجعة الميدانية عبر القنوات الإدارية الآمنة المخصصة.

### حسابات التفتيش التجريبية (بيئة المحاكاة المحلية):
1. **مشرف العمليات والإدارة:** `supervisor@hayest.test` (كلمة المرور: `[REDACTED_SECURE_AUTH]`).
2. **مندوب توصيل صنعاء:** `courier_sanaa@hayest.test` (كلمة المرور: `[REDACTED_SECURE_AUTH]`).
3. **مندوب توصيل عدن (لفحص عزل الصلاحيات):** `courier_aden@hayest.test` (كلمة المرور: `[REDACTED_SECURE_AUTH]`).
4. **موظف نقطة استلام التحرير:** `point_agent_tahrir@hayest.test` (كلمة المرور: `[REDACTED_SECURE_AUTH]`).
