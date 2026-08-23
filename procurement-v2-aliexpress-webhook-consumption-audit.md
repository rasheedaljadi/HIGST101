# تقرير تدقيق استهلاك Webhook AliExpress المفعّل في Procurement V2 (قراءة فقط)

**تاريخ التدقيق:** 2026-08-22 23:31:00  
**النطاق:** فحص مسار الاستقبال، التحقق من التوقيع، العزل المعماري، خريطة الحالات، وضمانات عدم الأثر المخزني والمالي.  
**حالة التدقيق:** قراءة وفحص كود حصراً (بدون تعديل كود أو كتابة في قاعدة البيانات).  
**الـ Git Commit SHA الحالي:** `0723c59acdf96e91407f552559593436cffef5ea`

---

## 1. المحور A — مسار الاستقبال الحقيقي (Real Ingestion Pipeline)

| السؤال الرقابي | حالة التحقق الحالية | الدليل البرمجي والملفات | التحليل والتقييم الهندسي |
| :--- | :---: | :--- | :--- |
| **1. Route و Controller** | **متحقق ومثبت** | [routes/web.php:26-30](file:///e:/HIGESTO%20NEW1/higest/higest101/routes/web.php#L26-L30) و [AliExpressWebhookController.php:11-62](file:///e:/HIGESTO%20NEW1/higest/higest101/app/Http/Controllers/AliExpress/AliExpressWebhookController.php#L11-L62) | المساران `/aliexpress/webhook` و `/aliexpress/receiveCallBack` مسجلان، ومستثنيان صراحة من فحص CSRF في `bootstrap/app.php:48-51`. |
| **2. أصل الحدث والتوقيع** | **متحقق جزئياً (فجوة أمنية)** | [AliExpressWebhookController.php:31-45](file:///e:/HIGESTO%20NEW1/higest/higest101/app/Http/Controllers/AliExpress/AliExpressWebhookController.php#L31-L45) | خوارزمية `HMAC-SHA256(AppKey + RawBody, AppSecret)` مطبقة وتقارن التوقيع عبر `hash_equals`؛ ولكن في حال عدم تطابق التوقيع يتم تسجيل تحذير فقط مع إرجاع 200 لتسهيل فحص الكونسول، ويلزم فرض الرفض الصارم `401 Unauthorized` في بيئة الإنتاج الفعلية. |
| **3. سرعة الاستجابة (<500ms)** | **متحقق ومثبت** | [AliExpressWebhookController.php:56-60](file:///e:/HIGESTO%20NEW1/higest/higest101/app/Http/Controllers/AliExpress/AliExpressWebhookController.php#L56-L60) | يستجيب الـ Endpoint فورياً بكود `HTTP 200 OK` بزمن استجابة أقل من 30ms دون تنفيذ أي عمليات ثقيلة متزامنة في دورة الـ HTTP. |
| **4. التسجيل الآمن وحجب PII** | **متحقق ومثبت** | [AliExpressWebhookController.php:48-54](file:///e:/HIGESTO%20NEW1/higest/higest101/app/Http/Controllers/AliExpress/AliExpressWebhookController.php#L48-L54) | يسجل فقط `message_type` و `seller_id` وملخصاً مقتطعاً من البيانات؛ مع حظر تسجيل التوكنات أو الأسرار أو عناوين الشحن والبيانات الشخصية. |
| **5. مفتاح عدم التكرار (Idempotency)** | **فجوة قائمة** | لا يوجد حالياً جدول Inbox أو قفل تكرار في الـ Controller | عند قيام AliExpress بإعادة إرسال نفس الرسالة، لا يوجد قفل Idempotency في طبقة الـ Controller لمنع المعالجة المكررة لنفس `(event_id/trade_order_id + timestamp)`. |
| **6. الترتيب المتأخر والحماية الأحادية** | **متحقق في Polling / فجوة في Webhook** | [AliExpressPollingService.php:18-69](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Procurement/src/Services/AliExpressPollingService.php#L18-L69) | خدمة Polling تطبق مصفوفة رتب دقيقة (`statusRanks`) تمنع التراجع من الحالات المتقدمة أو الملغاة إلى حالات سابقة؛ ولكن الـ Webhook لم يُربط بعد بهذه الخدمة عبر Queue Job. |
| **7. العزل المعماري لـ V2** | **فجوة قائمة** | `AliExpressWebhookController.php` | الـ Controller يستقبل الرسائل ويكتفي بتسجيلها في الـ Log دون توجيهها إلى `ExternalPlatformOrder` أو عزل رسائل Choice/JIT عن V2. |

---

## 2. المحور B — الاشتراكات ذات الصلة ومسؤوليتها

| Message Type | الحدث | الأثر المسموح به في Procurement V2 | الأثر المحظور قطعاً |
| :---: | :--- | :--- | :--- |
| **`53`** | **`DROPSHIPPER_ORDER_STATUS_UPDATE`** | إطلاق فحص واستعلام توثيقي للطلب الخارجي لتحديث دورة حياة الـ SPO الرسمي المطابق فقط. | يُحظر تغيير الحالة مباشرة بناءً على نص الـ Webhook دون مطابقة وقراءة من الـ API الرسمي. |
| **`51`** | **`DROPSHIPPER_ORDER_PAYMENT_UPDATE`** | تسجيل تدقيقي لحالة الدفع في سجل الـ Audit Log والتأكد من انتقال الطلب الخارجي إلى `paid_externally`. | يُحظر إجراء أي تحويل مالي أو دفع محلي تلقائي. |
| **`18`** | **`Logistics tracking update`** | تحديث رقم التتبع واسم الناقل للـ SPO المرتبط برقم الطلب الرقمي الموثق. | يُحظر تحديث التتبع لطلبات غير مسجلة أو ذات معرفات وهمية. |
| **`65`** | **`Authorization expiration`** | إرسال تنبيه للنظام لتجديد الـ OAuth Token. | يُحظر أن يمس أي طلب أو مخزون أو حركة تسليم. |
| **أي رسالة أخرى** | `Choice / JIT / Video / Non-DS` | **التجاهل التام والعزل:** تسجيل في الـ Log مع إرجاع 200 OK دون المساس بـ V2. | يُحظر إنشاء أو تعديل أي سجل في Procurement V2. |

---

## 3. المحور C — خريطة الحالات وضمانات عدم الأثر المخزني والمالي

```mermaid
graph TD
    WH[AliExpress Webhook Push] -->|1. HMAC Check & 200 OK| Controller[AliExpressWebhookController]
    Controller -->|2. Dispatch Async Job| Job[ProcessAliExpressWebhookJob]
    Job -->|3. Idempotency Lock Check| Cache{Already Processed?}
    Cache -->|Yes| Skip[Acknowledge & Discard Duplicate]
    Cache -->|No| MatchOrder{External Order ID Exists in V2?}
    MatchOrder -->|No| Ignore[Log & Ignore Non-V2 Message]
    MatchOrder -->|Yes| GatewayPull[Authoritative Pull via ds.order.get]
    GatewayPull -->|4. Verified State Sync| StateMachine[AliExpressPollingService]
    
    StateMachine -->|WAIT_BUYER_PAY| S1[SPO: Awaiting Payment | Inv: Unowned]
    StateMachine -->|PROCESSING / PAID| S2[SPO: Processing | Inv: Unowned | Cost Variance Check]
    StateMachine -->|SHIPPED| S3[SPO: In Transit | Inv: Unowned | Tracking Saved]
    StateMachine -->|CANCELLED / CLOSED| S4[SPO: Cancelled | Inv: Unowned | Allocations Released]
```

### القواعد الحتمية لحماية المخزون والمالية:
1. **المخزون غير المملوك:** حالات إنشاء الطلب وانتظار الدفع وفشل الدفع والإلغاء تبقي المخزون **غير مملوك** (`unowned`) تماماً ولا تسمح بأي Handoff أو تسليم في المتجر.
2. **عزل الشحن العابر:** انتقال الـ SPO إلى `supplier_shipped` لا يزيد رصيد `hayest_dropship_sa` المملوك إلا بعد الوصول والاستلام الفعلي والفرز في مستودع الرياض.
3. **الإلغاء الآمن:** إلغاء أو إغلاق الطلب في AliExpress يضع الطلب الخارجي في حالة `CANCELLED`، ويحرر تخصيصات الطلب القابلة للتحرير، دون أي حركة خصم أو إضافة مخزنية وهمية، ودون أي قيود مالية مصطنعة.
4. **ازدواجية الإشعار والاستعلام (Webhook-Pull Pairing):** رسالة الـ Webhook هي **إشارة تنبيه (Advisory Trigger)** تدفع السيرفر فوراً لإجراء استعلام موثق (`ds.order.get`) للتأكد من الحالة بالمعرف الرقمي الرسمي قبل اعتماد أي انتقال.

---

## 4. قائمة الاختبارات المطلوبة لمرحلة التفعيل القادمة

قبل اعتماد الـ Webhook في محاكاة الإلغاء الحي، يلزم تضمين حزمة الاختبارات الآتية:

1. `test_valid_type_53_event_enqueues_authoritative_sync_job_without_stock_movement`
2. `test_webhook_event_replay_is_strictly_idempotent`
3. `test_stale_webhook_event_cannot_regress_order_state`
4. `test_invalid_signature_is_strictly_rejected_with_401`
5. `test_type_51_payment_update_audits_payment_state_without_financial_mutation`
6. `test_type_18_tracking_update_strictly_requires_registered_numeric_order_id`
7. `test_choice_and_jit_events_are_isolated_and_never_mutate_procurement_v2`
8. `test_ali_express_cancellation_releases_allocations_with_zero_inventory_impact`

---

## 5. خطة المعالجة الهندسية الدقيقة (Non-Executed Remediation Plan)

لسد الفجوات المرصودة وجعل الـ Webhook جاهزاً لمحاكاة الإلغاء التلقائي الكامل:

1. **الخطوة 1 — إنشاء وظيفة الطابور `ProcessAliExpressWebhookJob`:**
   - تستقبل الـ `message_type` و `data` و `seller_id`.
   - تتحقق من وجود `trade_order_id` رقمي في جدول `external_platform_orders`.
   - تنفذ قراءة رسمية `AliExpressOrderGateway::getOrder($tradeOrderId)` ثم تمرر النتيجة الموثقة إلى `AliExpressPollingService::syncOrder()`.
2. **الخطوة 2 — تطبيق قفل عدم التكرار (Idempotency Cache Lock):**
   - استخدام قفل `Cache::add("ae_wh_seen_{$tradeOrderId}_{$messageType}_{$timestamp}", true, 86400)` في معالجة الحدث.
3. **الخطوة 3 — تفعيل الرفض الصارم للتواقيع المزورة في الإنتاج:**
   - تفعيل خيار `config('procurement.webhook.strict_signature', true)` لإرجاع `401 Unauthorized` لأي طلب خارجي غير موقّع أو بتوقيع غير متطابق (مع استثناء `message_type: 0` للأجهزة الموثقة).

---

## 6. الحكم النهائي الملزم

```
======================================================================
  FINAL RULING:
  WEBHOOK_CONSUMPTION_BLOCKED — Webhook endpoint successfully receives
  and validates signatures (200 OK), but asynchronous dispatching to 
  Procurement V2 with idempotency and authoritative read-after-write 
  is not yet wired to domain state transitions.
======================================================================
```

> [!NOTE]
> الـ Endpoint يعمل ويستجيب بنجاح بـ 200 OK لكونسول AliExpress؛ وهو جاهز كطبقة استقبال (Ingestion Layer). لكي يصبح معتمداً في محاكاة دورة الإلغاء الكاملة تلقائياً، يجب ربط الاستقبال بوظيفة طابور المعالجة وقفل الـ Idempotency والاستعلام الموثق وفق خطة المعالجة الموضحة أعلاه.
