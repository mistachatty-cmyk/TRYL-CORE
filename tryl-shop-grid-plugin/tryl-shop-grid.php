<?php
/**
 * Plugin Name: TRYL 3D Shop Grid for SeedProd
 * Description: Adds a [tryl_3d_shop] shortcode that outputs the custom flat-minimalist shop grid with 3D image tilt. Also applies Figma-level aesthetics to the Woo Cart & Checkout.
 * Version: 1.3
 * Author: TRYL Developer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. The Shop Grid Shortcode
if ( ! function_exists( 'tryl_3d_shop_shortcode' ) ) {
function tryl_3d_shop_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>WooCommerce is required for this shop grid.</p>';
    }

    ob_start();
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Oswald:wght@700&display=swap");
        :root {
            --ry-bg-primary: #FAFAFA;
            --ry-text-main: #0F0F0F;
        }
        .tryl-wrapper { background-color: var(--ry-bg-primary); font-family: "Inter", sans-serif; }
        .font-oswald { font-family: "Oswald", sans-serif; }
        
        .flat-border { border: 1px solid #E5E7EB; transition: border-color 0.3s ease; }
        .flat-hover:hover { border-color: #000000; }
        
        .tilt-image-container {
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        .tilt-image {
            transform: translateZ(30px);
        }
    </style>

    <?php
    $args = array(
        'status' => 'publish',
        'limit' => 24, 
        'return' => 'objects',
    );
    $products = wc_get_products( $args );
    ?>

    <div class="tryl-wrapper py-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        <div class="mb-12 border-b-2 border-black pb-4 flex justify-between items-end">
            <h2 class="text-4xl font-extrabold tracking-tight text-black uppercase font-oswald">
                The Righteous Yield Collection
            </h2>
            <span class="text-sm font-medium text-gray-500 uppercase tracking-widest">
                <?php echo count($products); ?> Items
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-y-12 gap-x-8">
            <?php foreach ( $products as $product ) : 
                $product_url = get_permalink( $product->get_id() );
                
                // NEW: Force the button to instantly take them to the Checkout Page with the item!
                $checkout_page_url = wc_get_checkout_url();
                if ( $product->is_type( 'variable' ) ) {
                    $buy_now_url = $product_url;
                    $buy_btn_text = "Select Size";
                } else {
                    $buy_now_url = add_query_arg( 'add-to-cart', $product->get_id(), $checkout_page_url );
                    $buy_btn_text = "Buy Now";
                }

                $product_title = $product->get_name();
                $product_price = $product->get_price_html();
                $categories = wc_get_product_category_list( $product->get_id() );
                
                $image_id  = $product->get_image_id();
                $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                if ( ! $image_url ) {
                    $image_url = "https://images.unsplash.com/photo-1588850561407-ed78c282e89b?auto=format&fit=crop&w=500&q=80";
                }
            ?>

            <div class="group relative flex flex-col">
                <div class="tilt-image-container relative w-full aspect-square bg-white flat-border overflow-hidden flat-hover cursor-pointer" 
                     data-tilt data-tilt-max="15" data-tilt-speed="400" data-tilt-perspective="1000" data-tilt-glare data-tilt-max-glare="0.3">
                    
                    <a href="<?php echo esc_url($product_url); ?>" class="block w-full h-full">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product_title); ?>" class="tilt-image w-full h-full object-center object-contain p-6 transition-transform duration-500 group-hover:scale-105" />
                    </a>
                    
                    <div class="absolute inset-x-0 bottom-0 bg-black translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out" style="transform: translateZ(40px); z-index: 50;">
                        <!-- The Buy Now Button -->
                        <a href="<?php echo esc_url($buy_now_url); ?>" class="block w-full py-4 text-center text-sm font-bold text-white uppercase tracking-widest hover:bg-gray-800">
                            <?php echo esc_html($buy_btn_text); ?>
                        </a>
                    </div>
                </div>

                <div class="mt-4 flex flex-col justify-between flex-1">
                    <div>
                        <h3 class="text-sm font-bold text-black uppercase tracking-wide">
                            <a href="<?php echo esc_url($product_url); ?>">
                                <?php echo esc_html($product_title); ?>
                            </a>
                        </h3>
                        <p class="mt-1 text-xs text-gray-500 font-mono stripped-cats">
                            <?php echo wp_strip_all_tags($categories); ?>
                        </p>
                    </div>
                    <div class="mt-2 text-sm font-medium text-black">
                        <?php echo $product_price; ?>
                    </div>
                </div>
            </div>

            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        if(typeof VanillaTilt !== 'undefined') {
            VanillaTilt.init(document.querySelectorAll(".tilt-image-container"));
        }
    </script>
    <?php
    return ob_get_clean();
}
} // endif function_exists( 'tryl_3d_shop_shortcode' )
if ( ! shortcode_exists( 'tryl_3d_shop' ) ) {
    add_shortcode( 'tryl_3d_shop', 'tryl_3d_shop_shortcode' );
}

// 2. Figma-Level Aesthetics for WooCommerce Cart & Checkout Pages
function tryl_beautify_woo_checkout() {
    if ( is_cart() || is_checkout() ) {
        ?>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Oswald:wght@700&display=swap");
            
            body { background-color: #FAFAFA !important; }
            .woocommerce {
                font-family: 'Inter', sans-serif !important;
                max-width: 1100px;
                margin: 60px auto;
                padding: 0 20px;
                color: #111;
            }
            .woocommerce h2, .woocommerce h3, .woocommerce h1 {
                font-family: 'Oswald', sans-serif !important;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border: none !important;
                font-size: 28px;
                margin-bottom: 20px;
            }
            
            /* Cart Layout Nike Style */
            .woocommerce-cart .woocommerce {
                display: flex;
                flex-wrap: wrap;
                gap: 40px;
            }
            .woocommerce-cart .woocommerce-notices-wrapper { width: 100%; }
            .woocommerce-cart .woocommerce-cart-form {
                flex: 1 1 60%;
            }
            .woocommerce-cart .cart-collaterals {
                flex: 1 1 30%;
            }
            
            /* Table Aesthetics */
            .woocommerce table.shop_table {
                border-collapse: collapse;
                border: none !important;
                border-radius: 0 !important;
                margin-bottom: 30px;
            }
            .woocommerce table.shop_table th {
                display: none; /* Hide headers for cleaner look */
            }
            .woocommerce table.shop_table td {
                border: none;
                border-bottom: 1px solid #E5E7EB;
                padding: 24px 0 !important;
                background: transparent !important;
            }
            .woocommerce table.shop_table .product-thumbnail img {
                width: 120px;
                height: 120px;
                object-fit: contain;
                border: 1px solid #E5E7EB;
                background: #FFF;
            }
            .woocommerce table.shop_table .product-name a {
                color: #111 !important;
                font-weight: 700;
                font-size: 16px;
                text-transform: uppercase;
                text-decoration: none !important;
            }
            .woocommerce table.shop_table .product-price, 
            .woocommerce table.shop_table .product-subtotal {
                font-weight: 500;
                color: #111;
            }
            .woocommerce table.shop_table .product-remove a {
                color: #999 !important;
                font-size: 24px;
                font-weight: 300;
            }
            .woocommerce table.shop_table .product-remove a:hover {
                color: #000 !important;
                background: transparent !important;
            }
            
            /* Inputs & Quantity */
            .woocommerce .quantity input.qty {
                width: 60px;
                height: 40px;
                border: 1px solid #E5E7EB;
                border-radius: 0;
                font-family: 'Inter', sans-serif;
                background: #FFF;
            }
            .woocommerce-cart .actions {
                padding: 20px 0 !important;
                border: none !important;
            }
            .woocommerce .coupon input.input-text {
                height: 50px;
                border: 1px solid #E5E7EB !important;
                border-radius: 0 !important;
                padding: 0 15px !important;
                width: 200px;
                background: #FFF;
            }
            
            /* Cart Totals Box */
            .cart-collaterals .cart_totals {
                width: 100% !important;
                background: #FFF;
                border: 1px solid #E5E7EB;
                padding: 30px;
            }
            .cart-collaterals .cart_totals h2 {
                font-size: 24px;
                margin-bottom: 20px;
            }
            .cart-collaterals table.shop_table {
                border-bottom: none !important;
            }
            .cart-collaterals table.shop_table th {
                display: table-cell;
                font-family: 'Inter', sans-serif !important;
                text-transform: none;
                font-weight: 500;
                color: #555;
            }
            .cart-collaterals table.shop_table td {
                text-align: right;
                font-weight: 700;
                border-bottom: 1px solid #E5E7EB !important;
            }
            
            /* Primary Buttons */
            .woocommerce button.button, 
            .woocommerce a.checkout-button,
            .woocommerce input.button {
                background-color: #111 !important;
                color: #fff !important;
                border-radius: 30px !important;
                font-family: 'Inter', sans-serif !important;
                font-weight: 700 !important;
                text-transform: none !important;
                font-size: 16px !important;
                padding: 16px 30px !important;
                border: none !important;
                transition: background 0.3s ease !important;
                width: 100%;
                text-align: center;
                display: block;
                margin-top: 10px;
            }
            .woocommerce button.button[name="update_cart"] {
                background-color: #FFF !important;
                color: #111 !important;
                border: 1px solid #E5E7EB !important;
                width: auto;
                display: inline-block;
            }
            .woocommerce button.button:hover,
            .woocommerce a.checkout-button:hover {
                background-color: #333 !important;
            }
            
            /* Checkout specific */
            .woocommerce-checkout .woocommerce-input-wrapper input,
            .woocommerce-checkout .woocommerce-input-wrapper select,
            .woocommerce-checkout .woocommerce-input-wrapper textarea {
                border: 1px solid #E5E7EB !important;
                padding: 14px 15px !important;
                background: #FFF !important;
                border-radius: 4px !important;
                width: 100%;
            }
            .woocommerce-checkout .woocommerce-input-wrapper input:focus {
                border-color: #111 !important;
                outline: none !important;
            }
            #payment .payment_methods li {
                background: #FFF;
                border: 1px solid #E5E7EB;
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 10px;
            }
        </style>
        <?php
    }
}
add_action( 'wp_head', 'tryl_beautify_woo_checkout' );

