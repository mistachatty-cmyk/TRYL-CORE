<?php
/**
 * LokServices Bridge — Auto-recovery: creates directories before deploy writes,
 * AND auto-fixes function collision between mu-plugin and main plugin.
 *
 * Drop into wp-content/mu-plugins/ to enable.
 */

// ── Part 1: Create missing directories before the deploy handler runs ──
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
    if ( $request->get_route() !== '/lokservices/v1/deploy' || $request->get_method() !== 'POST' ) {
        return $result;
    }
    $file_path = $request->get_param( 'file_path' );
    if ( $file_path && strpos( $file_path, '..' ) === false ) {
        $dir = dirname( WP_CONTENT_DIR . '/' . ltrim( $file_path, '/' ) );
        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
    }
    return $result;
}, 5, 3 );

// ── Part 2: Self-heal function collision on fatal error ──
register_shutdown_function( function () {
    $err = error_get_last();
    if ( ! $err || $err['type'] !== E_ERROR ) return;
    if ( strpos( $err['message'], 'Cannot redeclare' ) === false ) return;
    if ( strpos( $err['file'], 'tryl-ecommerce-core.php' ) === false ) return;

    $content = file_get_contents( $err['file'] );
    if ( strpos( $content, 'LOKSERVICES_BRIDGE_ACTIVE' ) !== false ) return;

    $content = preg_replace(
        '~// \X{3} 13\. LOKSERVICES BRIDGE MODULE\X{3}~u',
        "// \xE2\x94\x80\xE2\x94\x80\xE2\x94\x80 13. LOKSERVICES BRIDGE MODULE (fallback)\nif ( ! defined( 'LOKSERVICES_BRIDGE_ACTIVE' ) ) {",
        $content
    );
    $content = rtrim( $content ) . "\n}";
    file_put_contents( $err['file'], $content );
} );
