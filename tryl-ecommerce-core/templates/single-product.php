<?php
/**
 * TRYL Single Product Template
 * A premium, technical minimalist product page replacing the default theme blog view.
 * 
 * CHANGELOG / RECENT ADJUSTMENTS:
 * - Introduced standalone template for single product view.
 * - Implemented direct "Buy It Now" routing and dynamic variation URL checks.
 * - Added specific classes to integrate with the mini-cart AJAX interceptor.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

// SEO Upgrade: Triggers WooCommerce structured data (JSON-LD) for Google Rich Snippets
do_action( 'woocommerce_before_main_content' );

while ( have_posts() ) :
    the_post();
    global $product;
    
    if ( empty( $product ) ) {
        $product = wc_get_product( get_the_ID() );
    }
    
    $product_id = $product->get_id();
    $product_title = $product->get_name();
    $product_price = $product->get_price_html();
    
    // Fetch product summary (short description) and full info properly
    $short_description = apply_filters( 'woocommerce_short_description', $product->get_short_description() );
    $description = apply_filters( 'the_content', $product->get_description() );
    
    $categories = wc_get_product_category_list( $product_id );
    
    $main_image_id = $product->get_image_id();
    $main_image_url = wp_get_attachment_image_url( $main_image_id, 'full' );
    if ( ! $main_image_url ) {
        $main_image_url = wc_placeholder_img_src();
    }
    
    $gallery_image_ids = $product->get_gallery_image_ids();
    
    $is_variable = $product->is_type( 'variable' );
    $is_in_stock = $product->is_in_stock();
    $checkout_url = wc_get_checkout_url();
    $buy_now_url = $is_variable ? '#options' : add_query_arg( 'add-to-cart', $product_id, $checkout_url );
    
    // Accordion variables
    $accordion_active  = get_option( 'tryl_product_accordion_active' ) === '1';
    $accordion_title   = get_option( 'tryl_product_accordion_title', 'Sizing & Fit Guide' );
    $accordion_content = get_option( 'tryl_product_accordion_content', 'Fits true to size. Order your normal size.' );
    $accordion_cats    = get_option( 'tryl_product_accordion_categories', '' );
    
    // Determine if the accordion should render based on category restrictions
    $show_accordion = false;
    if ( $accordion_active && ! empty( $accordion_content ) ) {
        $show_accordion = true;
        if ( ! empty( trim( $accordion_cats ) ) ) {
            // Split comma-separated string, clean whitespace, and check if current product matches
            $allowed_cats = array_map( 'trim', explode( ',', $accordion_cats ) );
            if ( ! has_term( $allowed_cats, 'product_cat', $product_id ) ) {
                $show_accordion = false;
            }
        }
    }
?>

<style>
    :root, [data-theme="bright"] {
        --sp-bg: #fafafa;
        --sp-card: #ffffff;
        --sp-text: #000000;
        --sp-muted: #4b5563;
        --sp-border: #e5e7eb;
        --sp-btn-bg: #000000;
        --sp-btn-text: #ffffff;
    }
    [data-theme="mild"] {
        --sp-bg: #e6e4df;
        --sp-card: #f2f0eb;
        --sp-text: #33322e;
        --sp-muted: #6b6355;
        --sp-border: #c4c0b5;
        --sp-btn-bg: #33322e;
        --sp-btn-text: #ffffff;
    }
    [data-theme="dark"] {
        --sp-bg: #0d1b0f;
        --sp-card: #132615;
        --sp-text: #f5f8f5;
        --sp-muted: #8a9c8a;
        --sp-border: #2d6a4f;
        --sp-btn-bg: #31d190;
        --sp-btn-text: #0d1b0f;
    }

    .tryl-sp-wrapper { background-color: var(--sp-bg); padding: 64px 32px; font-family: var(--tryl-body-font, 'Inter', sans-serif); min-height: 100vh; transition: background-color 0.3s; color: var(--sp-text); position: relative; }
    @media(max-width: 768px) { .tryl-sp-wrapper { padding: 48px 16px 100px 16px; } }
    .tryl-sp-container { max-width: 1280px; margin: 0 auto; }
    .tryl-sp-notices { margin-bottom: 32px; }
    
    .tryl-sp-breadcrumbs { font-size: 0.75rem; color: var(--sp-muted); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600; margin-bottom: 32px; display: flex; gap: 8px; align-items: center; }
    .tryl-sp-breadcrumbs a { color: var(--sp-muted); text-decoration: none; transition: color 0.2s; }
    .tryl-sp-breadcrumbs a:hover { color: var(--sp-text); }
    
    .tryl-sp-layout { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 64px; align-items: start; }
    @media(max-width: 1024px) { .tryl-sp-layout { grid-template-columns: 1fr; gap: 40px; } }
    
    .tryl-sp-gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .tryl-sp-thumb-img { background: var(--sp-card); border: 1px solid var(--sp-border); aspect-ratio: 1; display: flex; align-items: center; justify-content: center; padding: 16px; }
    .tryl-sp-gallery-grid img { width: 100%; height: 100%; object-fit: contain; }

    .tryl-sp-info { position: sticky; top: 100px; padding-top: 16px; }
    .tryl-sp-title { font-family: var(--tryl-header-font, 'Oswald', sans-serif); font-size: 3.5rem; font-weight: 800; text-transform: uppercase; color: var(--sp-text); line-height: 1; margin: 0 0 16px 0; transition: color 0.3s; }
    @media(max-width: 768px) { .tryl-sp-title { font-size: 2.5rem; } }
    
    .tryl-sp-price { font-size: 1.5rem; font-weight: 500; color: var(--sp-text); margin-bottom: 24px; transition: color 0.3s; }
    .tryl-sp-price del { color: var(--sp-muted); font-size: 1.1rem; margin-right: 8px; }
    .tryl-sp-price ins { text-decoration: none; }
    
    .tryl-sp-excerpt { color: var(--sp-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 32px; transition: color 0.3s; }
    
    .tryl-sp-buy-btn { display: block; width: 100%; text-align: center; padding: 16px; background-color: var(--sp-btn-bg); color: var(--sp-btn-text); font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.85rem; text-decoration: none; margin-bottom: 16px; transition: opacity 0.2s; border: none; cursor: pointer; }
    .tryl-sp-buy-btn:hover { opacity: 0.8; color: var(--sp-btn-text); }
    
    .tryl-sp-details { margin-top: 48px; padding-top: 32px; border-top: 1px solid var(--sp-border); transition: border-color 0.3s; }
    .tryl-sp-details-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--sp-text); margin: 0 0 16px 0; }
    .tryl-sp-details-content { color: var(--sp-muted); font-size: 0.9rem; line-height: 1.7; transition: color 0.3s; }

    /* Badges */
    .tryl-sp-badge { display: inline-block; padding: 6px 14px; background: var(--sp-btn-bg); color: var(--sp-btn-text); font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 16px; border-radius: 4px; }
    .tryl-sp-badge.dynamic-badge { background: <?php echo esc_attr(get_option('tryl_badges_bg', '#31d190')); ?>; color: <?php echo esc_attr(get_option('tryl_badges_text_color', '#0d1b0f')); ?>; }
    .tryl-sp-badge.sold-out { background: #d63638; color: #fff; }
    
    /* Accordion Styles */
    .tryl-sp-accordion { border-top: 1px solid var(--sp-border); border-bottom: 1px solid var(--sp-border); margin-top: 32px; transition: border-color 0.3s; }
    .tryl-sp-details + .tryl-sp-accordion { border-top: none; margin-top: 0; }
    .tryl-sp-accordion details { padding: 16px 0; }
    .tryl-sp-accordion summary { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--sp-text); cursor: pointer; display: flex; justify-content: space-between; align-items: center; list-style: none; outline: none; }
    .tryl-sp-accordion summary::-webkit-details-marker { display: none; }
    .tryl-sp-accordion summary::after { content: '+'; font-size: 1.2rem; font-weight: 400; transition: transform 0.3s; color: var(--sp-text); }
    .tryl-sp-accordion details[open] summary::after { transform: rotate(45deg); }
    .tryl-sp-accordion-content { padding-top: 16px; color: var(--sp-muted); font-size: 0.9rem; line-height: 1.7; animation: trylFadeIn 0.3s ease; }
    .tryl-sp-accordion-content img { max-width: 100%; height: auto; border-radius: 4px; margin-top: 12px; }
    @keyframes trylFadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    
    /* Sticky Mobile ATC */
    .tryl-sp-sticky-atc { display: none; position: fixed; bottom: 0; left: 0; right: 0; background: var(--sp-card); border-top: 1px solid var(--sp-border); padding: 16px; z-index: 9999; justify-content: space-between; align-items: center; box-shadow: 0 -4px 20px rgba(0,0,0,0.08); animation: trylSlideUp 0.5s ease forwards; }
    @media(max-width: 768px) { .tryl-sp-sticky-atc { display: flex; } }
    .tryl-sticky-price { font-size: 1.2rem; font-weight: 700; color: var(--sp-text); line-height: 1; }
    .tryl-sticky-price del { font-size: 0.9rem; color: var(--sp-muted); margin-right: 6px; font-weight: 500; }
    .tryl-sticky-btn { background: var(--sp-btn-bg); color: var(--sp-btn-text); padding: 12px 24px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.8rem; text-decoration: none; border-radius: 4px; transition: opacity 0.2s; }
    @keyframes trylSlideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    
    .tryl-sp-related { margin-top: 80px; padding-top: 48px; border-top: 2px solid var(--sp-border); }
    
    .gsap-gallery-item, .gsap-product-info > * { will-change: transform, opacity; }

    /* Standardize Woo form inputs to match aesthetic */
    form.cart { margin-bottom: 1.5rem; }
    form.cart .variations { width: 100%; margin-bottom: 16px; }
    form.cart .variations td { padding: 0 0 16px 0; display: block; width: 100%; text-align: left; }
    form.cart .variations label { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sp-text); margin-bottom: 8px; display: block; }
    form.cart .variations select {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--sp-border);
        background: var(--sp-card);
        color: var(--sp-text);
        font-family: var(--tryl-body-font, 'Inter', sans-serif);
        border-radius: 0;
    }
    form.cart .woocommerce-variation-add-to-cart {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 1rem;
    }
    form.cart .quantity {
        display: flex;
        align-items: center;
        border: 1px solid var(--sp-border);
        background: var(--sp-card);
        color: var(--sp-text);
        width: 120px;
    }
    form.cart .quantity input.qty {
        width: 100%;
        padding: 12px;
        border: none;
        text-align: center;
        background: transparent;
        color: var(--sp-text);
    }
    form.cart button.single_add_to_cart_button {
        width: 100%;
        padding: 16px;
        background-color: var(--sp-card);
        color: var(--sp-text);
        border: 2px solid var(--sp-text);
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        cursor: pointer;
        transition: all 0.2s;
        border-radius: 0;
    }
    form.cart button.single_add_to_cart_button:hover {
        background-color: var(--sp-text);
        color: var(--sp-card);
    }
    form.cart button.single_add_to_cart_button.loading {
        opacity: 0.6;
        pointer-events: none;
    }
    form.cart button.single_add_to_cart_button.added {
        background-color: #2d6a4f;
        border-color: #2d6a4f;
        color: white;
    }

    /* WooCommerce Tabs & Related Products */
    .woocommerce-tabs ul.tabs { display: flex; gap: 24px; list-style: none; padding: 0; margin: 0 0 32px 0; border-bottom: 2px solid var(--sp-border); }
    .woocommerce-tabs ul.tabs li { margin: 0; padding: 0 0 12px 0; border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .woocommerce-tabs ul.tabs li.active { border-color: var(--sp-text); }
    .woocommerce-tabs ul.tabs li a { color: var(--sp-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; transition: color 0.2s; }
    .woocommerce-tabs ul.tabs li.active a, .woocommerce-tabs ul.tabs li a:hover { color: var(--sp-text); }
    .woocommerce-tabs .panel h2 { display: none; }
    .related.products > h2 { font-family: var(--tryl-header-font, 'Oswald', sans-serif); font-size: 2rem; text-transform: uppercase; color: var(--sp-text); margin-bottom: 32px; }
    .related.products ul.products { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; list-style: none; padding: 0; margin: 0; }
    @media(max-width: 900px) { .related.products ul.products { grid-template-columns: repeat(2, 1fr); } }
    .related.products li.product { text-align: center; }
    .related.products li.product img { width: 100%; aspect-ratio: 1; object-fit: contain; background: var(--sp-card); border: 1px solid var(--sp-border); margin-bottom: 16px; padding: 16px; }
    .related.products li.product .woocommerce-loop-product__title { font-size: 1rem; font-weight: 700; text-transform: uppercase; color: var(--sp-text); margin-bottom: 8px; }
    .related.products li.product .price { color: var(--sp-muted); font-weight: 500; }
    .related.products li.product .button { display: none; }
</style>
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<div id="product-<?php echo esc_attr( $product->get_id() ); ?>" <?php wc_product_class( 'tryl-sp-wrapper', $product ); ?>>
    <div class="tryl-sp-container">
        
        <div class="tryl-sp-notices">
            <?php do_action( 'woocommerce_before_single_product' ); ?>
        </div>

        <!-- Breadcrumbs -->
        <nav class="tryl-sp-breadcrumbs">
            <a href="<?php echo home_url('/'); ?>">Home</a>
            <span>/</span>
            <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>">Shop</a>
            <span>/</span>
            <span style="color: var(--sp-text);"><?php echo wp_strip_all_tags( $categories ); ?></span>
        </nav>

        <div class="tryl-sp-layout">
            
            <!-- Left: Image Gallery -->
            <div class="tryl-sp-gallery images">
                <div class="tryl-sp-gallery-grid">
                    <!-- Main Image -->
                    <div class="gsap-gallery-item tryl-sp-main-img">
                        <?php if ( ! $is_in_stock ) : ?>
                        <span style="position:absolute; top:24px; left:24px; background:#d63638; color:#fff; font-size:0.75rem; font-weight:700; letter-spacing:0.1em; text-transform:uppercase; padding:8px 16px; z-index:10; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">Sold Out</span>
                        <?php endif; ?>
                        <img src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="wp-post-image" data-o_src="<?php echo esc_url( $main_image_url ); ?>">
                    </div>
                    
                    <!-- Gallery Images -->
                    <?php if ( $gallery_image_ids ) : ?>
                        <?php foreach ( $gallery_image_ids as $attachment_id ) : 
                            $gallery_img_url = wp_get_attachment_image_url( $attachment_id, 'full' );
                        ?>
                        <div class="gsap-gallery-item tryl-sp-thumb-img">
                            <img src="<?php echo esc_url( $gallery_img_url ); ?>" alt="Gallery Image">
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if ( get_option('tryl_sp_trust_badges_active', '1') === '1' ) : ?>
                <div class="tryl-sp-trust-badges" style="margin-top: 32px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                    <div style="background: var(--sp-card); border: 1px solid var(--sp-border); padding: 16px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-shield" style="color: var(--sp-accent); font-size: 20px; width: 20px; height: 20px;"></span>
                        <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sp-text);">Secure Checkout</div>
                    </div>
                    <div style="background: var(--sp-card); border: 1px solid var(--sp-border); padding: 16px; border-radius: 8px; display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-admin-site-alt3" style="color: var(--sp-accent); font-size: 20px; width: 20px; height: 20px;"></span>
                        <div style="font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--sp-text);">Global Shipping</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Product Info -->
            <div class="tryl-sp-info gsap-product-info">
                <?php 
                    $badges_active = get_option('tryl_badges_active');
                    $badge_text = '';
                    $b_cls = 'tryl-sp-badge';
                    if ( ! $product->is_in_stock() ) {
                        $badge_text = 'Sold Out';
                        $b_cls .= ' sold-out';
                    } elseif ( $badges_active ) {
                        $total_sales = (int) get_post_meta( $product->get_id(), 'total_sales', true );
                        $created_date = $product->get_date_created() ? $product->get_date_created()->getTimestamp() : 0;
                        $days_old = ( current_time('timestamp') - $created_date ) / DAY_IN_SECONDS;
                        
                        $bestseller_thresh = (int) get_option('tryl_badges_bestseller_sales', 50);
                        if ($bestseller_thresh <= 0) $bestseller_thresh = 50;
                        $new_days_thresh = (int) get_option('tryl_badges_new_days', 14);
                        if ($new_days_thresh <= 0) $new_days_thresh = 14;
                        
                        if ( $total_sales > 0 && $total_sales >= $bestseller_thresh ) {
                            $badge_text = 'Bestseller'; $b_cls .= ' dynamic-badge';
                        } elseif ( $created_date && $days_old <= $new_days_thresh ) {
                            $badge_text = 'New Drop'; $b_cls .= ' dynamic-badge';
                        }
                    }
                    if ( ! $badge_text && $product->is_on_sale() ) $badge_text = 'Sale';

                    if ( $badge_text ) : 
                ?>
                <div class="<?php echo esc_attr($b_cls); ?>"><?php echo esc_html($badge_text); ?></div>
                <?php endif; ?>

                <h1 class="tryl-sp-title">
                    <?php echo esc_html( $product_title ); ?>
                </h1>
                
                <div class="tryl-sp-price">
                    <?php echo wp_kses_post($product_price); ?>
                </div>
                
                <!-- Product Summary (Short Description) -->
                <?php if ( ! empty( $short_description ) ) : ?>
                <div class="tryl-sp-excerpt">
                    <?php echo $short_description; ?>
                </div>
                <?php endif; ?>
                
                <!-- Product Form (Add to Cart / Variations) -->
                <div class="mb-6 w-full" id="options">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>

                <!-- Custom Buy Now Button -->
                <?php if ( ! $is_variable && $is_in_stock ) : ?>
                <a href="<?php echo esc_url( $buy_now_url ); ?>" class="tryl-sp-buy-btn">
                    Buy It Now
                </a>
                <?php endif; ?>
                
                <!-- Full Product Info (Description) -->
                <?php if ( ! empty( $description ) ) : ?>
                <div class="tryl-sp-details">
                    <h3 class="tryl-sp-details-title">Product Details</h3>
                    <div class="tryl-sp-details-content">
                        <?php echo $description; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Global Product Accordion (e.g. Sizing Chart) -->
                <?php if ( $show_accordion ) : ?>
                <div class="tryl-sp-accordion">
                    <details>
                        <summary><?php echo esc_html( $accordion_title ); ?></summary>
                        <div class="tryl-sp-accordion-content">
                            <?php echo do_shortcode( wpautop( $accordion_content ) ); ?>
                        </div>
                    </details>
                </div>
                <?php endif; ?>
                
            </div>
        </div>

        <!-- Related Products & Tabs (WooCommerce hooks) -->
        <div class="tryl-sp-related">
            <?php do_action( 'woocommerce_after_single_product_summary' ); ?>
        </div>

    </div>
</div>

<!-- Mobile Sticky Add to Cart (CTA) -->
<div class="tryl-sp-sticky-atc">
    <div class="tryl-sticky-price"><?php echo wp_kses_post($product_price); ?></div>
    <a href="#options" class="tryl-sticky-btn">Add to Cart</a>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof gsap !== "undefined") {
            gsap.fromTo(".gsap-gallery-item", 
                { opacity: 0, y: 30 }, 
                { opacity: 1, y: 0, duration: 0.8, stagger: 0.15, ease: "power3.out" }
            );
            gsap.fromTo(".gsap-product-info > *", 
                { opacity: 0, x: 20 }, 
                { opacity: 1, x: 0, duration: 0.8, stagger: 0.08, ease: "power3.out", delay: 0.2 }
            );
        }
    });
</script>

<?php
endwhile;

get_footer();
?>