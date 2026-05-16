<?php
/**
 * Plugin Name: TRYL Premium E-Commerce Core Universal
 * Description: All-in-one TRYL shop engine. Nike-inspired product pages, premium cart/checkout, and global nav enhancement.
 * Version: 3.3
 * Author: EHDesigns | Powered by LokServices
 * 
 * CHANGELOG:
 * 3.2 - Added intuitive Dev Dashboard for settings management.
 * 3.1 - Added single product template override, AJAX single-product add-to-cart, and conditional mini-cart loading logic.
 * 3.0 - Initial consolidation of premium cart, checkout, and shop grid features.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ─── ASSET ENQUEUING (Optimization) ──────────────────────────────────────────
function tryl_core_enqueue_assets() {
    $plugin_url = plugin_dir_url( __FILE__ );
    wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', [], null, true );
    wp_enqueue_style( 'tryl-core-css', $plugin_url . 'assets/css/tryl-core.css', [], '3.4.0' );
    wp_enqueue_script( 'tryl-core-js', $plugin_url . 'assets/js/tryl-core.js', ['gsap'], '3.4.0', true );
    
    wp_localize_script( 'tryl-core-js', 'trylCoreSettings', [
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'btnText' => get_option('tryl_atc_btn_text', 'Added!'),
        'checkoutAnimations' => get_option('tryl_checkout_animations', '1'),
        'isCartOrCheckout' => (function_exists('is_cart') && (is_cart() || is_checkout())) ? '1' : '0'
    ]);
}
add_action( 'wp_enqueue_scripts', 'tryl_core_enqueue_assets' );

// ─── 0. GLOBAL TYPOGRAPHY SYSTEM ──────────────────────────────────────────────
function tryl_global_fonts() {
    $pack = get_option('tryl_font_pack', 'default');
    $fonts_url = 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@300;400;500;600;700&display=swap';
    $h_font = "'Barlow Condensed', sans-serif";
    $b_font = "'Inter', sans-serif";
    
    if ($pack === 'editorial') {
        $fonts_url = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600;700&display=swap';
        $h_font = "'Cormorant Garamond', serif";
    } elseif ($pack === 'technical') {
        $fonts_url = 'https://fonts.googleapis.com/css2?family=Oswald:wght@500;700&family=Roboto:wght@300;400;500;700&display=swap';
        $h_font = "'Oswald', sans-serif";
        $b_font = "'Roboto', sans-serif";
    } elseif ($pack === 'minimalist') {
        $fonts_url = 'https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@300;400;500;600&display=swap';
        $h_font = "'Montserrat', sans-serif";
        $b_font = "'Open Sans', sans-serif";
    }
    
    $mobile_align = get_option('tryl_mobile_menu_align', 'left');
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="' . esc_url($fonts_url) . '" rel="stylesheet">';
    echo '<style>:root { --tryl-header-font: ' . $h_font . '; --tryl-body-font: ' . $b_font . '; --tryl-mobile-align: ' . esc_attr($mobile_align) . '; }</style>';
}
add_action('wp_head', 'tryl_global_fonts', 1);

// ─── 1. SINGLE PRODUCT & CHECKOUT TEMPLATE OVERRIDES ─────────────────────────

/**
 * Force our custom templates for single products and checkout form.
 * Using woocommerce_locate_template is more reliable than template_include 
 * for WooCommerce-specific files.
 */
function tryl_custom_woocommerce_templates( $template, $template_name, $template_path ) {
    $plugin_path = plugin_dir_path( __FILE__ );
    $premium_active = get_option('tryl_premium_products_active', '1');
    $single_product_path = $plugin_path . 'templates/single-product.php';
    $checkout_form_path = $plugin_path . 'templates/checkout/form-checkout.php';
    $thankyou_path = $plugin_path . 'templates/checkout/thankyou.php';

    // Single Product Override
    if ( $template_name === 'single-product.php' ) {
        if ( $premium_active === '1' && file_exists( $single_product_path ) ) {
            return $single_product_path;
        }
    }

    // Checkout Form Override
    if ( $template_name === 'checkout/form-checkout.php' ) {
        if ( get_option('tryl_nike_checkout_active', '1') === '1' && file_exists( $checkout_form_path ) ) {
            return $checkout_form_path;
        }
    }

    // Order Received (Thank You) Page Override
    if ( $template_name === 'checkout/thankyou.php' ) {
        if ( get_option('tryl_nike_checkout_active', '1') === '1' && file_exists( $thankyou_path ) ) {
            return $thankyou_path;
        }
    }

    return $template;
}
add_filter( 'woocommerce_locate_template', 'tryl_custom_woocommerce_templates', 999, 3 );

/**
 * Custom Checkout URL Filter
 * Ensures WooCommerce uses the user-defined checkout URL.
 */
function tryl_custom_checkout_url( $url ) {
    $custom_url = get_option('tryl_nav_checkout');
    if ( ! empty( $custom_url ) ) {
        return $custom_url;
    }
    return $url;
}
add_filter( 'woocommerce_get_checkout_url', 'tryl_custom_checkout_url', 999 );

/**
 * Universal Template Override
 * Intercepts WordPress's routing to forcefully apply our premium templates.
 */
function tryl_universal_template_overrides( $template ) {
    $plugin_path = plugin_dir_path( __FILE__ );
    
    $is_singular_product = is_singular( 'product' );
    $is_product_func = function_exists('is_product') && is_product();
    $is_shop_func = function_exists('is_shop');
    $is_shop = $is_shop_func && is_shop();
    $is_product_category = is_product_category();
    $is_product_tag = is_product_tag();
    $is_404 = is_404();
    $premium_active = get_option('tryl_premium_products_active', '1');
    $custom_404_active = get_option('tryl_custom_404_active', '1');
    
    // 1. Single Product Override
    if ( $is_singular_product || $is_product_func ) {
        if ( $premium_active === '1' && file_exists( $plugin_path . 'templates/single-product.php' ) ) {
            return $plugin_path . 'templates/single-product.php';
        }
    }
    
    // 2. Master Shop Page Override (Fixes "Shop acting like a blog" issue)
    if ( $is_shop_func && ( $is_shop || $is_product_category || $is_product_tag ) ) {
        // Prioritize Theme/Child Theme template if it exists
        $t = locate_template( 'page-righteous-shop.php' );
        if ( ! $t ) {
            $t = $plugin_path . 'templates/page-righteous-shop.php';
        }
        
        if ( file_exists( $t ) ) {
            return $t;
        }
    }
    
    // 3. 404 Error Page Override
    if ( $is_404 && $custom_404_active === '1' ) {
        $t = $plugin_path . 'templates/404.php';
        if ( file_exists( $t ) ) {
            return $t;
        }
    }
    
    return $template;
}
add_filter( 'template_include', 'tryl_universal_template_overrides', 999999 );

// Add body class for premium product pages
add_filter('body_class', function($classes) {
    if ( (is_singular('product') || (function_exists('is_product') && is_product())) && get_option('tryl_premium_products_active', '1') === '1' ) {
        $classes[] = 'tryl-premium-product-page';
    }
    return $classes;
});

// Ensure WooCommerce uses the standard template structure for checkout/cart
add_filter( 'woocommerce_checkout_use_block', '__return_false' );
add_filter( 'woocommerce_cart_use_block', '__return_false' );

// Safely intercept Gutenberg Cart & Checkout blocks and force classic shortcodes
add_filter( 'render_block', function( $block_content, $block ) {
    if ( $block['blockName'] === 'woocommerce/checkout' && get_option('tryl_nike_checkout_active', '1') === '1' ) {
        if ( function_exists('is_wc_endpoint_url') && empty( is_wc_endpoint_url('order-pay') ) && empty( is_wc_endpoint_url('order-received') ) ) {
            return do_shortcode( '[woocommerce_checkout]' );
        }
    }
    if ( $block['blockName'] === 'woocommerce/cart' ) {
        return do_shortcode( '[woocommerce_cart]' );
    }
    return $block_content;
}, 10, 2 );

// ─── 2. SHOP GRID SHORTCODE ───────────────────────────────────────────────────
if ( ! function_exists( 'tryl_get_core_product_card_html' ) ) {
function tryl_get_core_product_card_html( $product ) {
    $pid      = $product->get_id();
    $purl     = get_permalink($pid);
    $is_var   = $product->is_type('variable');
    $is_in_stock = $product->is_in_stock();
    $buy_url  = $is_var ? $purl : add_query_arg('add-to-cart',$pid,wc_get_checkout_url());
    $btn_txt  = $is_var ? 'Choose Size' : 'Buy Now';
    if ( ! $is_in_stock ) {
        $btn_txt = 'Sold Out';
        $buy_url = $purl;
    }
    $img      = wp_get_attachment_image_url($product->get_image_id(),'full') ?: 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=500';
    $cats     = wp_get_post_terms($pid,'product_cat',['fields'=>'slugs']);
    $cat_cls  = !is_wp_error($cats)&&!empty($cats) ? 'cat-'.implode(' cat-',$cats) : '';
    
    $available_variations = [];
    $swatches = [];
    if ( $is_var && $is_in_stock ) {
        $available_variations = $product->get_available_variations();
        foreach ( $available_variations as $var ) {
            if ( ! $var['is_in_stock'] || ! $var['is_purchasable'] ) continue;
            foreach ( $var['attributes'] as $attr_name => $attr_val ) {
                if ( strpos( strtolower($attr_name), 'color' ) !== false && ! empty($attr_val) ) {
                    if ( ! isset( $swatches[$attr_val] ) ) {
                        $term = get_term_by('slug', $attr_val, str_replace('attribute_', '', $attr_name));
                        $name = $term ? $term->name : ucfirst(str_replace('-', ' ', $attr_val));
                        $hex = $term ? get_term_meta( $term->term_id, 'color', true ) : '';
                        if ( empty($hex) ) $hex = $attr_val;
                        $swatch_img = isset($var['image']['src']) && !empty($var['image']['src']) ? $var['image']['src'] : $img;
                        $swatches[$attr_val] = [
                            'name' => $name,
                            'hex' => $hex,
                            'img' => $swatch_img
                        ];
                    }
                }
            }
        }
    }

    $badge_text = '';
    $is_dynamic_badge = false;
    $b_class = 'tryl-badge';
    if ( ! $is_in_stock ) {
        $badge_text = 'Sold Out';
        $b_class .= ' sold-out';
    } else {
        $badges_active = get_option('tryl_badges_active');
        if ( $badges_active ) {
            $total_sales = (int) get_post_meta( $pid, 'total_sales', true );
            $created_date = $product->get_date_created() ? $product->get_date_created()->getTimestamp() : 0;
            $days_old = ( current_time('timestamp') - $created_date ) / DAY_IN_SECONDS;
            $badges_bestseller_sales = (int) get_option('tryl_badges_bestseller_sales', 50);
            $badges_new_days = (int) get_option('tryl_badges_new_days', 14);
            if ( $total_sales >= $badges_bestseller_sales ) { $badge_text = 'Bestseller'; $is_dynamic_badge = true; }
            elseif ( $created_date && $days_old <= $badges_new_days ) { $badge_text = 'New Drop'; $is_dynamic_badge = true; }
        }
        if ( ! $badge_text && $product->is_on_sale() ) $badge_text = 'Sale';
    }
    if ( $is_dynamic_badge ) $b_class .= ' dynamic-badge';
    
    $badges_bg = get_option('tryl_badges_bg', '#31d190');
    $badges_text_color = get_option('tryl_badges_text_color', '#0d1b0f');
    $inline_badge_style = $is_dynamic_badge ? 'background: ' . esc_attr($badges_bg) . ' !important; color: ' . esc_attr($badges_text_color) . ' !important;' : '';

    ob_start();
    ?>
    <div class="tryl-card <?php echo esc_attr($cat_cls);?>" data-item>
      <div class="tryl-card-img" data-tilt data-tilt-max="8" data-tilt-speed="400" data-tilt-glare data-tilt-max-glare="0.15">
        <?php if ( $badge_text ) : ?>
        <div class="<?php echo esc_attr($b_class); ?>" style="<?php echo $inline_badge_style; ?>"><?php echo esc_html($badge_text); ?></div>
        <?php endif; ?>
        <img src="<?php echo esc_url($img);?>" alt="<?php echo esc_attr($product->get_name());?>" loading="lazy">
        <div class="tryl-card-overlay">
          <a href="<?php echo esc_url($buy_url);?>" class="tryl-card-buy"><?php echo esc_html($btn_txt);?></a>
          <a href="<?php echo esc_url($purl);?>" class="tryl-card-view">View Details</a>
        </div>
      </div>
      <div class="tryl-card-info">
        <div class="tryl-card-name"><a href="<?php echo esc_url($purl);?>"><?php echo esc_html($product->get_name());?></a></div>
        <div class="tryl-card-cat"><?php echo wp_strip_all_tags(wc_get_product_category_list($pid));?></div>
        
        <?php if ( ! empty($swatches) ) : ?>
        <div class="tryl-card-swatches" style="display:flex; gap:6px; margin-bottom:12px;">
            <?php foreach ( $swatches as $swatch ) : 
                $bg = strtolower($swatch['hex']);
                $css_colors = ['black'=>'#000','white'=>'#fff','red'=>'#d63638','blue'=>'#2271b1','green'=>'#007017','yellow'=>'#f0b849','navy'=>'#000080','gray'=>'#8c8f94','grey'=>'#8c8f94','pink'=>'#e51573','purple'=>'#8224e3','orange'=>'#d94f4f','tan'=>'#d2b48c','olive'=>'#808000','brown'=>'#a52a2a'];
                if ( isset($css_colors[$bg]) ) $bg = $css_colors[$bg];
            ?>
            <div class="tryl-swatch" data-img="<?php echo esc_url($swatch['img']); ?>" title="<?php echo esc_attr($swatch['name']); ?>" style="width:16px; height:16px; border-radius:50%; border:1px solid var(--border); background:<?php echo esc_attr($bg); ?>; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s;"></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tryl-card-footer-actions">
          <div class="tryl-card-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
        <?php if ( ! $is_in_stock ) : ?>
        <button class="tryl-atc disabled" disabled style="opacity:0.5;cursor:not-allowed;">
          <span>Out of Stock</span>
        </button>
        <?php elseif($is_var): ?>
        <div class="tryl-inline-var-wrapper" style="position:relative;">
          <button class="tryl-atc tryl-atc-choose tryl-atc-inline-toggle" type="button">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Select Size</span>
          </button>
          <div class="tryl-inline-var-dropdown" style="display:none; position:absolute; bottom:calc(100% + 8px); right:0; background:var(--card-bg); border:1px solid var(--border); box-shadow:0 10px 30px rgba(0,0,0,0.1); z-index:100; padding:8px; border-radius:4px; min-width:140px; animation: trylFadeIn 0.2s ease-out forwards;">
              <div style="font-size:0.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid var(--border); text-align:center;">Select Option</div>
              <div style="display:flex; flex-direction:column; gap:4px; max-height:200px; overflow-y:auto;">
              <?php 
              $has_in_stock = false;
              foreach ( $available_variations as $var ) : 
                  if ( ! $var['is_in_stock'] || ! $var['is_purchasable'] ) continue;
                  $has_in_stock = true;
                  $attr_vals = [];
                  foreach ($var['attributes'] as $k => $v) {
                      $term = get_term_by('slug', $v, str_replace('attribute_', '', $k));
                      $attr_vals[] = $term ? $term->name : ucfirst($v);
                  }
                  $label = implode(' / ', $attr_vals) ?: 'Option';
              ?>
                  <button class="tryl-atc-variation" data-pid="<?php echo $pid; ?>" data-vid="<?php echo $var['variation_id']; ?>" type="button" style="width:100%; text-align:left; padding:8px 12px; background:var(--off); border:1px solid var(--border); cursor:pointer; font-family:var(--tryl-body-font); font-size:0.75rem; font-weight:600; text-transform:uppercase; color:var(--txt); transition:all 0.2s; border-radius:4px;">
                      <?php echo esc_html( $label ); ?>
                  </button>
              <?php endforeach; 
              if ( ! $has_in_stock ) echo '<div style="font-size:0.7rem; color:var(--muted); text-align:center; padding:8px;">Sold Out</div>';
              ?>
              </div>
          </div>
        </div>
          <?php else: ?>
          <button class="tryl-atc" data-pid="<?php echo $pid;?>">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Add to Cart</span>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
}

if ( ! function_exists( 'tryl_core_3d_shop_shortcode' ) ) {
function tryl_core_3d_shop_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) return '<p>WooCommerce required.</p>';
    ob_start();
    $limit = get_option('tryl_shop_grid_limit', 32);
    $signature = get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices');
    $badges_active = get_option('tryl_badges_active');
    $badges_new_days = (int) get_option('tryl_badges_new_days', 14);
    $badges_bestseller_sales = (int) get_option('tryl_badges_bestseller_sales', 50);
    $badges_bg = get_option('tryl_badges_bg', '#31d190');
    $badges_text_color = get_option('tryl_badges_text_color', '#0d1b0f');
    $query = wc_get_products( [ 'status' => 'publish', 'limit' => $limit, 'page' => 1, 'paginate' => true, 'return' => 'objects' ] );
    $products = $query->products;
    $max_pages = $query->max_num_pages;
    $total_products = $query->total;
    $all_cats = [];
    $terms = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => true ] );
    if ( ! is_wp_error( $terms ) ) foreach ( $terms as $t ) $all_cats[ $t->slug ] = $t->name;
    ?>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
    :root, [data-theme="bright"] {
        --sage: #e8f0e8;
        --green: #2d6a4f;
        --dark: #0d1b0f;
        --off: #f5f8f5;
        --accent: #31d190;
        --txt: #1a2e1a;
        --muted: #6b7c6b;
        --border: #d4e0d4;
        --btn-txt: #fff;
        --card-bg: #fff;
    }
    [data-theme="mild"] {
        --sage: #d6d3cc;
        --green: #686358;
        --dark: #33322e;
        --off: #e6e4df;
        --accent: #a39e93;
        --txt: #33322e;
        --muted: #858178;
        --border: #c4c0b5;
        --btn-txt: #fff;
        --card-bg: #f2f0eb;
    }
    [data-theme="dark"] {
        --sage: #1a2e1a;
        --green: #31d190;
        --dark: #f5f8f5;
        --off: #0d1b0f;
        --accent: #31d190;
        --txt: #f5f8f5;
        --muted: #8a9c8a;
        --border: #2d6a4f;
        --btn-txt: #0d1b0f;
        --card-bg: #132615;
    }
    .tryl-shop{background:var(--off);font-family:var(--tryl-body-font);padding:48px 0 80px;}
    .tryl-shop-inner{max-width:1280px;margin:0 auto;padding:0 32px;}
    .tryl-shop-header{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:20px;padding-bottom:28px;border-bottom:2px solid var(--dark);margin-bottom:40px;}
    .tryl-shop-title{font-family:var(--tryl-header-font);font-weight:900;font-size:2.8rem;text-transform:uppercase;color:var(--dark);line-height:1;}
    .tryl-shop-count{font-size:.7rem;font-weight:600;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);}
    .tryl-filters{display:flex;flex-wrap:wrap;gap:8px;}
    .tryl-filter{padding:8px 20px;font-size:.68rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;border:1.5px solid var(--border);background:transparent;color:var(--txt);cursor:pointer;transition:all .2s;}
    .tryl-filter:hover{border-color:var(--dark);}
    .tryl-filter.active{background:var(--dark);color:var(--btn-txt);border-color:var(--dark);}
    .tryl-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2px;}
    @media(max-width:1100px){.tryl-grid{grid-template-columns:repeat(3,1fr);}}
    @media(max-width:700px){.tryl-grid{grid-template-columns:repeat(2,1fr);}}
    @media(max-width:460px){.tryl-grid{grid-template-columns:1fr;}}
    .tryl-card{background:var(--card-bg);border:1px solid var(--border);display:flex;flex-direction:column;transition:transform .3s,box-shadow .3s;position:relative;will-change:transform,opacity,box-shadow;}
    .tryl-card:hover{transform:translateY(-5px);box-shadow:0 20px 50px rgba(0,0,0,.09);}
    .tryl-card-img{aspect-ratio:1;overflow:hidden;padding:28px;display:flex;align-items:center;justify-content:center;position:relative;cursor:pointer;}
    .tryl-card-img img{width:100%;height:100%;object-fit:contain;transition:transform .5s cubic-bezier(.25,.46,.45,.94);will-change:transform;}
    .tryl-card:hover .tryl-card-img img{transform:scale(1.06);}
    .tryl-card-overlay{position:absolute;inset:0;background:rgba(13,27,15,.88);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;opacity:0;transition:opacity .25s;will-change:opacity;}
    .tryl-card:hover .tryl-card-overlay{opacity:1;}
    .tryl-card-buy,.tryl-card-view{width:80%;padding:12px;font-size:.68rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;text-align:center;border:none;cursor:pointer;transition:all .2s;}
    .tryl-card-buy{background:var(--accent);color:var(--dark);}
    .tryl-card-buy:hover{background:var(--card-bg);color:var(--dark);}
    .tryl-card-view{background:transparent;color:#fff;border:1.5px solid rgba(255,255,255,.4);}
    .tryl-card-view:hover{border-color:#fff;}
    .tryl-card-info{padding:16px 18px 20px;border-top:1px solid var(--border);}
    .tryl-card-name{font-family:var(--tryl-header-font);font-weight:700;font-size:1.05rem;text-transform:uppercase;color:var(--dark);margin-bottom:4px;}
    .tryl-card-name a{color:inherit;}
    .tryl-card-cat{font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);margin-bottom:8px;}
    .tryl-card-price{font-size:.9rem;font-weight:600;color:var(--green);}
    .tryl-card-footer-actions{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:10px;padding-top:10px;border-top:1px solid var(--border);}
    .tryl-atc{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;font-size:.62rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;background:var(--dark);color:var(--btn-txt);border:none;cursor:pointer;transition:all .25s;text-decoration:none;white-space:nowrap;line-height:1;}
    .tryl-atc:hover{background:var(--green);}
    .tryl-atc-choose{background:transparent;color:var(--dark);border:1.5px solid var(--dark);}
    .tryl-atc-choose:hover{background:var(--dark);color:var(--btn-txt);}
    .tryl-atc.loading{opacity:.5;pointer-events:none;}
    .tryl-atc.added{background:var(--accent);color:var(--dark);}
    .tryl-powered{text-align:center;padding:48px 0 0;font-size:.65rem;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--muted);}
    .tryl-atc-variation:hover{background:var(--dark)!important;color:var(--btn-txt)!important;border-color:var(--dark)!important;}
    .tryl-badge { position: absolute; top: 14px; left: 14px; background: var(--dark); color: var(--btn-txt); font-size: .6rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; padding: 6px 12px; z-index: 10; pointer-events: none; }
    <?php if ( $badges_active ) : ?>
    .tryl-badge.dynamic-badge { background: <?php echo esc_attr($badges_bg); ?> !important; color: <?php echo esc_attr($badges_text_color); ?> !important; }
    <?php endif; ?>
    .tryl-badge.sold-out { background: #d63638 !important; color: #fff !important; }
    </style>
    <div class="tryl-shop">
      <div class="tryl-shop-inner">
        <div class="tryl-shop-header">
          <div>
            <div class="tryl-shop-title">The Collection</div>
            <div class="tryl-shop-count"><?php echo $total_products; ?> Items Available</div>
          </div>
          <div class="tryl-filters">
            <button class="tryl-filter active" data-filter="all">All</button>
            <?php foreach($all_cats as $slug=>$name): ?>
            <button class="tryl-filter" data-filter="cat-<?php echo esc_attr($slug);?>"><?php echo esc_html($name);?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="tryl-grid" id="trylGrid">
              <?php 
              foreach($products as $product) {
                  echo tryl_get_core_product_card_html($product);
              }
              ?>
        </div>
            <?php if ( $max_pages > 1 ) : ?>
            <div class="tryl-load-more-wrap" style="text-align:center; margin-top:48px;">
                <button id="trylLoadMoreBtn" class="tryl-atc" data-page="1" data-max="<?php echo esc_attr($max_pages); ?>" style="padding: 16px 32px; font-size: 0.85rem;">
                    Load More Products
                </button>
            </div>
            <?php endif; ?>
        <div class="tryl-powered"><?php echo wp_kses_post($signature); ?></div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
      if(typeof VanillaTilt!=='undefined') VanillaTilt.init(document.querySelectorAll('[data-tilt]'));
          
          window.trylApplyGridFilter = function() {
              const activeBtn = document.querySelector('.tryl-filter.active');
              if (!activeBtn) return;
              const f = activeBtn.dataset.filter;
              document.querySelectorAll('[data-item]').forEach(c=>{
                c.style.display=(f==='all'||c.classList.contains(f))?'flex':'none';
              });
          };
          
      document.querySelectorAll('.tryl-filter').forEach(btn=>{
        btn.addEventListener('click',()=>{
          document.querySelectorAll('.tryl-filter').forEach(b=>b.classList.remove('active'));
          btn.classList.add('active');
              window.trylApplyGridFilter();
        });
      });
          
          function bindSwatches() {
              document.querySelectorAll('.tryl-swatch:not(.bound)').forEach(sw => {
                sw.classList.add('bound');
                sw.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var imgUrl = this.dataset.img;
                    if (imgUrl) {
                        var card = this.closest('.tryl-card');
                        var cardImg = card.querySelector('.tryl-card-img img');
                        if (cardImg) {
                            cardImg.src = imgUrl;
                            var swatches = card.querySelectorAll('.tryl-swatch');
                            swatches.forEach(s => { s.style.transform = 'scale(1)'; s.style.boxShadow = 'none'; });
                            this.style.transform = 'scale(1.2)';
                            this.style.boxShadow = '0 0 0 1.5px var(--dark)';
                        }
                    }
                });
              });
          }
          bindSwatches();
          
          var loadMoreBtn = document.getElementById('trylLoadMoreBtn');
          if (loadMoreBtn) {
              loadMoreBtn.addEventListener('click', function() {
                  var btn = this;
                  var page = parseInt(btn.getAttribute('data-page')) + 1;
                  var max = parseInt(btn.getAttribute('data-max'));
                  
                  var ogText = btn.textContent;
                  btn.textContent = 'Loading...';
                  btn.style.opacity = '0.5';
                  btn.style.pointerEvents = 'none';
    
                  var fd = new FormData();
                  fd.append('action', 'tryl_load_more_core_grid');
                  fd.append('page', page);
    
                  fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: fd })
                  .then(res => res.json())
                  .then(res => {
                      if (res.success) {
                          var grid = document.getElementById('trylGrid');
                          grid.insertAdjacentHTML('beforeend', res.data.html);
                          
                          if(typeof VanillaTilt!=='undefined') {
                              VanillaTilt.init(grid.querySelectorAll('[data-tilt]'));
                          }
                          bindSwatches();
                          window.trylApplyGridFilter();
    
                          btn.setAttribute('data-page', page);
                          btn.textContent = ogText;
                          btn.style.opacity = '1';
                          btn.style.pointerEvents = 'auto';
    
                          if (page >= max) {
                              btn.parentElement.style.display = 'none';
                          }
                      } else {
                          btn.textContent = 'Error loading';
                      }
                  })
                  .catch(err => {
                      btn.textContent = 'Error loading';
                  });
              });
          }
    });
    </script>
    <?php
    return ob_get_clean();
}
} // endif function_exists( 'tryl_core_3d_shop_shortcode' )

// Forcefully overwrite the shortcode tag, ignoring any older plugins
remove_shortcode( 'tryl_3d_shop' );
add_shortcode( 'tryl_3d_shop', 'tryl_core_3d_shop_shortcode' );

// ─── 3. PREMIUM CART & CHECKOUT CSS ──────────────────────────────────────────
function tryl_premium_cart_checkout_css() {
    if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) return;
    ?>
    <style>
    :root, [data-theme="bright"] {
        --sage: #e8f0e8; --green: #2d6a4f; --dark: #0d1b0f; --off: #f5f8f5;
        --accent: #31d190; --txt: #1a2e1a; --muted: #6b7c6b; --border: #d4e0d4;
        --btn-txt: #fff; --input-bg: #fff; --card-bg: #fff;
    }
    [data-theme="mild"] {
        --sage: #d6d3cc; --green: #686358; --dark: #33322e; --off: #e6e4df;
        --accent: #a39e93; --txt: #33322e; --muted: #858178; --border: #c4c0b5;
        --btn-txt: #fff; --input-bg: #f2f0eb; --card-bg: #f2f0eb;
    }
    [data-theme="dark"] {
        --sage: #1a2e1a; --green: #31d190; --dark: #f5f8f5; --off: #0d1b0f;
        --accent: #31d190; --txt: #f5f8f5; --muted: #8a9c8a; --border: #2d6a4f;
        --btn-txt: #0d1b0f; --input-bg: #132615; --card-bg: #132615;
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        background: var(--off);
        font-family: var(--tryl-body-font);
        color: var(--txt);
        transition: background .3s, color .3s;
    }

    /* ── Shared Wrapper ── */
    .woocommerce {
        font-family: var(--tryl-body-font);
        max-width: 1200px;
        margin: 48px auto;
        padding: 0 32px;
    }
    @media (max-width: 700px) {
        .woocommerce { padding: 0 16px; margin: 32px auto; }
    }

    h1.entry-title, h1, h2.woocommerce-cart-title, h1.woocommerce-order-confirmation-title {
        font-family: var(--tryl-header-font);
        font-weight: 900;
        font-size: 2.6rem;
        text-transform: uppercase;
        color: var(--dark);
        letter-spacing: .02em;
        margin-bottom: 36px;
    }

    /* ── Cart Styles ── */
    body.woocommerce-cart .woocommerce-cart-form { margin-bottom: 0; }
    body.woocommerce-cart .cart-collaterals { margin-top: 0; }

    body.woocommerce-cart table.shop_table {
        border: none;
        border-collapse: collapse;
        width: 100%;
    }
    body.woocommerce-cart table.shop_table th {
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .15em;
        text-transform: uppercase;
        color: var(--muted);
        padding: 0 0 16px;
        border-bottom: 2px solid var(--dark);
        background: transparent;
    }
    body.woocommerce-cart table.shop_table td {
        border: none;
        border-bottom: 1px solid var(--border);
        padding: 28px 12px;
        background: transparent;
        vertical-align: middle;
    }
    body.woocommerce-cart table.shop_table .product-thumbnail img {
        width: 100px;
        height: 100px;
        object-fit: contain;
        background: var(--input-bg);
        border: 1px solid var(--border);
        padding: 8px;
    }
    body.woocommerce-cart table.shop_table .product-name a {
        font-family: var(--tryl-header-font);
        font-weight: 700;
        font-size: 1.15rem;
        text-transform: uppercase;
        color: var(--dark);
        text-decoration: none;
    }
    body.woocommerce-cart table.shop_table .product-name a:hover { color: var(--green); }
    body.woocommerce-cart table.shop_table .product-remove a {
        font-size: 22px;
        color: var(--muted);
        background: transparent;
        line-height: 1;
    }
    body.woocommerce-cart table.shop_table .product-remove a:hover { color: var(--dark); }
    body.woocommerce-cart .quantity input.qty {
        border: 1.5px solid var(--border);
        border-radius: 0;
        height: 44px;
        width: 64px;
        text-align: center;
        font-family: var(--tryl-body-font);
        font-size: .9rem;
        font-weight: 600;
        background: var(--input-bg);
        color: var(--txt);
    }
    body.woocommerce-cart .quantity input.qty:focus { border-color: var(--dark); outline: none; }

    body.woocommerce-cart .actions { border: none; padding: 20px 0; display: flex; gap: 12px; align-items: center; }
    body.woocommerce-cart .coupon { display: flex; gap: 8px; flex: 1; }
    body.woocommerce-cart .coupon input.input-text {
        height: 48px;
        border: 1.5px solid var(--border);
        border-radius: 0;
        padding: 0 16px;
        font-family: var(--tryl-body-font);
        background: var(--input-bg);
        color: var(--txt);
        flex: 1;
    }
    body.woocommerce-cart .coupon input.input-text:focus { border-color: var(--dark); outline: none; }
    body.woocommerce-cart .cart_totals {
        background: var(--input-bg);
        border: 1px solid var(--border);
        padding: 32px;
    }
    body.woocommerce-cart .cart_totals h2 { font-size: 1.6rem; margin-bottom: 24px; }
    body.woocommerce-cart .cart_totals table.shop_table th {
        font-family: var(--tryl-body-font);
        text-transform: none;
        font-size: .85rem;
        font-weight: 500;
        color: var(--muted);
    }
    body.woocommerce-cart .cart_totals table.shop_table td { text-align: right; font-weight: 700; font-size: .95rem; }
    body.woocommerce-cart .cart_totals .order-total th,
    body.woocommerce-cart .cart_totals .order-total td { font-size: 1.1rem; color: var(--dark); }

    /* ── Global Buttons ── */
    .woocommerce button.button,
    .woocommerce a.button,
    .woocommerce input.button {
        font-family: var(--tryl-body-font);
        font-weight: 700;
        font-size: .78rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        background: var(--dark);
        color: var(--btn-txt);
        border: none;
        border-radius: 0;
        padding: 16px 28px;
        cursor: pointer;
        transition: background .25s;
    }
    .woocommerce button.button:hover,
    .woocommerce a.button:hover,
    .woocommerce input.button:hover { background: var(--green); }
    .woocommerce button.button[name="update_cart"] {
        background: var(--input-bg);
        color: var(--txt);
        border: 1.5px solid var(--border);
    }
    .woocommerce button.button[name="update_cart"]:hover { border-color: var(--dark); }

    /* ── CUSTOM CHECKOUT LAYOUT ── */
    .tryl-checkout-form { margin: 0; }
    .tryl-checkout-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 48px;
        align-items: start;
    }
    @media (max-width: 900px) {
        .tryl-checkout-grid { grid-template-columns: 1fr; gap: 32px; }
    }

    .tryl-checkout-sidebar {
        position: sticky;
        top: 80px;
    }
    @media (max-width: 900px) {
        .tryl-checkout-sidebar { position: static; }
    }

    /* ── Checkout Fields ── */
    .tryl-checkout-fields .woocommerce-billing-fields h3,
    .tryl-checkout-fields .woocommerce-shipping-fields h3 {
        font-family: var(--tryl-header-font);
        font-weight: 700;
        font-size: 1.4rem;
        text-transform: uppercase;
        color: var(--dark);
        margin-bottom: 20px;
    }
    .tryl-checkout-fields .woocommerce-billing-fields h3:first-of-type { margin-top: 0; }

    .woocommerce form .form-row {
        margin-bottom: 16px;
    }
    .woocommerce form .form-row label {
        font-size: .72rem;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        margin-bottom: 6px;
        display: block;
    }
    .woocommerce form .form-row input.input-text,
    .woocommerce form .form-row select,
    .woocommerce form .form-row textarea {
        width: 100%;
        border: 1.5px solid var(--border);
        border-radius: 0;
        padding: 14px 16px;
        background: var(--input-bg);
        color: var(--txt);
        font-family: var(--tryl-body-font);
        font-size: .9rem;
        line-height: 1.4;
        transition: border-color .2s;
    }
    .woocommerce form .form-row input.input-text:focus,
    .woocommerce form .form-row select:focus,
    .woocommerce form .form-row textarea:focus {
        border-color: var(--dark);
        outline: none;
    }
    .woocommerce form .form-row .select2-container .select2-selection--single {
        border: 1.5px solid var(--border);
        border-radius: 0;
        height: 48px;
        padding: 10px 14px;
        background: var(--input-bg);
    }
    .woocommerce form .form-row .select2-container .select2-selection--single .select2-selection__rendered {
        color: var(--txt);
        font-family: var(--tryl-body-font);
        font-size: .9rem;
    }
    .woocommerce form .form-row .select2-container--focus .select2-selection--single {
        border-color: var(--dark);
    }
    .woocommerce form .form-row .select2-container .select2-selection--single .select2-selection__arrow {
        height: 46px;
    }

    /* ── Order Review (sidebar) ── */
    #order_review {
        background: var(--card-bg);
        border: 1px solid var(--border);
        padding: 24px;
    }
    #order_review_heading {
        font-family: var(--tryl-header-font);
        font-weight: 700;
        font-size: 1.2rem;
        text-transform: uppercase;
        color: var(--dark);
        margin: 0 0 20px;
    }
    .woocommerce-checkout-review-order-table {
        width: 100%;
        border: none;
        border-collapse: collapse;
    }
    .woocommerce-checkout-review-order-table thead {
        display: none;
    }
    .woocommerce-checkout-review-order-table tbody tr {
        border-bottom: 1px solid var(--border);
    }
    .woocommerce-checkout-review-order-table tbody tr:last-child {
        border-bottom: none;
    }
    .woocommerce-checkout-review-order-table td {
        padding: 16px 0;
        vertical-align: middle;
        background: transparent;
        border: none;
    }
    .woocommerce-checkout-review-order-table .product-name {
        font-family: var(--tryl-header-font);
        font-weight: 600;
        font-size: .9rem;
        color: var(--txt);
    }
    .woocommerce-checkout-review-order-table .product-name .product-quantity {
        color: var(--muted);
    }
    .woocommerce-checkout-review-order-table .product-total {
        text-align: right;
        font-weight: 600;
        font-size: .9rem;
        color: var(--dark);
    }
    .woocommerce-checkout-review-order-table tfoot tr {
        border-top: 1px solid var(--border);
    }
    .woocommerce-checkout-review-order-table tfoot th {
        text-align: left;
        font-weight: 500;
        font-size: .85rem;
        color: var(--muted);
        padding: 12px 0;
        background: transparent;
        border: none;
    }
    .woocommerce-checkout-review-order-table tfoot td {
        text-align: right;
        font-weight: 600;
        font-size: .9rem;
        padding: 12px 0;
        background: transparent;
        border: none;
    }
    .woocommerce-checkout-review-order-table tfoot .order-total th,
    .woocommerce-checkout-review-order-table tfoot .order-total td {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark);
        padding-top: 16px;
    }

    /* ── Payment ── */
    #payment {
        background: transparent;
        border: none;
        padding: 20px 0 0;
        border-radius: 0;
        color: var(--txt);
    }
    #payment ul.payment_methods {
        list-style: none;
        margin: 0 0 16px;
        padding: 0;
        border: none;
    }
    #payment ul.payment_methods li {
        background: var(--card-bg);
        border: 1.5px solid var(--border);
        padding: 14px 16px;
        margin-bottom: 8px;
        list-style: none;
        transition: border-color .2s;
    }
    #payment ul.payment_methods li input[type="radio"]:checked + label {
        font-weight: 700;
    }
    #payment ul.payment_methods li label {
        font-family: var(--tryl-header-font);
        font-size: .85rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--dark);
        cursor: pointer;
    }
    #payment ul.payment_methods li div.payment_box {
        background: var(--off);
        margin-top: 12px;
        padding: 14px;
        font-size: .85rem;
        color: var(--txt);
    }
    #payment ul.payment_methods li div.payment_box p {
        margin: 0;
    }
    #payment .place-order {
        margin-top: 16px;
    }
    #place_order {
        width: 100%;
        padding: 18px 28px;
        font-family: var(--tryl-body-font);
        font-weight: 700;
        font-size: .8rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        background: var(--dark);
        color: var(--btn-txt);
        border: none;
        border-radius: 0;
        cursor: pointer;
        transition: background .25s;
        position: relative;
    }
    #place_order:hover { background: var(--green); }

    .woocommerce-terms-and-conditions-wrapper {
        font-size: .8rem;
        color: var(--muted);
        margin-bottom: 16px;
    }
    .woocommerce-terms-and-conditions-wrapper input { margin-right: 8px; }

    /* ── Order review images ── */
    .tryl-order-thumb {
        width: 56px;
        height: 56px;
        object-fit: contain;
        background: var(--input-bg);
        border: 1px solid var(--border);
        padding: 4px;
        vertical-align: middle;
        margin-right: 12px;
    }
    .tryl-order-name {
        font-family: var(--tryl-header-font);
        font-weight: 700;
        font-size: .9rem;
        text-transform: uppercase;
        vertical-align: middle;
    }

    /* ── Extra Features Dashboard ── */
    .feature-dashboard { background: var(--input-bg); border: 1px solid var(--border); border-radius: 12px; padding: 28px; margin-bottom: 32px; transition: background 0.3s, border-color 0.3s; }
    .feature-dashboard .dashboard-header { font-weight: 800; font-size: 1.25rem; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--dark); font-family: var(--tryl-header-font); }
    .feature-dashboard .dashboard-desc { font-size: 0.85rem; color: var(--muted); margin-bottom: 24px; font-weight: 500; }
    .feature-dashboard .feature-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--border); }
    .feature-dashboard .feature-item:last-child { border: none; padding-bottom: 0; }
    .feature-dashboard .feature-label { font-size: 0.95rem; font-weight: 600; color: var(--txt); }
    .nike-switch { position: relative; display: inline-block; width: 44px; height: 24px; margin: 0; flex-shrink: 0; }
    .nike-switch input { opacity: 0; width: 0; height: 0; margin: 0; position: absolute; }
    .nike-switch-inner { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--border); border-radius: 24px; transition: .3s; }
    .nike-switch-inner:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: var(--input-bg); border-radius: 50%; transition: .3s; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .nike-switch input:checked + .nike-switch-inner { background-color: var(--dark); }
    .nike-switch input:checked + .nike-switch-inner:before { transform: translateX(20px); background-color: var(--btn-txt); }
    .nike-switch.disabled { opacity: 0.5; cursor: not-allowed; }
    .nike-switch.disabled .nike-switch-inner { background-color: var(--border); cursor: not-allowed; }
    .badge-soon { background: var(--dark); color: var(--btn-txt); font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; margin-left: 12px; letter-spacing: 0.05em; line-height: 1; display: inline-block; }
    </style>
    <?php
}
// add_action( 'wp_head', 'tryl_premium_cart_checkout_css' ); // Safely extracted to tryl-core.css

function tryl_premium_cart_checkout_gsap() {
    if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) return;
    if ( get_option( 'tryl_checkout_animations', '1' ) !== '1' ) return;
    ?>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof gsap === "undefined") return;

        var tl = gsap.timeline({ defaults: { ease: "power3.out" } });

        // Cart page: fade + lift the whole wrapper
        if (document.body.classList.contains("woocommerce-cart")) {
            tl.fromTo(".woocommerce",
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.6 }
            );
        }

        // Checkout page: sequenced entrance
        if (document.body.classList.contains("woocommerce-checkout")) {
            var grid = document.querySelector(".tryl-checkout-grid");
            var fields = document.querySelectorAll(".tryl-checkout-main .form-row");
            var sidebar = document.querySelector(".tryl-checkout-sidebar");

            if (grid) {
                tl.fromTo(grid,
                    { opacity: 0, y: 24 },
                    { opacity: 1, y: 0, duration: 0.5 },
                    0
                );
            }

            if (fields.length) {
                tl.fromTo(fields,
                    { opacity: 0, y: 12 },
                    { opacity: 1, y: 0, duration: 0.35, stagger: 0.025 },
                    "-=0.15"
                );
            }

            if (sidebar) {
                tl.fromTo(sidebar,
                    { opacity: 0, x: 16 },
                    { opacity: 1, x: 0, duration: 0.45 },
                    "-=0.25"
                );
            }

            // Magnetic hover on place order
            var placeBtn = document.getElementById("place_order");
            if (placeBtn) {
                placeBtn.addEventListener("mousemove", function(e) {
                    var r = this.getBoundingClientRect();
                    var x = (e.clientX - r.left - r.width / 2) * 0.15;
                    var y = (e.clientY - r.top - r.height / 2) * 0.15;
                    gsap.to(this, { x: x, y: y, duration: 0.25, ease: "power2.out" });
                });
                placeBtn.addEventListener("mouseleave", function() {
                    gsap.to(this, { x: 0, y: 0, duration: 0.35, ease: "power2.out" });
                });
            }
        }
    });
    </script>
    <?php
}
// add_action( 'wp_footer', 'tryl_premium_cart_checkout_gsap' ); // Safely extracted to tryl-core.js

// ─── 4. INJECT GLOBAL SITE NAV ───────────────────────────────────────────────
function tryl_global_nav_css() {
    if ( get_option('tryl_nav_active', '1') !== '1' ) return;
    $mobile_align = get_option('tryl_mobile_menu_align', 'left');
    ?>
    <style>
    :root, [data-theme="bright"] {
        --ry-bg: #f5f8f5;
        --ry-nav-bg: rgba(245,248,245,.94);
        --ry-text: #1a2e1a;
        --ry-accent: #2d6a4f;
        --ry-border: #d4e0d4;
        --ry-footer-bg: #0d1b0f;
        --ry-footer-text: #f5f8f5;
        --ry-footer-muted: #d4e0d4;
        --ry-footer-accent: #31d190;
        --ry-hamburger: #1a2e1a;
    }
    [data-theme="mild"] {
        --ry-bg: #e6e4df;
        --ry-nav-bg: rgba(230,228,223,.94);
        --ry-text: #33322e;
        --ry-accent: #686358;
        --ry-border: #c4c0b5;
        --ry-footer-bg: #22211e;
        --ry-footer-text: #e6e4df;
        --ry-footer-muted: #c4c0b5;
        --ry-footer-accent: #a39e93;
        --ry-hamburger: #33322e;
    }
    [data-theme="dark"] {
        --ry-bg: #0d1b0f;
        --ry-nav-bg: rgba(13,27,15,.94);
        --ry-text: #f5f8f5;
        --ry-accent: #31d190;
        --ry-border: #2d6a4f;
        --ry-footer-bg: #050a06;
        --ry-footer-text: #8a9c8a;
        --ry-footer-muted: #4a5c4a;
        --ry-footer-accent: #31d190;
        --ry-hamburger: #f5f8f5;
    }
    /* ── Site Nav (Global) ── */
    body::before{content:'';display:block;height:64px;}
    .tryl-injected-nav{position:fixed;top:0;left:0;right:0;z-index:9999;background:var(--ry-nav-bg);backdrop-filter:blur(18px);border-bottom:1px solid var(--ry-border);display:flex;align-items:center;justify-content:space-between;padding:0 40px;height:64px;transition:background .3s, border-color .3s;}
    @media(max-width:700px){.tryl-injected-nav{padding:0 20px;}}
    .tryl-nav-brand-bar{font-family:var(--tryl-header-font);font-weight:900;font-size:1.1rem;letter-spacing:.05em;text-transform:uppercase;color:var(--ry-accent);text-decoration:none;}
    .tryl-nav-brand-logo{display:flex;align-items:center;}
    .tryl-nav-brand-logo img{max-height:40px;width:auto;display:block;}
    .tryl-nav-links-bar{display:flex;gap:32px;list-style:none;margin:0;padding:0;}
    .tryl-nav-links-bar a{font-family:var(--tryl-body-font);font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--ry-text);text-decoration:none;transition:color .2s;}
    .tryl-nav-links-bar a:hover{color:var(--ry-accent);}
    .tryl-nav-links-bar a.tryl-nav-cta-btn { background: var(--ry-text); color: var(--ry-bg) !important; padding: 8px 16px; border-radius: 4px; font-weight: 800; letter-spacing: 0.15em; }
    .tryl-nav-links-bar a.tryl-nav-cta-btn:hover { background: var(--ry-accent); color: var(--ry-bg) !important; }
    @media(max-width:700px){.tryl-nav-links-bar{display:none;}}

    /* ── Mobile Hamburger & Menu ── */
    .tryl-hamburger{display:none;flex-direction:column;justify-content:space-between;width:26px;height:18px;background:transparent;border:none;cursor:pointer;padding:0;z-index:10000;}
    .tryl-hamburger span{width:100%;height:2px;background:var(--ry-hamburger);transition:all .3s ease;border-radius:2px;}
    .tryl-hamburger.open span:nth-child(1){transform:translateY(8px) rotate(45deg);}
    .tryl-hamburger.open span:nth-child(2){opacity:0;}
    .tryl-hamburger.open span:nth-child(3){transform:translateY(-8px) rotate(-45deg);}
    @media(max-width:700px){.tryl-hamburger{display:flex;}}
    .tryl-mobile-nav{position:fixed;top:64px;left:0;right:0;bottom:0;overflow-y:auto;background:var(--ry-nav-bg);backdrop-filter:blur(18px);border-bottom:1px solid var(--ry-border);padding:24px 20px;display:flex;flex-direction:column;gap:16px;z-index:9998;transform:translateY(-100%);opacity:0;visibility:hidden;transition:all .4s cubic-bezier(0.25, 0.46, 0.45, 0.94);}
    .tryl-mobile-nav.open{transform:translateY(0);opacity:1;visibility:visible;box-shadow:0 10px 30px rgba(0,0,0,0.05);}
    .tryl-mobile-nav a{font-family:var(--tryl-body-font);font-size:1.1rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--ry-text);text-decoration:none;border-bottom:1px solid var(--ry-border);padding-bottom:12px; text-align: <?php echo esc_attr($mobile_align); ?>;}
    .tryl-mobile-nav a:last-child{border-bottom:none;padding-bottom:0;}
    .tryl-mobile-nav a.tryl-nav-cta-btn { background: var(--ry-text); color: var(--ry-bg) !important; padding: 12px; border-radius: 4px; text-align: center; margin-top: 8px; border-bottom: none; }
    .tryl-mobile-nav a.tryl-nav-cta-btn:hover { background: var(--ry-accent); }
    
    /* ── Theme Switcher ── */
    .tryl-theme-switcher { display: flex; gap: 8px; align-items: center; margin-left: 24px; padding-left: 24px; border-left: 1px solid var(--ry-border); }
    @media(max-width:700px){.tryl-theme-switcher{margin: 16px 0 0 0; padding: 16px 0 0 0; border-left: none; border-top: 1px solid var(--ry-border); justify-content: flex-start;}}
    .tryl-ts-btn { width: 18px; height: 18px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; padding: 0; transition: transform 0.2s, border-color 0.2s; }
    .tryl-ts-btn:hover { transform: scale(1.15); }
    .tryl-ts-btn.active { border-color: var(--ry-accent); transform: scale(1.15); box-shadow: 0 0 0 2px var(--ry-bg); }
    .tryl-ts-bright { background: #f5f8f5; border-color: #d4e0d4; }
    .tryl-ts-mild { background: #e6e4df; border-color: #c4c0b5; }
    .tryl-ts-dark { background: #0d1b0f; border-color: #2d6a4f; }
    
    /* ── Search Bar ── */
    .tryl-nav-search { display: flex; align-items: center; background: var(--ry-bg); border: 1px solid var(--ry-border); border-radius: 40px; padding: 6px 14px; transition: border-color 0.2s; margin-left: 16px; }
    .tryl-nav-search:focus-within { border-color: var(--ry-accent); }
    .tryl-search-field { background: transparent; border: none; outline: none; font-family: var(--tryl-body-font); font-size: 0.75rem; color: var(--ry-text); width: 120px; transition: width 0.3s; }
    .tryl-search-field::placeholder { color: var(--ry-text); opacity: 0.5; }
    .tryl-search-field:focus { width: 180px; }
    .tryl-search-btn { background: transparent; border: none; cursor: pointer; color: var(--ry-text); opacity: 0.6; display: flex; align-items: center; justify-content: center; padding: 0; transition: opacity 0.2s; }
    .tryl-search-btn:hover { opacity: 1; color: var(--ry-accent); }
    @media(max-width: 900px) { .desktop-search { display: none !important; } }
    
    .tryl-mobile-search { margin-bottom: 12px; width: 100%; }
    .tryl-mobile-search .tryl-nav-search { margin-left: 0; width: 100%; border-radius: 8px; padding: 10px 16px; }
    .tryl-mobile-search .tryl-search-field { width: 100%; font-size: 0.9rem; }
    
    /* ── Mobile Socials ── */
    .tryl-mobile-socials { display: flex; gap: 20px; margin-top: auto; padding-top: 24px; padding-bottom: 12px; align-items: center; border-top: 1px solid var(--ry-border); }
    .tryl-mobile-socials a { color: var(--ry-text); opacity: 0.6; transition: opacity 0.2s, color 0.2s; border-bottom: none !important; padding: 0 !important; }
    .tryl-mobile-socials a:hover { opacity: 1; color: var(--ry-accent); }
    .tryl-mobile-socials svg { width: 22px; height: 22px; display: block; }
    </style>
    <?php
}
// add_action( 'wp_head', 'tryl_global_nav_css' ); // Safely extracted to tryl-core.css

function tryl_inject_nav_bar() {
    if ( get_option('tryl_nav_active', '1') !== '1' ) return;

    $logo_url = get_option( 'tryl_header_logo' );
    if ( empty( $logo_url ) ) {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $logo_url       = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
        // Fallback for Divi's proprietary logo system
        if ( empty( $logo_url ) && function_exists( 'et_get_option' ) ) {
            $logo_url = et_get_option( 'divi_logo' );
        }
    }
    $shop_url = get_option('tryl_nav_shop');
    if ( empty( $shop_url ) ) {
        $shop_url = 'https://therighteousyieldlife.com/the-shop-wip/';
    }
    $cart_url  = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
    
    $checkout_url = get_option('tryl_nav_checkout');
    if ( empty( $checkout_url ) ) {
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    }
    
    $social_ig = get_option('tryl_social_instagram');
    $social_tk = get_option('tryl_social_tiktok');
    $social_tw = get_option('tryl_social_twitter');
    $social_yt = get_option('tryl_social_youtube');
    $social_fb = get_option('tryl_social_facebook');
    $nav_items = [
        'Shop'           => $shop_url,
    ];
    if ( get_option('tryl_header_checkout_cta', '1') === '1' ) {
        $nav_items['Checkout'] = $checkout_url;
    }
    $nav_items['Mission']        = get_option('tryl_nav_mission', home_url('/mission/'));
    $nav_items['Prayer Request'] = get_option('tryl_nav_prayer', home_url('/prayer-request/'));
    $nav_items['Contact']        = get_option('tryl_nav_contact', home_url('/contact/'));
    ?>
    <nav class="tryl-injected-nav">
      <?php if ( $logo_url ) : ?>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="tryl-nav-brand-logo"><img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"></a>
      <?php else : ?>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="tryl-nav-brand-bar">The Righteous Yield Life</a>
      <?php endif; ?>
      <ul class="tryl-nav-links-bar">
        <?php foreach($nav_items as $label=>$href): 
            $is_checkout = (strtolower($label) === 'checkout');
            $link_class = $is_checkout ? 'tryl-nav-cta-btn' : '';
        ?>
        <li><a href="<?php echo esc_url($href);?>" class="<?php echo $link_class; ?>"><?php echo esc_html($label);?></a></li>
        <?php endforeach;?>
        <li class="desktop-search" style="display:flex;align-items:center;">
            <form role="search" method="get" class="tryl-nav-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="hidden" name="post_type" value="product" />
                <input type="search" class="tryl-search-field" placeholder="Search..." value="<?php echo get_search_query(); ?>" name="s" />
                <button type="submit" class="tryl-search-btn" aria-label="Search">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </form>
        </li>
        <li style="display:flex;align-items:center;margin-left:8px;">
            <a href="<?php echo esc_url($cart_url); ?>" style="position:relative;display:flex;align-items:center;text-decoration:none;" aria-label="Cart">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                <span class="tryl-cart-count tryl-cart-count-badge" style="position:absolute;top:-6px;right:-8px;background:var(--ry-accent);color:var(--ry-bg);font-size:10px;font-weight:700;width:16px;height:16px;display:none;align-items:center;justify-content:center;border-radius:50%;line-height:1;font-family:var(--tryl-body-font);">0</span>
            </a>
        </li>
        <li class="tryl-theme-switcher">
          <button class="tryl-ts-btn tryl-ts-bright" data-set-theme="bright" aria-label="Bright Mode"></button>
          <button class="tryl-ts-btn tryl-ts-mild" data-set-theme="mild" aria-label="Mild Mode"></button>
          <button class="tryl-ts-btn tryl-ts-dark" data-set-theme="dark" aria-label="Dark Mode"></button>
        </li>
      </ul>
      <button class="tryl-hamburger" id="trylHamburger" aria-label="Toggle Menu">
        <span></span><span></span><span></span>
      </button>
    </nav>
    <div class="tryl-mobile-nav" id="trylMobileNav">
      <div class="tryl-mobile-search">
          <form role="search" method="get" class="tryl-nav-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
              <input type="hidden" name="post_type" value="product" />
              <input type="search" class="tryl-search-field" placeholder="Search products..." value="<?php echo get_search_query(); ?>" name="s" />
              <button type="submit" class="tryl-search-btn" aria-label="Search">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              </button>
          </form>
      </div>
      <?php foreach($nav_items as $label=>$href): 
            $is_checkout = (strtolower($label) === 'checkout');
            $link_class = $is_checkout ? 'tryl-nav-cta-btn' : '';
      ?>
      <a href="<?php echo esc_url($href);?>" class="<?php echo $link_class; ?>"><?php echo esc_html($label);?></a>
      <?php endforeach;?>
      <?php 
      $mobile_align = get_option('tryl_mobile_menu_align', 'left');
      $cart_justify = 'space-between';
      if ($mobile_align === 'center') $cart_justify = 'center';
      if ($mobile_align === 'right') $cart_justify = 'flex-end';
      ?>
      <a href="<?php echo esc_url($cart_url); ?>" onclick="if(window.trylOpenCart) { window.trylOpenCart(); } else { window.location.href='<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>'; } return false;" style="display:flex; align-items:center; justify-content:<?php echo esc_attr($cart_justify); ?>; gap:8px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Cart (<span class="tryl-cart-count">0</span>)
      </a>
      <div class="tryl-theme-switcher" style="justify-content: <?php echo esc_attr($cart_justify); ?>;">
          <button class="tryl-ts-btn tryl-ts-bright" data-set-theme="bright" aria-label="Bright Mode"></button>
          <button class="tryl-ts-btn tryl-ts-mild" data-set-theme="mild" aria-label="Mild Mode"></button>
          <button class="tryl-ts-btn tryl-ts-dark" data-set-theme="dark" aria-label="Dark Mode"></button>
      </div>
      <?php if ( $social_ig || $social_tk || $social_tw || $social_yt || $social_fb ) : ?>
      <div class="tryl-mobile-socials" style="justify-content: <?php echo esc_attr($cart_justify); ?>;">
          <?php if ( $social_ig ) : ?><a href="<?php echo esc_url($social_ig); ?>" target="_blank" aria-label="Instagram"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg></a><?php endif; ?>
          <?php if ( $social_tk ) : ?><a href="<?php echo esc_url($social_tk); ?>" target="_blank" aria-label="TikTok"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5"></path></svg></a><?php endif; ?>
          <?php if ( $social_tw ) : ?><a href="<?php echo esc_url($social_tw); ?>" target="_blank" aria-label="Twitter / X"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l11.733 16h4.267l-11.733 -16z"/><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"/></svg></a><?php endif; ?>
          <?php if ( $social_yt ) : ?><a href="<?php echo esc_url($social_yt); ?>" target="_blank" aria-label="YouTube"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg></a><?php endif; ?>
          <?php if ( $social_fb ) : ?><a href="<?php echo esc_url($social_fb); ?>" target="_blank" aria-label="Facebook"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <script>
    // Apply theme immediately to prevent Flash of Unstyled Content
    (function() {
        var defaultTheme = '<?php echo esc_js(get_option("tryl_default_theme", "bright")); ?>';
        var savedTheme = localStorage.getItem('tryl_theme');
        if (!savedTheme) {
            savedTheme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : defaultTheme;
        }
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();

    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('trylHamburger');
        var nav = document.getElementById('trylMobileNav');
        if(btn && nav) {
            btn.addEventListener('click', function() {
                btn.classList.toggle('open');
                nav.classList.toggle('open');
            });
        }
        
        var currentTheme = document.documentElement.getAttribute('data-theme') || 'bright';
        updateActiveBtns(currentTheme);

        document.querySelectorAll('[data-set-theme]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var theme = btn.getAttribute('data-set-theme');
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('tryl_theme', theme);
                updateActiveBtns(theme);
            });
        });
        
        function updateActiveBtns(theme) {
            document.querySelectorAll('[data-set-theme]').forEach(function(b) { b.classList.remove('active'); });
            document.querySelectorAll('[data-set-theme="'+theme+'"]').forEach(function(b) { b.classList.add('active'); });
        }
    });
    </script>
    <?php
}
add_action( 'wp_body_open', 'tryl_inject_nav_bar' );
add_action( 'wp_footer',    'tryl_inject_nav_bar_fallback' );
function tryl_inject_nav_bar_fallback() {
    // Only fire if wp_body_open wasn't supported by theme
    if ( did_action('wp_body_open') > 0 ) return;
    tryl_inject_nav_bar();
}

// ─── 5. INJECT GLOBAL SITE FOOTER ────────────────────────────────────────────
function tryl_global_footer_css() {
    if ( get_option('tryl_footer_active', '1') !== '1' ) return;

    $layout        = get_option('tryl_footer_layout_style', 'grid');
    $hover_anim    = get_option('tryl_footer_hover_anim', '1');
    $mobile_center = get_option('tryl_footer_mobile_center', '1');
    ?>
    <style>
    /* ── Site Footer (Global) ── */
    /* Hide default footers from Divi/SeedProd to prevent duplicates */
    #main-footer, footer.site-footer, #colophon, .site-info, .footer-widgets { display: none !important; }
    
    .tryl-global-footer {
        background: var(--ry-footer-bg);
        color: var(--ry-footer-text);
        padding: 64px 40px 32px;
        font-family: var(--tryl-body-font);
        margin-top: auto;
        position: relative;
        z-index: 10;
        transition: background .3s, color .3s;
    }
    @media(max-width:700px) { .tryl-global-footer { padding: 48px 20px 24px; } }
    .tryl-footer-inner {
        max-width: 1200px;
        margin: 0 auto;
        <?php if ( $layout === 'grid' ) : ?>
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        <?php else : ?>
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        <?php endif; ?>
        gap: 40px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-bottom: 40px;
        margin-bottom: 24px;
    }
    <?php if ( $layout === 'grid' ) : ?>
    @media(max-width:768px) { 
        .tryl-footer-inner { grid-template-columns: 1fr; gap: 32px; }
        .tryl-footer-brand { max-width: 100%; }
    }
    <?php endif; ?>
    .tryl-footer-brand { max-width: 320px; }
    .tryl-footer-logo-text {
        font-family: var(--tryl-header-font);
        font-weight: 900;
        font-size: 1.8rem;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--ry-footer-accent);
        text-decoration: none;
        display: block;
        margin-bottom: 16px;
    }
    .tryl-footer-desc { font-size: .85rem; line-height: 1.6; color: var(--ry-footer-muted); margin: 0; }
    .tryl-footer-links-col { display: flex; flex-direction: column; gap: 12px; }
    .tryl-footer-links-title {
        font-family: var(--tryl-header-font);
        font-weight: 700;
        font-size: 1.1rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--ry-footer-text);
        margin-bottom: 8px;
    }
    .tryl-footer-links-col a {
        font-size: .8rem;
        font-weight: 500;
        color: var(--ry-footer-muted);
        text-decoration: none;
        letter-spacing: .02em;
        <?php if ( $hover_anim ) : ?>
        transition: all .3s ease;
        display: inline-block;
        <?php else : ?>
        transition: color .2s;
        <?php endif; ?>
    }
    .tryl-footer-links-col a:hover { 
        color: var(--ry-footer-accent); 
        <?php if ( $hover_anim ) : ?>
        transform: translateX(4px);
        <?php endif; ?>
    }
    .tryl-footer-bottom {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        font-size: .75rem;
        color: var(--ry-footer-muted);
    }
    .tryl-footer-bottom a {
        color: var(--ry-footer-muted);
        text-decoration: none;
        transition: color .2s;
    }
    .tryl-footer-bottom a:hover { color: var(--ry-footer-text); }
    <?php if ( $mobile_center ) : ?>
    @media(max-width:768px) {
        .tryl-footer-bottom { flex-direction: column; text-align: center; justify-content: center; }
        .tryl-footer-bottom div:last-child { justify-content: center; width: 100%; }
    }
    <?php endif; ?>
    </style>
    <?php
}
// add_action( 'wp_head', 'tryl_global_footer_css' ); // Safely extracted to tryl-core.css

function tryl_inject_global_footer() {
    if ( get_option('tryl_footer_active', '1') !== '1' ) return;

    $shop_url = get_option('tryl_nav_shop');
    if ( empty( $shop_url ) ) {
        $shop_url = 'https://therighteousyieldlife.com/the-shop-wip/';
    }
    $footer_desc = get_option('tryl_footer_desc', 'Faith-forward essentials crafted with intention. Wear your values, represent your faith, and yield righteousness in all that you do.');
    $signature = get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices');
    
    $mission_url  = get_option('tryl_nav_mission', home_url('/mission/'));
    $prayer_url   = get_option('tryl_nav_prayer', home_url('/prayer-request/'));
    $contact_url  = get_option('tryl_nav_contact', home_url('/contact/'));
    $shipping_url = get_option('tryl_nav_shipping', home_url('/shipping-returns/'));
    $faq_url      = get_option('tryl_nav_faq', home_url('/faq/'));
    $privacy_url  = get_option('tryl_nav_privacy', home_url('/privacy-policy/'));
    $terms_url    = get_option('tryl_nav_terms', home_url('/terms/'));
    
    $layout        = get_option('tryl_footer_layout_style', 'grid');
    $hover_anim    = get_option('tryl_footer_hover_anim', '1');
    $mobile_center = get_option('tryl_footer_mobile_center', '1');
    $footer_classes = 'tryl-global-footer tryl-footer-layout-' . esc_attr($layout);
    $footer_classes .= $hover_anim ? ' tryl-footer-hover-anim' : ' tryl-footer-no-hover-anim';
    if ( $mobile_center ) $footer_classes .= ' tryl-footer-mobile-center';
    ?>
    <footer class="<?php echo esc_attr($footer_classes); ?>">
        <div class="tryl-footer-inner">
            <div class="tryl-footer-brand">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="tryl-footer-logo-text">The Righteous Yield Life</a>
                <p class="tryl-footer-desc"><?php echo esc_html($footer_desc); ?></p>
            </div>
            
            <div class="tryl-footer-links-col">
                <div class="tryl-footer-links-title">Explore</div>
                <a href="<?php echo esc_url($shop_url); ?>">The Collection</a>
                <a href="<?php echo esc_url($mission_url); ?>">Our Mission</a>
                <a href="<?php echo esc_url($prayer_url); ?>">Prayer Request</a>
            </div>
            
            <div class="tryl-footer-links-col">
                <div class="tryl-footer-links-title">Support</div>
                <a href="<?php echo esc_url($contact_url); ?>">Contact Us</a>
                <a href="<?php echo esc_url($shipping_url); ?>">Shipping & Returns</a>
                <a href="<?php echo esc_url($faq_url); ?>">FAQ</a>
            </div>
        </div>
        <div class="tryl-footer-bottom">
            <div>&copy; <?php echo date('Y'); ?> The Righteous Yield Life. All rights reserved. 
                <span style="margin-left: 8px; opacity: 0.8;" class="tryl-signature"><?php echo wp_kses_post($signature); ?></span>
            </div>
            <div style="display:flex; gap:16px;">
                <a href="<?php echo esc_url($privacy_url); ?>">Privacy Policy</a>
                <a href="<?php echo esc_url($terms_url); ?>">Terms of Service</a>
            </div>
        </div>
    </footer>
    <?php
}
add_action( 'wp_footer', 'tryl_inject_global_footer', 5 );

// ─── 6. RICH PRODUCT IMAGES ON CHECKOUT ORDER REVIEW ─────────────────────────
function tryl_checkout_item_name( $name, $cart_item, $key ) {
    if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) return $name;
    $product  = $cart_item['data'];
    $img_url  = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
    if ( ! $img_url ) return $name;
    return '<img class="tryl-order-thumb" src="'.esc_url($img_url).'" alt="'.esc_attr($product->get_name()).'">'
         . '<span class="tryl-order-name">'.esc_html($product->get_name()).'</span>';
}
add_filter( 'woocommerce_cart_item_name', 'tryl_checkout_item_name', 10, 3 );

// ─── 7. MINI CART DRAWER (site-wide AJAX cart) ───────────────────────────────

// Helper to determine if we should load the mini cart
function tryl_should_load_mini_cart() {
    if ( ! class_exists( 'WooCommerce' ) ) return false;
    // Load globally since the cart button is in the global nav
    return true;
}

function tryl_mini_cart_assets() {
    if ( ! tryl_should_load_mini_cart() ) return;
    ?>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <style>
    /* ── MINI CART CSS VARIABLES (skin defaults - override per skin) ── */
    :root, [data-theme="bright"] {
      --mc-bg: #fff;
      --mc-text: #1a2e1a;
      --mc-accent: #2d6a4f;
      --mc-dark: #0d1b0f;
      --mc-muted: #6b7c6b;
      --mc-border: #d4e0d4;
      --mc-overlay: rgba(0,0,0,0.45);
      --mc-btn-text: #fff;
    }
    [data-theme="mild"] {
      --mc-bg: #f2f0eb;
      --mc-text: #33322e;
      --mc-accent: #686358;
      --mc-dark: #33322e;
      --mc-muted: #858178;
      --mc-border: #c4c0b5;
      --mc-overlay: rgba(0,0,0,0.45);
      --mc-btn-text: #fff;
    }
    [data-theme="dark"] {
      --mc-bg: #132615;
      --mc-text: #f5f8f5;
      --mc-accent: #31d190;
      --mc-dark: #f5f8f5;
      --mc-muted: #8a9c8a;
      --mc-border: #2d6a4f;
      --mc-overlay: rgba(0,0,0,0.65);
      --mc-btn-text: #0d1b0f;
    }
    :root {
      --mc-header-font: var(--tryl-header-font);
      --mc-body-font: var(--tryl-body-font);
      --mc-radius: 0px;
      --mc-btn-radius: 0px;
      --mc-shadow: 0 0 40px rgba(0,0,0,0.08);
    }

    /* ── DRAWER ── */
    .tryl-mc-overlay{position:fixed;inset:0;z-index:99998;background:var(--mc-overlay);opacity:0;visibility:hidden;transition:opacity .35s,visibility .35s;will-change:opacity,visibility;}
    .tryl-mc-overlay.open{opacity:1;visibility:visible;}
    .tryl-mc-drawer{position:fixed;top:0;right:0;bottom:0;z-index:99999;width:420px;max-width:92vw;background:var(--mc-bg);box-shadow:var(--mc-shadow);display:flex;flex-direction:column;transform:translateX(100%);font-family:var(--mc-body-font);color:var(--mc-text);will-change:transform;}
    @media(max-width:480px){.tryl-mc-drawer{width:100vw;max-width:100vw;}}

    /* Header */
    .tryl-mc-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--mc-border);flex-shrink:0;}
    .tryl-mc-title{font-family:var(--mc-header-font);font-size:1.3rem;font-weight:700;color:var(--mc-dark);letter-spacing:.02em;margin:0;}
    .tryl-mc-count{font-size:.75rem;color:var(--mc-muted);margin-left:8px;}
    .tryl-mc-close{width:38px;height:38px;display:flex;align-items:center;justify-content:center;background:transparent;border:1px solid var(--mc-border);cursor:pointer;font-size:1.2rem;color:var(--mc-muted);transition:all .25s;border-radius:var(--mc-radius);}
    .tryl-mc-close:hover{background:var(--mc-dark);color:#fff;border-color:var(--mc-dark);}

    /* Items */
    .tryl-mc-items{flex:1;overflow-y:auto;padding:16px 24px;}
    .tryl-mc-empty{text-align:center;padding:60px 20px;color:var(--mc-muted);font-size:.9rem;}
    .tryl-mc-empty svg{width:48px;height:48px;margin-bottom:16px;opacity:.4;}
    .tryl-mc-empty p{margin:0;}

    /* Item row */
    .tryl-mc-item{display:flex;gap:14px;padding:16px 0;border-bottom:1px solid var(--mc-border);}
    .tryl-mc-item:last-child{border-bottom:none;}
    .tryl-mc-item-img{width:72px;height:72px;flex-shrink:0;background:var(--mc-border);padding:6px;display:flex;align-items:center;justify-content:center;border-radius:var(--mc-radius);}
    .tryl-mc-item-img img{width:100%;height:100%;object-fit:contain;}
    .tryl-mc-item-info{flex:1;min-width:0;}
    .tryl-mc-item-name{font-family:var(--mc-header-font);font-size:.95rem;font-weight:600;color:var(--mc-dark);margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .tryl-mc-item-meta{font-size:.72rem;color:var(--mc-muted);margin-bottom:6px;}
    .tryl-mc-item-bottom{display:flex;align-items:center;justify-content:space-between;}
    .tryl-mc-item-qty{display:flex;align-items:center;gap:0;}
    .tryl-mc-qty-btn{width:32px;height:32px;border:1px solid var(--mc-border);background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:var(--mc-text);transition:background .2s;}
    .tryl-mc-qty-btn:hover{background:var(--mc-border);}
    .tryl-mc-qty-num{width:36px;height:32px;border-top:1px solid var(--mc-border);border-bottom:1px solid var(--mc-border);display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:600;}
    .tryl-mc-item-price{font-weight:600;font-size:.9rem;color:var(--mc-dark);}
    .tryl-mc-item-remove{background:transparent;border:none;cursor:pointer;color:var(--mc-muted);font-size:1.1rem;padding:4px;transition:color .2s;line-height:1;}
    .tryl-mc-item-remove:hover{color:var(--mc-dark);}

    /* Footer */
    .tryl-mc-footer{padding:20px 24px 24px;border-top:1px solid var(--mc-border);flex-shrink:0;}
    .tryl-mc-subtotal{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;font-size:.95rem;}
    .tryl-mc-subtotal-label{color:var(--mc-muted);font-size:.72rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;}
    .tryl-mc-subtotal-amount{font-weight:700;font-size:1.15rem;color:var(--mc-dark);}
    .tryl-mc-free-ship{font-size:.72rem;color:var(--mc-accent);text-align:center;margin-bottom:14px;font-weight:500;}
    .tryl-mc-btns{display:flex;flex-direction:column;gap:8px;}
    .tryl-mc-btn{display:block;text-align:center;padding:14px 20px;font-family:var(--mc-body-font);font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;text-decoration:none;border:none;cursor:pointer;transition:all .25s;border-radius:var(--mc-btn-radius);}
    @keyframes trylPulseScale { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }
    .tryl-mc-btn-checkout{background:var(--mc-dark);color:var(--mc-btn-text); animation: trylPulseScale 2.5s infinite ease-in-out;}
    .tryl-mc-btn-checkout:hover{background:var(--mc-accent);color:var(--mc-btn-text); animation: none; transform: scale(1.03);}
    .tryl-mc-btn-view{background:transparent;color:var(--mc-text);border:1.5px solid var(--mc-border);}
    .tryl-mc-btn-view:hover{border-color:var(--mc-dark);}
    .tryl-mc-free-ship-wrap { margin-bottom: 24px; }
    .tryl-mc-free-ship-msg { font-size: 0.8rem; margin-bottom: 10px; color: var(--mc-text); text-align: center; }
    .tryl-mc-free-ship-msg strong { color: var(--mc-accent); }
    .tryl-mc-progress-bg { height: 6px; background: var(--mc-border); border-radius: 3px; overflow: hidden; }
    .tryl-mc-progress-bar { height: 100%; background: var(--mc-accent); transition: width 0.8s cubic-bezier(0.2, 0.8, 0.2, 1); }
    </style>
    <?php
}
// add_action( 'wp_head', 'tryl_mini_cart_assets' ); // Safely extracted to tryl-core.css

function tryl_mini_cart_html() {
    if ( ! tryl_should_load_mini_cart() ) return;
    $count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $subtotal = WC()->cart ? WC()->cart->get_cart_subtotal() : '$0.00';
    $items    = WC()->cart ? WC()->cart->get_cart() : [];
    $free_ship_threshold = get_option('tryl_free_shipping_threshold', '75');
    
    $checkout_url = get_option('tryl_nav_checkout');
    if ( empty( $checkout_url ) ) {
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    }
    ?>
    <div class="tryl-mc-overlay" id="trylMcOverlay"></div>
    <div class="tryl-mc-drawer" id="trylMcDrawer">
      <div class="tryl-mc-header">
        <div>
          <span class="tryl-mc-title">Your Cart</span>
          <span class="tryl-mc-count">(<?php echo $count; ?>)</span>
        </div>
        <button class="tryl-mc-close" id="trylMcClose" aria-label="Close cart">&times;</button>
      </div>

      <div class="tryl-mc-items" id="trylMcItems">
        <?php if ( empty( $items ) ): ?>
        <div class="tryl-mc-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          <p>Your cart is empty</p>
        </div>
        <?php else:
          foreach ( $items as $key => $item ):
            $prod = $item['data'];
            $img  = wp_get_attachment_image_url( $prod->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src();
        ?>
        <div class="tryl-mc-item" data-key="<?php echo esc_attr($key); ?>">
          <div class="tryl-mc-item-img"><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($prod->get_name()); ?>"></div>
          <div class="tryl-mc-item-info">
            <div class="tryl-mc-item-name"><?php echo esc_html($prod->get_name()); ?></div>
            <?php if ( ! empty( $item['variation'] ) ): ?>
            <div class="tryl-mc-item-meta"><?php echo wc_get_formatted_cart_item_data( $item ); ?></div>
            <?php endif; ?>
            <div class="tryl-mc-item-bottom">
              <div class="tryl-mc-item-qty">
                <button class="tryl-mc-qty-btn tryl-mc-qty-dec" data-key="<?php echo esc_attr($key); ?>">&minus;</button>
                <span class="tryl-mc-qty-num"><?php echo $item['quantity']; ?></span>
                <button class="tryl-mc-qty-btn tryl-mc-qty-inc" data-key="<?php echo esc_attr($key); ?>">+</button>
              </div>
              <span class="tryl-mc-item-price"><?php echo wp_kses_post(WC()->cart->get_product_price( $prod )); ?></span>
              <button class="tryl-mc-item-remove" data-key="<?php echo esc_attr($key); ?>" aria-label="Remove">&times;</button>
            </div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="tryl-mc-footer" id="trylMcFooter" <?php if ( empty( $items ) ) echo 'style="display:none"'; ?>>
        <?php 
          $subtotal_numeric = (float) WC()->cart->get_subtotal();
          $free_ship_val = (float) $free_ship_threshold;
          $percent = min(100, ($subtotal_numeric / $free_ship_val) * 100);
          $diff = $free_ship_val - $subtotal_numeric;
        ?>
        <div class="tryl-mc-free-ship-wrap">
          <div class="tryl-mc-free-ship-msg">
            <?php if ( $diff > 0 ): ?>
              You're only <strong>$<?php echo number_format($diff, 2); ?></strong> away from free shipping!
            <?php else: ?>
              🎉 You've unlocked <strong>Free Shipping!</strong>
            <?php endif; ?>
          </div>
          <div class="tryl-mc-progress-bg">
            <div class="tryl-mc-progress-bar" style="width: <?php echo $percent; ?>%"></div>
          </div>
        </div>
        <div class="tryl-mc-subtotal">
          <span class="tryl-mc-subtotal-label">Subtotal</span>
          <span class="tryl-mc-subtotal-amount" id="trylMcSubtotal"><?php echo wp_kses_post($subtotal); ?></span>
        </div>
        <div class="tryl-mc-btns">
          <a href="<?php echo esc_url( $checkout_url ); ?>" class="tryl-mc-btn tryl-mc-btn-checkout">Checkout</a>
          <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="tryl-mc-btn tryl-mc-btn-view">View Cart</a>
        </div>
      </div>
    </div>

    <script>
    (function(){
      "use strict";
      var overlay = document.getElementById('trylMcOverlay');
      var drawer  = document.getElementById('trylMcDrawer');
      var close   = document.getElementById('trylMcClose');

      if (!overlay || !drawer) return;

      var hasGSAP = typeof gsap !== 'undefined';

      // ── Open / Close (GSAP enhanced, CSS fallback) ──
      var openTimeline = null;
      function openCart() {
        if (!drawer) return;
        overlay.classList.add('open');
        if (hasGSAP) {
          if (openTimeline) openTimeline.kill();
          openTimeline = gsap.timeline();
          openTimeline.to(overlay, { opacity: 1, duration: 0.3, ease: 'power2.out' }, 0)
                       .to(drawer,   { x: '0%', duration: 0.45, ease: 'power3.out' }, 0);
        } else {
          drawer.style.transform = 'translateX(0%)';
        }
      }
      function closeCart() {
        if (!drawer) return;
        overlay.classList.remove('open');
        if (hasGSAP) {
          gsap.to(drawer,  { x: '100%', duration: 0.35, ease: 'power2.in' });
          gsap.to(overlay, { opacity: 0, duration: 0.3, ease: 'power2.in' });
        } else {
          drawer.style.transform = 'translateX(100%)';
        }
      }
      if (close) close.addEventListener('click', closeCart);
      overlay.addEventListener('click', closeCart);

      window.trylOpenCart  = openCart;
      window.trylCloseCart = closeCart;

      // ── Update nav cart count badge ──
      function updateCounts(count) {
        document.querySelectorAll('.tryl-cart-count').forEach(function(el){
          el.textContent = count;
        });
        document.querySelectorAll('.tryl-cart-count-badge').forEach(function(el){
          el.style.display = count > 0 ? 'flex' : 'none';
        });
      }

      // ── Refresh fragments via AJAX ──
      function refreshCart(callback) {
        fetch(trylMiniCart.ajaxurl + '?action=tryl_refresh_minicart', { method: 'GET' })
        .then(function(r){ return r.json(); })
        .then(function(res){
          if (!res.success) return;
          var itemsEl = document.getElementById('trylMcItems');
          var footerEl = document.getElementById('trylMcFooter');
          var subtotalEl = document.getElementById('trylMcSubtotal');
          var freeShip = document.getElementById('trylMcFreeShip');
          if (itemsEl) itemsEl.innerHTML = res.data.html;
          if (subtotalEl) subtotalEl.innerHTML = res.data.subtotal;
          if (footerEl) footerEl.style.display = res.data.count > 0 ? '' : 'none';
          if (freeShip) freeShip.textContent = res.data.free_ship || '';
          updateCounts(res.data.count);
          bindQtyButtons();
          if (typeof callback === 'function') callback(res);
        });
      }

      // ── AJAX Add to Cart ──
      document.addEventListener('click', function(e) {
        var inlineToggle = e.target.closest('.tryl-atc-inline-toggle');
        if (inlineToggle) {
            var wrap = inlineToggle.closest('.tryl-inline-var-wrapper');
            var drop = wrap.querySelector('.tryl-inline-var-dropdown');
            var isVis = drop.style.display === 'block';
            document.querySelectorAll('.tryl-inline-var-dropdown').forEach(function(d){ d.style.display = 'none'; });
            drop.style.display = isVis ? 'none' : 'block';
            return;
        }

        if (!e.target.closest('.tryl-inline-var-wrapper')) {
            document.querySelectorAll('.tryl-inline-var-dropdown').forEach(function(d){ d.style.display = 'none'; });
        }

        var btn = e.target.closest('.tryl-atc, .tryl-atc-variation');
        if (!btn || btn.classList.contains('tryl-atc-choose') || btn.classList.contains('tryl-atc-inline-toggle')) return;
        if (btn.tagName === 'A') e.preventDefault();
        if (btn.classList.contains('loading')) return;

        var pid = btn.dataset.pid;
        var vid = btn.dataset.vid;
        if (!pid) return;

        btn.classList.add('loading');
        if (vid) {
            var drop = btn.closest('.tryl-inline-var-dropdown');
            if (drop) drop.style.display = 'none';
        }

        var fd = new FormData();
        fd.append('action', 'tryl_ajax_add_to_cart');
        fd.append('product_id', pid);
        fd.append('quantity', 1);
        if (vid) fd.append('variation_id', vid);

        fetch(trylMiniCart.ajaxurl, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(fd) })
        .then(function(r){ return r.json(); })
        .then(function(res){
          btn.classList.remove('loading');
          if (res.success) {
            btn.classList.add('added');
            var span = btn.querySelector('span');
            var ogText = span ? span.textContent : btn.textContent;
            
            if (span) span.textContent = trylMiniCart.btnText || 'Added!';
            else btn.textContent = trylMiniCart.btnText || 'Added!';

            setTimeout(function(){ 
                btn.classList.remove('added'); 
                if (span) span.textContent = ogText;
                else btn.textContent = ogText;
            }, 1500);
            
            refreshCart(function(){ openCart(); });
          } else if (res.data && res.data.product_url) {
            window.location.href = res.data.product_url;
          }
        })
        .catch(function(){ btn.classList.remove('loading'); });
      });

      // ── AJAX Add to Cart for Single Product Page Forms ──
      document.addEventListener('submit', function(e) {
        var form = e.target.closest('form.cart');
        if (!form) return;
        e.preventDefault();

        var btn = form.querySelector('button[type="submit"]');
        if (btn && btn.classList.contains('loading')) return;
        
        if (btn) {
          btn.classList.add('loading');
          btn.dataset.ogText = btn.textContent;
          btn.textContent = 'Adding...';
        }

        var fd = new FormData(form);
        if (btn && btn.name && btn.value && !fd.has(btn.name)) {
          fd.append(btn.name, btn.value);
        } else if (!fd.has('add-to-cart') && btn && btn.value) {
          fd.append('add-to-cart', btn.value);
        }

        fetch(window.location.href, { method: 'POST', body: fd })
        .then(function(r){ return r.text(); })
        .then(function(html) {
          // Catch WooCommerce validation errors (e.g., missing size selection)
          var doc = new DOMParser().parseFromString(html, 'text/html');
          var errorEl = doc.querySelector('.woocommerce-error');
          if (errorEl) {
            if (btn) { btn.classList.remove('loading'); btn.textContent = btn.dataset.ogText; }
            alert(errorEl.textContent.trim());
            return;
          }
          
          if (btn) {
            btn.classList.remove('loading');
            btn.classList.add('added');
            btn.textContent = trylMiniCart.btnText || 'Added!';
            setTimeout(function(){ 
                btn.classList.remove('added'); 
                btn.textContent = btn.dataset.ogText; 
            }, 1800);
          }
          
          if (typeof refreshCart === 'function') { refreshCart(function(){ openCart(); }); }
        })
        .catch(function(){ if (btn) btn.classList.remove('loading'); form.submit(); });
      });

      // ── Cart qty / remove handlers ──
      function bindQtyButtons() {
        document.querySelectorAll('.tryl-mc-qty-dec, .tryl-mc-qty-inc').forEach(function(el) {
          el.onclick = function(e) {
            var b = e.target.closest('button');
            if (!b) return;
            var key = b.dataset.key;
            var inc = b.classList.contains('tryl-mc-qty-inc');
            var num = b.parentElement.querySelector('.tryl-mc-qty-num');
            var cur = parseInt(num.textContent) || 1;
            var qty = inc ? cur + 1 : Math.max(0, cur - 1);
            updateQty(key, qty);
          };
        });
        document.querySelectorAll('.tryl-mc-item-remove').forEach(function(el) {
          el.onclick = function(e) {
            var b = e.target.closest('button');
            if (b) updateQty(b.dataset.key, 0);
          };
        });
      }

      function updateQty(key, qty) {
        var fd = new FormData();
        fd.append('action', 'tryl_update_cart');
        fd.append('cart_key', key);
        fd.append('quantity', qty);
        fetch(trylMiniCart.ajaxurl, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(fd) })
        .then(function(r){ return r.json(); })
        .then(function(res){ if (res.success) refreshCart(); });
      }

      bindQtyButtons();

      // Auto-open drawer on page load if a Woo success notice exists
      var wooMsg = document.querySelector('.woocommerce-message');
      if (wooMsg && (wooMsg.textContent.indexOf('added') !== -1 || wooMsg.textContent.indexOf('Added') !== -1)) {
          setTimeout(function() { openCart(); }, 400);
      }
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'tryl_mini_cart_html' );

// ─── 8. AJAX HANDLERS ──────────────────────────────────────────────────────────
function tryl_ajax_add_to_cart_handler() {
    if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error( [ 'message' => 'WooCommerce inactive.' ] );

    $product_id = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;
    $quantity   = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 1;

    if ( ! $product_id ) {
        wp_send_json_error( [ 'message' => 'Invalid product.' ] );
    }

    $product = wc_get_product( $product_id );
    if ( ! $product ) {
        wp_send_json_error( [ 'message' => 'Product not found.' ] );
    }

    // Variable products should redirect to product page
    if ( $product->is_type( 'variable' ) ) {
        wp_send_json_error( [
            'message'     => 'Please choose options.',
            'product_url' => get_permalink( $product_id ),
        ] );
    }

    $added = WC()->cart->add_to_cart( $product_id, $quantity );
    if ( $added ) {
        wp_send_json_success( [ 'message' => 'Added to cart.' ] );
    } else {
        wp_send_json_error( [ 'message' => 'Could not add to cart.' ] );
    }
}
add_action( 'wp_ajax_tryl_ajax_add_to_cart', 'tryl_ajax_add_to_cart_handler' );
add_action( 'wp_ajax_nopriv_tryl_ajax_add_to_cart', 'tryl_ajax_add_to_cart_handler' );

function tryl_update_cart_handler() {
    if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error( [ 'message' => 'WooCommerce inactive.' ] );

    $cart_key = isset( $_POST['cart_key'] ) ? sanitize_text_field( $_POST['cart_key'] ) : '';
    $quantity = isset( $_POST['quantity'] ) ? (int) $_POST['quantity'] : 0;

    if ( $quantity > 0 ) {
        WC()->cart->set_quantity( $cart_key, $quantity );
    } else {
        WC()->cart->remove_cart_item( $cart_key );
    }

    wp_send_json_success( [ 'message' => 'Cart updated.' ] );
}
add_action( 'wp_ajax_tryl_update_cart', 'tryl_update_cart_handler' );
add_action( 'wp_ajax_nopriv_tryl_update_cart', 'tryl_update_cart_handler' );

function tryl_refresh_minicart_handler() {
    if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error( [ 'success' => false ] );

    ob_start();
    $count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $subtotal = WC()->cart ? WC()->cart->get_cart_subtotal() : '$0.00';
    $items    = WC()->cart ? WC()->cart->get_cart() : [];

    if ( empty( $items ) ): ?>
    <div class="tryl-mc-empty">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      <p>Your cart is empty</p>
    </div>
    <?php else:
      foreach ( $items as $key => $item ):
        $prod = $item['data'];
        $img  = wp_get_attachment_image_url( $prod->get_image_id(), 'thumbnail' ) ?: wc_placeholder_img_src();
    ?>
    <div class="tryl-mc-item" data-key="<?php echo esc_attr($key); ?>">
      <div class="tryl-mc-item-img"><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($prod->get_name()); ?>"></div>
      <div class="tryl-mc-item-info">
        <div class="tryl-mc-item-name"><?php echo esc_html($prod->get_name()); ?></div>
        <?php if ( ! empty( $item['variation'] ) ): ?>
        <div class="tryl-mc-item-meta"><?php echo wc_get_formatted_cart_item_data( $item ); ?></div>
        <?php endif; ?>
        <div class="tryl-mc-item-bottom">
          <div class="tryl-mc-item-qty">
            <button class="tryl-mc-qty-btn tryl-mc-qty-dec" data-key="<?php echo esc_attr($key); ?>">&minus;</button>
            <span class="tryl-mc-qty-num"><?php echo $item['quantity']; ?></span>
            <button class="tryl-mc-qty-btn tryl-mc-qty-inc" data-key="<?php echo esc_attr($key); ?>">+</button>
          </div>
          <span class="tryl-mc-item-price"><?php echo wp_kses_post(WC()->cart->get_product_price( $prod )); ?></span>
          <button class="tryl-mc-item-remove" data-key="<?php echo esc_attr($key); ?>" aria-label="Remove">&times;</button>
        </div>
      </div>
    </div>
    <?php endforeach; endif;

    $html = ob_get_clean();

    wp_send_json_success( [
        'html'      => $html,
        'subtotal'  => $subtotal,
        'count'     => $count,
        'free_ship' => $count > 0 ? 'Free shipping on orders over $' . esc_html(get_option('tryl_free_shipping_threshold', '75')) : '',
    ] );
}
add_action( 'wp_ajax_tryl_refresh_minicart', 'tryl_refresh_minicart_handler' );
add_action( 'wp_ajax_nopriv_tryl_refresh_minicart', 'tryl_refresh_minicart_handler' );

function tryl_load_more_core_grid_handler() {
    if ( ! class_exists( 'WooCommerce' ) ) wp_send_json_error();
    
    $page = isset($_POST['page']) ? (int) $_POST['page'] : 2;
    $limit = get_option('tryl_shop_grid_limit', 32);
    
    $query = wc_get_products( [ 'status' => 'publish', 'limit' => $limit, 'page' => $page, 'paginate' => true, 'return' => 'objects' ] );
    $products = $query->products;
    
    ob_start();
    foreach($products as $product) {
        echo tryl_get_core_product_card_html($product);
    }
    $html = ob_get_clean();
    wp_send_json_success( ['html' => $html] );
}
add_action( 'wp_ajax_tryl_load_more_core_grid', 'tryl_load_more_core_grid_handler' );
add_action( 'wp_ajax_nopriv_tryl_load_more_core_grid', 'tryl_load_more_core_grid_handler' );

// ─── 9. LOCALIZE MINI CART SCRIPT ────────────────────────────────────────────
function tryl_localize_minicart() {
    if ( ! tryl_should_load_mini_cart() ) return;
    ?>
    <script>
    var trylMiniCart = { 
        ajaxurl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
        btnText: '<?php echo esc_js( get_option('tryl_atc_btn_text', 'Added!') ); ?>'
    };
    </script>
    <?php
}
// add_action( 'wp_head', 'tryl_localize_minicart' ); // Safely extracted to tryl_core_enqueue_assets

// ─── 10. NEXT-GEN WOOCOMMERCE EMAILS ─────────────────────────────────────────
if ( get_option( 'tryl_nextgen_emails_active' ) ) {
    add_filter( 'woocommerce_email_styles', 'tryl_nextgen_email_styles', 99, 2 );
    add_action( 'woocommerce_email_header', 'tryl_nextgen_email_hero_image', 99, 2 );
    add_filter( 'woocommerce_email_footer_text', 'tryl_nextgen_email_footer_text', 99 );
}

function tryl_nextgen_email_styles( $css, $email ) {
    return "
        /* TRYL Premium Next-Gen Email Overrides */
        body, table, td, p, a, h1, h2, h3, h4, h5, h6 { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important; }
        body { background-color: #f5f8f5 !important; }
        #wrapper { background-color: #f5f8f5 !important; padding: 40px 0 !important; }
        #template_container { background-color: #ffffff !important; border: 1px solid #d4e0d4 !important; box-shadow: 0 16px 40px rgba(0,0,0,0.06) !important; border-radius: 0 !important; max-width: 600px !important; }
        #template_header { background-color: #0d1b0f !important; color: #ffffff !important; border-bottom: none !important; border-radius: 0 !important; padding: 36px 48px !important; }
        #template_header h1 { color: #ffffff !important; font-family: 'Arial Black', 'Arial Bold', Gadget, sans-serif !important; text-transform: uppercase !important; letter-spacing: 0.15em !important; font-size: 22px !important; font-weight: 900 !important; margin: 0 !important; text-align: center !important; }
        #template_body { padding: 40px 48px !important; }
        #template_body h2, #template_body h3 { color: #0d1b0f !important; text-transform: uppercase !important; letter-spacing: 0.08em !important; font-weight: 800 !important; border-bottom: 2px solid #0d1b0f !important; padding-bottom: 12px !important; margin-bottom: 24px !important; font-size: 16px !important; }
        table.td { border: 1px solid #e8f0e8 !important; padding: 16px !important; }
        table.td th { background-color: #f5f8f5 !important; color: #6b7c6b !important; text-transform: uppercase !important; font-size: 11px !important; letter-spacing: 0.1em !important; padding: 12px !important; }
        #template_footer { padding: 30px 48px 40px !important; }
        #template_footer_text { color: #8a9c8a !important; font-size: 11px !important; text-align: center !important; text-transform: uppercase !important; letter-spacing: 0.1em !important; line-height: 1.6 !important; }
        a { color: #31d190 !important; font-weight: bold !important; text-decoration: none !important; }
    ";
}

function tryl_nextgen_email_hero_image( $email_heading, $email ) {
    $hero_url = get_option('tryl_email_hero_url');
    if ( ! empty( $hero_url ) ) {
        echo '<div style="background-color:#ffffff; text-align:center;"><img src="' . esc_url($hero_url) . '" alt="The Righteous Yield Life" style="width:100%; max-width:600px; height:auto; display:block; border-bottom:1px solid #e8f0e8;" /></div>';
    }
}

function tryl_nextgen_email_footer_text( $text ) {
    $custom_msg = get_option('tryl_email_footer_msg', 'Yield righteousness in all that you do.');
    return esc_html( $custom_msg ) . '<br><br><strong>THE RIGHTEOUS YIELD LIFE</strong>';
}

// ─── 11. EXIT-INTENT NEWSLETTER POPUP ────────────────────────────────────────
function tryl_exit_intent_popup() {
    if ( ! get_option( 'tryl_popup_active' ) ) return;
    
    $heading = get_option( 'tryl_popup_heading', 'Wait, don\'t leave!' );
    $text = get_option( 'tryl_popup_text', 'Sign up for our newsletter to get 10% off your first order and updates on new drops.' );
    $action_url = get_option( 'tryl_popup_action_url', '#' );
    $btn_text = get_option( 'tryl_popup_btn_text', 'Subscribe' );
    ?>
    <div class="tryl-popup-overlay" id="trylExitPopup">
        <div class="tryl-popup-content">
            <button class="tryl-popup-close" id="trylPopupClose" aria-label="Close">&times;</button>
            <h3 class="tryl-popup-heading"><?php echo esc_html( $heading ); ?></h3>
            <p class="tryl-popup-text"><?php echo esc_html( $text ); ?></p>
            <form action="<?php echo esc_url( $action_url ); ?>" method="POST" target="_blank" class="tryl-popup-form">
                <input type="email" name="EMAIL" placeholder="Enter your email address" class="tryl-popup-input" required>
                <button type="submit" class="tryl-popup-btn"><?php echo esc_html( $btn_text ); ?></button>
            </form>
        </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'tryl_exit_intent_popup' );

// ─── 11. PRAYER REQUEST SYSTEM & SHORTCODES ──────────────────────────────────
if ( ! function_exists( 'tryl_register_prayer_cpt' ) ) {
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
        'menu_icon' => 'dashicons-heart',
        'menu_position' => 25,
        'supports' => ['title'],
        'capabilities' => [ 'create_posts' => false ],
        'map_meta_cap' => true,
    ]);
}
}
add_action('init', 'tryl_register_prayer_cpt');

if ( ! function_exists( 'tryl_prayer_meta_box' ) ) {
function tryl_prayer_meta_box() {
    add_meta_box('tryl_prayer_details', 'Prayer Details & Response', 'tryl_prayer_meta_box_html', 'prayer_request', 'normal', 'high');
}
}
add_action('add_meta_boxes', 'tryl_prayer_meta_box');

if ( ! function_exists( 'tryl_prayer_meta_box_html' ) ) {
function tryl_prayer_meta_box_html($post) {
    $name   = get_post_meta($post->ID, '_prayer_name', true);
    $email  = get_post_meta($post->ID, '_prayer_email', true);
    $status = get_post_meta($post->ID, '_prayer_status', true);
    $reply  = get_post_meta($post->ID, '_prayer_reply', true);
    $is_public = get_post_meta($post->ID, '_prayer_public', true);
    
    wp_nonce_field('tryl_save_prayer_reply', 'tryl_prayer_reply_nonce');

    echo '<div style="padding: 10px 0; font-size: 14px;">';
    echo '<p><strong>From:</strong> ' . esc_html($name) . ( $email ? ' (' . esc_html($email) . ')' : '' ) . '</p>';
    echo '<div style="background:#f5f8f5; padding:15px; border-left:4px solid #2d6a4f; margin:15px 0;"><strong>The Prayer:</strong><br/><br/>' . nl2br(esc_html($post->post_content)) . '</div>';

    if ( empty($email) ) {
        echo '<p style="color:#d63638;"><em>The user did not provide an email address, so you cannot reply directly from here.</em></p>';
        echo '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #c3c4c7;" />';
        echo '<p><label><input type="checkbox" name="prayer_public" value="yes" ' . checked($is_public, 'yes', false) . ' /> <strong>Approve for Public Prayer Wall (Anonymous)</strong></label></p>';
        echo '<p><button type="submit" class="button button-secondary">Save Settings</button></p>';
    } elseif ( $status === 'replied' ) {
        echo '<p style="color: #007017;"><strong>&check; You replied to this request:</strong></p>';
        echo '<div style="background:#fff; border:1px solid #c3c4c7; padding:15px;"><em>' . nl2br(esc_html($reply)) . '</em></div>';
        echo '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #c3c4c7;" />';
        echo '<p><label><input type="checkbox" name="prayer_public" value="yes" ' . checked($is_public, 'yes', false) . ' /> <strong>Approve for Public Prayer Wall (Anonymous)</strong></label></p>';
        echo '<p><button type="submit" class="button button-secondary">Save Settings</button></p>';
    } else {
        echo '<p><strong>Write a Response (This will be emailed directly to them):</strong></p>';
        echo '<textarea name="prayer_reply_message" rows="5" style="width:100%; border:1px solid #8c8f94; border-radius:4px; padding:10px;"></textarea>';
        echo '<hr style="margin: 20px 0; border: 0; border-top: 1px solid #c3c4c7;" />';
        echo '<p><label><input type="checkbox" name="prayer_public" value="yes" ' . checked($is_public, 'yes', false) . ' /> <strong>Approve for Public Prayer Wall (Anonymous)</strong></label></p>';
        echo '<p><button type="submit" class="button button-primary button-large" style="background:#0d1b0f; border-color:#0d1b0f;">Send Reply & Save</button></p>';
    }
    echo '</div>';
}
}

if ( ! function_exists( 'tryl_save_prayer_reply' ) ) {
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

    if (isset($_POST['prayer_public']) && $_POST['prayer_public'] === 'yes') {
        update_post_meta($post_id, '_prayer_public', 'yes');
    } else {
        delete_post_meta($post_id, '_prayer_public');
    }
}
}

// ─── 17. FLOATING CHECKOUT CTA ───────────────────────────────────────────────
function tryl_floating_checkout_cta() {
    if ( get_option('tryl_floating_checkout_active', '1') !== '1' ) return;
    
    // Don't show on Cart or Checkout pages to avoid redundancy
    if ( function_exists('is_checkout') && (is_checkout() || is_cart()) ) return;
    
    // Only show if there are items in the cart
    if ( ! function_exists('WC') || ! WC()->cart || WC()->cart->get_cart_contents_count() === 0 ) return;

    $checkout_url = get_option('tryl_nav_checkout');
    if ( empty( $checkout_url ) ) {
        $checkout_url = function_exists('wc_get_checkout_url') ? wc_get_checkout_url() : home_url('/checkout/');
    }
    
    ?>
    <a href="<?php echo esc_url($checkout_url); ?>" class="tryl-floating-checkout-btn" aria-label="Proceed to Checkout">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 9l2-4h10l2 4"/><path d="M3 9h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><line x1="12" y1="12" x2="12" y2="16"/></svg>
        Checkout Now
    </a>
    <?php
}
add_action( 'wp_footer', 'tryl_floating_checkout_cta' );
add_action('save_post_prayer_request', 'tryl_save_prayer_reply');

if ( ! function_exists( 'tryl_prayer_request_columns' ) ) {
function tryl_prayer_request_columns($columns) {
    return ['cb' => $columns['cb'], 'title' => 'Requester', 'prayer_status' => 'Status', 'prayer_public' => 'Public Wall', 'date' => 'Date Received'];
}
}
add_filter('manage_prayer_request_posts_columns', 'tryl_prayer_request_columns');

if ( ! function_exists( 'tryl_prayer_request_custom_column' ) ) {
function tryl_prayer_request_custom_column($column, $post_id) {
    if ($column === 'prayer_status') {
        $status = get_post_meta($post_id, '_prayer_status', true);
        echo $status === 'replied' ? '<span style="color:#007017; font-weight:bold;">&check; Replied</span>' : '<span style="color:#d63638; font-weight:bold;">Pending</span>';
    }
    if ($column === 'prayer_public') {
        $is_public = get_post_meta($post_id, '_prayer_public', true);
        echo $is_public === 'yes' ? '<span style="color:#2d6a4f; font-weight:bold;">Yes</span>' : '<span style="color:#8c8f94;">No</span>';
    }
}
}
add_action('manage_prayer_request_posts_custom_column', 'tryl_prayer_request_custom_column', 10, 2);

if ( ! function_exists( 'tryl_handle_prayer_request_submission' ) ) {
function tryl_handle_prayer_request_submission() {
    if ( isset($_POST['tryl_submit_prayer_request']) ) {
        if ( ! isset( $_POST['tryl_prayer_nonce'] ) || ! wp_verify_nonce( $_POST['tryl_prayer_nonce'], 'tryl_prayer_action' ) ) {
            wp_die( 'Security check failed. Please try again.' );
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
        $message .= "\nPrayer:\n$prayer\n\n--\nSubmitted via TRYL Website. You can reply directly from your WordPress 'Prayers' dashboard!";
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
                $auto_sub = get_option('tryl_prayer_auto_sub', 'We received your prayer request');
                $default_msg = "Hi {name},\n\nThank you for reaching out to us. We have received your prayer request and our team is standing in faith with you.\n\n\"For where two or three gather in my name, there am I with them.\" - Matthew 18:20\n\nBlessings,\nThe Righteous Yield Life Team";
                $auto_msg = get_option('tryl_prayer_auto_msg', $default_msg);
                $auto_msg = str_replace('{name}', $name, $auto_msg);
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
}
add_action( 'admin_post_nopriv_submit_prayer_request', 'tryl_handle_prayer_request_submission' );
add_action( 'admin_post_submit_prayer_request', 'tryl_handle_prayer_request_submission' );

if ( ! function_exists( 'tryl_prayer_form_shortcode' ) ) {
function tryl_prayer_form_shortcode() {
    ob_start();
    if ( isset( $_GET['prayer_status'] ) ) {
        if ( $_GET['prayer_status'] === 'success' ) echo '<div style="background:#e8f0e8; color:#2d6a4f; padding:16px; border-left:4px solid #2d6a4f; margin-bottom:24px; font-family:\'Inter\', sans-serif;">Thank you. Your prayer request has been securely received by our team.</div>';
        elseif ( $_GET['prayer_status'] === 'empty' ) echo '<div style="background:#fde8e8; color:#9b1c1c; padding:16px; border-left:4px solid #9b1c1c; margin-bottom:24px; font-family:\'Inter\', sans-serif;">Please fill in both your name and your prayer message.</div>';
        elseif ( $_GET['prayer_status'] === 'error' ) echo '<div style="background:#fde8e8; color:#9b1c1c; padding:16px; border-left:4px solid #9b1c1c; margin-bottom:24px; font-family:\'Inter\', sans-serif;">There was an issue sending your request. Please try again later.</div>';
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
function tryl_register_shortcodes() {
    // ── CORE SHORTCODES ──
    add_shortcode( 'tryl_3d_shop', 'tryl_core_3d_shop_shortcode' ); // Legacy/Core Grid
    add_shortcode( 'tryl_hero', 'tryl_hero_section_shortcode' ); // Premium Hero
    
    // ── PRAYER SYSTEM ──
    add_shortcode( 'tryl_prayer_form', 'tryl_prayer_form_shortcode' );
    add_shortcode( 'tryl_prayer_request', 'tryl_prayer_form_shortcode' ); // Alias for convenience
    add_shortcode( 'tryl_prayer_wall', 'tryl_prayer_wall_shortcode' );
}
// Hook with early priority (5) to ensure registration before most page builders process content
add_action( 'init', 'tryl_register_shortcodes', 5 );


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
            echo '<div class="tryl-prayer-author" style="font-family:var(--tryl-header-font, sans-serif); text-transform:uppercase; color:var(--accent, #2d6a4f); font-weight:700; letter-spacing:0.08em; font-size:0.9rem;">&mdash; ' . esc_html( $first_name ) . '</div>';
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


if ( ! function_exists( 'tryl_hero_section_shortcode' ) ) {
function tryl_hero_section_shortcode( $atts ) {
    $image_url = get_option( 'tryl_hero_image' ) ?: 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=800&q=80';
    $text_left  = get_option( 'tryl_hero_text_left', 'The Righteous' );
    $text_right = get_option( 'tryl_hero_text_right', 'Yield Life' );
    $btn_text   = get_option( 'tryl_hero_btn_text' );
    if ( $btn_text === false ) $btn_text = 'Shop Collection';
    $btn_url    = get_option( 'tryl_hero_btn_url' );
    if ( empty($btn_url) ) {
        $btn_url = get_option('tryl_nav_shop', 'https://therighteousyieldlife.com/the-shop-wip/');
    }
    ob_start();
    ?>
    <style>
    @keyframes trylHeroImgReveal {
        0% { opacity: 0; transform: scale(1.15); filter: blur(12px); }
        100% { opacity: 1; transform: scale(1); filter: blur(0); }
    }
    @keyframes trylHeroTextFade {
        0% { opacity: 0; transform: translateY(20px); }
        100% { opacity: 1; transform: translateY(0); }
    }
    .tryl-hero-img-anim { animation: trylHeroImgReveal 1.6s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
    .tryl-hero-txt-l { animation: trylHeroTextFade 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s forwards; opacity: 0; }
    .tryl-hero-txt-r { animation: trylHeroTextFade 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) 0.5s forwards; opacity: 0; }
    .tryl-hero-btn-wrap { animation: trylHeroTextFade 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) 0.7s forwards; opacity: 0; }
    .tryl-hero-btn { transition: background 0.3s, transform 0.3s; display:inline-block; padding:14px 28px; background:#0d1b0f; color:#fff; font-family:'Inter', sans-serif; font-weight:700; text-transform:uppercase; letter-spacing:0.12em; font-size:0.75rem; text-decoration:none; }
    .tryl-hero-btn:hover { background: #31d190 !important; color: #0d1b0f !important; transform: translateY(-3px); }
    </style>
    <section style="position:relative; width:100%; height:80vh; min-height:500px; display:flex; align-items:center; justify-content:center; background:#fff; overflow:hidden;">
        <!-- SEO Upgrade: Visually hidden semantic H1 tag for web crawlers -->
        <h1 style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(1px,1px,1px,1px);"><?php echo esc_html( $text_left . ' ' . $text_right ); ?></h1>
        <div style="position:relative; display:flex; align-items:center; justify-content:center; width:100%; max-width:1200px;">
            <div class="tryl-hero-txt-l" style="position:absolute; left:2%; z-index:1; font-size:clamp(3.5rem, 6vw, 5.5rem); font-family:var(--tryl-header-font, sans-serif); font-weight:bold; text-transform:uppercase; line-height:0.9; color:#1a2e1a; text-align:right;"><span><?php echo esc_html( $text_left ); ?></span></div>
            <div style="position:relative; z-index:2; width:100%; max-width:450px; aspect-ratio:3/4; overflow:hidden; display:flex; flex-direction:column; align-items:center; justify-content:center;">
                <img src="<?php echo esc_url( $image_url ); ?>" alt="Hero" class="tryl-hero-img-anim" style="width:100%; height:100%; object-fit:cover; display:block;">
                <?php if ( ! empty( $btn_text ) ) : ?>
                <div class="tryl-hero-btn-wrap" style="position:absolute; bottom:30px; z-index:10; text-align:center; width:100%;">
                    <a href="<?php echo esc_url( $btn_url ); ?>" class="tryl-hero-btn"><?php echo esc_html( $btn_text ); ?></a>
                </div>
                <?php endif; ?>
            </div>
            <div class="tryl-hero-txt-r" style="position:absolute; right:2%; z-index:3; font-size:clamp(3.5rem, 6vw, 5.5rem); font-family:var(--tryl-header-font, sans-serif); font-weight:bold; text-transform:uppercase; line-height:0.9; text-align:left;">
                <span style="background:linear-gradient(to right, #004db4, #0e8b70); -webkit-background-clip:text; -webkit-text-fill-color:transparent;"><?php echo esc_html( $text_right ); ?></span>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}
}


// ─── 12. ADMIN DASHBOARD SETTINGS ────────────────────────────────────────────
function tryl_register_admin_page() {
    add_menu_page( 'TRYL Settings', 'TRYL Settings', 'manage_options', 'tryl-ecommerce-settings', 'tryl_admin_page_html', 'dashicons-admin-generic', 58 );
    add_submenu_page( 'tryl-ecommerce-settings', 'Shop Settings', 'Shop Settings', 'manage_options', 'tryl-ecommerce-settings&tab=shop', 'tryl_admin_page_html' );
    add_submenu_page( 'tryl-ecommerce-settings', 'Checkout Settings', 'Checkout Settings', 'manage_options', 'tryl-ecommerce-settings&tab=checkout', 'tryl_admin_page_html' );
}
add_action( 'admin_menu', 'tryl_register_admin_page' );

function tryl_register_settings() {
    $settings = ['tryl_default_theme', 'tryl_font_pack', 'tryl_shop_grid_limit', 'tryl_nav_active', 'tryl_nav_shop', 'tryl_nav_checkout', 'tryl_nav_mission', 'tryl_nav_prayer', 'tryl_nav_contact', 'tryl_nav_shipping', 'tryl_nav_faq', 'tryl_nav_privacy', 'tryl_nav_terms', 'tryl_footer_active', 'tryl_free_shipping_threshold', 'tryl_footer_desc', 'tryl_prayer_email', 'tryl_developer_signature', 'tryl_header_logo', 'tryl_printful_token', 'tryl_printful_sync_enabled', 'tryl_printful_sync_time', 'tryl_printful_auto_publish', 'tryl_printful_inventory_sync', 'tryl_announcement_active', 'tryl_announcement_text', 'tryl_announcement_url', 'tryl_announcement_bg', 'tryl_announcement_text_color', 'tryl_badges_active', 'tryl_badges_new_days', 'tryl_badges_bestseller_sales', 'tryl_badges_bg', 'tryl_badges_text_color', 'tryl_footer_layout_style', 'tryl_footer_hover_anim', 'tryl_footer_mobile_center', 'tryl_popup_active', 'tryl_popup_heading', 'tryl_popup_text', 'tryl_popup_action_url', 'tryl_popup_btn_text', 'tryl_nextgen_emails_active', 'tryl_email_hero_url', 'tryl_email_footer_msg', 'tryl_checkout_animations', 'tryl_hero_image', 'tryl_hero_text_left', 'tryl_hero_text_right', 'tryl_hero_btn_text', 'tryl_hero_btn_url', 'tryl_nike_checkout_active', 'tryl_nike_checkout_accent', 'tryl_nike_checkout_input_bg', 'tryl_premium_products_active', 'tryl_custom_404_active', 'tryl_prayer_auto_sub', 'tryl_prayer_auto_msg', 'tryl_checkout_features_active', 'tryl_gift_wrapping_fee', 'tryl_product_accordion_active', 'tryl_product_accordion_title', 'tryl_product_accordion_content', 'tryl_product_accordion_categories', 'tryl_atc_btn_text', 'tryl_atc_notice_active', 'tryl_atc_notice_text', 'tryl_sp_trust_badges_active', 'tryl_header_checkout_cta', 'tryl_floating_checkout_active', 'tryl_mobile_menu_align', 'tryl_social_instagram', 'tryl_social_tiktok', 'tryl_social_twitter', 'tryl_social_youtube', 'tryl_social_facebook', 'tryl_myaccount_reskin_active', 'tryl_order_bump_active', 'tryl_order_bump_label', 'tryl_order_bump_fee'];
    foreach ($settings as $setting) {
        register_setting('tryl_settings_group', $setting);
    }
}
add_action( 'admin_init', 'tryl_register_settings' );

function tryl_admin_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap tryl-admin-wrap">
        <style>
            :root {
                --tryl-primary: #0d1b0f;
                --tryl-accent: #31d190;
                --tryl-bg: #f5f8f5;
                --tryl-border: #d4e0d4;
                --tryl-card: #ffffff;
                --tryl-text: #1a2e1a;
                --tryl-muted: #6b7c6b;
            }
            .tryl-admin-wrap { max-width: 1200px; margin: 30px auto; font-family: 'Inter', sans-serif; }
            .tryl-admin-layout { display: grid; grid-template-columns: 240px 1fr; gap: 40px; margin-top: 24px; min-height: 700px; }
            @media (max-width: 960px) { .tryl-admin-layout { grid-template-columns: 1fr; } }

            /* Header */
            .tryl-admin-header { background: var(--tryl-primary); padding: 48px; border-radius: 16px; color: #fff; position: relative; overflow: hidden; box-shadow: 0 20px 40px rgba(13,27,15,0.1); }
            .tryl-admin-header::after { content: ''; position: absolute; right: -5%; top: -10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(49,209,144,0.12) 0%, transparent 70%); border-radius: 50%; }
            .tryl-admin-header h1 { margin: 0; color: #fff; font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 3rem; text-transform: uppercase; letter-spacing: 0.02em; line-height: 1; border: none; padding: 0; }
            .tryl-admin-header p { margin: 12px 0 0; color: #8a9c8a; font-size: 1.1rem; max-width: 600px; font-weight: 500; }

            /* Sidebar */
            .tryl-admin-sidebar { position: sticky; top: 60px; }
            .tryl-admin-nav { list-style: none; padding: 0; margin: 0; }
            .tryl-admin-nav-item { margin-bottom: 8px; }
            .tryl-admin-nav-link { 
                display: flex; align-items: center; gap: 12px; padding: 14px 20px; 
                background: #fff; border: 1px solid var(--tryl-border); border-radius: 10px; 
                text-decoration: none; color: var(--tryl-text); font-weight: 600; font-size: 0.9rem;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); cursor: pointer;
            }
            .tryl-admin-nav-link .dashicons { font-size: 20px; color: var(--tryl-muted); transition: color 0.3s; }
            .tryl-admin-nav-link:hover { transform: translateX(5px); border-color: var(--tryl-accent); background: var(--tryl-bg); }
            .tryl-admin-nav-link.active { background: var(--tryl-primary); color: #fff; border-color: var(--tryl-primary); box-shadow: 0 10px 20px rgba(13,27,15,0.15); }
            .tryl-admin-nav-link.active .dashicons { color: var(--tryl-accent); }

            /* Content Area */
            .tryl-admin-main { position: relative; }
            .tryl-tab-content { display: none; }
            .tryl-tab-content.active { display: block; animation: trylTabFade 0.5s ease forwards; }
            @keyframes trylTabFade { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

            /* Cards */
            .tryl-admin-card { background: #fff; border: 1px solid var(--tryl-border); border-radius: 16px; padding: 40px; margin-bottom: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
            .tryl-admin-card h2 { margin: 0 0 32px; font-size: 1.4rem; font-weight: 800; color: var(--tryl-primary); display: flex; align-items: center; gap: 12px; font-family: 'Barlow Condensed', sans-serif; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--tryl-bg); padding-bottom: 16px; }
            .tryl-admin-card h2 .dashicons { font-size: 24px; width: 24px; height: 24px; color: var(--tryl-accent); }

            /* Form Elements */
            .tryl-admin-row { margin-bottom: 32px; }
            .tryl-admin-row:last-child { margin-bottom: 0; }
            .tryl-admin-row label { display: block; font-weight: 700; margin-bottom: 10px; color: var(--tryl-text); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.06em; }
            .tryl-admin-row input[type="text"], .tryl-admin-row input[type="number"], .tryl-admin-row input[type="email"], .tryl-admin-row textarea, .tryl-admin-row select {
                width: 100%; border: 2.5px solid var(--tryl-bg); border-radius: 10px; padding: 14px 18px; 
                font-family: inherit; font-size: 1rem; color: var(--tryl-primary); background: var(--tryl-bg); 
                transition: all 0.25s; box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
            }
            .tryl-admin-row input:focus, .tryl-admin-row textarea:focus, .tryl-admin-row select:focus { border-color: var(--tryl-accent); background: #fff; outline: none; box-shadow: 0 0 0 4px rgba(49,209,144,0.1); }
            .tryl-admin-row p.description { margin-top: 10px; font-size: 0.88rem; color: var(--tryl-muted); line-height: 1.6; }

            /* Toggles */
            .tryl-toggle-wrap { display: flex; align-items: center; justify-content: space-between; background: var(--tryl-bg); padding: 20px; border-radius: 12px; margin-bottom: 16px; }
            .tryl-toggle-wrap label { margin-bottom: 0; text-transform: none; font-size: 1rem; cursor: pointer; }
            .tryl-toggle { appearance: none; -webkit-appearance: none; width: 50px; height: 26px; background: #cdd9cd; border-radius: 24px; position: relative; cursor: pointer; outline: none; transition: background 0.3s; flex-shrink: 0; border: none; margin: 0 !important; }
            .tryl-toggle::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s; }
            .tryl-toggle:checked { background: var(--tryl-accent); }
            .tryl-toggle:checked::after { transform: translateX(24px); }

            /* Sticky Save */
            .tryl-save-btn { 
                position: fixed; bottom: 40px; right: 40px; z-index: 1000;
                background: var(--tryl-primary); color: #fff; border: none; padding: 18px 48px; 
                border-radius: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; 
                cursor: pointer; box-shadow: 0 20px 40px rgba(13,27,15,0.25); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: flex; align-items: center; gap: 12px; font-size: 1rem;
            }
            .tryl-save-btn:hover { background: var(--tryl-accent); color: var(--tryl-primary); transform: translateY(-5px) scale(1.05); }
            
            /* Guide Styling */
            .tryl-guide-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; }
            .tryl-guide-card { background: var(--tryl-bg); padding: 24px; border-radius: 12px; }
            .tryl-guide-card h3 { margin-top: 0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--tryl-accent); border-bottom: 1px solid var(--tryl-border); padding-bottom: 8px; margin-bottom: 16px; }
            .tryl-url-box { background: #fff; padding: 12px; border-radius: 8px; border: 1px solid var(--tryl-border); font-family: monospace; font-size: 0.85rem; margin-top: 12px; word-break: break-all; }
        </style>

        <div class="tryl-admin-header">
            <h1>TRYL Ecosystem Settings</h1>
            <p>Master control for the technical minimalist e-commerce platform.</p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields( 'tryl_settings_group' ); ?>
            
            <div class="tryl-admin-layout">
                <aside class="tryl-admin-sidebar">
                    <nav class="tryl-admin-nav">
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link active" data-tab="general"><span class="dashicons dashicons-admin-generic"></span> General</a></div>
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link" data-tab="shop"><span class="dashicons dashicons-cart"></span> Shop Settings</a></div>
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link" data-tab="checkout"><span class="dashicons dashicons-money-alt"></span> Checkout Flow</a></div>
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link" data-tab="design"><span class="dashicons dashicons-format-image"></span> Store Design</a></div>
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link" data-tab="marketing"><span class="dashicons dashicons-email-alt"></span> Marketing</a></div>
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link" data-tab="integrations"><span class="dashicons dashicons-rest-api"></span> APIs & Tools</a></div>
                        <div class="tryl-admin-nav-item"><a class="tryl-admin-nav-link" data-tab="docs"><span class="dashicons dashicons-welcome-learn-more"></span> Documentation</a></div>
                    </nav>
                </aside>

                <main class="tryl-admin-main">
            
                    <!-- 1. GENERAL TAB -->
                    <div id="tab-general" class="tryl-tab-content active">
                        <div class="tryl-admin-card">
                            <h2><span class="dashicons dashicons-admin-settings"></span> System Configuration</h2>
                            <div class="tryl-admin-row">
                                <label>Master Theme Mode</label>
                                <select name="tryl_default_theme">
                                    <option value="bright" <?php selected(get_option('tryl_default_theme', 'bright'), 'bright'); ?>>Bright Mode (Default)</option>
                                    <option value="mild" <?php selected(get_option('tryl_default_theme', 'bright'), 'mild'); ?>>Mild Mode</option>
                                    <option value="dark" <?php selected(get_option('tryl_default_theme', 'bright'), 'dark'); ?>>Dark Mode</option>
                                </select>
                            </div>
                            <div class="tryl-admin-row">
                                <label>Typography Pack</label>
                                <select name="tryl_font_pack">
                                    <option value="default" <?php selected(get_option('tryl_font_pack', 'default'), 'default'); ?>>TRYL Signature (Barlow Condensed & Inter)</option>
                                    <option value="editorial" <?php selected(get_option('tryl_font_pack', 'default'), 'editorial'); ?>>Editorial Luxury (Cormorant Garamond & Inter)</option>
                                    <option value="technical" <?php selected(get_option('tryl_font_pack', 'default'), 'technical'); ?>>Modern Tech (Oswald & Roboto)</option>
                                    <option value="minimalist" <?php selected(get_option('tryl_font_pack', 'default'), 'minimalist'); ?>>Clean Minimalist (Montserrat & Open Sans)</option>
                                </select>
                            </div>
                            <div class="tryl-admin-row">
                                <label>Header Logo URL</label>
                                <input type="text" name="tryl_header_logo" value="<?php echo esc_attr(get_option('tryl_header_logo')); ?>" />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Master Checkout URL</label>
                                <input type="text" name="tryl_nav_checkout" value="<?php echo esc_attr(get_option('tryl_nav_checkout', home_url('/checkout/'))); ?>" />
                                <p class="description">Current Default: <code><?php echo home_url('/checkout/'); ?></code></p>
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2><span class="dashicons dashicons-layout"></span> Theme Overrides</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_premium_products_active">Enable Premium Product Templates</label>
                                <input type="hidden" name="tryl_premium_products_active" value="0" />
                                <input type="checkbox" id="tryl_premium_products_active" class="tryl-toggle" name="tryl_premium_products_active" value="1" <?php checked(get_option('tryl_premium_products_active', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_custom_404_active">Enable Custom 404 Experience</label>
                                <input type="hidden" name="tryl_custom_404_active" value="0" />
                                <input type="checkbox" id="tryl_custom_404_active" class="tryl-toggle" name="tryl_custom_404_active" value="1" <?php checked(get_option('tryl_custom_404_active', '1'), '1'); ?> />
                            </div>
                        </div>
                    </div>

                    <!-- 2. DESIGN TAB -->
                    <div id="tab-design" class="tryl-tab-content">
                        <div class="tryl-admin-card">
                            <h2>Product Page Layout</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_product_accordion_active">Enable Global Sizing/Fit Accordion</label>
                                <input type="hidden" name="tryl_product_accordion_active" value="0" />
                                <input type="checkbox" id="tryl_product_accordion_active" class="tryl-toggle" name="tryl_product_accordion_active" value="1" <?php checked(get_option('tryl_product_accordion_active'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Accordion Title</label>
                                <input type="text" name="tryl_product_accordion_title" value="<?php echo esc_attr(get_option('tryl_product_accordion_title', 'Sizing & Fit Guide')); ?>" />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Accordion Content (HTML supported)</label>
                                <textarea name="tryl_product_accordion_content" rows="3"><?php echo esc_textarea(get_option('tryl_product_accordion_content', 'Fits true to size. Order your normal size.')); ?></textarea>
                            </div>
                            <div class="tryl-admin-row">
                                <label>Restrict to Categories (Optional)</label>
                                <input type="text" name="tryl_product_accordion_categories" value="<?php echo esc_attr(get_option('tryl_product_accordion_categories')); ?>" placeholder="e.g. shirts, hoodies" />
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Hero Section</h2>
                            <div class="tryl-admin-row">
                                <label>Portrait Image URL</label>
                                <input type="text" name="tryl_hero_image" value="<?php echo esc_attr(get_option('tryl_hero_image')); ?>" />
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="tryl-admin-row"><label>Heading Left</label><input type="text" name="tryl_hero_text_left" value="<?php echo esc_attr(get_option('tryl_hero_text_left', 'The Righteous')); ?>" /></div>
                                <div class="tryl-admin-row"><label>Heading Right</label><input type="text" name="tryl_hero_text_right" value="<?php echo esc_attr(get_option('tryl_hero_text_right', 'Yield Life')); ?>" /></div>
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Announcement Bar</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_announcement_active">Enable Announcement Bar</label>
                                <input type="hidden" name="tryl_announcement_active" value="0" />
                                <input type="checkbox" id="tryl_announcement_active" class="tryl-toggle" name="tryl_announcement_active" value="1" <?php checked(get_option('tryl_announcement_active'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row"><label>Message Text</label><input type="text" name="tryl_announcement_text" value="<?php echo esc_attr(get_option('tryl_announcement_text')); ?>" /></div>
                            <div class="tryl-admin-row"><label>Click URL (Optional)</label><input type="text" name="tryl_announcement_url" value="<?php echo esc_attr(get_option('tryl_announcement_url')); ?>" placeholder="https://..." /></div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="tryl-admin-row"><label>Background Color</label><input type="color" name="tryl_announcement_bg" value="<?php echo esc_attr(get_option('tryl_announcement_bg', '#31d190')); ?>" /></div>
                                <div class="tryl-admin-row"><label>Text Color</label><input type="color" name="tryl_announcement_text_color" value="<?php echo esc_attr(get_option('tryl_announcement_text_color', '#0d1b0f')); ?>" /></div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. SHOP TAB -->
                    <div id="tab-shop" class="tryl-tab-content">
                        <div class="tryl-admin-card">
                            <h2><span class="dashicons dashicons-grid-view"></span> Shop Grid Settings</h2>
                            <div class="tryl-admin-row">
                                <label>Shop Grid Product Limit</label>
                                <input type="number" name="tryl_shop_grid_limit" value="<?php echo esc_attr(get_option('tryl_shop_grid_limit', 32)); ?>" />
                                <p class="description">Maximum number of products to show in the 3D Shop Grid before pagination/cutoff.</p>
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Global Navigation & URLs</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_nav_active">Enable Fixed Nav Injection</label>
                                <input type="hidden" name="tryl_nav_active" value="0" />
                                <input type="checkbox" id="tryl_nav_active" class="tryl-toggle" name="tryl_nav_active" value="1" <?php checked(get_option('tryl_nav_active', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Master Shop URL</label>
                                <input type="text" name="tryl_nav_shop" value="<?php echo esc_attr(get_option('tryl_nav_shop', 'https://therighteousyieldlife.com/the-shop-wip/')); ?>" />
                                <p class="description">Current Default: <code>https://therighteousyieldlife.com/the-shop-wip/</code></p>
                            </div>
                            <div class="tryl-admin-row">
                                <label>Mobile Menu Alignment</label>
                                <select name="tryl_mobile_menu_align">
                                    <option value="left" <?php selected(get_option('tryl_mobile_menu_align', 'left'), 'left'); ?>>Left (Default)</option>
                                    <option value="center" <?php selected(get_option('tryl_mobile_menu_align', 'left'), 'center'); ?>>Center</option>
                                    <option value="right" <?php selected(get_option('tryl_mobile_menu_align', 'left'), 'right'); ?>>Right</option>
                                </select>
                            </div>
                            <div class="tryl-admin-row"><label>Mission Page URL</label><input type="text" name="tryl_nav_mission" value="<?php echo esc_attr(get_option('tryl_nav_mission', home_url('/mission/'))); ?>" /></div>
                            <div class="tryl-admin-row"><label>Prayer Request Page URL</label><input type="text" name="tryl_nav_prayer" value="<?php echo esc_attr(get_option('tryl_nav_prayer', home_url('/prayer-request/'))); ?>" /></div>
                            <div class="tryl-admin-row"><label>Contact Page URL</label><input type="text" name="tryl_nav_contact" value="<?php echo esc_attr(get_option('tryl_nav_contact', home_url('/contact/'))); ?>" /></div>
                            
                            <hr style="margin: 32px 0; border: none; border-top: 1px solid var(--tryl-border);" />
                            <h3 style="margin-top: 0; font-size: 1.1rem; color: var(--tryl-primary); text-transform: uppercase;">Footer Only Links</h3>
                            <div class="tryl-admin-row"><label>Shipping & Returns URL</label><input type="text" name="tryl_nav_shipping" value="<?php echo esc_attr(get_option('tryl_nav_shipping', home_url('/shipping-returns/'))); ?>" /></div>
                            <div class="tryl-admin-row"><label>FAQ Page URL</label><input type="text" name="tryl_nav_faq" value="<?php echo esc_attr(get_option('tryl_nav_faq', home_url('/faq/'))); ?>" /></div>
                            <div class="tryl-admin-row"><label>Privacy Policy URL</label><input type="text" name="tryl_nav_privacy" value="<?php echo esc_attr(get_option('tryl_nav_privacy', home_url('/privacy-policy/'))); ?>" /></div>
                            <div class="tryl-admin-row"><label>Terms of Service URL</label><input type="text" name="tryl_nav_terms" value="<?php echo esc_attr(get_option('tryl_nav_terms', home_url('/terms/'))); ?>" /></div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2><span class="dashicons dashicons-share"></span> Social Media Links</h2>
                            <p class="description" style="margin-top:-20px; margin-bottom: 24px;">Add your URLs here to display social icons at the bottom of the mobile menu. Leave blank to hide.</p>
                            <div class="tryl-admin-row"><label>Instagram URL</label><input type="text" name="tryl_social_instagram" value="<?php echo esc_attr(get_option('tryl_social_instagram')); ?>" placeholder="https://instagram.com/..." /></div>
                            <div class="tryl-admin-row"><label>TikTok URL</label><input type="text" name="tryl_social_tiktok" value="<?php echo esc_attr(get_option('tryl_social_tiktok')); ?>" placeholder="https://tiktok.com/@..." /></div>
                            <div class="tryl-admin-row"><label>X / Twitter URL</label><input type="text" name="tryl_social_twitter" value="<?php echo esc_attr(get_option('tryl_social_twitter')); ?>" placeholder="https://twitter.com/..." /></div>
                            <div class="tryl-admin-row"><label>YouTube URL</label><input type="text" name="tryl_social_youtube" value="<?php echo esc_attr(get_option('tryl_social_youtube')); ?>" placeholder="https://youtube.com/..." /></div>
                            <div class="tryl-admin-row"><label>Facebook URL</label><input type="text" name="tryl_social_facebook" value="<?php echo esc_attr(get_option('tryl_social_facebook')); ?>" placeholder="https://facebook.com/..." /></div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Footer Architecture</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_footer_active">Enable Global Footer</label>
                                <input type="hidden" name="tryl_footer_active" value="0" />
                                <input type="checkbox" id="tryl_footer_active" class="tryl-toggle" name="tryl_footer_active" value="1" <?php checked(get_option('tryl_footer_active', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Footer Style</label>
                                <select name="tryl_footer_layout_style">
                                    <option value="grid" <?php selected(get_option('tryl_footer_layout_style', 'grid'), 'grid'); ?>>Modern Grid</option>
                                    <option value="minimal" <?php selected(get_option('tryl_footer_layout_style', 'grid'), 'minimal'); ?>>Minimal</option>
                                    <option value="centered" <?php selected(get_option('tryl_footer_layout_style', 'grid'), 'centered'); ?>>Classic Centered</option>
                                </select>
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_footer_hover_anim">Enable Link Hover Animations</label>
                                <input type="hidden" name="tryl_footer_hover_anim" value="0" />
                                <input type="checkbox" id="tryl_footer_hover_anim" class="tryl-toggle" name="tryl_footer_hover_anim" value="1" <?php checked(get_option('tryl_footer_hover_anim', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_footer_mobile_center">Center Align Footer on Mobile</label>
                                <input type="hidden" name="tryl_footer_mobile_center" value="0" />
                                <input type="checkbox" id="tryl_footer_mobile_center" class="tryl-toggle" name="tryl_footer_mobile_center" value="1" <?php checked(get_option('tryl_footer_mobile_center', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Footer Brand Description</label>
                                <textarea name="tryl_footer_desc" rows="3"><?php echo esc_textarea(get_option('tryl_footer_desc', 'Faith-forward essentials crafted with intention. Wear your values, represent your faith, and yield righteousness in all that you do.')); ?></textarea>
                            </div>
                            <div class="tryl-admin-row">
                                <label>Developer Signature</label>
                                <input type="text" name="tryl_developer_signature" value="<?php echo esc_attr(get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices')); ?>" />
                            </div>
                        </div>
                    </div>

                    <!-- 4. CHECKOUT TAB -->
                    <div id="tab-checkout" class="tryl-tab-content">
                        <div class="tryl-admin-card">
                            <h2>Checkout Visibility Options</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_header_checkout_cta">Enable Header Checkout CTA</label>
                                <input type="hidden" name="tryl_header_checkout_cta" value="0" />
                                <input type="checkbox" id="tryl_header_checkout_cta" class="tryl-toggle" name="tryl_header_checkout_cta" value="1" <?php checked(get_option('tryl_header_checkout_cta', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_floating_checkout_active">Enable Floating "Checkout Now" Button</label>
                                <input type="hidden" name="tryl_floating_checkout_active" value="0" />
                                <input type="checkbox" id="tryl_floating_checkout_active" class="tryl-toggle" name="tryl_floating_checkout_active" value="1" <?php checked(get_option('tryl_floating_checkout_active', '1'), '1'); ?> />
                            </div>
                            <p class="description">Turn these off if you prefer a more minimalist navigation experience.</p>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Floating Cart & Mini-Cart</h2>
                            <div class="tryl-admin-row">
                                <label>Free Shipping Threshold ($)</label>
                                <input type="number" name="tryl_free_shipping_threshold" value="<?php echo esc_attr(get_option('tryl_free_shipping_threshold', '75')); ?>" />
                                <p class="description">Fills the interactive progress bar in the mini-cart.</p>
                            </div>
                            <div class="tryl-admin-row">
                                <label>Add to Cart Button Success Text</label>
                                <input type="text" name="tryl_atc_btn_text" value="<?php echo esc_attr(get_option('tryl_atc_btn_text', 'Added!')); ?>" />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_atc_notice_active">Customize WooCommerce Success Notice</label>
                                <input type="hidden" name="tryl_atc_notice_active" value="0" />
                                <input type="checkbox" id="tryl_atc_notice_active" class="tryl-toggle" name="tryl_atc_notice_active" value="1" <?php checked(get_option('tryl_atc_notice_active'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Custom Notice Text</label>
                                <textarea name="tryl_atc_notice_text" rows="2"><?php echo esc_textarea(get_option('tryl_atc_notice_text', '"{product}" was successfully added to your bag.')); ?></textarea>
                                <p class="description">Use <code>{product}</code> to dynamically insert the product name.</p>
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2><span class="dashicons dashicons-controls-forward"></span> 1-Click Order Bump</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_order_bump_active">Enable Order Bump Upsell</label>
                                <input type="hidden" name="tryl_order_bump_active" value="0" />
                                <input type="checkbox" id="tryl_order_bump_active" class="tryl-toggle" name="tryl_order_bump_active" value="1" <?php checked(get_option('tryl_order_bump_active'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Offer Label</label>
                                <input type="text" name="tryl_order_bump_label" value="<?php echo esc_attr(get_option('tryl_order_bump_label', 'Add a Righteous Yield Sticker Pack for $4.00')); ?>" />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Fee Amount ($)</label>
                                <input type="number" step="0.01" name="tryl_order_bump_fee" value="<?php echo esc_attr(get_option('tryl_order_bump_fee', '4.00')); ?>" />
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Nike Checkout System</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_nike_checkout_active">Enable Premium Nike Checkout</label>
                                <input type="hidden" name="tryl_nike_checkout_active" value="0" />
                                <input type="checkbox" id="tryl_nike_checkout_active" class="tryl-toggle" name="tryl_nike_checkout_active" value="1" <?php checked(get_option('tryl_nike_checkout_active', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_checkout_features_active">Enable Extra Features Switcher</label>
                                <input type="hidden" name="tryl_checkout_features_active" value="0" />
                                <input type="checkbox" id="tryl_checkout_features_active" class="tryl-toggle" name="tryl_checkout_features_active" value="1" <?php checked(get_option('tryl_checkout_features_active'), '1'); ?> />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_checkout_animations">Enable Checkout GSAP Animations</label>
                                <input type="hidden" name="tryl_checkout_animations" value="0" />
                                <input type="checkbox" id="tryl_checkout_animations" class="tryl-toggle" name="tryl_checkout_animations" value="1" <?php checked(get_option('tryl_checkout_animations', '1'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Gift Wrapping Fee ($)</label>
                                <input type="number" step="0.01" name="tryl_gift_wrapping_fee" value="<?php echo esc_attr(get_option('tryl_gift_wrapping_fee', '5.00')); ?>" />
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="tryl-admin-row"><label>Accent Color</label><input type="color" name="tryl_nike_checkout_accent" value="<?php echo esc_attr(get_option('tryl_nike_checkout_accent', '#111111')); ?>" /></div>
                                <div class="tryl-admin-row"><label>Input Background</label><input type="color" name="tryl_nike_checkout_input_bg" value="<?php echo esc_attr(get_option('tryl_nike_checkout_input_bg', '#f7f7f7')); ?>" /></div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. MARKETING TAB -->
                    <div id="tab-marketing" class="tryl-tab-content">
                        <div class="tryl-admin-card">
                            <h2>Exit-Intent Popup</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_popup_active">Enable Smart GSAP Popup</label>
                                <input type="hidden" name="tryl_popup_active" value="0" />
                                <input type="checkbox" id="tryl_popup_active" class="tryl-toggle" name="tryl_popup_active" value="1" <?php checked(get_option('tryl_popup_active'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row"><label>Heading</label><input type="text" name="tryl_popup_heading" value="<?php echo esc_attr(get_option('tryl_popup_heading', 'Wait, don\'t leave!')); ?>" /></div>
                            <div class="tryl-admin-row"><label>Body Text</label><textarea name="tryl_popup_text" rows="3"><?php echo esc_textarea(get_option('tryl_popup_text')); ?></textarea></div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Dynamic Product Badges</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_badges_active">Enable Automated Social Proof</label>
                                <input type="hidden" name="tryl_badges_active" value="0" />
                                <input type="checkbox" id="tryl_badges_active" class="tryl-toggle" name="tryl_badges_active" value="1" <?php checked(get_option('tryl_badges_active'), '1'); ?> />
                            </div>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_sp_trust_badges_active">Display Single Product Trust Markers</label>
                                <input type="hidden" name="tryl_sp_trust_badges_active" value="0" />
                                <input type="checkbox" id="tryl_sp_trust_badges_active" class="tryl-toggle" name="tryl_sp_trust_badges_active" value="1" <?php checked(get_option('tryl_sp_trust_badges_active', '1'), '1'); ?> />
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="tryl-admin-row"><label>Badge Background</label><input type="color" name="tryl_badges_bg" value="<?php echo esc_attr(get_option('tryl_badges_bg', '#31d190')); ?>" /></div>
                                <div class="tryl-admin-row"><label>Badge Text</label><input type="color" name="tryl_badges_text_color" value="<?php echo esc_attr(get_option('tryl_badges_text_color', '#0d1b0f')); ?>" /></div>
                            </div>
                        </div>
                        <div class="tryl-admin-card">
                            <h2>Next-Gen Order Emails</h2>
                            <div class="tryl-toggle-wrap">
                                <label for="tryl_nextgen_emails_active">Enable Premium Email Aesthetics</label>
                                <input type="hidden" name="tryl_nextgen_emails_active" value="0" />
                                <input type="checkbox" id="tryl_nextgen_emails_active" class="tryl-toggle" name="tryl_nextgen_emails_active" value="1" <?php checked(get_option('tryl_nextgen_emails_active'), '1'); ?> />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Email Hero Image URL (Optional)</label>
                                <input type="text" name="tryl_email_hero_url" value="<?php echo esc_attr(get_option('tryl_email_hero_url')); ?>" placeholder="https://..." />
                            </div>
                            <div class="tryl-admin-row">
                                <label>Email Footer Sign-off Message</label>
                                <textarea name="tryl_email_footer_msg" rows="2"><?php echo esc_textarea(get_option('tryl_email_footer_msg', 'Yield righteousness in all that you do.')); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 6. INTEGRATIONS TAB -->
                    <div id="tab-integrations" class="tryl-tab-content">
                        <div class="tryl-admin-card">
                            <h2>Prayer Request Settings</h2>
                            <div class="tryl-admin-row"><label>Notification Receiver Email</label><input type="email" name="tryl_prayer_email" value="<?php echo esc_attr(get_option('tryl_prayer_email', get_option('admin_email'))); ?>" /></div>
                            <div class="tryl-admin-row"><label>Auto-Responder Message</label><textarea name="tryl_prayer_auto_msg" rows="4"><?php echo esc_textarea(get_option('tryl_prayer_auto_msg')); ?></textarea></div>
                        </div>
<div class="tryl-admin-card">
    <h2>Technical Infrastructure</h2>
    <div class="tryl-admin-row"><label>Printful API Token</label><input type="text" name="tryl_printful_token" value="<?php echo esc_attr(get_option('tryl_printful_token')); ?>" /></div>
</div>

<div class="tryl-admin-card">
    <h2>Printful Synchronization</h2>
    <div class="tryl-toggle-wrap">
        <label for="tryl_printful_sync_enabled">Enable Printful Synchronization</label>
        <input type="hidden" name="tryl_printful_sync_enabled" value="0" />
        <input type="checkbox" id="tryl_printful_sync_enabled" class="tryl-toggle" name="tryl_printful_sync_enabled" value="1" <?php checked(get_option('tryl_printful_sync_enabled'), '1'); ?> />
    </div>
    <div class="tryl-admin-row">
        <label>Sync Schedule</label>
        <select name="tryl_printful_sync_time">
            <option value="hourly" <?php selected(get_option('tryl_printful_sync_time', 'hourly'), 'hourly'); ?>>Hourly</option>
            <option value="twicedaily" <?php selected(get_option('tryl_printful_sync_time', 'hourly'), 'twicedaily'); ?>>Twice Daily</option>
            <option value="daily" <?php selected(get_option('tryl_printful_sync_time', 'hourly'), 'daily'); ?>>Daily</option>
            <option value="weekly" <?php selected(get_option('tryl_printful_sync_time', 'hourly'), 'weekly'); ?>>Weekly</option>
        </select>
        <p class="description">How often to automatically sync products and inventory from Printful.</p>
    </div>
    <div class="tryl-toggle-wrap">
        <label for="tryl_printful_auto_publish">Auto-Publish Imported Products</label>
        <input type="hidden" name="tryl_printful_auto_publish" value="0" />
        <input type="checkbox" id="tryl_printful_auto_publish" class="tryl-toggle" name="tryl_printful_auto_publish" value="1" <?php checked(get_option('tryl_printful_auto_publish'), '1'); ?> />
    </div>
    <p class="description">When enabled, newly imported products will be published immediately. When disabled, they are saved as drafts for review.</p>
    <div class="tryl-toggle-wrap">
        <label for="tryl_printful_inventory_sync">Enable Real-Time Inventory Sync</label>
        <input type="hidden" name="tryl_printful_inventory_sync" value="0" />
        <input type="checkbox" id="tryl_printful_inventory_sync" class="tryl-toggle" name="tryl_printful_inventory_sync" value="1" <?php checked(get_option('tryl_printful_inventory_sync'), '1'); ?> />
    </div>
    <p class="description">When enabled, inventory levels are updated in real-time via webhooks. Requires webhook setup.</p>
    <div class="tryl-toggle-wrap">
        <label for="tryl_printful_mockup_sync">Enable Printful Mockup Sync</label>
        <input type="hidden" name="tryl_printful_mockup_sync" value="0" />
        <input type="checkbox" id="tryl_printful_mockup_sync" class="tryl-toggle" name="tryl_printful_mockup_sync" value="1" <?php checked(get_option('tryl_printful_mockup_sync'), '1'); ?> />
    </div>
    <p class="description">When enabled, the system will generate and attach Printful mockups to WooCommerce products during sync.</p>
</div>

<div class="tryl-admin-card">
    <h2>Printful Order Routing</h2>
    <div class="tryl-toggle-wrap">
        <label for="tryl_printful_order_routing">Enable Automatic Order Routing</label>
        <input type="hidden" name="tryl_printful_order_routing" value="0" />
        <input type="checkbox" id="tryl_printful_order_routing" class="tryl-toggle" name="tryl_printful_order_routing" value="1" <?php checked(get_option('tryl_printful_order_routing'), '1'); ?> />
    </div>
    <div class="tryl-admin-row">
        <label>Order Routing Rules</label>
        <select name="tryl_printful_routing_rules" multiple size="4" style="width: 100%; height: 150px;">
            <option value="all" <?php selected(get_option('tryl_printful_routing_rules', 'all'), 'all'); ?>>Send all orders to Printful</option>
            <option value="printful_only" <?php selected(get_option('tryl_printful_routing_rules', 'all'), 'printful_only'); ?>>Only Printful products</option>
            <option value="shipping_us" <?php selected(get_option('tryl_printful_routing_rules', 'all'), 'shipping_us'); ?>>US shipping addresses only</option>
            <option value="priority_shipping" <?php selected(get_option('tryl_printful_routing_rules', 'all'), 'priority_shipping'); ?>>Priority/Express shipping only</option>
            <option value="high_value" <?php selected(get_option('tryl_printful_routing_rules', 'all'), 'high_value'); ?>>Orders over $100</option>
        </select>
        <p class="description">Hold Ctrl (Cmd on Mac) to select multiple rules. Orders matching ANY selected rule will be sent to Printful.</p>
    </div>
    <div class="tryl-toggle-wrap">
        <label for="tryl_printful_order_override">Allow Manual Order Override</label>
        <input type="hidden" name="tryl_printful_order_override" value="0" />
        <input type="checkbox" id="tryl_printful_order_override" class="tryl-toggle" name="tryl_printful_order_override" value="1" <?php checked(get_option('tryl_printful_order_override'), '1'); ?> />
    </div>
    <p class="description">When enabled, you can manually choose to send or not send individual orders to Printful from the order edit screen.</p>
</div>
                    </div>

<!-- 7. DOCUMENTATION TAB -->
<div id="tab-docs" class="tryl-tab-content">
    <div class="tryl-admin-card">
        <h2>Merchant Support Hub</h2>
        <button id="tryl-download-pdf" class="button button-secondary" style="margin: 10px 0 20px;" type="button">Download Documentation as PDF</button>
        <div class="tryl-guide-grid">
            <?php tryl_documentation_tab_content(); ?>
        </div>
    </div>
</div>
                                <div class="tryl-guide-card">
                                    <h3>System Maintenance</h3>
                                    <p style="font-size: 0.85rem; line-height: 1.6;">If shop links fail or 404, click the button below to refresh the site routing map.</p>
                                    <a href="options-permalink.php" class="button button-primary">Flush Permalinks</a>
                                </div>
                                <div class="tryl-guide-card">
                                    <h3>Essential Site URLs</h3>
                                    <div class="tryl-url-box">
                                        <strong>Shop:</strong> <?php echo esc_html(get_option('tryl_nav_shop', home_url('/shop/'))); ?><br>
                                        <strong>Cart:</strong> <?php echo esc_html(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?><br>
                                        <strong>Checkout:</strong> <?php echo esc_html(get_option('tryl_nav_checkout', home_url('/checkout/'))); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            
            <div class="tryl-admin-save-bar">
                <button type="submit" class="tryl-admin-save-btn"><span class="dashicons dashicons-saved" style="margin-top: 2px;"></span> Save TRYL Settings</button>
            </div>
        </form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.tryl-admin-nav-link');
        const tabs = document.querySelectorAll('.tryl-tab-content');
        
        function switchTab(targetTab) {
            // Update Nav
            navLinks.forEach(l => {
                l.classList.remove('active');
                if (l.getAttribute('data-tab') === targetTab) l.classList.add('active');
            });
        
            // Update Tabs
            tabs.forEach(tab => {
                tab.classList.remove('active');
                if (tab.id === 'tab-' + targetTab) {
                    tab.classList.add('active');
                }
            });
        
            // Visual feedback with GSAP if available
            if (window.gsap) {
                gsap.fromTo('#tab-' + targetTab, { opacity: 0, y: 20 }, { opacity: 1, y: 0, duration: 0.6, ease: 'power2.out' });
            }
        }
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetTab = this.getAttribute('data-tab');
                switchTab(targetTab);
            });
        });
        
        // Handle direct tab access via URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const initialTab = urlParams.get('tab');
        if (initialTab) {
            switchTab(initialTab);
        }
        
        // Handle PDF download button
        const pdfButton = document.getElementById('tryl-download-pdf');
        if (pdfButton) {
            pdfButton.addEventListener('click', function() {
                window.print();
            });
        }
    });
</script>
    </div>
    <?php
}

// ─── 13. EXTRA CHECKOUT FEATURES LOGIC ───────────────────────────────────────

// 1. Add $5 Gift Wrapping Fee via AJAX
add_action( 'woocommerce_cart_calculate_fees', 'tryl_add_gift_wrapping_fee' );
function tryl_add_gift_wrapping_fee( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( get_option('tryl_checkout_features_active') !== '1' ) return;
    
    // Check if post_data is passed during AJAX checkout update
    parse_str( $_POST['post_data'] ?? '', $post_data );
    
    if ( isset( $post_data['tryl_gift_wrapping'] ) || isset( $_POST['tryl_gift_wrapping'] ) ) {
        $fee = 5.00; // You can make this configurable in the dashboard later!
        $cart->add_fee( __( 'Gift Message & Wrapping', 'woocommerce' ), $fee, true );
    }
}

// 2. Save Custom Checkout Fields to Order Meta
add_action( 'woocommerce_checkout_update_order_meta', 'tryl_save_checkout_features_meta' );
function tryl_save_checkout_features_meta( $order_id ) {
    if ( isset( $_POST['tryl_eco_packaging'] ) ) update_post_meta( $order_id, 'Eco-Friendly Packaging', 'Requested' );
    if ( isset( $_POST['tryl_gift_wrapping'] ) ) update_post_meta( $order_id, 'Gift Message & Wrapping', 'Yes' );
}

// 3. Display Custom Fields in WP Admin Order View
add_action( 'woocommerce_admin_order_data_after_billing_address', 'tryl_display_checkout_features_admin', 10, 1 );
function tryl_display_checkout_features_admin( $order ) {
    $eco  = get_post_meta( $order->get_id(), 'Eco-Friendly Packaging', true );
    $gift = get_post_meta( $order->get_id(), 'Gift Message & Wrapping', true );
    if ( $eco || $gift ) {
        echo '<h3>Extra Features Requested:</h3>';
        if ( $eco ) echo '<p><strong>Eco-Friendly Packaging:</strong> ' . esc_html( $eco ) . '</p>';
        if ( $gift ) echo '<p><strong>Gift Wrapping:</strong> ' . esc_html( $gift ) . '</p>';
    }
}

/**
 * ─── 14. MY ACCOUNT RESKIN ───
 */
function tryl_myaccount_reskin_css() {
    if ( ! function_exists('is_account_page') || ! is_account_page() || is_user_logged_in() === false ) return;
    if ( get_option('tryl_myaccount_reskin_active', '1') !== '1' ) return;
    ?>
    <style>
    :root, [data-theme="bright"] {
        --ma-bg: #f5f8f5; --ma-card: #ffffff; --ma-border: #d4e0d4;
        --ma-text: #1a2e1a; --ma-muted: #6b7c6b; --ma-accent: #31d190;
        --ma-dark: #0d1b0f; --ma-btn-txt: #ffffff;
    }
    [data-theme="mild"] {
        --ma-bg: #e6e4df; --ma-card: #f2f0eb; --ma-border: #c4c0b5;
        --ma-text: #33322e; --ma-muted: #858178; --ma-accent: #a39e93;
        --ma-dark: #33322e; --ma-btn-txt: #ffffff;
    }
    [data-theme="dark"] {
        --ma-bg: #0d1b0f; --ma-card: #132615; --ma-border: #2d6a4f;
        --ma-text: #f5f8f5; --ma-muted: #8a9c8a; --ma-accent: #31d190;
        --ma-dark: #f5f8f5; --ma-btn-txt: #0d1b0f;
    }
    body.woocommerce-account { background: var(--ma-bg); color: var(--ma-text); }
    body.woocommerce-account .woocommerce { display: grid; grid-template-columns: 280px 1fr; gap: 48px; max-width: 1200px; margin: 64px auto; align-items: start; }
    @media (max-width: 768px) { body.woocommerce-account .woocommerce { grid-template-columns: 1fr; gap: 32px; margin: 32px auto; } }
    
    .woocommerce-MyAccount-navigation { background: var(--ma-card); border: 1px solid var(--ma-border); border-radius: 12px; padding: 24px 0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .woocommerce-MyAccount-navigation ul { list-style: none; padding: 0; margin: 0; }
    .woocommerce-MyAccount-navigation ul li { margin: 0; border-bottom: 1px solid var(--ma-border); }
    .woocommerce-MyAccount-navigation ul li:last-child { border-bottom: none; }
    .woocommerce-MyAccount-navigation ul li a { display: block; padding: 16px 32px; color: var(--ma-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
    .woocommerce-MyAccount-navigation ul li.is-active a, .woocommerce-MyAccount-navigation ul li a:hover { color: var(--ma-dark); background: var(--ma-bg); border-left-color: var(--ma-accent); }
    
    .woocommerce-MyAccount-content { background: var(--ma-card); border: 1px solid var(--ma-border); border-radius: 12px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
    .woocommerce-MyAccount-content h2, .woocommerce-MyAccount-content h3 { font-family: var(--tryl-header-font, sans-serif); text-transform: uppercase; margin-top: 0; color: var(--ma-dark); margin-bottom: 24px; }
    .woocommerce-MyAccount-content p { color: var(--ma-text); line-height: 1.6; }
    .woocommerce-MyAccount-content a { color: var(--ma-accent); font-weight: 600; text-decoration: none; }
    .woocommerce-MyAccount-content a:hover { text-decoration: underline; }
    
    /* Form Inputs */
    .woocommerce-MyAccount-content input[type="text"], .woocommerce-MyAccount-content input[type="password"], .woocommerce-MyAccount-content input[type="email"] { width: 100%; padding: 12px 16px; border: 1.5px solid var(--ma-border); border-radius: 4px; background: var(--ma-bg); color: var(--ma-text); font-family: inherit; margin-bottom: 16px; }
    .woocommerce-MyAccount-content input:focus { outline: none; border-color: var(--ma-dark); }
    </style>
    <?php
}
// add_action('wp_head', 'tryl_myaccount_reskin_css'); // Safely extracted to tryl-core.css

/**
 * ─── 15. CUSTOM WOOCOMMERCE NOTICES ───
 */
function tryl_custom_add_to_cart_message( $message, $products ) {
    if ( get_option( 'tryl_atc_notice_active' ) !== '1' ) return $message;
    
    $custom_msg = get_option( 'tryl_atc_notice_text', '"{product}" was successfully added to your bag.' );
    
    $product_names = array();
    foreach ( (array)$products as $product_id => $qty ) {
        $product_names[] = get_the_title( $product_id );
    }
    $product_name_string = implode( ', ', $product_names );
    
    $final_msg = str_replace( '{product}', $product_name_string, $custom_msg );
    
    $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '';
    return '<a href="' . esc_url( $cart_url ) . '" class="button wc-forward">View Cart</a> ' . esc_html( $final_msg );
}
add_filter( 'wc_add_to_cart_message_html', 'tryl_custom_add_to_cart_message', 10, 2 );

// ─── 16. LOKSERVICES BRIDGE MODULE ───
if ( ! defined( 'LOKSERVICES_BRIDGE_ACTIVE' ) ) {
if ( ! function_exists( 'lok_bridge_menu' ) ) {
function lok_bridge_menu() {
    add_options_page( 'LokServices Connection', 'LokServices Bridge', 'manage_options', 'lokservices-bridge', 'lok_bridge_options_page' );
}
}
add_action( 'admin_menu', 'lok_bridge_menu' );

if ( ! function_exists( 'lok_bridge_options_page' ) ) {
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
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e8f0e8; padding-bottom: 12px; margin-bottom: 20px;">
                <h2 style="margin: 0; border: none; padding: 0;">2. Deployment Audit Log</h2>
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
function tryl_documentation_tab_content() {
    ?>
    <style>
        /* Print-specific styles */
        @media print {
            body { 
                font-size: 12pt; 
                line-height: 1.5; 
                color: #000; 
                background: #fff; 
                max-width: none; 
                margin: 0; 
                padding: 15mm; 
            }
            .no-print, .button, .tryl-admin-nav, .tryl-admin-save-bar { 
                display: none !important; 
            }
            .tryl-admin-card { 
                box-shadow: none; 
                border: none; 
                margin: 0; 
                padding: 0; 
            }
            h1, h2, h3 { 
                color: #000; 
                page-break-after: avoid; 
            }
            h1 { 
                font-size: 24pt; 
                text-align: center; 
                margin-bottom: 20pt; 
            }
            h2 { 
                font-size: 18pt; 
                border-bottom: 1px solid #31d190; 
                padding-bottom: 5pt; 
                margin-top: 25pt; 
            }
            h3 { 
                font-size: 14pt; 
                color: #31d190; 
                margin-top: 20pt; 
            }
            ul, ol { 
                margin-left: 20pt; 
            }
            li { 
                margin: 8pt 0; 
            }
            code { 
                background: #f0f0f0; 
                padding: 1px 4px; 
                border-radius: 3px; 
            }
            .tryl-url-box { 
                background: #f8f8f8; 
                padding: 12pt; 
                border-radius: 6pt; 
                margin: 15pt 0; 
                border: 1px solid #ddd; 
            }
            .tryl-url-box strong { 
                display: block; 
            }
            .setup-steps { 
                margin: 15pt 0; 
            }
            .setup-steps ol { 
                margin-left: 25pt; 
            }
            .setup-steps li { 
                margin: 6pt 0; 
            }
            .footer { 
                text-align: center; 
                margin-top: 30pt; 
                color: #666; 
                font-size: 10pt; 
            }
            /* Avoid breaking elements across pages */
            .tryl-guide-card { 
                page-break-inside: avoid; 
                margin-bottom: 20pt; 
            }
        }
    </style>
    <div class="tryl-guide-card">
        <h3>Quick Shortcodes</h3>
        <ul style="list-style: none; padding: 0; font-size: 0.9rem; line-height: 2;">
            <li><code>[tryl_hero]</code> — Entrance</li>
            <li><code>[tryl_3d_shop]</code> — Standard Grid</li>
            <li><code>[tryl_shop_editorial]</code> — Luxury Grid</li>
            <li><code>[tryl_prayer_form]</code> — Interaction</li>
        </ul>
    </div>
    <div class="tryl-guide-card">
        <h3>System Maintenance</h3>
        <p style="font-size: 0.85rem; line-height: 1.6;">If shop links fail or 404, click the button below to refresh the site routing map.</p>
        <a href="options-permalink.php" class="button button-primary">Flush Permalinks</a>
    </div>
    <div class="tryl-guide-card">
        <h3>Essential Site URLs</h3>
        <div class="tryl-url-box">
            <strong>Shop:</strong> <?php echo esc_html(get_option('tryl_nav_shop', home_url('/shop/'))); ?><br>
            <strong>Cart:</strong> <?php echo esc_html(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?><br>
            <strong>Checkout:</strong> <?php echo esc_html(get_option('tryl_nav_checkout', home_url('/checkout/'))); ?>
        </div>
    </div>
    <div class="tryl-guide-card">
        <h3>Printful Integration Guide</h3>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Automated Product Sync:</strong> Automatically imports products from Printful to WooCommerce. Configure in Integrations tab → Printful Synchronization.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Real-Time Inventory:</strong> Keep stock levels synchronized to prevent overselling. Enable Real-Time Inventory Sync for webhook-based updates.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Smart Order Routing:</strong> Automatically send orders to Printful based on rules (product type, location, shipping method, order value). Configure in Integrations tab → Printful Order Routing.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Manual Order Control:</strong> Override automatic routing per-order from the order edit screen when Manual Override is enabled.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Status Synchronization:</strong> Printful order status updates automatically update WooCommerce order status.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Setup Steps:</strong></p>
        <ol style="font-size: 0.85rem; line-height: 1.6; margin-left: 20px;">
            <li>Enter your Printful API token in Integrations tab</li>
            <li>Enable Printful Synchronization and configure schedule</li>
            <li>Enable Real-Time Inventory Sync (recommended)</li>
            <li>Configure Order Routing rules as needed</li>
            <li>Save changes and let the system run automatically</li>
        </ol>
    </div>
    <div class="tryl-guide-card">
        <h3>Shop Features</h3>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>3D Product Tilt:</strong> Products respond to mouse movement with subtle tilt effect</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Inline Variant Selection:</strong> Select size/color directly on product cards (no page reload)</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>AJAX Add to Cart:</strong> Items add instantly with mini-cart animation</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>GSAP Animations:</strong> Smooth transitions throughout the shopping experience</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Theme System:</strong> Bright/Mild/Dark modes with automatic OS detection</p>
    </div>
    <?php
}
            .no-print, .button, .tryl-admin-nav, .tryl-admin-save-bar { 
                display: none !important; 
            }
            .tryl-admin-card { 
                box-shadow: none; 
                border: none; 
                margin: 0; 
                padding: 0; 
            }
            h1, h2, h3 { 
                color: #000; 
                page-break-after: avoid; 
            }
            h1 { 
                font-size: 24pt; 
                text-align: center; 
                margin-bottom: 20pt; 
            }
            h2 { 
                font-size: 18pt; 
                border-bottom: 1px solid #31d190; 
                padding-bottom: 5pt; 
                margin-top: 25pt; 
            }
            h3 { 
                font-size: 14pt; 
                color: #31d190; 
                margin-top: 20pt; 
            }
            ul, ol { 
                margin-left: 20pt; 
            }
            li { 
                margin: 8pt 0; 
            }
            code { 
                background: #f0f0f0; 
                padding: 1px 4px; 
                border-radius: 3px; 
            }
            .tryl-url-box { 
                background: #f8f8f8; 
                padding: 12pt; 
                border-radius: 6pt; 
                margin: 15pt 0; 
                border: 1px solid #ddd; 
            }
            .tryl-url-box strong { 
                display: block; 
            }
            .setup-steps { 
                margin: 15pt 0; 
            }
            .setup-steps ol { 
                margin-left: 25pt; 
            }
            .setup-steps li { 
                margin: 6pt 0; 
            }
            .footer { 
                text-align: center; 
                margin-top: 30pt; 
                color: #666; 
                font-size: 10pt; 
            }
            /* Avoid breaking elements across pages */
            .tryl-guide-card { 
                page-break-inside: avoid; 
                margin-bottom: 20pt; 
            }
        }
    </style>
    <div class="tryl-guide-card">
        <h3>Quick Shortcodes</h3>
        <ul style="list-style: none; padding: 0; font-size: 0.9rem; line-height: 2;">
            <li><code>[tryl_hero]</code> — Entrance</li>
            <li><code>[tryl_3d_shop]</code> — Standard Grid</li>
            <li><code>[tryl_shop_editorial]</code> — Luxury Grid</li>
            <li><code>[tryl_prayer_form]</code> — Interaction</li>
        </ul>
    </div>
    <div class="tryl-guide-card">
        <h3>System Maintenance</h3>
        <p style="font-size: 0.85rem; line-height: 1.6;">If shop links fail or 404, click the button below to refresh the site routing map.</p>
        <a href="options-permalink.php" class="button button-primary">Flush Permalinks</a>
    </div>
    <div class="tryl-guide-card">
        <h3>Essential Site URLs</h3>
        <div class="tryl-url-box">
            <strong>Shop:</strong> <?php echo esc_html(get_option('tryl_nav_shop', home_url('/shop/'))); ?><br>
            <strong>Cart:</strong> <?php echo esc_html(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?><br>
            <strong>Checkout:</strong> <?php echo esc_html(get_option('tryl_nav_checkout', home_url('/checkout/'))); ?>
        </div>
    </div>
    <div class="tryl-guide-card">
        <h3>Printful Integration Guide</h3>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Automated Product Sync:</strong> Automatically imports products from Printful to WooCommerce. Configure in Integrations tab → Printful Synchronization.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Real-Time Inventory:</strong> Keep stock levels synchronized to prevent overselling. Enable Real-Time Inventory Sync for webhook-based updates.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Smart Order Routing:</strong> Automatically send orders to Printful based on rules (product type, location, shipping method, order value). Configure in Integrations tab → Printful Order Routing.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Manual Order Control:</strong> Override automatic routing per-order from the order edit screen when Manual Override is enabled.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Status Synchronization:</strong> Printful order status updates automatically update WooCommerce order status.</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Setup Steps:</strong></p>
        <ol style="font-size: 0.85rem; line-height: 1.6; margin-left: 20px;">
            <li>Enter your Printful API token in Integrations tab</li>
            <li>Enable Printful Synchronization and configure schedule</li>
            <li>Enable Real-Time Inventory Sync (recommended)</li>
            <li>Configure Order Routing rules as needed</li>
            <li>Save changes and let the system run automatically</li>
        </ol>
    </div>
    <div class="tryl-guide-card">
        <h3>Shop Features</h3>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>3D Product Tilt:</strong> Products respond to mouse movement with subtle tilt effect</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Inline Variant Selection:</strong> Select size/color directly on product cards (no page reload)</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>AJAX Add to Cart:</strong> Items add instantly with mini-cart animation</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>GSAP Animations:</strong> Smooth transitions throughout the shopping experience</p>
        <p style="font-size: 0.85rem; line-height: 1.6;"><strong>Theme System:</strong> Bright/Mild/Dark modes with automatic OS detection</p>
    </div>
    <?php
}



// ─── PRINTFUL INTEGRATION ───
if ( ! function_exists( 'tryl_printful_api_request' ) ) {
function tryl_printful_api_request( $endpoint, $method = 'GET', $data = null ) {
    $api_token = get_option( 'tryl_printful_token' );
    if ( empty( $api_token ) ) {
        return new WP_Error( 'no_token', 'Printful API token not configured.' );
    }
    
    $headers = [
        'Authorization' => 'Bearer ' . $api_token,
        'Content-Type' => 'application/json',
    ];
    
    $args = [
        'headers' => $headers,
        'method' => $method,
        'timeout' => 30,
        'httpversion' => '1.1',
        'sslverify' => true,
    ];
    
    if ( $data !== null && in_array( $method, ['POST', 'PUT', 'PATCH'] ) ) {
        $args['body'] = wp_json_encode( $data );
    }
    
    $response = wp_remote_get( 'https://api.printful.com/' . $endpoint, $args );
    
    if ( is_wp_error( $response ) ) {
        return $response;
    }
    
    $body = wp_remote_retrieve_body( $response );
    $code = wp_remote_retrieve_response_code( $response );
    
    if ( $code >= 200 && $code < 300 ) {
        $result = json_decode( $body, true );
        if ( isset( $result['result'] ) ) {
            return $result['result'];
        }
        return $result;
    } else {
        return new WP_Error( 'api_error', 'Printful API error: ' . $code, [ 'response' => $response ] );
    }
}
}

if ( ! function_exists( 'tryl_printful_sync_products' ) ) {
function tryl_printful_sync_products() {
    if ( get_option( 'tryl_printful_sync_enabled' ) !== '1' ) {
        return;
    }
    
    // Get products from Printful
    $products = tryl_printful_api_request( 'store/products' );
    if ( is_wp_error( $products ) ) {
        error_log( 'Printful product sync failed: ' . $products->get_error_message() );
        return;
    }
    
    $auto_publish = get_option( 'tryl_printful_auto_publish' ) === '1' ? 'publish' : 'draft';
    
    foreach ( $products as $product ) {
        // Check if product already exists
        $existing_post = get_posts( [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_tryl_printful_product_id',
                    'value' => $product['id'],
                ]
            ],
            'posts_per_page' => 1,
        ] );
        
        $product_data = [
            'post_title'   => $product['name'],
            'post_status'  => $auto_publish,
            'post_type'    => 'product',
            'post_content' => $product['description'] ?? '',
        ];
        
        if ( ! empty( $existing_post ) ) {
            $product_data['ID'] = $existing_post[0]->ID;
        }
        
        $post_id = wp_insert_post( $product_data );
        
        if ( ! is_wp_error( $post_id ) ) {
            // Update printful ID meta
            update_post_meta( $post_id, '_tryl_printful_product_id', $product['id'] );
            
            // Handle mockup images if enabled
            if ( get_option( 'tryl_printful_mockup_sync' ) === '1' ) {
                $mockups = tryl_printful_api_request( "store/products/{$product['id']}/mockups" );
                if ( ! is_wp_error( $mockups ) && ! empty( $mockups ) ) {
                    // We'll take the first mockup's file_url
                    $mockup_url = isset( $mockups[0]['file_url'] ) ? $mockups[0]['file_url'] : '';
                    if ( ! empty( $mockup_url ) ) {
                        update_post_meta( $post_id, '_tryl_printful_mockup_url', $mockup_url );
                    }
                }
            }
            
            // Handle variants
            if ( ! empty( $product['variants'] ) ) {
                foreach ( $product['variants'] as $variant ) {
                    // Check if variation already exists
                    $existing_variation = get_posts( [
                        'post_type' => 'product_variation',
                        'post_parent' => $post_id,
                        'meta_query' => [
                            [
                                'key' => '_tryl_printful_variant_id',
                                'value' => $variant['id'],
                            ]
                        ],
                        'posts_per_page' => 1,
                    ] );
                    
                    if ( empty( $existing_variation ) ) {
                        // Create variation
                        $variation_data = [
                            'post_title'  => $variant['name'] ?? 'Variant',
                            'post_status' => 'publish',
                            'post_type'   => 'product_variation',
                            'post_parent' => $post_id,
                        ];
                        
                        $variation_id = wp_insert_post( $variation_data );
                        
                        if ( ! is_wp_error( $variation_id ) ) {
                            update_post_meta( $variation_id, '_tryl_printful_variant_id', $variant['id'] );
                            update_post_meta( $variation_id, '_price', $variant['price'] ?? '0' );
                            update_post_meta( $variation_id, '_regular_price', $variant['price'] ?? '0' );
                            update_post_meta( $variation_id, '_sku', $variant['sku'] ?? '' );
                            update_post_meta( $variation_id, '_weight', $variant['weight'] ?? '0' );
                            update_post_meta( $variation_id, '_length', $variant['length'] ?? '0' );
                            update_post_meta( $variation_id, '_width', $variant['width'] ?? '0' );
                            update_post_meta( $variation_id, '_height', $variant['height'] ?? '0' );
                            
                            // Handle attributes
                            if ( ! empty( $variant['options'] ) ) {
                                foreach ( $variant['options'] as $option ) {
                                    // This would need attribute taxonomy setup
                                    // For now, we'll store as meta
                                    update_post_meta( $variation_id, '_attribute_' . sanitize_title( $option['name'] ), $option['value'] );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // Update last sync time
    update_option( 'tryl_printful_last_sync', current_time( 'mysql' ) );
}
}

if ( ! function_exists( 'tryl_printful_sync_inventory' ) ) {
function tryl_printful_sync_inventory() {
    if ( get_option( 'tryl_printful_sync_enabled' ) !== '1' ) {
        return;
    }
    
    // Get stock items from Printful
    $stock = tryl_printful_api_request( 'store/products/stock' );
    if ( is_wp_error( $stock ) ) {
        error_log( 'Printful inventory sync failed: ' . $stock->get_error_message() );
        return;
    }
    
    foreach ( $stock as $item ) {
        // Find product by Printful product ID
        $products = get_posts( [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_tryl_printful_product_id',
                    'value' => $item['product_id'],
                ]
            ],
            'posts_per_page' => 1,
        ] );
        
        if ( ! empty( $products ) ) {
            $product_id = $products[0]->ID;
            
            // Find variation by Printful variant ID
            if ( ! empty( $item['variant_id'] ) ) {
                $variations = get_posts( [
                    'post_type' => 'product_variation',
                    'post_parent' => $product_id,
                    'meta_query' => [
                        [
                            'key' => '_tryl_printful_variant_id',
                            'value' => $item['variant_id'],
                        ]
                    ],
                    'posts_per_page' => 1,
                ] );
                
                if ( ! empty( $variations ) ) {
                    $variation_id = $variations[0]->ID;
                    // Update stock for variation
                    wc_update_product_stock( $variation_id, $item['total'], 'set' );
                }
            } else {
                // Update stock for main product (simple product)
                wc_update_product_stock( $product_id, $item['total'], 'set' );
            }
        }
    }
    
    // Update last inventory sync time
    update_option( 'tryl_printful_last_inventory_sync', current_time( 'mysql' ) );
}
}

if ( ! function_exists( 'tryl_printful_webhook_handler' ) ) {
function tryl_printful_webhook_handler() {
    // Check if this is a Printful webhook
    $signature = isset( $_SERVER['HTTP_X_PRINTFUL_SIGNATURE'] ) ? $_SERVER['HTTP_X_PRINTFUL_SIGNATURE'] : '';
    $token = get_option( 'tryl_printful_token' );
    
    if ( empty( $signature ) || empty( $token ) ) {
        status_header( 401 );
        exit;
    }
    
    // Verify signature (simplified - in production you'd want to verify properly)
    $body = file_get_contents( 'php://input' );
    $expected_signature = base64_encode( hash_hmac( 'sha256', $body, $token, true ) );
    
    if ( ! hash_equals( $signature, $expected_signature ) ) {
        status_header( 401 );
        exit;
    }
    
    $data = json_decode( $body, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        status_header( 400 );
        exit;
    }
    
    // Handle different webhook types
    if ( ! empty( $data['type'] ) ) {
        switch ( $data['type'] ) {
            case 'order':
                // Handle order webhook
                // This would typically be used to update order status
                break;
                
            case 'product':
                // Handle product webhook - trigger sync
                if ( ! empty( $data['id'] ) ) {
                    // Sync specific product
                }
                break;
                
            case 'stock':
                // Handle stock webhook - trigger inventory sync
                if ( get_option( 'tryl_printful_inventory_sync' ) === '1' ) {
                    tryl_printful_sync_inventory();
                }
                break;
        }
    }
    
    // Return success
    header( 'Content-Type: application/json' );
    echo json_encode( [ 'success' => true ] );
    exit;
}
}

// Register Printful webhook endpoint
if ( ! function_exists( 'tryl_register_printful_webhook' ) ) {
function tryl_register_printful_webhook() {
    register_rest_route( 'tryl-printful/v1', '/webhook', array(
        'methods'             => 'POST',
        'callback'            => 'tryl_printful_webhook_handler',
        'args'                => array(
            // No specific args needed for webhook
        ),
    ) );
}
add_action( 'rest_api_init', 'tryl_register_printful_webhook' );
}

// Schedule automated sync
if ( ! function_exists( 'tryl_schedule_printful_sync' ) ) {
function tryl_schedule_printful_sync() {
    if ( wp_next_scheduled( 'tryl_printful_sync_hook' ) ) {
        return;
    }
    
    $schedule = get_option( 'tryl_printful_sync_time', 'daily' );
    switch ( $schedule ) {
        case 'hourly':
            $recurrence = 'hourly';
            break;
        case 'twicedaily':
            $recurrence = 'twicedaily';
            break;
        case 'weekly':
            $recurrence = 'weekly';
            break;
        default: // daily
            $recurrence = 'daily';
            break;
    }
    
    wp_schedule_event( time(), $recurrence, 'tryl_printful_sync_hook' );
}
}
add_action( 'wp', 'tryl_schedule_printful_sync' );

if ( ! function_exists( 'tryl_printful_sync_hook_callback' ) ) {
function tryl_printful_sync_hook_callback() {
    if ( get_option( 'tryl_printful_sync_enabled' ) === '1' ) {
        tryl_printful_sync_products();
        
        if ( get_option( 'tryl_printful_inventory_sync' ) === '1' ) {
            tryl_printful_sync_inventory();
        }
    }
}
}
add_action( 'tryl_printful_sync_hook', 'tryl_printful_sync_hook_callback' );

// ─── PRINTFUL ORDER ROUTING ───
if ( ! function_exists( 'tryl_printful_submit_order' ) ) {
function tryl_printful_submit_order( $order_id ) {
    // Check if order routing is enabled
    if ( get_option( 'tryl_printful_order_routing' ) !== '1' ) {
        return new WP_Error( 'routing_disabled', 'Printful order routing is disabled.' );
    }
    
    // Get the order
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return new WP_Error( 'invalid_order', 'Invalid order ID.' );
    }
    
    // Check if order should be routed to Printful based on rules
    if ( ! tryl_printful_should_route_order( $order ) ) {
        return new WP_Error( 'not_routed', 'Order does not match routing rules.' );
    }
    
    // Check for manual override (if enabled)
    if ( get_option( 'tryl_printful_order_override' ) === '1' ) {
        $manual_override = get_post_meta( $order_id, '_tryl_printful_manual_override', true );
        if ( $manual_override === 'skip' ) {
            return new WP_Error( 'manual_skip', 'Order manually skipped for Printful routing.' );
        }
        // If manual_override is 'force' or not set, continue with normal routing
    }
    
    // Prepare order data for Printful
    $printful_order = tryl_printful_prepare_order_data( $order );
    
    if ( is_wp_error( $printful_order ) ) {
        return $printful_order;
    }
    
    // Submit order to Printful
    $result = tryl_printful_api_request( 'orders', 'POST', $printful_order );
    
    if ( is_wp_error( $result ) ) {
        // Log the error
        error_log( 'Printful order submission failed for order '.$order_id.': '.$result->get_error_message() );
        return $result;
    }
    
    // Store Printful order ID and update order status
    if ( ! empty( $result['id'] ) ) {
        update_post_meta( $order_id, '_tryl_printful_order_id', $result['id'] );
        
        // Add note to order
        $order->add_order_note( 
            sprintf( 
                'Order submitted to Printful. Printful Order ID: %d', 
                $result['id'] 
            ) 
        );
        
        // Update custom status if needed
        if ( get_option( 'tryl_printful_order_status' ) !== '' ) {
            $order->update_status( get_option( 'tryl_printful_order_status' ), 
                'Submitted to Printful (Order ID: '.$result['id'].')' 
            );
        }
        
        return $result;
    } else {
        return new WP_Error( 'invalid_response', 'Invalid response from Printful API.' );
    }
}
}

if ( ! function_exists( 'tryl_printful_should_route_order' ) ) {
function tryl_printful_should_route_order( $order ) {
    // Get routing rules
    $rules = get_option( 'tryl_printful_routing_rules', 'all' );
    if ( empty( $rules ) ) {
        $rules = array( 'all' );
    }
    
    // If 'all' is selected, route everything
    if ( in_array( 'all', $rules ) ) {
        return true;
    }
    
    // Check each rule
    foreach ( $rules as $rule ) {
        switch ( $rule ) {
            case 'printful_only':
                // Check if order contains Printful products
                if ( tryl_printful_order_contains_printful_products( $order ) ) {
                    return true;
                }
                break;
                
            case 'shipping_us':
                // Check if shipping to US
                if ( tryl_printful_order_shipping_to_us( $order ) ) {
                    return true;
                }
                break;
                
            case 'priority_shipping':
                // Check if priority/express shipping
                if ( tryl_printful_order_has_priority_shipping( $order ) ) {
                    return true;
                }
                break;
                
            case 'high_value':
                // Check if order value over threshold
                $threshold = get_option( 'tryl_printful_high_value_threshold', '100' );
                if ( $order->get_total() > floatval( $threshold ) ) {
                    return true;
                }
                break;
        }
    }
    
    return false;
}
}

if ( ! function_exists( 'tryl_printful_order_contains_printful_products' ) ) {
function tryl_printful_order_contains_printful_products( $order ) {
    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        
        // Check if product or variation has Printful ID
        $printful_product_id = get_post_meta( $product_id, '_tryl_printful_product_id', true );
        $printful_variation_id = get_post_meta( $variation_id ?: $product_id, '_tryl_printful_variant_id', true );
        
        if ( ! empty( $printful_product_id ) || ! empty( $printful_variation_id ) ) {
            return true;
        }
    }
    return false;
}
}

if ( ! function_exists( 'tryl_printful_order_shipping_to_us' ) ) {
function tryl_printful_order_shipping_to_us( $order ) {
    $shipping_country = $order->get_shipping_country();
    return $shipping_country === 'US';
}
}

if ( ! function_exists( 'tryl_printful_order_has_priority_shipping' ) ) {
function tryl_printful_order_has_priority_shipping( $order ) {
    $shipping_method = $order->get_shipping_method();
    $priority_keywords = array( 'express', 'priority', 'overnight', 'next day', '2-day' );
    
    foreach ( $priority_keywords as $keyword ) {
        if ( stripos( $shipping_method, $keyword ) !== false ) {
            return true;
        }
    }
    return false;
}
}

if ( ! function_exists( 'tryl_printful_prepare_order_data' ) ) {
function tryl_printful_prepare_order_data( $order ) {
    $recipient = array(
        'name'      => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'address1'  => $order->get_billing_address_1(),
        'address2'  => $order->get_billing_address_2(),
        'city'      => $order->get_billing_city(),
        'state_code'=> $order->get_billing_state(),
        'country_code'=> $order->get_billing_country(),
        'zip'       => $order->get_billing_postcode(),
        'phone'     => $order->get_billing_phone(),
        'email'     => $order->get_billing_email(),
    );
    
    $items = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        
        // Get Printful IDs
        $printful_product_id = get_post_meta( $product_id, '_tryl_printful_product_id', true );
        $printful_variation_id = get_post_meta( $variation_id ?: $product_id, '_tryl_printful_variant_id', true );
        
        // Fallback to product ID if no Printful ID found (shouldn't happen if routing works)
        if ( empty( $printful_variation_id ) ) {
            $printful_variation_id = $printful_product_id;
        }
        
        if ( ! empty( $printful_variation_id ) ) {
            $items[] = array(
                'external_id'   => $item_id,
                'variant_id'    => intval( $printful_variation_id ),
                'quantity'      => $item->get_quantity(),
                'value'         => $item->get_total(),
                'name'          => $item->get_name(),
            );
        }
    }
    
    // Get shipping cost from order
    $shipping_cost = $order->get_shipping_total();
    
    // Get the selected shipping method name for Printful
    $shipping_method_name = $order->get_shipping_method();
    
    // Map WooCommerce shipping to Printful shipping (simplified mapping)
    // In a real implementation, you'd have a more comprehensive mapping
    $shipping = array(
        'amount'      => floatval( $shipping_cost ),
        'description' => $shipping_method_name,
        'tracking_number'  => '',
    );
    
    // Apply discount if any
    $discount = $order->get_discount_total();
    $retail_cost = $order->get_total() - $shipping_cost;
    
    $order_data = array(
        'recipient'   => $recipient,
        'items'       => $items,
        'shipping'    => $shipping,
        'retail_cost' => floatval( $retail_cost ),
        'currency'    => get_option( 'woocommerce_currency' ),
        'note'        => sprintf( 'WooCommerce Order #%d', $order->get_id() ),
    );
    
    return $order_data;
}
}

if ( ! function_exists( 'tryl_printful_order_status_webhook' ) ) {
function tryl_printful_order_status_webhook() {
    // Verify webhook signature
    $signature = isset( $_SERVER['HTTP_X_PRINTFUL_SIGNATURE'] ) ? $_SERVER['HTTP_X_PRINTFUL_SIGNATURE'] : '';
    $token = get_option( 'tryl_printful_token' );
    
    if ( empty( $signature ) || empty( $token ) ) {
        status_header( 401 );
        exit;
    }
    
    $body = file_get_contents( 'php://input' );
    $expected_signature = base64_encode( hash_hmac( 'sha256', $body, $token, true ) );
    
    if ( ! hash_equals( $signature, $expected_signature ) ) {
        status_header( 401 );
        exit;
    }
    
    $data = json_decode( $body, true );
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        status_header( 400 );
        exit;
    }
    
    // Handle order status updates from Printful
    if ( ! empty( $data['order'] ) && ! empty( $data['order']['id'] ) ) {
        $printful_order_id = intval( $data['order']['id'] );
        
        // Find WooCommerce order by Printful order ID
        $orders = get_posts( array(
            'post_type'      => 'shop_order',
            'meta_key'       => '_tryl_printful_order_id',
            'meta_value'     => $printful_order_id,
            'posts_per_page' => 1,
        ) );
        
        if ( ! empty( $orders ) ) {
            $order_id = $orders[0]->ID;
            $order = wc_get_order( $order_id );
            
            if ( $order ) {
                // Map Printful status to WooCommerce status
                $printful_status = strtolower( $data['order']['status'] );
                
                // Define status mapping
                $status_map = array(
                    'draft'          => '',
                    'pending'        => 'pending',
                    'failed'         => 'failed',
                    'cancelled'      => 'cancelled',
                    'inprogress'     => 'processing',
                    'fulfilled'      => 'completed',
                    // Add more mappings as needed
                );
                
                if ( isset( $status_map[ $printful_status ] ) && $status_map[ $printful_status ] !== '' ) {
                    $new_status = $status_map[ $printful_status ];
                    $order->update_status( $new_status, 
                        'Updated from Printful: '.$printful_status 
                    );
                }
                
                // Update tracking information if available
                if ( ! empty( $data['order']['tracking'] ) ) {
                    foreach ( $data['order']['tracking'] as $tracking ) {
                        $order->add_order_note( 
                            sprintf( 
                                'Printful tracking update: %s - %s (%s)', 
                                $tracking['carrier'], 
                                $tracking['number'], 
                                $tracking['url'] 
                            ) 
                        );
                    }
                }
            }
        }
    }
    
    // Return success
    header( 'Content-Type: application/json' );
    echo json_encode( [ 'success' => true ] );
    exit;
}
}

// Register Printful order status webhook endpoint
if ( ! function_exists( 'tryl_register_printful_order_webhook' ) ) {
function tryl_register_printful_order_webhook() {
    register_rest_route( 'tryl-printful/v1', '/order-webhook', array(
        'methods'             => 'POST',
        'callback'            => 'tryl_printful_order_status_webhook',
        'args'                => array(
            // No specific args needed for webhook
        ),
    ) );
}
add_action( 'rest_api_init', 'tryl_register_printful_order_webhook' );
}

// Add manual override metabox to order edit screen
if ( ! function_exists( 'tryl_printful_add_order_override_metabox' ) ) {
function tryl_printful_add_order_override_metabox() {
    add_meta_box(
        'tryl_printful_order_override',
        'Printful Order Routing',
        'tryl_printful_order_override_metabox_callback',
        'shop_order',
        'side',
        'high'
    );
}
}
add_action( 'add_meta_boxes', 'tryl_printful_add_order_override_metabox' );

if ( ! function_exists( 'tryl_printful_order_override_metabox_callback' ) ) {
function tryl_printful_order_override_metabox_callback( $post ) {
    // Add nonce for security
    wp_nonce_field( 'tryl_printful_order_override', 'tryl_printful_order_override_nonce' );
    
    // Get current override value
    $override = get_post_meta( $post->ID, '_tryl_printful_manual_override', true );
    ?>
    <p>
        <label for="tryl_printful_manual_override_force">
            <input type="radio" name="tryl_printful_manual_override" value="force" id="tryl_printful_manual_override_force" <?php checked( $override, 'force' ); ?> />
            Force send to Printful (ignore rules)
        </label>
    </p>
    <p>
        <label for="tryl_printful_manual_override_skip">
            <input type="radio" name="tryl_printful_manual_override" value="skip" id="tryl_printful_manual_override_skip" <?php checked( $override, 'skip' ); ?> />
            Skip Printful routing (ignore rules)
        </label>
    </p>
    <p>
        <label for="tryl_printful_manual_override_auto">
            <input type="radio" name="tryl_printful_manual_override" value="auto" id="tryl_printful_manual_override_auto" <?php checked( $override, 'auto' ) || empty( $override ); ?> />
            Use automatic routing rules
        </label>
    </p>
    <?php
}
}

// Save manual override value
if ( ! function_exists( 'tryl_printful_save_order_override' ) ) {
function tryl_printful_save_order_override( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['tryl_printful_order_override_nonce'] ) || 
         ! wp_verify_nonce( $_POST['tryl_printful_order_override_nonce'], 'tryl_printful_order_override' ) ) {
        return;
    }
    
    // Check user permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Save override value
    if ( isset( $_POST['tryl_printful_manual_override'] ) ) {
        $override = sanitize_text_field( $_POST['tryl_printful_manual_override'] );
        update_post_meta( $post_id, '_tryl_printful_manual_override', $override );
    } else {
        // If no value submitted, delete the meta (default to auto)
        delete_post_meta( $post_id, '_tryl_printful_manual_override' );
    }
}
}
add_action( 'save_post_shop_order', 'tryl_printful_save_order_override' );

// Add order routing to checkout process
if ( ! function_exists( 'tryl_printful_route_order_on_checkout' ) ) {
function tryl_printful_route_order_on_checkout( $order_id ) {
    if ( get_option( 'tryl_printful_order_routing' ) !== '1' ) {
        return;
    }
    
    // Small delay to ensure order is fully processed
    sleep( 1 );
    
    // Submit order to Printful
    $result = tryl_printful_submit_order( $order_id );
    
    if ( is_wp_error( $result ) ) {
        // Log error but don't break checkout
        error_log( 'Printful order routing failed for order '.$order_id.': '.$result->get_error_message() );
        // Optionally add a note to the order about the failure
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $order->add_order_note( 
                'Printful order routing failed: '.$result->get_error_message() 
            );
        }
    }
}
}
add_action( 'woocommerce_thankyou', 'tryl_printful_route_order_on_checkout' );
add_action( 'woocommerce_process_shop_order_meta', 'tryl_printful_route_order_on_checkout' );