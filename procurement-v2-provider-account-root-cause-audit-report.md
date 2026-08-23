# تقرير تدقيق السبب الجذري لربط حساب مزود AliExpress بين V1 و Procurement V2 (Provider Account Root Cause Audit Report)

**تاريخ وتوقيت التدقيق:** 2026-08-23 03:02:00 +03:00  
**البيئة المستهدفة:** Staging (`76.13.79.242`)  
**الـ Commit المعتمد:** `f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4`  
**حالة شجرة Git:** `CLEAN` (خالية من أي تعديل غير متتبع)  
**حالة APP_DEBUG:** `false`  
**القرار والنتيجة النهائية للتدقيق:** `PROVIDER_ACCOUNT_ROOT_CAUSE_CONFIRMED`

---

## 1. بيان صريح بالامتثال للسلامة المطلقة

```text
======================================================================
  STRICT AUDIT SAFETY & ZERO-MUTATION CONFIRMATION
======================================================================
[CONFIRMED] READ-ONLY AUDIT:         100% read-only inspection.
[CONFIRMED] NO_API_CALLS:            Zero AliExpress API calls executed.
[CONFIRMED] NO_TOKEN_REFRESH:        Zero OAuth token refreshes triggered.
[CONFIRMED] NO_DB_WRITES:            Zero database writes, updates, or deletes.
[CONFIRMED] NO_RETRY:                Zero retries of SPO #35 or EPO #26.
[CONFIRMED] NO_SECRETS_EXPOSED:      Zero tokens, secrets, or PII exposed in output.
======================================================================
```

---

## 2. (أ) نموذج هوية V1 ومصدر الحقائق (V1 Identity & Provider Model)

من خلال الفحص البرمجي لطبقة V1 (حزم Key Management و Dropshipping و AliExpress Services):

1. **حساب المورد والمحل (Provider Account / Store Identity):**
   - يتم إدارة بيانات المنصة المركزية عبر جدول `aliexpress_settings` (نموذج `App\Models\AliExpressSetting`) الذي يخزن `app_key` و `app_secret` و `business_url` و `authorize_url`.
   - لا يوجد جدول منفصل باسم `provider_accounts` ككيان متعدد الحسابات في V1؛ بل يعتمد النظام على حساب الدروب شيبينغ المصرح له عبر OAuth.
2. **سجلات التفويض والتوكن (OAuth Authorization / Token Persistence):**
   - يتم تخزين التوكنات المشفرة في جدول `aliexpress_tokens` (نموذج `App\Models\AliExpressToken`).
   - الحقول المخزنة تشمل: `account` (البريد المموه)، `account_id` (معرف المستخدم في AliExpress)، `seller_id`، `access_token` (مشفر)، `refresh_token` (مشفر)، `access_token_expires_at`، و `payload`.
   - التوكنات تُنشأ بسجلات جديدة متزايدة الترقيم التلقائي (Auto-Increment ID) عند كل دورة OAuth أو تجديد دوري.
3. **عنوان الشحن (Shipping Address Source of Truth):**
   - مصدر الحقائق الحصري لعنوان الشحن هو سجل المستودع الافتراضي في جدول `inventory_sources` حيث `code = 'default'` (المُدار من صفحة Key Management في لوحة التحكم).
4. **هوية التطبيق/العميل (App/Client Identity):**
   - مُعرفة مركزياً عبر `AliExpressSetting::current()` مع fallback إلى إعدادات `config/aliexpress.php`.
5. **إدارة دورة حياة التوكن وحالة الصلاحية (Active/Expired/Revoked Lifecycle):**
   - خدمة `App\Services\AliExpress\AliExpressOAuthService` هي المسؤولة الحصرية عن استرجاع وتجديد التوكن عبر الدالة:
     `AliExpressOAuthService::latestToken()`
   - تقوم الدالة بالتحقق من دالة `isAccessTokenValid()`، وإذا كان التوكن منتهي الصلاحية ولديه `refresh_token` صالح، تقوم تلقائياً باستدعاء `refreshToken()` وتخزين سجل جديد مشفر وإرجاعه.
6. **كيف تختار V1 الحساب الصحيح عند استدعاء الـ APIs:**
   - جميع خدمات V1 (`AliExpressProductImporter`, `AliExpressFreightService`, `AliExpressProductSyncer`) تستدعي مباشرة:
     `$token = $this->oauthService->latestToken();`
     ثم تمرر `$token->access_token` إلى `$apiClient->call($method, $token->access_token, $params)`.

### المخطط المنطقي لعلاقات V1:

```text
+------------------------+
|  aliexpress_settings   |  --> App Key / App Secret (IOP Client Credentials)
+------------------------+
           |
           v
+------------------------+
|   aliexpress_tokens    |  --> Stored OAuth Grant (access_token, refresh_token, seller_id)
+------------------------+
           |
           v
+------------------------+
| AliExpressOAuthService |  --> latestToken() [Lifecycle, Auto-refresh, Validity Guard]
+------------------------+
           |
           v
+------------------------+
|  AliExpressApiClient   |  --> call(method, access_token, params) [HMAC-SHA256 Signed]
+------------------------+
           |
           v
+------------------------+
|   AliExpress IOP API   |  --> aliexpress.ds.product.get / aliexpress.ds.freight.query
+------------------------+
```

---

## 3. (ب) نموذج V2 والانحراف البرمجي (V2 Model & Drift Analysis)

### 1. موقع وسبب كتابة القيمة الافتراضية `1`:
- **الملف الحرج:** `packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php`
- **السطر الحرج:** السطر `147`
```php
144:        return [
145:            'is_imported' => true,
146:            'provider' => 'aliexpress',
147:            'provider_account_id' => $payload['provider_account_id'] ?? 1,
148:            'supplier_store_id' => $supplierStoreId,
```
- **السبب الجذري:** تم وضع fallback افتراضي `1` بافتراض أن الحساب هو "الحساب رقم 1" (Index/ID = 1)، دون أن يكون هناك حساب بالمعرف `1` في قاعدة البيانات.

### 2. سلسلة انتشار القيمة الخاطئة في مسار V2:
```text
ProcurementEligibilityService (line 147: default 1)
        |
        v
ProcurementDemandService (persists provider_account_id = 1 to procurement_demands)
        |
        v
ProcurementBatchService (copies provider_account_id = 1 to procurement_batches & supplier_purchase_orders)
        |
        v
ProcurementSubmitService::buildOrderDraft (passes providerAccountId = 1 to ExternalOrderDraft)
        |
        v
AliExpressOrderSubmissionGateway::resolveToken(1) (calls $oauthService->getTokenById(1))
        |
        v
AliExpressToken::find(1) --> Returns NULL (Row 1 does not exist in aliexpress_tokens!)
        |
        v
Gateway Pre-Submit Safety Interceptor --> Aborts cleanly with IllegalAccessToken!
```

### 3. تحليل Foreign Key والقيود:
- في جداول V2 (`procurement_demands`, `procurement_batches`, `supplier_purchase_orders`, `external_platform_orders`):
  الحقل مُعرّف كـ `$table->unsignedBigInteger('provider_account_id')->nullable();` بدون Foreign Key قسري إلى جدول `aliexpress_tokens` (لأن `aliexpress_tokens` هو جدول دوري للتوكنات المتجددة وليس جدول حسابات ثابتة).
- الخطأ المعماري كان في دالة `AliExpressOrderSubmissionGateway::resolveToken`:
```php
    protected function resolveToken(?int $accountId = null): ?AliExpressToken
    {
        if ($accountId) {
            return $this->oauthService->getTokenById($accountId);
        }

        return $this->oauthService->latestToken();
    }
```
حيث قامت بمعاملة `$accountId` (الذي تم افتراضه `1`) كـ Primary Key لجدول `aliexpress_tokens` بدلاً من استرجاع الحساب النشط المعتمد.

### 4. فحص حالة SPO #35 و EPO #26 في قاعدة البيانات (Read-Only):
- **SPO ID #35 (`SPO-20260823-YXOU0M-01`):**
  - الحالة الحالية: `state = supplier_exception`, `payment_state = submission_failed`, `external_sync_state = submission_failed`.
  - `external_order_id`: **`NULL`**.
- **EPO ID #26:**
  - الحالة: `raw_status = SUBMISSION_FAILED`, `normalized_status = submission_failed`.
  - `failure_code`: `IllegalAccessToken`.
  - `failure_message`: `No valid AliExpress OAuth access token configured.`.
  - `external_order_id`: **`NULL`**.
- **ProcurementAuditLog #16:**
  - الإجراء: `supplier_order_submission_failed` مع توثيق السبب بصفر أسرار.
- **إثبات عدم إمكانية إعادة التقديم التلقائي:**
  كود `ProcurementSubmitService` يشترط أن يكون أمر الشراء في حالة `ready_to_submit` أو أن تكون الدفعة في حالة `approved`. بما أن SPO #35 أصبحت في حالة `supplier_exception` و EPO #26 مسجل كفشل، فإن محاولة إرسالها مجدداً محظورة ومرفوضة نظامياً وتتطلب أمر محاكاة جديد.

### 5. فحص سجلات التوكن النشطة (Masked Active Grant Status):
- **إجمالي السجلات التاريخية:** 28 سجلاً.
- **السجل النشط الصالح حالياً:** السجل رقم `#193`.
- **معرف الحساب الخارجي المرتبط:** `account_id = "4586371333"`, `seller_id = "4586371333"`.
- **الحساب المموه:** `m***@gmail.com`.
- **تاريخ انتهاء صلاحية التوكن النشط:** `2026-08-23 22:12:33` (صالح وسارٍ).
- **مفتاح الربط المستقر:** `seller_id` / `account_id` في `aliexpress_tokens`، وليس الـ Primary Key الداخلي للصف.

---

## 4. (ج) قرار التصميم والتوصية الواحدة الملزمة (Architectural Recommendation)

### مقارنة الخيارات الثلاثة:

| الخيار | المزايا | العيوب والمحاذير | الحكم |
| :--- | :--- | :--- | :---: |
| **1. Reuse V1 resolver مباشرة** | يضمن دائماً استرجاع التوكن النشط عبر `latestToken()` مع التجديد التلقائي الموثوق. | لا يوثق الحساب المعتمد في الـ DTO إن تعددت الحسابات مستقبلاً. | **مقبول كحل سريع لكنه غير مكتمل** |
| **2. Shared Provider Account Context Resolver** | يحدد سياق حساب المورد الرسمي (`ProviderAccountContext`)، يفصل الـ `seller_id` المستقر عن الـ Auto-increment ID، ويحظر أي Fallback اعتباطي. | يتطلب تعريف Interface و DTO موحد. | **الموصى به (RECOMMENDED)** |
| **3. V2 Config Mapping** | بسيط ظاهرياً. | خطير جداً؛ قد يعيد إدخال قيم افتراضية غير موثقة ويفشل عند تجديد التوكن. | **مرفوض قطعاً (REJECTED)** |

---

### التوصية المعتمدة ومحددات التصميم غير القابلة للتفاوض (Design Invariants):

> [!IMPORTANT]
> **التوصية المعتمدة: اعتماد مزود سياق حساب المورد المشترك (`AliExpressProviderAccountResolver` / `ProviderAccountContext`)**
> 
> 1. **منع الـ Fallback الافتراضي:** إزالة أي `?? 1` نهائياً من `ProcurementEligibilityService` وكافة طبقات V2.
> 2. **عدم معاملة Token PK كـ Account ID:** حقل `provider_account_id` في V2 يجب أن يخزن المعرف الثابت للمورد أو الحساب الخارجي (`seller_id` / `account_id`) أو `null` للنظام أحادي الحساب، ولا يخزن رقم الـ Primary Key الداخلي لجدول `aliexpress_tokens`.
> 3. **ملكية التوكن لـ V1:** تبقى دورة حياة التوكن وتجديده وتشفيره ملكاً حصرياً لـ `AliExpressOAuthService`.
> 4. **الفشل الآمن قبل أي اتصال خارجي:** إذا لم يتوفر سياق حساب مصرح له وصالح، يفشل المسار فوراً في مرحلة الـ Preflight مع تسجيل تدقيق نظامي آمن، دون أي محاولة استدعاء لـ `order.create`.
> 5. **فصل السجلات الفاشلة السابقة:** بقاء SPO #35 و EPO #26 كسجلات تدقيق فشل تاريخية غير قابلة للإرسال، وتنفيذ أي اختبارات لاحقة عبر محاكاة جديدة ونظيفة.

---

## 5. (د) خطة الإصلاح المحدودة ومصفوفة الاختبارات (Limited Repair Plan & Test Matrix)

### 1. الملفات المحددة للتعديل (Code Changes Plan):
1. `packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php`:
   - تعديل السطر 147 لإزالة `?? 1`، واستدعاء مزود سياق الحساب أو تعيين `provider_account_id = null` إذا لم يحدد في الحمولة.
2. `packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php`:
   - تعديل `resolveToken(?int $accountId)` لتقوم بالاسترجاع عبر سياق الحساب النشط `latestToken()`، وتجنب `getTokenById($accountId)` إلا إذا كان المعرف يطابق حساباً معرفاً ومثبتاً، مع Fallback آمن للتوكن النشط.
3. `packages/Webkul/Procurement/src/Services/ProcurementSubmitService.php`:
   - تعزيز التحقق من صحة التوكن وسياق الحساب داخل الـ Preflight الداخلي قبل الشروع في الإرسال.

### 2. هل تلزم Migration جديدة؟
- **لا تلزم أي Migration:** الجداول الحالية تحتوي بالفعل على حقول `provider_account_id` كحقول اختيارية (`nullable`) ومتوافقة مع المعرفات الرقمية.

### 3. مصفوفة الاختبارات الصارمة (Test Verification Matrix):

| # | سيناريو الاختبار | السلوك المتوقع والتحقق |
| :---: | :--- | :--- |
| **1** | وجود توكن V1 نشط ومصرح | يقوم V2 بحل سياق التوكن الصحيح بنجاح وتجاوز بوابة الـ Preflight. |
| **2** | غياب التوكن أو انتهاء صلاحيته بالكامل | يفشل المسار فوراً بـ `IllegalAccessToken` قبل أي اتصال API خارجي مع تسجيل التدقيق. |
| **3** | غياب معرف الحساب في الحمولة | لا يتم افتراض `ID=1` اعتباطياً، ويعتمد السياق الافتراضي النشط بدون أخطاء. |
| **4** | التحقق من عدم معاملة Token PK كـ Account ID | عدم استدعاء `find(1)` أو الاعتماد على ترقيم الصفوف الداخلي. |
| **5** | تنفيذ محاكاة داخلية جديدة (Fresh Simulation SPO) | إنشاء SPO جديدة نظيفة، وتنفيذ Preflight بنجاح، ثم إرسالها بحساب المورد الصحيح. |
| **6** | التحقق من مناعة SPO #35 / EPO #26 | التأكد من أن محاولة إعادة إرسال SPO #35 مرفوضة برمجياً ولا يمكن تغيير حالتها. |
| **7** | خلو السجلات والتقارير من أي أسرار | التأكد من أن حقول `access_token` و `payload` تظل مشفرة ومحجوبة تماماً في Audit Logs. |

---

## 6. الحكم النهائي الصريح

```
======================================================================
  FINAL RULING:
  PROVIDER_ACCOUNT_ROOT_CAUSE_CONFIRMED
======================================================================
```

> [!NOTE]
> **تأكيد الحالة الراهنة:**  
> تم الانتهاء من تدقيق السبب الجذري بنسبة 100% وبطريقة القراءة فقط الحصرية.  
> **لم يتم إنشاء أي طلب AliExpress، لم يتم إجراء أي دفع، لم تتم أي إعادة محاولة لـ SPO #35، ولم يتم إجراء أي تعديل على قاعدة البيانات أو الكود.** النظام في حالة توقف تام وجاهز لقرارات قائد التنفيذ.
