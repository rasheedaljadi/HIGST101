# تقرير التحقق المستقل من بوابة AliExpress الموحدة وقواعد النزاهة في Procurement V2

**تاريخ التحقق:** 2026-08-22 23:02:00  
**النطاق:** فحص الكود، الاختبارات المثبتة، وعزل المخزون وإثبات دقة البوابة المشتركة.  
**التصنيف المعماري الدقيق:** `V2_REUSES_V1_PROVEN_CLIENT_AND_ADDRESS_SOURCE`  
**الـ Git Commit SHA النهائي:** `1bf55226bab236c4526a071d35373e08941137a8`

---

## 1. مصفوفة التحقق المستقل من الكود (Acceptance Conditions Verification Matrix)

تم فحص أسطر الكود فعليًا في مسار البوابة والخدمات للتحقق من شروط القبول:

| شرط القبول | حالة التحقق | الدليل البرمجي الدقيق (الملف والأسطر) | التفاصيل والآلية |
| :--- | :---: | :--- | :--- |
| **1. مصدر العنوان موحد** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:35-83](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L35-L83) | V2 تقرأ حصريًا من `DB::table('inventory_sources')->where('code', 'default')->first()` وهو السجل المُدار في لوحة التحكم في صفحة إدارة المفاتيح. لا يوجد أي عنوان صلب بديل. |
| **2. عزل المصدر عن الرصيد** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:465-479](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L465-L479) | دالة `normalizeAddress` تستخرج حقول النصوص فقط (contact, phone, address, city, zip, country). لا يوجد أي استدعاء لتخصيص مخزون أو حركات رصيد على `default`. |
| **3. عميل وبروتوكول V1** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:19-22, 331](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L19-L22) و[AliExpressApiClient.php:36-175](file:///e:/HIGESTO%20NEW1/higest/higest101/app/Services/AliExpress/AliExpressApiClient.php#L36-L175) | تستخدم البوابة `App\Services\AliExpress\AliExpressApiClient` مع نقطة النهاية الرسمية `https://api-sg.aliexpress.com/sync` وتوقيع `HMAC-SHA256` عبر `Http::asForm()`. |
| **4. حل SKU حي** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:123-145, 288-306](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L123-L145) | تستدعي البوابة `aliexpress.ds.product.get` لاستخراج سمة الـ SKU الدقيقة (`sku_attr`) ومطابقة `sku_id`؛ ولا تمرر `sku_id` بدلاً منها مطلقاً. |
| **5. شحن حي** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:148-237, 537-567](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L148-L237) | تستدعي البوابة `aliexpress.ds.freight.query` وتستخرج خيارات التوصيل الحية، وتختار أفضل خدمة مع الرسوم والتتبع والمدد اللحظية دون افتراض `shipping=0`. |
| **6. فشل آمن** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:347-385](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L347-L385) | تفحص البوابة غلاف الخطأ `error_response` وحالة `is_success === false` وأي رد غير رقمي لتعيد `ExternalOrderSubmissionFailed` مصنفاً. |
| **7. منع Fallback** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:377-385](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L377-L385) و[ProcurementSubmitService.php:98-105](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php#L98-L105) | التحقق الصارم عبر `ctype_digit($extractedId)`؛ لا يُقبل أي `AE-LIVE-*` أو UUID، ويُسجل `external_order_id` بـ `null` في حال الفشل. |
| **8. عزل مسار الإنشاء** | **متحقق ومثبت** | [ProcurementSubmitService.php:264-282](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php#L264-L282) | `submitUnpaid()` لا يمكن استدعاؤه إلا عبر `submitSupplierPurchaseOrder()` المباشر وبوجود صلاحية `ProcurementAcl::PERMISSION_SUBMIT` وتفعيل القفل الصريح. |
| **9. قراءة ما بعد الإنشاء** | **متحقق ومثبت** | [AliExpressOrderSubmissionGateway.php:398-448](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php#L398-L448) | دالة `getOrder()` تستقبل المعرف الرقمي الرسمي وتستعلم عبر `aliexpress.ds.order.get` وترفض أي بيانات وهمية. |

---

## 2. الدقة المعمارية والتسمية (Architectural Classification)

- **التصنيف الدقيق:** `V2_REUSES_V1_PROVEN_CLIENT_AND_ADDRESS_SOURCE`.
- **التعليل الهندسي:** مسار V1 التاريخي يظل مقفلاً عن إنشاء أي أوامر شراء جديدة ومخصصاً فقط للقراءة التاريخية. أما V2 فقد أعادت استخدام العميل البرمجي المثبت `AliExpressApiClient` ومصدر العنوان المعتمد في إدارة المفاتيح `inventory_sources.code=default` من خلال الواجهة الموحدة `AliExpressOrderGateway`.

---

## 3. نتائج تنفيذ حزمة الاختبارات الكاملة (Test Suite Execution)

تم تنفيذ حزمة الاختبارات الشاملة المكونة من 7 اختبارات تفصيلية:

```
=== Running Isolated AliExpress Gateway Tests ===

PASS [1/7]: Unit: gateway resolves unified warehouse shipping address from inventory_sources
PASS [2/7]: Unit: client spy proves preflight calls product/freight only, never order.create, 0 DB writes
PASS [3/7]: Unit: default inventory source is strictly metadata, zero allocations/movements
PASS [4/7]: Unit: submitUnpaid fails on HTTP 200 with error_response envelope
PASS [5/7]: Unit: submitUnpaid strictly rejects non-numeric or synthetic order IDs
PASS [6/7]: Unit: out_order_id is correlation key only and never external_order_id
PASS [7/7]: Regression: V1 purchase_orders table preserved for historical read, never written by V2

Summary: 7 tests executed, 26 assertions passed with 0 failures.
```

---

## 4. فحص الأنماط وسلامة Git

- **فحص Pint:** اجتاز بنجاح (`{"tool":"pint","result":"passed"}`).
- **فحص `git diff --check`:** اجتاز نظيفاً بدون أي أخطاء تباعد أو أسطر تالفة.
- **الـ Commit SHA النهائي:** `1bf55226bab236c4526a071d35373e08941137a8` (`feat(procurement): unify aliexpress order gateway and saudi preflight`).
