# PDP Implementation Execution Plan v1.0

**Document Title:** HIGEST Product Detail Page (PDP) Implementation Execution Plan  
**Version:** 1.0 (Execution Roadmap)  
**Status:** Binding Execution Blueprint  
**Parent Specifications:**  
- [`RSR_v3.0_Part1_Functional_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part1)  
- [`RSR_v3.1_HIGEST_Refinements.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.1_HIGEST_Refinements)  
- [`RSR_v3.0_Part2_Responsive_UX_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part2_Responsive_UX_Specification.md)  
- [`RSR_v3.0_Part2.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part2.1_Review_Corrections.md)  
- [`RSR_v3.0_Part3_Visual_Design_System_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3_Visual_Design_System_Specification.md)  
- [`RSR_v3.0_Part3.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3.1_Review_Corrections.md)  
- [`RSR_v3.0_Part4_Technical_Implementation_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part4_Technical_Implementation_Specification.md)  
- [`RSR_v3.0_Part4.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part4.1_Review_Corrections.md)  

---

## Executive Summary & Constraint Directive

This document defines the strict, phased execution blueprint for developing the HIGEST Product Detail Page (PDP). It translates the sealed RSR v3.0 (Part 1 - 4.1) architecture into a deterministic sequence of file creations, modifications, risk mitigation steps, Git branching rules, and test verification gates.

> **CRITICAL DIRECTIVE:**  
> *This plan is for blueprinting and staging only. No source code modifications shall be executed during this step.*

---

## 1. File Modification & Creation Roadmap

### 1.1 New Files to be Created (`[NEW]`)

| Target Path | Type / Layer | Description & Architectural Purpose |
| :--- | :--- | :--- |
| [`packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php) | Backend Transformer | Isolates PDP data transformation logic from controller; builds clean ViewModel array. |
| [`packages/Webkul/Shop/src/Resources/views/components/products/dropshipping-transparency.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/dropshipping-transparency.blade.php) | Blade Component | Renders cross-border fulfillment transparency card (Origin, Dispatch, Tracking, Local RMA). |
| [`packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php) | Vue / Blade View | Renders `<v-mobile-sticky-bar>` with scroll sentinel trigger & option validation. |
| [`packages/Webkul/Shop/src/Resources/views/components/products/stock-meter.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/stock-meter.blade.php) | Blade Component | Renders stock meter indicator (Green for In-Stock, Amber for Low Stock ≤ 5, Red for Out-of-Stock). |
| [`packages/Webkul/Shop/tests/e2e-pw/pdp-flow.spec.ts`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/tests/e2e-pw/pdp-flow.spec.ts) | Playwright E2E | End-to-end test suite for PDP interaction, variant switching, and mobile sticky bar. |

### 1.2 Existing Files to be Modified (`[MODIFY]`)

| Target Path | Layer | Scope of Engineering Changes |
| :--- | :--- | :--- |
| [`packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php) | Controller | Delegates data payload resolution to `ProductPDPTransformer`. |
| [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) | Main Blade | Injects dropshipping card, mobile sticky bar sentinel, LCP preloads, and analytics event bus. |
| [`packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php) | Gallery Blade | Updates `<v-product-gallery>` data handlers with multi-tier `@error` fallback attributes. |
| [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php) | Gallery Desktop | Adds fixed `aspect-[560/610]` aspect box, `width="560"`, `height="610"`, and `fetchpriority="high"`. |
| [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php) | Gallery Mobile | Enforces touch swipe translate and fixed square aspect reservation (`aspect-square`). |
| [`packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php) | Swatch Vue | Refactors price reloading to emit reactive `@variant-selected` events instead of raw DOM manipulation. |
| [`packages/Webkul/Product/src/ProductImage.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/ProductImage.php) | Helper | Adds original fallback URLs to `getCachedImageUrls()` and handles storage checks gracefully. |
| [`packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php) | Controller | Intercepts missing cache requests to stream original files when dynamic resizing fails. |
| [`packages/Webkul/Product/src/Helpers/View.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/Helpers/View.php) | Helper | Provides dropshipping transparency resolution helpers. |

---

## 2. Phased Implementation Sequence

```
+-----------------------------------------------------------------------------------+
| PHASE 1: Backend Data & Transformer Layer                                        |
|   1.1 Create ProductPDPTransformer.php                                            |
|   1.2 Extend Webkul\Product\Helpers\View                                         |
|   1.3 Enhance ProductImage.php fallback URLs                                      |
|   1.4 Update ImageCache Controller stream fallback                                |
+-----------------------------------------------------------------------------------+
                                      │
                                      ▼
+-----------------------------------------------------------------------------------+
| PHASE 2: Core Design System Tokens & Sub-components                              |
|   2.1 Create dropshipping-transparency.blade.php                                 |
|   2.2 Create stock-meter.blade.php                                                |
|   2.3 Verify Tailwind tokens (Navy #060C3B, Light Cream #F6F2EB)                  |
+-----------------------------------------------------------------------------------+
                                      │
                                      ▼
+-----------------------------------------------------------------------------------+
| PHASE 3: Product Gallery Subsystem & LCP / CLS Prevention                         |
|   3.1 Refactor desktop.blade.php (aspect-[560/610], fetchpriority="high")        |
|   3.2 Refactor mobile.blade.php (aspect-square touch swipe)                       |
|   3.3 Update v-product-gallery with @error inline fallbacks                       |
+-----------------------------------------------------------------------------------+
                                      │
                                      ▼
+-----------------------------------------------------------------------------------+
| PHASE 4: Variant Swatches & Purchase Form Subsystem                               |
|   4.1 Refactor configurable.blade.php (emit @variant-selected)                    |
|   4.2 Add disabled diagonal strike-through styling for OOS swatches              |
|   4.3 Wire Buy Now / Add to Cart loading spinners                                 |
+-----------------------------------------------------------------------------------+
                                      │
                                      ▼
+-----------------------------------------------------------------------------------+
| PHASE 5: Mobile UX & Sticky Purchase Bar Subsystem                                |
|   5.1 Create mobile-sticky-bar.blade.php                                          |
|   5.2 Attach IntersectionObserver to #primary-pdp-cta-container                   |
|   5.3 Enforce option validation guard ("Select Options")                           |
+-----------------------------------------------------------------------------------+
                                      │
                                      ▼
+-----------------------------------------------------------------------------------+
| PHASE 6: SEO Schemas, Analytics Bus & Verification Gates                          |
|   6.1 Inject JSON-LD Rich Snippet array in view.blade.php                         |
|   6.2 Wire GA4 & Meta Pixel event emitter bus                                     |
|   6.3 Run vendor/bin/pint --dirty                                                 |
|   6.4 Run Pest & Playwright automated test suites                                 |
+-----------------------------------------------------------------------------------+
```

---

## 3. Risk Matrix & Mitigation Strategies

| Identified Risk | Severity | Root Cause | Technical Mitigation Strategy |
| :--- | :--- | :--- | :--- |
| **Broken Image 404s** | **HIGH** | Missing cache file on disk or Nginx routing override. | Multi-tier fallback: Nginx `try_files` ──► Controller on-the-fly stream ──► Frontend `@error` asset fallback. |
| **Cumulative Layout Shift (CLS > 0)** | **HIGH** | Unsized image containers expanding upon load. | Enforce hard aspect wrappers (`aspect-[560/610]`) & explicit HTML `width="560" height="610"` attributes. |
| **JS Errors on Variant Selection** | **MEDIUM** | Direct DOM queries (`document.querySelector('.final-price')`) failing if price markup changes. | Eliminate direct DOM mutation; use Vue Event Emitter bus (`@variant-selected`) to update reactive price models. |
| **Unauthorized DB Schema Drift** | **HIGH** | Attempting to create new migration files for dropshipping data. | Strictly map dropshipping fields to core configuration (`core()->getConfigData()`) and existing EAV attributes. |
| **Mobile CTA Clutter** | **MEDIUM** | Rendering sticky purchase bar while main CTA is visible. | Activate sticky bar only when `#primary-pdp-cta-container` is scrolled out of viewport via `IntersectionObserver`. |

---

## 4. Git Branching & Commit Strategy

### 4.1 Branch Topology

```
main (Production Base)
  └── feature/pdp-rsr-v3.0 (Isolated Execution Branch)
```

### 4.2 Granular Commit Rules

Each phase must be committed separately upon passing `vendor/bin/pint --dirty`:

1. `feat(pdp): add ProductPDPTransformer and extend image fallback helpers`
2. `feat(pdp): add dropshipping transparency and stock meter Blade components`
3. `fix(pdp): optimize gallery rendering for LCP < 2.5s and zero CLS`
4. `refactor(pdp): update configurable variant selector to use reactive event bus`
5. `feat(pdp): implement v-mobile-sticky-bar with scroll sentinel observer`
6. `feat(pdp): add JSON-LD schemas and GA4/Meta analytics event bus`

---

## 5. Test & Verification Protocol

### 5.1 Static Code Quality & Formatting Gate

```bash
# Must return zero errors
vendor/bin/pint --dirty
```

### 5.2 Automated Backend Feature Tests

```bash
# Execute Shop package test suite
php artisan test --compact packages/Webkul/Shop/tests
```

### 5.3 End-to-End Browser Verification (Playwright)

```bash
cd packages/Webkul/Shop && npx playwright test tests/e2e-pw/pdp-flow.spec.ts
```

### 5.4 Manual Verification Checklist

* [ ] Verify LCP < 2.5s using Chrome DevTools Lighthouse audit on 4G throttling.
* [ ] Verify CLS = 0 during image loading and variant switching.
* [ ] Test LTR (English) and RTL (Arabic) rendering.
* [ ] Verify image fallback by testing a non-existent image path.
* [ ] Validate JSON-LD schema using Google Rich Results Test.

---

## Blueprint Authorization & Staging Seal

**Prepared By:** Lead Platform Architect  
**Status:** Execution Blueprint Finalized (Ready for Code Implementation)
