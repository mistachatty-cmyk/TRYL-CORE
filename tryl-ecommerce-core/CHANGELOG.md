C:\Users\glory\.gemini\antigravity\scratch\tryl-ecommerce-core\tryl-ecommerce-core.php# Changelog

All notable changes to the TRYL E-Commerce Core plugin and associated templates will be documented in this file.

## [3.8.0]

### Added
- LokConnect Extension API: Registered headless REST endpoint (`/tryl/v1/ecosystem-stats`) guarded by a custom cryptographic header token (`X-TRYL-Extension-Key`). Prepares the store to integrate natively with a future LokServices Chrome Extension for live remote analytics monitoring.

'## [3.7.0]

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