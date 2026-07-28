# RSR v3.0 Part 4.1 — Technical Implementation Review Corrections

**Document Title:** RSR v3.0 Part 4.1 — Technical Implementation Review Corrections & Architecture Seals  
**Version:** 3.0 (Part 4.1 Addendum)  
**Status:** Binding Specification Addendum / Approved Technical Engineering Seal  
**Parent Specification:** [`RSR_v3.0_Part4_Technical_Implementation_Specification.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/RSR_v3.0_Part4_Technical_Implementation_Specification.md)  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## Executive Context

Following leadership review and conditional approval (🟡 **Approved with Review 4.1 Required**) of **RSR v3.0 Part 4 — Technical Implementation & Architecture Specification**, this addendum formalizes six critical engineering corrections and seals.

These additions establish the final infrastructure contracts for Image Delivery, Dropshipping Data Sources, Transformer Architecture, SEO JSON-LD Schemas, GA4/Meta Analytics Event Tracking, and Mobile Sticky Bar State Rules.

---

## 1. Image Delivery Infrastructure Contract (Nginx Layer)

### 1.1 Mandatory Architecture Rule

> **CRITICAL INFRASTRUCTURE RULE:**  
> *Nginx web server routing is an explicit part of HIGEST PDP image architecture. Image cache generation MUST NOT rely solely on Laravel application code.*

### 1.2 Required Nginx Configuration Specification

During production deployments, static asset location blocks in `/etc/nginx/sites-available/higest` must not override the `/cache/` dynamic generation endpoint. Nginx must be configured to pass missing image cache requests to Laravel's front controller (`index.php`):

```nginx
# HIGEST Image Cache Infrastructure Route
location ^~ /cache/ {
    try_files $uri $uri/ /index.php?$query_string;
    expires 30d;
    add_header Cache-Control "public, no-transform";
}

# General Static Assets (Must not match /cache/ paths)
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires max;
    log_not_found off;
    try_files $uri =404;
}
```

### 1.3 Production Image Verification Protocol

Before certifying any PDP deployment, automated CI/CD or staging checks must execute the following curl verification command against a newly uploaded image:

```bash
# Production Verification Command
curl -I -s "https://higest.com/cache/large/product/1/sample.jpg" | grep -E "HTTP/|Content-Type"

# Mandatory Expected Output:
# HTTP/1.1 200 OK
# Content-Type: image/webp
```

---

## 2. Dropshipping Data Contract Sources & Migration Safety

To prevent engineers from executing unauthorized database migrations, every field in `DropshippingFulfillmentContract` is mapped strictly to its exact data source origin:

| Field Name | Data Type | Data Source Origin | Resolution Strategy |
| :--- | :--- | :--- | :--- |
| `origin_country` | `string` | **Configuration Value** / **Product EAV Attribute** | Resolved via `core()->getConfigData('catalog.products.dropshipping.default_origin')` or product custom attribute `country_of_origin`. |
| `dispatch_lead_time_days`| `number` | **Configuration Value** | Resolved via `core()->getConfigData('catalog.products.dropshipping.dispatch_lead_time')` (Default: 1-2 days). |
| `estimated_delivery_range`| `string` | **Calculated Service Rule** | Formatted string based on `dispatch_lead_time_days` + shipping carrier method configuration. |
| `tracking_available` | `boolean` | **Constant Business Rule** | Fixed `true` for all HIGEST express logistics shipments. |
| `local_rma_days` | `number` | **Configuration Value** | Resolved via `core()->getConfigData('sales.shipping.rma.default_return_days')` (Default: 14 days). |
| `return_center_location` | `string` | **Configuration Value** | Resolved via `core()->getConfigData('sales.shipping.rma.return_center_address')`. |

> **DATABASE MIGRATION MANDATE:**  
> *Engineers MUST NOT create new database migrations for dropshipping transparency fields. All fields rely on existing Bagisto core configuration keys or EAV custom attributes.*

---

## 3. PDP Controller & Transformer Architecture Refactoring

To prevent controller bloat in `ProductsCategoriesProxyController`, data extraction and transformation logic for the PDP is isolated using a dedicated **PDP Data Transformer Service**:

```
ProductsCategoriesProxyController::index()
                │
                ▼
Webkul\Product\Helpers\View (Product View Helper)
                │
                ▼
Webkul\Shop\Transformers\ProductPDPTransformer
                │
                ▼
   Returns Clean PDP ViewModel Array
                │
                ▼
view('shop::products.view', $pdpViewModel)
```

### 3.1 `ProductPDPTransformer` Specification

* **Class File:** `packages/Webkul/Shop/src/Transformers/ProductPDPTransformer.php`
* **Responsibilities:**
  1. Transforms raw Eloquent `Product` & `ProductFlat` models into clean arrays.
  2. Pre-calculates price formatting, MSRP strikethroughs, and discount percentages.
  3. Formats gallery images with fallback URLs (`small`, `medium`, `large`, `original`).
  4. Resolves dropshipping transparency metadata from configuration.
  5. Serializes variant configuration matrices for Vue hydration.

---

## 4. SEO Product Structured Data Implementation (PART 12)

### 4.1 Schema Injection Architecture

SEO Rich Snippets are generated server-side using Bagisto's SEO helper (`Webkul\Product\Helpers\SEO`) and rendered inside `<head>` via `@push('meta')` in [`view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php).

### 4.2 Required JSON-LD Specification

```json
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "{{ $product->name }}",
  "image": [
    "{{ $productBaseImage['large_image_url'] }}"
  ],
  "description": "{{ strip_tags($product->short_description) }}",
  "sku": "{{ $product->sku }}",
  "brand": {
    "@type": "Brand",
    "name": "HIGEST"
  },
  "offers": {
    "@type": "Offer",
    "url": "{{ route('shop.product_or_category.index', $product->url_key) }}",
    "priceCurrency": "{{ core()->getCurrentCurrencyCode() }}",
    "price": "{{ $product->getTypeInstance()->getMinimalPrice() }}",
    "itemCondition": "https://schema.org/NewCondition",
    "availability": "{{ $product->isSaleable() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}"
  },
  @if ($totalRatings = $reviewHelper->getTotalFeedback($product))
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ $avgRatings }}",
    "reviewCount": "{{ $totalRatings }}"
  },
  @endif
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ route('shop.home.index') }}"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "{{ $product->name }}",
        "item": "{{ route('shop.product_or_category.index', $product->url_key) }}"
      }
    ]
  }
}
```

### 4.3 Automated SEO Validation Standards

* **Google Rich Results Test:** Must pass without errors or warnings for `Product`, `Offer`, and `Merchant Listings`.
* **Schema.org Validator:** Must yield zero structural syntax errors.

---

## 5. PDP Analytics & Tracking Event Contract (PART 13)

To ensure conversion tracking parity with top-tier marketplaces (AliExpress / SHEIN), all PDP interactions trigger standard analytics events compatible with **Google Analytics 4 (GA4)** and **Meta Pixel (Facebook)**:

### 5.1 Standard Event Payload Matrix

| Interaction / Trigger | Event Name | GA4 Event Payload | Meta Pixel Event Call |
| :--- | :--- | :--- | :--- |
| **Page View (PDP Load)** | `view_item` | `{ items: [{ item_id: sku, item_name: name, price: price, item_category: category }] }` | `fbq('track', 'ViewContent', { content_ids: [sku], content_type: 'product', value: price, currency: currency })` |
| **Variant Selected** | `select_variant` | `{ item_id: parent_sku, variant_id: child_sku, attribute_code: code, option_value: label }` | `fbq('trackCustom', 'SelectVariant', { sku: child_sku })` |
| **Gallery Zoomed / Swiped** | `view_gallery_image` | `{ item_id: sku, image_index: index }` | N/A |
| **Add To Cart Click** | `add_to_cart` | `{ items: [{ item_id: sku, item_name: name, price: price, quantity: qty }] }` | `fbq('track', 'AddToCart', { content_ids: [sku], content_type: 'product', value: price * qty, currency: currency })` |
| **Buy Now Click** | `buy_now` | `{ items: [{ item_id: sku, item_name: name, price: price, quantity: qty }], is_express: true }` | `fbq('track', 'InitiateCheckout', { content_ids: [sku], content_type: 'product', value: price * qty, currency: currency })` |
| **Wishlist Toggle** | `wishlist_add` | `{ item_id: sku, item_name: name }` | `fbq('track', 'AddToWishlist', { content_ids: [sku] })` |
| **Review Accordion Click**| `review_expand` | `{ item_id: sku, action: 'expand_reviews' }` | N/A |

### 5.2 Client-Side Event Emitter Bus

Events are dispatched globally through Bagisto's Vue Event Emitter bus:

```javascript
// Dispatched from v-product components
this.$emitter.emit('track-analytics-event', {
    event: 'add_to_cart',
    payload: { item_id: this.productSku, price: this.currentPrice }
});
```

---

## 6. Mobile Sticky Bar Validation & State Rules

The `<v-mobile-sticky-bar>` component must enforce two strict runtime validation guards:

### 6.1 Hydration & Price Lock Guard

* **Rule:** The mobile sticky purchase bar **MUST NOT** render or activate before price hydration and Vue initialization complete.
* **Implementation:** Component visibility is bound to `isHydrated === true`.

### 6.2 Unselected Configurable Option Guard

* **Rule:** If a product is configurable (`type === 'configurable'`) and the customer has not selected all required super attributes, the sticky bar CTA buttons must remain visually disabled or prompt option selection upon tap.
* **Visual State:**
  * Primary Sticky CTA Text: `"Select Options"` (in Navy Blue Outline / Muted Zinc background).
  * On Tap Action: Smoothly scrolls the page up to the variant swatch selector container (`#configurable-options-container`) or opens the Mobile Bottom Sheet Options Selector (`z-500`) with a highlighted warning outline on missing swatches.

```
Configurable Product Variant Validation Flow:

[ User Taps Sticky CTA ]
          │
  [ All Options Selected? ]
     ├── YES ──► Execute Add To Cart / Buy Now Async API
     └── NO  ──► Open Bottom Sheet Selector (z-500) & Highlight Missing Swatch
```

---

## Final Specification Authorization & Seal

With the release of **Part 4.1**, the complete architectural specification suite for the HIGEST PDP is 100% sealed and binding:

| Specification Document | Scope & Domain | Status |
| :--- | :--- | :--- |
| **Part 1** | Functional Specification | ✅ Approved |
| **Part 1 Addendum** | HIGEST Refinements | ✅ Approved |
| **Part 2** | Responsive UX Specification | ✅ Approved |
| **Part 2.1** | UX Review Corrections | ✅ Approved |
| **Part 3** | Visual Design System Specification | ✅ Approved |
| **Part 3.1** | Visual Design Review Corrections | ✅ Approved |
| **Part 4** | Technical Implementation Specification | ✅ Approved |
| **Part 4.1** | **Technical Implementation Review Corrections** | ✅ **Approved (This Document)** |

---

**Approved By:** HIGEST Core Platform Engineering Team & Product Architecture Lead  
**Status:** ALL ARCHITECTURAL DOCUMENTS 100% SEALED — READY FOR CODE EXECUTION
