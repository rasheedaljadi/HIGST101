# تقرير الإصلاح الصارم لبوابة AliExpress وتثبيت حواجز P0 (Strict Gateway Correctness Report)

**تاريخ الإصلاح والتدقيق:** 2026-08-23 00:31:00 +03:00  
**الـ Commit SHA المعتمد:** `4c3931539e761842d9d3cae2537ce0f131b544f9`  
**الملفات المعدلة:**
- `packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php`
- `packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php`
- `packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php` [جديد]
**القرار النهائي:** `STRICT_GATEWAY_READY_FOR_FRESH_PREFLIGHT`

---

## 1. مصفوفة معالجة حواجز الـ P0 (Condition $\rightarrow$ Enforced Behavior $\rightarrow$ Test)

| # | الحاجز البرمجي (P0) | السلوك الصارم المنفذ (Enforced Behavior) | الاختبار المعتمد | النتيجة |
| :---: | :--- | :--- | :--- | :---: |
| **1** | عنوان الشحن وعزل إدارة المفاتيح | إزالة كافة العناوين الافتراضية والبيانات الوهمية (`Higesto Warehouse`, `0500000000`). يجب أن يوجد السجل `inventory_sources.code=default` مكتمل الحقول (`SA`, contact, phone, street, city, state, zip) وإلا يرمي `SHIPPING_ADDRESS_NOT_CONFIGURED`. | `Test 1: Missing default address source fails strictly without hardcoded fallback` | **PASS** |
| **2** | حظر تجاوز العنوان (`override`) | حظر الـ `overrideShippingAddress` تماماً في الإنتاج والـ Staging، وحصره في بيئة الاختبار `testing` فقط. | `Test 2: Address override is strictly forbidden in non-testing environments` | **PASS** |
| **3** | حل الـ SKU بدقة ومنع Fallback | `ds.product.get` يجب أن ينجح ويجد مطابقة تامة لـ `product_id + sku_id` مع `sku_attr` غير فارغ، وإلا يفشل الـ Preflight بـ `SKU_ATTR_RESOLUTION_FAILED`. | `Test 3: SKU without matching sku_attr strictly fails Preflight with SKU_ATTR_RESOLUTION_FAILED` | **PASS** |
| **4** | حظر الاستعلام العام عن الشحن | `ds.freight.query` يرسل `selectedSkuId` ولا يحذفه إطلاقاً للقيام باستعلام عام. عند خلو الخيارات يفشل بـ `NO_SKU_SPECIFIC_SHIPPING_OPTION`. | `Test 4: Empty SKU-specific freight options strictly fails with NO_SKU_SPECIFIC_SHIPPING_OPTION without generic fallback` | **PASS** |
| **5** | تطبيع المبالغ المالية ورسوم الشحن | إنشاء `AliExpressMoneyNormalizer` لمعالجة `shipping_fee_cent` و `shipping_fee` بوحدات صغرى صحيحة (`minor_cents`) وتنسيق عشري معتمد مع رفض أي غموض بـ `PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS`. | `Test 5: Money Normalizer correctly normalizes shipping_fee_cent, decimal shipping_fee, and free shipping` | **PASS** |
| **6** | اقتران `submitUnpaid` بـ Preflight فوري | `submitUnpaid()` ينفذ `preflight($draft)` أولاً، ويتوقف فوراً عند أي فشل قبل استدعاء `order.create`. | `Test 6: submitUnpaid executes Preflight first and strictly aborts on Preflight failure without calling order.create` | **PASS** |
| **7** | بناء الحمولة من Preflight الموثق فقط | استخراج `sku_attr` و `logistics_service_name` حصراً من مخرجات الـ Preflight المعتمدة، مع حظر أي بارامترات دفع آلي (`try_to_pay` غير مفعّل). | `Test 7: submitUnpaid constructs creation payload strictly from verified preflight outputs without auto-pay` | **PASS** |
| **8** | استجابة النجاح الصارمة | لا يقبل النجاح إلا بغياب `error_response` و `is_success === true` صراحة ومعرف رقمي رسمي 16-Digit؛ وأي خلاف ينتج `ExternalOrderSubmissionFailed` بلا معرف خارجي. | `Test 8: HTTP 200 with is_success=false or missing is_success returns ExternalOrderSubmissionFailed with null external ID` | **PASS** |
| **9** | القراءة الموجهة `getOrder` | التحقق الاستباقي من `ctype_digit($id)` ورفض المعرفات غير الرقمية أو الاصطناعية فوراً قبل أي اتصال خارجي. | `Test 9: getOrder strictly rejects non-numeric, UUID, and AE-LIVE-* IDs upfront without API invocation` | **PASS** |
| **10** | حظر المعرفات الاصطناعية نهائياً | التأكد من عدم توليد أي معرف `AE-LIVE-*` أو تخزين `out_order_id` كمعرف خارجي. | `Test 10: Regression: No synthetic AE-LIVE-* ID is generated anywhere in the codebase` | **PASS** |

---

## 2. ملخص نتائج الاختبارات البرمجية الفعلية

```text
======================================================================
  STRICT GATEWAY CORRECTNESS TEST SUITE EXECUTION
======================================================================
PASS: Missing default address source fails strictly without hardcoded fallback
PASS: Address override is strictly forbidden in non-testing environments
PASS: SKU without matching sku_attr strictly fails Preflight with SKU_ATTR_RESOLUTION_FAILED
PASS: Empty SKU-specific freight options strictly fails with NO_SKU_SPECIFIC_SHIPPING_OPTION without generic fallback
PASS: Money Normalizer correctly normalizes shipping_fee_cent (1250 cents -> 1250 minor, $12.50 formatted)
PASS: Money Normalizer correctly normalizes decimal shipping_fee ("12.50" -> 1250 minor, $12.50 formatted)
PASS: Money Normalizer correctly normalizes free shipping (is_free -> 0 minor, $0.00 formatted)
PASS: Money Normalizer rejects missing fee fields with ambiguous error
PASS: submitUnpaid executes Preflight first and strictly aborts on Preflight failure without calling order.create
PASS: submitUnpaid constructs creation payload strictly from verified preflight outputs without auto-pay
PASS: HTTP 200 with is_success=false or missing is_success returns ExternalOrderSubmissionFailed with null external ID
PASS: getOrder strictly rejects non-numeric, UUID, and AE-LIVE-* IDs upfront without API invocation
PASS: Regression: No synthetic AE-LIVE-* ID is generated anywhere in the codebase
======================================================================
SUMMARY: 13 tests, 13 passed, 0 failed.
======================================================================
```

---

## 3. الفروقات الكودية المعتمدة (Git Diff Summary)

```diff
+ packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php
  - Normalizes minor units (cents) vs standard decimal units.
  - Generates exact minor integers, formatted decimals, and strict error diagnostics.

* packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php
  - resolveWarehouseShippingAddress(): Throws DomainException on missing/incomplete source; forbids overrides in production.
  - preflight(): Strict exact sku_attr resolution; no generic freight fallback; strict money normalization.
  - submitUnpaid(): Mandatory preflight validation; takes sku_attr and service name strictly from preflight; strict is_success===true check; strict numeric order ID check.
  - getOrder(): Strict upfront ctype_digit check before token resolution or API calls.
```

---

## 4. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  STRICT_GATEWAY_READY_FOR_FRESH_PREFLIGHT
======================================================================
```

> [!IMPORTANT]
> تم تثبيت وإحكام كافة حواجز الـ P0 البرمجية لبوابة AliExpress بنجاح 100%، واجتازت كافة الاختبارات المعزولة بلا أي فشل. لم يتم استدعاء أي API حي، لم يُنشأ أي طلب AliExpress، ولم يُطلب أي دفع أو إلغاء. البوابة جاهزة تماماً لأمر قائد التنفيذ بتجديد الـ Preflight وعرض الموافقة الحية.
