# تقرير إصلاح سياق هوية AliExpress المشترك بين V1 و Procurement V2 (AliExpress Authorization Context Remediation Report)

**تاريخ وتوقيت الإصلاح:** 2026-08-23 03:15:30 +03:00  
**الـ Commit المعتمد بعد الإصلاح:** `fffd0d1c42cefd9b10dc63e307c083dd9f83ef40`  
**الفرع (Branch):** `origin/feat/delivery-admin-ui-rebuild`  
**حالة Pint و StyleCheck:** `PASSED` (0 violations)  
**حالة مصفوفة الاختبارات:** `PASSED` (25/25 assertions passed)  
**القرار والنتيجة النهائية:** `PROVIDER_ACCOUNT_CONTEXT_FIX_READY_FOR_CONTROLLED_STAGING_DEPLOYMENT`

---

## 1. بيان الامتثال لقيود السلامة

```text
======================================================================
  STRICT SAFETY & ZERO-SIDE-EFFECT CONFIRMATIONS
======================================================================
[CONFIRMED] NO_LIVE_API_CALLS:       Zero AliExpress API calls made during fix.
[CONFIRMED] NO_TOKEN_REFRESH:        Zero live OAuth token refreshes triggered.
[CONFIRMED] NO_DB_BUSINESS_WRITE:    Zero historical records altered in database.
[CONFIRMED] NO_RETRY_FAILED_RECORDS: SPO #35 and EPO #26 remain historical failure records.
[CONFIRMED] NO_SECRETS_EXPOSED:      Tokens are encapsulated with #[SensitiveParameter],
                                     logs and audit trail contain zero raw secrets.
[CONFIRMED] NO_STAGING_DEPLOYMENT:   Fix is committed locally and pushed to GitHub repo.
                                     Staging deployment will proceed under explicit command.
======================================================================
```

---

## 2. الرسم التوضيحي للهندسة المشتركة (Shared Architecture Diagram)

```text
========================================================================================
  V1 OAUTH SERVICE ---> SHARED CONTEXT RESOLVER ---> V2 PROCUREMENT GATEWAY
========================================================================================

+-----------------------------------+
|      App\Models\AliExpressToken   |  --> Stored encrypted tokens (Active #193)
+-----------------------------------+
                  |
                  v
+-----------------------------------+
|  AliExpressOAuthService (V1 Core) |  --> latestToken() [Lifecycle, Validity, Auto-refresh]
+-----------------------------------+
                  |
                  v
+-------------------------------------------------------+
| AliExpressAuthorizationContextResolver (V2 Interface) |
|   -> resolveForDropshipperSubmission()                |  --> Validates active grant before API call
|   -> Encapsulates ResolvedAliExpressAuthorization DTO |  --> Throws ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE
+-------------------------------------------------------+
                  |
                  v
+-------------------------------------------------------+
|      AliExpressOrderSubmissionGateway (V2 Gateway)    |
|   -> preflight()                                      |  --> Receives ResolvedAliExpressAuthorization
|   -> submitUnpaid()                                   |  --> ZERO find($providerAccountId) calls
|   -> getOrder()                                       |  --> ZERO token PK assumptions
+-------------------------------------------------------+
```

---

## 3. تفاصيل الملفات المعدلة وقرار الـ Schema

### أ. قرار الـ Schema:
- **عدم الحاجة لأي Migration جديدة:**  
  جميع جداول Procurement V2 (`procurement_demands`, `procurement_batches`, `supplier_purchase_orders`, `external_platform_orders`) تحتوي مسبقاً على الحقل `provider_account_id` بصيغة `nullable()` وبدون Foreign Key قسري.
- **تطبيق الدلالة السليمة:**  
  تم توحيد سلوك النظام ليبقى `provider_account_id = null` لسياق V1 الافتراضي، ولا يتم تخزين الـ Primary Key الداخلي لجدول التوكنات إطلاقاً.

### ب. قائمة الملفات المتغيرة والجديدة:

| الملف | نوع التغيير | الوصف والهدف |
| :--- | :---: | :--- |
| `packages/Webkul/Procurement/src/Contracts/AliExpressAuthorizationContextResolver.php` | **جديد (NEW)** | واجهة برمجية موحدة لاسترجاع سياق التفويض والتحقق من صلاحيته. |
| `packages/Webkul/Procurement/src/DTO/ResolvedAliExpressAuthorization.php` | **جديد (NEW)** | كائن بيانات محمي يغلف `accessToken` بوسم `#[SensitiveParameter]` ويقدم ملخصاً مموهاً. |
| `packages/Webkul/Procurement/src/Exceptions/AliExpressAuthorizationUnavailableException.php` | **جديد (NEW)** | استثناء Domain صريح يحمل الرمز `ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE`. |
| `packages/Webkul/Procurement/src/Services/AliExpressAuthorizationResolver.php` | **جديد (NEW)** | التنفيذ الرسمي المعتمد على `AliExpressOAuthService::latestToken()`. |
| `packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php` | **معدل (MODIFY)** | حقن الـ Resolver واستخدامه في `preflight` و `submitUnpaid` و `getOrder`، وإزالة دالة `resolveToken` السابقة التي كانت تستدعي `find()`. |
| `packages/Webkul/Procurement/src/Providers/ProcurementServiceProvider.php` | **معدل (MODIFY)** | تسجيل `AliExpressAuthorizationContextResolver` كـ Singleton في الحاوية. |
| `packages/Webkul/Procurement/src/Services/ProcurementEligibilityService.php` | **معدل (MODIFY)** | السطر 147: إزالة `?? 1` واستبدالها بـ `?? null`. |
| `packages/Webkul/Procurement/tests/Unit/AliExpressAuthorizationResolverTest.php` | **جديد (NEW)** | اختبارات الوحدة الشاملة لحل السياق والتحقق من عدم تسريب الأسرار. |
| `scripts/run_auth_context_remediation_tests.php` | **جديد (NEW)** | مشغل فحص شامل يحاكي كافة السيناريوهات. |

---

## 4. إثبات إزالة `?? 1` و `find(provider_account_id)`

```text
======================================================================
  STATIC CODE VERIFICATION PROOF
======================================================================
1. ProcurementEligibilityService.php:
   - Line 147 Before: 'provider_account_id' => $payload['provider_account_id'] ?? 1,
   - Line 147 After:  'provider_account_id' => $payload['provider_account_id'] ?? null,
   - Result: ZERO instances of '?? 1' remaining in the service.

2. AliExpressOrderSubmissionGateway.php:
   - Before: protected function resolveToken(?int $accountId = null) { ... $this->oauthService->getTokenById($accountId); }
   - After:  Completely removed. All methods call $this->authResolver->resolveForDropshipperSubmission().
   - Result: ZERO instances of getTokenById() or find() on token table.
======================================================================
```

---

## 5. نتائج الاختبارات ومطابقة الأسلوب (Test Results & Code Quality)

```text
======================================================================
  TEST MATRIX EXECUTION RESULTS (25 / 25 PASSED)
======================================================================
PASS: Test 1.1: Context accessToken matches token
PASS: Test 1.2: Context accountIdentifier matches seller_id/account_id
PASS: Test 1.3: Context sellerId is preserved
PASS: Test 1.4: Context account is properly masked (b***@highest-internal.test)
PASS: Test 1.5: Context isValid is true
PASS: Test 1.6: Masked summary masks account_identifier (4586***)
PASS: Test 1.7: Masked summary masks seller_id (4586***)
PASS: Test 1.8: Masked summary omits accessToken secret (Safe Audit)
PASS: Test 2.1: Missing token throws AliExpressAuthorizationUnavailableException
PASS: Test 2.2: Error code is ALIEXPRESS_AUTHORIZATION_CONTEXT_UNAVAILABLE
PASS: Test 2.3: hasValidAuthorization returns false when missing
PASS: Test 2.4: Expired token throws AliExpressAuthorizationUnavailableException
PASS: Test 3.1: Preflight succeeds with historical providerAccountId=1 (No find(1) crash)
PASS: Test 3.2: Freight service name is resolved (CAINIAO_FULFILLMENT_STD)
PASS: Test 3.3: Freight minor cost is normalized (500 minor cents)
PASS: Test 3.4: Exact sku_attr is resolved (14:29;200000124:200000364)
PASS: Test 4.1: ProcurementEligibilityService explicitly uses null fallback
PASS: Test 4.2: ProcurementEligibilityService contains ZERO ?? 1 fallbacks
PASS: Test 4.3: AliExpressOrderSubmissionGateway contains ZERO getTokenById / find() calls
PASS: Test 5.1: SPO #35 state is supplier_exception (Historical Isolation)
PASS: Test 5.2: SPO #35 payment_state is submission_failed (Historical Isolation)
PASS: Test 5.3: EPO #26 external_order_id is strictly NULL (Historical Isolation)
PASS: Test 5.4: EPO #26 failure_code is IllegalAccessToken (Historical Isolation)
PASS: Test 6.1: Exception errorCode is public non-sensitive string
PASS: Test 6.2: Exception message does not leak tokens

RESULTS: 25 Total Assertions | 25 Passed | 0 Failed
PINT: passed (0 style violations)
GIT DIFF: passed (0 whitespace / formatting errors)
======================================================================
```

---

## 6. خطة النشر المنضبطة للبيئة التجريبية (Controlled Staging Deployment Plan)

1. **الـ Commit المستهدف:** `fffd0d1c42cefd9b10dc63e307c083dd9f83ef40` على `origin/feat/delivery-admin-ui-rebuild`.
2. **خطوات النشر على Staging (عند صدور أمر النشر):**
   - سحب الـ Commit عبر `git pull origin feat/delivery-admin-ui-rebuild` أو `git checkout fffd0d1`.
   - التأكد من نظافة شجرة العمل `git status --short`.
   - تشغيل اختبارات الوحدة عبر `scripts/run_auth_context_remediation_tests.php` على Staging للتأكد من ربط التوكن الحي `#193` بسياق V2 بسلاسة.
   - إنشاء محاكاة داخلية جديدة ونظيفة (Fresh Simulation Order $\rightarrow$ Demand $\rightarrow$ Batch $\rightarrow$ SPO) لإثبات أن `provider_account_id = null` وأن التوكن يحل بنجاح 100%.

---

## 7. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  PROVIDER_ACCOUNT_CONTEXT_FIX_READY_FOR_CONTROLLED_STAGING_DEPLOYMENT
======================================================================
```

> **تأكيد التوقف الكامل:**  
> تم الانتهاء من كافة التعديلات، الاختبارات، والتنسيق البرمجي. تم دفع الـ Commit إلى مستودع Git (`fffd0d1c42cefd9b10dc63e307c083dd9f83ef40`). لم يتم نشر الكود على Staging أو تنفيذ أي طلبات خارجية. بانتظار أمر قائد التنفيذ للنشر المنضبط.
