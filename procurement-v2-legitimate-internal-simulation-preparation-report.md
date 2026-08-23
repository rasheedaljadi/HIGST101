# تقرير تدقيق تجهيز المحاكاة الداخلية الشرعية حتى Supplier PO (Legitimate Internal Simulation Preparation Report)

**تاريخ وتوقيت التدقيق:** 2026-08-23 02:05:00 +03:00  
**حالة التدقيق:** قراءة فقط (Read-Only) — صفر كتابة في قاعدة البيانات (DB Delta = 0)  
**الـ Commit المعتمد على Staging:** `f85f9b956f82f1bc4eb9e7428cd8ead06cd37be4`  
**القرار النهائي:** `SIMULATION_PATH_READY`

---

## 1. نتائج تدقيق أهلية المنتج والـ SKU وحالة العجز (Product & Deficit Audit)

```text
======================================================================
  CATALOG & INVENTORY MAPPING (READ-ONLY AUDIT)
======================================================================
External Product ID:     1005010378829324 (Men's Casual Sports Shoes)
External SKU ID:         12000052207602660 (White / Size 39)
Internal Parent ID:      3153 (Configurable Product in Bagisto)
Internal Variant ID:     3163 (Simple Variant Product in Bagisto)
Catalog SKU:             ae-1005010378829324-variant-227
Catalog Status:          Active (status=1, visible_individually=1)
Import Record:           AliExpressProductImport #457 (status: success)
Source Offer:            HigestSourceOffer #2908 (source_provider: aliexpress, cost: 27.15 USD)
Virtual Catalog Source:  aliexpress_source (qty: 482)
Owned Local Hub Stock:   hayest_dropship_ye = 0
Owned Internal Stock:    hayest_internal_ye = 0
Proven Deficit (Qty=1):  1 unit external deficit (100% genuine dropship requirement)
======================================================================
```

---

## 2. شروط استحقاق Demand وقواعد الحوكمة في مسار V2

1. **أهلية الطلب (Order Eligibility):**
   * للطلبات بالدفع عند الاستلام (`cashondelivery`): يُشترط أن تكون حالة الطلب مؤكدة (`processing` أو `pending_fulfillment`).
   * للطلبات مسبقة الدفع: يُشترط توليد الفاتورة (`invoices()->exists()`) أو حالة `processing`.
2. **عزل الطلبات المختلطة (Mixed Orders Isolation):**
   * المنتجات الداخلية غير المستوردة (`is_imported = false`) يتم حجبها ولا يُنشأ لها أي طلب خارجي؛ وإذا كان بها عجز يُسجل سجل تدقيق داخلي `internal_stock_exception`.
   * المنتجات المستوردة فقط تمر عبر تقييم رصيد `hayest_dropship_ye` المحجوز، وما يتبقى كعجز خارجي يتحول إلى `ProcurementDemand`.
3. **حوكمة المتجر والعملة (Store & Currency Grouping):**
   * يتم استخراج معرف المتجر (`1102890756`) واسم المتجر (`Shop1102890756 Store`) والعملة (`USD`).
   * تشترط الخدمة تطابق العملة `USD` بدقة، وتمنع تجميع مطالب متاجر مختلفة في أمر شراء واحد.
4. **حواجز منع التكرار (Idempotency & Guardrails):**
   * يتم توليد بصمة فريدة `active_fingerprint` عبر `hash('sha256', "demand|order_id|item_id|provider|sku")` تمنع تكرار الـ Demand لنفس البند.
   * يتم استخدام `lockForUpdate()` عند قراءة المخزون وتكوين الدفعات لمنع أي تعارض متزامن.

---

## 3. جدول مراحل التنفيذ الشرعي الأدنى للمحاكاة (Legitimate V2 Pipeline)

| المرحلة | الإجراء / الخدمة المسؤولة | المدخلات النظامية | الحالة المتوقعة | السجلات المتوقع إنشاؤها في DB | الآثار الجانبية المحظورة (Prohibited) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **1. إنشاء طلب تجريبي محاكى** | `OrderRepository` عبر مسار Bagisto / V2 | عميل تجريبي معزول (`SIM-CUST-...`)، المنتج `3163`، كمية `1`، دفع `cashondelivery`. | `order.status = processing` | `orders` (1), `order_items` (1), `order_payment` (1), `order_addresses` (2) | **ممنوع:** إنشاء فواتير حقيقية، شحنات، إشعارات للعميل، أو خصم رصيد مالي. |
| **2. تقييم العجز وتوليد Demand** | `ProcurementDemandService::processOrderDemands($order)` | كائن الطلب `$order` | `demand.state = open_for_batching` | `procurement_demands` (1), `procurement_audit_logs` (1) | **ممنوع:** استدعاء أي بوابة مورد خارجية أو حجز مخزون غير موجود. |
| **3. تجميع الدفعة وإنشاء SPO** | `ProcurementBatchService::createBatch([$demandId], $adminId)` | معرف الطلب `$demandId` ومعرف المسؤول المعتمد | `batch.state = ready_for_review`<br>`spo.state = draft` | `procurement_batches` (1), `supplier_purchase_orders` (1), `supplier_purchase_order_items` (1), `procurement_demand_allocations` (1), `procurement_cost_snapshots` (2), `procurement_audit_logs` (1) | **ممنوع:** استدعاء AliExpress API أو توليد معرف خارجي اصطناعي (`AE-LIVE-*`). |
| **4. مراجعة واعتماد أمر الشراء** | `ProcurementBatchService::approveBatch($batchId, $adminId)` | معرف الدفعة `$batchId` ومعرف المسؤول | `batch.state = approved`<br>`spo.state = ready_to_submit` | تحديث `procurement_batches` و `supplier_purchase_orders`، إضافة `procurement_audit_logs` (1) | **ممنوع:** إرسال طلب الشراء إلى المورد تلقائياً أو طلب دفع. |

---

## 4. أصغر مجموعة سجلات ستنشأ في المحاكاة ومحددات العزل (Isolation & Cleanup)

### أ. الـ Test Marker الموحد
سيتم ربط جميع السجلات الداخلية بمحدد موحد غير مبهم:
`SIM-PROC-V2-20260823-XXXX` في حقول الملاحظات والـ correlation ID.

### ب. السجلات المتوقع إضافتها عند تشغيل المحاكاة
1. `orders`: سجل واحد (طلب محاكاة تجريبي).
2. `order_items`: بند واحد للمتغير `3163`.
3. `order_payment`: سجل دفع COD تجريبي.
4. `order_addresses`: عنوان تسليم افتراضي تجريبي.
5. `procurement_demands`: طلب توريد واحد للعجز (`qty_required_external = 1`).
6. `procurement_batches`: دفعة توريد واحدة (`BATCH-...`).
7. `supplier_purchase_orders`: أمر شراء مورد واحد (`SPO-...`) بحالة `ready_to_submit`.
8. `supplier_purchase_order_items`: بند واحد مقيد بالمنتج `1005010378829324` و SKU `12000052207602660`.
9. `procurement_demand_allocations`: ربط كمي واحد بين الـ Demand وبند الـ SPO.
10. `procurement_cost_snapshots`: لقطتي تكلفة محاسبية موثقة بالدولار الأمريكي.
11. `procurement_audit_logs`: سجلات تتبع المراجعة والتدقيق.

### ج. سياسة الحوكمة وعدم التلويث (No Manual SQL Policy)
* **يمنع منعاً باتاً** استخدام أوامر `DELETE` أو التعديل المباشر عبر SQL في أي مرحلة.
* تظل السجلات معلمة بـ `SIM-PROC-V2` لضمان تكامل سجلات التدقيق المحاسبي (Audit Trail) أو تُلغى دورة حياتها عبر مسار Bagisto/V2 النظامي (`cancel`).

---

## 5. التمييز الصارم بين المحاكاة الداخلية والتفويض الخارجي

> [!IMPORTANT]
> **تأكيد مبدئي ملزم:**  
> الموافقة السابقة التي منحها المالك لإنشاء طلب AliExpress غير مدفوع ($32.15 USD) **لا تمنح تفويضاً تلقائياً** لكتابة أو إنشاء سجلات تجريبية في قاعدة البيانات الداخلية.  
> هذا التقرير هو دراسة جاهزية وتخطيط (Read-Only) تثبت أن المسار الداخلي جاهز ومطابق 100%، وفي انتظار أمر قائد التنفيذ الصريح لتشغيل المحاكاة الداخلية.

---

## 6. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  SIMULATION_PATH_READY
======================================================================
```
