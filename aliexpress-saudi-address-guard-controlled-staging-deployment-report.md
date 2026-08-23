# تقرير النشر المضبوط لحارس العنوان الوطني السعودي على بيئة Staging
(Controlled Staging Deployment Report for Unified Saudi National Address Guard)

**تاريخ وتوقيت النشر والتحقق:** 2026-08-23 04:32:30 +03:00  
**معرّف الـ Commit المنشور (Target HEAD):** `c517da3d22e6dac2b872993ec2a2948b4d183f63`  
*(Descendant of `ce87b4d4670a92eacfdbfe7ba1da3e2a7a5ca53c` - `feat(shipping): introduce unified Saudi national address guard validator across V1 and V2 AliExpress order creation`)*  
**الفرع المستهدف:** `feat/delivery-admin-ui-rebuild`  
**البيئة المستهدفة:** `Staging (highest-ye.store)`  
**طريقة النشر:** Git-Only (`git fetch` + `git checkout` محافظ، دون أي SFTP أو cp يدوي لمسار التطبيق).  
**النتيجة والحكم النهائي الملزم:**  
```
STAGING_SAUDI_ADDRESS_GUARD_READY_FOR_NEW_SIMULATION_APPROVAL
```

---

## 1. بيان الامتثال لقيود السلامة والممنوعات المطلقة

```text
======================================================================
  STRICT SAFETY & ZERO-SIDE-EFFECT CONFIRMATIONS
======================================================================
[CONFIRMED] ZERO_API_CALLS:            Zero live AliExpress API calls made during deployment or verification.
[CONFIRMED] ZERO_OAUTH_REFRESH:        Zero OAuth token refreshes triggered.
[CONFIRMED] ZERO_DB_WRITES:            Database business data, sources, and inventory 100% preserved.
[CONFIRMED] HISTORICAL_RECORDS_SAFE:   SPO #35/EPO #26 and SPO #36/EPO #27 remain 100% intact and unchanged.
[CONFIRMED] ZERO_SECRETS_EXPOSED:      Exceptions, logs, and outputs strictly mask PII and short address codes.
[CONFIRMED] ZERO_SIMULATION_MUTATION:  Zero new simulations, customer orders, or purchase orders created.
[CONFIRMED] GIT_ONLY_DEPLOYMENT:       Deployed strictly via Git checkout without overwriting unmanaged files.
======================================================================
```

---

## 2. سجل بوابات النشر والنسخ الاحتياطي (Deployment Gates & Backup)

### 1. حالة الـ Git المحلية والمصدر (Origin):
- **Local HEAD:** `c517da3d22e6dac2b872993ec2a2948b4d183f63`
- **Origin HEAD:** `c517da3d22e6dac2b872993ec2a2948b4d183f63`
- **Commit Log:**
  - `c517da3` *fix(tests): use fully qualified Console Kernel contract in isolated test runner*
  - `ce87b4d` *feat(shipping): introduce unified Saudi national address guard validator across V1 and V2 AliExpress order creation*

### 2. النسخة الاحتياطية قبل النشر (Pre-deployment Backup):
- **مسار النسخ الاحتياطي خارج Webroot:**
  ```
  /home/highest-ye/backups/address_guard_pre_deploy_20260823_043206
  ```
- **الملفات والمجلدات المؤرشفة:**
  - `Procurement_backup/` (`packages/Webkul/Procurement`)
  - `Fulfillment_backup/` (`packages/Webkul/Fulfillment`)
  - `AliExpress_backup/` (`app/Services/AliExpress`)
  - `AliExpressKeysController.php.bak` (`app/Http/Controllers/AliExpress/AliExpressKeysController.php`)

### 3. التحقق من اكتمال نشر Git على Staging:
- **Staging HEAD الفعلي:** `c517da3d22e6dac2b872993ec2a2948b4d183f63`
- **حالة شجرة العمل (`git status --short`):** نظيفة تماماً (`Clean / Empty`).
- **فرق الكود (`git diff --exit-code`):** `0` (لا يوجد أي تباين).
- **تنظيف الذاكرة المؤقتة (Cache Clear):**
  - `php artisan config:clear` (تم بنجاح).
  - `php artisan route:clear` (تم بنجاح).
  - `php artisan view:clear` (تم بنجاح).

---

## 3. التحقق التشغيلي الميداني على Staging (Runtime Verification)

### 1. فحص مصدر العنوان الوطني السعودي الحالي (Current Source Invariants):
تم فحص السجل الفعلي لمستودع الشحن في جدول `inventory_sources` (`code = default`):

| البند | القيمة المحققة | الحالة |
| :--- | :--- | :--- |
| **كود المستودع** | `default` | مطابق |
| **الدولة (`country`)** | `SA` | مطابق |
| **وجود الرمز المختصر (`zip_present`)** | `true` | محقق |
| **طول الرمز (`zip_length`)** | `8` خانات | محقق ومطابق |
| **مطابقة نمط الكود الوطني (`matches_pattern`)** | `true` (مطابق لـ `^[A-Z]{4}[0-9]{4}$`) | محقق بنسبة 100% |
| **القيمة المموهة الآمنة (`zip_masked`)** | `RO****41` | آمنة وخالية من أي تسريب |
| **التحقق عبر المدقق المركزي (`Validator DTO`)** | `is_valid: true`, `country: SA`, `zip_len: 8` | نجاح التحقق الدوميني |

### 2. مصفوفة الاختبارات المعزولة على بيئة Staging:
تم تشغيل جناح الاختبارات الميداني على Staging عبر Mock Transport مع منع كامل لأي استدعاء شبكي:

```text
======================================================================
  ALIEXPRESS SAUDI NATIONAL ADDRESS GUARD ISOLATED TEST SUITE (STAGING)
======================================================================

[PASS] Test 1: SA valid fixture produces correct uppercase 8-char zip and matching V1/V2 output.
[PASS] Test 2: SA 5-digit postal fixture throws domain error and prevents API client call (clientCalls = 0).
[PASS] Test 3: SA missing, short, malformed codes guarded and lowercase normalizes to uppercase.
[PASS] Test 4: Non-SA fixtures (US and AE) do not fail due to Saudi regex.
[PASS] Test 5: Masked summary and string representation do not leak raw address, phone, or secrets.
[PASS] Test 6: V2 Gateway preflight and submitUnpaid are both strictly guarded before API call.

======================================================================
  TEST SUMMARY: 6 tests passed, 35 assertions verified (100% SUCCESS)
======================================================================
```

---

## 4. تدقيق قاعدة البيانات وثبات السجلات التاريخية (DB Delta & Historical Audit)

### 1. إحصائيات الجداول قبل وبعد النشر (Read-Only Baseline vs After):

| الجدول | قبل النشر | بعد النشر | الفارق (Delta) | الحالة |
| :--- | :---: | :---: | :---: | :---: |
| `orders` | 18 | 18 | **0** | ثابت |
| `order_items` | 26 | 26 | **0** | ثابت |
| `invoices` | 5 | 5 | **0** | ثابت |
| `shipments` | 0 | 0 | **0** | ثابت |
| `refunds` | 2 | 2 | **0** | ثابت |
| `procurement_demands` | 3 | 3 | **0** | ثابت |
| `procurement_batches` | 28 | 28 | **0** | ثابت |
| `supplier_purchase_orders` | 28 | 28 | **0** | ثابت |
| `supplier_purchase_order_items` | 8 | 8 | **0** | ثابت |
| `external_platform_orders` | 25 | 25 | **0** | ثابت |
| `inventory_sources` | 8 | 8 | **0** | ثابت |
| `product_inventories` | 2,759 | 2,759 | **0** | ثابت |
| `aliexpress_tokens` | 27 | 27 | **0** | ثابت |

### 2. ثبات سجلات الفشل التاريخية للتدقيق:

- **`SPO #35` (المحاكاة الأولى):**
  - الحالة: `state = supplier_exception`, `payment_state = submission_failed` (ثابتة 100%).
- **`EPO #26` (المحاكاة الأولى):**
  - الحالة: `raw_status = SUBMISSION_FAILED`, `failure_code = IllegalAccessToken`, `external_order_id = NULL` (ثابتة 100%).
- **`SPO #36` (المحاكاة الثانية):**
  - الحالة: `state = supplier_exception`, `payment_state = submission_failed` (ثابتة 100%).
- **`EPO #27` (المحاكاة الثانية):**
  - الحالة: `raw_status = SUBMISSION_FAILED`, `failure_code = B_DROPSHIPPER_DELIVERY_ADDRESS_VALIDATE_FAIL`, `external_order_id = NULL` (ثابتة 100%).

---

## 5. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  STAGING_SAUDI_ADDRESS_GUARD_READY_FOR_NEW_SIMULATION_APPROVAL
======================================================================
```

> **تأكيد التوقف التام:**  
> تم نشر واكتمال الحارس الموحد للعنوان الوطني السعودي على بيئة Staging بنجاح واكتمال تام، واجتياز كافة بوابات التحقق التشغيلي وعزل الحالات الشاذة، مع ثبات كامل لبيانات قاعدة البيانات ومطابقة الفوارق للصفر (`Deltas = 0`).  
> **لم يتم إنشاء أي محاكاة جديدة، ولم يتم إجراء أي طلب خارجي أو تجديد OAuth.** النظام متوقف تماماً بانتظار أوامر قائد التنفيذ للمرحلة التالية.
