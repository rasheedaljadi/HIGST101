# تقرير النشر المضبوط لإصلاح بوابة AliExpress الصارمة على Staging

**تاريخ النشر والتحقق:** 2026-08-23 00:37:00 +03:00  
**الـ Target Commit SHA:** `4c3931539e761842d9d3cae2537ce0f131b544f9`  
**البيئة المستهدفة:** Staging (`highest-ye.store`)  
**القرار النهائي:** `STAGING_STRICT_GATEWAY_READY_FOR_FRESH_LIVE_PREFLIGHT`

---

## 1. إثبات المصدر وسلسلة الـ Git (Source & Lineage Proof)

```text
======================================================================
  GIT LINEAGE & MERGE-BASE PROOF
======================================================================
Staging Git HEAD Before: e4f9dbd0ddb7b24b53d691af801267def5a960ab
Target Commit SHA:       4c3931539e761842d9d3cae2537ce0f131b544f9
Commit Message:          fix(procurement): strictly enforce P0 gates on shipping address, SKU-specific freight, money normalization, preflight pairing, and numeric ID validation
Ancestor Check:          git merge-base --is-ancestor e4f9dbd0ddb7b24b53d691af801267def5a960ab 4c3931539e761842d9d3cae2537ce0f131b544f9 -> VALID (EXIT CODE 0)
======================================================================
```

---

## 2. مصفوفة الملفات ونطاق التغيير (Files & Diff Scope)

| الملف | نوع العملية | الوصف البرمجي |
| :--- | :---: | :--- |
| `packages/Webkul/Procurement/src/Support/AliExpressMoneyNormalizer.php` | **[NEW]** | مطبّع المبالغ المالية ورسوم الشحن (Minor cents, formatted decimals, rejection of ambiguous fees). |
| `packages/Webkul/Procurement/src/DTO/AliExpressOrderPreflight.php` | **[MODIFY]** | إضافة حقول المبالغ الصغرى `shippingCostMinor` و `shippingCostFormatted` و `moneyEvidence`. |
| `packages/Webkul/Procurement/src/Gateways/AliExpressOrderSubmissionGateway.php` | **[MODIFY]** | إحكام حواجز P0 (حظر العناوين الوهمية، حظر الـ Override في الإنتاج، فرض مطابقة الـ SKU، حظر حذف SKU ID في الشحن، اقتران submitUnpaid بالـ Preflight، والتحقق الصارم من is_success والمعرف الرقمي). |

---

## 3. نقطة الاستعادة والنسخ الاحتياطي (Recovery Points & Manifests)

### أ. النسخة الاحتياطية لقاعدة البيانات (Database Backup)
* **المسار على الخادم:** `/home/highest-ye/backups/staging_db_backup_pre_strict_gw_20260823_003632.sql.gz`
* **الحجم:** `12.54 MB` (`13,154,212 bytes`)
* **SHA-256 Checksum:** `88a0a1dbebfc5ec78a308372a82b466a1739b4ce503d63782a6430935f954d1d`
* **سلامة الأرشيف (Gzip Test):** `VALID (true)`

### ب. النسخة الاحتياطية لملفات البوابة القديمة
* **المسار:** `/home/highest-ye/backups/gw_backup_731c2d/`
* تم حفظ النسخ السابقة لملفات البوابة خارج مسار الـ Webroot قبل تطبيق التحديث.

---

## 4. إثبات حواجز P0 على Staging (بلا شبكة خارجية وبلا مساس بالبيانات)

تم تشغيل حزمة اختبارات المطابقة الصارمة مباشرة على كود Staging المنشور باستخدام Mocks و Fixtures داخلية معزولة:

```text
======================================================================
  STAGING STRICT GATEWAY ISOLATED TEST RESULTS
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

## 5. حالة الـ Migrations وثبات جداول الأعمال (Database State & Migration Invariance)

### أ. حالة الـ Migrations
* **آخر Migration مسجل:** `2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table`
* **رقم الـ Batch:** `29`
* **الـ Migrations المنفذة خلال هذا الأمر:** `0` (لم يتم تشغيل أي ترحيل).

### ب. إحصائيات جداول الأعمال والمخزون والمالية (قبل وبعد النشر)

| الجدول (Table) | العدد قبل النشر | العدد بعد النشر | التغيير (Delta) | الحالة |
| :--- | :---: | :---: | :---: | :---: |
| `external_platform_orders` | `23` | `23` | `0` | **ثابت** |
| `supplier_purchase_orders` | `26` | `26` | `0` | **ثابت** |
| `procurement_batches` | `26` | `26` | `0` | **ثابت** |
| `procurement_demands` | `1` | `1` | `0` | **ثابت** |
| `procurement_demand_allocations` | `4` | `4` | `0` | **ثابت** |
| `procurement_cost_snapshots` | `11` | `11` | `0` | **ثابت** |
| `inventory_sources` | `8` | `8` | `0` | **ثابت** |
| `aliexpress_webhook_inbox_messages` | `13` | `13` | `0` | **ثابت** |
| `orders` | `14` | `14` | `0` | **ثابت** |
| `invoices` | `5` | `5` | `0` | **ثابت** |
| `shipments` | `0` | `0` | `0` | **ثابت** |
| `refunds` | `2` | `2` | `0` | **ثابت** |
| `failed_jobs` | `0` | `0` | `0` | **ثابت** |
| `AE-LIVE-*` Synthetic Records | `0` | `0` | `0` | **نظيف تماماً** |

---

## 6. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  STAGING_STRICT_GATEWAY_READY_FOR_FRESH_LIVE_PREFLIGHT
======================================================================
```

> [!IMPORTANT]
> تم إتمام نشر وتدقيق كود البوابة الصارم على بيئة Staging بنجاح كامل 100%. لم يتم استدعاء أي API حي، لم يُنشأ أو يُلغى أي طلب، ولم تتغير قاعدة البيانات أو إعدادات البيئة. الكود الصارم هو الفعّال حالياً والجاهز تماماً لأمر قائد التنفيذ بإجراء Preflight الحي.
