# وثيقة الاعتماد المبدئي لـ Preflight الحي على نسخة Git النظيفة (Git-Clean Live Preapproval)

**تاريخ ووقت الاستخراج:** 2026-08-23 01:51:23 +03:00  
**تاريخ ووقت انتهاء الصلاحية:** 2026-08-23 02:06:23 +03:00 *(صلاحية 15 دقيقة فقط)*  
**الحالة والقرار النهائي:** `GIT_CLEAN_STRICT_LIVE_PREAPPROVAL_READY`  
**مرجع المسودة في الذاكرة:** `DRAFT-SIM-SA-DF13E807`

---

## 1. إثبات نزاهة شجرة Git وبيئة Staging (Git Cleanliness & Invariants)

```text
======================================================================
  GIT REPOSITORY & APPLICATION INTEGRITY PROOF
======================================================================
Staging Git HEAD:        f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4
Git Working Tree:        CLEAN (0 untracked modifications, exit code 0)
File SHA256 (Disk):      5cf6fdf3b6aebab7702316959e573a3f0809baf9b48f8549d3dd03052e5269a4
Blob SHA256 (HEAD):      5cf6fdf3b6aebab7702316959e573a3f0809baf9b48f8549d3dd03052e5269a4
Byte-for-Byte Match:     100% VERIFIED
APP_DEBUG:               false
======================================================================
```

---

## 2. جدول البيانات والمطابقة الحية الصارمة للـ Preflight

| الحقل / المتطلب | القيمة الحية المستخرجة والتحقق البرمجي | الحالة |
| :--- | :--- | :---: |
| **الحكم النهائي** | `GIT_CLEAN_STRICT_LIVE_PREAPPROVAL_READY` | **READY** |
| **صلاحية العرض** | 15 دقيقة نافذة استحقاق (تنتهي عند 02:06:23 +03:00) | **سارية** |
| **مرجع المسودة** | `DRAFT-SIM-SA-DF13E807` *(في الذاكرة فقط)* | **معزول** |
| **المنتج / المورد** | `1005010378829324` — Men's Casual Sports Shoes (متجر: `Shop1102890756 Store`) | **مطابق وموثق** |
| **الـ SKU والسمات** | SKU: `12000052207602660` \| `sku_attr`: `14:29;200000124:200000364` (Color: White, Size: 39) | **مطابقة تامة** |
| **الكمية** | `1` قطعة | **محددة** |
| **عنوان الشحن** | `SA / Riyadh / Key Management source [default]` (المصدر الوحيد: `inventory_sources.code=default`) | **موثق** |
| **خدمة الشحن والتتبع** | Service: `CAINIAO_FULFILLMENT_STD` \| Tracking: `Available (true)` \| النطاق: `7 - 11` يوم | **مؤكد حياً** |
| **سعر المنتج الحي** | Raw: `offer_sale_price` (`27.15`) \| `decimal_usd` \| `2715 minor` $\rightarrow$ **`$27.15 USD`** | **مطبع بدقة** |
| **رسوم الشحن الحية** | Raw: `shipping_fee_cent` (`5.00`) \| `decimal_major_despite_cent_name` \| `500 minor` $\rightarrow$ **`$5.00 USD`** | **مطبع بدقة** |
| **الرسوم والخصومات** | `0 minor` (صفر موثق) | **صفر** |
| **الإجمالي المحسوب** | $(2715 \text{ minor} \times 1) + 500 \text{ minor} + 0 - 0 = \mathbf{3215\text{ minor USD}}$ $\rightarrow$ $\mathbf{\$32.15\text{ USD}}$ | **محسوب بدقة** |
| **نمط الإنشاء المستقبلي** | `manual-payment-only` \| `try_to_pay = false` \| `UNPAID_ONLY` | **مؤمن بالكامل** |
| **دليل استدعاءات الـ API** | تم استدعاء `ds.product.get` و `ds.freight.query` فقط؛ لم يستدعَ `order.create` أو `order.get`. | **قراءة فقط** |
| **سلامة قاعدة البيانات** | لم يتم إنشاء أو تعديل أي سجل في جداول الأعمال، المخزون، أو المحاسبة (Delta = 0). | **مطابقة 100%** |

---

## 3. مصفوفة تطبيع المبالغ المالية (Money Normalization Matrix)

```text
======================================================================
  FINANCIAL & FREIGHT NORMALIZATION EVIDENCE
======================================================================
1. Product Unit Price:
   - Raw Field:          offer_sale_price
   - Raw Value:          "27.15"
   - Raw Unit:           decimal_usd
   - Normalized Minor:   2715 cents
   - Formatted Value:    $27.15 USD

2. Logistics Freight Fee:
   - Raw Field:          shipping_fee_cent
   - Raw Value:          "5.00"
   - Raw Unit:           decimal_major_despite_cent_name
   - Normalized Minor:   500 cents
   - Formatted Value:    $5.00 USD

3. Total Order Amount:
   - Exact Formula:      (2715 minor × 1 qty) + 500 freight minor + 0 fees = 3215 minor
   - Normalized Minor:   3215 cents
   - Formatted Value:    $32.15 USD
======================================================================
```

---

## 4. إثبات سلامة وثبات قاعدة البيانات (Database Invariance Audit)

| الجدول الحساس | العدد قبل الـ Preflight | العدد بعد الـ Preflight | التغيير (Delta) |
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

## 5. إذن الموافقة المطلوب لاحقاً للمالك (Unexecuted Authorization Block)

```
إذن مطلوب لاحقًا: إنشاء AliExpress order واحد غير مدفوع فقط للمرجع DRAFT-SIM-SA-DF13E807،
بحد أقصى $32.15 USD ضمن نافذة العرض (تنتهي عند 02:06:23 +03:00)؛
لا دفع ولا إلغاء ولا مخزون ولا مالية.
```

> [!IMPORTANT]
> **تأكيد التوقف الكامل:** تم التوقف التام عند تسليم هذه الوثيقة. لم يتم إنشاء أي أمر AliExpress، لم يتم طلب دفع، ولم يتم إجراء أي إلغاء أو أي كتابة في قاعدة البيانات. الكتلة أعلاه معروضة فقط للمالك لطلب الموافقة الحصرية الصريحة عليها.
