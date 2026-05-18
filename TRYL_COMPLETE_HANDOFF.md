# ⚔️ TRYL Premium E-Commerce Core — Complete Handoff Document

> **Purpose:** This document gives a new AI assistant (Claude, etc.) everything needed to understand, debug, and extend the TRYL E-Commerce ecosystem to a production-ready, $10,000-grade launch.

---

## 1. Project Identity

| Field | Value |
|---|---|
| **Brand** | The Righteous Yield Life (TRYL) Apparel |
| **Live Domain** | `https://righteousyieldlife.com` |
| **Platform** | WordPress 6.5+ / WooCommerce 8.0+ / Divi Theme |
| **Repository** | `https://github.com/mistachatty-cmyk/TRYL-CORE.git` |
| **Local Workspace** | `c:\Users\glory\.gemini\antigravity\scratch\` |
| **Current Core Version** | `3.19` |
| **Author/Owner** | EHDesigns / LokServices |

---

## 2. Workspace File Tree (Annotated)

```
scratch/                              ← Git root
├── .git/                             ← Git repo (remote: GitHub TRYL-CORE)
├── TRyl_Ecommerce_Development_Summary.md ← Master dev log/journal
│
├── tryl-ecommerce-core/              ← ★ PRIMARY PLUGIN (WordPress MU or plugins/)
│   ├── tryl-ecommerce-core.php       ← 5,640 lines. THE monolith. All PHP logic.
│   ├── assets/
│   │   ├── css/tryl-core.css         ← Global storefront CSS (18KB)
│   │   └── js/tryl-core.js          ← Global frontend JS engine (19KB)
│   ├── templates/
│   │   ├── single-product.php        ← Nike-inspired PDP (Product Detail Page)
│   │   ├── checkout/
│   │   │   ├── form-checkout.php     ← 3-step accordion checkout form
│   │   │   └── thankyou.php          ← Post-purchase thank-you page
│   │   ├── page-righteous-shop.php   ← Shop grid shortcode router
│   │   └── 404.php                   ← Custom 404 page
│   ├── lokservices-bridge/           ← OTA update receptor (server-side)
│   │   └── lokservices-bridge.php    ← REST endpoint for remote plugin updates
│   ├── lokservices-bridge.php        ← Duplicate/legacy bridge (may need cleanup)
│   ├── lok_deploy.py                 ← Python deploy script (alt to Node)
│   ├── PRINTFUL_INTEGRATION_PLAN.md  ← Detailed Printful roadmap
│   ├── CHANGELOG.md / README.md      ← Documentation
│   ├── api-reference.html            ← Interactive API reference page
│   │
│   │  ── Chrome Extension Files (LokConnect) ──
│   ├── manifest.json                 ← MV3 Chrome Extension manifest
│   ├── popup.html                    ← Extension popup dashboard
│   ├── popup.css                     ← Extension styling (Obsidian Cyberpunk theme)
│   ├── index.js                      ← Extension logic controller
│   └── popup.js                      ← Legacy extension script (may be redundant with index.js)
│
├── tryl-editorial-skin/              ← Optional "editorial" visual skin plugin
│   └── tryl-editorial-skin.php       ← 42KB standalone visual override
│
├── tryl-shop-grid-plugin/            ← Legacy shop grid (superseded by core shortcode)
│   └── tryl-shop-grid.php            ← Stub/redirect
│
├── divi-tryl-child/                  ← WordPress child theme for Divi
│   ├── style.css                     ← Theme declaration
│   ├── functions.php                 ← Child theme functions
│   └── page-righteous-shop.php       ← Shop page template
│
├── lokbridge/                        ← ★ OTA UPDATE SERVER (Node.js)
│   ├── server.js                     ← Express server for update distribution
│   ├── deploy.js                     ← Build script: zips plugin + generates JSON manifest
│   ├── package.json
│   └── public/updates/               ← Built artifacts (zip + tryl-core.json)
│
└── tryl-website/                     ← Static website assets (if any)
```

---

## 3. Mandatory Development Protocols (MCPs)

These are non-negotiable architectural rules. **Every code change must comply.**

### MCP-1: Scorched-Earth FSE Bypass
```php
add_filter('woocommerce_has_block_template', '__return_false', 99999);
```
WooCommerce 8.0+ forces Full Site Editing block templates. This filter ensures our custom PHP templates (`single-product.php`, `form-checkout.php`) always take priority.

### MCP-2: GSAP Animation Engine
- All complex UI animations **MUST** use GSAP 3.12+.
- CSS transitions are **only** for simple hover states (color, opacity).
- GSAP is loaded globally: `https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js`

### MCP-3: LokConnect Headless Bridge
- All REST API endpoints for external tools → `tryl/v1` namespace.
- External clients authenticate via `X-TRYL-Extension-Key` header, **never** WordPress cookies.

### MCP-4: Fort Knox Security
- All Admin AJAX handlers → protected with `check_ajax_referer()`.
- All REST endpoints with heavy DB queries → wrapped in WordPress Transient cache (minimum 5 minutes).

### MCP-5: Printful Fulfillment Pipeline
- **Live billable order**: append `?confirm=1` to Printful `/orders` endpoint.
- **Sandbox/test order**: OMIT `?confirm=1`. This is the foundation of Sandbox Mode.

### MCP-6: Asynchronous Offload Mandate
- **NEVER** make blocking synchronous external API calls inside checkout hooks.
- **ALWAYS** use `wp_schedule_single_event()` to offload to background cron.

### MCP-7: Secure Media Handler
- Upload MIME filters **MUST** be gated behind `current_user_can('manage_options')`.
- Raw CSS from DB options → sanitize with `wp_strip_all_tags()` to prevent XSS.

---

## 4. Architecture Overview

### 4.1 The Monolith: `tryl-ecommerce-core.php`

This single 5,640-line file contains **everything**. Here's the section map:

| Section | Approx Lines | Purpose |
|---|---|---|
| Asset Enqueuing | 30-44 | Loads GSAP, `tryl-core.css`, `tryl-core.js`, localizes settings |
| Global Mini Cart Vars | 46-60 | Inlines `trylMiniCart` config object into `<head>` |
| Typography System | 62-101 | Font pack selector (Default/Editorial/Technical/Minimalist/Custom/Lok) |
| Font Upload MIME | 102-125 | Allows `.ttf/.woff/.woff2/.otf` uploads (admin-gated per MCP-7) |
| WooCommerce Template Overrides | ~130-170 | Forces custom templates, FSE bypass |
| Shop Grid Shortcode `[tryl_3d_shop]` | ~170-600 | Full product grid with category filtering, AJAX cart |
| Injected Navigation Bar | ~600-1200 | Global sticky nav, mobile drawer, theme switcher, cart icon |
| Mini Cart Drawer HTML | ~1200-1930 | Slide-out cart drawer with GSAP animations |
| Mini Cart Inline Script | ~1906-1930 | Config init + auto-open on Woo notices (logic moved to `tryl-core.js`) |
| AJAX Handlers | ~1930-2050 | `tryl_ajax_add_to_cart`, `tryl_refresh_minicart`, `tryl_update_cart` |
| Admin Dashboard | ~2050-4200 | Full settings UI: General, Shop, Checkout, Integrations, Typography, etc. |
| Printful Integration | ~4200-4600 | Token storage, webhook handlers, order routing, sandbox mode |
| LokConnect REST API | ~4600-4670 | `/ecosystem-stats` + `/sync-font` endpoints |
| CRM Automations | ~4700-5000 | Welcome email/SMS, Mailchimp/Klaviyo opt-ins |
| Gutenberg Blocks | ~5000-5400 | Prayer Wall, Order Tracker, etc. |
| Order Progress Tracker | ~5400-5640 | Customer-facing order status visualizer |

### 4.2 Frontend JS Engine: `tryl-core.js`

| Section | Purpose |
|---|---|
| Navigation & Theme Switcher | Hamburger menu, mobile drawer, `data-theme` toggling |
| Checkout GSAP Animations | Sequenced field entrances, floating label effects |
| Mini Cart Drawer Controller | `openCart()`, `closeCart()`, `refreshCart()` — exposed globally as `window.trylOpenCart`, `window.trylCloseCart`, `window.trylRefreshCart` |
| AJAX Add to Cart (Grid) | Delegated click handler for `.tryl-atc` / `.tryl-atc-variation` |
| AJAX Add to Cart (Single Product) | Form submit interceptor for `form.cart` |
| Inline Variation Dropdown | GSAP-animated variation selector for variable products |
| Cart Qty/Remove Handlers | `bindQtyButtons()` for drawer item manipulation |
| GSAP Visual Feedback | Scale/Glow/Bounce animations on cart icon after add |

### 4.3 Templates

**`single-product.php`** — Nike-inspired Product Detail Page:
- 2-column layout: image gallery grid (left) + sticky info panel (right)
- Multi-theme support (Bright / Mild / Dark via CSS variables)
- Nike-style size selector buttons for variable products
- "Buy It Now" direct-to-checkout routing
- Accordion for sizing/fit guides (admin-configurable)
- Sticky mobile ATC bar
- Related products grid

**`form-checkout.php`** — 3-Step Accordion Checkout:
- Step 1: Contact & Billing
- Step 2: Shipping Details
- Step 3: Payment (Stripe/PayPal/etc via WooCommerce hooks)
- Sticky Order Summary sidebar
- Preserves all `woocommerce_checkout_*` hooks for gateway compatibility

**`thankyou.php`** — Post-Purchase Confirmation Page

---

## 5. Current Working State

### ✅ What's Working
- Global injected navigation bar with cart count badge
- Mobile hamburger drawer with theme switcher (Bright/Mild/Dark)
- AJAX Add to Cart from shop grid (simple + variable products)
- AJAX Add to Cart from single product page forms
- Mini cart slide-out drawer with GSAP animations
- Cart quantity adjustment and item removal in drawer
- Drawer auto-refresh after cart modifications
- Nike-inspired single product template with gallery grid
- 3-step accordion checkout form
- Typography system with 5 font packs + custom font injection
- LokConnect Chrome Extension (connection, stats, font sync)
- Admin settings dashboard with tabbed navigation
- Printful token storage and basic webhook scaffolding
- OTA update system via LokBridge (Node.js zip + manifest compiler)
- Gutenberg blocks (Prayer Wall, Order Tracker)

### ⚠️ Known Issues / Gaps
1. **Printful mockup generation not connected** — The Printful Mockup API is planned but not implemented. Product images are currently manually uploaded.
2. **Apple Pay / Express Payments** — Not yet integrated. Requires Stripe's Payment Request Button API or WooCommerce Payments plugin.
3. **Checkout payment gateway UI** — Currently relies on default WooCommerce gateway rendering inside Step 3. Needs custom styling to match the Nike aesthetic.
4. **No product reviews section** — The single product template omits WooCommerce reviews.
5. **`popup.js` vs `index.js` conflict** — Two Chrome Extension JS files exist. `index.js` is the correct one; `popup.js` is legacy and should be removed.
6. **`lokservices-bridge.php` duplicate** — Exists both at root and inside `lokservices-bridge/` subdirectory.

---

## 6. Owner Sovereignty Rules

> [!IMPORTANT]
> These are non-negotiable business rules from the store owner:

1. **No Automated Pricing** — The welcome discount code `WELCOME10` is stored as inert text only. It must be manually activated by the owner in WooCommerce > Coupons. Do NOT programmatically create or activate discount codes.
2. **Printful Sandbox Default** — All Printful order submissions MUST default to sandbox mode (no `?confirm=1`) unless explicitly toggled to live by the owner in the dashboard.
3. **Owner Controls All Settings** — Every feature must have a toggle in the admin dashboard. Nothing should be forced on.

---

## 7. Launch Roadmap: $10,000 Premium Site

### Phase 1: Printful Product Page Integration 🔥

**Goal:** When a customer visits a product page, they see all Printful mockup angles automatically.

**Tasks:**
1. **Implement Printful Mockup API Integration**
   - On product sync, call `POST https://api.printful.com/mockup-generator/create-task/{id}`
   - Poll task status, then fetch generated mockup URLs
   - Auto-attach mockups to WooCommerce product gallery via `wp_insert_attachment()`
   - Show all angles (front, back, side, detail) in the `single-product.php` gallery grid

2. **Enhance Product Gallery**
   - Add image lightbox/zoom on click (GSAP-powered)
   - Thumbnail strip below main image for angle selection
   - Lazy loading with skeleton placeholders

3. **Dynamic Product Description Engine**
   - Auto-generate product descriptions from Printful metadata (material, print technique, weight)
   - Format into clean accordions: "Product Details", "Material & Care", "Sizing & Fit"

4. **Sizing Guide Integration**
   - Fetch size charts from Printful API per product type
   - Render as a modal or accordion on the product page
   - Show measurements in both inches and centimeters

### Phase 2: Nike-Inspired Checkout with Apple Pay 💳

**Goal:** A streamlined, conversion-optimized checkout that supports express payments.

**Tasks:**
1. **Stripe Payment Request Button (Apple Pay / Google Pay)**
   - Install WooCommerce Stripe Gateway plugin (or implement via Stripe.js directly)
   - Mount `PaymentRequest` button above the checkout form
   - Style to match the TRYL dark/minimal aesthetic
   - Test on Safari (Apple Pay) and Chrome (Google Pay)

2. **Express Checkout Bar**
   - Render Apple Pay / Google Pay / Shop Pay buttons at the top of checkout
   - If customer uses express checkout, skip Steps 1-2 (billing/shipping auto-filled)
   - Fall through to standard 3-step form if no express payment available

3. **Payment Gateway Styling**
   - Override Stripe Elements iframe styling to match the dark theme
   - Custom credit card input with floating labels
   - Error states with GSAP shake animation

4. **Modular Payment Dashboard**
   - Admin panel to toggle which payment methods are shown
   - Drag-and-drop ordering of payment options
   - Per-gateway enable/disable toggles

### Phase 3: Dashboard Payment Branches 🌿

**Goal:** The admin dashboard provides full control over payment routing.

**Tasks:**
1. **Payment Methods Manager** in Settings > Checkout Flow tab
   - List all active WooCommerce gateways
   - Toggle visibility per gateway
   - Set display order
   - Custom labels (e.g., "Pay with Apple Pay" instead of default text)

2. **Conditional Payment Rules**
   - Show/hide gateways based on cart total, product category, or customer location
   - Example: Show PayPal only for international orders
   - Store rules as serialized option, evaluate at checkout render time

### Phase 4: Visual Polish & Performance ✨

**Goal:** Every pixel screams premium.

**Tasks:**
1. **Product Page Polish**
   - Image hover zoom effect (GSAP `scale` on mousemove)
   - "Complete The Look" recommendations section (cross-sell)
   - Trust badges below Add to Cart (SSL, Free Returns, etc.)
   - Animated "Added to Cart" confirmation with product thumbnail flying to cart icon

2. **Checkout Polish**
   - Progress indicator bar at top (Step 1 → 2 → 3 → Done)
   - Order summary with product thumbnails (not just text)
   - Promo code field with inline validation
   - Estimated delivery date display

3. **Performance**
   - Lazy load all product images
   - Defer non-critical CSS
   - Minimize render-blocking scripts
   - Add `loading="lazy"` to all `<img>` tags
   - Implement critical CSS inlining for above-the-fold content

4. **SEO Hardening**
   - JSON-LD structured data for products (already started via `woocommerce_before_main_content`)
   - Open Graph meta tags for social sharing
   - Canonical URLs on all product pages
   - XML sitemap integration verification

---

## 8. Key Technical Reference

### WooCommerce Hooks in Use
```php
// Template overrides
add_filter('woocommerce_has_block_template', '__return_false', 99999);
add_filter('template_include', 'tryl_override_single_product_template');

// AJAX endpoints
add_action('wp_ajax_tryl_ajax_add_to_cart', 'tryl_ajax_add_to_cart_handler');
add_action('wp_ajax_nopriv_tryl_ajax_add_to_cart', 'tryl_ajax_add_to_cart_handler');
add_action('wp_ajax_tryl_refresh_minicart', 'tryl_refresh_minicart_handler');
add_action('wp_ajax_nopriv_tryl_refresh_minicart', 'tryl_refresh_minicart_handler');
add_action('wp_ajax_tryl_update_cart', 'tryl_update_cart_handler');
add_action('wp_ajax_nopriv_tryl_update_cart', 'tryl_update_cart_handler');
```

### Global JS Variables Available on Frontend
```javascript
window.trylMiniCart = {
  ajaxurl: '/wp-admin/admin-ajax.php',
  btnText: 'Added!',
  autoOpen: '1',           // Auto-open drawer on add
  animEffect: 'scale',     // 'scale' | 'glow' | 'bounce' | 'none'
  checkoutUrl: '/checkout/'
};

window.trylCoreSettings = {
  ajaxurl: '/wp-admin/admin-ajax.php',
  btnText: 'Added!',
  checkoutAnimations: '1',
  isCartOrCheckout: '0'
};

// Globally exposed cart functions
window.trylOpenCart();
window.trylCloseCart();
window.trylRefreshCart(callback);
```

### REST API Endpoints
| Endpoint | Method | Auth | Purpose |
|---|---|---|---|
| `/wp-json/tryl/v1/ecosystem-stats` | GET | `X-TRYL-Extension-Key` | Dashboard metrics for Chrome Extension |
| `/wp-json/tryl/v1/sync-font` | POST | `X-TRYL-Extension-Key` | Remote typography injection |
| `/wp-json/tryl-printful/v1/webhook` | POST | Printful signature | Inventory/status webhooks |
| `/wp-json/tryl-printful/v1/order-webhook` | POST | Printful signature | Order fulfillment webhooks |
| `/wp-json/lokservices/v1/deploy` | POST | Deploy key | OTA plugin update receptor |

### Printful API Reference (for implementation)
```
Base URL: https://api.printful.com
Auth: Bearer {tryl_printful_token}

GET    /store/products              → List synced products
GET    /store/products/{id}         → Get product with variants
POST   /orders                      → Create order (sandbox)
POST   /orders?confirm=1            → Create order (LIVE/BILLABLE)
POST   /mockup-generator/create-task/{id} → Generate mockups
GET    /mockup-generator/task?task_key={key} → Check mockup status
GET    /products/{id}/sizes         → Get size chart
```

---

## 9. Git State

```
Branch: main
Remote: origin → https://github.com/mistachatty-cmyk/TRYL-CORE.git
Status: 2 commits ahead of origin/main (need to push)

Recent commits:
2d0d4fc fix(core): remove duplicate inline js and stabilize cart ajax pipeline
b3914c5 feat: implement public order tracker beta with Gutenberg blocks
c589005 chore: Bump version to 3.16.0, optimize local compiler
e5f6f57 feat: Add premium sizing reveal options, GSAP feedbacks, cart settings
2d730c6 feat: implement TRyl E-Commerce core with GSAP animations
```

---

## 10. Quick Start for New AI Assistant

1. **Clone**: `git clone https://github.com/mistachatty-cmyk/TRYL-CORE.git`
2. **Primary file**: `tryl-ecommerce-core/tryl-ecommerce-core.php` — this is the monolith
3. **Frontend JS**: `tryl-ecommerce-core/assets/js/tryl-core.js` — all client-side logic
4. **Frontend CSS**: `tryl-ecommerce-core/assets/css/tryl-core.css` — global styles
5. **Product template**: `tryl-ecommerce-core/templates/single-product.php`
6. **Checkout template**: `tryl-ecommerce-core/templates/checkout/form-checkout.php`
7. **Read the MCPs above** — they are non-negotiable architectural rules
8. **Build OTA package**: `cd lokbridge && node deploy.js`
9. **The site runs on Divi theme** with the child theme in `divi-tryl-child/`

> [!CAUTION]
> The `tryl-ecommerce-core.php` file is a 5,640-line monolith. Be surgical with edits. Always use line-targeted diffs, never replace the whole file.

---

*Document compiled for AI-assisted handoff. Last updated: 2026-05-18.*
