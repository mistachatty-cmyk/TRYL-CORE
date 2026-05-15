<?php
/**
 * Plugin Name: LokServices Bridge
 * Description: Secure remote deployment for TRYL ecosystem. Auto-creates directories, validates extensions.
 * Version: 1.1
 * Author: EHDesigns | Powered by LokServices
 *
 * Drop this file into wp-content/mu-plugins/ to make it auto-loaded and indestructible.
 * Once active, the duplicate code in tryl-ecommerce-core.php (Section 11) will skip itself.
 */

defined( 'ABSPATH' ) || exit;

// If the MU-plugin version is already loaded, skip entirely to avoid redeclaration
if ( defined( 'LOKSERVICES_BRIDGE_ACTIVE' ) ) {
    return;
}

define( 'LOKSERVICES_BRIDGE_ACTIVE', true );

// ─── Allowed file extensions ─────────────────────────────────────────────────
define( 'LOK_ALLOWED_EXTENSIONS', [ 'php', 'css', 'js', 'json', 'txt', 'html', 'svg', 'xml', 'md', 'htaccess' ] );

// ─── Admin Page ──────────────────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_options_page(
        'LokServices Connection',
        'LokServices Bridge',
        'manage_options',
        'lokservices-bridge',
        'lok_bridge_options_page'
    );
} );

if ( ! function_exists( 'lok_bridge_options_page' ) ) {
function lok_bridge_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    if ( isset( $_POST['lok_api_key'] ) && check_admin_referer( 'lok_save_key' ) ) {
        update_option( 'lokservices_api_key', sanitize_text_field( $_POST['lok_api_key'] ) );
        echo '<div class="notice notice-success is-dismissible"><p>Connection Key saved securely.</p></div>';
    }

    $key = get_option( 'lokservices_api_key', wp_generate_password( 32, false ) );
    $api_url = rest_url( 'lokservices/v1/deploy' );
    ?>
    <div class="wrap" style="font-family: 'Inter', sans-serif; max-width: 850px;">
        <style>
            .lok-admin-header { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 2.5rem; text-transform: uppercase; margin-bottom: 8px; color: #0d1b0f; }
            .lok-card { background: #fff; border: 1px solid #d4e0d4; padding: 24px 32px; margin-bottom: 24px; border-radius: 4px; box-shadow: 0 8px 24px rgba(0,0,0,0.03); }
            .lok-card h2 { margin-top: 0; border-bottom: 1px solid #e8f0e8; padding-bottom: 12px; font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; }
            .lok-input { width: 100%; max-width: 400px; border: 1px solid #d4e0d4; padding: 10px 12px; font-family: monospace; font-size: 1rem; }
            .lok-btn { background: #0d1b0f !important; color: #fff !important; border: none !important; padding: 10px 24px !important; font-size: 0.95rem !important; font-weight: 600 !important; cursor: pointer !important; }
            .lok-status-ok { color: #007017; font-weight: 700; }
            .lok-status-err { color: #d63638; font-weight: 700; }
        </style>
        <h1 class="lok-admin-header">LokServices Bridge</h1>
        <div class="lok-card">
            <h2>Connection Credentials</h2>
            <form method="POST">
                <?php wp_nonce_field( 'lok_save_key' ); ?>
                <p><strong>Your Secret API Key:</strong></p>
                <input type="text" name="lok_api_key" class="lok-input" value="<?php echo esc_attr( $key ); ?>">
                <p><button type="submit" class="lok-btn">Save Key</button></p>
            </form>
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8f0e8;">
                <p><strong>API Endpoint URL:</strong></p>
                <code style="background: #f5f8f5; padding: 6px 10px; border: 1px solid #d4e0d4; display: block;"><?php echo esc_html( $api_url ); ?></code>
            </div>
        </div>
        <div class="lok-card">
            <h2>Deployed Files</h2>
            <?php lok_bridge_render_file_list(); ?>
        </div>
    </div>
    <?php
}
}

if ( ! function_exists( 'lok_bridge_render_file_list' ) ) {
function lok_bridge_render_file_list() {
    $manifest = get_option( 'lok_deploy_manifest', [] );
    if ( empty( $manifest ) ) {
        echo '<p>No files deployed yet via this bridge.</p>';
        return;
    }
    echo '<table class="widefat fixed" style="font-size: 0.85rem;">';
    echo '<thead><tr><th>Path</th><th>Size</th><th>Deployed</th></tr></thead><tbody>';
    foreach ( $manifest as $path => $info ) {
        $full = WP_CONTENT_DIR . '/' . ltrim( $path, '/' );
        $exists = file_exists( $full ) ? '<span class="lok-status-ok">OK</span>' : '<span class="lok-status-err">Missing</span>';
        $size = $exists ? size_format( filesize( $full ) ) : '-';
        echo '<tr><td>' . esc_html( $path ) . '</td><td>' . esc_html( $size ) . '</td><td>' . esc_html( $info['time'] ) . ' ' . $exists . '</td></tr>';
    }
    echo '</tbody></table>';
}
}

// ─── REST Routes ─────────────────────────────────────────────────────────────
add_action( 'rest_api_init', function () {
    register_rest_route( 'lokservices/v1', '/deploy', [
        [
            'methods'             => 'POST',
            'callback'            => 'lok_bridge_handle_deployment',
            'permission_callback' => 'lok_bridge_check_auth',
        ],
        [
            'methods'             => 'GET',
            'callback'            => 'lok_bridge_handle_status',
            'permission_callback' => 'lok_bridge_check_auth',
        ],
    ] );
} );

if ( ! function_exists( 'lok_bridge_check_auth' ) ) {
function lok_bridge_check_auth( WP_REST_Request $request ) {
    $provided_key = $request->get_header( 'X-Lok-Key' );
    $stored_key   = get_option( 'lokservices_api_key' );
    if ( empty( $stored_key ) || $provided_key !== $stored_key ) {
        return new WP_Error( 'forbidden', 'Invalid or missing LokServices API Key.', [ 'status' => 403 ] );
    }
    return true;
}
}

if ( ! function_exists( 'lok_bridge_handle_status' ) ) {
function lok_bridge_handle_status() {
    $manifest = get_option( 'lok_deploy_manifest', [] );
    $files = [];
    foreach ( $manifest as $path => $info ) {
        $full = WP_CONTENT_DIR . '/' . ltrim( $path, '/' );
        $files[] = [
            'path'     => $path,
            'exists'   => file_exists( $full ),
            'size'     => file_exists( $full ) ? filesize( $full ) : 0,
            'deployed' => $info['time'],
        ];
    }
    return new WP_REST_Response( [
        'success' => true,
        'files'   => $files,
        'count'   => count( $files ),
    ], 200 );
}
}

if ( ! function_exists( 'lok_bridge_handle_deployment' ) ) {
function lok_bridge_handle_deployment( WP_REST_Request $request ) {
    $file_path = $request->get_param( 'file_path' );
    $content   = $request->get_param( 'content' );

    if ( empty( $file_path ) || strpos( $file_path, '..' ) !== false ) {
        return new WP_Error( 'invalid', 'Missing or invalid file_path.', [ 'status' => 400 ] );
    }

    // Extension validation
    $ext = pathinfo( $file_path, PATHINFO_EXTENSION );
    if ( ! in_array( strtolower( $ext ), LOK_ALLOWED_EXTENSIONS, true ) ) {
        return new WP_Error( 'forbidden', 'File extension not allowed: .' . $ext, [ 'status' => 403 ] );
    }

    $target_file = WP_CONTENT_DIR . '/' . ltrim( $file_path, '/' );
    $target_dir  = dirname( $target_file );

    if ( ! is_dir( $target_dir ) ) {
        wp_mkdir_p( $target_dir );
    }

    if ( empty( $content ) ) {
        return new WP_Error( 'invalid', 'Content is empty.', [ 'status' => 400 ] );
    }

    $result = file_put_contents( $target_file, $content );
    if ( $result === false ) {
        return new WP_Error( 'error', 'Write failed.', [ 'status' => 500 ] );
    }

    // Update deploy manifest
    $manifest = get_option( 'lok_deploy_manifest', [] );
    $manifest[ $file_path ] = [ 'time' => current_time( 'mysql' ) ];
    update_option( 'lok_deploy_manifest', $manifest );

    return new WP_REST_Response( [ 'success' => true, 'path' => $file_path, 'bytes' => $result ], 200 );
}
}
