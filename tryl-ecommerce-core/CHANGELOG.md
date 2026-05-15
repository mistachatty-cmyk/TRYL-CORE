C:\Users\glory\.gemini\antigravity\scratch\tryl-ecommerce-core\tryl-ecommerce-core.php# Changelog

All notable changes to the TRYL E-Commerce Core plugin and associated templates will be documented in this file.

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