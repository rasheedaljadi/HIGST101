# تقرير استعادة نزاهة Git لمطبع رسوم AliExpress على Staging (Money Normalizer Git Integrity Report)

**تاريخ وتوقيت التدقيق:** 2026-08-23 01:38:00 +03:00  
**الـ Target Commit SHA:** `f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4`  
**البيئة المستهدفة:** Staging (`highest-ye.store`)  
**القرار النهائي:** `STAGING_MONEY_NORMALIZER_GIT_INTEGRITY_READY_FOR_FRESH_PREFLIGHT`

---

## 1. إثبات المصدر وسلسلة الـ Git والتحقق التام (Git Lineage & Integrity Proof)

```text
======================================================================
  GIT COMMIT & REPOSITORY INVARIANTS
======================================================================
Target Commit SHA:       f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4
Commit Message:          fix(procurement): strictly normalize aliexpress freight fees and add unit test coverage
Origin Push Status:      Cleanly pushed to origin/feat/delivery-admin-ui-rebuild (NO FORCE)
Staging HEAD Before:     e4f9dbd0ddb7b24b53d691af801267def5a960ab
Staging HEAD After:      f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4
Staging Git Status:      CLEAN (0 modifications, exit code 0)
Staging File SHA256:     5cf6fdf3b6aebab7702316959e573a3f0809baf9b48f8549d3dd03052e5269a4
Staging HEAD Blob SHA256:5cf6fdf3b6aebab7702316959e573a3f0809baf9b48f8549d3dd03052e5269a4
Blob Matching Result:    EXACT 100% BYTE-FOR-BYTE MATCH
======================================================================
```

---

## 2. توثيق الفرق وسجل النسخ الاحتياطي (Backup & Manual SFTP Remediation)

### أ. فحص الحالة السابقة قبل الإصلاح
* كان الملف على Staging قد رُفع عبر SFTP مباشرة بعد تعديل محلي، مما جعله في حالة `DIRTY_MANUAL_NORMALIZER_CHANGE` مقارنة بـ `HEAD` السابق.
* تم إلغاء الاعتماد على أي تعديل يدوي، وأُخذت نسخة احتياطية آمنة للملف خارج مسار الـ Webroot:
  * **المسار:** `/home/highest-ye/backups/normalizer_backup_20260823_003516/AliExpressMoneyNormalizer.php`
  * **بصمة النسخة السابقة:** `331de67bf0a1d7a2b2535b7f7ad16fcdc138bc42f932e85987403c793588d9c3`

### ب. النشر عبر Git الصارم
* تم تنفيذ النشر عبر `git fetch` و `git checkout f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4` بالكامل، وأصبح الملف الفعّال مطابقاً تماماً للـ Blob الموثق في شجرة Git الرسمية.

---

## 3. مصفوفة عقد التطبيع المالي المعتمدة (Normalization Contract Matrix)

| الإدخال الخام الوارد من الرد | الكشف والوحدة المعتمدة (`raw_unit`) | الوحدات الصغرى (`minor_cents`) | القيمة العشرية المنسقة | الحالة |
| :--- | :--- | :---: | :---: | :---: |
| `shipping_fee_cent = 500` أو `"500"` (Integer) | `minor_cents` | `500` | `"$5.00"` | **PASS** |
| `shipping_fee_cent = "5.00"` (Live Fixture Decimal String) | `decimal_major_despite_cent_name` | `500` | `"$5.00"` | **PASS** |
| `shipping_fee = "12.50"` أو `freight = 12.5` | `decimal_standard` | `1250` | `"$12.50"` | **PASS** |
| `is_free = true` أو `free_shipping = true` | `boolean_free` | `0` | `"$0.00"` | **PASS** |
| قيم متعارضة (`cent = 500` و `decimal = "15.00"`) | `conflict` | `0` | `"$0.00"` | **REJECTED (`PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS`)** |
| حقل مفقود أو غير قابل للتفسير | `unknown` | `0` | `"$0.00"` | **REJECTED (`PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS`)** |

---

## 4. نتائج اختبارات الوحدة والحزمة على Staging (Test Execution Results)

### أ. اختبارات الوحدة للمطبع (`AliExpressMoneyNormalizerTest.php`)
```text
PASS: test_normalizes_integer_cents_correctly
PASS: test_normalizes_decimal_string_in_cent_field_without_100x_error
PASS: test_normalizes_standard_decimal_fee_correctly
PASS: test_normalizes_free_shipping_correctly
PASS: test_rejects_conflicting_shipping_fee_fields
PASS: test_rejects_missing_or_ambiguous_fields
----------------------------------------------------------------------
Summary: 6 tests, 6 passed, 0 failed (100% Success).
```

### ب. اختبارات حواجز الـ Gateway الصارمة (`run_strict_gateway_correctness_tests.php`)
```text
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
----------------------------------------------------------------------
Summary: 13 tests, 13 passed, 0 failed (100% Success).
```

---

## 5. إثبات سلامة وثبات قاعدة البيانات (Database Invariance Audit)

| الجدول الحساس | العدد قبل النشر | العدد بعد النشر | التغيير (Delta) |
| :--- | :---: | :---: | :---: |
| `external_platform_orders` | `23` | `23` | `0` |
| `supplier_purchase_orders` | `26` | `26` | `0` |
| `procurement_batches` | `26` | `26` | `0` |
| `procurement_demands` | `1` | `1` | `0` |
| `procurement_demand_allocations` | `4` | `4` | `0` |
| `procurement_cost_snapshots` | `11` | `11` | `0` |
| `inventory_sources` | `8` | `8` | `0` |
| `aliexpress_webhook_inbox_messages` | `13` | `13` | `0` |
| `orders` | `14` | `14` | `0` |
| `invoices` | `5` | `5` | `0` |
| `shipments` | `0` | `0` | `0` |
| `refunds` | `2` | `2` | `0` |
| `failed_jobs` | `0` | `0` | `0` |

---

## 6. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  STAGING_MONEY_NORMALIZER_GIT_INTEGRITY_READY_FOR_FRESH_PREFLIGHT
======================================================================
```

> [!IMPORTANT]
> تم استعادة نزاهة Git بنسبة 100%، وأصبح كود بيئة Staging خاضعاً بالكامل للتحكم بالإصدارات عبر commit الهدف `f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4` الموثق على origin، مع اجتياز كافة اختبارات الوحدة والحواجز البرمجية دون أي اتصال خارجي أو مساس بقاعدة البيانات.
