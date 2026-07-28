# PDP Implementation Discovery Report v1.0

**Document Title:** HIGEST Product Detail Page (PDP) Implementation Discovery & Code Mapping Report  
**Version:** 1.0 (Phase -1 Deliverable)  
**Status:** Completed & Sealed Discovery Baseline  
**Parent Roadmap:** [`PDP_Implementation_Execution_Roadmap_v2.1.md`](file:///e:/HIGESTO%20NEW1/higest/higest101/docs/PDP_Implementation_Execution_Roadmap_v2.1.md)  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## Executive Summary

As mandated by **Phase -1 (Implementation Discovery & Code Mapping)** of the HIGEST PDP Implementation Execution Roadmap v2.1, this discovery report documents the complete architectural state of the existing Product Detail Page before any code modifications are performed.

Every diagram, rendering tree, dependency map, and file path in this report represents empirical findings from auditing the `packages/Webkul/` repository codebase.

---

## 1. Current PDP Architecture Map

The existing Bagisto 2.4.x PDP relies on a single proxy entry point that resolves product slugs and renders a monolithic Blade view containing hydrated Vue 3 inline templates:

```
[ HTTP Request: /products/{url_key} ]
                  │
                  ▼
[ Routes File: packages/Webkul/Shop/src/Routes/storefront-routes.php ]
                  │
                  ▼
[ Proxy Controller: Webkul\Shop\Http\Controllers\ProductsCategoriesProxyController@index ]
                  │
                  ├─► CategoryRepository::findBySlug()  (Check if Category Slug)
                  ├─► ProductRepository::findBySlug()   (Check if Product Slug)
                  └─► URLRewriteRepository              (Check 301/302 Redirects)
                  │
                  ▼
[ Render Main View: shop::products.view (compact('product')) ]
                  │
                  ├─► Inject Helpers: ReviewHelper, ProductViewHelper
                  ├─► Insert Layout: <x-shop::layouts>
                  └─► Mount Vue 3 Inline Components:
                        ├── <v-product>
                        ├── <v-product-gallery>
                        ├── <v-product-configurable-options>
                        └── <v-product-associations>
```

---

## 2. Controller Flow Diagram

```
                       [ Request Arrives: $request->getPathInfo() ]
                                           │
                                           ▼
                      [ Regex Validation: Slug/URL Key Syntax ]
                                           │
                           ┌───────────────┴───────────────┐
                       (Valid)                         (Invalid)
                           │                               │
                           ▼                               ▼
               [ CategoryRepository ]                 [ Return Home View ]
               findBySlug($slug)                      shop::home.index
                           │
                 ┌─────────┴─────────┐
             (Found)             (Not Found)
                 │                   │
                 ▼                   ▼
    [ Return Category View ]   [ ProductRepository ]
    shop::categories.view      findBySlug($slug)
                                     │
                             ┌───────┴───────┐
                         (Found)         (Not Found)
                             │               │
                             ▼               ▼
                   [ Check Active Status ]  [ URLRewriteRepository ]
                   - url_key exists?       find request_path
                   - visible_individually?   │
                   - status == 1?    ┌───────┴───────┐
                             │    (Found)         (Not Found)
                       ┌─────┴─────┐ │               │
                    (Pass)       (Fail)              ▼
                       │           │            [ Abort 404 ]
                       ▼           ▼
             [ Return PDP View ] [ Abort 404 ]
             shop::products.view
```

---

## 3. Blade Rendering Tree

```
packages/Webkul/Shop/src/Resources/views/products/view.blade.php
│
├── @inject ('reviewHelper', 'Webkul\Product\Helpers\Review')
├── @inject ('productViewHelper', 'Webkul\Product\Helpers\View')
│
├── @push('meta')
│     ├── <meta name="description">
│     ├── <meta name="keywords">
│     ├── <script type="application/ld+json"> (SEO Helper)
│     └── OpenGraph & Twitter Card Meta Tags
│
└── <x-shop::layouts>
      │
      ├── {!! view_render_event('bagisto.shop.products.view.before') !!}
      │
      ├── <x-shop::breadcrumbs entity="$product" />
      │
      ├── <v-product> (Vue Inline Wrapper)
      │     │
      │     ├── <x-shop::shimmer.products.view /> (Server Fallback)
      │     │
      │     └── <script type="text/x-template" id="v-product-template">
      │           <x-shop::form @submit="handleSubmit($event, addToCart)">
      │                 │
      │                 ├── @include('shop::products.view.gallery')
      │                 │     └── <v-product-gallery>
      │                 │           ├── @include('shop::products.view.gallery.desktop')
      │                 │           ├── @include('shop::products.view.gallery.mobile')
      │                 │           └── <x-shop::image-zoomer>
      │                 │
      │                 └── Product Details Column (max-w-[590px]):
      │                       ├── {!! view_render_event('bagisto.shop.products.name.before') !!}
      │                       ├── Product H1 Title & Wishlist Button (addToWishlist)
      │                       ├── Star Rating & Review Scroll Anchor (scrollToReview)
      │                       ├── Price Block HTML ({!! $product->getTypeInstance()->getPriceHtml() !!})
      │                       ├── Short Description ({!! $product->short_description !!})
      │                       │
      │                       ├── Type-Specific Option Blade Includes:
      │                       │     ├── @include('shop::products.view.types.simple')
      │                       │     ├── @include('shop::products.view.types.configurable')
      │                       │     ├── @include('shop::products.view.types.grouped')
      │                       │     ├── @include('shop::products.view.types.bundle')
      │                       │     ├── @include('shop::products.view.types.downloadable')
      │                       │     └── @include('shop::products.view.types.booking')
      │                       │
      │                       ├── Quantity Changer (<x-shop::quantity-changer>)
      │                       ├── Add To Cart Button (<x-shop::button ::loading="isStoring.addToCart">)
      │                       ├── Buy Now Button (<x-shop::button ::loading="isStoring.buyNow">)
      │                       └── Compare Button (addToCompare)
      │
      ├── Tabs Section (Desktop ≥1180px):
      │     <x-shop::tabs>
      │           ├── Description Tab ({!! $product->description !!})
      │           ├── Additional Information Tab ($customAttributeValues)
      │           └── Reviews Tab (@include('shop::products.view.reviews'))
      │
      ├── Accordions Section (Mobile <1180px):
      │     <x-shop::accordion> (Description, Additional Info, Reviews)
      │
      └── <v-product-associations>
            └── <script type="text/x-template" id="v-product-associations-template">
                  ├── Related Products Carousel (<x-shop::products.carousel>)
                  └── Up-sell Products Carousel (<x-shop::products.carousel>)
```

---

## 4. Vue Component Dependency Map

```
Vue 3 Root Application Instance (packages/Webkul/Shop/src/Resources/assets/js/app.js)
  │
  ├── Mitt Global Event Emitter (this.$emitter)
  ├── Axios HTTP Client Instance (this.$axios)
  ├── VeeValidate Form Provider (v-form, v-field, v-error-message)
  │
  ├── v-product Component (Main Form & Action Coordinator)
  │     ├── State: isWishlist, isCustomer, is_buy_now, isStoring { addToCart, buyNow }
  │     ├── Axios Endpoints Called:
  │     │     ├── POST: shop.api.checkout.cart.store
  │     │     ├── GET:  shop.api.customers.account.wishlist.index
  │     │     ├── POST: shop.api.customers.account.wishlist.store
  │     │     └── POST: shop.api.compare.store
  │     └── Emits Events: 'update-mini-cart', 'add-flash'
  │
  ├── v-product-gallery Component (Media Gallery Manager)
  │     ├── State: isImageZooming, isMediaLoading, media { images, videos }, baseFile, activeIndex
  │     ├── Child Component: <x-shop::image-zoomer> (Lightbox overlay)
  │     └── Data Source: product_image()->getGalleryImages($product)
  │
  ├── v-product-configurable-options Component (Swatch & Variant Resolver)
  │     ├── State: childAttributes, selectedOptionVariant, config
  │     ├── Data Source: app('Webkul\Product\Helpers\ConfigurableOption')->getConfigurationConfig($product)
  │     ├── Methods: configure(), reloadPrice(), reloadImages()
  │     └── Directly Mutates: DOM (.final-price, .regular-price) & parent gallery images
  │
  └── v-product-associations Component (Intersection Observer Lazy Loader)
        ├── State: isVisible (Triggered when scrolled into viewport threshold 0.1)
        └── Child Components: <x-shop::products.carousel> for Related and Up-sell SKUs
```

---

## 5. Existing JavaScript & PHP View Events Map

### 5.1 Client-Side Mitt Event Bus (`this.$emitter`)

| Event Name | Direction | Payload | Trigger Source / Target |
| :--- | :--- | :--- | :--- |
| `update-mini-cart` | Dispatched | `cartData: object` | `v-product` ──► Header Mini-Cart Drawer |
| `add-flash` | Dispatched | `{ type: 'success'\|'warning'\|'error', message: string }` | `v-product` ──► Global Toast System |
| `configurable-variant-selected-event` | Dispatched | `variantId: number` | `v-product-configurable-options` ──► Parent View |
| `configurable-variant-update-images-event` | Dispatched | `galleryImages: array` | `v-product-configurable-options` ──► `v-product-gallery` |

### 5.2 Server-Side Blade Render Events (`view_render_event`)

1. `bagisto.shop.products.view.before`
2. `bagisto.shop.products.name.before`
3. `bagisto.shop.products.name.after`
4. `bagisto.shop.products.rating.before`
5. `bagisto.shop.products.rating.after`
6. `bagisto.shop.products.price.before`
7. `bagisto.shop.products.price.after`
8. `bagisto.shop.products.short_description.before`
9. `bagisto.shop.products.short_description.after`
10. `bagisto.shop.products.view.quantity.before`
11. `bagisto.shop.products.view.quantity.after`
12. `bagisto.shop.products.view.add_to_cart.before`
13. `bagisto.shop.products.view.add_to_cart.after`
14. `bagisto.shop.products.view.buy_now.before`
15. `bagisto.shop.products.view.buy_now.after`
16. `bagisto.shop.products.view.additional_actions.before`
17. `bagisto.shop.products.view.additional_actions.after`
18. `bagisto.shop.products.view.description.before`
19. `bagisto.shop.products.view.description.after`
20. `bagisto.shop.products.view.after`

---

## 6. Current Image Pipeline

```
[ Eloquent Product Model ($product->images) ]
                     │
                     ▼
[ ProductImage Helper: getGalleryImages($product) ]
                     │
                     ├─► Check Storage::has($image->path)
                     │
                     ▼
[ ProductImage Helper: getCachedImageUrls($path) ]
                     │
                     ├── small_image_url    => url('cache/small/'.$path)
                     ├── medium_image_url   => url('cache/medium/'.$path)
                     ├── large_image_url    => url('cache/large/'.$path)
                     └── original_image_url => url('cache/original/'.$path)
                     │
                     ▼
[ Browser Requests URL: https://higest.com/cache/large/product/12/image.jpg ]
                     │
         ┌───────────┴───────────┐
     (Static File Exists)   (File Missing)
         │                       │
         ▼                       ▼
    [ Nginx Serves ]      [ Request Handled by Intervention ImageCache ]
    200 OK WebP           Webkul\ImageCache\Http\Controllers\ImageController
                                 │
                         ┌───────┴───────┐
                    (Generated)     (Failed / Symlink Break)
                         │               │
                         ▼               ▼
                    200 OK WebP     404 Not Found (Broken Image Icon)
```

---

## 7. Current Variant Selection Flow

```
1. Configurable Product Blade Template Loaded (configurable.blade.php)
   └── Reads JSON config: app('Webkul\Product\Helpers\ConfigurableOption')->getConfigurationConfig($product)

2. User Clicks Swatch / Selects Dropdown Option
   └── Triggers Vue method: configure(attribute, optionId)

3. Vue Method Logic:
   ├── Updates attribute.selectedValue = optionId
   ├── Filters childAttributes to resolve remaining allowed child product IDs
   └── Calculates possibleOptionVariant (Child Product ID)

4. Executed Side Effects:
   ├── reloadPrice():
   │     ├── If all options selected:
   │     │     Directly queries DOM: document.querySelector('.final-price')
   │     │     Directly queries DOM: document.querySelector('.regular-price')
   │     │     Updates innerHTML with configVariant.final.formatted_price
   │     └── Emits: configurable-variant-selected-event(possibleOptionVariant)
   │
   └── reloadImages():
         ├── Clears parent gallery images array (galleryImages.splice(0))
         ├── Pushes variant_images[possibleOptionVariant]
         ├── Directly mutates parent Vue ref: this.$parent.$parent.$refs.gallery.media.images = [...galleryImages]
         └── Emits: configurable-variant-update-images-event(galleryImages)
```

---

## 8. Pre-Modification Risk Register

| Risk ID | Severity | Failure Scenario & Impact | Discovery Remediation Plan |
| :--- | :--- | :--- | :--- |
| **RISK-01** | **HIGH** | **Direct DOM Mutation in Swatch Reload:** `configurable.blade.php` query-selects `.final-price` and `.regular-price` innerHTML directly. If Blade markup changes, variant price updates break silently. | Refactor `reloadPrice()` in Phase 4 to emit `@variant-selected` events to `<v-product>`, updating price via Vue reactive state. |
| **RISK-02** | **HIGH** | **Image Cache 404 Breakage:** If Intervention cache generation fails or Nginx static location rules override `/cache/`, browser renders broken images. | Implement multi-tier fallback helper in Phase 2 and add `@error` inline fallback attributes on all Blade `<img>` elements. |
| **RISK-03** | **HIGH** | **Cumulative Layout Shift (CLS):** Gallery image containers in `desktop.blade.php` lack explicit aspect ratio boxes, causing page layout jumping during hydration. | Wrap gallery images in fixed `aspect-[560/610]` containers with `width="560"` and `height="610"` attributes in Phase 2/3. |
| **RISK-04** | **MEDIUM**| **Mobile Sticky Bar Double CTAs:** Rendering mobile sticky bar simultaneously with inline CTA buttons creates button clutter. | Use `IntersectionObserver` in Phase 5 to activate sticky bar only when `#primary-pdp-cta-container` is scrolled out of viewport. |
| **RISK-05** | **MEDIUM**| **Breaking Event Hook Integrity:** Modifying `view.blade.php` structure might displace 20 `bagisto.shop.products.view.*` Blade event hooks. | Retain 100% of existing `view_render_event` hooks in their exact anatomical positions in `view.blade.php`. |

---

## 9. Files Planned For Modification

The following 9 files are explicitly designated for modification during the implementation sequence:

1. [`packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Http/Controllers/ProductsCategoriesProxyController.php)
2. [`packages/Webkul/Shop/src/Resources/views/products/view.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view.blade.php)
3. [`packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery.blade.php)
4. [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/desktop.blade.php)
5. [`packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/gallery/mobile.blade.php)
6. [`packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Shop/src/Resources/views/products/view/types/configurable.blade.php)
7. [`packages/Webkul/Product/src/ProductImage.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/ProductImage.php)
8. [`packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/ImageCache/src/Http/Controllers/ImageController.php)
9. [`packages/Webkul/Product/src/Helpers/View.php`](file:///e:/HIGESTO%20NEW1/higest/higest101/packages/Webkul/Product/src/Helpers/View.php)

---

## 10. Files Explicitly Protected From Modification

The following core files and directories are **STRICTLY PROTECTED** from any alteration:

1. **Vendor & Dependencies:** `vendor/`, `node_modules/`, `composer.lock`, `package-lock.json`
2. **Build Outputs:** `public/themes/*/build/` (Vite output managed via build scripts)
3. **Core Architectural Providers:** `bootstrap/app.php`, `bootstrap/providers.php`, `config/concord.php`
4. **Checkout & Order Sagas:** `packages/Webkul/Checkout/`, `packages/Webkul/Sales/`, `packages/Webkul/Customer/`
5. **Admin Package Core:** `packages/Webkul/Admin/` (outside PDP storefront scope)
6. **Database Schema Migrations:** All files inside `database/migrations/` and `packages/Webkul/*/src/Database/Migrations/` (Zero migration rule)

---

## Discovery Gate Sign-Off

**Audited & Prepared By:** Lead Software Architect  
**Phase -1 Status:** **100% COMPLETED & SEALED**  
**Approval Recommendation:** Phase -1 Gate Cleared. Ready for Project Lead Sign-off to initiate **Phase 0 (Preparation & Safety)**.
