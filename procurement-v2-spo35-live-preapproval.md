# وثيقة الاعتماد المبدئي لـ Preflight الحي المجدد لأمر الشراء الشرعي SPO #35 (Renewed SPO-35 Live Preapproval)

**تاريخ ووقت الاستخراج المجدد:** 2026-08-23 02:41:58 +03:00  
**تاريخ ووقت انتهاء الصلاحية:** 2026-08-23 02:56:58 +03:00 *(صلاحية 15 دقيقة فقط)*  
**أمر شراء المورد المعتمد:** `SPO-20260823-YXOU0M-01` (ID #35)  
**رمز المحاكاة المرتبط:** `SIM-PROC-V2-20260822232451-BA4D7F`  
**الحالة والقرار النهائي:** `SPO_LIVE_PREAPPROVAL_READY`

---

## 1. إثبات المصدر ونزاهة شجرة Git وبيئة Staging

```text
======================================================================
  APPLICATION & SPO CONTEXT VERIFICATION
======================================================================
Staging Git HEAD:        f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4
Git Working Tree:        CLEAN (0 modifications, exit code 0)
APP_DEBUG:               false
Supplier PO:             ID #35 (SPO-20260823-YXOU0M-01)
Supplier PO State:       ready_to_submit
Procurement Batch:       ID #27 (BATCH-20260823-O7RVEE, state: approved)
External Order ID:       NULL (Not submitted, 0 external calls)
======================================================================
```

---

## 2. جدول البيانات والمطابقة الحية الصارمة للـ Preflight

| الحقل / المتطلب | القيمة الحية المستخرجة والتحقق البرمجي | الحالة |
| :--- | :--- | :---: |
| **الحكم النهائي** | `SPO_LIVE_PREAPPROVAL_READY` | **READY** |
| **صلاحية العرض** | 15 دقيقة نافذة استحقاق (تنتهي عند 02:56:58 +03:00) | **سارية ومجددة** |
| **أمر شراء المورد** | `SPO-20260823-YXOU0M-01` (ID #35) \| الحالة: `ready_to_submit` | **معتمد وشرعي** |
| **الدفعة المرتبطة** | `BATCH-20260823-O7RVEE` (ID #27) \| الحالة: `approved` | **معتمدة** |
| **المنتج / المورد** | `1005010378829324` — Men's Casual Sports Shoes (متجر: `Shop1102890756 Store`) | **موثق حياً** |
| **الـ SKU والسمات** | SKU: `12000052207602660` \| `sku_attr`: `14:29;200000124:200000364` (White, Size 39) | **مطابقة تامة** |
| **الكمية** | `1` قطعة | **محددة** |
| **عنوان الشحن** | `SA / Riyadh / Key Management source [default]` (المصدر الوحيد: `inventory_sources.code=default`) | **موثق** |
| **خدمة الشحن والتتبع** | Service: `CAINIAO_FULFILLMENT_STD` \| Tracking: `Available (true)` \| النطاق: `7 - 11` يوم | **مؤكد حياً** |
| **سعر المنتج الحي** | Raw: `offer_sale_price` (`27.15`) \| `decimal_usd` \| `2715 minor` $\rightarrow$ **`$27.15 USD`** | **مطبع بدقة** |
| **رسوم الشحن الحية** | Raw: `shipping_fee_cent` (`5.00`) \| `decimal_major_despite_cent_name` \| `500 minor` $\rightarrow$ **`$5.00 USD`** | **مطبع بدقة** |
| **الرسوم والخصومات** | `0 minor USD` (صفر موثق) | **صفر** |
| **الإجمالي المحسوب** | $(2715 \text{ minor} \times 1) + 500 \text{ minor} + 0 - 0 = \mathbf{3215\text{ minor USD}}$ $\rightarrow$ $\mathbf{\$32.15\text{ USD}}$ | **محسوب بدقة** |
| **دليل استدعاءات الـ API** | تم استدعاء `ds.product.get` و `ds.freight.query` فقط؛ لم يستدعَ `order.create` أو `order.get`. | **قراءة فقط** |
| **سلامة قاعدة البيانات** | لم يتم تعديل أي سجل في جداول الأعمال، المخزون، أو المحاسبة (Delta = 0). | **مطابقة 100%** |

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
| `orders` | `17` | `17` | `0` |
| `order_items` | `25` | `25` | `0` |
| `order_payment` | `14` | `14` | `0` |
| `addresses` | `63` | `63` | `0` |
| `procurement_demands` | `2` | `2` | `0` |
| `procurement_batches` | `27` | `27` | `0` |
| `supplier_purchase_orders` | `27` | `27` | `0` |
| `supplier_purchase_order_items` | `7` | `7` | `0` |
| `procurement_demand_allocations` | `5` | `5` | `0` |
| `procurement_cost_snapshots` | `13` | `13` | `0` |
| `procurement_audit_logs` | `15` | `15` | `0` |
| `external_platform_orders` | `23` | `23` | `0` |
| `invoices` | `5` | `5` | `0` |
| `shipments` | `0` | `0` | `0` |
| `refunds` | `2` | `2` | `0` |
| `failed_jobs` | `0` | `0` | `0` |

---

## 5. إذن الموافقة المطلوب من المالك (Unexecuted Authorization Block)

```
إذن مطلوب من المالك: إنشاء AliExpress order واحد غير مدفوع من SPO-20260823-YXOU0M-01 فقط،
بحد أقصى $32.15 USD ضمن صلاحية العرض (تنتهي عند 02:56:58 +03:00)؛
لا دفع ولا إلغاء ولا مخزون ولا مالية.
```

> [!IMPORTANT]
> **تأكيد التوقف الكامل:**  
> تم التوقف التام عند تسليم هذه الوثيقة. لم يتم استدعاء أي أمر لإنشاء طلب AliExpress (`order.create`)، لم يتم طلب دفع، ولم يتم إجراء أي إلغاء أو أي كتابة في قاعدة البيانات. الكتلة أعلاه معروضة فقط للمالك لطلب الموافقة الحصرية الصريحة عليها للبدء بالإنشاء الفعلي للـ SPO #35.
