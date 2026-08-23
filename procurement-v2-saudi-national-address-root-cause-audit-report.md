# تقرير تدقيق جذر تمرير العنوان الوطني السعودي من V1 إلى V2
(Saudi National Address Root Cause Audit Report: V1 vs V2 Contract Reconciliation)

**تاريخ وتوقيت التدقيق:** 2026-08-23 04:00:00 +03:00  
**النطاق:** تدقيق شامل وقراءة فقط 100% لجذر رفض منصة AliExpress للعنوان السعودي في محاولة `SPO #36` (`B_DROPSHIPPER_DELIVERY_ADDRESS_VALIDATE_FAIL`)  
**الـ Commit الخاضع للتدقيق:** `fffd0d1c42cefd9b10dc63e307c083dd9f83ef40`  
**البيئة:** Staging (`highest-ye.store` / `76.13.79.242`)  
**النتيجة والحكم النهائي الملزم:**  
```
SAUDI_NATIONAL_ADDRESS_ROOT_CAUSE_CONFIRMED
```

---

## 1. بيان الامتثال لقيود السلامة والممنوعات المطلقة

```text
======================================================================
  STRICT AUDIT COMPLIANCE & SAFETY CONFIRMATIONS (READ-ONLY)
======================================================================
[CONFIRMED] READ_ONLY_EXECUTION:      Zero AliExpress API calls made.
[CONFIRMED] NO_TOKEN_REFRESH:         Zero OAuth token refresh requests triggered.
[CONFIRMED] NO_DATABASE_WRITES:       Zero records inserted, updated, or deleted.
[CONFIRMED] HISTORICAL_RECORDS_SAFE:  SPO #35/EPO #26 and SPO #36/EPO #27 remain unchanged.
[CONFIRMED] SECRETS_MASKED:           All national address codes, phones, names, and tokens
                                      are strictly masked (e.g. AB****34, 05****00).
======================================================================
```

---

## 2. معيار المقارنة الرسمي لمنصة AliExpress (Official API Contract)

بناءً على التوثيق الرسمي لمنصة AliExpress Open Platform لواجهة `aliexpress.ds.order.create` (`param_place_order_request4_open_api_d_t_o.logistics_address`):

```text
+-----------------------------------------------------------------------------------------------+
| ALIEXPRESS LOGISTICS ADDRESS CONTRACT (COUNTRY = 'SA')                                        |
+----------------------+----------+-------------+-----------------------------------------------+
| Field Name           | Required | Type / Len  | Country 'SA' Specific Rules & Validation      |
+----------------------+----------+-------------+-----------------------------------------------+
| country              | Required | ISO 2 (SA)  | Must be exactly 'SA'                          |
| contact_person       | Required | String      | Recipient contact person name                 |
| phone_country        | Required | Numeric     | Must be '966' (without '+' sign)              |
| mobile_no / phone_num| Required | String      | 10-digit Saudi mobile (e.g. 05XXXXXXXX)       |
| province             | Required | String      | Province/Region name (e.g. 'Riyadh')          |
| city                 | Required | String      | City name (e.g. 'Riyadh')                     |
| address              | Required | String      | Detailed Street Address (Line 1)              |
| zip                  | Required | String (8)  | ★ CRITICAL: For SA, MUST be the 8-character   |
|                      |          |             | Short National Address Code (4 letters +      |
|                      |          |             | 4 digits, e.g. 'ABCD1234').                   |
|                      |          |             | Regex: ^[A-Z]{4}[0-9]{4}$                     |
|                      |          |             | Passing 5-digit postal code (e.g. 11564) fails|
|                      |          |             | with B_DROPSHIPPER_DELIVERY_ADDRESS_VALIDATE..|
| company_name         | Optional | String      | Warehouse or company name                     |
+----------------------+----------+-------------+-----------------------------------------------+
```

---

## 3. مخطط مقارنة تدفق العنوان: V1 مقابل V2 (Masked Architecture Diagram)

```text
=================================================================================================
  V1 vs V2 ADDRESS RESOLUTION & SUBMISSION FLOW
=================================================================================================

+-----------------------------------------------------------------------------------------------+
| Database Source of Truth: `inventory_sources` (code = 'default')                             |
| - contact_name: 'Al-Miftah Transport...'                                                      |
| - street: 'Southern Ring Road, Al-Aziziyah'                                                   |
| - city / state / country: 'Riyadh' / 'Riyadh' / 'SA'                                          |
| - postcode: '11564'  <--- (5-digit postal code stored in database)                            |
+-----------------------------------------------------------------------------------------------+
                                                |
                +-------------------------------+-------------------------------+
                |                                                               |
                v                                                               v
+-----------------------------------------------+       +-----------------------------------------------+
| V1 Fulfillment Path                           |       | V2 Procurement Gateway Path                   |
| (FulfillmentService / SubmitSupplierOrder)    |       | (AliExpressOrderSubmissionGateway)            |
+-----------------------------------------------+       +-----------------------------------------------+
| 1. Reads `inventory_sources` (code='default') |       | 1. Reads `inventory_sources` (code='default') |
| 2. Translates Arabic warehouse to English     |       | 2. contact_person: 'Al-Miftah...'             |
| 3. Maps postcode directly to $address->postcode|      | 3. phone_country: '966'                       |
| 4. Passes 'zip' => $address->postcode ('11564')|      | 4. address: 'Southern Ring Road...'           |
| 5. Missing pre-flight national address regex  |       | 5. Maps 'zip' => $warehouse->postcode ('11564')|
|    validation against ^[A-Z]{4}[0-9]{4}$      |       | 6. Missing pre-flight national address regex  |
|                                               |       |    validation against ^[A-Z]{4}[0-9]{4}$      |
+-----------------------------------------------+       +-----------------------------------------------+
                                                |
                                                v
+-----------------------------------------------------------------------------------------------+
| AliExpress Open Platform: `aliexpress.ds.order.create`                                        |
| -> Payload contains: {"country": "SA", "zip": "11564"}                                        |
| -> Platform Validation Rejection:                                                             |
|    Error Code: B_DROPSHIPPER_DELIVERY_ADDRESS_VALIDATE_FAIL                                   |
|    Message: "Please enter a 8-digit national address in right format, eg. ABCD1234."          |
+-----------------------------------------------------------------------------------------------+
```

---

## 4. جدول المطابقة الدقيق لكل حقل (Detailed Field-by-Field Contract Matrix)

| الحقل | AliExpress Contract | مصدر V1 | مطابقة V1 | موجود بـ V2 | مطابقة V2 | الفرق والتشخيص الدقيق |
| :--- | :--- | :--- | :---: | :---: | :---: | :--- |
| **الدولة (country)** | `SA` (ISO-2) | `inventory_sources.country` | `SA` $\checkmark$ | `country` | `SA` $\checkmark$ | **متطابق وسليم** 100%. |
| **اسم المستلم (contact_person)** | String (الاسم بالإنجليزية) | `inventory_sources.contact_name` | `Al-M***` $\checkmark$ | `contact_person` | `Al-M***` $\checkmark$ | **متطابق وسليم** 100%. |
| **مفتاح الدولة (phone_country)** | `966` (بدون `+`) | كود ثابت مطبع | `966` $\checkmark$ | `phone_country` | `966` $\checkmark$ | **متطابق وسليم** 100%. |
| **رقم الهاتف (mobile_no / phone_num)** | 10 أرقام سعودي | `inventory_sources.contact_number`| `05****00` $\checkmark$| `mobile_no` + `phone_num`| `05****00` $\checkmark$| **متطابق وسليم** 100%. |
| **المدينة (city)** | `Riyadh` | `inventory_sources.city` | `Riyadh` $\checkmark$ | `city` | `Riyadh` $\checkmark$ | **متطابق وسليم** 100%. |
| **المنطقة (province)** | `Riyadh` | `inventory_sources.state` | `Riyadh` $\checkmark$ | `province` | `Riyadh` $\checkmark$ | **متطابق وسليم** 100%. |
| **العنوان التفصيلي (address)** | Street Address Line | `inventory_sources.street` | `So****ah` $\checkmark$ | `address` | `So****ah` $\checkmark$ | **متطابق وسليم** 100%. |
| **العنوان الوطني (zip)** | **8 خانات (4 حروف + 4 أرقام)** `^[A-Z]{4}[0-9]{4}$` | `inventory_sources.postcode` | **`11564`** $\times$ | `zip` | **`11564`** $\times$ | **جذر الفشل المشترك**: إرسال الرمز البريدي المكون من 5 أرقام بدلاً من كود العنوان الوطني المكون من 8 خانات. |

---

## 5. النتائج المستقلة لكل مكون من مكونات العنوان

### 1. العنوان الوطني السعودي (Saudi National Address):
- **النتيجة:** `INVALID_FORMAT_REJECTED_BY_ALIEXPRESS`
- **التشخيص:** تتطلب AliExpress عند الشحن إلى السعودية تمرير **كود العنوان الوطني المختصر (Short National Address Code)** المكون من 8 خانات (4 أحرف إنجليزية كبيرة + 4 أرقام، مثل `ABCD1234`) داخل حقل `zip`.
- **الواقع:** الحقل المخزن في جدول `inventory_sources` لقيد المستودع الافتراضي (`code = 'default'`) يحمل الرمز البريدي العادي `11564` (5 أرقام)، مما تسبب في رفض المنصة للطلب.

### 2. اسم المستلم (Recipient / Contact Person):
- **النتيجة:** `VALID` (تم تمرير اسم مسؤول المستودع بالإنجليزية بسلاسة `Al-Miftah Transport...`).

### 3. رقم الهاتف ومفتاح الدولة (Mobile & Phone Country):
- **النتيجة:** `VALID` (تم تمرير `phone_country = '966'` ورقم الهاتف السعودي `0500000000` بصيغة صحيحة ونقية).

### 4. المدينة والمنطقة (City & Province):
- **النتيجة:** `VALID` (تم تمرير `city = 'Riyadh'` و `province = 'Riyadh'`).

### 5. العنوان التفصيلي (Street Address):
- **النتيجة:** `VALID` (تم تمرير العنوان بالإنجليزية `Southern Ring Road, Al-Aziziyah`).

### 6. التحقق في Preflight مقابل Create (Validation Gap):
- **النتيجة:** `PREFLIGHT_DOES_NOT_VALIDATE_CREATE_ADDRESS`
- **التشخيص:** واجهة `aliexpress.ds.freight.query` في Preflight تتحقق فقط من إمكانية التوصيل للدولة (`shipToCountry = 'SA'`) ولا تفحص صيغة العنوان الوطني أو الشارع. بينما واجهة `aliexpress.ds.order.create` تطبق تحققاً صارماً على مستوى الحقول وتطلب كود العنوان الوطني المكون من 8 خانات.

---

## 6. توصية الإصلاح الموحدة (Single Architectural Remediation Recommendation)

### الخطوات المعتمدة للإصلاح:

1. **إضافة التحقق الصارم من العنوان الوطني السعودي في بوابة V2 (`AliExpressOrderSubmissionGateway`):**
   - في دالة `resolveWarehouseShippingAddress()`، إضافة فحص إلزامي عند `country === 'SA'`:
     ```php
     if ($country === 'SA' && ! preg_match('/^[A-Za-z]{4}[0-9]{4}$/', $postcode)) {
         throw new DomainException("SHIPPING_ADDRESS_INVALID_SA_NATIONAL_ADDRESS: Saudi warehouse postcode must be an 8-character National Address code (4 letters + 4 digits, e.g. ABCD1234), found '{$postcode}'.");
     }
     ```
   - تحويل كود العنوان الوطني تلقائياً إلى أحرف كبيرة (`strtoupper`).

2. **تحديث قيمة العنوان الوطني في قاعدة البيانات عبر واجهة إدارة المفاتيح الرسمية (Key Management):**
   - تحديث حقل `postcode` لقيد المستودع الافتراضي (`inventory_sources` حيث `code = 'default'`) ليحمل كود العنوان الوطني السعودي المعتمد لمستودع العزيزية المكون من 8 خانات (مثال: `RNNA4124` أو الكود الوطني المعتمد للمنشأة).

3. **إضافة التحقق الصارم في واجهة إدارة المفاتيح (`AliExpressKeysController`):**
   - إضافة قاعدة التحقق في تبويب `warehouse`:
     ```php
     'warehouse_postcode' => [
         'required',
         'string',
         $request->input('warehouse_country') === 'SA' ? 'regex:/^[A-Za-z]{4}[0-9]{4}$/' : 'max:20'
     ],
     ```

4. **حظر أي Hardcoded Fallback وتأمين سجلات التدقيق:**
   - منع أي Fallback ثابت في الكود خارج اختبارات الـ Unit Test.
   - تمويه كود العنوان الوطني وأرقام الهواتف في سجلات التدقيق والاستثناءات (`AB****34`).

---

## 7. مصفوفة الاختبارات المقترحة (Proposed Test Matrix)

1. **اختبار التحقق من العنوان الوطني:**
   - تمرير كود 8 خانات (`ABCD1234`) $\rightarrow$ نجاح بناء الـ Payload وتمريره كـ `zip`.
   - تمرير كود 5 أرقام (`11564`) $\rightarrow$ إلقاء استثناء `SHIPPING_ADDRESS_INVALID_SA_NATIONAL_ADDRESS` قبل أي اتصال بـ API.
2. **اختبار تحويل الأحرف:**
   - تمرير `abcd1234` $\rightarrow$ تطبيعه تلقائياً إلى `ABCD1234`.
3. **اختبار عزل Preflight عن Create:**
   - التأكد من أن فشل العنوان الوطني يمنع استدعاء `aliexpress.ds.order.create` تماماً مع تسجيل الفشل محلياً.
4. **اختبار تمويه السجلات:**
   - التأكد من عدم ظهور الكود الوطني كاملاً في الـ Logs أو Snapshots.

---

## 8. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  SAUDI_NATIONAL_ADDRESS_ROOT_CAUSE_CONFIRMED
======================================================================
```

> **تأكيد التوقف الكامل:**  
> تم الانتهاء بنجاح كامل من التدقيق الجذري وتحديد سبب رفض AliExpress بدقة (إرسال رمز بريدي 5 أرقام بدلاً من كود العنوان الوطني السعودي 8 خانات). لم يتم إجراء أي اتصال بـ API، ولم يتم تعديل أي ملف كود أو قاعدة بيانات. النظام متوقف تماماً بانتظار توجيهات قائد التنفيذ.
