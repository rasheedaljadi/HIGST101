# RSR v3.0 Part 2.1 — Review Corrections & Business Realignment

**Document Title:** RSR v3.0 Part 2.1 — Review Corrections & Business Realignment  
**Version:** 3.0 (Part 2.1 Addendum)  
**Status:** Binding Specification Addendum / Approved with Corrections  
**Parent Document:** `RSR_v3.0_Part2_Responsive_UX_Specification.md`  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## Executive Context

Following review and approval of **RSR v3.0 Part 2 — Responsive & UX Specification**, this addendum formalizes four critical corrections to align the binding UX specification strictly with HIGEST's operational dropshipping model, technical architecture, and commercial policy engine.

These corrections supersede and refine specific sections in `RSR_v3.0_Part2_Responsive_UX_Specification.md`.

---

## 1. Commercial & Policy Realignment

### 1.1 Promotion Display Adjustment (Hierarchy Item #5)
* **Previous Specification:** Hardcoded *"Flash Sale / Limited Time Offer Countdown Timer"*.
* **Correction:** 
  * **Promotional Badge (Optional & Dynamic):** Replaced with a dynamic promotion pill controlled strictly by the **HIGEST Promotion Engine** (`Webkul\CartRule` / `Webkul\CatalogRule`).
  * **Condition:** Countdown timers or promotional tags must only render if an active promotion rule exists for the product in Bagisto admin. No hardcoded timer widgets or un-backed countdown scripts shall be rendered.

### 1.2 Trust Layer Financial Policy Neutrality (Section 6)
* **Previous Specification:** Specified *"On-Time Guarantee or $5 Credit"*.
* **Correction:**
  * **Policy-Driven Delivery Guarantee:** Removed hardcoded monetary compensation promises (`$5 credit`).
  * **Engine Dependency:** Replaced with a configurable trust string: *"Delivery Guarantee — Displayed only if supported and enabled by HIGEST policy engine"*.
  * **Rule:** The frontend UX must render trust micro-copy dynamically from backend configuration without assuming specific customer compensation policies.

---

## 2. Dropshipping Transparency UX Engine

Because HIGEST operates a cross-border marketplace dropshipping model (integrating AliExpress and direct supplier feeds), customer trust depends on absolute fulfillment transparency. The following mandatory UX specification is added to **Section 6 (Trust Layer UX)**:

```
+-------------------------------------------------------------------------+
| 🌐 SUPPLIER FULFILLMENT & DISPATCH TRANSPARENCY                         |
| ----------------------------------------------------------------------- |
| 📍 Item Origin       : International Overseas Warehouse (Express Freight) |
| 🚚 Estimated Delivery: 5 - 8 Business Days to your address              |
| 📦 Parcel Tracking   : End-to-End Tracking Number Provided upon dispatch|
| ↩️ Return Policy     : Local HIGEST Return Hub Processing (14 Days)      |
+-------------------------------------------------------------------------+
```

### 2.1 Mandatory Transparency Elements

1. **Origin Country & Dispatch Source:**
   * Clearly informs the buyer if an item is shipped from a local hub or an international warehouse (e.g. *"Shipped from International Fulfillment Center"*).
2. **Realistic Estimated Delivery Window:**
   * Calculated based on actual supplier lead times + transit rules rather than static retail shipping estimates.
3. **End-to-End Tracking Availability:**
   * Explicit visual indicator reassuring the customer that a valid tracking number will be issued once the AliExpress/supplier fulfillment saga processes the order.
4. **Local Return Process Clarity:**
   * Explains that returns are handled through HIGEST's local return system (RMA package) rather than forcing customers to ship packages back internationally.

---

## 3. Mobile Sticky Bar Collision & Layering Constraints

To ensure flawless mobile usability, **Section 8 (Mobile Conversion Optimization)** is updated with strict z-index hierarchy and collision avoidance rules for the `v-mobile-sticky-bar`:

```
Layering & Z-Index Stack Matrix:
[ Z-Index 1000 ] : Modal Lightbox (`v-gallery-zoomer`)
[ Z-Index 500  ] : Mobile Bottom Sheet Options Selector
[ Z-Index 100  ] : Checkout Overlays & Full-Screen Drawers
[ Z-Index 50   ] : Fixed Mobile Sticky Purchase Bar (`v-mobile-sticky-bar`)
[ Z-Index 40   ] : Cookie Consent / Compliance Banners
```

### 3.1 Collision Avoidance Rules

* **Overlay Priority:** The sticky purchase bar (`z-50`) **MUST NOT** obscure or cover:
  1. Active Cookie Consent / Privacy Banners (Banners take priority or push sticky bar above).
  2. Mobile Bottom Sheet Swatch Selectors (Bottom sheet opens over sticky bar at `z-500`).
  3. System Toast / Flash Messages (`add-flash` notifications).
  4. Checkout Drawers or Modal Overlays.
* **Auto-Hide Threshold:** The sticky bar must automatically hide when:
  * The user opens a modal/drawer.
  * The user scrolls into the footer area.
  * Inline purchase buttons on the primary product card are fully visible within the viewport.

---

## 4. Performance Acceptance Criteria (AC-PERF)

The following performance acceptance tests are added to **Section 10 (UX Acceptance Criteria)** to guarantee sub-second rendering and Web Vitals compliance on Bagisto 2.4.x:

### 4.1 Automated Performance Criteria

* **AC-PERF-01 (Largest Contentful Paint - LCP):**
  * The main product image / LCP element must render within **< 2.5 seconds** on standard mobile 4G network throttling.
* **AC-PERF-02 (Zero Cumulative Layout Shift - CLS):**
  * Image gallery and variant swatches must use fixed aspect-ratio aspect boxes (`aspect-square` / aspect ratio CSS) and shimmer skeletons to ensure **CLS = 0** during image loading and Vue component hydration.
* **AC-PERF-03 (Progressive Usability & Critical Path Readiness):**
  * The primary product interaction block (Title, Price, Variants, ATC/Buy Now buttons) must be 100% interactive **BEFORE** secondary non-critical async data payloads (Reviews list, Related product carousels, Up-sell carousels) finish fetching from the API.

---

## Summary of Changes Applied to Base Specification

| Document Section | Change Type | Description of Correction |
| :--- | :--- | :--- |
| **Section 4 (#5)** | Modified | Replaced hardcoded Flash Sale timer with optional, dynamic `Promotional Badge` controlled by Cart/Catalog rules. |
| **Section 6** | Modified | Removed hardcoded `$5 credit` promise; made delivery guarantee policy-engine driven. |
| **Section 6.1** | **NEW** | Added **Dropshipping Transparency UX Specification** (Origin, Estimated Transit, Tracking, Local RMA). |
| **Section 8.1** | Modified | Added **Z-Index & Collision Avoidance Matrix** for Mobile Sticky Bar (no covering cookies/bottom sheets). |
| **Section 10.4**| **NEW** | Added **Performance Acceptance Criteria (AC-PERF-01, AC-PERF-02, AC-PERF-03)** for Web Vitals & Hydration. |

---

**Approved By:** HIGEST Core Platform Engineering Team  
**Status:** Binding Specification Addendum (Ready for Part 3 — Visual Design System)
