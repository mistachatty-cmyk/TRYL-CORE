<?php
/**
 * TRYL Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook.
 *
 * @return void
 */
if ( ! function_exists( 'tryl_theme_setup' ) ) {
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
}
add_action( 'after_setup_theme', 'tryl_theme_setup' );

/**
 * Enqueues the theme's stylesheets and scripts.
 *
 * Includes GSAP and ScrollTrigger for custom animations.
 *
 * @return void
 */
if ( ! function_exists( 'tryl_enqueue_assets' ) ) {
function tryl_enqueue_assets() {
    wp_enqueue_style( 'tryl-style', get_stylesheet_uri(), array(), '1.0.0' );
    wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), null, true );
    wp_enqueue_script( 'scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', array('gsap'), null, true );
    wp_enqueue_script( 'tryl-script', get_theme_file_uri('script.js'), array('gsap', 'scrolltrigger'), '1.0.0', true );
}
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
remove_action( 'admin_post_nopriv_submit_prayer_request', 'tryl_handle_prayer_request_submission' );
remove_action( 'admin_post_submit_prayer_request', 'tryl_handle_prayer_request_submission' );
add_action( 'admin_post_nopriv_submit_prayer_request', 'tryl_handle_prayer_request_submission' );
add_action( 'admin_post_submit_prayer_request', 'tryl_handle_prayer_request_submission' );

if ( ! defined( 'TRYL_PRINTFUL_ROUTE_REGISTERED' ) ) {
    define( 'TRYL_PRINTFUL_ROUTE_REGISTERED', true );
    function tryl_theme_register_printful_route() {
        register_rest_route( 'tryl/v1', '/printful-sync', array(
            'methods'  => 'POST',
            'callback' => 'tryl_handle_printful_sync',
            'permission_callback' => 'tryl_verify_printful_webhook'
        ) );
    }
    add_action( 'rest_api_init', 'tryl_theme_register_printful_route' );
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

/**
 * Hero Section HTML Shortcode
 * 
 * Renders the GSAP-animated hero portrait and typography section.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output of the hero section.
 */
if ( ! function_exists( 'tryl_hero_section_shortcode' ) ) {
function tryl_hero_section_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'image'      => '',
        'text_left'  => '',
        'text_right' => '',
        'btn_text'   => '',
        'btn_url'    => ''
    ), $atts, 'tryl_hero' );

    // Check for inline shortcode attribute, then TRYL settings, then fallback
    $image_url = ! empty( $atts['image'] ) ? $atts['image'] : get_option( 'tryl_hero_image' );
    if ( empty( $image_url ) ) {
        // High quality placeholder if no image is configured
        $image_url = 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=800&q=80';
    }
    
    $text_left  = ! empty( $atts['text_left'] ) ? $atts['text_left'] : get_option( 'tryl_hero_text_left', 'The Righteous' );
    $text_right = ! empty( $atts['text_right'] ) ? $atts['text_right'] : get_option( 'tryl_hero_text_right', 'Yield Life' );
    $btn_text   = ! empty( $atts['btn_text'] ) ? $atts['btn_text'] : get_option( 'tryl_hero_btn_text', 'Shop Collection' );
    $btn_url    = ! empty( $atts['btn_url'] ) ? $atts['btn_url'] : get_option( 'tryl_hero_btn_url', home_url('/shop/') );
    
    ob_start();
    ?>
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-text-left"><span class="text-righteous"><?php echo esc_html( $text_left ); ?></span></div>
            <div class="hero-image-wrapper">
                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $text_left . ' ' . $text_right ); ?>" class="hero-portrait">
                <?php if ( ! empty( $btn_text ) ) : ?>
                <div class="hero-cta-wrapper" style="text-align:center; margin-top: 24px; position: relative; z-index: 10;">
                    <a href="<?php echo esc_url( $btn_url ); ?>" style="display:inline-block; padding: 14px 28px; background: #0d1b0f; color: #fff; font-family: 'Inter', sans-serif; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; font-size: 0.75rem; text-decoration: none; transition: background 0.3s;"><?php echo esc_html( $btn_text ); ?></a>
                </div>
                <?php endif; ?>
            </div>
            <div class="hero-text-right"><span class="text-yield-life"><?php echo esc_html( $text_right ); ?></span></div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
}
add_shortcode( 'tryl_hero', 'tryl_hero_section_shortcode' );

/**
 * Prayer Request Form Shortcode
 * Displays the form for users to submit prayer requests.
 * Usage: [tryl_prayer_form]
 *
 * @return string HTML output of the prayer form.
 */
if ( ! function_exists( 'tryl_prayer_form_shortcode' ) ) {
function tryl_prayer_form_shortcode() {
    ob_start();
    
    // Display status messages from the redirect
    if ( isset( $_GET['prayer_status'] ) ) {
        if ( $_GET['prayer_status'] === 'success' ) {
            echo '<div style="background:#e8f0e8; color:#2d6a4f; padding:16px; border-left:4px solid #2d6a4f; margin-bottom:24px; font-family:\'Inter\', sans-serif;">Thank you. Your prayer request has been securely received by our team.</div>';
        } elseif ( $_GET['prayer_status'] === 'empty' ) {
            echo '<div style="background:#fde8e8; color:#9b1c1c; padding:16px; border-left:4px solid #9b1c1c; margin-bottom:24px; font-family:\'Inter\', sans-serif;">Please fill in both your name and your prayer message.</div>';
        } elseif ( $_GET['prayer_status'] === 'error' ) {
            echo '<div style="background:#fde8e8; color:#9b1c1c; padding:16px; border-left:4px solid #9b1c1c; margin-bottom:24px; font-family:\'Inter\', sans-serif;">There was an issue sending your request. Please try again later.</div>';
        }
    }
    ?>
    <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST" class="tryl-prayer-form" style="max-width:600px; margin:0 auto; font-family: 'Inter', sans-serif;">
        <input type="hidden" name="action" value="submit_prayer_request">
        <input type="hidden" name="tryl_submit_prayer_request" value="1">
        <?php wp_nonce_field( 'tryl_prayer_action', 'tryl_prayer_nonce' ); ?>
        
        <div style="margin-bottom:20px;">
            <label for="prayer_name" style="display:block; font-weight:600; margin-bottom:8px; color:var(--txt, #1a2e1a);">Your Name *</label>
            <input type="text" id="prayer_name" name="prayer_name" required style="width:100%; padding:12px; border:1px solid var(--border, #d4e0d4); border-radius:4px; font-family:inherit;">
        </div>
        
        <div style="margin-bottom:20px;">
            <label for="prayer_email" style="display:block; font-weight:600; margin-bottom:8px; color:var(--txt, #1a2e1a);">Your Email (Optional, if you'd like a response)</label>
            <input type="email" id="prayer_email" name="prayer_email" style="width:100%; padding:12px; border:1px solid var(--border, #d4e0d4); border-radius:4px; font-family:inherit;">
        </div>
        
        <div style="margin-bottom:24px;">
            <label for="prayer_message" style="display:block; font-weight:600; margin-bottom:8px; color:var(--txt, #1a2e1a);">Your Prayer *</label>
            <textarea id="prayer_message" name="prayer_message" rows="6" required style="width:100%; padding:12px; border:1px solid var(--border, #d4e0d4); border-radius:4px; font-family:inherit;"></textarea>
        </div>
        
        <button type="submit" style="background:#0d1b0f; color:#fff; padding:16px 32px; border:none; border-radius:4px; font-weight:700; text-transform:uppercase; letter-spacing:0.1em; cursor:pointer; width:100%; transition:background 0.3s;">Submit Prayer Request</button>
    </form>
    <?php
    return ob_get_clean();
}
}
add_shortcode( 'tryl_prayer_form', 'tryl_prayer_form_shortcode' );

/**
 * Public Prayer Wall Shortcode
 * Displays approved anonymous prayer requests.
 * Usage: [tryl_prayer_wall]
 *
 * @return string HTML output for the prayer wall grid.
 */
if ( ! function_exists( 'tryl_prayer_wall_shortcode' ) ) {
function tryl_prayer_wall_shortcode() {
    $args = array(
        'post_type'      => 'prayer_request',
        'posts_per_page' => 50,
        'meta_key'       => '_prayer_public',
        'meta_value'     => 'yes',
        'orderby'        => 'date',
        'order'          => 'DESC'
    );
    $prayers = new WP_Query( $args );
    
    ob_start();
    echo '<div class="tryl-prayer-wall" style="display:grid; gap:24px; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); margin: 40px 0; font-family: \'Inter\', sans-serif;">';
    
    if ( $prayers->have_posts() ) {
        while ( $prayers->have_posts() ) {
            $prayers->the_post();
            $name = get_post_meta( get_the_ID(), '_prayer_name', true );
            $first_name = ! empty( $name ) ? explode( ' ', trim( $name ) )[0] : 'Anonymous';
            
            echo '<div class="tryl-prayer-card" style="background:var(--card-bg, #fff); border:1px solid var(--border, #d4e0d4); padding:32px; border-radius:8px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); position:relative; overflow:hidden;">';
            echo '<svg style="position:absolute; top:16px; right:16px; width:32px; height:32px; opacity:0.05; color:var(--dark, #0d1b0f);" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
            echo '<div class="tryl-prayer-content" style="color:var(--txt, #1a2e1a); font-size:1rem; line-height:1.7; margin-bottom:24px; font-style:italic;">"' . esc_html( get_the_content() ) . '"</div>';
            echo '<div class="tryl-prayer-author" style="font-family:\'Barlow Condensed\', sans-serif; text-transform:uppercase; color:var(--accent, #2d6a4f); font-weight:700; letter-spacing:0.08em; font-size:0.9rem;">&mdash; ' . esc_html( $first_name ) . '</div>';
            echo '</div>';
        }
        wp_reset_postdata();
    } else {
        echo '<div style="grid-column: 1 / -1; text-align: center; padding: 48px; background:var(--card-bg, #fff); border:1px dashed var(--border, #d4e0d4); color:var(--muted, #6b7c6b); border-radius:8px;">No public prayer requests at this time. Be the first to share one!</div>';
    }
    
    echo '</div>';
    return ob_get_clean();
}
}
add_shortcode( 'tryl_prayer_wall', 'tryl_prayer_wall_shortcode' );
