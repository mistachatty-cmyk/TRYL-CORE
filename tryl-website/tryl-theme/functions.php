<?php
/**
 * TRYL Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tryl_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ) );
}
add_action( 'after_setup_theme', 'tryl_theme_setup' );

function tryl_enqueue_assets() {
    wp_enqueue_style( 'tryl-style', get_stylesheet_uri(), array(), '1.0.0' );
    wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), null, true );
    wp_enqueue_script( 'scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', array('gsap'), null, true );
    wp_enqueue_script( 'tryl-script', get_template_directory_uri() . '/script.js', array('gsap', 'scrolltrigger'), '1.0.0', true );
}
add_action( 'wp_enqueue_scripts', 'tryl_enqueue_assets' );

if ( ! function_exists( 'tryl_register_core_product_categories' ) ) {
function tryl_register_core_product_categories() {
    $categories = array( 'Men', 'Women', 'Kids', 'Hats & Accessories' );
    foreach ( $categories as $category ) {
        if ( ! term_exists( $category, 'product_cat' ) ) {
            wp_insert_term( $category, 'product_cat', array(
                'description' => 'The Righteous Yield Life - ' . $category,
                'slug'        => sanitize_title( $category )
            ));
        }
    }
}
} // endif function_exists( 'tryl_register_core_product_categories' )
if ( ! has_action( 'init', 'tryl_register_core_product_categories' ) ) {
    add_action( 'init', 'tryl_register_core_product_categories' );
}

if ( ! function_exists( 'tryl_handle_prayer_request_submission' ) ) {
function tryl_handle_prayer_request_submission() {
    if ( isset($_POST['tryl_submit_prayer_request']) ) {
        if ( ! isset( $_POST['tryl_prayer_nonce'] ) || ! wp_verify_nonce( $_POST['tryl_prayer_nonce'], 'tryl_prayer_action' ) ) {
            wp_die( 'Security check failed.' );
        }
        $name   = sanitize_text_field( $_POST['prayer_name'] );
        $prayer = sanitize_textarea_field( $_POST['prayer_message'] );
        $email  = isset($_POST['prayer_email']) ? sanitize_email( $_POST['prayer_email'] ) : '';
        if ( empty( $name ) || empty( $prayer ) ) {
            wp_redirect( add_query_arg( 'prayer_status', 'empty', wp_get_referer() ) );
            exit;
        }
        $to = get_option('tryl_prayer_email');
        if ( empty($to) ) $to = get_option('admin_email');
        $subject = 'New Prayer Request: ' . $name;
        $message = "Name: $name\n";
        if ( !empty($email) ) $message .= "Email: $email\n";
        $message .= "\nPrayer:\n$prayer\n\n--\nSubmitted via TRYL Website.";
        $headers = array('Content-Type: text/plain; charset=UTF-8');
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
                $auto_sub = "We received your prayer request";
                $auto_msg = "Hi $name,\n\nThank you for reaching out to us. We have received your prayer request and our team is standing in faith with you.\n\n\"For where two or three gather in my name, there am I with them.\" - Matthew 18:20\n\nBlessings,\nThe Righteous Yield Life Team";
                wp_mail( $email, $auto_sub, $auto_msg );
            }
        }
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
