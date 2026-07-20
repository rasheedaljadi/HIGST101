# مواصفات عقود الأحداث (05 - Event Contract Specification)
## منصة HIGEST — نظام إدارة الطلبات طويل العمر (OMS)

> **الإصدار:** 2.1  
> **الحالة:** معتمد للتجميد (Architecture Freeze Ready)  
> **المجال:** معمارية الأحداث (Event-Driven Architecture) — عقود البيانات للرسائل، المنتجون والمستهلكون، وقوانين المزامنة والترتيب.

---

## 1. مصفوفة الأحداث والمنتجين والمستمعين (Publisher & Subscriber Matrix)

تحدد هذه المصفوفة الهيكل العام لتداول الرسائل والأحداث بين المجالات الكبرى للمنصة:

| اسم الحدث (Event Name) | المنتج للحدث (Publisher Domain) | المستمع الأساسي (Subscriber Domain) | الهدف التشغيلي للحدث |
| :--- | :--- | :--- | :--- |
| **Sales.OrderPlaced** | المبيعات (Sales Package) | التخصيص (Allocation Domain) | بدء مراجعة الطلب وتهيئة عمليات التخصيص |
| **Sales.InvoicePaid** | المبيعات (Sales Package) | المشتريات (Procurement Bridge) | إطلاق الشراء الفعلي من الموردين ودفع الأموال |
| **Inventory.InventoryReserved** | المخزون (Inventory Domain) | التخصيص (Allocation Domain) | تأكيد قفل وحجز القطع فعلياً في المستودع |
| **Allocation.AllocationCreated** | التخصيص (Allocation Domain) | المشتريات / العمليات | ربط بنود الطلب بمصادرها والتوجيه |
| **Procurement.POCreated** | المشتريات (Procurement Bridge) | المحاسبة المالية (Ledger) | إدراج قيد ذمم دائنة للمورد في الحسابات |
| **Procurement.POSubmitted** | المشتريات (Procurement Bridge) | العمليات / خدمة العملاء | تأكيد قبول المورد الخارجي للطلب والحساب |
| **Procurement.POShipped** | المشتريات (Procurement Bridge) | اللوجستيات (Logistics / Sales) | تحديث بوليصة الشحن وتوليد الشحنة للعميل |
| **Procurement.POFailed** | المشتريات (Procurement Bridge) | المنسق (Orchestrator) / الإشعارات | تشغيل ساقا التعويض ونقل الطلب للمراجعة |
| **Financial.TimelineRecorded** | الحسابات (Financial Domain) | التحليلات ولوحة البيانات | توثيق التدفقات النقدية اللحظية والأرباح |

---

## 2. مواصفات الـ Payload للأحداث الأساسية

### 1) حدث `Sales.InvoicePaid` (تأكيد دفع الفاتورة)
* **المتغيرات الصارمة وأنواع البيانات:**
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Sales.InvoicePaid",
  "type": "object",
  "properties": {
    "event_id": { "type": "string", "format": "uuid" },
    "timestamp": { "type": "string", "format": "date-time" },
    "order_id": { "type": "integer" },
    "invoice_id": { "type": "integer" },
    "amount_paid": { "type": "number", "minimum": 0.0 },
    "currency": { "type": "string", "maxLength": 3 },
    "payment_method": { "type": "string" },
    "items": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "order_item_id": { "type": "integer" },
          "sku": { "type": "string" },
          "qty": { "type": "integer", "minimum": 1 }
        },
        "required": ["order_item_id", "sku", "qty"]
      }
    }
  },
  "required": ["event_id", "timestamp", "order_id", "invoice_id", "amount_paid", "currency", "items"]
}
```

### 2) حدث `Procurement.POShipped` (تم الشحن من المورد)
* **المتغيرات الصارمة وأنواع البيانات:**
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "title": "Procurement.POShipped",
  "type": "object",
  "properties": {
    "event_id": { "type": "string", "format": "uuid" },
    "timestamp": { "type": "string", "format": "date-time" },
    "purchase_order_id": { "type": "integer" },
    "external_order_id": { "type": "string" },
    "tracking_number": { "type": "string" },
    "carrier_code": { "type": "string" },
    "estimated_delivery": { "type": "string", "format": "date" }
  },
  "required": ["event_id", "timestamp", "purchase_order_id", "external_order_id", "tracking_number", "carrier_code"]
}
```

---

## 3. منع تكرار الأحداث (Idempotency & Deduplication)

تلتزم جميع خدمات الاستماع للأحداث (Event Listeners) بتطبيق خوارزمية منع التكرار لضمان عدم تنفيذ الإجراء المالي أو التشغيلي أكثر من مرة في حال إعادة إرسال الحدث:

1. **مفتاح المنع للحدث (Event Idempotency Key):**
   * يحتوي كل حدث على معرف فريد `event_id`.
   * قبل معالجة الحدث، يقوم المستمع بفحص جدول `processed_events` في قاعدة البيانات:
     * إذا كان المعرف موجوداً، يتم تجاهل الحدث فوراً باعتباره مكرراً تم معالجته سابقاً.
     * إذا لم يكن موجوداً، يتم إدراج المعرف بحالة `processing` داخل معاملة قاعدة البيانات للبدء بالمعالجة.
2. **عمر المفتاح في الكاش/قاعدة البيانات:**
   * تحفظ معرفات الأحداث المعالجة لمدة لا تقل عن **30 يوماً** لضمان استقرار عمليات التتبع والتسويات اللاحقة.

---

## 4. سياسات الترتيب وإعادة المحاولة (Ordering & Retry Policies)

### 1) ترتيب الرسائل (Message Ordering Constraints)
تتطلب معمارية الـ OMS ترتيباً صارماً لبعض الأحداث لضمان منطقية العمليات.
* *مثال:* يمنع معالجة حدث `Procurement.POSubmitted` قبل اكتمال معالجة `Allocation.AllocationCreated`.
* *حل المشكلة:*
  * تستخدم المنصة طوابير مهام ذات مسار تسلسلي للطلب الواحد (Partitioned/Key-Based Queues) حيث يمثل `order_id` مفتاح التقسيم (Partition Key). تضمن هذه الآلية معالجة جميع أحداث الطلب الواحد خلف بعضها بترتيب صدورها الزمني تماماً وعلى خادم معالجة (Worker) واحد.

### 2) استراتيجية إعادة المحاولة عند تعطل المستمع (Retry Policy)
* في حال فشل المستمع في معالجة الحدث بسبب خطأ مؤقت في قاعدة البيانات أو تعطل خارجي:
  1. يعاد إدراج المهمة في طابور المحاولات بفواصل زمنية متزايدة (Exponential Backoff: 5s, 30s, 15m, 1h).
  2. إذا استمر الفشل بعد **5 محاولات**، يتم نقل الحدث إلى طابور الرسائل الميتة (DLQ) وتوليد تنبيه تشغيلي بمستوى `Critical Error` لتدخل المهندسين لحل المشكلة يدوياً دون توقف الأنظمة الأخرى.
