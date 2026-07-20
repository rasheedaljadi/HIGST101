# ADR-001 — Fulfillment Architecture Decision

- **Status:** Accepted
- **Date:** 2026-07-06
- **Deciders:** Architecture / Backend
- **Supersedes:** —
- **Related:** ADR-002 (Fulfillment Provider Abstraction), ADR-003 (Order Fulfillment Trigger Policy), `Fulfillment-Bridge-Architecture.md`

> **Scope note.** This ADR describes the target design for the Phase‑2 *Order Fulfillment* module. That module does **not** exist in the codebase yet. As of this writing the AliExpress integration under `app/Services/AliExpress/` only covers **product import** and **price/stock synchronization** (`AliExpressProductImporter`, `AliExpressProductSyncer`, `SyncProductJob`, `SyncAllProductsJob`). No order or purchase-order entity exists. This document fixes the architectural decisions *before* any fulfillment code is written so they are not discovered late during implementation.

---

## Context

The platform sells products sourced from AliExpress inside a standard Bagisto 2.4.x storefront. When a customer places and pays for an order, the corresponding items must be purchased from AliExpress (dropshipping) and shipped to the customer. This introduces a second, external order lifecycle that runs in parallel to the native Bagisto sales order.

Two lifecycles are now in play:

1. **Customer Order** — the native Bagisto `Order` the customer sees, pays for, and tracks. Owned entirely by Bagisto (Sales package).
2. **Purchase Order (PO)** — the order(s) we place *on the supplier* (AliExpress) to fulfill the customer order. Owned by us, mirrored against the supplier's own order record.

The core question is how to model the relationship between these two lifecycles, and where the source of truth lives at each stage.

## Decision

### D1 — Customer Order and Purchase Order are separate entities

We will **not** overload the Bagisto `Order` (or its shipments) to also represent the supplier purchase. A dedicated Purchase Order entity is introduced, living in the app layer (`App\`), linked to the Bagisto order by id but never merging with it.

**Why not merge them:**

- **Different owners, different lifecycles.** The customer order state machine (pending → processing → completed / canceled / refunded) is Bagisto's. The supplier order state machine (placed → paid → shipped → delivered / failed / refunded by supplier) is AliExpress's. They advance independently and at different times. Cramming both into one record forces one state field to mean two things.
- **Different cardinality.** One customer order can map to several supplier purchases (see D3). A single `Order` row cannot cleanly represent N supplier states, tracking numbers, and failure reasons.
- **Different failure semantics.** A supplier purchase can fail (out of stock, price change, supplier rejection) *after* the customer order is already paid and confirmed. That failure must be recorded and retried without corrupting the customer-facing order or its accounting.
- **Cancellation and refund isolation.** Cancelling a supplier PO (e.g. supplier out of stock) must not implicitly cancel the customer order — it triggers a business decision (re-source, substitute, or refund). Separate entities keep these decisions explicit.
- **Auditing and replaceability.** Keeping the supplier order as its own record lets us swap or add suppliers (ADR-002) without touching the sales schema, and preserves a clean audit trail per supplier attempt.

### D2 — A Bridge Layer mediates between Bagisto and the supplier

All communication between the Bagisto sales domain and the supplier is routed through a **Bridge Layer** (the Fulfillment module). Bagisto never calls AliExpress directly; the supplier client never reaches into Bagisto's order tables directly.

**Why a bridge:**

- **Isolation.** Bagisto stays ignorant of AliExpress specifics (payload shapes, signing, endpoints, rate limits). The supplier client stays ignorant of Bagisto internals.
- **Replaceability.** Adding or swapping a supplier (e.g. CJ Dropshipping) becomes an implementation detail behind the bridge, not a rewrite of the sales flow (formalized in ADR-002).
- **Consistent state translation.** Supplier states are heterogeneous and provider-specific. The bridge is the single place that maps supplier states to our internal PO states (see `Fulfillment-Bridge-Architecture.md`).
- **Testability.** The bridge boundary is a natural seam for faking the supplier in tests, exactly as the current code already fakes `AliExpressApiClient`.

This matches the existing pattern: the AliExpress import/sync code already keeps AliExpress details inside `App\Services\AliExpress\*` and exposes Bagisto-native results (native `Product` rows). The bridge extends that same discipline to the order side.

### D3 — Customer Order → Purchase Orders is a 1:N relationship

One customer order produces **one or more** purchase orders.

**Why 1:N:**

- **AliExpress is multi-seller.** Items in a single cart frequently come from different AliExpress sellers/stores; each store is a separate supplier order. One PO per store is the natural unit.
- **Bagisto already supports multiple shipments per order.** A 1:N PO model aligns with Bagisto's existing multi-shipment capability, so tracking numbers and shipment states map cleanly onto individual POs.
- **Partial fulfillment / partial failure.** With 1:N, one PO can succeed and ship while another fails and retries, without blocking the rest of the customer order.

The reverse (N customer orders → 1 PO, i.e. batching) is explicitly **out of scope** for this decision and not supported by the initial model.

### D4 — Single Source of Truth (SSOT) per stage

Ownership of truth moves along the lifecycle. This is fixed to avoid divergence:

| Stage | Single Source of Truth | Notes |
|-------|------------------------|-------|
| Cart / pricing / customer checkout | **Bagisto** (`Order`, `OrderItem`, catalog price) | Customer-facing amounts and order identity. |
| Payment / capture status | **Bagisto + payment gateway** | Determines the fulfillment trigger (ADR-003). |
| Decision to purchase, PO identity, internal PO state | **Fulfillment module (our DB)** | The PO row is authoritative for *our* view of the supplier order. |
| Supplier order state, tracking, supplier price actually charged | **Supplier (AliExpress)**, mirrored into the PO | Supplier is authoritative; we cache/mirror it into the PO via sync/poll. Our mirror is never treated as more current than the supplier. |
| Customer-facing shipment / delivery status | **Bagisto shipment**, updated *from* the PO mirror | The bridge writes supplier tracking back into Bagisto shipments. |

Rule: **we never mutate a field whose SSOT is elsewhere as if we owned it.** The PO mirrors supplier state; it does not invent it. Bagisto owns customer money and order identity; the fulfillment module never rewrites those directly except through supported Bagisto repositories (shipments, comments).

### D5 — Purchase Order creation happens only after payment is confirmed

A PO is created (and the supplier is charged) **only after the customer's payment is confirmed**, never at checkout/order-placement time for prepaid methods.

**Why:**

- **Financial risk.** Placing a real, paid supplier order for an unpaid or abandoned customer order means we spend money on orders we may never collect on.
- **Clean trigger.** Payment confirmation is a well-defined event we can hook, giving an unambiguous point to start fulfillment.

**Documented exception — Cash on Delivery (COD):** for COD there is no upfront payment to confirm. COD orders follow a *separate trigger path* defined in ADR-003 (e.g. trigger on order confirmation/approval rather than on payment capture), and carry higher inherent risk that is accepted as a business decision. The exact COD trigger, and any manual-approval gate, are owned by ADR-003.

## Consequences

**Positive**

- Independent, auditable lifecycles for customer orders and supplier purchases.
- Supplier is replaceable/extensible without touching the sales domain (see ADR-002).
- Partial fulfillment and partial failure are first-class, matching AliExpress reality and Bagisto's multi-shipment model.
- Financial exposure is bounded by the "purchase after payment" rule.

**Negative / costs**

- More entities and more moving parts than a single-order model: a PO table, state machine, sync/poll jobs, and reconciliation logic must be built and maintained.
- State must be mirrored from the supplier, introducing eventual-consistency windows between supplier truth and our mirror.
- Requires the trigger policy (ADR-003) and idempotent PO creation (see `Fulfillment-Bridge-Architecture.md`) to be correct, because "purchase real money" is a side effect that must never be duplicated.

**Idempotency note (carried into the bridge design):** duplicate PO creation must be prevented by **our own system** (bridge + database constraints), and must **not** rely on AliExpress supporting an idempotency key. AliExpress idempotency support is to be treated as **unproven/unsupported until confirmed** against official docs; if it later proves available it is used only as an additional safety layer, never as the primary guard. Full mechanism is specified in `Fulfillment-Bridge-Architecture.md`.

## Open items (must be confirmed before implementation)

These do not change the decisions above but affect implementation detail. They require an independent audit of the AliExpress Dropshipping **Order** API:

1. Does supplier order creation support an idempotency key? (assume **no** until proven)
2. One PO per store, or can multiple stores be submitted in a single supplier call?
3. Are there official webhooks for dropshipping orders, or is polling the only documented option?
4. What are the rate limits on the order APIs?

Tracked in `Fulfillment-Bridge-Architecture.md` → "Open API Questions".
