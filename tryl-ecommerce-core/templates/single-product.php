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
    $checkout_url = wc_get_checkout_url();
    $buy_now_url = $is_variable ? '#options' : add_query_arg( 'add-to-cart', $product_id, $checkout_url );
?>

<div class="tryl-sp-bg min-h-screen py-16 px-4 sm:px-6 lg:px-8 font-inter transition-colors duration-300">
    <div class="max-w-7xl mx-auto">
        <!-- Breadcrumbs -->
        <nav class="text-xs text-gray-500 mb-8 uppercase tracking-widest font-semibold flex items-center gap-2">
            <a href="<?php echo home_url('/'); ?>" class="tryl-sp-text-hover transition-colors">Home</a>
            <span>/</span>
            <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="tryl-sp-text-hover transition-colors">Shop</a>
            <span>/</span>
            <span class="tryl-sp-text"><?php echo wp_strip_all_tags( $categories ); ?></span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-12 lg:gap-20">
            
            <!-- Left: Image Gallery -->
            <div class="w-full lg:w-3/5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Main Image -->
                    <div class="gsap-gallery-item tryl-sp-card tryl-sp-border aspect-square md:aspect-[4/5] flex items-center justify-center p-8 md:col-span-2 relative transition-colors duration-300">
                        <img src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $product_title ); ?>" class="w-full h-full object-contain">
                    </div>
                    
                    <!-- Gallery Images -->
                    <?php if ( $gallery_image_ids ) : ?>
                        <?php foreach ( $gallery_image_ids as $attachment_id ) : 
                            $gallery_img_url = wp_get_attachment_image_url( $attachment_id, 'full' );
                        ?>
                        <div class="gsap-gallery-item tryl-sp-card tryl-sp-border aspect-square flex items-center justify-center p-4 transition-colors duration-300">
                            <img src="<?php echo esc_url( $gallery_img_url ); ?>" alt="Gallery Image" class="w-full h-full object-contain">
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Product Info -->
            <div class="gsap-product-info w-full lg:w-2/5 flex flex-col pt-4 lg:pt-0 sticky top-24 self-start">
                
                <h1 class="text-4xl sm:text-5xl font-extrabold uppercase font-oswald tryl-sp-text leading-none mb-4 transition-colors duration-300">
                    <?php echo esc_html( $product_title ); ?>
                </h1>
                
                <div class="text-xl font-medium tryl-sp-text mb-6 transition-colors duration-300">
                    <?php echo $product_price; ?>
                </div>
                
                <!-- Product Summary (Short Description) -->
                <?php if ( ! empty( $short_description ) ) : ?>
                <div class="prose prose-sm tryl-sp-muted mb-8 font-inter transition-colors duration-300">
                    <?php echo $short_description; ?>
                </div>
                <?php endif; ?>
                
                <!-- Product Form (Add to Cart / Variations) -->
                <div class="mb-6 w-full" id="options">
                    <?php woocommerce_template_single_add_to_cart(); ?>
                </div>

                <!-- Custom Buy Now Button -->
                <?php if ( ! $is_variable ) : ?>
                <a href="<?php echo esc_url( $buy_now_url ); ?>" class="block w-full text-center py-4 tryl-sp-btn-bg tryl-sp-btn-text font-bold uppercase tracking-widest text-sm hover:opacity-80 transition-colors mb-4 rounded-none shadow-md">
                    Buy It Now
                </a>
                <?php endif; ?>
                
                <!-- Full Product Info (Description) -->
                <?php if ( ! empty( $description ) ) : ?>
                <div class="mt-12 pt-8 border-t tryl-sp-border">
                    <h3 class="text-sm font-bold uppercase tracking-widest mb-4 tryl-sp-text">Product Details</h3>
                    <div class="prose prose-sm tryl-sp-muted font-inter transition-colors duration-300">
                        <?php echo $description; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<!-- Inject Tailwind & Fonts if not already loaded by theme -->
<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Oswald:wght@700&display=swap");
    .font-oswald { font-family: "Oswald", sans-serif; }
    .font-inter { font-family: "Inter", sans-serif; }
    
    /* Theme System Variables */
    :root, [data-theme="bright"] {
        --sp-bg: #fafafa;
        --sp-card: #ffffff;
        --sp-text: #000000;
        --sp-muted: #4b5563; /* text-gray-600 */
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

    .tryl-sp-bg { background-color: var(--sp-bg); }
    .tryl-sp-card { background-color: var(--sp-card); }
    .tryl-sp-text { color: var(--sp-text); }
    .tryl-sp-text-hover:hover { color: var(--sp-text); }
    .tryl-sp-muted { color: var(--sp-muted); }
    .tryl-sp-border { border-color: var(--sp-border); border-width: 1px; }
    .tryl-sp-btn-bg { background-color: var(--sp-btn-bg); }
    .tryl-sp-btn-text { color: var(--sp-btn-text); }
    
    .gsap-gallery-item, .gsap-product-info > * { will-change: transform, opacity; }

    /* Standardize Woo form inputs to match aesthetic */
    form.cart { margin-bottom: 1.5rem; }
    form.cart .variations select {
        width: 100%;
        padding: 14px;
        border: 1px solid var(--sp-border);
        background: var(--sp-card);
        color: var(--sp-text);
        margin-bottom: 16px;
        font-family: "Inter", sans-serif;
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
</style>
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