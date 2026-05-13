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
 * 1. Register WooCommerce Categories for TRYL
 * Ensures Men, Women, Kids, and Hats/Accessories exist on setup.
 */
if ( ! function_exists( 'tryl_register_core_product_categories' ) ) {
function tryl_register_core_product_categories() {
    $categories = array( 'Men', 'Women', 'Kids', 'Hats & Accessories' );

    foreach ( $categories as $category ) {
        if ( ! term_exists( $category, 'product_cat' ) ) {
            wp_insert_term(
                $category,
                'product_cat',
                array(
                    'description' => 'The Righteous Yield Life - ' . $category,
                    'slug'        => sanitize_title( $category )
                )
            );
        }
    }
}
} // endif function_exists( 'tryl_register_core_product_categories' )
if ( ! has_action( 'init', 'tryl_register_core_product_categories' ) ) {
    add_action( 'init', 'tryl_register_core_product_categories' );
}

/**
 * 2. Handle Prayer Request Form Submission
 * Custom route and email handler for the Prayer Request functionality.
 */
if ( ! function_exists( 'tryl_handle_prayer_request_submission' ) ) {
function tryl_handle_prayer_request_submission() {
    // Check if form was submitted
    if ( isset($_POST['tryl_submit_prayer_request']) ) {
        
        // Verify Nonce for security
        if ( ! isset( $_POST['tryl_prayer_nonce'] ) || ! wp_verify_nonce( $_POST['tryl_prayer_nonce'], 'tryl_prayer_action' ) ) {
            wp_die( 'Security check failed. Please try again.' );
        }

        // Sanitize input
        $name   = sanitize_text_field( $_POST['prayer_name'] );
        $prayer = sanitize_textarea_field( $_POST['prayer_message'] );
        $email  = isset($_POST['prayer_email']) ? sanitize_email( $_POST['prayer_email'] ) : '';

        // Validate
        if ( empty( $name ) || empty( $prayer ) ) {
            wp_redirect( add_query_arg( 'prayer_status', 'empty', wp_get_referer() ) );
            exit;
        }

        // Prepare email
        $to      = get_option('tryl_prayer_email');
        if ( empty($to) ) {
            $to = get_option('admin_email');
        }
        $subject = 'New Prayer Request: ' . $name;
        $message = "Name: $name\n";
        if ( !empty($email) ) $message .= "Email: $email\n";
        $message .= "\nPrayer:\n$prayer\n\n--\nSubmitted via TRYL Website. You can reply directly from your WordPress 'Prayers' dashboard!";
        $headers = array('Content-Type: text/plain; charset=UTF-8');

        // Save Prayer to Database
        $post_id = wp_insert_post(array(
            'post_title'   => 'Prayer from ' . $name,
            'post_content' => $prayer,
            'post_status'  => 'publish',
            'post_type'    => 'prayer_request'
        ));

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_prayer_name', $name );
            update_post_meta( $post_id, '_prayer_status', 'pending' );
            if ( !empty($email) ) {
                update_post_meta( $post_id, '_prayer_email', $email );
                
                // Send Auto-Responder to User
                $auto_sub = "We received your prayer request";
                $auto_msg = "Hi $name,\n\nThank you for reaching out to us. We have received your prayer request and our team is standing in faith with you.\n\n\"For where two or three gather in my name, there am I with them.\" - Matthew 18:20\n\nBlessings,\nThe Righteous Yield Life Team";
                wp_mail( $email, $auto_sub, $auto_msg );
            }
        }

        // Send Admin Notification Email
        $sent = wp_mail( $to, $subject, $message, $headers );

        if ( $sent ) {
            wp_redirect( add_query_arg( 'prayer_status', 'success', wp_get_referer() ) );
        } else {
            wp_redirect( add_query_arg( 'prayer_status', 'error', wp_get_referer() ) );
        }
        exit;
    }
}
} // endif function_exists( 'tryl_handle_prayer_request_submission' )
add_action( 'admin_post_nopriv_submit_prayer_request', 'tryl_handle_prayer_request_submission' );
add_action( 'admin_post_submit_prayer_request', 'tryl_handle_prayer_request_submission' );

/**
 * 2.1 Register Prayer Request Dashboard System
 */
function tryl_register_prayer_cpt() {
    register_post_type('prayer_request', [
        'labels' => [
            'name' => 'Prayer Requests',
            'singular_name' => 'Prayer Request',
            'menu_name' => 'Prayers',
            'all_items' => 'All Prayers',
        ],
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-heart', // Intuitive heart icon
        'menu_position' => 25,
        'supports' => ['title'], // Hides the clunky standard editor
        'capabilities' => [ 'create_posts' => false ], // Prevent manual creation
        'map_meta_cap' => true,
    ]);
}
add_action('init', 'tryl_register_prayer_cpt');

/**
 * 2.2 Custom Prayer Details & Reply UI
 */
function tryl_prayer_meta_box() {
    add_meta_box('tryl_prayer_details', 'Prayer Details & Response', 'tryl_prayer_meta_box_html', 'prayer_request', 'normal', 'high');
}
add_action('add_meta_boxes', 'tryl_prayer_meta_box');

function tryl_prayer_meta_box_html($post) {
    $name   = get_post_meta($post->ID, '_prayer_name', true);
    $email  = get_post_meta($post->ID, '_prayer_email', true);
    $status = get_post_meta($post->ID, '_prayer_status', true);
    $reply  = get_post_meta($post->ID, '_prayer_reply', true);
    
    wp_nonce_field('tryl_save_prayer_reply', 'tryl_prayer_reply_nonce');

    echo '<div style="padding: 10px 0; font-size: 14px;">';
    echo '<p><strong>From:</strong> ' . esc_html($name) . ( $email ? ' (' . esc_html($email) . ')' : '' ) . '</p>';
    echo '<div style="background:#f5f8f5; padding:15px; border-left:4px solid #2d6a4f; margin:15px 0;"><strong>The Prayer:</strong><br/><br/>' . nl2br(esc_html($post->post_content)) . '</div>';

    if ( empty($email) ) {
        echo '<p style="color:#d63638;"><em>The user did not provide an email address, so you cannot reply directly from here.</em></p>';
    } elseif ( $status === 'replied' ) {
        echo '<p style="color: #007017;"><strong>&check; You replied to this request:</strong></p>';
        echo '<div style="background:#fff; border:1px solid #c3c4c7; padding:15px;"><em>' . nl2br(esc_html($reply)) . '</em></div>';
    } else {
        echo '<p><strong>Write a Response (This will be emailed directly to them):</strong></p>';
        echo '<textarea name="prayer_reply_message" rows="5" style="width:100%; border:1px solid #8c8f94; border-radius:4px; padding:10px;"></textarea>';
        echo '<p><button type="submit" class="button button-primary button-large" style="background:#0d1b0f; border-color:#0d1b0f;">Send Reply & Save</button></p>';
    }
    echo '</div>';
}

function tryl_save_prayer_reply($post_id) {
    if (!isset($_POST['tryl_prayer_reply_nonce']) || !wp_verify_nonce($_POST['tryl_prayer_reply_nonce'], 'tryl_save_prayer_reply')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (!empty($_POST['prayer_reply_message'])) {
        $reply = sanitize_textarea_field($_POST['prayer_reply_message']);
        $email = get_post_meta($post_id, '_prayer_email', true);
        $name  = get_post_meta($post_id, '_prayer_name', true);
        
        if ($email) {
            $subject = "Re: Your Prayer Request";
            $message = "Dear " . $name . ",\n\nWe received your prayer request and want you to know we are praying for you.\n\n" . $reply . "\n\nBlessings,\nThe Righteous Yield Life Team";
            wp_mail($email, $subject, $message);
            
            update_post_meta($post_id, '_prayer_reply', $reply);
            update_post_meta($post_id, '_prayer_status', 'replied');
        }
    }
}
add_action('save_post_prayer_request', 'tryl_save_prayer_reply');

/**
 * 2.3 Custom Columns for the Prayers List
 */
add_filter('manage_prayer_request_posts_columns', function($columns) {
    return ['cb' => $columns['cb'], 'title' => 'Requester', 'prayer_status' => 'Status', 'date' => 'Date Received'];
});
add_action('manage_prayer_request_posts_custom_column', function($column, $post_id) {
    if ($column === 'prayer_status') {
        $status = get_post_meta($post_id, '_prayer_status', true);
        echo $status === 'replied' ? '<span style="color:#007017; font-weight:bold;">&check; Replied</span>' : '<span style="color:#d63638; font-weight:bold;">Pending</span>';
    }
}, 10, 2);

/**
 * 3. Printful & Payment Gateway Hooks (Scaffold)
 * Hooks to configure external integrations once authenticated.
 */

// Printful webhook integration placeholder
if ( ! defined( 'TRYL_PRINTFUL_ROUTE_REGISTERED' ) ) {
    define( 'TRYL_PRINTFUL_ROUTE_REGISTERED', true );
    add_action( 'rest_api_init', function () {
        register_rest_route( 'tryl/v1', '/printful-sync', array(
            'methods'  => 'POST',
            'callback' => 'tryl_handle_printful_sync',
            'permission_callback' => 'tryl_verify_printful_webhook'
        ) );
    } );
}

if ( ! function_exists( 'tryl_verify_printful_webhook' ) ) {
function tryl_verify_printful_webhook( WP_REST_Request $request ) {
    $token = $request->get_param('token');
    $stored_token = get_option('tryl_printful_token');
    
    // Strictly enforce the token if it has been configured in TRYL Settings
    if ( ! empty($stored_token) ) {
        return hash_equals($stored_token, (string) $token);
    }
    return true;
}
} // endif function_exists( 'tryl_verify_printful_webhook' )

if ( ! function_exists( 'tryl_handle_printful_sync' ) ) {
function tryl_handle_printful_sync( WP_REST_Request $request ) {
    $payload = $request->get_json_params();

    if ( empty( $payload['type'] ) ) {
        return new WP_Error( 'invalid_payload', 'Missing webhook event type', array( 'status' => 400 ) );
    }

    $event_type = $payload['type'];
    $data       = isset($payload['data']) ? $payload['data'] : [];

    // Printful maps their 'external_id' to the WooCommerce Order ID
    $order_id = isset($data['order']['external_id']) ? (int) $data['order']['external_id'] : 0;

    if ( ! $order_id ) return new WP_REST_Response( array( 'status' => 'ignored', 'message' => 'No WooCommerce external_id mapping found.' ), 200 );
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) return new WP_REST_Response( array( 'status' => 'ignored', 'message' => 'WooCommerce order not found.' ), 200 );

    switch ( $event_type ) {
        case 'package_shipped':
            $tracking = isset($data['shipment']['tracking_number']) ? sanitize_text_field($data['shipment']['tracking_number']) : '';
            $carrier  = isset($data['shipment']['carrier']) ? sanitize_text_field($data['shipment']['carrier']) : '';
            if ( $tracking ) {
                $order->update_meta_data( '_printful_tracking_number', $tracking );
                $order->update_meta_data( '_printful_carrier', $carrier );
                $order->add_order_note( sprintf( 'Printful Shipped! Carrier: %s, Tracking: %s', $carrier, $tracking ) );
            }
            $order->update_status( 'completed', 'Order completed automatically via Printful webhook.' );
            break;
        case 'package_returned':
            $order->update_status( 'on-hold', 'Printful reported this package was returned to them. Please review.' );
            break;
        case 'order_failed':
        case 'order_canceled':
            $reason = isset($data['reason']) ? sanitize_text_field($data['reason']) : 'No reason provided';
            $order->update_status( 'failed', 'Printful reported order failure/cancellation. Reason: ' . $reason );
            break;
    }

    return new WP_REST_Response( array( 'status' => 'success', 'message' => "Successfully processed $event_type for Order #$order_id" ), 200 );
}
} // endif function_exists( 'tryl_handle_printful_sync' )

// Enforce required payment gateways in WooCommerce (Stripe, PayPal)
function tryl_ensure_payment_gateways( $gateways ) {
    // In production, this can enforce specific gateways depending on env
    return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'tryl_ensure_payment_gateways' );
