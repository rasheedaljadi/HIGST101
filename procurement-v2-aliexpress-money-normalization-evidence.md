# وثيقة إثبات وتطبيع المبالغ المالية ورسوم الشحن لبوابة AliExpress (Money Normalization Evidence)

**تاريخ الإثبات:** 2026-08-23 00:31:00 +03:00  
**الفئة المستهدفة:** `Webkul\Procurement\Support\AliExpressMoneyNormalizer`  
**Commit SHA:** `4c3931539e761842d9d3cae2537ce0f131b544f9`

---

## 1. تحليل حقول المبالغ في استجابة AliExpress (`ds.freight.query`)

توثق واجهة برمجة تطبيقات AliExpress (`aliexpress.ds.freight.query`) خيارات الشحن بمجموعة حقول مالية مختلفة تبعاً لنوع الخدمة:

1. **`shipping_fee_cent` / `amount_cent`**:
   - **الوحدة:** وحدات صغرى صحيحة (Minor Units / Cents).
   - **مثال:** قيمة `1250` تمثل `$12.50` دولار.
   - **المخاطرة السابقة:** معاملتها كـ Float مباشر كان يؤدي لخطر ضرب أو قسمة خاطئة بمقدار 100×.
2. **`shipping_fee` / `shipping_fee_amount` / `freight`**:
   - **الوحدة:** مبالغ قياسية بنقاط عشرية (Standard Decimal Float/String).
   - **مثال:** قيمة `"12.50"` أو `12.5`.
3. **`is_free`**:
   - **الوحدة:** قيمة منطقية (Boolean).
   - **مثال:** `true` تمثل شحناً مجانياً بقيمة `0.00` دولار.

---

## 2. المنطق المالي الموحد المعتمد في `AliExpressMoneyNormalizer`

تم بناء مطبّع مالي صارم يرفض التقريب العشوائي أو التخمين، ويعتمد القواعد التالية:

$$\text{Minor Units (Integer)} = 
\begin{cases} 
\text{round}(\text{raw}) & \text{إذا كان الحقل } \texttt{shipping\_fee\_cent} \text{ أو } \texttt{amount\_cent} \\
\text{round}(\text{raw} \times 100) & \text{إذا كان الحقل } \texttt{shipping\_fee} \text{ أو } \texttt{freight} \\
0 & \text{إذا كان الحقل } \texttt{is\_free} = \text{true}
\end{cases}$$

$$\text{Formatted Decimal (String)} = \text{number\_format}\left(\frac{\text{Minor Units}}{100}, 2, '.', ''\right)$$

---

## 3. مصفوفة الإثبات التجريبي للفئات المالية (Normalization Test Matrix)

| الحقل الوارد من API | القيمة الخام (Raw) | الحقل المكتشف | الوحدة المعتمدة | القيمة الصغرى (Minor Cents) | القيمة العشرية المنسقة | الحالة |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: |
| `shipping_fee_cent` | `1250` | `shipping_fee_cent` | `minor_cents` | `1250` | `"12.50"` | **PASS** |
| `amount_cent` | `350` | `amount_cent` | `minor_cents` | `350` | `"3.50"` | **PASS** |
| `shipping_fee` | `"12.50"` | `shipping_fee` | `decimal_standard` | `1250` | `"12.50"` | **PASS** |
| `freight` | `4.25` | `freight` | `decimal_standard` | `425` | `"4.25"` | **PASS** |
| `is_free` | `true` | `is_free` | `boolean_free` | `0` | `"0.00"` | **PASS** |
| *(حقل مفقود/غير معرف)* | `null` | `none` | `unknown` | `0` | `"0.00"` | **REJECTED (`PRICE_OR_FREIGHT_AMOUNT_AMBIGUOUS`)** |

---

## 4. هيكل البيانات المعتمد في `AliExpressOrderPreflight`

```php
public readonly ?int $shippingCostMinor;      // e.g. 1250
public readonly ?string $shippingCostFormatted; // e.g. "12.50"
public readonly array $moneyEvidence;          // Full audit dictionary
```

* **الضمانة التشغيلية:** لا يتم تخزين أرقام الـ Float كحقيقة مالية في قاعدة البيانات أو استخراج عروض الأسعار منها، مما يقضي تماماً على أي خطأ في التسعير بمقدار 100× أو أخطاء التقريب العشري.
