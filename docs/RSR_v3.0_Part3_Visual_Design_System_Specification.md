# RSR v3.0 Part 3 — Visual Design System Specification

**Document Title:** HIGEST eCommerce Product Detail Page (PDP) Visual Design System Specification  
**Version:** 3.0 (Part 3)  
**Status:** Binding Visual Design Specification / Frontend Design Contract  
**Parent Specifications:** `RSR_v3.0_Part1`, `RSR_v3.1_HIGEST_Refinements`, `RSR_v3.0_Part2_UX`, `RSR_v3.0_Part2.1_Corrections`  
**Target Platform:** HIGEST eCommerce (Bagisto 2.4.x / Laravel 11 / Vue 3 / TailwindCSS)  

---

## 1. Executive Visual Philosophy & Brand Foundation

The HIGEST Visual Design System transforms the Product Detail Page from a standard ecommerce template into a premium, trustworthy visual commerce engine. Built on Bagisto 2.4.x's Tailwind CSS layer, the visual identity strikes a balance between **marketplace conversion density** and **retail brand elegance**.

### 1.1 Brand Visual Identity & Color Palette

The HIGEST color architecture is strictly defined in `packages/Webkul/Shop/tailwind.config.js` and extended for the PDP design system:

```
[ Primary Brand ]    Navy Blue     : #060C3B  (Dominant Brand Anchor, CTAs, Active States)
[ Secondary Surface] Light Cream   : #F6F2EB  (Warm Premium Background Accent)
[ Accent Interactive]Deep Blue     : #0044F2  (Links, Focus Rings, Interactive Hints)
[ Base Neutral ]     Charcoal Dark : #18181B  (Headings, Primary Text)
[ Surface Muted ]    Zinc Slate    : #F4F4F5  (Card Backgrounds, Skeleton Shimmers)
```

### 1.2 Semantic Color System Matrix

Semantic colors communicate state instantly without requiring user interpretation:

| Semantic Function | Color Token / Hex | Tailwind Class | Application / Component Context |
| :--- | :--- | :--- | :--- |
| **Success State** | Dark Green (`#40994A`) | `text-darkGreen`, `bg-darkGreen` | In-Stock meter, Order Success toasts, Verified Badge. |
| **Warning / Urgency** | Amber (`#F59E0B`) | `text-amber-500`, `bg-amber-50` | Low Stock indicator (≤5 items), Flash promotion pills. |
| **Error / Validation** | Crimson (`#EF4444`) | `text-red-500`, `border-red-500` | Unselected swatch warning, API failure flash banner. |
| **Trust & Security** | Navy/Green Blend | `text-navyBlue`, `bg-zinc-50` | Dropshipping Transparency Box, Payment Security locks. |
| **Discount Highlight** | Dark Pink (`#F85156`) | `bg-darkPink text-white` | Promotional savings pill (`-25% OFF`), Sale badge. |
| **MSRP Strikethrough** | Zinc Muted (`#71717A`) | `text-zinc-500 line-through` | Original pre-discount price display. |

---

## 2. Typography System Specification

HIGEST uses a responsive, dual-locale typography system configured for both LTR (English) and RTL (Arabic) rendering.

```
Typography Hierarchy Preview:
H1 Product Title   : 32px / 28px Mobile | Font-Weight 500 (Medium) | Line-Height 1.25
Price Primary      : 30px / 24px Mobile | Font-Weight 700 (Bold)   | Color: #060C3B
Section Headings   : 20px / 18px Mobile | Font-Weight 600 (SemiBold)| Line-Height 1.3
Body & Specs       : 16px / 14px Mobile | Font-Weight 400 (Regular) | Color: #52525B
Micro-Copy & Tags  : 12px / 13px Mobile | Font-Weight 500 (Medium)  | Color: #71717A
```

### 2.1 Font Families by Locale

* **English (LTR):** `Poppins`, `sans-serif` (Fallback: `Inter`, `system-ui`).
* **Arabic (RTL):** `Cairo` / `Tajawal` / `IBM Plex Sans Arabic`, `sans-serif`.
* **Font Rendering:** Enforced `-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;`.

### 2.2 Typography Scale Matrix

| Role / Element | Font Size (Desktop) | Font Size (Mobile) | Line Height | Font Weight | Tailwind Class Combination |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Product Title (H1)** | 32px (`text-3xl`) | 20px (`text-xl`) | 1.25 | Medium (`500`) | `text-3xl font-medium max-sm:text-xl break-words text-black` |
| **Primary Price** | 30px (`text-3xl`) | 24px (`text-2xl`) | 1.2 | Bold (`700`) | `text-3xl font-bold text-navyBlue max-sm:text-2xl` |
| **MSRP Price** | 18px (`text-lg`) | 14px (`text-sm`) | 1.2 | Medium (`500`) | `text-lg font-medium text-zinc-500 line-through max-sm:text-sm` |
| **Discount Badge** | 14px (`text-sm`) | 12px (`text-xs`) | 1.0 | Bold (`700`) | `bg-darkPink text-white px-2 py-0.5 rounded-md text-sm font-bold` |
| **Section H2 / H3** | 20px (`text-xl`) | 16px (`text-base`) | 1.3 | SemiBold (`600`)| `text-xl font-semibold max-sm:text-base text-black` |
| **Swatch Labels** | 16px (`text-base`)| 14px (`text-sm`) | 1.4 | Medium (`500`) | `text-base font-medium text-black max-sm:text-sm` |
| **Body & Specs** | 16px (`text-base`)| 14px (`text-sm`) | 1.6 | Regular (`400`) | `text-base text-zinc-600 max-sm:text-sm leading-relaxed` |
| **Trust Micro-Copy**| 13px (`text-xs`)| 12px (`text-xs`) | 1.4 | Medium (`500`) | `text-xs font-medium text-zinc-500` |

---

## 3. Product Page Design Tokens

Design tokens ensure mathematical consistency across padding, grids, rounded corners, and elevation layers.

### 3.1 Spacing Scale & Container Layout Tokens

* **Outer Container Width:** Max `1440px` (`2xl`), inner page wrapper `1280px`.
* **Container Padding:** 
  * Desktop (≥1180px): `px-[60px]`
  * Tablet (768px - 1179px): `px-5`
  * Mobile (<768px): `px-3.5`
* **Column Gap Token:** `gap-9` (`36px`) between Gallery and Information panel on Desktop.
* **Vertical Section Rhythm:** `mt-12` (Main sections), `mt-8` (Component stacks), `mt-5` (Sub-elements).

### 3.2 Border Radius Scale Tokens

```
[ rounded-sm ]  : 4px   (Micro badges, tag pills)
[ rounded-md ]  : 6px   (Discount pills, image swatch frames)
[ rounded-lg ]  : 8px   (Select dropdowns, ratings cards, price containers)
[ rounded-xl ]  : 12px  (CTA buttons, thumbnail tiles, main image wrapper)
[ rounded-2xl ] : 16px  (Trust block container, modal overlays)
[ rounded-full ]: 9999px(Color swatches, text size pills, wishlist heart button)
```

### 3.3 Elevation & Shadow Tokens

* **`shadow-sm`:** Micro cards, rating pills (`0 1px 2px 0 rgb(0 0 0 / 0.05)`).
* **`shadow-md`:** Active CTA hover state, dropdown menus (`0 4px 6px -1px rgb(0 0 0 / 0.1)`).
* **`shadow-lg`:** Mobile Sticky Purchase Bar (`z-50`), Full-screen Zoomer lightbox (`0 10px 15px -3px rgb(0 0 0 / 0.1)`).
* **`shadow-xl`:** Mobile Bottom Sheet Selector (`z-500`).

---

## 4. Component Design Specification (Full State Matrix)

Every interactive component on the HIGEST PDP is defined across six deterministic states:  
**(1) Default, (2) Hover, (3) Active/Selected, (4) Focus, (5) Disabled, (6) Loading.**

---

### 4.1 Product Gallery Component

* **Desktop Gallery (`desktop.blade.php`):**
  * **Vertical Thumbnail List:** `max-w-[100px]`, `max-h-[540px]`, item size `100x100px`, `rounded-xl`.
  * **Main Image Display:** `560px` max width, `610px` max height, `rounded-xl` with high-priority WebP image.
* **Mobile Gallery (`mobile.blade.php`):**
  * Edge-to-edge square container (`aspect-square w-screen`), swipe-enabled CSS translate.
* **States Matrix:**

| State | Thumbnail Visual Effect | Main Image Visual Effect |
| :--- | :--- | :--- |
| **Default** | Border `border-white`, opacity `0.85`. | Clean image render, `cursor-pointer`. |
| **Hover** | Border `border-zinc-300`, opacity `1.0`. | Subtle scale transform `scale-[1.01]` transition. |
| **Selected** | Ring border `border-2 border-navyBlue` (`#060C3B`). | Displays selected asset instantly. |
| **Focus** | Outline `ring-2 ring-darkBlue`. | Focus ring active for keyboard accessibility. |
| **Disabled** | Opacity `0.3`, `pointer-events-none`. | N/A |
| **Loading** | Shimmer block (`x-shop::shimmer.products.gallery`). | Shimmer skeleton `min-h-[607px] min-w-[560px] rounded-xl bg-zinc-200`. |

---

### 4.2 Price Block & Discount Badge Component

* **Component Tokens:**
  * Current Price: `text-3xl font-bold text-navyBlue`.
  * Strikethrough MSRP: `text-lg font-medium text-zinc-500 line-through`.
  * Savings Badge: `bg-darkPink text-white text-sm font-bold px-2.5 py-1 rounded-md`.
  * Tax Inclusion Label: `text-xs text-zinc-500` `(Tax Inclusive)`.

```
Price Block Design Composition:
+-------------------------------------------------------------+
| $49.99  <s>$79.99</s>  [ -37% OFF ]                         |
| (Tax Inclusive)                                             |
+-------------------------------------------------------------+
```

---

### 4.3 Variant Selector Component (`configurable.blade.php`)

```
Color Swatches (Circle):   (•) Red   ( ) Blue   ( ) Black
Size Swatches (Text Pill): [ S ]     [ M ]      [ L ]      [ XL (Disabled) ]
```

* **States Matrix for Color, Image & Text Swatches:**

| Swatch Type | Default State | Hover State | Active / Selected State | Disabled / Out-of-Stock State |
| :--- | :--- | :--- | :--- | :--- |
| **Color Swatch** | `h-8 w-8 rounded-full border border-gray-200` | `scale-110 shadow-sm` | `ring-2 ring-navyBlue ring-offset-2 scale-105` | Opacity `0.3`, diagonal strike-through icon overlay. |
| **Image Swatch** | `h-[60px] w-[60px] rounded-md border border-gray-200` | `border-gray-400` | `border-2 border-navyBlue shadow-sm` | Opacity `0.35`, gray filter `grayscale`. |
| **Text Swatch** | `px-5 py-3 rounded-full border border-gray-300 bg-white` | `bg-gray-50 border-gray-400` | `!bg-navyBlue text-white border-transparent` | Opacity `0.4`, background `bg-gray-100`, `cursor-not-allowed`. |

---

### 4.4 Purchase CTA Buttons (*Buy Now* & *Add to Cart*)

* **Primary CTA: *Buy Now***
  * Dimensions: Height `56px` (`h-14`), width `100%` (`max-w-[470px]`), `rounded-xl`.
  * Default: `bg-navyBlue text-white font-semibold text-base shadow-md`.
  * Hover: `bg-opacity-90 shadow-lg scale-[1.005] transition-all`.
  * Active: `scale-[0.99]`.
  * Loading: Button text hidden, inline white SVG spinner active (`isStoring.buyNow = true`).
  * Disabled: `bg-zinc-300 text-zinc-500 cursor-not-allowed opacity-60 shadow-none`.

* **Secondary CTA: *Add to Cart***
  * Dimensions: Height `56px` (`h-14`), width `100%`, `rounded-xl`.
  * Default: `bg-white border-2 border-navyBlue text-navyBlue font-semibold text-base`.
  * Hover: `bg-zinc-50 border-navyBlue`.
  * Loading: Inline navy SVG spinner active (`isStoring.addToCart = true`).

---

### 4.5 Dropshipping Transparency & Trust Card Component

* **Visual Container:** `rounded-2xl border border-zinc-200 bg-zinc-50/60 p-4 mt-6`.

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

* **States Matrix:**

| Item | Default Render | Interactive Hover / Click |
| :--- | :--- | :--- |
| **Origin Badge** | `text-xs font-semibold text-zinc-700` + Location Pin Icon | Tooltip showing warehouse region. |
| **Delivery Date** | `text-xs font-bold text-darkGreen` + Truck Icon | Opens Shipping Policy modal drawer. |
| **Tracking Status**| `text-xs font-medium text-zinc-600` + Package Icon | Static reassurance badge. |
| **Local Return** | `text-xs font-medium text-zinc-600` + Return Icon | Opens 14-Day Local RMA Policy modal. |

---

### 4.6 Product Reviews Component (`reviews.blade.php`)

* **Star Rating Summary:** `icon-star-fill text-amber-500 text-2xl`.
* **Rating Distribution Bar:** Background `bg-zinc-200 h-2.5 rounded-full`, fill `bg-amber-500 rounded-full`.
* **Review Attachments Thumbnail:** `h-16 w-16 rounded-lg border object-cover cursor-pointer hover:opacity-80`.

---

### 4.7 Mobile Sticky Purchase Bar Component (`v-mobile-sticky-bar`)

* **Dimensions & Layering:** Fixed `bottom-0 left-0 right-0 z-50 bg-white border-t border-zinc-200 p-3 shadow-2xl flex items-center justify-between gap-3`.
* **Content:** Truncated price (`text-lg font-bold text-navyBlue`) + Dual Action CTA Buttons (`h-11 rounded-lg text-sm`).
* **Z-Index Layer Hierarchy:** `Cookie Banner (z-40) < Mobile Sticky Bar (z-50) < Bottom Sheet Selector (z-500) < Lightbox Modal (z-1000)`.

---

## 5. Competitive Visual Synthesis

The HIGEST PDP design system synthesizes the top marketplace visual features into a cohesive, branded identity:

```
[ AliExpress ] --> High Conversion Density, Social Proof Counters, Rapid Variant Syncing
       +
[ Trendyol ]   --> Clean Typography, High Contrast Pricing, Elegant Card Structures
       +
[ SHEIN ]      --> Visual-First Media Carousels, Rich Image Swatches, Micro-Animations
       +
[ eBay ]       --> Uncompromising Trust Badges, Transparent Shipping & Local RMA Protection
       ================================================================================
       = HIGEST BRANDED VISUAL SYSTEM (Navy Blue #060C3B / Bagisto 2.4.x Native Tokenization)
```

---

## Document Authorization

**Prepared By:** Lead Product Architect & UX Systems Designer  
**Approved For Implementation:** HIGEST Core Platform Engineering Team  
**Target Delivery Phase:** RSR v3.0 Execution Cycle (Ready for RSR v3.0 Part 4 — Technical & Implementation Specification)
