# تقرير دمج بوابة الشراء V1 وإعادة استخدامها داخل Procurement V2 وفحص Preflight السعودي الحي

**تاريخ الإنجاز:** 2026-08-22 22:35:00  
**الحالة الهندسية:** مكتمل بنجاح  
**الحكم النهائي الملزم:** `V1_GATEWAY_REUSED_AND_SAUDI_PREFLIGHT_READY`

---

## 1. الملخص التنفيذي

استجابةً لأمر قائد التنفيذ المعتمد:
1. **استخراج وتوحيد مسار V1 الناجح:** تم استخراج مسار التنفيذ التشغيلي الناجح من وحدة V1، بما في ذلك مصدر العنوان الحقيقي الموثق من إدارة المفاتيح (`inventory_sources (code=default)`)، وعميل الاتصال الرسمي المعتمد `AliExpressApiClient` مع بروتوكول IOP/TOP وتوقيع `HMAC-SHA256`.
2. **بناء البوابة المشتركة الموحدة (`AliExpressOrderGateway`):** تم استخراج واجهة برمجية موحدة نظيفة `AliExpressOrderGateway` وفصل مسؤوليات التوقيع وبناء عناوين الشحن واستعلام خيارات الشحن وحل الـ `sku_attr` اللحظي في فئة `AliExpressOrderSubmissionGateway`.
3. **تحديث `ProcurementSubmitService` في V2:** تم ربط خدمة الإرسال في V2 بالبوابة الموحدة، مع الحفاظ الكامل على كافة ميزات V2 المعمارية (Demands, Batches, Supplier POs, Allocations, Cost Snapshots, Audit Logs, Multi-Currency Safeguards) وإلزامية رفض أي Fallback أو معرفات مصطنعة.
4. **تنفيذ Preflight حي ناجح 100% للعنوان السعودي والـ SKU والشحن:** تم تنفيذ استعلام مباشر وحي على خوادم AliExpress الرسمية لمنتج تجريبي مستورد، وأثبت الفحص الحي تطابق العنوان السعودي، واستخراج سمة الـ SKU بدقة، والحصول على خيار شحن حي (`CAINIAO_FULFILLMENT_STD`) بتكلفة `5.0 USD` وتتبع مفعل ومدد تسليم من 7 إلى 12 يوم، **دون إنشاء أي طلب خارجي أو دفع أو مساس بالبيانات**.

---

## 2. مصدر العنوان الموحد والمفاتيح المعتمدة

- **مصدر إعدادات ومفاتيح الربط:** `App\Models\AliExpressSetting` (الجدول: `aliexpress_settings`، السجل الرئيسي `id = 1`) و`App\Models\AliExpressToken`.
- **مصدر عنوان الشحن المعتمد:** جدول `inventory_sources` للسجل ذي الكود `default` (`code = 'default'`)، وهو العنوان المُدار من لوحة التحكم في صفحة إدارة المفاتيح عبر `App\Http\Controllers\AliExpress\AliExpressKeysController`.
- **بيانات العنوان المعتمدة (بعد الحجب والتمويه):**
  - المسؤول: `Haye****` (مكتب المفتاح للنقل)
  - الدولة: `SA` (المملكة العربية السعودية)
  - المدينة / المقاطعة: `RIYADH` / `Riyadh`
  - الرمز البريدي / العنوان الوطني: `RMAD****` (كود العنوان الوطني المختصر المعتمد)
  - مفتاح الهاتف: `966`
  - رقم الهاتف: `057****78`
  - الشارع: `Al aziziyah Dis [MASKED]`

---

## 3. تفاصيل البنية البرمجية المضافة

### 3.1 الواجهة والعقود (Contracts & DTOs)
- **عقد البوابة:** [AliExpressOrderGateway.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Contracts/AliExpressOrderGateway.php)
- **كائنات البيانات (DTOs):**
  - [ExternalOrderDraft.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/DTO/ExternalOrderDraft.php)
  - [AliExpressOrderPreflight.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php)
  - [AliExpressOrderSnapshot.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/DTO/AliExpressOrderSnapshot.php)
  - [VerifiedExternalOrderCreated.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/DTO/VerifiedExternalOrderCreated.php)
  - [ExternalOrderSubmissionFailed.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/DTO/ExternalOrderSubmissionFailed.php)

### 3.2 فئة التنفيذ (Gateway Implementation)
- **الفئة:** [AliExpressOrderSubmissionGateway.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php)
- **المسؤوليات:**
  1. `resolveWarehouseShippingAddress()`: جلب وتطبيع عنوان المستودع السعودي الافتراضي.
  2. `preflight()`: فحص صلاحية الـ SKU، استعلام `sku_attr` عبر `ds.product.get`، واستعلام خيارات الشحن الحية عبر `ds.freight.query`.
  3. `submitUnpaid()`: إعداد وتوقيع الطلب الرسمي ببروتوكول TOP/IOP والتحقق الصارم من استخراج المعرف الرقمي الرسمي.
  4. `getOrder()`: استعلام حالة الطلب ومعلومات التتبع عبر `ds.order.get`.

---

## 4. نتائج الفحص الحي للـ Preflight السعودي (Live Saudi Preflight Results)

تم إجراء الفحص الحي على الخادم البعيد لمنتج مستورد صالح ومفعل في المتجر:

```json
{
    "candidate_product_id": "1005010378829324",
    "candidate_sku_id": "12000052207602669",
    "warehouse_shipping_address_source": "inventory_sources (code=default)",
    "warehouse_shipping_address": {
        "contact_person": "Haye****",
        "country": "SA",
        "city": "RIYADH",
        "province": "Riyadh",
        "zip": "RMAD****",
        "phone_country": "966",
        "phone_num": "057****78",
        "address": "Al aziziyah Dis [MASKED]",
        "company_name": "المستودع الافتراضي"
    },
    "product_get": {
        "is_success": true,
        "resolved_sku_attr": "14:29;200000124:200000900",
        "error": null
    },
    "freight_preflight": {
        "is_success": true,
        "options_count": 1,
        "selected_option": {
            "service_name": "CAINIAO_FULFILLMENT_STD",
            "code": "CAINIAO_FULFILLMENT_STD",
            "cost": 5.0,
            "currency": "USD",
            "min_days": 7,
            "max_days": 12,
            "tracking": true
        },
        "error": null
    }
}
```

### مؤشرات النجاح المثبتة:
1. **صلاحية المنتج والـ SKU:** تم التحقق بنجاح من المنتج واستخراج سمة الـ SKU الدقيقة (`14:29;200000124:200000900`).
2. **أهلية الشحن إلى السعودية:** تم تأكيد توفر خيار شحن معتمد (`CAINIAO_FULFILLMENT_STD`) برسم `5.0 USD` ومدد تسليم محددة (7-12 يوم) وتتبع مفعل.
3. **سلامة العمليات:** لم يتم استدعاء `order.create`، ولم يُنشأ أي سجل طلب خارجي، ولم تحدث أي حركة مالية أو مخزنية.

---

## 5. الاختبارات وفحص الأنماط (Test Suite & Code Style)

### 5.1 حزمة الاختبارات المضافة
تم إنشاء ملف الاختبارات الشامل:
[ProcurementUnifiedAliExpressGatewayTest.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/tests/Feature/ProcurementUnifiedAliExpressGatewayTest.php)

ويغطي الاختبارات الآتية:
- `Unit: gateway resolves unified warehouse shipping address from inventory_sources`: إثبات توحيد مصدر العنوان.
- `Unit: gateway rejects non-numeric external ID and error_response with ExternalOrderSubmissionFailed`: إثبات رفض الاستجابات غير الموثقة.
- `Unit: out_order_id is strictly correlation key and never stored as external_order_id`: إثبات عزل مفتاح الارتباط.
- `Feature: preflightSupplierPurchaseOrder produces preflight check without creating platform orders or altering DB state`: إثبات عمل الـ Preflight دون تعديل قواعد البيانات.
- `Regression: V1 purchase_orders table is preserved for historical read and never written by V2 operations`: إثبات حماية جداول V1 التاريخية وعدم المساس بها.

### 5.2 فحص التنسيق (Pint)
- تم تشغيل `pint --dirty`، واجتاز بنجاح تام وفق معايير Laravel الرسمية.

---

## 6. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  V1_GATEWAY_REUSED_AND_SAUDI_PREFLIGHT_READY
======================================================================
```

> [!NOTE]
> تم إنجاز كافة متطلبات الأمر وإعادة استخدام بنية V1 التشغيلية الناجحة داخل بوابة موحدة تخدم V2. النظام في حالة جاهزية تامة ومطابق للضوابط الأمنية. تم إيقاف أي طلبات خارجية أو عمليات دفع التزاماً بتعليمات الأمر.
