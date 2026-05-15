# TRYL Premium E-Commerce Core — Documentation

**Version:** 3.3 | **Author:** EHDesigns | **Powered by:** LokServices  
**Plugin Slug:** `tryl-ecommerce-core`

---

## Table of Contents

1. [What This Plugin Does](#1-what-this-plugin-does)
2. [Installation & Activation](#2-installation--activation)
3. [The TRYL Settings Dashboard](#3-the-tryl-settings-dashboard)
4. [Feature Reference](#4-feature-reference)
5. [Deployment via LokServices Bridge](#5-deployment-via-lokservices-bridge)
6. [Template Files](#6-template-files)
7. [Shortcodes](#7-shortcodes)
8. [AJAX Endpoints](#8-ajax-endpoints)
9. [Troubleshooting](#9-troubleshooting)
10. [Changelog](#10-changelog)

---

## 1. What This Plugin Does

`tryl-ecommerce-core.php` is the **all-in-one WooCommerce enhancement layer** for The Righteous Yield Life. It replaces the need for multiple third-party plugins by consolidating the following into a single managed file:

| Feature | Description |
|---|---|
| **Global Typography** | Switchable font packs applied site-wide via CSS variables |
| **Shop Grid** | Premium 4-column product grid with category filtering and tilt effects |
| **Single Product Override** | Custom Nike-inspired product page template |
| **Mini Cart Drawer** | GSAP-powered slide-in AJAX cart (no page refresh) |
| **Premium Cart & Checkout** | Full WooCommerce cart/checkout reskin with 2-col checkout grid |
| **Global Nav** | Fixed header nav injected on every page, with mobile hamburger |
| **Global Footer** | Custom footer with configurable layout (grid/flex), hover animations |
| **Product Badges** | Auto "New Drop" / "Bestseller" / "Sale" labels on shop cards |
| **Next-Gen Emails** | WooCommerce transactional email rebrand |
| **Exit-Intent Popup** | Newsletter capture popup (Mailchimp/Klaviyo compatible) |
| **LokServices Bridge** | Secure REST API endpoint for remote file deployment |
| **TRYL Settings Dashboard** | Central admin UI to configure all features without touching code |

---

## 2. Installation & Activation

### Method A — Manual Upload (Recommended for first install)
1. Zip the entire `tryl-ecommerce-core/` folder
2. In WordPress Admin → **Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Activate Plugin**
4. The **"TRYL Settings"** menu will appear in the left sidebar

### Method B — LokServices Bridge (Ongoing updates)
Use `lok_deploy.py` to push the file directly without needing SFTP or file manager access. See [Section 5](#5-deployment-via-lokservices-bridge).

### Required Plugins
- **WooCommerce** — Required for shop, cart, checkout, and mini-cart features. All WooCommerce-dependent code is safely guarded with `class_exists('WooCommerce')` checks.

---

## 3. The TRYL Settings Dashboard

Navigate to **WordPress Admin → TRYL Settings** (left sidebar icon: gear ⚙).

> **If the TRYL Settings menu is missing**, it means the plugin is not active or a PHP error is preventing it from loading. Check **Plugins** page to confirm it shows as Active. If it does, check the browser console or PHP error log for fatal errors.

### Dashboard Cards

| Card | Key Settings |
|---|---|
| **General Options** | Default theme (Bright/Mild/Dark), Font Pack, Grid product limit, Header Logo URL |
| **Product Badges** | Enable toggle, Days for "New Drop", Sales threshold for "Bestseller", badge colors |
| **Global Navigation** | Custom URLs for Mission, Prayer, Contact, Shipping, FAQ, Privacy, Terms pages |
| **Cart & Footer** | Footer layout (Grid/Flex), hover animations, mobile centering, free shipping threshold, developer signature |
| **Exit-Intent Popup** | Enable toggle, heading, body text, form action URL (Mailchimp/Klaviyo), button text |
| **Next-Gen Order Emails** | Enable toggle, hero image URL, footer message |
| **Checkout Experience** | Enable/disable GSAP checkout animations |
| **Notifications** | Prayer request email recipient address |
| **Integrations** | Printful webhook secret token |

### Saving Settings
Click the sticky **"Save TRYL Settings"** button in the bottom-right corner of the page.

### Theme Switcher
The nav bar includes Bright / Mild / Dark theme buttons that persist via `localStorage`. The default theme at first visit is set in **General Options → Default Theme Mode**.

---

## 4. Feature Reference

### 4.1 Global Typography System
**Option:** `tryl_font_pack` | Default: `default`

| Pack | Heading Font | Body Font |
|---|---|---|
| `default` | Barlow Condensed | Inter |
| `editorial` | Cormorant Garamond | Inter |
| `technical` | Oswald | Roboto |
| `minimalist` | Montserrat | Open Sans |

Fonts are loaded via Google Fonts and applied using `--tryl-header-font` and `--tryl-body-font` CSS variables, which cascade into all plugin components automatically.

---

### 4.2 Shop Grid Shortcode
**Shortcode:** `[tryl_3d_shop]`  
Place this on any page (e.g., the Righteous Shop page) to render the full product grid.

**What it renders:**
- A filterable 4-column product grid (responsive: 3-col → 2-col → 1-col)
- Category filter buttons that show/hide cards instantly
- 3D tilt effect on product images (via Vanilla Tilt)
- Hover overlay with "Buy Now" / "View Details" buttons
- Dynamic product badges ("New Drop", "Bestseller", "Sale")
- AJAX "Add to Cart" button at the bottom of each card
- A "Powered By" signature line at the bottom

---

### 4.3 Mini Cart Drawer
The floating cart drawer slides in from the right when any "Add to Cart" action fires.

- **Open trigger:** `window.trylOpenCart()` (callable from any JS on the page)
- **Auto-refresh:** The drawer re-renders its item list via AJAX after every cart update
- **Free shipping nudge:** Configurable via the **Free Shipping Threshold** setting
- **Condition:** Only loaded on WooCommerce pages, the shop page, and pages using the `[tryl_3d_shop]` shortcode — it does **not** load globally to avoid conflicts

---

### 4.4 Product Badges
Badges appear as small labels in the top-left corner of each product card image.

**Priority order:**
1. ✅ **Bestseller** — if `total_sales >= tryl_badges_bestseller_sales`
2. ✅ **New Drop** — if product was created within `tryl_badges_new_days` days
3. ✅ **Sale** — always shows if WooCommerce marks the product on sale (no setting required, always active when badge system is on)

---

### 4.5 Exit-Intent Popup
Triggers when:
- **Desktop:** User's mouse cursor exits the top of the browser window (`clientY < 10`)
- **Mobile:** After 30 seconds on page (fallback timer)

Once dismissed, a `localStorage` key (`tryl_popup_closed`) is set and the popup will **not** show again for that browser session.

**Form URL:** Set the form action to your Mailchimp or Klaviyo embed URL to capture emails directly.

---

### 4.6 Next-Gen Order Emails
When enabled, overrides WooCommerce's default email CSS with TRYL brand styles:
- Dark header (`#0d1b0f`) with white uppercase type
- Clean white body with bordered section headers
- Optional hero image banner below the header
- Custom footer message before the standard WooCommerce footer

---

## 5. Deployment via LokServices Bridge

The `lok_deploy.py` script pushes local files to your live WordPress site over the REST API. **No SFTP credentials needed.**

### Setup
The API key is already configured in `lok_deploy.py` line 5. To find or reset it:
1. WordPress Admin → **Settings → LokServices Bridge**
2. Copy the **Secret API Key** value
3. Paste it into `lok_deploy.py` as `API_KEY = "your-key-here"`

### Commands

```bash
# Deploy ALL files in the manifest
python lok_deploy.py batch

# Deploy a single file (local path → remote wp-content path)
python lok_deploy.py deploy tryl-ecommerce-core/tryl-ecommerce-core.php plugins/tryl-ecommerce-core/tryl-ecommerce-core.php

# Check which manifest files exist on the server
python lok_deploy.py status
```

### The MANIFEST
The manifest in `lok_deploy.py` maps every remote WordPress file path to its local equivalent in this workspace:

| Remote (in wp-content/) | Local (in scratch/) |
|---|---|
| `plugins/tryl-ecommerce-core/tryl-ecommerce-core.php` | `tryl-ecommerce-core/tryl-ecommerce-core.php` |
| `plugins/tryl-ecommerce-core/templates/single-product.php` | `tryl-ecommerce-core/templates/single-product.php` |
| `plugins/tryl-ecommerce-core/templates/page-righteous-shop.php` | `tryl-ecommerce-core/templates/page-righteous-shop.php` |
| `plugins/tryl-editorial-skin/tryl-editorial-skin.php` | `tryl-editorial-skin/tryl-editorial-skin.php` |
| `themes/divi-tryl-child/page-righteous-shop.php` | `divi-tryl-child/page-righteous-shop.php` |
| `themes/tryl-theme/functions.php` | `tryl-website/tryl-theme/functions.php` |

---

## 6. Template Files

Located in `tryl-ecommerce-core/templates/`:

| File | Purpose |
|---|---|
| `single-product.php` | Overrides WooCommerce's default single product page. Nike-inspired layout with large image, bold title, AJAX add-to-cart, and related products. |
| `page-righteous-shop.php` | The main shop page template. Renders the `[tryl_3d_shop]` shortcode inside the theme wrapper. Used if the page is assigned this template in WordPress. |

---

## 7. Shortcodes

| Shortcode | Description |
|---|---|
| `[tryl_3d_shop]` | Full product grid with filtering, badges, and AJAX cart |

---

## 8. AJAX Endpoints

All endpoints are registered on `wp-admin/admin-ajax.php`. They are **WooCommerce-guarded** — if WooCommerce is inactive, they return a clean JSON error instead of crashing.

| Action | Method | Purpose |
|---|---|---|
| `tryl_ajax_add_to_cart` | POST | Add a simple product to cart. Sends `product_id`, `quantity`. |
| `tryl_update_cart` | POST | Update or remove a cart item. Sends `cart_key`, `quantity` (0 = remove). |
| `tryl_refresh_minicart` | POST | Returns fresh HTML for the mini cart drawer + subtotal + count. |

**REST API (LokServices Bridge):**

| Endpoint | Method | Auth Header | Purpose |
|---|---|---|---|
| `/wp-json/lokservices/v1/deploy` | POST | `X-Lok-Key: your-key` | Write a file to wp-content/ from a JSON payload |

---

## 9. Troubleshooting

### ❌ "TRYL Settings" menu is missing from WordPress admin

**Most likely cause:** The plugin file was not deployed to WordPress, or a PHP error is preventing it from loading.

**Fix checklist:**
1. Go to **WordPress Admin → Plugins** and confirm `TRYL Premium E-Commerce Core Universal` is listed and **Active**
2. If it's not listed, deploy it: `python lok_deploy.py deploy tryl-ecommerce-core/tryl-ecommerce-core.php plugins/tryl-ecommerce-core/tryl-ecommerce-core.php`
3. If it's listed but inactive, click **Activate** — if it fails, there's a PHP error in the file
4. Enable WP_DEBUG in `wp-config.php` (`define('WP_DEBUG', true); define('WP_DEBUG_LOG', true);`) and check `/wp-content/debug.log`

### ❌ Checkboxes won't turn off (stuck on)

This is a known WordPress Options API bug. It has been fixed in v3.3 with `<input type="hidden" value="0">` before every checkbox. Ensure you are running the latest deployed version.

### ❌ Mini cart doesn't open

- Verify the page has the `[tryl_3d_shop]` shortcode, is a WooCommerce page, or is using `page-righteous-shop.php` as its template
- The mini cart only loads on those pages — not globally — to prevent layout conflicts
- Open browser console and check for JS errors

### ❌ AJAX add-to-cart returns an error

- Check that WooCommerce is active
- Confirm `wc_ajax_url` is being localized (it is, via `tryl_localize_minicart`)
- Check browser Network tab for the raw admin-ajax.php response

### ❌ Fonts not changing after selecting a new Font Pack

- Font pack is applied at page load via `wp_head` priority 1
- After saving settings, do a **hard refresh** (Ctrl+Shift+R) to bypass browser cache
- CSS variables (`--tryl-header-font`, `--tryl-body-font`) are set in a `<style>` tag in `<head>` and cascade into all plugin CSS

---

## 10. Changelog

| Version | Changes |
|---|---|
| **3.3** | Fixed checkbox Options API bug (hidden input fallback). Added WooCommerce class_exists() guards on all AJAX handlers. Added Next-Gen Email settings card. Added Checkout Animations toggle. Registered all missing settings keys. |
| **3.2** | Added intuitive Dev Dashboard with grid layout, iOS toggles, sticky save bar. Added Font Pack switcher. Added Product Badges system. Added Exit-Intent Popup. Added Next-Gen WooCommerce Email styling. Expanded footer to use settings-driven URLs. |
| **3.1** | Added single product template override, AJAX single-product add-to-cart, and conditional mini-cart loading logic. |
| **3.0** | Initial consolidation of premium cart, checkout, shop grid, global nav, and footer features. |