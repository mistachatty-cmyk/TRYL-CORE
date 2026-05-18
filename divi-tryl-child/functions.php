<?php
// ─── TRYL NUCLEAR TEMPLATE SAFETY NET ─────────────────────────────────────────
// Theme-level override: SeedProd and other page builders cannot intercept this
// because it runs at priority 9999999 from the active theme itself.
add_filter('template_include', function($template) {
    // Force TRYL product template on all single product pages
    if ( is_singular('product') || ( function_exists('is_product') && is_product() ) ) {
        $t = WP_PLUGIN_DIR . '/tryl-ecommerce-core/templates/single-product.php';
        if ( file_exists($t) ) return $t;
    }
    // Force TRYL checkout template
    if ( function_exists('is_checkout') && is_checkout() && ! is_wc_endpoint_url() ) {
        $t = WP_PLUGIN_DIR . '/tryl-ecommerce-core/templates/checkout/form-checkout.php';
        if ( get_option('tryl_nike_checkout_active', '1') === '1' && file_exists($t) ) return $t;
    }
    return $template;
}, 9999999);

function divi_tryl_child_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'divi_tryl_child_enqueue_styles' );
