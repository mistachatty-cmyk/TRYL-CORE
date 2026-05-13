<?php
/**
 * Plugin Name: TRYL Premium E-Commerce Core Universal
 * Description: All-in-one TRYL shop engine. Nike-inspired product pages, premium cart/checkout, and global nav enhancement.
 * Version: 3.2
 * Author: EHDesigns | Powered by LokServices
 * 
 * CHANGELOG:
 * 3.2 - Added intuitive Dev Dashboard for settings management.
 * 3.1 - Added single product template override, AJAX single-product add-to-cart, and conditional mini-cart loading logic.
 * 3.0 - Initial consolidation of premium cart, checkout, and shop grid features.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ─── 1. SINGLE PRODUCT OVERRIDE ───────────────────────────────────────────────
function tryl_single_product_template( $template ) {
    if ( is_singular( 'product' ) ) {
        $t = plugin_dir_path( __FILE__ ) . 'templates/single-product.php';
        if ( file_exists( $t ) ) return $t;
    }
    return $template;
}
add_filter( 'template_include', 'tryl_single_product_template', 999999 );

// ─── 2. SHOP GRID SHORTCODE ───────────────────────────────────────────────────
if ( ! function_exists( 'tryl_3d_shop_shortcode' ) ) {
function tryl_3d_shop_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) return '<p>WooCommerce required.</p>';
    ob_start();
    $limit = get_option('tryl_shop_grid_limit', 32);
    $signature = get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices');
    $products = wc_get_products( [ 'status' => 'publish', 'limit' => $limit, 'return' => 'objects' ] );
    $all_cats = [];
    foreach ( $products as $p ) {
        $terms = wp_get_post_terms( $p->get_id(), 'product_cat' );
        if ( ! is_wp_error( $terms ) ) foreach ( $terms as $t ) $all_cats[ $t->slug ] = $t->name;
    }
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    .tryl-shop{background:var(--off);font-family:'Inter',sans-serif;padding:48px 0 80px;}
    .tryl-shop-inner{max-width:1280px;margin:0 auto;padding:0 32px;}
    .tryl-shop-header{display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:20px;padding-bottom:28px;border-bottom:2px solid var(--dark);margin-bottom:40px;}
    .tryl-shop-title{font-family:'Barlow Condensed',sans-serif;font-weight:900;font-size:2.8rem;text-transform:uppercase;color:var(--dark);line-height:1;}
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
    .tryl-card-name{font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1.05rem;text-transform:uppercase;color:var(--dark);margin-bottom:4px;}
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
    </style>
    <div class="tryl-shop">
      <div class="tryl-shop-inner">
        <div class="tryl-shop-header">
          <div>
            <div class="tryl-shop-title">The Collection</div>
            <div class="tryl-shop-count"><?php echo count($products); ?> Items Available</div>
          </div>
          <div class="tryl-filters">
            <button class="tryl-filter active" data-filter="all">All</button>
            <?php foreach($all_cats as $slug=>$name): ?>
            <button class="tryl-filter" data-filter="cat-<?php echo esc_attr($slug);?>"><?php echo esc_html($name);?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="tryl-grid" id="trylGrid">
          <?php foreach($products as $product):
            $pid      = $product->get_id();
            $purl     = get_permalink($pid);
            $is_var   = $product->is_type('variable');
            $buy_url  = $is_var ? $purl : add_query_arg('add-to-cart',$pid,wc_get_checkout_url());
            $btn_txt  = $is_var ? 'Choose Size' : 'Buy Now';
            $img      = wp_get_attachment_image_url($product->get_image_id(),'full') ?: 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=500';
            $cats     = wp_get_post_terms($pid,'product_cat',['fields'=>'slugs']);
            $cat_cls  = !is_wp_error($cats)&&!empty($cats) ? 'cat-'.implode(' cat-',$cats) : '';
          ?>
          <div class="tryl-card <?php echo esc_attr($cat_cls);?>" data-item>
            <div class="tryl-card-img" data-tilt data-tilt-max="8" data-tilt-speed="400" data-tilt-glare data-tilt-max-glare="0.15">
              <img src="<?php echo esc_url($img);?>" alt="<?php echo esc_attr($product->get_name());?>" loading="lazy">
              <div class="tryl-card-overlay">
                <a href="<?php echo esc_url($buy_url);?>" class="tryl-card-buy"><?php echo esc_html($btn_txt);?></a>
                <a href="<?php echo esc_url($purl);?>" class="tryl-card-view">View Details</a>
              </div>
            </div>
            <div class="tryl-card-info">
              <div class="tryl-card-name"><a href="<?php echo esc_url($purl);?>"><?php echo esc_html($product->get_name());?></a></div>
              <div class="tryl-card-cat"><?php echo wp_strip_all_tags(wc_get_product_category_list($pid));?></div>
              <div class="tryl-card-footer-actions">
                <div class="tryl-card-price"><?php echo $product->get_price_html();?></div>
                <?php if($is_var): ?>
                <a href="<?php echo esc_url($purl);?>" class="tryl-atc tryl-atc-choose" data-pid="<?php echo $pid;?>" data-variable="1">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  <span>Choose Options</span>
                </a>
                <?php else: ?>
                <button class="tryl-atc" data-pid="<?php echo $pid;?>">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  <span>Add to Cart</span>
                </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach;?>
        </div>
        <div class="tryl-powered"><?php echo wp_kses_post($signature); ?></div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
      if(typeof VanillaTilt!=='undefined') VanillaTilt.init(document.querySelectorAll('[data-tilt]'));
      document.querySelectorAll('.tryl-filter').forEach(btn=>{
        btn.addEventListener('click',()=>{
          document.querySelectorAll('.tryl-filter').forEach(b=>b.classList.remove('active'));
          btn.classList.add('active');
          const f=btn.dataset.filter;
          document.querySelectorAll('[data-item]').forEach(c=>{
            c.style.display=(f==='all'||c.classList.contains(f))?'flex':'none';
          });
        });
      });
    });
    </script>
    <?php
    return ob_get_clean();
}
} // endif function_exists( 'tryl_3d_shop_shortcode' )
if ( ! shortcode_exists( 'tryl_3d_shop' ) ) {
    add_shortcode( 'tryl_3d_shop', 'tryl_3d_shop_shortcode' );
}

// ─── 3. PREMIUM CART & CHECKOUT CSS ──────────────────────────────────────────
function tryl_premium_cart_checkout_css() {
    if ( ! is_cart() && ! is_checkout() ) return;
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        --input-bg: #fff;
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
        --input-bg: #f2f0eb;
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
        --input-bg: #132615;
    }
    *{box-sizing:border-box;}
    body{background:var(--off)!important;font-family:'Inter',sans-serif!important;color:var(--txt)!important;transition:background .3s,color .3s;}

    /* ── Page Headings ── */
    h1.entry-title,h1,h2.woocommerce-cart-title,h1.woocommerce-order-confirmation-title{
      font-family:'Barlow Condensed',sans-serif!important;font-weight:900!important;font-size:2.6rem!important;
      text-transform:uppercase!important;color:var(--dark)!important;letter-spacing:.02em!important;
      border:none!important;margin-bottom:36px!important;
    }

    /* ── Main Wrapper ── */
    .woocommerce{font-family:'Inter',sans-serif!important;max-width:1200px!important;margin:48px auto!important;padding:0 32px!important;}
    @media(max-width:700px){.woocommerce{padding:0 16px!important;margin:32px auto!important;}}

    /* ── Cart 2-column layout ── */
    .woocommerce-cart .woocommerce{display:grid!important;grid-template-columns:1fr!important;}
    .woocommerce-cart .woocommerce-cart-form{margin-bottom:0!important;}
    .woocommerce-cart .cart-collaterals{margin-top:0!important;}

    /* ── Table ── */
    .woocommerce table.shop_table{border:none!important;border-radius:0!important;border-collapse:collapse!important;width:100%!important;}
    .woocommerce table.shop_table th{font-size:.65rem!important;font-weight:700!important;letter-spacing:.15em!important;text-transform:uppercase!important;color:var(--muted)!important;padding:0 0 16px!important;border-bottom:2px solid var(--dark)!important;background:transparent!important;}
    .woocommerce table.shop_table td{border:none!important;border-bottom:1px solid var(--border)!important;padding:28px 12px!important;background:transparent!important;vertical-align:middle!important;}
    .woocommerce table.shop_table .product-thumbnail img{width:100px!important;height:100px!important;object-fit:contain!important;background:var(--input-bg)!important;border:1px solid var(--border)!important;padding:8px!important;}
    .woocommerce table.shop_table .product-name a{font-family:'Barlow Condensed',sans-serif!important;font-weight:700!important;font-size:1.15rem!important;text-transform:uppercase!important;color:var(--dark)!important;text-decoration:none!important;}
    .woocommerce table.shop_table .product-name a:hover{color:var(--green)!important;}
    .woocommerce table.shop_table .product-remove a{font-size:22px!important;color:var(--muted)!important;background:transparent!important;line-height:1!important;}
    .woocommerce table.shop_table .product-remove a:hover{color:var(--dark)!important;background:transparent!important;}
    .woocommerce .quantity input.qty{border:1.5px solid var(--border)!important;border-radius:0!important;height:44px!important;width:64px!important;text-align:center!important;font-family:'Inter',sans-serif!important;font-size:.9rem!important;font-weight:600!important;background:var(--input-bg)!important;color:var(--txt)!important;}
    .woocommerce .quantity input.qty:focus{border-color:var(--dark)!important;outline:none!important;}

    /* Coupon & Actions */
    .woocommerce-cart .actions{border:none!important;padding:20px 0!important;display:flex!important;gap:12px!important;align-items:center!important;}
    .woocommerce .coupon{display:flex!important;gap:8px!important;flex:1!important;}
    .woocommerce .coupon input.input-text{height:48px!important;border:1.5px solid var(--border)!important;border-radius:0!important;padding:0 16px!important;font-family:'Inter',sans-serif!important;background:var(--input-bg)!important;color:var(--txt)!important;flex:1!important;}
    .woocommerce .coupon input.input-text:focus{border-color:var(--dark)!important;outline:none!important;}

    /* Cart Totals Box */
    .woocommerce .cart-collaterals{margin-top:32px!important;}
    .woocommerce .cart_totals{background:var(--input-bg)!important;border:1px solid var(--border)!important;padding:32px!important;}
    .woocommerce .cart_totals h2{font-size:1.6rem!important;margin-bottom:24px!important;}
    .woocommerce .cart_totals table.shop_table th{display:table-cell!important;font-family:'Inter',sans-serif!important;text-transform:none!important;font-size:.85rem!important;font-weight:500!important;color:var(--muted)!important;}
    .woocommerce .cart_totals table.shop_table td{text-align:right!important;font-weight:700!important;font-size:.95rem!important;}
    .woocommerce .cart_totals .order-total th,.woocommerce .cart_totals .order-total td{font-size:1.1rem!important;color:var(--dark)!important;}

    /* ── Buttons ── */
    .woocommerce button.button,
    .woocommerce a.checkout-button,
    .woocommerce input.button,
    .woocommerce #place_order{
      font-family:'Inter',sans-serif!important;font-weight:700!important;font-size:.78rem!important;
      letter-spacing:.14em!important;text-transform:uppercase!important;
      background:var(--dark)!important;color:var(--btn-txt)!important;
      border:none!important;border-radius:0!important;
      padding:16px 28px!important;cursor:pointer!important;
      transition:background .25s!important;
    }
    .woocommerce button.button:hover,
    .woocommerce a.checkout-button:hover,
    .woocommerce #place_order:hover{background:var(--green)!important;}
    .woocommerce button.button[name="update_cart"]{
      background:var(--input-bg)!important;color:var(--txt)!important;
      border:1.5px solid var(--border)!important;
    }
    .woocommerce button.button[name="update_cart"]:hover{border-color:var(--dark)!important;}
    .woocommerce a.checkout-button{display:block!important;width:100%!important;text-align:center!important;margin-top:16px!important;padding:18px 28px!important;font-size:.8rem!important;}

    /* ── Checkout Fields ── */
    .woocommerce-checkout .woocommerce-input-wrapper input,
    .woocommerce-checkout .woocommerce-input-wrapper select,
    .woocommerce-checkout .woocommerce-input-wrapper textarea{
      border:1.5px solid var(--border)!important;border-radius:0!important;
      padding:14px 16px!important;background:var(--input-bg)!important;color:var(--txt)!important;
      font-family:'Inter',sans-serif!important;font-size:.9rem!important;
      transition:border-color .2s!important;
    }
    .woocommerce-checkout .woocommerce-input-wrapper input:focus,
    .woocommerce-checkout .woocommerce-input-wrapper select:focus{
      border-color:var(--dark)!important;outline:none!important;
    }
    .woocommerce form .form-row label{font-size:.72rem!important;font-weight:600!important;letter-spacing:.08em!important;text-transform:uppercase!important;color:var(--muted)!important;margin-bottom:6px!important;}

    /* Payment box */
    #payment{background:var(--input-bg)!important;border:1px solid var(--border)!important;padding:28px!important;border-radius:0!important;color:var(--txt)!important;}
    #payment ul.payment_methods{border-bottom:1px solid var(--border)!important;padding-bottom:20px!important;margin-bottom:20px!important;}
    #payment ul.payment_methods li{background:var(--input-bg)!important;border:1.5px solid var(--border)!important;padding:16px!important;margin-bottom:8px!important;border-radius:0!important;list-style:none!important;transition:border-color .2s!important;}
    #payment ul.payment_methods li:has(input:checked){border-color:var(--dark)!important;}

    /* Order review images */
    .tryl-order-thumb{width:64px;height:64px;object-fit:contain;background:var(--input-bg);border:1px solid var(--border);padding:4px;vertical-align:middle;margin-right:12px;}
    .tryl-order-name{font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;text-transform:uppercase;vertical-align:middle;}
    </style>
    <?php
}
add_action( 'wp_head', 'tryl_premium_cart_checkout_css' );

function tryl_premium_cart_checkout_gsap() {
    if ( ! is_cart() && ! is_checkout() ) return;
    ?>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (typeof gsap !== "undefined") {
                gsap.fromTo(".woocommerce", 
                    { opacity: 0, y: 20 }, 
                    { opacity: 1, y: 0, duration: 0.8, ease: "power3.out", delay: 0.1 }
                );
            }
        });
    </script>
    <?php
}
add_action( 'wp_footer', 'tryl_premium_cart_checkout_gsap' );

// ─── 4. INJECT GLOBAL SITE NAV ───────────────────────────────────────────────
function tryl_global_nav_css() {
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    .tryl-nav-brand-bar{font-family:'Barlow Condensed',sans-serif;font-weight:900;font-size:1.1rem;letter-spacing:.05em;text-transform:uppercase;color:var(--ry-accent);text-decoration:none;}
    .tryl-nav-brand-logo{display:flex;align-items:center;}
    .tryl-nav-brand-logo img{max-height:40px;width:auto;display:block;}
    .tryl-nav-links-bar{display:flex;gap:32px;list-style:none;margin:0;padding:0;}
    .tryl-nav-links-bar a{font-family:'Inter',sans-serif;font-size:.7rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--ry-text);text-decoration:none;transition:color .2s;}
    .tryl-nav-links-bar a:hover{color:var(--ry-accent);}
    @media(max-width:700px){.tryl-nav-links-bar{display:none;}}

    /* ── Mobile Hamburger & Menu ── */
    .tryl-hamburger{display:none;flex-direction:column;justify-content:space-between;width:26px;height:18px;background:transparent;border:none;cursor:pointer;padding:0;z-index:10000;}
    .tryl-hamburger span{width:100%;height:2px;background:var(--ry-hamburger);transition:all .3s ease;border-radius:2px;}
    .tryl-hamburger.open span:nth-child(1){transform:translateY(8px) rotate(45deg);}
    .tryl-hamburger.open span:nth-child(2){opacity:0;}
    .tryl-hamburger.open span:nth-child(3){transform:translateY(-8px) rotate(-45deg);}
    @media(max-width:700px){.tryl-hamburger{display:flex;}}
    .tryl-mobile-nav{position:fixed;top:64px;left:0;right:0;background:var(--ry-nav-bg);backdrop-filter:blur(18px);border-bottom:1px solid var(--ry-border);padding:24px 20px;display:flex;flex-direction:column;gap:16px;z-index:9998;transform:translateY(-100%);opacity:0;visibility:hidden;transition:all .4s cubic-bezier(0.25, 0.46, 0.45, 0.94);}
    .tryl-mobile-nav.open{transform:translateY(0);opacity:1;visibility:visible;box-shadow:0 10px 30px rgba(0,0,0,0.05);}
    .tryl-mobile-nav a{font-family:'Inter',sans-serif;font-size:1.1rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--ry-text);text-decoration:none;border-bottom:1px solid var(--ry-border);padding-bottom:12px;}
    .tryl-mobile-nav a:last-child{border-bottom:none;padding-bottom:0;}
    
    /* ── Theme Switcher ── */
    .tryl-theme-switcher { display: flex; gap: 8px; align-items: center; margin-left: 24px; padding-left: 24px; border-left: 1px solid var(--ry-border); }
    @media(max-width:700px){.tryl-theme-switcher{margin: 16px 0 0 0; padding: 16px 0 0 0; border-left: none; border-top: 1px solid var(--ry-border); justify-content: flex-start;}}
    .tryl-ts-btn { width: 18px; height: 18px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; padding: 0; transition: transform 0.2s, border-color 0.2s; }
    .tryl-ts-btn:hover { transform: scale(1.15); }
    .tryl-ts-btn.active { border-color: var(--ry-accent); transform: scale(1.15); box-shadow: 0 0 0 2px var(--ry-bg); }
    .tryl-ts-bright { background: #f5f8f5; border-color: #d4e0d4; }
    .tryl-ts-mild { background: #e6e4df; border-color: #c4c0b5; }
    .tryl-ts-dark { background: #0d1b0f; border-color: #2d6a4f; }
    </style>
    <?php
}
add_action( 'wp_head', 'tryl_global_nav_css' );

function tryl_inject_nav_bar() {
    $logo_url = get_option( 'tryl_header_logo' );
    if ( empty( $logo_url ) ) {
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        $logo_url       = $custom_logo_id ? wp_get_attachment_image_url( $custom_logo_id, 'full' ) : '';
        // Fallback for Divi's proprietary logo system
        if ( empty( $logo_url ) && function_exists( 'et_get_option' ) ) {
            $logo_url = et_get_option( 'divi_logo' );
        }
    }
    $shop_url       = function_exists('wc_get_page_id') ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url('/shop/');
    
    $nav_items = [
        'Shop'           => $shop_url,
        'Mission'        => get_option('tryl_nav_mission', home_url('/mission/')),
        'Prayer Request' => get_option('tryl_nav_prayer', home_url('/prayer-request/')),
        'Contact'        => get_option('tryl_nav_contact', home_url('/contact/')),
    ];
    ?>
    <nav class="tryl-injected-nav">
      <?php if ( $logo_url ) : ?>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="tryl-nav-brand-logo"><img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"></a>
      <?php else : ?>
      <a href="<?php echo esc_url(home_url('/')); ?>" class="tryl-nav-brand-bar">The Righteous Yield Life</a>
      <?php endif; ?>
      <ul class="tryl-nav-links-bar">
        <?php foreach($nav_items as $label=>$href): ?>
        <li><a href="<?php echo esc_url($href);?>"><?php echo esc_html($label);?></a></li>
        <?php endforeach;?>
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
      <?php foreach($nav_items as $label=>$href): ?>
      <a href="<?php echo esc_url($href);?>"><?php echo esc_html($label);?></a>
      <?php endforeach;?>
      <div class="tryl-theme-switcher">
          <button class="tryl-ts-btn tryl-ts-bright" data-set-theme="bright" aria-label="Bright Mode"></button>
          <button class="tryl-ts-btn tryl-ts-mild" data-set-theme="mild" aria-label="Mild Mode"></button>
          <button class="tryl-ts-btn tryl-ts-dark" data-set-theme="dark" aria-label="Dark Mode"></button>
      </div>
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
    ?>
    <style>
    /* ── Site Footer (Global) ── */
    /* Hide default footers from Divi/SeedProd to prevent duplicates */
    #main-footer, footer.site-footer, #colophon, .site-info, .footer-widgets { display: none !important; }
    
    .tryl-global-footer {
        background: var(--ry-footer-bg);
        color: var(--ry-footer-text);
        padding: 64px 40px 32px;
        font-family: 'Inter', sans-serif;
        margin-top: auto;
        position: relative;
        z-index: 10;
        transition: background .3s, color .3s;
    }
    @media(max-width:700px) { .tryl-global-footer { padding: 48px 20px 24px; } }
    .tryl-footer-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 40px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-bottom: 40px;
        margin-bottom: 24px;
    }
    .tryl-footer-brand { max-width: 320px; }
    .tryl-footer-logo-text {
        font-family: 'Barlow Condensed', sans-serif;
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
        font-family: 'Barlow Condensed', sans-serif;
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
        transition: color .2s;
        letter-spacing: .02em;
    }
    .tryl-footer-links-col a:hover { color: var(--ry-footer-accent); }
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
    </style>
    <?php
}
add_action( 'wp_head', 'tryl_global_footer_css' );

function tryl_inject_global_footer() {
    $shop_url = function_exists('wc_get_page_id') ? get_permalink( wc_get_page_id( 'shop' ) ) : home_url('/shop/');
    $footer_desc = get_option('tryl_footer_desc', 'Faith-forward essentials crafted with intention. Wear your values, represent your faith, and yield righteousness in all that you do.');
    $signature = get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices');
    ?>
    <footer class="tryl-global-footer">
        <div class="tryl-footer-inner">
            <div class="tryl-footer-brand">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="tryl-footer-logo-text">The Righteous Yield Life</a>
                <p class="tryl-footer-desc"><?php echo esc_html($footer_desc); ?></p>
            </div>
            
            <div class="tryl-footer-links-col">
                <div class="tryl-footer-links-title">Explore</div>
                <a href="<?php echo esc_url($shop_url); ?>">The Collection</a>
                <a href="<?php echo esc_url(home_url('/mission/')); ?>">Our Mission</a>
                <a href="<?php echo esc_url(home_url('/prayer-request/')); ?>">Prayer Request</a>
            </div>
            
            <div class="tryl-footer-links-col">
                <div class="tryl-footer-links-title">Support</div>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact Us</a>
                <a href="<?php echo esc_url(home_url('/shipping-returns/')); ?>">Shipping & Returns</a>
                <a href="<?php echo esc_url(home_url('/faq/')); ?>">FAQ</a>
            </div>
        </div>
        <div class="tryl-footer-bottom">
            <div>&copy; <?php echo date('Y'); ?> The Righteous Yield Life. All rights reserved. 
                <span style="margin-left: 8px; opacity: 0.8;" class="tryl-signature"><?php echo wp_kses_post($signature); ?></span>
            </div>
            <div style="display:flex; gap:16px;">
                <a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>">Privacy Policy</a>
                <a href="<?php echo esc_url(home_url('/terms/')); ?>">Terms of Service</a>
            </div>
        </div>
    </footer>
    <?php
}
add_action( 'wp_footer', 'tryl_inject_global_footer', 5 );

// ─── 6. RICH PRODUCT IMAGES ON CHECKOUT ORDER REVIEW ─────────────────────────
function tryl_checkout_item_name( $name, $cart_item, $key ) {
    if ( ! is_checkout() ) return $name;
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
    if ( is_woocommerce() || is_cart() || is_checkout() ) return true;
    
    global $post;
    if ( is_a( $post, 'WP_Post' ) ) {
        if ( has_shortcode( $post->post_content, 'tryl_3d_shop' ) ) return true;
        if ( has_shortcode( $post->post_content, 'tryl_shop_editorial' ) ) return true;
    }
    
    if ( is_page_template( 'page-righteous-shop.php' ) ) return true;
    
    return false;
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
      --mc-header-font: 'Barlow Condensed', sans-serif;
      --mc-body-font: 'Inter', sans-serif;
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
    .tryl-mc-btn-checkout{background:var(--mc-dark);color:var(--mc-btn-text);}
    .tryl-mc-btn-checkout:hover{background:var(--mc-accent);color:var(--mc-btn-text);}
    .tryl-mc-btn-view{background:transparent;color:var(--mc-text);border:1.5px solid var(--mc-border);}
    .tryl-mc-btn-view:hover{border-color:var(--mc-dark);}
    </style>
    <?php
}
add_action( 'wp_head', 'tryl_mini_cart_assets' );

function tryl_mini_cart_html() {
    if ( ! tryl_should_load_mini_cart() ) return;
    $count    = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    $subtotal = WC()->cart ? WC()->cart->get_cart_subtotal() : '$0.00';
    $items    = WC()->cart ? WC()->cart->get_cart() : [];
    $free_ship_threshold = get_option('tryl_free_shipping_threshold', '75');
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
              <span class="tryl-mc-item-price"><?php echo WC()->cart->get_product_price( $prod ); ?></span>
              <button class="tryl-mc-item-remove" data-key="<?php echo esc_attr($key); ?>" aria-label="Remove">&times;</button>
            </div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <div class="tryl-mc-footer" id="trylMcFooter" <?php if ( empty( $items ) ) echo 'style="display:none"'; ?>>
        <div class="tryl-mc-free-ship" id="trylMcFreeShip">Free shipping on orders over $<?php echo esc_html($free_ship_threshold); ?></div>
        <div class="tryl-mc-subtotal">
          <span class="tryl-mc-subtotal-label">Subtotal</span>
          <span class="tryl-mc-subtotal-amount" id="trylMcSubtotal"><?php echo $subtotal; ?></span>
        </div>
        <div class="tryl-mc-btns">
          <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="tryl-mc-btn tryl-mc-btn-checkout">Checkout</a>
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
          el.style.display = count > 0 ? '' : 'none';
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
          if (subtotalEl) subtotalEl.textContent = res.data.subtotal;
          if (footerEl) footerEl.style.display = res.data.count > 0 ? '' : 'none';
          if (freeShip) freeShip.textContent = res.data.free_ship || '';
          updateCounts(res.data.count);
          bindQtyButtons();
          if (typeof callback === 'function') callback(res);
        });
      }

      // ── AJAX Add to Cart ──
      document.addEventListener('click', function(e) {
        var btn = e.target.closest('.tryl-atc');
        if (!btn || btn.classList.contains('tryl-atc-choose')) return;
        if (btn.tagName === 'A') e.preventDefault();
        if (btn.classList.contains('loading')) return;

        var pid = btn.dataset.pid;
        if (!pid) return;

        btn.classList.add('loading');

        var fd = new FormData();
        fd.append('action', 'tryl_ajax_add_to_cart');
        fd.append('product_id', pid);
        fd.append('quantity', 1);

        fetch(trylMiniCart.ajaxurl, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(fd) })
        .then(function(r){ return r.json(); })
        .then(function(res){
          btn.classList.remove('loading');
          if (res.success) {
            btn.classList.add('added');
            setTimeout(function(){ btn.classList.remove('added'); }, 1200);
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
            btn.textContent = 'Added!';
            setTimeout(function(){ btn.classList.remove('added'); btn.textContent = btn.dataset.ogText; }, 2000);
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
    })();
    </script>
    <?php
}
add_action( 'wp_footer', 'tryl_mini_cart_html' );

// ─── 8. AJAX HANDLERS ──────────────────────────────────────────────────────────
function tryl_ajax_add_to_cart_handler() {
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
          <span class="tryl-mc-item-price"><?php echo WC()->cart->get_product_price( $prod ); ?></span>
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

// ─── 9. LOCALIZE MINI CART SCRIPT ────────────────────────────────────────────
function tryl_localize_minicart() {
    if ( ! tryl_should_load_mini_cart() ) return;
    ?>
    <script>var trylMiniCart = { ajaxurl: '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>' };</script>
    <?php
}
add_action( 'wp_head', 'tryl_localize_minicart' );

// ─── 10. ADMIN DASHBOARD SETTINGS ────────────────────────────────────────────
function tryl_register_admin_page() {
    add_menu_page( 'TRYL Settings', 'TRYL Settings', 'manage_options', 'tryl-ecommerce-settings', 'tryl_admin_page_html', 'dashicons-admin-generic', 58 );
}
add_action( 'admin_menu', 'tryl_register_admin_page' );

function tryl_register_settings() {
    $settings = ['tryl_default_theme', 'tryl_shop_grid_limit', 'tryl_nav_mission', 'tryl_nav_prayer', 'tryl_nav_contact', 'tryl_free_shipping_threshold', 'tryl_footer_desc', 'tryl_prayer_email', 'tryl_developer_signature', 'tryl_header_logo', 'tryl_printful_token'];
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
            .tryl-admin-wrap { max-width: 800px; font-family: 'Inter', sans-serif; }
            .tryl-admin-wrap h1 { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 2.5rem; text-transform: uppercase; margin-bottom: 24px; color: #0d1b0f; }
            .tryl-admin-card { background: #fff; border: 1px solid #d4e0d4; padding: 24px 32px; margin-bottom: 24px; border-radius: 4px; box-shadow: 0 8px 24px rgba(0,0,0,0.03); }
            .tryl-admin-card h2 { margin-top: 0; border-bottom: 1px solid #e8f0e8; padding-bottom: 12px; font-size: 1.25rem; font-weight: 700; color: #1a2e1a; margin-bottom: 20px; }
            .tryl-admin-row { margin-bottom: 20px; }
            .tryl-admin-row label { font-weight: 600; display: block; margin-bottom: 8px; color: #1a2e1a; font-size: 0.95rem; }
            .tryl-admin-row input[type="text"], .tryl-admin-row input[type="number"], .tryl-admin-row input[type="email"], .tryl-admin-row textarea, .tryl-admin-row select { width: 100%; max-width: 100%; border: 1px solid #d4e0d4; border-radius: 4px; padding: 10px 12px; font-family: 'Inter', sans-serif; font-size: 0.95rem; transition: border-color 0.2s; }
            .tryl-admin-row input:focus, .tryl-admin-row textarea:focus, .tryl-admin-row select:focus { border-color: #2d6a4f; outline: none; box-shadow: 0 0 0 1px #2d6a4f; }
            .tryl-admin-row p.description { margin-top: 6px; font-size: 0.85rem; color: #6b7c6b; }
            .tryl-admin-save-btn { background: #0d1b0f !important; color: #fff !important; border: none !important; padding: 12px 32px !important; font-size: 1rem !important; font-weight: 600 !important; font-family: 'Inter', sans-serif !important; border-radius: 4px !important; cursor: pointer !important; transition: background 0.2s !important; }
            .tryl-admin-save-btn:hover { background: #2d6a4f !important; }
        </style>
        <h1>TRYL Core Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'tryl_settings_group' ); ?>
            
            <div class="tryl-admin-card">
                <h2>General Options</h2>
                <div class="tryl-admin-row">
                    <label>Default Theme Mode</label>
                    <select name="tryl_default_theme">
                        <option value="bright" <?php selected(get_option('tryl_default_theme', 'bright'), 'bright'); ?>>Bright Mode</option>
                        <option value="mild" <?php selected(get_option('tryl_default_theme', 'bright'), 'mild'); ?>>Mild Mode</option>
                        <option value="dark" <?php selected(get_option('tryl_default_theme', 'bright'), 'dark'); ?>>Dark Mode</option>
                    </select>
                    <p class="description">Sets the fallback theme if the user's OS doesn't specify a preference.</p>
                </div>
                <div class="tryl-admin-row">
                    <label>Shop Grid Product Limit</label>
                    <input type="number" name="tryl_shop_grid_limit" value="<?php echo esc_attr(get_option('tryl_shop_grid_limit', 32)); ?>" />
                    <p class="description">Maximum number of products to show in the global TRYL Shop Grid.</p>
                </div>
                <div class="tryl-admin-row">
                    <label>Header Logo URL</label>
                    <input type="text" name="tryl_header_logo" value="<?php echo esc_attr(get_option('tryl_header_logo')); ?>" />
                    <p class="description">Paste the image URL for your brand logo here. This will override the default theme Customizer logo.</p>
                </div>
            </div>

            <div class="tryl-admin-card">
                <h2>Global Navigation Links</h2>
                <div class="tryl-admin-row">
                    <label>Mission Page URL</label>
                    <input type="text" name="tryl_nav_mission" value="<?php echo esc_attr(get_option('tryl_nav_mission', home_url('/mission/'))); ?>" />
                </div>
                <div class="tryl-admin-row">
                    <label>Prayer Request Page URL</label>
                    <input type="text" name="tryl_nav_prayer" value="<?php echo esc_attr(get_option('tryl_nav_prayer', home_url('/prayer-request/'))); ?>" />
                </div>
                <div class="tryl-admin-row">
                    <label>Contact Page URL</label>
                    <input type="text" name="tryl_nav_contact" value="<?php echo esc_attr(get_option('tryl_nav_contact', home_url('/contact/'))); ?>" />
                </div>
            </div>

            <div class="tryl-admin-card">
                <h2>Cart & Footer Content</h2>
                <div class="tryl-admin-row">
                    <label>Free Shipping Threshold ($)</label>
                    <input type="number" name="tryl_free_shipping_threshold" value="<?php echo esc_attr(get_option('tryl_free_shipping_threshold', '75')); ?>" />
                    <p class="description">The dollar amount displayed in the mini-cart urging users to qualify for free shipping.</p>
                </div>
                <div class="tryl-admin-row">
                    <label>Footer Brand Description</label>
                    <textarea name="tryl_footer_desc" rows="3"><?php echo esc_textarea(get_option('tryl_footer_desc', 'Faith-forward essentials crafted with intention. Wear your values, represent your faith, and yield righteousness in all that you do.')); ?></textarea>
                    <p class="description">The short brand mission statement appearing on the left side of the global footer.</p>
                </div>
                <div class="tryl-admin-row">
                    <label>Developer Signature (Powered By)</label>
                    <input type="text" name="tryl_developer_signature" value="<?php echo esc_attr(get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices')); ?>" />
                    <p class="description">The signature text that appears at the bottom of the shop grids and global footer. Supports text or HTML `&lt;img&gt;` tags.</p>
                </div>
            </div>

            <div class="tryl-admin-card">
                <h2>Forms & Notifications</h2>
                <div class="tryl-admin-row">
                    <label>Prayer Request Notification Email</label>
                    <input type="email" name="tryl_prayer_email" value="<?php echo esc_attr(get_option('tryl_prayer_email', get_option('admin_email'))); ?>" />
                    <p class="description">The email address that will receive new prayer request submissions.</p>
                </div>
            </div>

            <div class="tryl-admin-card">
                <h2>External Integrations</h2>
                <div class="tryl-admin-row">
                    <label>Printful Webhook Token</label>
                    <input type="text" name="tryl_printful_token" value="<?php echo esc_attr(get_option('tryl_printful_token')); ?>" />
                    <p class="description">Secure your Printful webhook. Enter a secret phrase here, then in the Printful Dashboard append it to your URL (e.g., <code>?token=YOUR_PHRASE</code>).</p>
                </div>
            </div>

            <button type="submit" class="tryl-admin-save-btn">Save TRYL Settings</button>
        </form>
    </div>
    <?php
}

// ─── 11. LOKSERVICES BRIDGE MODULE ───────────────────────────────────────────
function lok_bridge_menu() {
    add_options_page( 'LokServices Connection', 'LokServices Bridge', 'manage_options', 'lokservices-bridge', 'lok_bridge_options_page' );
}
add_action( 'admin_menu', 'lok_bridge_menu' );

function lok_bridge_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    
    if ( isset( $_POST['lok_api_key'] ) && check_admin_referer('lok_save_key') ) {
        update_option( 'lokservices_api_key', sanitize_text_field( $_POST['lok_api_key'] ) );
        echo '<div class="notice notice-success is-dismissible"><p>Connection Key saved securely.</p></div>';
    }
    
    $key = get_option( 'lokservices_api_key', wp_generate_password( 32, false ) );
    $api_url = rest_url( 'lokservices/v1/deploy' );
    ?>
    <div class="wrap" style="font-family: 'Inter', sans-serif; max-width: 850px;">
        <style>
            .lok-admin-header { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 2.5rem; text-transform: uppercase; margin-bottom: 8px; color: #0d1b0f; }
            .lok-card { background: #fff; border: 1px solid #d4e0d4; padding: 24px 32px; margin-bottom: 24px; border-radius: 4px; box-shadow: 0 8px 24px rgba(0,0,0,0.03); }
            .lok-card h2 { margin-top: 0; border-bottom: 1px solid #e8f0e8; padding-bottom: 12px; font-size: 1.25rem; font-weight: 700; margin-bottom: 20px; }
            .lok-input { width: 100%; max-width: 400px; border: 1px solid #d4e0d4; padding: 10px 12px; font-family: monospace; font-size: 1rem; }
            .lok-btn { background: #0d1b0f !important; color: #fff !important; border: none !important; padding: 10px 24px !important; font-size: 0.95rem !important; font-weight: 600 !important; cursor: pointer !important; }
        </style>
        <h1 class="lok-admin-header">LokServices Bridge</h1>
        <div class="lok-card">
            <h2>1. Connection Credentials</h2>
            <form method="POST">
                <?php wp_nonce_field('lok_save_key'); ?>
                <p><strong>Your Secret API Key:</strong></p>
                <input type="text" name="lok_api_key" class="lok-input" value="<?php echo esc_attr( $key ); ?>">
                <p><button type="submit" class="lok-btn">Save Key</button></p>
            </form>
            <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #e8f0e8;">
                <p><strong>Your API Endpoint URL:</strong></p>
                <code style="background: #f5f8f5; padding: 6px 10px; border: 1px solid #d4e0d4; display: block;"><?php echo esc_html($api_url); ?></code>
            </div>
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
    $provided_key = $request->get_header( 'X-Lok-Key' );
    $stored_key   = get_option( 'lokservices_api_key' );
    if ( empty( $stored_key ) || $provided_key !== $stored_key ) {
        return new WP_Error( 'forbidden', 'Invalid or missing LokServices API Key.', array( 'status' => 403 ) );
    }
    return true;
}

function lok_bridge_handle_deployment( WP_REST_Request $request ) {
    $file_path = $request->get_param( 'file_path' );
    $content   = $request->get_param( 'content' );
    if ( empty( $file_path ) || empty( $content ) || strpos( $file_path, '..' ) !== false ) {
        return new WP_Error( 'invalid', 'Invalid request.', array( 'status' => 400 ) );
    }
    $target_file = WP_CONTENT_DIR . '/' . ltrim( $file_path, '/' );
    $result = file_put_contents( $target_file, $content );
    return $result !== false ? new WP_REST_Response( [ 'success' => true ], 200 ) : new WP_Error( 'error', 'Write failed', [ 'status' => 500 ] );
}
