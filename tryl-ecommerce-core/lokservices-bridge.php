<?php
/**
 * Plugin Name: LokServices Deployment Bridge
 * Description: Secure REST API endpoint for remote file deployment, backups, and rollback. Completely decoupled for bulletproof reliability.
 * Version: 2.1
 * Author: LokServices
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'LOKSERVICES_BRIDGE_ACTIVE' ) ) {
    define( 'LOKSERVICES_BRIDGE_ACTIVE', true );

    function lok_bridge_menu() {
        add_options_page( 'LokServices Connection', 'LokServices Bridge', 'manage_options', 'lokservices-bridge', 'lok_bridge_options_page' );
    }
    add_action( 'admin_menu', 'lok_bridge_menu' );

    function lok_bridge_options_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        if ( isset( $_POST['lok_api_key'] ) && check_admin_referer('lok_save_key') ) {
            update_option( 'lokservices_api_key', sanitize_text_field( $_POST['lok_api_key'] ) );
            update_option( 'lokservices_ip_whitelist', sanitize_text_field( $_POST['lok_ip_whitelist'] ?? '' ) );
            update_option( 'lokservices_enable_alerts', isset($_POST['lok_enable_alerts']) ? '1' : '0' );
            echo '<div class="notice notice-success is-dismissible"><p>Connection Key and settings saved securely.</p></div>';
        }
        
        if ( isset( $_POST['lok_clear_log'] ) && check_admin_referer('lok_clear_log') ) {
            delete_option( 'lokservices_audit_log' );
            echo '<div class="notice notice-success is-dismissible"><p>Audit log cleared.</p></div>';
        }

        if ( isset( $_POST['lok_restore_file'] ) && check_admin_referer('lok_restore_file') ) {
            $file_to_restore = sanitize_text_field( wp_unslash( $_POST['lok_restore_file'] ) );
            $target_file = wp_normalize_path( WP_CONTENT_DIR . '/' . ltrim( $file_to_restore, '/' ) );
            if ( file_exists( $target_file . '.bak' ) ) {
                copy( $target_file . '.bak', $target_file );
                echo '<div class="notice notice-success is-dismissible"><p>Successfully restored <strong>' . esc_html($file_to_restore) . '</strong> from backup.</p></div>';
                lok_bridge_log_event( $file_to_restore, 'Restored via UI', 'Admin' );
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Backup file not found.</p></div>';
            }
        }
        
        $key = get_option( 'lokservices_api_key', wp_generate_password( 32, false ) );
        $whitelist = get_option( 'lokservices_ip_whitelist', '' );
        $api_url = rest_url( 'lokservices/v1/deploy' );
        $audit_log = get_option( 'lokservices_audit_log', [] );
        ?>
        <div class="wrap" style="font-family: 'Inter', sans-serif; max-width: 850px;">
            <style>
                .lok-admin-header { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 2.5rem; text-transform: uppercase; margin-bottom: 8px; color: #0d1b0f; }
                .lok-card { background: #fff; border: 1px solid #d4e0d4; padding: 24px 32px; margin-bottom: 24px; border-radius: 4px; box-shadow: 0 8px 24px rgba(0,0,0,0.03); }
                .lok-card h2 { margin-top: 0; border-bottom: 1px solid #e8f0e8; padding-bottom: 12px; font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; }
                .lok-input { width: 100%; max-width: 400px; border: 1px solid #d4e0d4; padding: 10px 12px; font-family: monospace; font-size: 1rem; }
                .lok-btn { background: #0d1b0f !important; color: #fff !important; border: none !important; padding: 10px 24px !important; font-size: 0.95rem !important; font-weight: 600 !important; cursor: pointer !important; }
                .lok-btn-danger { background: transparent !important; color: #d63638 !important; border: 1px solid #d63638 !important; padding: 6px 16px !important; font-size: 0.85rem !important; font-weight: 600 !important; cursor: pointer !important; border-radius: 3px; }
                .lok-btn-danger:hover { background: #d63638 !important; color: #fff !important; }
                .lok-table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 0.9rem; }
                .lok-table th { text-align: left; padding: 12px; background: #f5f8f5; color: #6b7c6b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; border-bottom: 2px solid #d4e0d4; }
                .lok-table td { padding: 12px; border-bottom: 1px solid #e8f0e8; color: #1a2e1a; vertical-align: top; }
                .lok-table tr:last-child td { border-bottom: none; }
                .lok-status-success { color: #007017; font-weight: 600; }
                .lok-status-error { color: #d63638; font-weight: 600; }
                .lok-code-path { font-family: monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 0.85rem; word-break: break-all; }
            </style>
            <h1 class="lok-admin-header">LokServices Bridge</h1>
            
            <div class="lok-card">
                <h2>1. Connection Credentials</h2>
                <form method="POST">
                    <?php wp_nonce_field('lok_save_key'); ?>
                    <p><strong>Your Secret API Key:</strong></p>
                    <input type="text" name="lok_api_key" class="lok-input" value="<?php echo esc_attr( $key ); ?>">
                    <p style="margin-top: 16px;"><strong>IP Whitelist (Optional, highly recommended):</strong></p>
                    <input type="text" name="lok_ip_whitelist" class="lok-input" value="<?php echo esc_attr( $whitelist ); ?>" placeholder="e.g. 192.168.1.5, 203.0.113.10">
                    <p class="description" style="margin-bottom: 16px;">Comma-separated list of IP addresses allowed to deploy. Leave blank to allow any IP.</p>
                    <p style="margin-bottom: 16px;">
                        <label>
                            <input type="checkbox" name="lok_enable_alerts" value="1" <?php checked(get_option('lokservices_enable_alerts'), '1'); ?>>
                            <strong>Enable Email Alerts:</strong> Send me an email if a deployment fails or is blocked.
                        </label>
                    </p>
                    <p><button type="submit" class="lok-btn">Save Key</button></p>
                </form>
                <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8f0e8;">
                    <p><strong>Your API Endpoint URL:</strong></p>
                    <code style="background: #f5f8f5; padding: 6px 10px; border: 1px solid #d4e0d4; display: block;"><?php echo esc_html($api_url); ?></code>
                </div>
            </div>

            <div class="lok-card">
                <h2>2. Environment Status</h2>
                <table class="lok-table">
                    <tr>
                        <td>PHP Version</td>
                        <td><strong><?php echo esc_html(phpversion()); ?></strong></td>
                    </tr>
                    <tr>
                        <td>WP_CONTENT_DIR Writable</td>
                        <td class="<?php echo is_writable(WP_CONTENT_DIR) ? 'lok-status-success' : 'lok-status-error'; ?>"><?php echo is_writable(WP_CONTENT_DIR) ? 'Yes &check;' : 'No (Deployment will fail)'; ?></td>
                    </tr>
                    <tr>
                        <td>Plugin Execution Priority</td>
                        <td class="lok-status-success">Decoupled & Insulated</td>
                    </tr>
                </table>
            </div>

            <div class="lok-card">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e8f0e8; padding-bottom: 12px; margin-bottom: 20px;">
                    <h2 style="margin: 0; border: none; padding: 0;">3. Deployment Audit Log</h2>
                    <form method="POST" style="margin: 0;">
                        <?php wp_nonce_field('lok_clear_log'); ?>
                        <input type="hidden" name="lok_clear_log" value="1">
                        <button type="submit" class="lok-btn-danger" onclick="return confirm('Are you sure you want to clear the deployment history?');">Clear Log</button>
                    </form>
                </div>
                
                <?php if ( empty( $audit_log ) ) : ?>
                    <p style="color: #6b7c6b; font-style: italic;">No deployments recorded yet.</p>
                <?php else : ?>
                    <div style="overflow-x: auto;">
                        <table class="lok-table">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                    <th>File Deployed</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $audit_log as $entry ) : ?>
                                    <tr>
                                        <td style="white-space: nowrap;"><?php echo esc_html( date( 'Y-m-d H:i:s', strtotime( $entry['time'] ) ) ); ?></td>
                                        <td><code><?php echo esc_html( $entry['ip'] ); ?></code></td>
                                        <td class="<?php echo (strpos( $entry['status'], 'Success' ) !== false || strpos( $entry['status'], 'Restored' ) !== false) ? 'lok-status-success' : 'lok-status-error'; ?>"><?php echo esc_html( $entry['status'] ); ?></td>
                                        <td><span class="lok-code-path"><?php echo esc_html( $entry['file'] ); ?></span></td>
                                        <td>
                                            <?php 
                                            if ( $entry['file'] !== 'N/A' && $entry['file'] !== 'Unknown' ) {
                                                $target_file = wp_normalize_path( WP_CONTENT_DIR . '/' . ltrim( $entry['file'], '/' ) );
                                                if ( file_exists( $target_file . '.bak' ) ) : 
                                            ?>
                                            <form method="POST" style="margin:0;">
                                                <?php wp_nonce_field('lok_restore_file'); ?>
                                                <input type="hidden" name="lok_restore_file" value="<?php echo esc_attr($entry['file']); ?>">
                                                <button type="submit" class="button button-small" onclick="return confirm('Restore this file to its previous state?');">Restore .bak</button>
                                            </form>
                                            <?php 
                                                endif;
                                            } 
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    add_action( 'rest_api_init', function () {
        register_rest_route( 'lokservices/v1', '/deploy', array(
            'methods'             => 'POST',
            'callback'            => 'lok_bridge_handle_deployment',
            'permission_callback' => 'lok_bridge_check_auth'
        ) );
    } );

    function lok_bridge_check_auth( WP_REST_Request $request ) {
        $client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR'];

        $whitelist = get_option( 'lokservices_ip_whitelist' );
        if ( ! empty( $whitelist ) ) {
            $allowed_ips = array_map( 'trim', explode( ',', $whitelist ) );
            if ( ! in_array( trim($client_ip), $allowed_ips ) ) {
                lok_bridge_log_event( 'N/A', 'Blocked (IP Not Whitelisted)', $client_ip );
                return new WP_Error( 'forbidden', 'Deployment IP not whitelisted.', array( 'status' => 403 ) );
            }
        }

        $provided_key = $request->get_header( 'X-Lok-Key' );
        $stored_key   = get_option( 'lokservices_api_key' );

        if ( empty( $stored_key ) || $provided_key !== $stored_key ) {
            lok_bridge_log_event( 'N/A', 'Failed (Invalid API Key)', $client_ip );
            return new WP_Error( 'forbidden', 'Invalid or missing LokServices API Key.', array( 'status' => 403 ) );
        }

        return true;
    }

    function lok_bridge_log_event( $file_path, $status, $ip ) {
        $log = get_option( 'lokservices_audit_log', [] );
        if ( ! is_array( $log ) ) $log = [];
        array_unshift( $log, [
            'time'   => current_time( 'mysql' ),
            'file'   => sanitize_text_field( $file_path ),
            'status' => sanitize_text_field( $status ),
            'ip'     => sanitize_text_field( $ip )
        ] );
        $log = array_slice( $log, 0, 100 );
        update_option( 'lokservices_audit_log', $log );
        
        if ( get_option('lokservices_enable_alerts') === '1' && (strpos($status, 'Failed') !== false || strpos($status, 'Blocked') !== false) ) {
            $site_name = get_bloginfo('name');
            wp_mail( get_option('admin_email'), "[$site_name] LokServices Security Alert", "A deployment attempt returned the following status:\n\nStatus: $status\nFile: $file_path\nIP: $ip\nTime: " . current_time('mysql') );
        }
    }

    function lok_bridge_handle_deployment( WP_REST_Request $request ) {
        $file_path = $request->get_param( 'file_path' );
        $content   = $request->get_param( 'content' );
        $checksum  = $request->get_param( 'checksum' );
        $client_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0] : $_SERVER['REMOTE_ADDR'];

        if ( empty( $file_path ) || empty( $content ) || strpos( $file_path, '..' ) !== false ) {
            lok_bridge_log_event( $file_path ?? 'Unknown', 'Failed (Invalid Request)', $client_ip );
            return new WP_Error( 'invalid', 'Invalid request.', array( 'status' => 400 ) );
        }
        
        $target_file = wp_normalize_path( WP_CONTENT_DIR . '/' . ltrim( $file_path, '/' ) );
        if ( strpos( $target_file, wp_normalize_path( WP_CONTENT_DIR ) ) !== 0 ) {
            lok_bridge_log_event( $file_path, 'Blocked (Path Traversal)', $client_ip );
            return new WP_Error( 'forbidden', 'Path traversal attempt blocked.', array( 'status' => 403 ) );
        }

        $backup_made = false;
        if ( file_exists( $target_file ) ) {
            copy( $target_file, $target_file . '.bak' );
            $backup_made = true;
        }

        if ( ! is_dir( dirname( $target_file ) ) ) {
            wp_mkdir_p( dirname( $target_file ) );
        }
        
        $result = file_put_contents( $target_file, $content );
        
        if ( $result !== false && ! empty( $checksum ) && md5_file( $target_file ) !== $checksum ) {
            if ( $backup_made ) copy( $target_file . '.bak', $target_file );
            lok_bridge_log_event( $file_path, 'Rollback (Checksum Mismatch)', $client_ip );
            return new WP_Error( 'checksum_mismatch', 'Payload corrupted during transfer. Auto-rolled back.', [ 'status' => 500 ] );
        }

        if ( $result !== false ) {
            lok_bridge_log_event( $file_path, 'Success', $client_ip );
            return new WP_REST_Response( [ 'success' => true, 'backed_up' => $backup_made ], 200 );
        } else {
            lok_bridge_log_event( $file_path, 'Failed (Write Error)', $client_ip );
            return new WP_Error( 'error', 'Write failed', [ 'status' => 500 ] );
        }
    }
}
