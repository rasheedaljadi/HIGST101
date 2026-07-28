# PDP Implementation Execution Roadmap v2.1

**Document Title:** HIGEST Product Detail Page (PDP) Implementation Execution Roadmap  
**Version:** 2.1 (Official Sealed Execution & Progress Tracking Blueprint)  
**Status:** Active Tracking & Governance Blueprint  
**Parent Specifications:**  
- [`RSR_v3.0_Part1_Functional_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part1)  
- [`RSR_v3.1_HIGEST_Refinements.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.1_HIGEST_Refinements)  
- [`RSR_v3.0_Part2_Responsive_UX_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part2_Responsive_UX_Specification.md)  
- [`RSR_v3.0_Part2.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part2.1_Review_Corrections.md)  
- [`RSR_v3.0_Part3_Visual_Design_System_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3_Visual_Design_System_Specification.md)  
- [`RSR_v3.0_Part3.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3.1_Review_Corrections.md)  
- [`RSR_v3.0_Part4_Technical_Implementation_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part4_Technical_Implementation_Specification.md)  
- [`RSR_v3.0_Part4.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part4.1_Review_Corrections.md)  
- [`PDP_Implementation_Execution_Plan_v1.0.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/PDP_Implementation_Execution_Plan_v1.0.md)  

---

## Mandated Agent Execution Rules

> **STRICT AGENT GOVERNANCE RULES:**  
> The executing agent MUST ALWAYS adhere to the following 5 rules without exception:  
> 1. **No Parallel Phase Mutations:** The agent MUST NOT modify code across multiple phases simultaneously. Work must proceed strictly one phase at a time.  
> 2. **No Gate Skipping:** The agent MUST NOT advance to a new Phase without explicit project lead approval and 100% completion of prior gate criteria.  
> 3. **No Unrelated Refactoring:** The agent MUST NOT refactor unrelated Bagisto core components, packages, or themes outside the PDP scope.  
> 4. **No Unauthorized Migrations:** The agent MUST NOT create database migrations unless explicitly approved by the project lead.  
> 5. **No Business Logic Alteration:** The agent MUST NOT alter existing core business logic (checkout rules, payment integrations, or order fulfillment sagas) outside the designated PDP UI/UX scope.

---

## Phase -1: Implementation Discovery & Code Mapping

**Phase Goal:** Lock codebase inspection, map full Blade hierarchy, trace Vue component dependencies, verify route bindings, and audit custom Bagisto package overrides prior to touching source code.

### Task -1.1: Comprehensive PDP File & Route Audit
* **Description:** Audit all active PDP controller routes (`shop.product_or_category.index`), Blade templates, Vue 3 components, and JS asset entry points.
* **Affected Files:** Inspection Logs & Mapping Matrix
* **Type of Work:** `CONFIG`
* **Risk Level:** `LOW`
* **Dependencies:** None
* **Acceptance Criteria:** Full mapping of `ProductsCategoriesProxyController`, `view.blade.php`, `v-product`, `v-product-gallery`, and `v-product-configurable-options` verified against physical disk locations.
* **Test Method:** Static code inspection & directory tree verification.

### Task -1.2: Overrides & Event Listener Audit
* **Description:** Audit custom Bagisto overrides in `packages/Webkul/` for event listeners (`bagisto.shop.products.view.*`), custom package hooks, and asset compilation scripts.
* **Affected Files:** Inspection Logs
* **Type of Work:** `CONFIG`
* **Risk Level:** `LOW`
* **Dependencies:** Task -1.1
* **Acceptance Criteria:** Complete list of third-party package hooks and custom theme listeners documented to prevent breaking existing extensions during PDP refactoring.
* **Test Method:** Grep search for `view_render_event` and `Event::listen` across `packages/Webkul/`.

---

## Phase 0: Preparation & Safety

**Phase Goal:** Establish execution branch, capture production baselines, verify database integrity, and lock safety rollback mechanisms before modifying any codebase assets.

### Task 0.1: Git Feature Branch & Tag Creation
* **Description:** Create isolated execution branch `feature/pdp-rsr-v3.0` and tag pre-migration baseline `v3.0-pdp-pre-execution`.
* **Affected Files:** Git Repository Metadata
* **Type of Work:** `CONFIG`
* **Risk Level:** `LOW`
* **Dependencies:** Phase -1 Approval Gate
* **Acceptance Criteria:** Branch created and checked out; tag created locally.
* **Test Method:** `git branch --show-current` outputs `feature/pdp-rsr-v3.0`.

### Task 0.2: Baseline Performance & Visual Snapshot Capture
* **Description:** Run baseline Chrome Lighthouse performance audit and capture full-page desktop/mobile screenshots of existing PDP.
* **Affected Files:** QA Audit Logs
* **Type of Work:** `CONFIG`
* **Risk Level:** `LOW`
* **Dependencies:** Task 0.1
* **Acceptance Criteria:** Baseline LCP, CLS, FID scores recorded; reference PNG screenshots saved.
* **Test Method:** Inspection of baseline audit report in QA artifacts folder.

---

## Phase 1: Backend Data Architecture

**Phase Goal:** Decouple data transformation from controllers, introduce `ProductPDPTransformer`, resolve dropshipping transparency metadata without DB migration, and validate Eloquent model queries.

### Task 1.1: Create ProductPDPTransformer Service
* **Description:** Implement `ProductPDPTransformer` class to assemble clean PDP ViewModel array (Pricing, MSRP strikethrough, savings percentage, tax status, variant matrix).
* **Affected Files:** [`packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php) `[NEW]`
* **Type of Work:** `NEW`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Phase 0 Approval Gate
* **Acceptance Criteria:** ViewModel array completely decouples raw Eloquent queries from Blade views; returns deterministic types for simple and configurable SKUs.
* **Test Method:** Unit test `php artisan test --compact --filter=ProductPDPTransformerTest`.

### Task 1.2: Refactor Proxy Controller Data Resolution
* **Description:** Update `ProductsCategoriesProxyController::index()` to pass `$product` model through `ProductPDPTransformer` before rendering `shop::products.view`.
* **Affected Files:** [`packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 1.1
* **Acceptance Criteria:** Controller contains zero array building logic; delegates 100% to Transformer.
* **Test Method:** Feature test `php artisan test --compact --filter=ProductPageTest`.

### Task 1.3: Extend Dropshipping Transparency Metadata Helper
* **Description:** Extend `Webkul\Product\Helpers\View` to resolve dropshipping fulfillment origin, dispatch lead time, tracking availability, and 14-day local RMA policy from system configuration.
* **Affected Files:** [`packages/Webkul/Product/src/Helpers/View.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/Helpers/View.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `LOW`
* **Dependencies:** Task 1.2
* **Acceptance Criteria:** Resolves origin, delivery estimate, and RMA policy from `core()->getConfigData()` without creating any new database migrations.
* **Test Method:** Unit test inspecting return payload of `getDropshippingMetadata()`.

---

## Phase 2: Image Delivery & Gallery Architecture

**Phase Goal:** Audit production image infrastructure, eliminate broken image 404s, implement multi-tier image fallback, verify symlinks/Nginx compatibility, and enforce zero CLS aspect reservation.

### Task 2.1: Production Image Delivery Compatibility Audit
* **Description:** Audit production image delivery pipeline including `public/storage` symlinks, `public/cache` directory access, `ImageCache` dynamic routes, and web server behavior.
* **Affected Files:** Infrastructure & Storage Setup
* **Type of Work:** `CONFIG`
* **Risk Level:** `HIGH`
* **Dependencies:** Phase 1 Approval Gate
* **Acceptance Criteria:** Production image pipeline verified across storage symlinks and Intervention cache routes; all PDP image URLs return HTTP 200 with `Content-Type: image/webp`.
* **Test Method:** Terminal command `curl -I -s "https://higest.com/cache/large/product/1/test.jpg" | grep -E "HTTP/|Content-Type"`.

### Task 2.2: Multi-Tier Image Fallback Helper Implementation
* **Description:** Update `ProductImage::getCachedImageUrls()` to include explicit `original_image_url` and `fallback_url` attributes.
* **Affected Files:** [`packages/Webkul/Product/src/ProductImage.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/ProductImage.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `HIGH`
* **Dependencies:** Task 2.1
* **Acceptance Criteria:** If dynamic image cache route returns empty or fails, helper output guarantees a valid image URL pointing to original source or theme placeholder asset.
* **Test Method:** Unit test passing null/invalid image paths through `ProductImage`.

### Task 2.3: ImageCache Controller Resiliency Interceptor
* **Description:** Modify `ImageController` to intercept missing cache requests and stream original uploaded file binary directly with HTTP 200 headers if dynamic Intervention generation fails.
* **Affected Files:** [`packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `HIGH`
* **Dependencies:** Task 2.2
* **Acceptance Criteria:** HTTP request to missing `/cache/large/product/...` file never returns HTTP 404 or broken image icon.
* **Test Method:** HTTP GET request simulation against unrendered cache URL.

### Task 2.4: Gallery Aspect Ratio & Preload Optimization
* **Description:** Refactor gallery Blade templates to include fixed aspect ratio wrappers (`aspect-[560/610]`), explicit `width="560" height="610"`, `fetchpriority="high"`, and `@error` fallback bindings.
* **Affected Files:** 
  - [`packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php) `[MODIFY]`
  - [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 2.3
* **Acceptance Criteria:** Gallery image container reserves exact vertical space before image download; main image LCP candidate renders within < 1.8s.
* **Test Method:** Chrome DevTools Performance Audit & CLS score measurement.

---

## Phase 3: Core PDP Layout Implementation

**Phase Goal:** Implement premium HIGEST design system visual tokens, typography hierarchy, price block, discount savings badge, stock meter indicator, and dropshipping transparency card.

### Task 3.1: Create Stock Meter Component
* **Description:** Implement `<x-shop::products.stock-meter>` Blade component to render stock status meters (Green for In-Stock, Amber for Low Stock ≤ 5, Red for Out-of-Stock).
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/components/products/stock-meter.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/stock-meter.blade.php) `[NEW]`
* **Type of Work:** `NEW`
* **Risk Level:** `LOW`
* **Dependencies:** Phase 2 Approval Gate
* **Acceptance Criteria:** Renders semantic colors accurately based on `$product->total_qty`; displays low stock urgency pill when quantity is ≤ 5.
* **Test Method:** Render component under dummy product quantities (0, 3, 20).

### Task 3.2: Create Dropshipping Transparency Card Component
* **Description:** Implement `<x-shop::products.dropshipping-transparency>` Blade component displaying item origin, estimated 5-8 business days transit window, tracking availability, and 14-day local RMA policy.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/components/products/dropshipping-transparency.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/dropshipping-transparency.blade.php) `[NEW]`
* **Type of Work:** `NEW`
* **Risk Level:** `LOW`
* **Dependencies:** Task 3.1
* **Acceptance Criteria:** Card renders inside information column with rounded-2xl border, zinc-50 background, and clickable modal policy triggers.
* **Test Method:** Visual inspection in LTR and RTL viewports.

### Task 3.3: Main PDP Blade View Layout Assembly
* **Description:** Update `view.blade.php` to integrate typography hierarchy, Price Block (`#060C3B`), MSRP Strikethrough, Discount Pill (`#F85156`), Stock Meter, and Dropshipping Card.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 3.2
* **Acceptance Criteria:** Information column matches Part 3 Visual System Specification with exact padding (`px-[60px]`), gap spacing (`gap-9`), and brand colors.
* **Test Method:** Visual comparison against RSR Part 3 visual design contract.

---

## Phase 4: Variant & Purchase Experience

**Phase Goal:** Refactor configurable variant selector to use reactive Vue event bus, eliminate direct DOM manipulations, update pricing seamlessly, and wire Buy Now / Add to Cart loading spinners.

### Task 4.1: Variant Selector Event Bus Refactoring
* **Description:** Update `<v-product-configurable-options>` in `configurable.blade.php` to emit `@variant-selected` events instead of directly manipulating DOM elements (`document.querySelector('.final-price')`).
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `HIGH`
* **Dependencies:** Phase 3 Approval Gate
* **Acceptance Criteria:** Variant option changes emit clean events caught by parent `<v-product>` Vue component, updating price reactively without console JS errors.
* **Test Method:** Playwright browser interaction test selecting color/size options.

### Task 4.2: Out-Of-Stock Swatch Visual Styling
* **Description:** Update swatch rendering matrix (Color, Image, Text) to display diagonal strike-through icon overlay and 30% opacity for out-of-stock variant options.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `LOW`
* **Dependencies:** Task 4.1
* **Acceptance Criteria:** Out-of-stock swatches are visually distinct and prevent option selection clicks.
* **Test Method:** Render configurable product with partial variant stock availability.

### Task 4.3: CTA Action & Loading State Synchronization
* **Description:** Synchronize *Buy Now* and *Add to Cart* CTA buttons with Vue `isStoring.addToCart` and `isStoring.buyNow` reactive states, activating inline white SVG spinners during Axios requests.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 4.2
* **Acceptance Criteria:** Buttons display inline loading spinners while processing; `is_buy_now = 1` triggers immediate redirect to `/checkout/onepage` upon API success.
* **Test Method:** Axios network throttling test checking button loading states and cart updates.

---

## Phase 5: Mobile Experience

**Phase Goal:** Implement responsive mobile gallery slider, construct `<v-mobile-sticky-bar>` with `IntersectionObserver` scroll sentinel, and enforce option selection validation guards.

### Task 5.1: Mobile Edge-to-Edge Touch Gallery
* **Description:** Refactor `mobile.blade.php` gallery view to use edge-to-edge container (`aspect-square w-screen`), touch-enabled swipe translates, and pagination dots.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Phase 4 Approval Gate
* **Acceptance Criteria:** Gallery swipes smoothly on touch devices without horizontal page overflow.
* **Test Method:** Mobile viewport emulation (iPhone / Android) in Chrome DevTools.

### Task 5.2: Mobile Sticky Purchase Bar Vue Component
* **Description:** Implement `<v-mobile-sticky-bar>` Vue component with `IntersectionObserver` observing primary CTA sentinel `#primary-pdp-cta-container`.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php) `[NEW]`
* **Type of Work:** `NEW`
* **Risk Level:** `HIGH`
* **Dependencies:** Task 5.1
* **Acceptance Criteria:** Sticky bar remains hidden while primary inline purchase buttons are visible; activates at `fixed bottom-0 z-50` with slide-up transition only after scrolling past the sentinel.
* **Test Method:** Playwright mobile scroll test verifying `isVisible` state changes.

### Task 5.3: Option Selection Guard for Sticky Bar
* **Description:** Add option selection validation guard to sticky purchase bar: if required configurable swatches are unselected, sticky CTA displays `"Select Options"` and scrolls up to swatch selector upon tap.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 5.2
* **Acceptance Criteria:** Prevents unvalidated cart additions from mobile sticky bar; provides instant visual feedback scrolling to missing options.
* **Test Method:** Tapping sticky CTA on unselected configurable product.

---

## Phase 6: SEO, Analytics & Performance

**Phase Goal:** Inject server-side JSON-LD rich snippets, wire GA4 and Meta Pixel analytics event bus, and achieve Lighthouse LCP < 2.5s and CLS = 0.

### Task 6.1: SEO JSON-LD Rich Snippet Schema Injection
* **Description:** Inject comprehensive JSON-LD schema array (`Product`, `Offer`, `AggregateRating`, `BreadcrumbList`) into `<head>` via Blade `@push('meta')`.
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `LOW`
* **Dependencies:** Phase 5 Approval Gate
* **Acceptance Criteria:** Generates valid, error-free JSON-LD containing product details, currency, price, availability, ratings, and breadcrumbs.
* **Test Method:** Validation via Google Rich Results Test & Schema.org validator.

### Task 6.2: GA4 & Meta Pixel Event Emitter Bus
* **Description:** Wire client-side event bus (`track-analytics-event`) to dispatch standard eCommerce events (`view_item`, `select_variant`, `add_to_cart`, `buy_now`, `wishlist_add`).
* **Affected Files:** [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) `[MODIFY]`
* **Type of Work:** `MODIFY`
* **Risk Level:** `LOW`
* **Dependencies:** Task 6.1
* **Acceptance Criteria:** Triggering purchase actions dispatches formatted GA4 eCommerce payloads and Meta Pixel tracking calls.
* **Test Method:** Console event listener verification during user interaction.

### Task 6.3: Automated Performance Audit (AC-PERF Verification)
* **Description:** Run Chrome Lighthouse performance audit to verify LCP < 2.5s and CLS = 0 on 4G network throttling.
* **Affected Files:** QA Audit Reports
* **Type of Work:** `CONFIG`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 6.2
* **Acceptance Criteria:** LCP < 2.5s, CLS = 0.00, Performance score ≥ 90.
* **Test Method:** Automated Lighthouse CLI run against local PDP URL.

---

## Phase 7: Quality Assurance & Release Certification

**Phase Goal:** Execute full desktop, mobile, LTR/RTL, and regression test suites; verify production deployment readiness.

### Task 7.1: Code Style & Pint Verification
* **Description:** Execute Laravel Pint code formatting check across all dirty workspace files.
* **Affected Files:** All PHP files in `packages/Webkul/`
* **Type of Work:** `CONFIG`
* **Risk Level:** `LOW`
* **Dependencies:** Phase 6 Approval Gate
* **Acceptance Criteria:** `vendor/bin/pint --dirty` finishes with zero style violations.
* **Test Method:** Run `vendor/bin/pint --dirty`.

### Task 7.2: Pest PHP & Playwright E2E Test Suite Execution
* **Description:** Run Pest backend feature tests and Playwright E2E browser tests across Desktop (1440px) and Mobile (375px) in English (LTR) and Arabic (RTL).
* **Affected Files:** [`packages/Webkul/Shop/tests/e2e-pw/pdp-flow.spec.ts`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/tests/e2e-pw/pdp-flow.spec.ts) `[NEW]`
* **Type of Work:** `NEW`
* **Risk Level:** `MEDIUM`
* **Dependencies:** Task 7.1
* **Acceptance Criteria:** 100% of Pest feature tests pass; 100% of Playwright E2E scenarios pass.
* **Test Method:** Run `php artisan test --compact` and `npx playwright test`.

### Task 7.3: Production Release Certification Sign-off & Blade Compilation Gate
* **Description:** Compile production Vite assets, run mandatory Blade compilation gate (`php artisan view:cache`) to prevent Laravel Directive vs Vue event shorthand collisions (e.g. `@error` vs `v-on:error`), clear application caches, and generate signed release approval document.
* **Affected Files:** Production Build Manifest (`public/themes/shop/build/`)
* **Type of Work:** `CONFIG`
* **Risk Level:** `LOW`
* **Dependencies:** Task 7.2
* **Acceptance Criteria:** Production assets compiled cleanly; `php artisan view:cache` passes with zero parse errors; zero test failures; release sign-off certified by project lead.
* **Test Method:** Inspection of release manifest, `php artisan view:cache` execution logs, and test suite logs.
* **Lesson Learned (PDP v3.0 Hotfix):** *إضافة Blade Compilation Gate قبل Production Release لمنع تعارض Laravel Directives مع Vue Event Shorthand.*

---

## Phase 8: Controlled Production Deployment

**Phase Goal:** Execute safe, zero-downtime production deployment, clear system caches, restart background queue workers, conduct live smoke testing, and maintain active rollback readiness.

### Task 8.1: Pre-Deployment Database Backup & Snapshot
* **Description:** Take full database backup and storage snapshot prior to initiating production deployment.
* **Affected Files:** Server Environment Backups
* **Type of Work:** `CONFIG`
* **Risk Level:** `HIGH`
* **Dependencies:** Phase 7 Approval Gate
* **Acceptance Criteria:** SQL database dump and storage volume snapshot verified in secure backup repository.
* **Test Method:** Database restoration integrity check on staging server.

### Task 8.2: Git Merge & Controlled Production Rollout
* **Description:** Merge `feature/pdp-rsr-v3.0` into `main` branch and trigger automated deployment pipeline.
* **Affected Files:** Production Server Codebase
* **Type of Work:** `CONFIG`
* **Risk Level:** `HIGH`
* **Dependencies:** Task 8.1
* **Acceptance Criteria:** Git merge executed without merge conflicts; deployment script executes `npm run build`, `php artisan view:clear`, `php artisan config:clear`, and `php artisan queue:restart`.
* **Test Method:** Automated deployment pipeline log verification.

### Task 8.3: Live Production Smoke Testing & Rollback Readiness
* **Description:** Execute live smoke tests across key PDP product types (Simple & Configurable) in LTR and RTL viewports.
* **Affected Files:** Production Endpoint
* **Type of Work:** `CONFIG`
* **Risk Level:** `HIGH`
* **Dependencies:** Task 8.2
* **Acceptance Criteria:** Live PDP renders with WebP images (HTTP 200), Add to Cart and Buy Now function cleanly, no JS errors in browser console. Rollback script `deploy_rollback.sh` verified on standby.
* **Test Method:** Real-time production browser verification.

---

## Execution Dashboard

*Project Lead Daily Progress & Status Tracking Table:*

| Phase | Status | Tasks Completed | Blockers | Approval Status |
| :--- | :--- | :--- | :--- | :--- |
| **Phase -1: Discovery & Code Mapping** | `NOT STARTED` | 0 / 2 | None | Pending Gate -1 Review |
| **Phase 0: Preparation & Safety** | `NOT STARTED` | 0 / 2 | None | Pending Gate 0 Review |
| **Phase 1: Backend Data Architecture** | `NOT STARTED` | 0 / 3 | None | Pending Gate 1 Review |
| **Phase 2: Image Delivery & Gallery** | `NOT STARTED` | 0 / 4 | None | Pending Gate 2 Review |
| **Phase 3: Core PDP Layout** | `NOT STARTED` | 0 / 3 | None | Pending Gate 3 Review |
| **Phase 4: Variant & Purchase UX** | `NOT STARTED` | 0 / 3 | None | Pending Gate 4 Review |
| **Phase 5: Mobile Experience** | `NOT STARTED` | 0 / 3 | None | Pending Gate 5 Review |
| **Phase 6: SEO Analytics & Perf** | `NOT STARTED` | 0 / 3 | None | Pending Gate 6 Review |
| **Phase 7: Quality Assurance** | `NOT STARTED` | 0 / 3 | None | Pending Gate 7 Review |
| **Phase 8: Production Deployment** | `NOT STARTED` | 0 / 3 | None | Pending Final Live Deployment |

*Allowed Status Values:* `NOT STARTED` | `IN PROGRESS` | `BLOCKED` | `READY FOR REVIEW` | `APPROVED` | `COMPLETED`

---

## Mandatory Approval Gates

> **STRICT GOVERNANCE RULE:**  
> *Development MUST NOT transition to a new Phase until the project lead verifies all four gate conditions for the current phase.*

### Gate Transition Criteria

1. **Task Completion:** 100% of tasks listed in the current Phase must be in `COMPLETED` status.
2. **Test Passage:** All associated automated tests (Pest / Playwright / Pint) for the phase must pass with zero failures.
3. **Artifact Review:** Phase outputs (code diffs, test logs, DevTools screenshots) must be reviewed against binding RSR v3.0 specifications.
4. **Formal Sign-off:** Project Lead must update the Execution Dashboard status to `APPROVED` before initiating the next phase.
