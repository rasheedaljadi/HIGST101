# تقرير التنفيذ المعماري والتشغيلي: إعادة بناء وحدة أوامر شراء هايست المجمّعة — الإصدار الثاني (V2)
**Architectural & Operational Implementation Report: Hayest Aggregated Purchase Orders & Procurement Module V2**

---

## 1. الملخص التنفيذي والقرار المعماري (Executive Decision & Architecture)

تم تنفيذ قرار القيادة الملزم ببناء وحدة شراء وتوريد مجمعة **Procurement V2** مستقلة تمامًا تحت حزمة `Webkul\Procurement`، مع عزل السجل القديم (V1) عزلًا صارمًا وحمايته كمرجع تاريخي للقراءة فقط. تم التخلص نهائيًا من نموذج `1:1` بين طلب العميل وأمر الشراء، واستبداله بنموذج تجميعي مرن يربط طلبات الشراء المؤهلة عبر طبقات الاحتياج (`ProcurementDemand`)، ودفعات التجميع (`ProcurementBatch`)، وأوامر المورد المستقلة (`SupplierPurchaseOrder`)، وتخصيصات الكميات الدقيقة (`ProcurementDemandAllocation`).

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                          دورة حياة التوريد V2                                │
└─────────────────────────────────────────────────────────────────────────────┘
  [طلب العميل المؤكد / COD المقبول]
                 │
                 ▼
  [ProcurementEligibilityService]
        │
        ├─► منتج داخلي ──► فحص مخزون (hayest_central) ──► عجز ──► [استثناء داخلي (لا يرسل لعلي إكسبرس)]
        │
        └─► منتج مستورد ──► استهلاك مخزون (hayest_dropship_ye) أولاً
                                 │
                                 ▼
                     حساب كمية العجز الخارجي
                                 │
                                 ▼
                    [ProcurementDemand: open_for_batching]
                                 │
                                 ▼
              [ProcurementBatchService: تجميع 1 إلى 100+ طلب]
                                 │
                                 ▼
                     تقسيم الدفعة حسب متجر المورد
                                 │
                                 ▼
                 [SupplierPurchaseOrder & Allocations]
                                 │
                                 ▼
             اعتماد إداري ──► [ProcurementSubmitService]
                                 │
                                 ▼
                   [ExternalPlatformOrder: WAIT_BUYER_PAY]
                                 │
                                 ▼
        إقرار الدفع اليدوي ──► [ProcurementManualPaymentService]
                                 │
                                 ▼
              [AliExpressPollingService: مزامنة دورية]
                                 │
                                 ▼
      فحص فرق التكلفة (Variance) ──► [ProcurementVarianceApprovalService]
                                 │
                                 ▼
                شحن المورد ──► استلام وتوزيع البنود
                                 │
                                 ▼
             [ProcurementInboundReceiptService: hayest_dropship_sa]
```

---

## 2. هيكل الجداول والنماذج المحدثة (Database Schemas & Models)

تم إنشاء 10 ترحيلات (Migrations) جديدة بروابط وفهارس محكمة ومفاتيح أجنبية متكاملة:

| # | اسم الجدول (Table Name) | النموذج (Model) | الغرض المعماري |
|---|---|---|---|
| 1 | `procurement_demands` | `ProcurementDemand` | تسجيل احتياج التوريد الخارجي المحسوب بدقة لكل بند طلب عميل |
| 2 | `procurement_batches` | `ProcurementBatch` | رأس دفعة التجميع الإدارية (تجميع 1 إلى 100+ طلب) |
| 3 | `procurement_batch_demands` | `ProcurementBatchDemand` | جدول وسيط يربط الدفعة بطلبات التوريد المجمعة ويمنع تكرار التخصيص |
| 4 | `supplier_purchase_orders` | `SupplierPurchaseOrder` | أمر الشراء الموجه لمتجر مورد محدد على منصة علي إكسبرس |
| 5 | `supplier_purchase_order_items` | `SupplierPurchaseOrderItem` | تجميع كميات الـ SKU الواحد لمتجر المورد في بند أمر شراء موحد |
| 6 | `procurement_demand_allocations` | `ProcurementDemandAllocation` | الرابط الكمي الصارم بين بند أمر الشراء وبند طلب العميل الأصلي |
| 7 | `external_platform_orders` | `ExternalPlatformOrder` | تتبع أمر منصة علي إكسبرس الخارجي، الحالات، وأرقام التتبع |
| 8 | `procurement_cost_snapshots` | `ProcurementCostSnapshot` | لقطات التكلفة غير القابلة للتعديل أو الحذف (Immutable Ledger) |
| 9 | `procurement_manual_payment_confirmations` | `ProcurementManualPaymentConfirmation` | توثيق إقرارات الدفع اليدوي الإدارية المنفذة في كونسول علي إكسبرس |
| 10 | `procurement_audit_logs` | `ProcurementAuditLog` | سجل تدقيق غير قابل للتعديل (Append-Only) لجميع الحركات |

---

## 3. قواعد العمل والخدمات الميدانية (Domain Services & Invariants)

### أ. المنتجات الداخلية (Internal Products)
- تُفحص في مستودع هايست المركزي (`hayest_central` / `hayest_internal_ye`).
- في حال وجود عجز، يُسجل استثناء تدقيق فوري `internal_stock_exception`.
- **ممنوع تمامًا** إنشاء أي طلب توريد خارجي أو دفعة أو أمر علي إكسبرس للمنتجات الداخلية.

### ب. المنتجات المستوردة وتغطية المخزون المحلي أولاً (Local Stock First)
- يُفحص المخزون المملوك في مستودع الدروب شيبينغ اليمني (`hayest_dropship_ye`).
- تُغطى الكمية المتوفرة محليًا بالكامل أولاً: `qty_covered_by_local = min(available, requested)`.
- يُحسب العجز الفعلي فقط: `qty_required_external = requested - qty_covered_by_local`.
- يُنشأ سجل `ProcurementDemand` للعجز الفعلي فقط بالحالة `open_for_batching`.

### ج. التجميع والتقسيم الذكي (Aggregation & Store Splitting)
- تجمع الطلبات ذات الحساب المزود نفسه، والعملة (USD حصراً)، ووجهة الشحن الموحدة.
- تُقفل السجلات بواسطة `SELECT ... FOR UPDATE` لمنع أي Race Condition أو تكرار تجميع للبند نفسه.
- تُقسم الدفعة تلقائيًا حسب `supplier_store_id` إلى أوامر شراء مورد مستقلة (`SupplierPurchaseOrder`).
- تُدمج كميات الـ SKU المتطابق في بند أمر شراء موحد مع توثيق التخصيصات الفردية لكل عميل (`ProcurementDemandAllocation`).

### د. حماية الأسعار والموافقة على الفروقات (Cost Protection & Variance Approval)
- لقطات التكلفة `ProcurementCostSnapshot` غير قابلة للتعديل (Immutable) وتمنع الحذف أو التعديل برمجيًا على مستوى الـ Model Boot.
- التوريد حصري بعملة الدولار الأمريكي (USD).
- في حال وجود أي فرق بين التكلفة المتوقعة عند التجميع والتكلفة الفعلية بعد الدفع، يتوقف أمر الشراء فوراً عند حالة `cost_variance_review` ولا يستأنف دورته إلا بموافقة مستخدم يملك صلاحية `dropshipping.procurement_v2.variance_approve`.

### هـ. الاستلام المستودعي والتوزيع الدقيق (Inbound Receiving & Stock Updates)
- عند وصول الشحنة إلى مركز الفرز السعودي (`hayest_dropship_sa`)، تُسجل الكميات السليمة والتالفة والمفقودة.
- المخزون القابل للبيع يزيد **فقط بالكمية السليمة** (`qty_received_good`).
- تُحدث تخصيصات طلبات العملاء بنسب الاستلام السليم بدقة متناهية.

---

## 4. تصحيح المعالجة المحاسبية لإيرادات الدفع عند الاستلام (COD Revenue Recognition Fix)

تم تعديل `FinancialSettlementService::settleOrderShipmentCOD()` ليتوافق مع المعايير المحاسبية الصارمة:
1. **عند شحن طلب الدفع عند الاستلام (COD Shipment):**
   - مدين: ذمم شركة الشحن والتوصيل (`1210` - Courier Receivables).
   - دائن: إيرادات COD غير مكتسبة قيد الشحن (`2210` - Unearned COD Revenue In-Transit).
   - **لا يُسجل أي إيراد محقق (`4010`) في هذه المرحلة.**
2. **عند توثيق التسليم والتحصيل الفعلي (`cod_collected_at`):**
   - استدعاء `FinancialSettlementService::settleOrderCODCollection()`.
   - مدين: إيرادات COD غير مكتسبة قيد الشحن (`2210`).
   - دائن: إيرادات المبيعات المحققة (`4010` - Realized Sales Revenue).

---

## 5. واجهات الإدارة والصلاحيات (Admin UI, DataGrids & ACL)

تم بناء الواجهات الكاملة ضمن لوحة تحكم الإدارة:
- **طلبات التوريد المؤهلة (`demands`):** استعراض العجز والكميات المتوفرة محليًا والمجمعة.
- **دفعات التجميع (`batches`):** إنشاء الدفعات، المعاينة غير المحدثة للبيانات، الاعتماد، الرفض، والإرسال.
- **أوامر المورد (`supplier-orders`):** متابعة بنود المورد، التخصيصات، شاشات استلام الشحنات وتوزيع البنود.
- **أوامر المنصة (`platform-orders`):** تتبع أرقام علي إكسبرس وحالات الشحن والمزامنة اليدوية والآلية.
- **إقرارات الدفع اليدوي (`manual-payments`):** توثيق المراجع البنكية ومبالغ السداد دون حفظ أي بيانات دفع سرية.
- **فروق التكلفة والموافقات (`cost-variances`):** شاشة مخصصة لمدراء العمليات لاعتماد فروق الأسعار أو رفضها.
- **المصالحات والاستثناءات (`exceptions`):** تتبع عجز المخزون الداخلي وفروقات الشحن والاستلام.
- **التقارير والربحية (`reports`):** لوحة مؤشرات الأداء المالي، التكاليف المتوقعة والفعلية، وهوامش الربح، وأرصدة COD غير المحصلة.

### مصفوفة الصلاحيات (ACL Permissions Matrix)
| الصلاحية (Permission Key) | الوصف |
|---|---|
| `dropshipping.procurement_v2.view` | استعراض واجهات التوريد V2 |
| `dropshipping.procurement_v2.batch_create` | تجميع الطلبات وإنشاء الدفعات |
| `dropshipping.procurement_v2.batch_approve` | مراجعة واعتماد دفعات التجميع |
| `dropshipping.procurement_v2.submit` | إرسال الدفعات إلى علي إكسبرس |
| `dropshipping.procurement_v2.payment_confirm` | تسجيل إقرار الدفع اليدوي |
| `dropshipping.procurement_v2.cost_view` | استعراض التكاليف وهوامش الأرباح (محجوبة تلقائياً عن غير المخولين) |
| `dropshipping.procurement_v2.variance_approve` | اعتماد فروق التكلفة واستئناف دورة الشراء |
| `dropshipping.procurement_v2.exception_handle` | معالجة استثناءات التوريد والمخزون |
| `dropshipping.procurement_v2.reports_view` | استعراض التقارير المالية والتشغيلية |

### دعم اللغات (21 Locales 100% Complete)
تم إنشاء ملفات الترجمة لجميع لغات Bagisto الـ 21 مع التحقق بنجاح من `php artisan bagisto:translations:check`:
`ar`, `bn`, `ca`, `de`, `en`, `es`, `fa`, `fr`, `he`, `hi_IN`, `id`, `it`, `ja`, `nl`, `pl`, `pt_BR`, `ru`, `sin`, `tr`, `uk`, `zh_CN`.

---

## 6. نتائج الاختبارات الآلية (Automated Verification Results)

تم تنفيذ وتشغيل حزمة الاختبارات الآلية الشاملة بنجاح بنسبة **100% (17/17 Passed)**:

```
   PASS  Webkul\Procurement\Tests\Feature\ProcurementV2RebuildFullWorkflowTest
  ✓ 1 internal product order never generates external demand or po                                               2.83s  
  ✓ 2 imported product with local ye stock covers local first and demands deficit only                           0.57s  
  ✓ 3 external imported order eligible demand requires order confirmation or accepted cod                        0.57s  
  ✓ 4 mixed order splits internal items locally and external items to v2 demands                                 0.64s  
  ✓ 5 hundred demands same store usd destination aggregated into single batch and po                             5.48s  
  ✓ 6 multi store batch splits into distinct supplier pos and platform orders                                    0.67s  
  ✓ 7 concurrent batching race condition prevents double demand allocation                                       0.61s  
  ✓ 8 allocation sum invariants strictly enforced for demands and po items                                       0.73s  
  ✓ 9 price change or non usd currency diverts to review required                                                0.81s  
  ✓ 10 awaiting manual payment records declaration and polling advances state                                    0.85s  
  ✓ 11 idempotent polling and out of order status events never regress state                                     0.70s  
  ✓ 12 cost variance review triggered on discrepancy with immutable snapshot and approval                        0.85s  
  ✓ 13 partial receipt damage missing increments good quantity only                                              0.71s  
  ✓ 14 handoff strictly rejected from invalid sources or unreceived imported stock                               0.52s  
  ✓ 15 cod shipment does not recognize realized revenue until cod collected at                                   0.81s  
  ✓ 16 fresh install upgrade path and clean rollback of all v2 migrations                                        0.49s  
  ✓ 17 acl permissions strictly enforce cost view payment confirm and variance approval                          0.52s  

  Tests:    17 passed (64 assertions)
  Duration: 19.29s
```

---

## 7. فحص التنسيق والامتثال الكودي (Code Style Compliance)

تم تشغيل Laravel Pint بنجاح:
```
{"tool":"pint","result":"passed"}
```
جميع الملفات تتوافق 100% مع معايير PSR-12 وLaravel/Bagisto Coding Standards.
