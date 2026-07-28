# RSR v3.0 Part 3.1 — Visual Design System Review Corrections & Data Rendering Matrix

**Document Title:** RSR v3.0 Part 3.1 — Visual Design System Review Corrections & Data Rendering Matrix  
**Version:** 3.0 (Part 3.1 Addendum)  
**Status:** Binding Specification Addendum / Approved with Corrections  
**Parent Document:** [`RSR_v3.0_Part3_Visual_Design_System_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3_Visual_Design_System_Specification.md)  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## Executive Context

Following review and conditional approval (🟡 **Approved with Minor Amendments**) of **RSR v3.0 Part 3 — Visual Design System Specification**, this addendum formalizes four critical operational and technical leadership amendments. These amendments refine the visual frontend design contract into an unshakeable, production-ready specification before proceeding to **Part 4 — Technical Implementation**.

This document directly addresses:
1. **Gallery Architecture & Image Cache Fallback Strategy** (Eliminating broken `/cache/` images).
2. **Product Data & Rendering State Matrix** (Handling incomplete, out-of-stock, or pending supplier data).
3. **Mobile Sticky Bar Scroll Trigger Strategy** (Preventing redundant CTAs on small screens).
4. **Image Performance Rules** (Guaranteeing LCP < 2.5s and CLS = 0).

---

## 1. Gallery Architecture & Image Cache Fallback Strategy

### 1.1 Architectural Decision: Dynamic ImageCache with Preset Fallback

To avoid storage bloat across thousands of dropshipped SKUs while guaranteeing robust rendering, HIGEST adopts a **Hybrid Image Cache Architecture**:

* **Primary Mechanism:** Image rendering uses Bagisto's dynamic image caching pipeline (`Webkul\ImageCache`), generating on-demand web-optimized images in sizes:
  * `large`: 560px × 610px (Main Gallery Display)
  * `medium`: 300px × 300px (Grid Cards & Mobile Carousels)
  * `small`: 100px × 100px (Gallery Thumbnails)
* **Storage Location:** Cached assets reside in `storage/app/public/cache/{preset}/product/{id}/{filename}.webp`.

### 1.2 Uncompromising Fallback Mandate

> **MANDATORY SYSTEM RULE:**  
> *The Product Detail Page (PDP) must NEVER attempt to display a non-existing physical cache asset without an automatic multi-tier fallback strategy.*

### 1.3 Multi-Tier Fallback Mechanism

```
                       [ Image Request ]
                              │
               ┌──────────────┴──────────────┐
               ▼                             ▼
   [ Cache File Exists? ]          [ Cache File Missing ]
               │                             │
        (200 OK Render)              ┌───────┴───────┐
                                     ▼               ▼
                           [ On-the-Fly Gen ]  [ Source Direct ]
                                     │               │
                              (Success Render)   (Fallback 1)
                                                     │
                                             ┌───────┴───────┐
                                             ▼               ▼
                                      [ Original File ] [ Placeholder ]
                                         (Fallback 1)    (Fallback 2)
```

1. **Backend Layer (PHP / Laravel `ImageCache` Controller):**
   * If the requested cache path does not exist on disk, the custom controller interceptor must attempt on-the-fly generation.
   * If generation fails (e.g., source file permission issue on Windows or corrupt remote asset), the controller must stream the original uploaded image file directly (`storage/app/public/product/...`) with HTTP 200 rather than throwing an HTTP 404 image break.

2. **Frontend Layer (Blade & Vue 3 Image Handling):**
   * All gallery `<img>` elements must implement explicit error handlers:
     ```html
     <img 
         src="{{ $productImage->url('large') }}" 
         alt="{{ $product->name }}"
         @error="$event.target.src = '{{ $productImage->original_url ?? asset('themes/higest/assets/images/placeholder-product.webp') }}'"
         class="rounded-xl object-cover w-full h-full"
     />
     ```
   * A fallback default placeholder image (`placeholder-product.webp`) styling strictly using HIGEST Navy and Zinc Slate must be embedded in theme assets.

---

## 2. Product Data & Rendering State Matrix

To prevent UI breakdown when product data is incomplete, processing, or restricted by dropshipping suppliers, the PDP frontend must adhere to 6 deterministic rendering states:

| Rendering State | Triggers / Conditions | Visual UI Behavior | Pricing & Badge State | CTA Button Behavior | Dropshipping Transparency Box |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **1. Fully Available** | `status = 1`, `in_stock = true`, images ready, supplier verified. | Complete standard PDP view. | Full price, strikethrough MSRP, savings badge. | **Active** (*Buy Now* & *Add to Cart* fully enabled). | Fully visible with active transit estimates. |
| **2. Out Of Stock** | `status = 1`, total `qty = 0`. | Gallery & details visible. Stock meter turns red (`text-red-500`). | Normal price visible. Strikethrough visible. | **Disabled CTA:** Replaced by disabled button *"Out of Stock"* or *"Notify Me When Available"*. | Visible with note: *"Restocking from supplier"*. |
| **3. Limited Stock** | `status = 1`, `qty <= 5`. | Amber urgency pill: *"Only {qty} left in stock - order soon"*. | Price visible with pulse highlight on savings badge. | **Active** with subtle pulsing border on *Buy Now*. | Visible with express dispatch tag. |
| **4. Pending Media Processing** | Product imported from supplier (AliExpress), but high-res media is background caching. | Main image displays high-res shimmer skeleton; thumbnail bar displays progress indicator. | Price visible. Strikethrough visible. | **Active** (*Add to Cart* enabled using raw supplier preview). | Visible with tag: *"Catalog Syncing"*. |
| **5. Supplier Unavailable** | Cross-border supplier API feed offline or price sync failed. | Soft banner alert on top of page: *"Supplier data currently updating"*. | Price hidden or replaced with *"Price Updating"*. | **Disabled CTA:** Replaced by *"Temporarily Unavailable"*. | Retains return policy; transit window updated to *"Sync Pending"*. |
| **6. Disabled Product** | `status = 0` or deleted SKU. | 404 / 410 Graceful layout or auto-redirect to parent category with toast notice. | Hidden. | Hidden. | Hidden. |

---

## 3. Mobile Sticky Purchase Bar Trigger Strategy

### 3.1 Dual-CTA Avoidance & Scroll Trigger Rule

On mobile viewports (< 768px), rendering two identical purchase button blocks simultaneously (inline on the page + sticky at the bottom) degrades user experience and clutter screen space.

```
Mobile Scroll Position Behavior:

+-----------------------------------+
| Top of Page -> Primary CTA Zone   |  ===> Mobile Sticky Bar: HIDDEN (Retracted)
| (Inline CTA buttons visible)      |       `translate-y-full opacity-0`
+-----------------------------------+
| Scrolled Past Primary CTA Zone    |  ===> Mobile Sticky Bar: ACTIVATED (Visible)
| (Inline CTA scrolled out of view) |       `translate-y-0 opacity-100 z-50`
+-----------------------------------+
```

### 3.2 Technical Implementation via `IntersectionObserver`

1. **Trigger Element:** The main product card CTA container (`#primary-pdp-cta-container`) acts as the sentinel element.
2. **Observer Logic:**
   * When `#primary-pdp-cta-container` is **intersecting** the mobile viewport: `isStickyBarVisible = false`.
   * When `#primary-pdp-cta-container` is **scrolled out of view** (above viewport): `isStickyBarVisible = true`.
3. **Transition Animation:**
   * Tailwind CSS transition: `transform transition-transform duration-300 ease-in-out`.
   * Retracted state: `translate-y-full opacity-0 pointer-events-none`.
   * Active state: `translate-y-0 opacity-100 pointer-events-auto fixed bottom-0 left-0 right-0 z-50`.

### 3.3 Layering Stack Re-Confirmation

```
[ Z-Index 1000 ] : Full-Screen Gallery Lightbox (`v-gallery-zoomer`)
[ Z-Index 500  ] : Mobile Bottom Sheet Option Selector
[ Z-Index 100  ] : Slide-over Drawers & Checkout Overlays
[ Z-Index 50   ] : Mobile Sticky Purchase Bar (`v-mobile-sticky-bar`) - Active only post-scroll
[ Z-Index 40   ] : Cookie Consent / GDPR Compliance Banner
```

---

## 4. Image Performance Rules (AC-PERF-IMG)

Product Detail Pages are the most image-heavy pages in the HIGEST marketplace. To maintain sub-2.5s LCP and zero layout shift, all theme templates must comply with the following image loading rules:

### 4.1 Performance Optimization Rules Matrix

| Target Image Component | Loading Strategy | Priority Attribute | Format & Aspect Constraints | Target Load Window |
| :--- | :--- | :--- | :--- | :--- |
| **Main Product Gallery Image** | `loading="eager"` | `fetchpriority="high"` + `<link rel="preload">` in `<head>` | WebP Mandatory. Fixed aspect box `560x610` (`aspect-[560/610]`). | **< 1.8s** (LCP Candidate) |
| **Gallery Thumbnails (1-4)** | `loading="lazy"` | `fetchpriority="auto"`, `decoding="async"` | WebP Mandatory. `100x100` (`aspect-square`). | **< 2.5s** |
| **Variant Swatch Thumbnails** | `loading="lazy"` | `decoding="async"` | WebP / Compressed PNG. `60x60` (`aspect-square`). | Non-blocking async |
| **Review Photo Attachments** | `loading="lazy"` | `decoding="async"` | WebP. `64x64` thumbnail (`aspect-square`). | Scroll-triggered lazy load |
| **Related & Up-sell Carousels**| `loading="lazy"` | `decoding="async"` | WebP. `300x300` grid cards (`aspect-square`). | Below-the-fold lazy load |

### 4.2 Cumulative Layout Shift (CLS = 0) Prevention Code Pattern

Every image container on the PDP must enforce hard aspect ratios using Tailwind CSS or inline width/height tags to reserve vertical layout space before the image binary completes downloading:

```html
<!-- Main Image Aspect Reserved Wrapper -->
<div class="relative w-full aspect-[560/610] min-h-[500px] max-w-[560px] rounded-xl overflow-hidden bg-zinc-100">
    <img 
        src="{{ $productImage->url('large') }}" 
        alt="{{ $product->name }}"
        width="560"
        height="610"
        loading="eager"
        fetchpriority="high"
        class="w-full h-full object-cover transition-opacity duration-300"
    />
</div>
```

---

## RSR v3.0 Specification Hierarchy & Authorization Status

With the release and integration of **Part 3.1**, the complete architectural specification suite for HIGEST PDP is structured as follows:

| Document | Title / Scope | Status |
| :--- | :--- | :--- |
| **Part 1** | Functional Specification | ✅ Approved |
| **Part 1 Addendum** | HIGEST Refinements | ✅ Approved |
| **Part 2** | Responsive UX Specification | ✅ Approved |
| **Part 2.1** | UX Review Corrections & Business Realignment | ✅ Approved |
| **Part 3** | Visual Design System Specification | ✅ Approved |
| **Part 3.1** | **Visual Design System Review Corrections & Data Rendering Matrix** | ✅ **Approved (This Document)** |
| **Part 4** | Technical Implementation & Bagisto Integration Specification | 🔜 **NEXT PHASE** |

---

**Approved By:** HIGEST Core Platform Engineering Team  
**Status:** Binding Specification Addendum (Part 3 Phase Fully Sealed — Ready for Part 4 Technical Implementation)
