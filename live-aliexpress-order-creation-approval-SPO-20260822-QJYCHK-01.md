# تقرير طلب موافقة اعتماد أمر شراء خارجي حي (غير مدفوع) — AliExpress

## 1. بيانات أمر الشراء والتتبع الداخلي (Traceability & Correlation)

```yaml
Supplier PO Number: "SPO-20260822-QJYCHK-01"
Supplier PO ID: 1
Batch Number: "BATCH-20260822-CXRJWW" (Batch ID: 1)
Demand Number: "DEMAND-1" (Demand ID: 1)
Customer Order Reference: "STG-LIVE-AE-20260822_045404" (Order ID: 287, Item ID: 162)
Correlation / Idempotency Key: "IDEMP-SPO-SPO-20260822-QJYCHK-01"
Status: "ready_to_submit"
```

---

## 2. تفاصيل المورد والمنتجات والكميات (Supplier & Product Details)

| الحقل | القيمة المعتمدة |
|---|---|
| **متجر المورد (AliExpress Store)** | `Official Men Polo Store` (Seller ID: `4586371333`) |
| **اسم المنتج** | قميص بولو رجال الأعمال (Variant 281 267) |
| **معرّف المنتج في علي إكسبرس (Item ID)** | `1005008248073626` |
| **معرّف المتغير (AliExpress SKU ID)** | `12000044371414236` |
| **رمز SKU الداخلي** | `ae-1005008248073626-variant-281-267` |
| **الكمية المطلوبة (عجز حقيقي)** | `1` قطعة |
| **المخزون المملوع المتاح بصنعاء** | `0` (لا يوجد مخزون محلي يغطي الطلب) |

---

## 3. محطة الاستلام والشحن المعتمدة (Fulfillment & Shipping Station)

- **كود المحطة المستهدفة:** `hayest_dropship_sa`
- **اسم المحطة:** محطة توريد وتجميع الرياض (المملكة العربية السعودية)
- **المدينة والدولة:** الرياض، المملكة العربية السعودية (`Riyadh, SA - 11564`)
- **حالة اكتمال العنوان:** ✅ **مكتمل ومطابق لبيانات الشحن المسجلة بحساب AliExpress المعتمد**.

---

## 4. تدقيق التكلفة والـ Snapshot المالي (Financial Cost Snapshot)

| البيان المالي | المبلغ بالدولار (USD) |
|---|---|
| **تكلفة السلعة (Items Subtotal)** | `$10.19` USD |
| **تكلفة الشحن المتوقعة (Shipping)** | `$0.00` USD (Free AliExpress Standard Shipping) |
| **الرسوم والضرائب (Taxes & Fees)** | `$0.00` USD |
| **المبلغ الإجمالي المتوقع (Total Expected)** | **`$10.19` USD** |
| **Snapshot ID & Hash** | Snapshot #1 (`8f7d350b9cbe26d82b5b7ad15313f56c5b8fd5fe69e43105229a3e368624c668`) |

---

## 5. الأثر التشغيلي والالتزام المالي (Operational Impact & Commitment)

> [!IMPORTANT]
> **تنبيه وإقرار الالتزام:**
> عند إصدار الموافقة على هذا الطلب، سيتم استدعاء واجهة برمجة التطبيقات الرسمية `aliexpress.ds.order.create` لإنشاء **طلب حقيقي غير مدفوع (Unpaid Order)** داخل حساب AliExpress المرتبط بالبائع `4586371333`.
> - الطلب سيظهر في لوحة تحكم AliExpress الخاصة بحساب المتجر.
> - لن يتم خصم أي مبالغ مالية تلقائياً.
> - يصبح الطلب مستحقاً للسداد اليدوي من قبل الموظف المالي المفوض داخل AliExpress بعد اعتماد منفصل.

---

## 6. حالة البوابة الحالية

```
AWAITING USER APPROVAL FOR LIVE UNPAID ORDER CREATION
```
*(يتوقف النظام هنا بالكامل ولا يرسل أي استدعاء خارجي حتى استلام موافقة صريحة ومحددة على هذا الأمر في هذه المحادثة).*
