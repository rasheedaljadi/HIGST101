# RSR v3.0 Part 4 — Technical Implementation & Architecture Specification

**Document Title:** RSR v3.0 Part 4 — Technical Implementation & Architecture Specification  
**Version:** 3.0 (Part 4 Final Engineering Contract)  
**Status:** Binding Technical Specification / Implementation Contract  
**Parent Specifications:**  
- [`RSR_v3.0_Part1_Functional_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part1)  
- [`RSR_v3.1_HIGEST_Refinements.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.1_HIGEST_Refinements)  
- [`RSR_v3.0_Part2_Responsive_UX_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part2_Responsive_UX_Specification.md)  
- [`RSR_v3.0_Part2.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part2.1_Review_Corrections.md)  
- [`RSR_v3.0_Part3_Visual_Design_System_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3_Visual_Design_System_Specification.md)  
- [`RSR_v3.0_Part3.1_Review_Corrections.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part3.1_Review_Corrections.md)  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## Executive Context & Purpose

This document serves as the **final, binding engineering contract** for implementing the HIGEST Product Detail Page (PDP). It translates business requirements, responsive UX rules, and the premium visual design system into exact, file-level code modifications across Bagisto 2.4.x, Laravel 11, Vue 3, and TailwindCSS.

Every specification herein is derived directly from an empirical audit of the HIGEST codebase (`packages/Webkul/*`). Engineers must execute these modifications without architectural ambiguity or unapproved assumptions.

---

## PART 1 — Implementation Architecture Overview

### 1.1 PDP Rendering Architecture

HIGEST implements a **Hybrid SSR/Client-Hydrated Architecture**. Initial HTML, SEO meta, schema markup, and critical DOM structure are pre-rendered server-side via Laravel Blade. Interactive layers (gallery zoomers, variant option state resolution, cart/wishlist async mutations, mobile sticky bar visibility, and carousel intersection observers) hydrate on the client via Vue 3 components mounted inside Blade templates.

```
+-----------------------------------------------------------------------------------+
| SERVER-SIDE (Laravel 11 / Bagisto 2.4.x)                                         |
|                                                                                   |
|  [ HTTP Request: /products/{url_key} ]                                           |
|                     │                                                             |
|                     ▼                                                             |
|  ProductsCategoriesProxyController::index()                                       |
|                     │                                                             |
|                     ▼                                                             |
|  ProductRepository::findBySlug() ──► Product & ProductFlat Eloquent Models        |
|                     │                                                             |
|                     ▼                                                             |
|  Blade Template Hierarchy: shop::products.view                                    |
|   ├── SEO Meta & JsonLd (Pre-rendered)                                            |
|   ├── <x-shop::layouts> Page Wrapper                                              |
|   └── Inline Vue Template Scripts (<script type="text/x-template">)                |
+-----------------------------------------------------------------------------------+
                                      │
                               (HTML Payload)
                                      │
                                      ▼
+-----------------------------------------------------------------------------------+
| CLIENT-SIDE (Vue 3 / Vite Hydration)                                              |
|                                                                                   |
|  [ Vue 3 Root App Hydration ]                                                     |
|                     │                                                             |
|                     ├─► v-product (Master Form, State, Cart/Wishlist Async AXIOS) |
|                     ├─► v-product-gallery (Image Base, Zoomer, Thumbnails)         |
|                     ├─► v-product-configurable-options (Swatch State & Dynamic)  |
|                     ├─► v-mobile-sticky-bar (IntersectionObserver & Scroll Trigger)  |
|                     └─► v-product-associations (IntersectionObserver Carousel)    |
+-----------------------------------------------------------------------------------+
```

### 1.2 Server-Side vs Client-Side Boundaries

| Architectural Layer | Server-Side (Blade / PHP) | Client-Side (Vue 3 / JS) |
| :--- | :--- | :--- |
| **SEO & OpenGraph** | Meta title, description, JSON-LD rich snippets, canonical URLs. | None. |
| **Product Data Initialization** | Initial product ID, name, base price HTML, short description, static attributes. | JSON serialization of variants (`variant_prices`, `variant_images`), initial gallery array. |
| **Interactivity & State** | Static HTML fallbacks (`<x-shop::shimmer>`). | Variant swatch selection, price reloading, image gallery switching, cart storing spinners. |
| **API Async Operations** | CSRF tokens, session verification, route generation. | Axios requests to cart (`shop.api.checkout.cart.store`), wishlist, compare, and reviews. |

### 1.3 Data Flow Diagram

```
Product Model (Webkul\Product\Models\Product)
      │
      ▼
Product Repository (Webkul\Product\Repositories\ProductRepository)
      │
      ▼
PDP Proxy Controller (Webkul\Shop\Http\Controllers\ProductsCategoriesProxyController)
      │
      ▼
Blade Main View (packages/Webkul/Shop/src/Resources/views/products/view.blade.php)
      │
      ├───────────────────────────────┬───────────────────────────────┐
      ▼                               ▼                               ▼
v-product Component          v-product-gallery              v-product-configurable-options
  (Master CTA & State)       (Media & Zoomer)               (Swatches & Variant Sync)
      │                               │                               │
      ├───────────────────────────────┴───────────────────────────────┤
      ▼                                                               
Axios Async Endpoint Interceptor (Cart, Wishlist, Supplier Verification)
```

---

## PART 2 — Existing Code Audit

Based on empirical inspection of `packages/Webkul/`, the following audit documents the current state, identified structural flaws, and required engineering changes:

### 2.1 Proxy & Product Controllers

#### [ProductsCategoriesProxyController.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php)
* **Current State:** Routes `/products/{slug}` or `{url_key}`. Validates `url_key`, `visible_individually`, and `status`. Returns `view('shop::products.view', compact('product'))`.
* **Problem:** Does not pass supplier dropshipping fulfillment metadata, warehouse origin, or cache fallback status to the Blade view.
* **Required Change:** Extend controller response data to pass structured dropshipping transparency object (`supplier_origin`, `estimated_delivery`, `rma_policy_days`) and register cache health validation.

### 2.2 Blade Templates

#### 1. [view.blade.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php)
* **Current State:** 854-line template containing `<v-product>` inline script, tab containers, description accordions, and `<v-product-associations>`.
* **Problem:** Monolithic inline script mixes wishlist checking, compare operations, contact-us modals, and cart handling. Lacks container for the dropshipping transparency card and mobile sticky bar trigger sentinel.
* **Required Change:** Inject `x-shop::products.dropshipping-transparency` component into details column; add `<v-mobile-sticky-bar>` component with IntersectionObserver sentinel `#primary-pdp-cta-container`.

#### 2. [gallery.blade.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php) & Sub-views ([desktop.blade.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php), [mobile.blade.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php))
* **Current State:** Renders `<v-product-gallery>` with inline template `#v-product-gallery-template`.
* **Problem:** Images lack hardcoded `width` and `height` attributes causing Cumulative Layout Shift (CLS). Gallery images request `/cache/large/...` directly without inline `@error` fallback attributes.
* **Required Change:** Add `aspect-[560/610]` containers, `width="560" height="610"`, `fetchpriority="high"`, and `@error` fallback binding.

#### 3. [configurable.blade.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php)
* **Current State:** Renders `<v-product-configurable-options>` handling swatches and variant option matrices.
* **Problem:** Direct DOM manipulation (`document.querySelector('.final-price').innerHTML = ...`) breaks Vue reactivity and causes JS errors if price markup elements change. Swatches lack disabled diagonal strikethrough icons for out-of-stock variants.
* **Required Change:** Refactor variant price reloading to emit reactive events (`@variant-selected`) caught by `v-product`, updating price via Vue data models instead of direct DOM manipulation.

### 2.3 Image Pipeline Handler

#### [ProductImage.php](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/ProductImage.php)
* **Current State:** `getCachedImageUrls()` builds URLs pointing to `url('cache/large/'.$path)`.
* **Problem:** If cached files fail to generate on disk (e.g. Windows permissions or web server rewrite failure), the browser receives an HTTP 404 image link.
* **Required Change:** Update `getCachedImageUrls()` to include an explicit `original_fallback_url` parameter pointing directly to `Storage::url($path)`, and update `getGalleryImages()` to verify file existence or trigger instant cache synthesis.

---

## PART 3 — Component Implementation Map

The 15 core design system components are mapped to technical components and physical codebase locations:

| # | Design System Component | Technical Component / Strategy | Physical File Location |
| :- | :--- | :--- | :--- |
| **1** | **Product Gallery** | Vue Component `<v-product-gallery>` | [`packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php) |
| **2** | **Image Zoom** | Blade Component `<x-shop::image-zoomer>` | [`packages/Webkul/Shop/src/Resources/views/components/image-zoomer/index.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/image-zoomer/index.blade.php) |
| **3** | **Thumbnail Navigation** | Swiper Container / Vue Ref (`swiperContainer`) | [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php) |
| **4** | **Product Information Panel**| Master Vue Wrapper `<v-product>` | [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) |
| **5** | **Price Block** | Blade Type Instance / Vue Reactive Price | [`packages/Webkul/Shop/src/Resources/views/products/prices/configurable.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/prices/configurable.blade.php) |
| **6** | **Discount Badge** | Blade Utility / Vue Savings Pill | [`packages/Webkul/Shop/src/Resources/views/components/products/card.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/card.blade.php) |
| **7** | **Variant Selector** | Vue Component `<v-product-configurable-options>` | [`packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php) |
| **8** | **Stock Indicator** | Blade Component `<x-shop::products.stock-meter>` | [`packages/Webkul/Shop/src/Resources/views/components/products/stock-meter.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/stock-meter.blade.php) |
| **9** | **Shipping Transparency Card**| Blade Component `<x-shop::products.dropshipping-transparency>`| **[NEW]** [`packages/Webkul/Shop/src/Resources/views/components/products/dropshipping-transparency.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/components/products/dropshipping-transparency.blade.php) |
| **10**| **Add To Cart** | Vue Method `addToCart(0)` / Form Submit | [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) |
| **11**| **Buy Now** | Vue Method `addToCart(1)` / Form Submit | [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) |
| **12**| **Wishlist** | Vue Method `addToWishlist()` | [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) |
| **13**| **Reviews** | Sub-template Blade Include | [`packages/Webkul/Shop/src/Resources/views/products/view/reviews.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/reviews.blade.php) |
| **14**| **Mobile Sticky Purchase Bar**| Vue Component `<v-mobile-sticky-bar>` | **[NEW]** [`packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php) |
| **15**| **Recommended Products** | Vue Component `<v-product-associations>` | [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php) |

---

## PART 4 — Data Contract Specification

All data schemas correspond directly to existing database structures in Bagisto 2.4.x (`products`, `product_flat`, `product_images`, `product_inventories`):

### 4.1 Product Entity Data Contract

```typescript
interface ProductEntityContract {
  id: number;                          // Database Column: products.id (PK)
  sku: string;                         // Database Column: products.sku
  type: 'simple' | 'configurable';     // Database Column: products.type
  name: string;                        // Database Column: product_flat.name
  url_key: string;                     // Database Column: product_flat.url_key (Unique)
  short_description: string;           // Database Column: product_flat.short_description
  description: string;                 // Database Column: product_flat.description
  price: number;                       // Database Column: product_flat.price (Decimal 12,4)
  special_price: number | null;        // Database Column: product_flat.special_price
  special_price_from: string | null;   // Database Column: product_flat.special_price_from
  special_price_to: string | null;     // Database Column: product_flat.special_price_to
  status: 0 | 1;                       // Database Column: product_flat.status
  visible_individually: 0 | 1;         // Database Column: product_flat.visible_individually
  total_qty: number;                   // Aggregated Column: SUM(product_inventories.qty)
  in_stock: boolean;                   // Evaluated: total_qty > 0 && status === 1
}
```

### 4.2 Media Asset Data Contract

```typescript
interface MediaAssetContract {
  id: number;                          // Database Column: product_images.id
  product_id: number;                  // Database Column: product_images.product_id
  path: string;                        // Relative Storage Path: "product/12/image.jpg"
  small_image_url: string;             // Generated: "https://higest.com/cache/small/product/12/image.jpg"
  medium_image_url: string;            // Generated: "https://higest.com/cache/medium/product/12/image.jpg"
  large_image_url: string;             // Generated: "https://higest.com/cache/large/product/12/image.jpg"
  original_image_url: string;          // Direct Fallback: "https://higest.com/storage/product/12/image.jpg"
  fallback_url: string;                // Theme Asset: "https://higest.com/themes/higest/assets/images/placeholder-product.webp"
}
```

### 4.3 Configurable Variant Option Contract

```typescript
interface VariantOptionContract {
  attributes: Array<{
    id: number;                        // Attribute ID (e.g. Color = 23, Size = 24)
    code: string;                      // Attribute Code: "color" | "size"
    label: string;                     // Display Label: "Color" | "Size"
    swatch_type: 'color' | 'image' | 'text' | 'dropdown';
    options: Array<{
      id: number;                      // Attribute Option ID
      label: string;                   // Option Text: "Red", "XL"
      swatch_value: string;            // Hex Color "#FF0000" or Image Path
      products: number[];              // Child Product IDs offering this option
    }>;
  }>;
  variant_prices: Record<number, {     // Keyed by Child Product ID
    regular: { price: number; formatted_price: string };
    final: { price: number; formatted_price: string };
  }>;
  variant_images: Record<number, MediaAssetContract[]>;
}
```

### 4.4 Dropshipping Fulfillment Contract

```typescript
interface DropshippingFulfillmentContract {
  origin_country: string;              // Attribute: "International Fulfillment Hub"
  dispatch_lead_time_days: number;     // Configured: 1 to 2 business days
  estimated_delivery_range: string;    // Calculated: "5 - 8 Business Days"
  tracking_available: boolean;         // Always true for HIGEST express logistics
  local_rma_days: number;              // Configured Policy: 14 Days
  return_center_location: string;      // "HIGEST Local RMA Center"
}
```

---

## PART 5 — Backend Changes Required

### 5.1 Extension of Product View Helper (`Webkul\Product\Helpers\View`)

* **Purpose:** Inject dropshipping fulfillment transparency metadata into the product payload passed to `shop::products.view`.
* **Files Affected:** [`packages/Webkul/Product/src/Helpers/View.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/Helpers/View.php)
* **Risk:** Low. Additive helper method `getDropshippingMetadata($product)`.
* **Migration Required:** No DB migration required (uses core channel configuration + product attribute values).

### 5.2 ImageCache Resiliency Controller Interceptor

* **Purpose:** Catch missing cache asset HTTP requests and perform on-the-fly image generation or stream original files with 200 OK headers instead of 404 errors.
* **Files Affected:** [`packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php)
* **Risk:** Medium. Requires careful stream error catching for corrupted source images.
* **Migration Required:** No.

### 5.3 Cart Store API Response Harmonization

* **Purpose:** Ensure `shop.api.checkout.cart.store` returns standardized JSON payload including `is_buy_now` redirect URL (`/checkout/onepage`) when `is_buy_now = 1`.
* **Files Affected:** [`packages/Webkul/Shop/src/Http/Controllers/API/CartController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Http/Controllers/API/CartController.php)
* **Risk:** Low. Standardizes API response contract.
* **Migration Required:** No.

---

## PART 6 — Frontend Implementation Specification

### 6.1 Master Vue Wrapper (`v-product`)

```typescript
// Component: v-product (packages/Webkul/Shop/src/Resources/views/products/view.blade.php)
Props: None (Reads initial state from Blade inline script & DOM hidden inputs)
Events:
  - @variant-selected(variantId: number): Updates reactive price & active child product ID
  - @toggle-sticky-bar(visible: boolean): Controls mobile sticky purchase bar activation
State:
  - isWishlist: boolean
  - isCustomer: boolean
  - is_buy_now: 0 | 1
  - isStoring: { addToCart: boolean, buyNow: boolean }
  - selectedVariantId: number | null
Loading: Handled via isStoring properties driving SVG spinner states on CTAs.
Error Handling: Axios error interceptor emits global flash messages via this.$emitter.emit('add-flash', { type, message }).
```

### 6.2 Mobile Sticky Purchase Bar (`v-mobile-sticky-bar`)

```typescript
// Component: v-mobile-sticky-bar (packages/Webkul/Shop/src/Resources/views/products/view/mobile-sticky-bar.blade.php)
Props:
  - productId: number (Required)
  - priceHtml: string (Required)
  - isSaleable: boolean (Required)
  - sentinelId: string (Default: 'primary-pdp-cta-container')
Events:
  - @trigger-add-to-cart: Invokes parent v-product addToCart(0)
  - @trigger-buy-now: Invokes parent v-product addToCart(1)
State:
  - isVisible: boolean (Default: false)
  - observer: IntersectionObserver | null
Lifecycle:
  mounted(): Attaches IntersectionObserver to sentinelId. Sets isVisible = true only when sentinel scrolls above the viewport.
  unmounted(): Disconnects observer.
```

### 6.3 Product Gallery Vue Component (`v-product-gallery`)

```typescript
// Component: v-product-gallery (packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php)
Props: None (Reads product gallery JSON array)
Events:
  - @open-zoomer(index: number): Sets isImageZooming = true
State:
  - isImageZooming: boolean
  - activeIndex: number
  - media: { images: MediaAssetContract[], videos: any[] }
  - baseFile: { type: 'image' | 'video', path: string }
Methods:
  - handleImageError(imageItem, index): Sets imageItem.large_image_url = imageItem.original_image_url || fallbackUrl
```

---

## PART 7 — Image Architecture Implementation

To prevent broken image icons under all operational conditions, the PDP image pipeline is governed by five mandatory technical rules:

1. **Original Image Preservation:** Original uploaded product files are stored in immutable format under `storage/app/public/product/{id}/{filename}`.
2. **On-The-Fly Cache Generation:** Cache URLs (`url('cache/{preset}/'.$path)`) trigger Intervention Image filters. If a requested cache file does not exist on the filesystem, the `ImageController` intercepts the 404, resizes the source image, writes to disk, and returns the binary stream.
3. **WebP Format Standardization:** All cache outputs are rendered in `.webp` with 85% compression quality, saving ~60% bandwidth compared to raw JPEGs.
4. **Nginx / Apache Server Rule:** Nginx configuration must attempt file delivery first, falling back to Laravel's `index.php` router if the cache file is not yet compiled on disk:
   ```nginx
   location /cache/ {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```
5. **Frontend Multi-Tier Fallback Mandate:**
   All product `<img>` tags on PDP must include native inline `@error` fallback attributes:
   ```html
   <img 
       src="{{ $image['large_image_url'] }}" 
       alt="{{ $product->name }}"
       width="560" 
       height="610"
       loading="eager"
       fetchpriority="high"
       @error="$event.target.src = '{{ $image['original_image_url'] ?? bagisto_asset('images/large-product-placeholder.webp', 'shop') }}'"
   />
   ```

---

## PART 8 — Performance Implementation

### 8.1 Core Web Vitals Targets

* **Largest Contentful Paint (LCP):** **< 2.5s** on 4G network throttling.
* **Cumulative Layout Shift (CLS):** **0.00** (Zero layout shift during hydration).

### 8.2 Technical Implementation Rules

1. **Main Gallery Image LCP Optimization:**
   * HTML `<head>` injection via Blade `@push('meta')`:
     ```html
     <link rel="preload" as="image" href="{{ $productBaseImage['large_image_url'] }}" fetchpriority="high" type="image/webp">
     ```
   * Image attribute decoration: `loading="eager"`, `fetchpriority="high"`, `decoding="sync"`.

2. **Zero CLS Layout Reservation:**
   * Gallery main viewport container wrapped in fixed CSS aspect ratio:
     ```html
     <div class="relative w-full aspect-[560/610] min-h-[500px] max-w-[560px] rounded-xl overflow-hidden bg-zinc-100">
     ```
   * Image element includes explicit `width="560"` and `height="610"` HTML dimensions.

3. **Secondary Resource Deferred Loading:**
   * Gallery Thumbnails: `loading="lazy"`, `decoding="async"`.
   * Related & Up-sell Carousels: Wrapped in `<v-product-associations>` using `IntersectionObserver` with a 10% threshold. Carousels do NOT execute API requests until the user scrolls near the container.

---

## PART 9 — Security Considerations

1. **XSS Prevention in Dynamic Rich Text:**
   * Product descriptions (`{!! $product->description !!}`) sanitized via Laravel Purifier / HTMLPurifier rules before outputting raw HTML.
2. **Price Manipulation Protection:**
   * Cart creation requests (`shop.api.checkout.cart.store`) only accept `product_id`, `quantity`, and `super_attribute` IDs. Prices are strictly resolved server-side from database records inside `CartRepository::add()`. Client-side price strings are ignored during checkout processing.
3. **External Asset Validation (SSRF Prevention):**
   * Downloadable sample preview URLs validated via `validateExternalUrl()` inside `ProductController.php`, verifying scheme (`http`/`https`) and blocking internal IP ranges (`FILTER_FLAG_NO_PRIV_RANGE`).
4. **Variant Attribute Tampering:**
   * Super attribute option validation verifies that selected option IDs belong to valid child variants of the specified parent configurable SKU.

---

## PART 10 — Testing & Verification Strategy

### 10.1 Automated Pest PHP Tests

Execute unit and feature test suites covering PDP routes and repositories:

```bash
# Run Product & Shop package tests
php artisan test --compact packages/Webkul/Shop/tests
php artisan test --compact --filter=ProductPageTest
```

**Key Test Cases:**
* `test_pdp_returns_200_for_active_visible_product()`
* `test_pdp_returns_404_for_disabled_product()`
* `test_add_to_cart_api_validates_required_configurable_attributes()`
* `test_image_cache_helper_returns_fallback_url_when_image_missing()`

### 10.2 Playwright E2E Browser Tests

Located in `packages/Webkul/Shop/tests/e2e-pw/`:

```bash
cd packages/Webkul/Shop && npx playwright test tests/e2e-pw/pdp-flow.spec.ts
```

**E2E Test Matrix:**
1. **Desktop Flow (1440px Viewport - English LTR):** Verify gallery thumbnail clicks, image zoomer modal trigger, variant swatch selection, price update, Add to Cart drawer trigger.
2. **Mobile Flow (375px Viewport - Arabic RTL):** Verify horizontal gallery swipe, sticky purchase bar activation upon scrolling past `#primary-pdp-cta-container`, Buy Now redirect to `/checkout/onepage`.

### 10.3 Code Style Compliance

```bash
vendor/bin/pint --dirty
```

---

## PART 11 — Deployment & Rollout Plan

```
[ DEVELOPMENT ] ──► [ STAGING / QA ] ──► [ PRODUCTION ROLLOUT ]
  Local Windows        Sail Docker          Zero-Downtime Pipeline
  Vite HMR Dev         Pest & Playwright    Asset Compilation & Cache Flush
```

### 11.1 Deployment Sequence Checklist

1. **Database Migration Check:**
   ```bash
   php artisan migrate --force
   ```
2. **Frontend Asset Compilation:**
   Execute production build inside Shop package directory:
   ```bash
   cd packages/Webkul/Shop && npm install && npm run build
   ```
3. **Application Cache Invalidation:**
   ```bash
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
4. **Image Cache Warmup & Storage Symlink Verification:**
   Ensure public storage link exists:
   ```bash
   php artisan storage:link
   ```

### 11.2 Rollback Strategy

If critical regressions occur post-deployment:
1. Revert Git release tag to previous stable commit (`git checkout tags/v3.0-pdp-stable`).
2. Re-run `npm run build` in `packages/Webkul/Shop`.
3. Execute `php artisan view:clear` and `php artisan cache:clear`.

---

## Specification Authorization & Seal

**Prepared By:** Lead Software Architect & Platform Engineering Team  
**Status:** Approved & Sealed Technical Engineering Contract (Part 4 Complete)  
**Next Steps:** Proceed directly to execution & code implementation according to this specification.
