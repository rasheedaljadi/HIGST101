# RSR v3.0 Part 2 — Responsive & UX Specification

**Document Title:** HIGEST eCommerce Product Detail Page (PDP) Responsive & UX Specification  
**Version:** 3.0 (Part 2 — Integrated with Part 2.1 Corrections)  
**Status:** Binding UX Specification / Architecture Contract  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## 1. Executive UX Principles

The HIGEST Product Detail Page (PDP) is the core conversion engine of the HIGEST eCommerce marketplace. Built on Bagisto 2.4.x, HIGEST integrates cross-border marketplace supply (AliExpress dropshipping & direct vendor feeds) with localized retail fulfillment. 

Every design and interaction pattern on the PDP is governed by five binding executive principles:

### 1.1 Conversion-First Philosophy
* **Aggressive Visual Hierarchy:** Primary CTAs (*Buy Now* and *Add to Cart*) maintain permanent visual prominence and high-contrast color precedence across all viewports.
* **Frictionless Action Path:** Reducing cognitive steps between product interest and checkout initialization. Secondary actions (wishlist, share, compare) must never visually compete with transaction actions.

### 1.2 Mobile-First Architectural Priority
* **68%+ Traffic Optimization:** Mobile viewports (< 768px) and compact tablets (< 1180px) are treated as the primary interface standard rather than degraded desktop viewports.
* **Thumb-Zone Ergonomics:** All critical interactive controls (variant swatches, sticky CTA bar, bottom sheet selectors) reside within the natural single-handed thumb reach zone on mobile screens.

### 1.3 Trust-First Purchasing Engine
* **Overcoming Cross-Border Hesitation:** Products sourced via dropshipping/marketplace providers require immediate reassurance regarding delivery timelines, land cost transparency, return policies, and payment safety.
* **Integrated Micro-Badging:** Contextual trust signals (delivery guarantees, buyer protection, payment security badges) are embedded directly within the decision zone above and below the main CTAs.

### 1.4 Minimal Cognitive Load & Information Parsing
* **Scannable Layout & Progressive Disclosure:** Technical specifications, full buyer reviews, and expanded policy details are structured into clean, expandable tabs (Desktop) and accordions (Mobile) to prevent visual overload upon initial page mount.
* **Instant Signal Recognition:** Clear stock meters, pricing badges, and selected variant chips provide immediate feedback without reading long text strings.

### 1.5 Sub-Second Product Understanding
* **Visual-First Discovery:** High-density, high-resolution media galleries with smooth touch-carousels and vertical thumbnail streams ensure customers inspect products visually within 3 seconds of page render.
* **Synchronized State Response:** Variant color selection immediately updates the primary gallery media item, stock availability indicator, price calculation, and SKU details without any page reload.

---

## 2. Responsive Layout Specification

The HIGEST PDP responsive architecture uses Tailwind CSS breakpoints, with the primary responsive pivot set at `1180px` (`max-1180:` and `1180:` classes in Bagisto Shop theme).

```
Desktop Wide (≥1180px)    : 2-Column Side-by-Side (Gallery Left 560px | Sticky Info Right 590px)
Tablet View (768px - 1179px): Stacked / Adapted Grid (Full-width gallery top | Full-width details bottom)
Mobile View (<768px)       : 1-Column Mobile Fluid (Full-width edge-to-edge gallery | Sticky Bottom Bar)
```

---

### 2.1 Desktop Specification (≥ 1180px Viewport)

* **Grid Architecture:** 
  * Max Container Width: `1280px` centered with `px-[60px]` container padding.
  * Desktop Layout: 2-Column asymmetric grid (`gap-9` / `gap-10`).
* **Column Breakdown:**
  * **Left Gallery Column:** Fixed width layout with vertical thumbnail sidebar (`100px`) + base image wrapper (`560px` max width, `610px` max height).
  * **Right Information Column:** Flexible width panel (`max-w-[590px]`), containing title, rating summary, price block, variant selectors, quantity, and purchase buttons.
* **Sticky Purchase Panel & Scroll Behavior:**
  * Left gallery thumbnail container uses `sticky top-20` to stay locked during vertical scrolling up to the description section.
  * Right column flows naturally alongside the sticky gallery; description, additional specs, and reviews sit below the fold in a full-width centered tabbed container (`1180:mt-20`).

---

### 2.2 Tablet Specification (768px – 1179px Viewport)

* **Breakpoint Transition (`max-1180:`):**
  * Desktop vertical thumbnail gallery collapses into a touch-enabled horizontal swipe carousel (`v-product-carousel`).
  * Page layout transitions from 2-column horizontal split to a single column flow:
    1. Breadcrumbs hide (`max-lg:hidden`).
    2. Gallery occupies 100% container width.
    3. Product Information area stacks directly below the gallery with `px-5` side padding.
* **Interaction Adaptations:**
  * Hover zoom on main desktop image is disabled; tapping images triggers the full-screen modal lightbox (`x-shop::image-zoomer`).
  * Tabbed information sections (`1180:hidden`) collapse into vertical, accessible accordions (`x-shop::accordion`) for Description, Additional Specs, and Reviews.

---

### 2.3 Mobile Specification (< 768px Viewport)

* **Layout Structure:**
  * 1-Column fluid container with `px-4` / `px-3.5` mobile margins.
  * Gallery: 1:1 aspect ratio square carousel container (`w-screen aspect-square`) spanning edge-to-edge.
* **Sticky Bottom Purchase Bar (`v-mobile-sticky-bar`):**
  * Persistent fixed bottom bar (`fixed bottom-0 left-0 right-0 z-50 bg-white border-t p-3 shadow-lg flex items-center gap-3`).
  * Displays truncated product price + primary *Buy Now* / *Add to Cart* CTA buttons.
  * Appears automatically once the inline purchase buttons scroll out of view.
  * **Layering & Collision Rule:** Must NOT obscure cookie banners, bottom sheet selectors, checkout drawers, or toast notifications.
* **Thumb-Friendly Touch Interactions:**
  * All swatch selectors maintain a minimum touch target size of `44x44px`.
  * Horizontal variant swatch lists wrap cleanly without overflowing screen boundaries.
  * Sheet overlay modal pattern used for complex options on mobile viewports.

---

## 3. Product Gallery UX Specification

The gallery implementation sits inside `packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php` and communicates via Vue `v-product-gallery`.

```
Desktop Gallery Layout (≥ 1180px):
+-------------------------------------------------------------+
| [^] Up Arrow                                               |
| +-------+  +----------------------------------------------+ |
| |Thumb 1|  |                                              | |
| +-------+  |                                              | |
| |Thumb 2|  |                                              | |
| +-------+  |              MAIN DISPLAY IMAGE              | |
| |Thumb 3|  |                (560px x 610px)               | |
| +-------+  |                                              | |
| |Thumb 4|  |                                              | |
| +-------+  |                                              | |
| [v] Down   +----------------------------------------------+ |
+-------------------------------------------------------------+

Mobile Gallery Layout (< 1180px):
+-------------------------------------------------------------+
|                                                             |
|                   FULL WIDTH SWIPE CAROUSEL                 |
|                        (1:1 Square)                         |
|                                                             |
|                   ( o  O  o  o ) Pagination Dots            |
+-------------------------------------------------------------+
```

---

### 3.1 Main Image & Video Experience

* **Loading Behavior:**
  * Initial render displays a subtle skeleton shimmer placeholder (`x-shop::shimmer.products.gallery`) matching the exact pixel dimensions to avoid Layout Shift (CLS).
  * Main image Tag uses `fetchpriority="high"` and pre-calculated aspect ratio.
* **Image Switching:**
  * Clicking any thumbnail instantly updates `baseFile.path` and smooth-fades the main container (`duration-200`).
* **Zoom & Fullscreen:**
  * Desktop: Tapping/clicking the main image opens the full-screen lightbox (`v-gallery-zoomer`) with drag-to-pan zoom capability (`cursor-zoom-in` / `cursor-grab`).
  * Mobile: Tapping any carousel image opens the full-screen touch zoom modal.
* **Video Playback Integration:**
  * Videos are rendered inline inside the main display container (`<video controls>`). Selecting a video thumbnail automatically sets `baseFile.type = 'video'`.
* **Error Fallback:**
  * If a remote image fails to load (e.g. broken dropship image URL), the system gracefully renders `vendor/webkul/ui/assets/images/product/meduim-product-placeholder.webp`.

---

### 3.2 Thumbnail Experience

* **Desktop Vertical Thumbnails (`desktop.blade.php`):**
  * Rendered as a left-aligned vertical scroll column (`max-w-[100px]`, `max-h-[540px]`).
  * Up/Down navigation arrows (`icon-arrow-up`, `icon-arrow-down`) appear dynamically when `media.images.length + media.videos.length > 5`.
  * Active thumbnail receives a high-contrast border ring (`border-navyBlue`).
* **Mobile Horizontal Carousel (`mobile.blade.php`):**
  * Full width swipe container using CSS translation with `touchmove` / `touchend` gesture handling.
  * Bottom pagination dots (`bg-navyBlue` for active, `opacity-30` for inactive) indicate total slide index.

---

### 3.3 Variant Image Synchronization

When a customer selects a variant option (e.g. Color = "Midnight Blue"):

```
Customer Clicks Color Swatch 
  │
  ▼
`configure(attribute, optionId)` Triggered in `v-product-configurable-options`
  │
  ▼
Finds Matching Child SKU / Variant ID
  │
  ▼
Calls `reloadImages()` 
  │
  ▼
Updates `galleryImages` Array & Emits `configurable-variant-update-images-event`
  │
  ▼
`v-product-gallery` Catches Event & Replaces `media.images` Stack
  │
  ▼
Main Image Instantly Swaps to Target Color Image (No Page Refresh)
```

---

## 4. Product Information Hierarchy

Elements on the HIGEST PDP right column are ordered according to conversion impact and user cognitive scan patterns:

```
[1]  Product Title (H1) + Wishlist Heart Icon
[2]  Rating & Reviews Counter Badge (Scroll Anchor)
[3]  Social Proof & Sales Counter ("124 bought in last 24h")
[4]  Price Block (Current Price, Strikethrough MSRP, Discount % Badge, Tax Label)
[5]  Promotional Badge (Optional - Controlled by HIGEST Promotion Engine)
[6]  Variant Selector Area (Color Swatches, Size Chips, Dropdowns)
[7]  Stock Status & Inventory Urgency Indicator
[8]  Quantity Selector + Add To Cart Button + Buy Now Button
[9]  Trust Badges & Dropshipping Fulfillment Transparency Block
[10] Collapsible Product Details / Short Description
[11] Specifications & Attribute Matrix Tab / Accordion
[12] Customer Reviews & Photo Gallery Block
[13] Related & Cross-Sell Product Carousels
```

---

### Rationale for Hierarchy Order:

1. **Title & Wishlist (Top):** Instant confirmation that the customer landed on the exact product. Wishlist positioned top-right for quick bookmarking.
2. **Ratings & Social Proof:** Builds immediate validation before the customer evaluates cost.
3. **Pricing & Discount:** High visual weight (`text-3xl font-medium`). Discount percentage pill (e.g. `-35%`) creates perceived value.
4. **Promotional Badge (Optional):** Rendered only if an active promotion rule exists in `Webkul\CatalogRule` / `Webkul\CartRule`.
5. **Variant Selectors:** Essential choices required before purchase can take place.
6. **Stock Indicator:** Urgency trigger directly above action buttons.
7. **Primary CTAs:** Clear, un-obstructed transaction zone.
8. **Trust Layer & Dropshipping Transparency:** Directly below CTAs to reassure cross-border buyers on shipping timeline, origin, tracking, and local returns.
9. **Description, Specs & Reviews (Bottom):** Detailed technical validation for high-involvement buyers.

---

## 5. Purchase Conversion Experience

```
+-------------------------------------------------------------+
| QUANTITY SELECTOR           ADD TO CART BUTTON             |
| [ - |  1  | + ]            [ 🛒 Add to Cart ]             |
+-------------------------------------------------------------+
| BUY NOW BUTTON                                              |
| [ ⚡ Buy Now - Direct Checkout ]                            |
+-------------------------------------------------------------+
```

---

### 5.1 Add To Cart (ATC)

* **Button Placement & Styling:**
  * Desktop: Full-width within action block (`max-w-[470px]`), styled as secondary high-visibility button (`secondary-button`, dark border / neutral fill).
  * Mobile: 50% width inside sticky bottom bar.
* **Loading & Feedback Flow:**
  * Tapping ATC triggers an immediate button inline spinner (`::loading="isStoring.addToCart"`).
  * On success response from `shop.api.checkout.cart.store`:
    1. Emits `update-mini-cart` event to update header cart count badge instantly.
    2. Emits `add-flash` event triggering a green success notification toast: *"Product added to cart successfully."*
    3. Mini-cart side drawer automatically slides open for 3 seconds to confirm insertion.

---

### 5.2 Buy Now

* **Priority & Visual Dominance:**
  * Styled with primary brand color (`primary-button bg-navyBlue text-white`), positioned directly beneath ATC with `mt-5`.
  * Explicit visual emphasis to encourage single-item rapid checkout.
* **Checkout Transition:**
  * Sets `is_buy_now = 1` hidden input field before form submission.
  * Submits payload directly to cart API; API response includes `redirect: "/checkout/onepage"`.
  * Customer is immediately redirected to checkout, bypassing cart page.

---

### 5.3 Quantity Selector

* **Placement & Component Spec:**
  * Uses `x-shop::quantity-changer` Vue component positioned side-by-side with ATC.
  * Controls: Rounded stepper buttons (`icon-minus`, `icon-plus`) with center numeric readout.
* **Rules & Stock Safeguards:**
  * Minimum value fixed at `1`.
  * Increment blocked if quantity reaches total available inventory for selected variant.
  * Input field enforces integer validation.

---

## 6. Trust Layer & Dropshipping Transparency UX

To ensure maximal buyer confidence on dropshipped & marketplace items, HIGEST embeds a dedicated **Trust & Fulfillment Transparency Block** immediately below the checkout action buttons.

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

---

### Required Trust & Transparency Matrix:

| Trust / Transparency Element | Visual Presentation | Location & Priority | Contextual Logic |
| :--- | :--- | :--- | :--- |
| **Shipping Estimate** | Truck Icon + Date Range (e.g. "Delivered in 5-8 days") | Directly below CTAs (Priority 1) | Calculated dynamically based on customer shipping destination & supplier dispatch rules. |
| **Delivery Guarantee** | Shield Icon + "On-Time Delivery Guarantee" | Inside Shipping Box (Priority 1) | Rendered only if enabled and supported by HIGEST policy engine. |
| **Supplier Origin Transparency** | Origin Pin + "International Overseas Warehouse" | Inside Fulfillment Box (Priority 1) | Clarifies cross-border sourcing so customers have realistic delivery expectations. |
| **Parcel Tracking Reassurance** | Package Icon + "End-to-End Tracking Provided" | Inside Fulfillment Box (Priority 2) | Assures buyer that tracking code will be attached to order history upon dispatch. |
| **Local Return Policy** | Rotate-Left Icon + "14-Day Local Return Processing" | Micro trust row below CTAs (Priority 2) | Reassures buyer that returns are processed locally by HIGEST RMA, not shipped abroad. |
| **Payment Security** | Lock Icon + Payment Logos (Visa, Mastercard, Mada, Apple Pay) | Footer of purchase area (Priority 2) | Confirms safe 256-bit SSL encrypted transaction processing. |
| **Supplier Profile Info** | Store Icon + Supplier Name + Verification Badge | Accordion / Sub-Title Area | Displays verified supplier credentials for marketplace transparency. |

---

## 7. Variant Selection UX

Variant selection sits inside `packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php`.

```
Color (Swatch Type: Color / Image):
(•) Red   ( ) Blue   ( ) Black  [Image Thumbnail Swatches]

Size (Swatch Type: Text):
[ S ]   [ M ]   [ L ]   [ XL (Disabled / Out of Stock) ]
```

---

### 7.1 Variant Handling by Product Type

* **Simple Products:** Displays static stock badge ("In Stock" / "Out of Stock") without selection controls.
* **Configurable Products:** Renders dynamic `childAttributes` loop for multi-axis selection (Color, Size, Material).

---

### 7.2 Swatch Types & State Matrix

1. **Color Swatch (`swatch_type == 'color'`):**
   * Circular color pills (`h-8 w-8 rounded-full border`).
   * Selected State: Double ring indicator (`ring-2 ring-gray-900 border-white`).
2. **Image Swatch (`swatch_type == 'image'`):**
   * Small square thumbnail tiles (`h-[60px] w-[60px] rounded-md border`).
   * Selected State: Bold border (`border-navyBlue`).
3. **Text Swatch (`swatch_type == 'text'`):**
   * Pill buttons (`rounded-full px-5 py-3 border`).
   * Selected State: Solid dark fill (`!bg-navyBlue text-white`).

---

### 7.3 State Logic & Attribute Locking

* **Disabled / Unavailable State:** Options not present in valid child SKUs are styled with low opacity (`opacity-40 cursor-not-allowed`) and diagonal strike lines.
* **Out of Stock Variant State:** When a valid combination exists but stock is `0`, swatch remains selectable, but primary CTA switches to disabled state reading *"Out of Stock"*, and a *"Notify Me When Available"* button replaces *Buy Now*.
* **Synchronization Trigger:** Selection calls `configure(attribute, optionId)` -> recalculates allowed child options for dependent attributes -> updates pricing display -> syncs gallery images.

---

## 8. Mobile Conversion Optimization

Mobile UX is tailored to match top-tier marketplace conversion benchmarks (AliExpress, SHEIN, Trendyol):

```
Mobile Screen Bottom View:
+-------------------------------------------------------------+
|              [ PRODUCT CONTENT & REVIEWS ]                  |
|                                                             |
+-------------------------------------------------------------+
| STICKY BOTTOM BAR (Fixed - Z-Index 50)                      |
| $49.99 (-20%)  |  [ Add to Cart ]  |  [ ⚡ Buy Now ]        |
+-------------------------------------------------------------+
```

---

### Mandatory Mobile Features:

1. **Sticky Bottom Purchase Bar (`v-mobile-sticky-bar`):**
   * Fixed at `bottom-0`, viewport full width, `z-index: 50`.
   * Contains price summary + dual action buttons.
   * **Collision Rules:** MUST NOT obscure cookie consent banners, mobile bottom sheets, checkout drawers, or toast alerts.
   * Auto-hides when user reaches full checkout form or footer.
2. **Bottom Sheet Selector Pattern:**
   * Tapping *Buy Now* or *Add to Cart* on mobile when mandatory variants are unselected slides up a **Mobile Bottom Sheet Overlay** (`z-index: 500`).
   * Sheet presents swatch options with large tap areas + confirmation CTA button without forcing user to scroll up to top of page.
3. **Edge-to-Edge Touch Gallery:**
   * Native smooth touch swipe carousel with lazy-loaded slide images.
4. **Floating Secondary Actions:**
   * Floating overlay back button and wishlist heart icon fixed over top of main image gallery.

---

## 9. Loading and Interaction States

Every state transition on the PDP must provide deterministic feedback to eliminate layout shifts and ambiguous user states:

```
[ State ]             [ Visual Response ]
Initial Page Load --> Full Shimmer Skeleton (`x-shop::shimmer.products.view`)
Variant Click     --> Instant Price & Image Loading Overlay Spinner
Add to Cart Click --> ATC Button Loading State (`isStoring.addToCart = true`) -> Flash Toast -> Drawer Open
Out of Stock      --> Buttons Disabled -> "Out of Stock" Text -> Notify Me Button
API Error         --> Red Flash Alert Banner ("Selected variant configuration is unavailable")
```

---

### Detailed Interaction Specifications:

* **Initial Mount:** Page shell loads with full shimmering skeletal layout (`shimmer bg-zinc-200`) mimicking title, price, gallery, and buttons until Vue components hydrate.
* **Image Loading:** Base gallery container displays micro-shimmer until `@load` event triggers `isMediaLoading = false`.
* **Price Calculation:** Price block updates instantly without layout jump when valid variant combination is chosen.
* **Error Handling:** Form validation errors (e.g. unselected size) render clean inline red text (`v-error-message`) directly under the offending attribute swatch row.

---

## 10. UX Acceptance Criteria

To ensure implementation compliance, the finalized PDP must pass these measurable acceptance tests:

### 10.1 Gallery Acceptance Tests
* **AC-GAL-01:** Upon PDP initial open, the main product image must render high-priority visual content within **1.2 seconds** on standard 4G connections.
* **AC-GAL-02:** Clicking any gallery thumbnail must update the main display image in under **100ms**.
* **AC-GAL-03:** On mobile screens, swiping the gallery carousel must track touch position at **60fps** with smooth snap pagination.

### 10.2 Variant Selection Acceptance Tests
* **AC-VAR-01:** Selecting a color swatch must update both product pricing and primary gallery image stack without performing a full or partial browser page reload.
* **AC-VAR-02:** Attempting to submit *Add to Cart* without selecting required variant options must focus and highlight the unselected variant section with a clear validation error.
* **AC-VAR-03:** Disabling logic must immediately grey out invalid size/color permutations based on actual backend variant inventory data.

### 10.3 Conversion & Mobile Acceptance Tests
* **AC-CON-01:** Clicking *Add to Cart* must update the header cart quantity badge instantly and open the mini-cart drawer without navigating away from the page.
* **AC-CON-02:** On mobile screens, scrolling down past the fold must trigger the sticky bottom purchase bar smoothly within **150ms**.
* **AC-CON-03:** Clicking *Buy Now* must bypass the shopping cart page and land the user directly on `/checkout/onepage` with the selected item pre-loaded.

### 10.4 Performance & Web Vitals Acceptance Tests
* **AC-PERF-01:** **Largest Contentful Paint (LCP)** on mobile 4G throttling must be **< 2.5 seconds**.
* **AC-PERF-02:** **Cumulative Layout Shift (CLS)** caused by gallery loading or Vue component hydration must be **0**.
* **AC-PERF-03:** The core product decision block (Title, Price, Variants, CTAs) must be interactive before secondary async payloads (Reviews, Related products) finish loading.

---

## 11. Implementation Constraints

This specification is tightly bound to HIGEST's existing tech stack and architectural choices:

* **Framework Engine:** Native Bagisto 2.4.x theme layout engine (`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`).
* **Frontend Architecture:** Vue 3 Options API embedded inside Laravel Blade templates via script tags (`app.component('v-product', ...)`). All new interaction logic must extend existing Vue components without breaking blade event hooks (`view_render_event`).
* **Styling System:** Vanilla Tailwind CSS with custom Bagisto theme tokens (NavyBlue `#060C3B`, zinc color scales, responsive utility classes).
* **Media Pipeline:** Integration with Bagisto `ProductImageRepository` webp caching pipeline. Gallery media must ingest raw imported AliExpress asset URLs gracefully.
* **AliExpress Dropshipping Sync:** Configurable products generated by the `AliExpressProductImporter` service must align with Bagisto's EAV attribute family structures and variant lookup maps (`ResolvedAxes` DTO).

---

## Document Authorization

**Prepared By:** Lead Product Architect & UX Systems Designer  
**Approved For Implementation:** HIGEST Core Platform Engineering Team  
**Target Delivery Phase:** RSR v3.0 Execution Cycle (Ready for RSR v3.0 Part 3 — Visual Design System)
