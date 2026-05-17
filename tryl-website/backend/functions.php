<?php
/**
 * TRYL Theme Functions
 * Core configuration, WooCommerce tweaks, and form handlers.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enforces required payment gateways in WooCommerce.
 *
 * @param array $gateways Array of available payment gateways.
 * @return array Filtered array of payment gateways.
 */
if ( ! function_exists( 'tryl_ensure_payment_gateways' ) ) {
function tryl_ensure_payment_gateways( $gateways ) {
    // In production, this can enforce specific gateways depending on env
    return $gateways;
}
}
add_filter( 'woocommerce_available_payment_gateways', 'tryl_ensure_payment_gateways' );
