# Requirements Document

## وثيقة المتطلبات: جسر تنفيذ الطلبات (Order Fulfillment Bridge)

## Introduction

جسر تنفيذ الطلبات هو طبقة برمجية في متجر Bagisto 2.4.x (دروبشيبينغ) تربط **طلب العميل الداخلي** (Customer Order) بـ **أمر شراء خارجي لدى المورّد** (Purchase Order). عند تأكيد الدفع لطلب العميل، يُنشئ الجسر أمر شراء (أو أكثر) لدى مورّد خارجي (AliExpress هو أول مزوّد، لكن الطبقة مستقلة عن المزوّد)، ثم يتابع حالته ويعكسها على طلب العميل.

هذه الوثيقة مشتقّة من وثيقة التصميم المعتمدة (`design.md`) وتلتزم بقراراتها المعمارية (ADR-001…ADR-003) وخصائص الصحة (القسم 6.6). المصطلحات التقنية تبقى بالإنجليزية بينما الشرح بالعربية. تتّبع كل معايير القبول صيغة EARS.

**النطاق:** تحويل طلب عميل مدفوع إلى أمر/أوامر شراء، تعيين الحالات، منع التكرار الداخلي، إعادة المحاولة ومعالجة الفشل، متابعة حالة المورّد عبر Polling، وتجريد المزوّد، تصنيف الإجراءات، معمارية DataGrid والإشعارات، وسجل التدقيق والتحكم بالأداء والمزايا، وقواعد النسخ والتبعية الخلفية.

## Glossary

- **Bridge (الجسر):** الطبقة الكاملة المسؤولة عن الترجمة بين طلب العميل وأمر الشراء لدى المورّد؛ تعيش في حزمة Concord جديدة `Webkul\Fulfillment`.
- **Fulfillment_Service:** الخدمة المنسّقة التي تنفّذ التجميع ومنع التكرار والتنفيذ وتعيين الحالات.
- **Fulfillment_Provider:** أي صنف يُطبّق العقد البرمجي الموحد ويغلّف تكامل مورّد محدد.
- **Provider_Registry:** السجل الذي يحلّ رمز الموفر النصّي إلى صنف المزوّد المسجَّل.
- **Purchase_Order:** الكيان الذي يمثّل أمر شراء لدى مورّد؛ مرتبط بطلب عميل بعلاقة 1:N.
- **Purchase_Order_Item:** الكيان الذي يمثّل بند أمر شراء ويشير إلى بند طلب العميل من نظام المبيعات وإلى مُعرّف المنتج لدى المورّد.
- **Fulfillment_Attempt:** الكيان الذي يسجّل كل محاولة اتصال آلي بالـ API للمورد في قاعدة البيانات.
- **Fulfillment_Audit_Log:** الكيان الذي يسجّل تصرفات البشر والموظفين داخل لوحة الإدارة.
- **Fulfillment_Error_Type:** نوع الفشل المعين كـ (Strongly Typed Enumeration) يمثل نوع الفشل (PROVIDER_ERROR, NETWORK_ERROR, AUTH_ERROR, VALIDATION_ERROR, BUSINESS_RULE_ERROR, MANUAL_REVIEW).
- **Customer_Order:** طلب العميل الداخلي في نظام المبيعات.
- **Initiate_Fulfillment_Listener:** المستمع الذي يستمع لحدث تأكيد الفاتورة.
- **Create_Purchase_Order_Job:** المهمة التي تنفّذ إنشاء أمر الشراء لدى المورّد.
- **Poll_Supplier_Orders_Job:** المهمة المجدولة التي تزامن حالات أوامر الشراء غير النهائية.
- **Idempotency_Key:** مفتاح فريد يُخزَّن في قاعدة البيانات لمنع تكرار الإرسال للمورد.
- **Internal_Reference:** المرجع الداخلي الفريد المرسل للمورّد ويُستخدم في المصالحة.
- **Supplier_Signature:** مُعرّف المورّد/المتجر داخل المزوّد، مصدره سجلات استيراد المنتجات.
- **State_Dictionary:** قاموس حالات أمر الشراء الموحّد المستقل عن المزوّد.
- **Transient_Failure:** فشل عابر (شبكة/timeout/حد معدّل) قابل لإعادة المحاولة.
- **Permanent_Failure:** فشل دائم (بيانات غير صالحة/منتج غير متاح/رفض المورّد) غير قابل لإعادة المحاولة الآلية.
- **Feature_Flags:** رايات المزايا القابلة للتخصيص للتحكم بالإطلاق التدريجي للميزات.
- **Approval_Workflow:** مسار العمليات الحساسة التشغيلية التي تتطلب موافقة المدير قبل التنفيذ الفعلي (ميزة اختيارية للمؤسسات).

---

## Requirements

### Requirement 1: تجريد مزوّد التنفيذ وعزل الواجهات (Provider-Agnostic Fulfillment Layer)

**User Story:** بصفتي مطوّرًا للمنصّة، أريد أن يعتمد الجسر والواجهات على مسميات مجردة وعقد موحّد للمزوّدين بدل AliExpress مباشرة، لتجنب حدوث ارتباك وتغييرات كبيرة عند إضافة موردين جدد.

#### Acceptance Criteria

1. THE Bridge SHALL communicate with every Fulfillment_Provider exclusively through the common abstraction interface.
2. THE Bridge SHALL identify each Fulfillment_Provider by a non-empty, case-sensitive text `provider code` stored in the database.
3. WHEN the Fulfillment_Service needs a Fulfillment_Provider, THE Provider_Registry SHALL resolve the `provider code` to a provider class registered in the configuration files.
4. THE Admin UI layouts and grids SHALL display abstract labels like `External Order ID` and `Provider` (retrieved dynamically from the provider code) instead of hardcoded vendor-specific labels.
5. WHEN a Fulfillment_Provider receives a response or an error from its upstream provider, THE Fulfillment_Provider SHALL translate it into a normalized result or status data object.
6. THE Fulfillment_Provider SHALL NOT write to the Bridge database tables or to the Customer_Order.
7. IF the Provider_Registry cannot resolve a requested `provider code`, THEN THE Provider_Registry SHALL raise an error, and THE Fulfillment_Service SHALL NOT create a supplier order.

---

### Requirement 2: كيان أمر الشراء المنفصل والعلاقة 1:N (Separate Purchase Order Entity)

**User Story:** بصفتي مهندس نظام، أريد كيان `PurchaseOrder` مستقلًّا عن طلب العميل بعلاقة واحد-إلى-متعدّد، حتى تبقى البيانات المالية لطلب العميل معزولة عن بيانات المورّد وتُدعَم السلال متعددة الموردين.

#### Acceptance Criteria

1. THE Bridge SHALL represent supplier orders using the separate Purchase_Order entity and SHALL NOT store supplier identifiers, supplier signatures, or supplier product identifiers on the Customer_Order record.
2. THE Bridge SHALL associate one Customer_Order with zero or more Purchase_Order records through a one-to-many relationship.
3. WHEN the Fulfillment_Service plans purchase orders, THE Fulfillment_Service SHALL assign each order item to exactly one group identified by `(provider, supplier_signature)`.
4. WHEN the Fulfillment_Service has grouped the order items, THE Fulfillment_Service SHALL create exactly one Purchase_Order per group and assign all items of that group to that Purchase_Order.
5. THE Bridge SHALL derive each Supplier_Signature from the product import records linked to the product of the order item.
6. IF an order item has no linked supplier source, THEN THE Fulfillment_Service SHALL set the related Purchase_Order state to `needs_manual_review` and flag it for manual review.

---

### Requirement 3: سياسة تحفيز التنفيذ بعد الدفع (Post-Payment Trigger Policy)

**User Story:** بصفتي صاحب متجر، أريد ألّا يبدأ شراء البضاعة من المورّد إلا بعد تأكيد الدفع، حتى لا أشتري بضاعة لطلبات غير مدفوعة.

#### Acceptance Criteria

1. WHEN the invoice save event is dispatched for a Customer_Order that has at least one invoice, THE Initiate_Fulfillment_Listener SHALL invoke `FulfillmentService::planPurchaseOrders` for that Customer_Order exactly once.
2. IF a Customer_Order has zero invoices, THEN THE Bridge SHALL NOT create any Purchase_Order for that Customer_Order.
3. WHERE Cash-on-Delivery automatic invoice generation is enabled, THE Bridge SHALL treat the Cash-on-Delivery order as paid and SHALL invoke `FulfillmentService::planPurchaseOrders` through the invoice event.
4. WHERE Cash-on-Delivery automatic invoice generation is disabled, THE Bridge SHALL NOT invoke `FulfillmentService::planPurchaseOrders` until an invoice is manually generated for the Customer_Order.
5. THE Initiate_Fulfillment_Listener SHALL implement asynchronous queuing and SHALL dispatch fulfillment planning to the queue instead of running it inline.

---

### Requirement 4: منع التكرار الداخلي (Internal Idempotency)

**User Story:** بصفتي مسؤول عمليات، أريد ضمانًا داخليًا بعدم تكرار أوامر الشراء دون الاعتماد على دعم المورّد للـ idempotency، حتى لا يتكرّر الشراء عند إعادة تشغيل المهام أو تكرار الأحداث.

#### Acceptance Criteria

1. THE Bridge SHALL compute the Idempotency_Key as a deterministic hash of `order_id . '|' . provider . '|' . supplier_signature` for each supplier group.
2. THE Bridge SHALL enforce a unique database constraint on the `idempotency_key` column such that at most one Purchase_Order row can exist per Idempotency_Key value.
3. WHEN the Fulfillment_Service plans a purchase order for a supplier group, THE Fulfillment_Service SHALL use unique find-or-create logic on the Idempotency_Key so that a repeated trigger returns the existing Purchase_Order instead of creating a new one.
4. WHILE a Create_Purchase_Order_Job is executing a Purchase_Order, THE Fulfillment_Service SHALL hold an execution lock with a maximum lease duration of 600 seconds.
5. IF a Purchase_Order is already in state `submitted`, `shipped`, or `delivered`, THEN THE Fulfillment_Service SHALL NOT call the provider creation logic.
6. WHILE a Purchase_Order is in state `submitting`, THE Fulfillment_Service SHALL query the reference lookup before resubmitting and SHALL adopt any returned external order id instead of creating a new supplier order.
7. IF a Create_Purchase_Order_Job cannot acquire the execution lock within 10 seconds, THEN THE Fulfillment_Service SHALL terminate the job.

---

### Requirement 5: إعادة المحاولة ومعالجة الفشل وتصنيف الأخطاء (Retry and Failure Handling)

**User Story:** بصفتي مسؤول عمليات، أريد إعادة محاولة محدودة للأخطاء العابرة وتصنيف الأخطاء وتحديث الحقول المخصصة، حتى أتمكن من فرز المشكلات بدقة وسرعة.

#### Acceptance Criteria

1. THE Fulfillment_Provider SHALL classify each failed attempt using a value from the `Fulfillment_Error_Type` strongly typed enumeration.
2. THE Fulfillment_Service SHALL write the classified enum value to the `error_type` column of the automated attempts table.
3. IF a Transient_Failure occurs (`FulfillmentErrorType::NETWORK_ERROR` or `FulfillmentErrorType::AUTH_ERROR` when refresh is possible) AND the attempts count is below the maximum attempts count, THEN the job SHALL retry with an exponential backoff delay.
4. THE Fulfillment_Service SHALL limit the retry attempts of a Purchase_Order to the configured maximum attempts.
5. IF a Permanent_Failure occurs (e.g. `FulfillmentErrorType::VALIDATION_ERROR`, `FulfillmentErrorType::BUSINESS_RULE_ERROR`) OR the attempts count reaches the maximum limit, THEN THE Fulfillment_Service SHALL set the Purchase_Order state to `needs_manual_review` and record a comment on the Customer_Order.
6. WHEN a Purchase_Order state is set to `needs_manual_review`, THE Fulfillment_Service SHALL stop automatic retries and SHALL trigger an Error severity notification to the administrators.
7. WHEN each connection attempt completes, THE Fulfillment_Service SHALL record a `Fulfillment_Attempt` row containing the attempt number, result, error type enum, provider code, and message truncated to 1000 characters.

---

### Requirement 6: تعيين الحالات وعكسها وسلوك الواجهة (State Mapping and UI States)

**User Story:** بصفتي موظف تشغيل، أريد أن تعكس الواجهات حالة أوامر الشراء بدقة مع تفعيل الأزرار والعمليات المسموح بها فقط لكل حالة، لمنع الأخطاء البشرية.

#### Acceptance Criteria

1. THE Bridge SHALL represent every Purchase_Order state using a value from the State_Dictionary exclusively.
2. WHEN the Fulfillment_Provider returns a supplier status, THE Fulfillment_Provider SHALL map the raw supplier state to exactly one value within the State_Dictionary, and IF the raw state is unknown or empty, THEN it SHALL map it to `needs_manual_review`.
3. WHEN the Purchase_Order is in state `pending` or `submitting`, THE Admin UI SHALL disable all action buttons (Retry, Cancel, Sync) to prevent concurrent executions.
4. WHEN the Purchase_Order is in state `submitted` or `awaiting_payment_to_supplier`, THE Admin UI SHALL enable the `Sync Status` and `Cancel` actions, and SHALL disable `Retry`.
5. WHEN the Purchase_Order is in state `needs_manual_review`, THE Admin UI SHALL enable the `Manual Retry`, `Cancel`, and `Manual Edit` action buttons.
6. WHEN the Purchase_Order is in a final state (`delivered` or `canceled`), THE Admin UI SHALL disable all operations.
7. THE Fulfillment_Service SHALL update the Customer_Order status only through the official repository updates of the sales package.

---

### Requirement 7: عزل المصدر الوحيد للحقيقة المالي (Financial Single Source of Truth)

**User Story:** بصفتي مسؤول مالي، أريد ألّا يمسّ الجسر أي حقل مالي في طلب العميل أو الفاتورة، حتى تبقى الحسابات المالية سليمة ومملوكة لـ Bagisto حصريًا.

#### Acceptance Criteria

1. THE Bridge SHALL NOT write to any monetary or financial column of the orders or invoices tables.
2. THE Bridge SHALL restrict its database writes to the Bridge tables (`purchase_orders`, `purchase_order_items`, `fulfillment_attempts`, `fulfillment_audit_logs`).
3. THE Bridge SHALL perform Customer_Order status updates and comments only through published sales repository or service methods and SHALL NOT modify order or invoice records via direct SQL.
4. THE Bridge SHALL consume payment events as read-only.
5. IF a Bridge operation attempts a prohibited financial write, THEN THE Bridge SHALL abort the operation before database persistence.

---

### Requirement 8: متابعة حالة المورّد عبر Polling (Supplier Status Polling)

**User Story:** بصفتي مسؤول عمليات، أريد تحديثًا دوريًا لحالات أوامر الشراء غير النهائية، حتى تعكس المنصّة حالة الشحن والتتبّع من المورّد.

#### Acceptance Criteria

1. WHILE a Purchase_Order is in a non-final state (any State_Dictionary value other than `delivered`, `canceled`, or `needs_manual_review`), THE Poll_Supplier_Orders_Job SHALL query the status lookup for that Purchase_Order on each scheduled cycle.
2. WHEN a supplier status query returns a mapped state, THE Fulfillment_Service SHALL update the Purchase_Order `state` and `supplier_state_raw` fields.
3. WHEN a supplier status query returns both `tracking_number` and `tracking_company`, THE Fulfillment_Service SHALL store both values on the Purchase_Order.
4. IF a status query results in a Transient_Failure, THEN THE Fulfillment_Service SHALL preserve the Purchase_Order `state` and SHALL retry on the next scheduled cycle.
5. IF a status query results in a Permanent_Failure, THEN THE Fulfillment_Service SHALL set the Purchase_Order state to `needs_manual_review` and record a comment on the Customer_Order.
6. THE Poll_Supplier_Orders_Job SHALL run at the configured polling interval.

---

### Requirement 9: نماذج بيانات الجسر وسلامة العلاقات (Bridge Data Models)

**User Story:** بصفتي مطوّرًا، أريد نماذج بيانات واضحة تشمل جداول المحاولات وسجلات التدقيق للموظفين، حتى تبقى بيانات الجسر متّسقة وقابلة للتدقيق القانوني.

#### Acceptance Criteria

1. THE Bridge SHALL persist purchase orders in `purchase_orders`, purchase order items in `purchase_order_items`, automated API connection attempts in `fulfillment_attempts`, and human operator actions in `fulfillment_audit_logs`.
2. THE `fulfillment_audit_logs` schema SHALL include the columns: `id`, `purchase_order_id` (nullable), `user_id` (FK to admin user), `action` (retry, cancel, state_override, edit), `reason` (text, required for cancel/override/edit), `ip_address`, `changes_payload` (JSON), `approval_status`, `approved_by` (FK to admin user), and `timestamps`.
3. THE Bridge SHALL ensure that the Idempotency_Key and the Internal_Reference of every Purchase_Order are unique.
4. WHERE a Purchase_Order state is `submitted`, `awaiting_payment_to_supplier`, `shipped`, or `delivered`, THE Bridge SHALL ensure the `external_order_id` is recorded.
5. THE Bridge SHALL enforce a unique constraint on the pair `(purchase_order_id, order_item_id)` in `purchase_order_items`.
6. IF a validation or referential-integrity constraint is violated on write, THEN THE Bridge SHALL reject the write and SHALL surface an error indicating the violated rule.

---

### Requirement 10: بنية حزمة Concord (Concord Package Structure)

**User Story:** بصفتي قائمًا على المستودع، أريد أن يتّبع الجسر نمط Concord المعتمد في المشروع، حتى يبقى الكود متّسقًا مع بقية الحزم وقابلًا للترقية.

#### Acceptance Criteria

1. THE Bridge SHALL define each Bridge entity with all four Concord artifacts — a Contract, a Model, a Proxy, and a Repository — under the `Webkul\Fulfillment` package.
2. THE Bridge SHALL register the `Webkul\Fulfillment` service provider in the application providers registration file.
3. THE Bridge SHALL reuse the existing services without altering their public methods or signatures.
4. THE Bridge SHALL read all configuration values from the module configuration files.
5. THE Bridge SHALL provide administrator-facing message keys for comments and notifications in each of the 21 locale files, with no key present in one locale and absent in another.
6. THE Bridge SHALL register the `Webkul\Fulfillment` model bindings in the concord configuration files.
7. THE Bridge SHALL NOT invoke environment getters (`env`) in any file located outside the `config/` directory.

---

### Requirement 11: أمان السجلات والأسرار (Log and Secret Safety)

**User Story:** بصفتي مسؤول أمن، أريد ألّا تُسرَّب أي أسرار في السجلات أو البيانات المخزّنة، حتى تبقى بيانات الاعتماد محمية.

#### Acceptance Criteria

1. THE Bridge SHALL exclude all authentication token values from every log entry, `last_error` field, and `payload_snapshot` field by replacing each occurrence with a fixed redaction placeholder.
2. WHEN the Bridge logs a fulfillment failure, THE Bridge SHALL write the entry to the existing dedicated log channel.
3. THE Bridge SHALL truncate every stored error message to a maximum of 2000 characters before persisting it.
4. IF an authentication token value appears anywhere within an error message or serialized payload before it is written, THEN THE Bridge SHALL replace that value with the redaction placeholder.

---

### Requirement 12: تصنيف الإجراءات التشغيلية (Operations Actions Classification)

**User Story:** بصفتي مدير عمليات، أريد تصنيفاً واضحاً لكافة الإجراءات الإدارية وتحديد طريقة عملها وصلاحياتها، لضمان الانضباط التشغيلي.

#### Acceptance Criteria

1. WHEN an administrator triggers a `Manual Retry` action, THE Bridge SHALL execute it asynchronously via the background queue and SHALL NOT require a confirmation dialog.
2. WHEN an administrator triggers a `Force Cancel` action, THE Admin UI SHALL display a confirmation alert, and THE Bridge SHALL execute it asynchronously to cancel the order on the supplier's API.
3. WHEN an administrator triggers a `Refresh Status` action, THE Bridge SHALL execute it asynchronously and query the provider API to update the local state.
4. WHEN an administrator triggers a `Manual Edit` or `State Override` action, THE Admin UI SHALL enforce a confirmation dialog and require the administrator to provide a non-empty `reason` string of at least 10 characters.
5. THE Bridge SHALL restrict the execution of actions based on RBAC permissions.
6. THE Bridge SHALL log every human action in the `fulfillment_audit_logs` database table.

---

### Requirement 13: معمارية جدول البيانات (DataGrid Architecture & Views)

**User Story:** بصفتي موظف تشغيل، أريد جداول بيانات ذات خيارات تصفح وفلترة مخصصة ومحفوظة بناءً على دوري، لزيادة الكفاءة اليومية.

#### Acceptance Criteria

1. THE DataGrid SHALL load Default Columns: `PO ID`, `Order ID`, `Provider`, `Supplier Store`, `Cost`, `State`, `Tracking Number`, and `Submitted At`.
2. THE DataGrid SHALL support optional toggleable columns: `Internal Reference`, `External Order ID`, `Attempts`, and `Last Error`, and SHALL hide `Idempotency Key` and `Payload Snapshot` from regular UI lists.
3. THE DataGrid SHALL provide a role-specific saved view named "My Queue" containing pending/submitting orders, and a "Needs Review" view containing orders in `needs_manual_review` state.
4. THE DataGrid SHALL provide an "Executive View" for administrators displaying overall success rate KPIs, active provider issues, and orders breaching the 48-hour SLA.
5. THE DataGrid SHALL support bulk operations: `Bulk Retry` for orders in `needs_manual_review` state, `Bulk Cancel` for orders not yet submitted/shipped, and `Bulk Refresh`.
6. THE DataGrid SHALL support standard file exports containing only the filtered dataset.

---

### Requirement 14: معمارية الإشعارات والتنبيهات (Notification Severity)

**User Story:** بصفتي مشرف نظام، أريد توجيه وتصنيف التنبيهات بناءً على خطورتها، حتى لا تُهمل المشكلات الكبرى بسبب كثرة التنبيهات الروتينية.

#### Acceptance Criteria

1. WHEN an action of severity `Info` occurs (e.g. manual retry initiated), THE Admin UI SHALL show a temporary Toast notification lasting between 3 and 5 seconds.
2. WHEN an action of severity `Warning` occurs (e.g. polling delay exceeding 30 minutes), THE Bridge SHALL increment the sidebar badge counter and the dashboard warning widgets.
3. WHEN a severity `Error` event occurs (e.g. automated retry exhaustion, single PO permanent failure), THE Bridge SHALL dispatch a persistent admin notification banner and send an email alert to the operations team within 60 seconds.
4. WHEN a severity `Critical` event occurs (e.g. provider token expiration without auto-refresh, API rate limits blocking connections), THE Bridge SHALL display a prominent Red Alert Banner at the top of the Admin dashboard for all logged-in administrators and send an immediate email alert.

---

### Requirement 15: سجل التدقيق للعمليات البشرية (Human Actions Audit Trail)

**User Story:** بصفتي مشرف تدقيق مالي، أريد فصلاً تاماً بين محاولات الاتصال التقنية بالـ API وبين أفعال الموظفين البشرية، لضمان المساءلة الكاملة.

#### Acceptance Criteria

1. THE Bridge SHALL log all administrator actions (retry, cancel, state_override, edit) in the `fulfillment_audit_logs` table and SHALL NOT record automated API retry attempts in this table.
2. THE Bridge SHALL record the `user_id` of the logged-in administrator, the administrator's `ip_address` (as resolved from the HTTP request), the timestamp, and the `changes_payload` capturing the before/after state in a standard JSON format.
3. WHEN a high-risk operation (cancellation, manual edit, override) is initiated, THE Bridge SHALL require a non-empty `reason` string and store it in the audit log.
4. THE Bridge SHALL enforce that each audit log entry has a status of `executed`, `pending_approval`, `approved`, or `rejected`.

---

### Requirement 16: مؤشرات الأداء للوحة التحكم (Dashboard KPIs)

**User Story:** بصفتي مدير عمليات، أريد لوحة بيانات تعرض مؤشرات أداء دقيقة وصحيحة ومستندة لقواعد بيانات واضحة، لمراقبة جودة الخدمة.

#### Acceptance Criteria

1. THE Dashboard SHALL calculate the `Success Rate` KPI using the formula: `Count of POs with state IN (submitted, shipped, delivered) / Total POs` in the selected date filter range.
2. THE Dashboard SHALL calculate the `Retry Rate` KPI using the formula: `Count of POs with attempts > 1 / Total POs` in the selected date filter range.
3. THE Dashboard SHALL calculate the `Average Fulfillment Time` KPI using the formula: `AVG(submitted_at - invoices.created_at)` for successfully submitted POs.
4. THE Dashboard SHALL query `purchase_orders` to display immediate counters for "Orders Waiting" (`state` ∈ `pending`, `submitting`) and "Orders in Manual Review" (`state` = `needs_manual_review`).
5. THE Dashboard SHALL retrieve the `Provider Health` indicator dynamically based on the current validation of the authentication tokens and the success rate of the last 50 attempts recorded in automated attempts logs.
6. THE Dashboard SHALL display "Queue Backlog" by counting pending jobs in the queue for the PO creation task.

---

### Requirement 17: التصميم المتجاوب للواجهات (Responsive Behavior)

**User Story:** بصفتي موظف مستودع يتنقل بجهاز لوحي، أريد واجهة إدارة متجاوبة تتلاءم مع شاشة جهازي دون ضياع للبيانات أو صعوبة في الضغط على الأزرار.

#### Acceptance Criteria

1. THE Admin UI SHALL hide Priority 3 columns (Internal Reference, Submitted At, Last Error) on screens narrower than 1024 pixels.
2. THE Admin UI SHALL hide Priority 2 columns (Provider, Supplier Store) on screens narrower than 768 pixels.
3. WHEN a row in the DataGrid is clicked on a mobile or tablet device, THE Admin UI SHALL expand the row vertically to display the hidden Priority 2 and Priority 3 columns in an accordion layout.
4. THE Admin UI SHALL consolidate action buttons into a single "More Actions" dropdown list on screens narrower than 768 pixels.

---

### Requirement 18: ميزانية الأداء ومتطلبات الاستعلامات (Performance Budget & Queries)

**User Story:** بصفتي مدير فني للمنصة، أريد حدوداً قصوى لزمن تحميل لوحة البيانات وقواعد صارمة لمنع الاستعلامات غير الفعالة، لحماية أداء المتجر.

#### Acceptance Criteria

1. THE Admin Dashboard page load time SHALL NOT exceed 2.0 seconds.
2. THE Dashboard KPIs SHALL read from a cached data store updated periodically (every 15 minutes) via a background job, and SHALL NOT execute aggregate database queries inline with the page request.
3. THE Bridge controllers and DataGrids SHALL prevent N+1 Queries by utilizing eager loading strategies appropriate for the framework to retrieve purchase orders, items, and attempts in consolidated queries.
4. THE DataGrid database queries SHALL apply index-optimized filters on columns `order_id`, `state`, and `created_at`, and SHALL enforce pagination with a default page size of 20.

---

### Requirement 19: رايات المزايا والإطلاق التدريجي (Feature Flags & Progressive Rollout)

**User Story:** بصفتي مسؤول الإطلاق، أريد مفاتيح تحكم في الإعدادات لإيقاف أو تشغيل أجزاء الوحدة تدريجياً، لتقليل المخاطر عند النشر الأول.

#### Acceptance Criteria

1. THE Bridge SHALL expose the feature flags `FULFILLMENT_ADMIN_UI_ENABLED` (hides/shows admin menus), `FULFILLMENT_RETRY_ENABLED` (enables/disables automatic queue retries), and `FULFILLMENT_MANUAL_CANCEL_ENABLED` (enables/disables cancel actions).
2. THE core business logic of the order lifecycle and payments SHALL NOT permanently depend on these feature flags being disabled.
3. THE development team SHALL remove these temporary feature flags from the codebase and configurations within 90 days after the features stabilize in the production environment to prevent code debt.
4. IF a feature flag is set to `false`, THEN THE Bridge SHALL return a disabled error response when the corresponding feature or action is invoked and SHALL hide the UI elements.

---

### Requirement 20: مسار موافقة العمليات الحساسة (Approval Workflow - Optional Enterprise Capability)

**User Story:** بصفتي مدير متجر، أريد إيقاف العمليات عالية الخطورة حتى يراجعها مدير معتمد، لتجنب الخسائر المالية الناجمة عن تصرفات عشوائية.

#### Acceptance Criteria

1. THE Approval Workflow SHALL be optional and configurable via configuration files, allowing it to be completely disabled for small retail operations.
2. WHEN the workflow is enabled AND an administrator triggers a high-risk operation (cancellation of a paid PO, manual state override, or modification of item quantities in a submitted PO), THE Bridge SHALL suspend execution and create a record in `fulfillment_audit_logs` with status `pending_approval`.
3. THE Bridge SHALL notify administrators with Manager or Super Admin roles of the pending approval request.
4. IF a Manager or Super Admin approves the request, THEN THE Bridge SHALL execute the operation, update the audit log status to `executed`, and record the supervisor's ID in the `approved_by` field.
5. IF a Manager or Super Admin rejects the request, THEN THE Bridge SHALL cancel the operation, update the audit log status to `rejected`, and notify the requesting administrator.

---

### Requirement 21: النسخ والتطور المستقبلي (Versioning & Future Evolution)

**User Story:** بصفتي مشرفًا على المستودع، أريد قواعد واضحة لإدارة الإصدارات وإضافة موردين أو حالات جديدة مع الحفاظ على التوافق الخلفي، لضمان استدامة النظام لسنوات قادمة.

#### Acceptance Criteria

1. WHEN a new supplier provider is added, THE development team SHALL implement it using a new adapter class that implements the unified `FulfillmentProviderInterface` and SHALL NOT alter any common sales or checkout core code.
2. WHEN new states are added, THE development team SHALL map the new external provider states to the existing nine core states in the `State_Dictionary`, or store them as raw supplier states without breaking the core bridge states.
3. THE Bridge interface contracts SHALL remain immutable after release, and IF signature changes are required, THEN a new version of the contract (e.g., `FulfillmentProviderV2Interface`) SHALL be introduced.
4. ALL database schema modifications for tables or columns SHALL be backward-compatible by defining columns as nullable or with default values, ensuring existing records are not corrupted and production updates do not lock the database.
