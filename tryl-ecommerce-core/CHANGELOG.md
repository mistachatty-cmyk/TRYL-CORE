C:\Users\glory\.gemini\antigravity\scratch\tryl-ecommerce-core\tryl-ecommerce-core.php# Changelog

All notable changes to the TRYL Premium E-Commerce Core will be documented here.

## [3.15.0]

### Added
- Customer Order Progress Visualizer: Added a responsive, SVG-powered visual progress tracker to the WooCommerce "View Order" page in the customer dashboard (My Account). Customers can now visually track their order from "Placed" -> "Production" -> "Shipped", heavily reducing customer support inquiries.

## [3.14.0]

### Added
- Gutenberg Block Suite Completion (Phase 6): Officially bridged the Prayer System (both the Form and the Public Wall) into the React-powered Gutenberg environment. Clients can now drag-and-drop the Prayer Wall into pages and see live masonry grid previews securely in the backend.

## [3.13.0]

### Added / Optimized
- Native Welcome Automations (CRM): You can now automatically fire an instant Welcome Email and Welcome SMS (via Twilio) the moment someone subscribes to your newsletter list!
- Extensive Marketing Customizations: The entire messaging ecosystem is now fully editable from the dashboard. You can customize the Exit-Intent Popup success message, the Checkout opt-in label, and set default messaging (e.g., "Find Out When New Bless's Are In!").

## [3.12.0] - The Automation & Stability Update

### ✨ New Features & Enhancements
- **Native Marketing Automations:** You can now connect Mailchimp and Klaviyo directly inside the TRYL dashboard! We added a premium toggle to the checkout sidebar, allowing your customers to easily subscribe to newsletters during their purchase.
- **AJAX Exit-Intent Popup:** We upgraded your exit popup. When customers enter their email, it submits instantly in the background without reloading the page or opening annoying new tabs.
- **LokBridge OTA Network:** We officially integrated the LokBridge distribution network. Your store will now automatically receive secure, over-the-air (OTA) updates directly from our private servers, exactly like official WordPress plugins!

### 🐛 Bug Fixes & Optimizations
- **Fixed Checkout Freezing:** Previously, if Mailchimp's servers were slow, your customers' checkout wheel would spin indefinitely. We completely rewrote this logic to be "Asynchronous." Now, the customer's order completes instantly, and the system adds them to Mailchimp 10 seconds later in the background. Blazing fast!
- **Fixed Dashboard Slowdowns:** We re-enabled background caching for the LokBridge updater to prevent it from slowing down your WordPress admin dashboard while checking for new updates.

## [3.11.0]

### Added / Optimized
- Granular Typography Engine: Decoupled the font architecture into CSS variable tokens, adding granular targets for `Hero Headline`, `Navigation`, and `Buttons`. Fallback inheritance handles empty states safely.
- Live Font Scroller: Built an animated marquee into the dashboard that dynamically updates its font-family based on the user's inputs to preview Custom or Lok Extension fonts in real-time.
- LokConnect Font Receptor API: Created the `POST /tryl/v1/sync-font` endpoint. This allows the future Chrome Extension to automatically inject cloud fonts directly into the WordPress styling engine.
- PDF Engine Polish: Built dedicated `@media print` CSS rules into the admin dashboard so that clicking the "Download Documentation as PDF" button yields a perfectly formatted, paginated technical manual without any sidebar clutter.

## [3.10.0]

### Added / Optimized
- Enterprise Security Polish: Implemented stringent CSRF `check_ajax_referer` nonces for all sensitive Admin AJAX handlers (Data Purge, DB Cleanup, Force Sync).
- LokConnect API Optimization: Wrapped the headless `/tryl/v1/ecosystem-stats` REST endpoint in a 5-minute caching Transient. This prevents the Chrome Extension from degrading site performance by caching heavy `WP_Query` meta lookups.

## [3.9.0]

### Added
- Advanced Typography Manager: Added a system filter to securely allow WordPress Media Library uploads for `.ttf`, `.woff`, and `.woff2` font files. Added custom `@font-face` CSS editors and explicit Font Family target inputs.
- Lok Font Cloud Sync: Added the UI scaffolding in the Design tab to allow the upcoming LokServices Chrome Extension to push fonts directly into the WordPress ecosystem.

## [3.8.0]

### Added
- LokConnect Extension API: Registered headless REST endpoint (`/tryl/v1/ecosystem-stats`) guarded by a custom cryptographic header token (`X-TRYL-Extension-Key`). Prepares the store to integrate natively with a future LokServices Chrome Extension for live remote analytics monitoring.

## [3.7.0]

### Added
- Data & Migration Tab (Phase 5): Added a dedicated tab for backend database management.
- Bulk Operations: Added a "Force Full Printful Sync" button that overrides cron schedules and forces an immediate catalog/stock alignment via AJAX.
- Database Cleanup: Added an intelligent scrub tool that hunts down and deletes orphaned Printful metadata left behind by deleted products.
- Settings Export: Built a 1-click JSON generator to instantly backup the entire TRYL configuration ecosystem.

## [3.6.0]

### Added
- Developer Sandbox Mode: Safely test the entire checkout and order routing flow. Intercepts Printful API calls and sends them as Drafts, guaranteeing your card is never charged during testing. 
- Live Fulfillment Hotfix: Discovered that Printful defaults to drafting orders. The live engine now correctly appends the `?confirm=1` parameter to automatically bill and fulfill live orders.
- Sandbox Data Purge: Added a 1-click database cleanup tool to permanently delete any fake WooCommerce orders created while Sandbox Mode was active.

## [3.5.0]

### Added
- Printful Live Shipping Rates: Added a custom WooCommerce shipping method that pings the Printful API (`/orders/estimate-costs`) at checkout to return exact real-time shipping rates.
- Smart Shipping Valve: Configurable fallback flat-rate shipping cost added just in case the Printful API times out, preventing lost sales.
- WhatsApp Tracking (Meta Cloud API): Added a zero-cost tracking alternative using official WhatsApp template messaging for Order Confirmations and Printful Shipping updates.
- Supercharged Tracking Emails: Intercepts the Printful shipped webhook and fires a beautifully branded HTML email via the WooCommerce mailer system.
- Communications Tab: New dedicated settings tab featuring a step-by-step Meta API tutorial and a JS-powered "Preset Engine" allowing owners to instantly switch tracking notification styles (Luxury, Faith-Forward, Direct) with a "Reset to Default" safety feature.
- Printful Branding API: Configured dynamic packing slip integration that automatically intercepts WooCommerce gift messages and appends them to Printful's white-label packaging inserts.
- Dashboard UI Customizer: Added a new settings tab to allow real-time live-preview customization of the entire TRYL WP Admin dashboard. Added local-storage powered "Unseen" notification dots.
- Analytics Dashboard: Added the Analytics & Reports tab (Phase 4), featuring live product/order sync counts and financial SLA configuration.

## [3.4.0]

### Added
- Printful Mockup Sideloading: Automatically downloads high-resolution Printful mockups directly into the WordPress Media Library instead of hotlinking them.
- 3D / AI Mockup Pipeline: Added "Mockup Generation Engine" setting to optionally fetch advanced 3D Printful mockups.
- Smart Fallback Valve: If a 3D/AI mockup fails to generate or times out, the system automatically falls back to standard mockups to guarantee zero broken images.
- Printful Integration Column: Added a custom visual column to the WooCommerce product list showing mockup sync status (Images Synced vs Pending Mockup).
- Bulk Mockup Generation: Added a 1-click "Generate TRYL Mockups" bulk action to sync 50+ product mockups at once.

### Fixed
- Settings Dashboard: Resolved a silent PHP Fatal Error by correctly defining `tryl_documentation_tab_content()`, restoring tab navigation and saving capabilities.

## [3.3.0]

### Added
- Functional Checkout Switches: Hooked "Gift Wrapping" and "Eco-Packaging" toggles into the WooCommerce backend. Added a $5.00 Gift Wrapping fee via AJAX and saved user preferences to Order Meta.
- Universal Reference & Guide: Added a dedicated documentation card to the TRYL Settings dashboard for quick access to shortcodes and feature logic.
- Shortcode Alias: Added `[tryl_prayer_request]` as an alias for the prayer form shortcode.

### Fixed
- Shortcode Registration: Moved all shortcode registrations into the `init` hook to ensure compatibility across themes and page builders.


## [3.2.0]

### Added
- TRYL Core Settings Dashboard: Added an intuitive WP Admin menu to configure the Default Theme, Shop Grid Product Limit, Nav Links, Free Shipping Threshold, Footer Description, and Prayer Request Notification Email.
- Prayers Dashboard (Custom Post Type): Built a user-friendly backend UI allowing admins to read submitted prayers, see pending/replied statuses, and send replies directly to users. 
- Automated Prayer Auto-Responder: The system now immediately emails users thanking them for their prayer submission with a comforting verse.

## [3.1.0]

### Added
- `templates/single-product.php`: A premium, technical minimalist product page replacing the default theme blog view.
- AJAX Add to Cart interceptor for single product forms to integrate with the mini-cart drawer without page reloads.
- `tryl_should_load_mini_cart()` helper to restrict mini-cart assets to WooCommerce pages and specific shortcodes, keeping the rest of the site lightweight.

### Changed
- Plugin Version: 3.3
- Righteous Shop Grid (`page-righteous-shop.php`): Modified "Buy Now" button logic to redirect directly to checkout with the item added, skipping the cart page. Variable products redirect to the single product options.
- Improved Righteous Shop Grid accessibility by using standard anchor tags instead of inline JavaScript `onclick` handlers.
- Righteous Shop Grid: Prioritize 3D mockup gallery image, with standard fallback to featured image or Woo placeholder.
- Integrated GSAP site-wide for premium entry animations on Single Product, Cart, Checkout, and Shop Grid pages.

### Fixed
- Added `class_exists( 'WooCommerce' )` checks to gracefully handle missing/deactivated WooCommerce plugin without throwing fatal PHP errors.