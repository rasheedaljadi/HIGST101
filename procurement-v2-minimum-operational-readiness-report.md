# تقرير الجاهزية التشغيلية الدنيا لوحدة الشراء V2 (Minimum Operational Path Readiness)

**تاريخ ووقت التدقيق:** 2026-08-23 00:18:00 +03:00  
**Commit SHA المعتمد:** `5ceba2a6983fc55bea67b87b7069d8c550067eac`  
**البيئة المستهدفة:** Staging (`highest-ye.store`)  
**القرار النهائي:** `MINIMUM_OPERATIONAL_PATH_READY_FOR_FRESH_PREFLIGHT`

---

## 1. مصفوفة الجاهزية التشغيلية (P0 / P1 Classification Matrix)

| الأولوية | البند التشغيلي | الحالة | الدليل الموثق |
| :--- | :--- | :---: | :--- |
| **P0 (مانع)** | إعادة استخدام عميل V1 (`AliExpressApiClient`) وعنوان إدارة المفاتيح وحل SKU والشحن الحي | **PASS** | البوابة `AliExpressOrderSubmissionGateway` تستدعي `AliExpressApiClient` الرسمي وعنوان `inventory_sources.code=default`. |
| **P0 (مانع)** | حظر Fallback والمعرفات الاصطناعية ورفض الاستجابة التجارية الفاشلة | **PASS** | استبعاد تام لأي توليد `AE-LIVE-*`؛ أي خطأ تجاري في غلاف AliExpress يعيد كائن `ExternalOrderSubmissionFailed` بلا معرف خارجي. |
| **P0 (مانع)** | `external_order_id` يقبل Null + خلو السجلات السابقة من المعرفات المزيفة | **PASS** | ترحيل `2026_08_22_000001` مطبق؛ جميع السجلات الـ 18 السابقة إما Null أو معرفات رقمية رسمية 16-Digit (صفر سجلات اصطناعية). |
| **P0 (مانع)** | مسار واحد مصرح به لـ `submitUnpaid` مع قفل التزامن وACL | **PASS** | التحقق من `state` و `lockForUpdate()` داخل المعاملة يمنع ازدواج الإرسال، وقفل الصلاحيات على مستوى أمر الشراء المعتمد. |
| **P0 (مانع)** | قراءة موجهة رسمية `getOrder(numericID)` للتحقق من الإنشاء والإلغاء | **PASS** | البوابة وخدمة `AliExpressPollingService` ترفضان المعرفات غير الرقمية، وتطبقان رتب الحالة (`STATUS_RANKS`) وتحرير التخصيصات عند الإلغاء. |
| **P0 (مانع)** | Preflight جديد وموافقة مستخدم محددة بالمبلغ قبل الإنشاء | **مؤجل للأمر التالي** | لم يُنشأ أي أمر حي في هذا الأمر؛ بانتظار أمر قائد التنفيذ لتجديد الـ Preflight وعرض الموافقة الحية. |
| **P1 (مؤجل)** | إرسال Test Callback من App Console | **مؤجل** | مؤجل لمرحلة التحسين اللاحقة ولا يعطل دورة الشراء الفردية. |
| **P1 (مؤجل)** | دوام Worker عبر Reboot وعزل طابور `default` | **جاهز ومؤجل** | تم تثبيت `Linger=yes` وخدمة Systemd على طابور `aliexpress-webhooks`، لكن لا يُعتمد عليه كشرط مانع للشراء الفردي. |
| **P1 (مؤجل)** | استقبال Webhook التلقائي في الوقت الفعلي | **جاهز ومؤجل** | الكود منشور ومختبر معزولاً، وتعتمد التجربة الفردية على المزامنة الموجهة `getOrder` كمسار أساسي موثوق. |

---

## 2. إثبات المصدر ونزاهة البيئة (Environment & Provenance Proof)

- **الـ Commit المحلي المعتمد:** `5ceba2a6983fc55bea67b87b7069d8c550067eac`
- **حالة شجرة العمل (Working Tree):** نظيفة ومطابقة (`git status --short` خالية من تعديلات غير ملتزمة على ملفات الكود).
- **بيئة الخادم:** `APP_ENV=production` و `APP_DEBUG=false` (محجوب بالكامل).
- **حالة الـ Migrations في Staging:**
  - `2026_08_22_000001_make_external_order_id_nullable_in_external_platform_orders_table` → **Ran [Batch 28]**
  - `2026_08_22_000002_create_aliexpress_webhook_inbox_messages_table` → **Ran [Batch 29]**

---

## 3. تدقيق نزاهة البيانات السابقة والمخزون والمالية (Data Integrity Audit)

تم إجراء فحص شامل لقاعدة البيانات على البيئة التجريبية:

```json
{
    "total_external_orders": 18,
    "synthetic_or_invalid_external_orders_count": 0,
    "failed_records_with_external_id": 0,
    "inventory_movements_count": 0,
    "ledger_entries_count": 36,
    "default_inventory_source": {
        "code": "default",
        "name": "Al-Miftah Main Hub",
        "country": "SA",
        "state": "Riyadh",
        "city": "Riyadh",
        "status": 1
    }
}
```

* **خلو السجلات من التزييف:** صفر سجلات تحمل البادئة `AE-LIVE-%` أو قيم UUID.
* **عزل المستودع `default`:** مستودع `default` مخصص حصراً كمصدر لعنوان الشحن السعودي في إدارة المفاتيح لدى البوابة، ولا يُستخدم في تخصيصات المخزون المحلي أو التسليم.
* **سلامة القيود:** صفر حركات مخزنية (`inventory_movements = 0`) مرتبطة بأي محاولة إرسال فاشلة.

---

## 4. أدلة مسار الإنشاء غير المدفوع والمزامنة الموجهة (Execution & Sync Path)

### 4.1 بوابة الإنشاء غير المدفوع (`AliExpressOrderSubmissionGateway::submitUnpaid`):
1. **العميل التشغيلي:** يستدعي `app(AliExpressApiClient::class)` الخاص بوحدة V1 الناجحة.
2. **العنوان التشغيلي:** يحل العنوان من `inventory_sources.code = default`.
3. **التسعير والشحن:** يستند إلى `sku_attr` الحية وخدمة الشحن المفحوصة في الـ Preflight.
4. **عدم الدفع:** لا يتضمن أي معامل دفع آلي (`try_to_pay` غير مفعّل)، ويُنشئ الطلب غير مدفوع في AliExpress.
5. **معرف الارتباط:** يُمرر `out_order_id` كـ Idempotency Key فقط ولا يُخزن كـ `external_order_id`.
6. **معيار النجاح الصارم:** يشترط غياب أخطاء الـ Envelope (`code == 0` أو `result_code == 200`) وتحقق النجاح التجاري ووجود معرف رقمي رسمي (`is_numeric($orderId)`).

### 4.2 المزامنة الموجهة البديلة (`Targeted Direct Sync`):
- المسار التشغيلي:
  $$\text{Official Numeric ID} \longrightarrow \text{AliExpressOrderGateway::getOrder(id)} \longrightarrow \text{AliExpressPollingService::syncOrder}$$
- **رفض المعرفات الباطلة:** يرفض صراحة أي معرف فارغ أو غير رقمي أو اصطناعي.
- **تدرج الحالات الرتيب (Monotonic Progression):** يمنع تراجع الحالة في حال وصول استجابة قديمة.
- **تحرير التخصيصات عند الإلغاء:** فور تحول الطلب إلى `cancelled` أو `closed`، يتم تحرير التخصيصات المعلقة (`ProcurementDemandAllocation` تتحول إلى `cancelled` مع `qty_allocated = 0` و `qty_cancelled = qty_allocated`) بصفر حركات مخزنية أو قيود مالية.

---

## 5. نتائج الاختبارات البرمجية الفعلية (Test Suite Results)

### 5.1 اختبارات الجاهزية التشغيلية الدنيا (Minimum Operational Suite):
```text
PASS [1/4]: Gateway Rejects Fallback & Synthetic IDs on Commercial Failure
PASS [2/4]: Gateway Requires Official 16-Digit Numeric ID for Success
PASS [3/4]: Targeted Sync Rejects Non-Numeric IDs and Executes State Transitions
PASS [4/4]: Submit Service Enforces DB Transaction and Locks Supplier PO
```
* **النتيجة:** 4 اختبارات نفذت بنجاح كامل (0 فشل).

### 5.2 اختبارات استهلاك الـ Webhook ودورة الحياة المعزولة (Feature Suite):
* **النتيجة:** 13 اختباراً شملت 34 تأكيداً (34 Assertions) واجتازت جميعها بنسبة 100% (0 فشل).

---

## 6. خطة التراجع السريعة (Rollback Plan - وثائقية فقط)

1. الكود التشغيلي محمي بـ Git Commits معزولة.
2. قاعدة البيانات سليمة ولم يطرأ عليها أي تعديل يدوي أو تشويه هيكلي.
3. التراجع - إن لزم - يتم بالرجوع إلى الـ Commit السابق دون الحاجة لاستعادة النسخ الاحتياطية.

---

## 7. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  MINIMUM_OPERATIONAL_PATH_READY_FOR_FRESH_PREFLIGHT
======================================================================
```

> [!IMPORTANT]
> تم إثبات جميع متطلبات الـ P0 لمسار الحد الأدنى التشغيلي بنجاح 100%. تم التوقف الفوري والمطلق عند إصدار هذا التقرير. لم يتم إنشاء أي طلب شراء أو طلب AliExpress حي، لم يُطلب أي دفع، ولم يُجرَ أي إلغاء. النظام جاهز فوراً للأمر التالي بتجديد الـ Preflight وعرض الموافقة الحية.
