# Implementation Plan: جسر تنفيذ الطلبات (Order Fulfillment Bridge)

## Overview

تُبنى الميزة كحزمة Concord جديدة `Webkul\Fulfillment` تحت `packages/Webkul/Fulfillment/` وفق `AGENTS.md` (نمط Contract + Model + Proxy + Repository، Prettus L5 Repositories، تسجيل في `bootstrap/providers.php` و`config/concord.php`). لغة التنفيذ **PHP (Laravel/Bagisto 2.4.x)** والاختبارات بـ **Pest**. مُحوّل AliExpress يعيد استخدام `App\Services\AliExpress\*` القائمة **دون تعديلها**.

كل مهمة تبني على سابقتها وتنتهي بربط المكوّنات معًا؛ لا كود معلّق أو غير مدمج. المهام المؤشّرة بـ `*` اختيارية (اختبارات) ويمكن تخطّيها لبناء MVP أسرع.

## Tasks

- [ ] 1. تهيئة هيكل حزمة Concord `Webkul\Fulfillment`
  - [x] 1.1 إنشاء بنية الحزمة ومزوّدي الخدمة
    - إنشاء بنية المجلدات تحت `packages/Webkul/Fulfillment/src/` (Config, Contracts, DataObjects, Database/Migrations, Jobs, Listeners, Models, Providers, Repositories, Services, Resources/lang)
    - إنشاء `Providers/FulfillmentServiceProvider.php` (تحميل config, migrations, translations, listeners)، `Providers/ModuleServiceProvider.php` (تسجيل موديلات Concord)، `Providers/EventServiceProvider.php`
    - إضافة `composer.json` للحزمة بنمط `"type": "path"` ثم `composer dump-autoload`
    - _Requirements: 10.1, 10.2_

  - [x] 1.2 إنشاء ملف الإعدادات `config/fulfillment.php`
    - تعريف `default_provider`, `providers`, `trigger.event`, `retry.max_attempts`, `retry.backoff`, `lock_ttl`, `poll.enabled`, `poll.interval`
    - استخدام `env()` داخل هذا الملف فقط (لا `env()` خارج `config/`)
    - _Requirements: 10.4, 10.7_

  - [~] 1.3 تسجيل الحزمة في نقاط الإقلاع
    - تسجيل `FulfillmentServiceProvider` في `bootstrap/providers.php`
    - تسجيل موديلات الحزمة في `config/concord.php`
    - _Requirements: 10.2, 10.6_

- [ ] 2. تهيئة قاعدة البيانات وكيانات الجسر (نمط Concord)
  - [~] 2.1 إنشاء الهجرات (migrations)
    - `create_purchase_orders_table` مع فهرس فريد على `idempotency_key` وعلى `internal_reference` وقيد فريد مركّب `(order_id, provider, supplier_signature)`
    - `create_purchase_order_items_table` مع قيد فريد `(purchase_order_id, order_item_id)`
    - `create_fulfillment_attempts_table`
    - _Requirements: 9.1, 9.5, 4.2, 2.1_

  - [~] 2.2 إنشاء العقود (Contracts)
    - `Contracts/PurchaseOrder`, `Contracts/PurchaseOrderItem`, `Contracts/FulfillmentAttempt` مع توقيعات العلاقات
    - _Requirements: 10.1_

  - [~] 2.3 إنشاء الموديلات والـ Proxies
    - `Models/PurchaseOrder` مع ثوابت الحالات (State_Dictionary القسم 5.3)، `$fillable`, `casts()` (payload_snapshot=array, submitted_at=datetime)، علاقات `order()` و`items()`
    - `Models/PurchaseOrderItem`, `Models/FulfillmentAttempt` والعلاقات
    - `Models/PurchaseOrderProxy`, `PurchaseOrderItemProxy`, `FulfillmentAttemptProxy` (Konekt Concord)
    - _Requirements: 6.1, 9.1, 9.4_

  - [~] 2.4 تسجيل ربط الموديلات في `ModuleServiceProvider`
    - ربط كل Contract بموديله عبر Concord
    - _Requirements: 10.1, 10.6_

  - [~] 2.5 إنشاء المستودعات (Repositories)
    - `Repositories/PurchaseOrderRepository`, `PurchaseOrderItemRepository`, `FulfillmentAttemptRepository` تمتد `Webkul\Core\Eloquent\Repository` وتُرجع `model()` = Contract
    - تطبيق قواعد التحقق عند الكتابة (قيمة `state` ضمن القاموس، صيغة `idempotency_key`، قيود عدم الفراغ) مع رفض الكتابة المخالفة وإبقاء الحالة القائمة
    - _Requirements: 9.2, 9.3, 9.6, 9.7_

  - [ ]* 2.6 كتابة property test لسلامة العلاقة 1:N
    - **Property 6: سلامة العلاقة (Referential integrity)**
    - **Validates: Requirements 9.4, 9.5**

  - [ ]* 2.7 كتابة unit tests لتحقق الموديلات
    - اختبار رفض قيم `state` خارج القاموس، وصيغة `idempotency_key` (SHA-256 hex 64 حرفًا)، وقيود عدم الفراغ وتفرّد `internal_reference`
    - _Requirements: 9.2, 9.3, 9.6_

- [ ] 3. كائنات نقل البيانات (DTOs) وعقد المزوّد والسجل
  - [~] 3.1 إنشاء الـ DTOs المستقلة عن المورّد
    - `DataObjects/SupplierOrderRequest`, `SupplierOrderLine`, `SupplierOrderResult`, `SupplierOrderStatus`, `ShippingAddress`
    - _Requirements: 1.5_

  - [~] 3.2 إنشاء عقد `FulfillmentProviderInterface`
    - توقيعات `code()`, `isConfigured()`, `createSupplierOrder()`, `getSupplierOrderStatus()`, `findByReference()`, `cancelSupplierOrder()`
    - _Requirements: 1.1, 1.5, 1.6_

  - [~] 3.3 إنشاء `FulfillmentProviderRegistry`
    - حلّ `provider code` النصّي إلى صنف المزوّد من `config/fulfillment.php`؛ رمي خطأ عند تعذّر الحلّ
    - _Requirements: 1.2, 1.3, 1.4, 1.7_

  - [ ]* 3.4 كتابة unit tests لسجل المزوّدين
    - اختبار الحلّ الناجح، والخطأ عند `provider code` غير مسجَّل دون أي كتابة على قاعدة البيانات أو طلب العميل
    - _Requirements: 1.3, 1.7_

- [ ] 4. أمان الأسرار والسجلات
  - [~] 4.1 تنفيذ أداة تنقيح الأسرار (secret redaction)
    - دالة تستبدل `access_token`/`app_secret`/`refresh_token` بعنصر نائب ثابت، وتقصّ الرسائل إلى 2000 حرف مع مؤشّر قصّ، وتكتب لقناة السجل `aliexpress`
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

  - [ ]* 4.2 كتابة property test لخلوّ السجل من الأسرار
    - **Property 8: خلوّ السجل من الأسرار (No secrets leaked)**
    - **Validates: Requirements 11.1, 11.4**

- [ ] 5. FulfillmentService — التجميع ومنع التكرار (planPurchaseOrders)
  - [~] 5.1 تنفيذ التجميع 1:N وبناء idempotency_key و`planPurchaseOrders`
    - `groupItemsBySupplier` عبر `AliExpressProductImport` لاشتقاق `supplier_signature`
    - حساب `idempotency_key = sha256(order_id.'|'.provider.'|'.supplier_signature)`
    - `firstOrCreate` على المفتاح، وسم `needs_manual_review` للبنود بلا مصدر مورّد، وإطلاق `CreatePurchaseOrderJob` للصفوف الجديدة فقط
    - _Requirements: 2.3, 2.4, 2.5, 2.6, 2.7, 4.1, 4.2, 4.3, 4.4, 4.8, 4.9_

  - [ ]* 5.2 كتابة property test لعدم تكرار أمر الشراء
    - **Property 1: عدم تكرار أمر الشراء (No duplicate PO)**
    - **Validates: Requirements 4.1, 4.2, 4.3, 4.9**

  - [ ]* 5.3 كتابة unit tests للتجميع ووسم المراجعة اليدوية
    - اختبار تقسيم البنود إلى مجموعات `(provider, supplier_signature)`، وحالة غياب `AliExpressProductImport`
    - _Requirements: 2.3, 2.4, 2.6_

- [ ] 6. FulfillmentService — التنفيذ وإعادة المحاولة (executePurchaseOrder)
  - [~] 6.1 تنفيذ `executePurchaseOrder` و`reconcileBeforeSubmit`
    - `Cache::lock("fulfillment-po-{id}", config('fulfillment.lock_ttl'))` مع إنهاء آمن عند تعذّر الحصول على القفل خلال 10 ثوانٍ دون استدعاء `createSupplierOrder`
    - تخطّي الإرسال إن كانت الحالة `submitted`/`shipped`/`delivered`، ومصالحة عبر `findByReference` عند `submitting` (مع الحفاظ على الحالة عند فشل/تجاوز مهلة البحث 30 ثانية)
    - تصنيف الفشل عابر/دائم، إعادة المحاولة بـ backoff أسّي حتى `max_attempts`، ثم `needs_manual_review` + تعليق طلب، وتسجيل `FulfillmentAttempt`
    - _Requirements: 4.5, 4.6, 4.7, 4.10, 4.11, 5.1, 5.2, 5.3, 5.4, 5.5, 5.9, 5.10_

  - [ ]* 6.2 كتابة property test لحدّ إعادة المحاولة
    - **Property 4: حدّ إعادة المحاولة (Bounded retries)**
    - **Validates: Requirements 5.2, 5.3**

  - [ ]* 6.3 كتابة property test لأمان القفل (تنفيذ متبادل الاستبعاد)
    - **Property 7: أمان القفل (Mutual exclusion)**
    - **Validates: Requirements 4.5, 4.10**

  - [ ]* 6.4 كتابة unit tests لإعادة المحاولة ومعالجة الفشل
    - اختبار تصنيف عابر/دائم، انتهاء توكن OAuth (عابر) وفشل التجديد (دائم)، إخطار الأدمن، وقصّ رسالة المحاولة إلى 1000 حرف
    - _Requirements: 5.1, 5.6, 5.7, 5.8, 5.9_

- [ ] 7. تعيين الحالات وعكسها على طلب العميل
  - [~] 7.1 تنفيذ `reflectOnCustomerOrder` وحارس المصدر الوحيد للحقيقة
    - تحديث حالة الطلب إلى `processing` عند `submitted` وإلى `completed` عند اكتمال كل الأوامر، حصريًا عبر `OrderRepository::updateOrderStatus` + `OrderComment` واحد
    - منع أي كتابة على الأعمدة المالية لـ `orders`/`invoices` أو أي SQL مباشر؛ إجهاض العملية وتسجيل خطأ عند محاولة كتابة محظورة، وتعليق فشل عند فشل `updateOrderStatus`
    - _Requirements: 6.4, 6.5, 6.6, 6.7, 6.8, 7.1, 7.2, 7.3, 7.4, 7.5_

  - [ ]* 7.2 كتابة property test للحفاظ على المصدر الوحيد للحقيقة
    - **Property 3: الحفاظ على المصدر الوحيد للحقيقة (SSOT preserved)**
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.5**

  - [ ]* 7.3 كتابة property test لإجمالية تعيين الحالة
    - **Property 5: إجمالية تعيين الحالة (State mapping totality)**
    - **Validates: Requirements 6.1, 6.2, 6.3**

- [ ] 8. مُحوّل مزوّد AliExpress (يعيد استخدام `App\Services\AliExpress`)
  - [~] 8.1 تنفيذ `AliExpressFulfillmentProvider::createSupplierOrder` والتطبيع
    - حقن `AliExpressOAuthService` و`AliExpressApiClient` القائمين دون تعديلهما، بناء params لـ `aliexpress.ds.order.create`، وتطبيع `['ok','code','message','body']` إلى `SupplierOrderResult` مع تصنيف `isRetryable`
    - رمي خطأ عند تعذّر التطبيع دون حفظ أمر جزئي
    - _Requirements: 1.5, 1.6, 1.8, 5.1, 10.3_

  - [~] 8.2 تنفيذ `getSupplierOrderStatus` وتعيين الحالات الخام
    - تعيين حالة المورّد الخام إلى قيمة واحدة من State_Dictionary، وتعيين المجهول/الفارغ إلى `needs_manual_review`، واستخراج `tracking_number`/`tracking_company`
    - _Requirements: 6.1, 6.2, 6.3_

  - [~] 8.3 تنفيذ `findByReference` و`cancelSupplierOrder`
    - البحث بالمرجع الداخلي (out order id) وإرجاع `external_order_id` أو `null`، وإلغاء أفضل جهد
    - _Requirements: 1.5, 4.7_

  - [ ]* 8.4 كتابة unit tests للمُحوّل باستجابات وهمية
    - اختبار التطبيع، التصنيف عابر/دائم من الأكواد، وتعيين الحالات؛ دون اتصال شبكي فعلي
    - _Requirements: 1.5, 1.8, 5.1, 6.2, 6.3_

- [ ] 9. المستمع وربط الإطلاق بعد الدفع
  - [~] 9.1 تنفيذ `InitiateFulfillmentListener`
    - `implements ShouldQueue`، الاستماع لـ `sales.invoice.save.after`، استدعاء `FulfillmentService::planPurchaseOrders` مرة واحدة لكل حدث، والإنهاء دون إنشاء أوامر عند غياب بنود مرتبطة بمزوّد أو غياب فاتورة
    - _Requirements: 3.1, 3.2, 3.5, 3.6_

  - [~] 9.2 تسجيل المستمع في `EventServiceProvider` الخاص بالحزمة
    - ربط `sales.invoice.save.after` بالمستمع، بما يوحّد مسار الدفع الإلكتروني وCOD
    - _Requirements: 3.1, 3.3, 3.4_

  - [ ]* 9.3 كتابة property test لعدم الإنشاء قبل الدفع
    - **Property 2: لا إنشاء قبل الدفع (No pre-payment fulfillment)**
    - **Validates: Requirements 3.1, 3.2**

  - [ ]* 9.4 كتابة integration test لمسار COD
    - اختبار توليد فاتورة COD مفعّل (يبدأ التنفيذ) ومعطّل (لا يبدأ) عبر نفس حدث الفاتورة
    - _Requirements: 3.3, 3.4_

- [ ] 10. المهام (Jobs)
  - [~] 10.1 تنفيذ `CreatePurchaseOrderJob`
    - `tries=3`, `backoff=60`, `SerializesModels`، استدعاء `FulfillmentService::executePurchaseOrder`
    - _Requirements: 4.4, 5.2, 5.3, 5.10_

  - [~] 10.2 تنفيذ `PollSupplierOrdersJob` وجدولته
    - الاستعلام عن الأوامر غير النهائية على كل دورة، تحديث `state`/`supplier_state_raw`/التتبّع، معالجة الفشل العابر (إبقاء الحالة) والدائم (`needs_manual_review` + تعليق)، والجدولة على `config('fulfillment.poll.interval')`
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6, 8.7_

  - [ ]* 10.3 كتابة unit tests لمهمة الاستطلاع
    - اختبار تحديث الحالة والتتبّع الجزئي، ومسارات الفشل العابر/الدائم
    - _Requirements: 8.2, 8.3, 8.4, 8.5, 8.6_

- [ ] 11. إخطار الأدمن والترجمات
  - [~] 11.1 تنفيذ إخطار الأدمن عند `needs_manual_review`
    - إخطار خلال 60 ثانية يحدّد طلب العميل وتصنيف الفشل
    - _Requirements: 5.6_

  - [~] 11.2 إضافة مفاتيح الرسائل إلى كل الـ 21 locale
    - مفاتيح موحّدة لتعليقات الطلب وإشعارات الأدمن في جميع ملفات `Resources/lang/{locale}`، والتحقق بـ `php artisan bagisto:translations:check`
    - _Requirements: 10.5, 10.8_

- [~] 12. Checkpoint — تأكّد من نجاح كل الاختبارات
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 13. الدمج والربط النهائي
  - [~] 13.1 ربط المكوّنات معًا واختبار تكامل بمزوّد وهمي
    - حقن مزوّد fake يُطبّق `FulfillmentProviderInterface` (بلا شبكة) لإثبات عزل المزوّد عبر المسار الكامل: حدث الفاتورة ← تخطيط ← تنفيذ ← عكس الحالة على الطلب
    - _Requirements: 1.1, 1.4, 3.1, 6.4_

- [~] 14. Checkpoint نهائي — تأكّد من نجاح كل الاختبارات
  - Ensure all tests pass, run `vendor/bin/pint --dirty`, ask the user if questions arise.

## Notes

- المهام المؤشّرة بـ `*` اختيارية (اختبارات) ويمكن تخطّيها لبناء MVP أسرع.
- كل مهمة تشير إلى بنود متطلبات محددة للتتبّع.
- الـ Checkpoints تضمن التحقق التدريجي.
- property tests تتحقق من خصائص الصحة العامة (القسم 6.6)، وunit/integration tests تتحقق من الأمثلة والحواف.
- الأسئلة المفتوحة (Q-A…Q-D) حول شكل حقول `aliexpress.ds.order.create` وأكواد الحالات/الأخطاء تُحسم من التوثيق الرسمي قبل إتمام مهام المُحوّل 8.x.
- بعد إضافة الحزمة نفّذ `composer dump-autoload`، وبعد أي تغيير PHP نفّذ `vendor/bin/pint --dirty`.

## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["1.1", "1.2"] },
    { "id": 1, "tasks": ["1.3", "2.1", "3.1", "4.1"] },
    { "id": 2, "tasks": ["2.2", "3.2"] },
    { "id": 3, "tasks": ["2.3", "3.3"] },
    { "id": 4, "tasks": ["2.4", "2.5"] },
    { "id": 5, "tasks": ["5.1", "4.2", "3.4", "2.6", "2.7"] },
    { "id": 6, "tasks": ["6.1", "5.2", "5.3"] },
    { "id": 7, "tasks": ["7.1", "8.1", "6.2", "6.3", "6.4"] },
    { "id": 8, "tasks": ["8.2", "7.2", "7.3"] },
    { "id": 9, "tasks": ["8.3", "9.1"] },
    { "id": 10, "tasks": ["9.2", "10.1", "8.4"] },
    { "id": 11, "tasks": ["10.2", "11.1", "9.3"] },
    { "id": 12, "tasks": ["11.2", "10.3", "9.4"] },
    { "id": 13, "tasks": ["13.1"] }
  ]
}
```
