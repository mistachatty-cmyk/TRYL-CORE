<?php
/**
 * TRYL Single Product Template - Nike Inspired Redesign
 * A premium, technical minimalist product page replacing the default theme blog view.
 * 
 * This template implements a split-screen layout with:
 * - Left column: Full-width, sticky product image gallery (Nike-style)
 * - Right column: Product info with large variant selectors and prominent CTA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check if premium products are active
$premium_active = get_option('tryl_premium_products_active', '1');
if ( $premium_active !== '1' ) {
    // Fallback to default template if premium products disabled
    wc_get_template_part( 'content', 'single-product' );
    return;
}

get_header();

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
        --sp-bg: #ffffff;
        --sp-card: #ffffff;
        --sp-text: #111111;
        --sp-muted: #444444;
        --sp-border: #eeeeee;
        --sp-btn-bg: #004db4;
        --sp-btn-text: #ffffff;
        --sp-accent: #d62f31;
        --sp-accent-2: #0e8b70;
        --sp-accent-3: #fad02c;
    }
    [data-theme="mild"] {
        --sp-bg: #f8f9fa;
        --sp-card: #ffffff;
        --sp-text: #222222;
        --sp-muted: #666666;
        --sp-border: #e0e0e0;
        --sp-btn-bg: #004db4;
        --sp-btn-text: #ffffff;
        --sp-accent: #d62f31;
        --sp-accent-2: #0e8b70;
        --sp-accent-3: #fad02c;
    }
    [data-theme="dark"] {
        --sp-bg: #000000;
        --sp-card: #111111;
        --sp-text: #f8f9fa;
        --sp-muted: #cccccc;
        --sp-border: #333333;
        --sp-btn-bg: #004db4;
        --sp-btn-text: #ffffff;
        --sp-accent: #d62f31;
        --sp-accent-2: #0e8b70;
        --sp-accent-3: #fad02c;
    }

    .tryl-sp-wrapper { 
        background-color: var(--sp-bg); 
        padding: 0; 
        font-family: var(--tryl-body-font, 'Helvetica Neue', Helvetica, sans-serif); 
        min-height: 100vh; 
        transition: background-color 0.3s; 
        color: var(--sp-text); 
        position: relative;
        overflow-x: hidden;
    }
    
    @media(max-width: 1024px) { 
        .tryl-sp-wrapper { padding: 0; }
    }
    
    .tryl-sp-container { 
        max-width: 100%; 
        margin: 0 auto; 
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        height: 100vh;
    }
    
    @media(max-width: 1024px) { 
        .tryl-sp-container { 
            grid-template-columns: 1fr; 
            height: auto; 
            min-height: 100vh;
        }
    }

    .tryl-sp-notices { 
        margin-bottom: 0; 
        padding: 32px; 
        grid-column: 1 / -1;
    }
    
    .tryl-sp-breadcrumbs { 
        font-size: 0.75rem; 
        color: var(--sp-muted); 
        text-transform: uppercase; 
        letter-spacing: 0.1em; 
        font-weight: 600; 
        margin-bottom: 24px; 
        display: flex; 
        gap: 8px; 
        align-items: center;
        grid-column: 1 / -1;
    }
    .tryl-sp-breadcrumbs a { 
        color: var(--sp-muted); 
        text-decoration: none; 
        transition: color 0.2s; 
    }
    .tryl-sp-breadcrumbs a:hover { 
        color: var(--sp-text); 
    }
    
    .tryl-sp-layout { 
        display: contents;
        height: 100%;
    }
    
    .tryl-sp-gallery { 
        grid-column: 1; 
        display: flex; 
        flex-direction: column; 
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    
    @media(max-width: 1024px) { 
        .tryl-sp-gallery { 
            grid-column: 1 / -1; 
            height: 60vh; 
            min-height: 400px;
        }
    }
    
    .tryl-sp-gallery-images { 
        flex: 1; 
        overflow: hidden;
        position: relative;
    }
    
    .tryl-sp-main-img { 
        width: 100%; 
        height: 100%; 
        object-fit: contain; 
        display: block;
        position: sticky;
        top: 0;
        left: 0;
        z-index: 10;
    }
    
    .tryl-sp-main-img img { 
        width: 100%; 
        height: 100%; 
        object-fit: contain; 
        display: block;
    }
    
    .tryl-sp-thumbs { 
        display: flex; 
        gap: 12px; 
        padding: 16px; 
        background: var(--sp-card);
        border-top: 1px solid var(--sp-border);
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .tryl-sp-thumbs::-webkit-scrollbar { 
        display: none; 
    }
    
    .tryl-sp-thumb { 
        flex: 0 0 auto; 
        width: 80px; 
        height: 80px; 
        border: 2px solid transparent; 
        border-radius: 8px; 
        overflow: hidden; 
        cursor: pointer; 
        transition: all 0.2s ease;
        position: relative;
    }
    
    .tryl-sp-thumb.active { 
        border-color: var(--sp-text); 
    }
    
    .tryl-sp-thumb img { 
        width: 100%; 
        height: 100%; 
        object-fit: cover; 
        display: block;
    }
    
    .tryl-sp-thumb:hover:not(.active) { 
        border-color: var(--sp-muted);
    }
    
    .tryl-sp-sold-out-badge { 
        position: absolute; 
        top: 16px; 
        left: 16px; 
        background: #d63638; 
        color: #fff; 
        font-size: 0.75rem; 
        font-weight: 700; 
        letter-spacing: 0.1em; 
        text-transform: uppercase; 
        padding: 6px 12px; 
        border-radius: 4px; 
        z-index: 20;
    }
    
    .tryl-sp-info { 
        grid-column: 2; 
        padding: 64px 48px; 
        display: flex; 
        flex-direction: column; 
        justify-content: center; 
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
    }
    
    @media(max-width: 1024px) { 
        .tryl-sp-info { 
            grid-column: 1 / -1; 
            padding: 32px 24px; 
            height: auto; 
            position: static;
        }
    }
    
    .tryl-sp-badge { 
        display: inline-block; 
        padding: 8px 16px; 
        background: var(--sp-btn-bg); 
        color: var(--sp-btn-text); 
        font-size: 0.75rem; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.1em; 
        margin-bottom: 24px; 
        border-radius: 4px;
    }
    
    .tryl-sp-badge.dynamic-badge { 
        background: <?php echo esc_attr(get_option('tryl_badges_bg', '#31d190')); ?>; 
        color: <?php echo esc_attr(get_option('tryl_badges_text_color', '#0d1b0f')); ?>; 
    }
    
    .tryl-sp-badge.sold-out { 
        background: #d63638; 
        color: #fff; 
    }
    
    .tryl-sp-title { 
        font-family: var(--tryl-header-font, 'Helvetica Neue', Helvetica, sans-serif); 
        font-size: 4rem; 
        font-weight: 900; 
        text-transform: uppercase; 
        color: var(--sp-text); 
        line-height: 1; 
        margin: 0 0 24px 0; 
        letter-spacing: -0.02em;
    }
    
    @media(max-width: 1024px) { 
        .tryl-sp-title { 
            font-size: 2.5rem; 
        }
    }
    
    @media(max-width: 768px) { 
        .tryl-sp-title { 
            font-size: 2rem; 
        }
    }
    
    .tryl-sp-price { 
        font-size: 2.25rem; 
        font-weight: 600; 
        color: var(--sp-text); 
        margin-bottom: 32px; 
        line-height: 1.2;
    }
    
    .tryl-sp-price del { 
        color: var(--sp-muted); 
        font-size: 1.1rem; 
        margin-right: 8px; 
    }
    
    .tryl-sp-price ins { 
        text-decoration: none; 
    }
    
    .tryl-sp-excerpt { 
        color: var(--sp-muted); 
        font-size: 1.125rem; 
        line-height: 1.6; 
        margin-bottom: 32px; 
    }
    
    .tryl-sp-variations { 
        margin-bottom: 40px; 
    }
    
    .tryl-sp-variations-label { 
        font-size: 0.875rem; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        color: var(--sp-text); 
        margin-bottom: 12px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center;
    }
    
    .tryl-sp-variations-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); 
        gap: 12px; 
        margin-top: 8px;
    }
    
    .tryl-sp-variant-btn { 
        aspect-ratio: 1; 
        background: var(--sp-card); 
        border: 2px solid var(--sp-border); 
        color: var(--sp-text); 
        font-family: var(--tryl-body-font, 'Helvetica Neue', Helvetica, sans-serif); 
        font-size: 0.875rem; 
        font-weight: 600; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        cursor: pointer; 
        border-radius: 12px; 
        transition: all 0.2s ease;
        display: flex; 
        align-items: center; 
        justify-content: center;
        position: relative;
        overflow: hidden;
    }
    
    .tryl-sp-variant-btn:hover { 
        border-color: var(--sp-text); 
        transform: translateY(-2px);
    }
    
    .tryl-sp-variant-btn.active { 
        border-color: var(--sp-text); 
        background: var(--sp-bg); 
        box-shadow: 0 0 0 3px var(--sp-text);
    }
    
    .tryl-sp-variant-btn .variant-label { 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        gap: 4px;
    }
    
    .tryl-sp-variant-btn .variant-value { 
        font-weight: 700; 
    }
    
    .tryl-sp-variant-btn .variant-name { 
        font-size: 0.75rem; 
        opacity: 0.8;
    }
    
    .tryl-sp-color-swatch { 
        width: 20px; 
        height: 20px; 
        border-radius: 50%; 
        border: 2px solid var(--sp-border);
    }
    
    .tryl-sp-buy-btn { 
        display: block; 
        width: 100%; 
        max-width: 400px; 
        padding: 20px 32px; 
        background-color: var(--sp-btn-bg); 
        color: var(--sp-btn-text); 
        font-weight: 800; 
        text-transform: uppercase; 
        letter-spacing: 0.1em; 
        font-size: 1rem; 
        text-decoration: none; 
        border: none; 
        border-radius: 12px; 
        cursor: pointer; 
        transition: all 0.2s ease; 
        margin: 32px 0; 
        display: flex; 
        align-items: center; 
        justify-content: center;
        gap: 12px;
    }
    
    .tryl-sp-buy-btn:hover { 
        background-color: var(--sp-accent); 
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(214, 47, 49, 0.3);
    }
    
    .tryl-sp-buy-btn:active { 
        transform: translateY(0);
    }
    
    .tryl-sp-details { 
        margin-top: auto; 
        padding-top: 32px; 
        border-top: 1px solid var(--sp-border); 
    }
    
    .tryl-sp-details-title { 
        font-size: 0.875rem; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.1em; 
        color: var(--sp-text); 
        margin: 0 0 16px 0; 
    }
    
    .tryl-sp-details-content { 
        color: var(--sp-muted); 
        font-size: 1rem; 
        line-height: 1.7; 
    }
    
    /* Accordion Styles */
    .tryl-sp-accordion { 
        border-top: 1px solid var(--sp-border); 
        border-bottom: 1px solid var(--sp-border); 
        margin-top: 32px; 
    }
    
    .tryl-sp-details + .tryl-sp-accordion { 
        border-top: none; 
        margin-top: 0; 
    }
    
    .tryl-sp-accordion details { 
        padding: 16px 0; 
    }
    
    .tryl-sp-accordion summary { 
        font-size: 0.875rem; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.1em; 
        color: var(--sp-text); 
        cursor: pointer; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        list-style: none; 
        outline: none; 
    }
    
    .tryl-sp-accordion summary::-webkit-details-marker { 
        display: none; 
    }
    
    .tryl-sp-accordion summary::after { 
        content: '+'; 
        font-size: 1.2rem; 
        font-weight: 400; 
        transition: transform 0.3s; 
        color: var(--sp-text); 
    }
    
    .tryl-sp-accordion details[open] summary::after { 
        transform: rotate(45deg); 
    }
    
    .tryl-sp-accordion-content { 
        padding-top: 16px; 
        color: var(--sp-muted); 
        font-size: 1rem; 
        line-height: 1.7; 
    }
    
    .tryl-sp-accordion-content img { 
        max-width: 100%; 
        height: auto; 
        border-radius: 4px; 
        margin-top: 12px; 
    }
    
    /* Related Products */
    .tryl-sp-related { 
        margin-top: 64px; 
        padding-top: 48px; 
        border-top: 2px solid var(--sp-border); 
        grid-column: 1 / -1; 
    }
    
    .tryl-sp-related h2 { 
        font-family: var(--tryl-header-font, 'Helvetica Neue', Helvetica, sans-serif); 
        font-size: 2rem; 
        text-transform: uppercase; 
        color: var(--sp-text); 
        margin-bottom: 32px; 
    }
    
    .tryl-sp-related ul.products { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
        gap: 24px; 
        list-style: none; 
        padding: 0; 
        margin: 0; 
    }
    
    .tryl-sp-related li.product { 
        text-align: center; 
    }
    
    .tryl-sp-related li.product img { 
        width: 100%; 
        aspect-ratio: 1; 
        object-fit: contain; 
        background: var(--sp-card); 
        border: 1px solid var(--sp-border); 
        margin-bottom: 16px; 
        padding: 16px; 
    }
    
    .tryl-sp-related li.product .woocommerce-loop-product__title { 
        font-size: 1rem; 
        font-weight: 700; 
        text-transform: uppercase; 
        color: var(--sp-text); 
        margin-bottom: 8px; 
    }
    
    .tryl-sp-related li.product .price { 
        color: var(--sp-muted); 
        font-weight: 500; 
    }
    
    .tryl-sp-related li.product .button { 
        display: none; 
    }
    
    /* Mobile Sticky Add to Cart */
    .tryl-sp-sticky-atc { 
        display: none; 
        position: fixed; 
        bottom: 0; 
        left: 0; 
        right: 0; 
        background: var(--sp-card); 
        border-top: 1px solid var(--sp-border); 
        padding: 24px; 
        z-index: 9999; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08); 
    }
    
    @media(max-width: 1024px) { 
        .tryl-sp-sticky-atc { 
            display: flex; 
        }
    }
    
    .tryl-sticky-price { 
        font-size: 1.5rem; 
        font-weight: 700; 
        color: var(--sp-text); 
        line-height: 1; 
    }
    
    .tryl-sticky-price del { 
        font-size: 0.9rem; 
        color: var(--sp-muted); 
        margin-right: 6px; 
        font-weight: 500; 
    }
    
    .tryl-sticky-btn { 
        background: var(--sp-btn-bg); 
        color: var(--sp-btn-text); 
        padding: 12px 24px; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.1em; 
        font-size: 0.9rem; 
        text-decoration: none; 
        border-radius: 8px; 
        transition: opacity 0.2s; 
    }
    
    .tryl-sticky-btn:hover { 
        opacity: 0.8; 
    }
    
    /* GSAP Animations */
    .gsap-gallery-item, .gsap-product-info > * { 
        will-change: transform, opacity; 
    }
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
            <div class="tryl-sp-gallery">
                <?php if ( ! $is_in_stock ) : ?>
                <span class="tryl-sp-sold-out-badge">Sold Out</span>
                <?php endif; ?>
                
                <div class="tryl-sp-gallery-images">
                    <img src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="tryl-sp-main-img">
                </div>
                
                <?php if ( $gallery_image_ids ) : ?>
                <div class="tryl-sp-thumbs">
                    <?php foreach ( $gallery_image_ids as $attachment_id ) : 
                        $gallery_img_url = wp_get_attachment_image_url( $attachment_id, 'full' );
                    ?>
                    <div class="tryl-sp-thumb" data-thumb-url="<?php echo esc_url( $gallery_img_url ); ?>">
                        <img src="<?php echo esc_url( $gallery_img_url ); ?>" alt="Gallery Image">
                    </div>
                    <?php endforeach; ?>
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
                
                <!-- Product Variations -->
                <div class="tryl-sp-variations" id="options">
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
            gsap.fromTo(".tryl-sp-main-img img", 
                { opacity: 0, y: 30 }, 
                { opacity: 1, y: 0, duration: 0.8, ease: "power3.out" }
            );
            
            // Animate product info elements
            const infoElements = document.querySelectorAll('.tryl-sp-info > *');
            infoElements.forEach((el, index) => {
                gsap.fromTo(el, 
                    { opacity: 0, x: 20 }, 
                    { opacity: 1, x: 0, duration: 0.6, delay: index * 0.08, ease: "power3.out" }
                );
            });
        }
    });
    
    // Thumbnail clicks
    document.addEventListener("DOMContentLoaded", () => {
        const thumbs = document.querySelectorAll('.tryl-sp-thumb');
        const mainImg = document.querySelector('.tryl-sp-main-img img');
        
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                // Remove active class from all thumbs
                thumbs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked thumb
                thumb.classList.add('active');
                // Update main image
                const thumbUrl = thumb.getAttribute('data-thumb-url');
                if (thumbUrl && mainImg) {
                    mainImg.src = thumbUrl;
                    // Add subtle fade-in effect
                    mainImg.style.opacity = 0;
                    setTimeout(() => {
                        mainImg.style.opacity = 1;
                    }, 50);
                }
            });
        });
    });
    
    // Variant selection (convert dropdowns to buttons)
    document.addEventListener("DOMContentLoaded", () => {
        const selects = document.querySelectorAll('form.cart .variations select');
        selects.forEach(select => {
            const wrapper = select.parentNode;
            
            // Add a clean label
            const labelEl = wrapper.previousElementSibling;
            let labelText = 'Select Option';
            if (labelEl && labelEl.tagName === 'LABEL') {
                labelText = labelEl.textContent;
                labelEl.style.display = 'none'; // hide standard woo label
            }
            
            const title = document.createElement('div');
            title.className = 'tryl-sp-variations-label';
            title.innerHTML = `<span>${labelText}</span>`;
            wrapper.insertBefore(title, select);
            
            const grid = document.createElement('div');
            grid.className = 'tryl-sp-variations-grid';
            
            Array.from(select.options).forEach(option => {
                if(option.value === '') return;
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'tryl-sp-variant-btn';
                
                // Check if it's a color swatch
                const optionText = option.text.toLowerCase();
                if (optionText.includes('#') || 
                    ['red', 'blue', 'green', 'yellow', 'black', 'white', 'orange', 'purple', 'pink', 'brown', 'gray', 'grey'].some(color => optionText.includes(color))) {
                    // Create color swatch
                    btn.innerHTML = `
                        <div class="tryl-sp-color-swatch" style="background: ${getColorFromText(option.text)}; border: 2px solid var(--sp-border);"></div>
                        <div class="variant-label">
                            <div class="variant-value">${option.text}</div>
                        </div>
                    `;
                } else {
                    // Regular text button
                    btn.innerHTML = `
                        <div class="variant-label">
                            <div class="variant-value">${option.text}</div>
                        </div>
                    `;
                }
                
                btn.addEventListener('click', () => {
                    grid.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    select.value = option.value;
                    jQuery(select).trigger('change');
                    
                    // Update main image if variation has image
                    if (typeof jQuery !== 'undefined') {
                        jQuery('.variations_form').trigger('check_variations', [select, false]);
                    }
                });
                
                grid.appendChild(btn);
            });
            
            wrapper.insertBefore(grid, select);
            select.style.display = 'none';
            
            // Initialize first option as selected if none selected
            if (!select.value && select.options[1]) {
                select.value = select.options[1].value;
                jQuery(select).trigger('change');
                grid.querySelector('button').classList.add('active');
            }
        });
        
        // Handle variation image changes
        if (typeof jQuery !== 'undefined') {
            jQuery('.variations_form').on('show_variation', function(event, variation) {
                if (variation.image && variation.image.src && variation.image.src.length > 1) {
                    var mainImg = document.querySelector('.tryl-sp-main-img img');
                    if (mainImg) {
                        mainImg.src = variation.image.src;
                        if (variation.image.srcset) mainImg.srcset = variation.image.srcset;
                        
                        // Animate image change
                        if (typeof gsap !== 'undefined') {
                            gsap.fromTo(mainImg, { opacity: 0.5, scale: 0.98 }, { opacity: 1, scale: 1, duration: 0.4, ease: "power2.out" });
                        }
                    }
                }
            });
            
            jQuery('.variations_form').on('reset_image', function() {
                var mainImg = document.querySelector('.tryl-sp-main-img img');
                if (mainImg && mainImg.dataset.o_src) {
                    mainImg.src = mainImg.dataset.o_src;
                    mainImg.srcset = '';
                }
            });
        }
    });
    
    // Helper function to extract color from text
    function getColorFromText(text) {
        const colors = {
            'red': '#d62f31',
            'blue': '#004db4',
            'green': '#0e8b70',
            'yellow': '#fad02c',
            'black': '#000000',
            'white': '#ffffff',
            'orange': '#ff6b35',
            'purple': '#8e44ad',
            'pink': '#ec87b0',
            'brown': '#8b4513',
            'gray': '#808080',
            'grey': '#808080'
        };
        
        const lowerText = text.toLowerCase();
        for (const [colorName, colorValue] of Object.entries(colors)) {
            if (lowerText.includes(colorName)) {
                return colorValue;
            }
        }
        
        // Default to accent color if no match found
        return '#004db4';
    }
    
    // Form submission handling
    document.addEventListener("DOMContentLoaded", () => {
        const cartForm = document.querySelector('form.cart');
        if (cartForm) {
            cartForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const btn = this.querySelector('button[type="submit"]');
                if (!btn || btn.classList.contains('loading')) return;
                
                const ogText = btn.innerText;
                btn.classList.add('loading');
                btn.innerText = 'Adding...';
                
                const formData = new FormData(this);
                formData.append('add-to-cart', btn.value || this.querySelector('[name="add-to-cart"]')?.value || '<?php echo esc_js($product_id); ?>');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: { 'Accept': 'text/html' }
                })
                .then(response => {
                    btn.classList.remove('loading');
                    if (typeof window.trylOpenCart === 'function') window.trylOpenCart();
                    btn.innerText = (typeof trylCoreSettings !== 'undefined') ? trylCoreSettings.btnText : (window.trylMiniCart ? window.trylMiniCart.btnText : 'Added!');
                    setTimeout(() => { btn.innerText = ogText; }, 2500);
                })
                .catch(err => {
                    btn.classList.remove('loading');
                    btn.innerText = 'Error';
                    setTimeout(() => { btn.innerText = ogText; }, 2000);
                });
            });
        }
    });
</script>

<?php
endwhile;

get_footer();
?>