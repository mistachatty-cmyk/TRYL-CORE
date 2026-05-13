<?php
/**
 * Template Name: Righteous Yield Master Grid
 * Description: A custom, high-speed shop grid tailored for flat, technical minimalism and 3D product mockups.
 * 
 * CHANGELOG / RECENT ADJUSTMENTS:
 * - Graceful fallback if WooCommerce is deactivated to prevent fatal errors.
 * - Accessible semantic HTML `<a>` links instead of inline JS wrappers.
 * - Image fallback to WooCommerce placeholder if product has no featured/gallery image.
 * - Custom direct-to-checkout logic for "Buy Now" button.
 */

// Inject Tailwind CSS and Custom Fonts for the frontend styling
add_action('wp_head', function() {
    echo '<script src="https://cdn.tailwindcss.com"></script>';
    echo '<style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&family=Oswald:wght@700&display=swap");
        :root {
            --ry-bg-primary: #FAFAFA;
            --ry-bg-secondary: #FFFFFF;
            --ry-text-main: #0F0F0F;
        }
        .font-oswald { font-family: "Oswald", sans-serif; }
        .font-inter { font-family: "Inter", sans-serif; }
        
        /* Flat UI overrides */
        .flat-border { border: 1px solid #E5E7EB; }
        .flat-hover:hover { border-color: #000000; }
    </style>';
});

get_header(); 

// Gracefully handle missing WooCommerce plugin
if ( ! class_exists( 'WooCommerce' ) ) {
    echo '<div class="py-16 px-4 text-center font-inter"><p>WooCommerce is required to display this collection.</p></div>';
    get_footer();
    exit;
}

// Fetch up to 24 products from the catalog safely via PHP
$args = array(
    'status' => 'publish',
    'limit' => 24, 
    'return' => 'objects',
);
$products = wc_get_products( $args );
?>

<section class="bg-[#FAFAFA] py-16 px-4 sm:px-6 lg:px-8 font-inter min-h-screen">
    <div class="max-w-7xl mx-auto">
        
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
                
                // Get the product URL, Title, and Price
                $product_url = get_permalink( $product->get_id() );
                $product_title = $product->get_name();
                $product_price = $product->get_price_html();
                
                // Logic to grab the 3D Printful Mockup
                $gallery_image_ids = $product->get_gallery_image_ids();
                $featured_image_url = get_the_post_thumbnail_url( $product->get_id(), 'large' );
                
                // Prioritize the first gallery image (3D mockup), fallback to featured flat image
                $display_image = !empty($gallery_image_ids) ? wp_get_attachment_url( $gallery_image_ids[0] ) : $featured_image_url;
                
                // Fallback to placeholder if no images exist
                if ( empty( $display_image ) ) {
                    $display_image = wc_placeholder_img_src();
                }
                
                // Get Product Categories
                $categories = wc_get_product_category_list( $product->get_id() );
                
                // Direct to checkout "Buy Now" button logic
                $checkout_page_url = wc_get_checkout_url();
                if ( $product->is_type( 'variable' ) ) {
                    $buy_now_url = $product_url;
                    $buy_btn_text = "Select Size";
                } else {
                    $buy_now_url = add_query_arg( 'add-to-cart', $product->get_id(), $checkout_page_url );
                    $buy_btn_text = "Buy Now";
                }
            ?>

            <div class="gsap-product-card group relative flex flex-col">
                
                <div class="relative w-full aspect-square bg-white flat-border overflow-hidden transition-colors duration-300 flat-hover">
                    <a href="<?php echo esc_url($product_url); ?>" class="block w-full h-full relative z-0">
                        <img
                            src="<?php echo esc_url($display_image); ?>"
                            alt="<?php echo esc_attr($product_title); ?>"
                            class="w-full h-full object-center object-contain p-6 transition-transform duration-500 group-hover:scale-105"
                        />
                    </a>
                    
                    <div class="absolute inset-x-0 bottom-0 bg-black translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out z-10">
                        <a href="<?php echo esc_url($buy_now_url); ?>" class="block text-center w-full py-4 text-sm font-bold text-white uppercase tracking-widest hover:bg-gray-800">
                            <?php echo esc_html($buy_btn_text); ?>
                        </a>
                    </div>
                </div>

                <div class="mt-4 flex flex-col justify-between flex-1 relative z-10">
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
</section>

<script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        if (typeof gsap !== "undefined") {
            gsap.fromTo(".gsap-product-card", 
                { opacity: 0, y: 40 }, 
                { opacity: 1, y: 0, duration: 0.6, stagger: 0.05, ease: "power2.out" }
            );
        }
    });
</script>

<?php get_footer(); ?>
