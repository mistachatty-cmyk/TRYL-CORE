<?php
/**
 * Plugin Name: TRYL Editorial Shop Skin
 * Description: Soft, airy luxury editorial skin. Use shortcode [tryl_shop_editorial]. Modular — works alongside the existing shop grid.
 * Version: 1.0
 * Author: EHDesigns | Powered by LokServices
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// ─── EDITORIAL SHOP SHORTCODE ─────────────────────────────────────────────────
if ( ! function_exists( 'tryl_editorial_shop_shortcode' ) ) {
function tryl_editorial_shop_shortcode( $atts ) {
    if ( ! class_exists( 'WooCommerce' ) ) return '<p>WooCommerce required.</p>';

    $atts = shortcode_atts( [
        'limit'   => 24,
        'columns' => 3,
    ], $atts );

    ob_start();
    $signature = get_option('tryl_developer_signature', 'Made by EHDesigns and powered by LokServices');

    $products = wc_get_products( [
        'status' => 'publish',
        'limit'  => (int) $atts['limit'],
        'return' => 'objects',
    ] );

    // Build category list for filters
    $cats = [];
    foreach ( $products as $p ) {
        $terms = wp_get_post_terms( $p->get_id(), 'product_cat' );
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $t ) $cats[ $t->slug ] = $t->name;
        }
    }
    $badges_active = get_option('tryl_badges_active');
    $badges_new_days = (int) get_option('tryl_badges_new_days', 14);
    $badges_bestseller_sales = (int) get_option('tryl_badges_bestseller_sales', 50);
    $badges_bg = get_option('tryl_badges_bg', '#31d190');
    $badges_text_color = get_option('tryl_badges_text_color', '#0d1b0f');
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

    <style>
    /* ── EDITORIAL TOKENS ── */
    :root, [data-theme="bright"] {
      --te-cream: #faf8f3;
      --te-warm:  #f2ede3;
      --te-sand:  #e8e0d0;
      --te-ink:   #1c1a17;
      --te-stone: #6b6560;
      --te-sage:  #4a7c59;
      --te-sage-l:#deeae2;
      --te-card:  #ffffff;
      --te-view:  rgba(255,255,255,0.9);
      --te-view-h:#ffffff;
    }
    [data-theme="mild"] {
      --te-cream: #e6e4df;
      --te-warm:  #dcd9d1;
      --te-sand:  #ccc8be;
      --te-ink:   #33322e;
      --te-stone: #7a7671;
      --te-sage:  #5c6e5c;
      --te-sage-l:#cdd9cd;
      --te-card:  #f2f0eb;
      --te-view:  rgba(242,240,235,0.9);
      --te-view-h:#f2f0eb;
    }
    [data-theme="dark"] {
      --te-cream: #1c1a17;
      --te-warm:  #2a2723;
      --te-sand:  #3f3b36;
      --te-ink:   #faf8f3;
      --te-stone: #a39d96;
      --te-sage:  #6b9c7a;
      --te-sage-l:#2c3b31;
      --te-card:  #23211d;
      --te-view:  rgba(28,26,23,0.9);
      --te-view-h:#1c1a17;
    }
    .te-wrap{
      --radius:12px;
      --shadow:0 4px 24px rgba(0,0,0,.07);
      --shadow-hover:0 16px 48px rgba(0,0,0,.13);
      background:var(--te-cream);
      font-family:'Inter',sans-serif;
      color:var(--te-ink);
      padding:56px 0 100px;
      transition: background .3s, color .3s;
    }
    .te-inner{max-width:1260px;margin:0 auto;padding:0 36px;}
    @media(max-width:700px){.te-inner{padding:0 18px;}}

    /* ── Header ── */
    .te-header{text-align:center;margin-bottom:56px;}
    .te-eyebrow{font-size:.65rem;font-weight:600;letter-spacing:.22em;text-transform:uppercase;color:var(--te-sage);margin-bottom:16px;}
    .te-heading{font-family:'Cormorant Garamond',serif;font-weight:600;font-size:clamp(2.4rem,5vw,3.8rem);line-height:1.05;color:var(--te-ink);margin-bottom:18px;transition: color .3s;}
    .te-subheading{font-size:.85rem;font-weight:300;color:var(--te-stone);max-width:420px;margin:0 auto;line-height:1.7;transition: color .3s;}

    /* ── Filters ── */
    .te-filters{display:flex;justify-content:center;flex-wrap:wrap;gap:8px;margin-bottom:52px;}
    .te-filter{padding:9px 22px;font-size:.68rem;font-weight:500;letter-spacing:.1em;text-transform:uppercase;border:1px solid var(--te-sand);background:transparent;color:var(--te-stone);cursor:pointer;border-radius:40px;transition:all .22s;}
    .te-filter:hover{border-color:var(--te-ink);color:var(--te-ink);}
    .te-filter.active{background:var(--te-ink);color:var(--te-cream);border-color:var(--te-ink);}

    /* ── Grid ── */
    .te-grid{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:28px;
    }
    @media(max-width:960px){.te-grid{grid-template-columns:repeat(2,1fr);gap:20px;}}
    @media(max-width:560px){.te-grid{grid-template-columns:1fr;gap:16px;}}

    /* ── Card ── */
    .te-card{
      background:var(--te-card);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
      display:flex;
      flex-direction:column;
      transition:transform .35s cubic-bezier(.25,.46,.45,.94), box-shadow .35s;
      opacity:0;
      transform:translateY(20px);
      will-change:transform,opacity,box-shadow;
    }
    .te-card.visible{opacity:1;transform:translateY(0);}
    .te-card:hover{transform:translateY(-7px);box-shadow:var(--shadow-hover);}

    /* Image */
    .te-card-img{
      position:relative;
      background:var(--te-warm);
      aspect-ratio:1;
      overflow:hidden;
      cursor:pointer;
    }
    .te-card-img img{
      width:100%;height:100%;object-fit:contain;
      padding:28px;
      transition:transform .55s cubic-bezier(.25,.46,.45,.94);
      will-change:transform;
    }
    .te-card:hover .te-card-img img{transform:scale(1.07);}

    /* Tag badge */
    .te-badge{
      position:absolute;top:14px;left:14px;
      background:var(--te-sage-l);color:var(--te-sage);
      font-size:.58rem;font-weight:600;letter-spacing:.12em;text-transform:uppercase;
      padding:5px 10px;border-radius:20px;
    }
    <?php if ( $badges_active ) : ?>
    .te-badge.dynamic-badge { background: <?php echo esc_attr($badges_bg); ?> !important; color: <?php echo esc_attr($badges_text_color); ?> !important; }
    <?php endif; ?>
    .te-badge.sold-out { background: #d63638 !important; color: #fff !important; }

    /* Quick-action overlay */
    .te-card-actions{
      position:absolute;bottom:0;left:0;right:0;
      padding:16px;display:flex;gap:8px;
      transform:translateY(100%);transition:transform .3s ease;
      will-change:transform;
    }
    .te-card:hover .te-card-actions{transform:translateY(0);}
    .te-action-buy{
      flex:1;padding:11px 0;
      background:var(--te-ink);color:var(--te-cream);
      font-size:.65rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;
      text-align:center;border-radius:8px;
      transition:background .2s;text-decoration:none;
    }
    .te-action-buy:hover{background:var(--te-sage);}
    .te-action-view{
      padding:11px 14px;
      background:var(--te-view);color:var(--te-ink);
      font-size:.65rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;
      text-align:center;border-radius:8px;
      transition:background .2s;text-decoration:none;white-space:nowrap;
    }
    .te-action-view:hover{background:var(--te-view-h);}

    /* Info */
    .te-card-info{padding:20px 22px 24px;}
    .te-card-cat{font-size:.62rem;font-weight:500;letter-spacing:.14em;text-transform:uppercase;color:var(--te-sage);margin-bottom:7px;}
    .te-card-name{
      font-family:'Cormorant Garamond',serif;
      font-weight:600;font-size:1.25rem;
      line-height:1.2;color:var(--te-ink);
      margin-bottom:10px;text-decoration:none;display:block;
      transition:color .2s;
    }
    .te-card-name:hover{color:var(--te-sage);}
    .te-card-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;}
    .te-card-price{font-size:.95rem;font-weight:500;color:var(--te-ink);}
    .te-card-price del{color:var(--te-stone);margin-right:6px;font-size:.85rem;}
    .te-card-footer-actions{display:flex;align-items:center;gap:6px;}
    .te-atc,.te-atc:visited{display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:var(--te-ink);color:var(--te-cream);font-size:.6rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;border:none;border-radius:var(--radius);cursor:pointer;transition:all .22s;text-decoration:none;line-height:1;}
    .te-atc:hover{background:var(--te-sage);color:var(--te-cream);}
    .te-atc-choose,.te-atc-choose:visited{background:transparent;color:var(--te-ink);border:1px solid var(--te-sand);}
    .te-atc-choose:hover{background:var(--te-ink);color:var(--te-cream);border-color:var(--te-ink);}
    .te-atc.loading,.te-atc.disabled{opacity:.5;pointer-events:none;}
    .te-atc.added{background:var(--te-sage);}
    .te-atc-variation:hover{background:var(--te-sage)!important;color:var(--te-cream)!important;}
    .te-card-arrow{
      width:34px;height:34px;border-radius:50%;
      border:1px solid var(--te-sand);background:transparent;
      display:flex;align-items:center;justify-content:center;
      cursor:pointer;transition:all .2s;text-decoration:none;color:var(--te-ink);
    }
    .te-card-arrow:hover{background:var(--te-ink);border-color:var(--te-ink);color:var(--te-cream);}
    .te-card-arrow svg{width:14px;height:14px;}

    /* ── Featured large card (first item) ── */
    .te-card.te-featured{grid-column:span 1;}
    @media(min-width:960px){.te-card.te-featured{grid-column:span 1;}}

    /* ── Bottom CTA ── */
    .te-cta{text-align:center;margin-top:64px;}
    .te-cta-link{
      display:inline-flex;align-items:center;gap:10px;
      padding:16px 40px;border:1.5px solid var(--te-ink);border-radius:40px;
      font-size:.72rem;font-weight:600;letter-spacing:.14em;text-transform:uppercase;
      color:var(--te-ink);text-decoration:none;
      transition:all .25s;
    }
    .te-cta-link:hover{background:var(--te-ink);color:var(--te-cream);}
    .te-cta-link svg{width:16px;height:16px;transition:transform .25s;}
    .te-cta-link:hover svg{transform:translateX(4px);}

    /* ── Soft Cart/Checkout Override (editorial palette) ── */
    .te-wrap .woocommerce-cart,
    .woocommerce-cart .woocommerce,
    .woocommerce-checkout .woocommerce{
      font-family:'Inter',sans-serif!important;
    }
    </style>

    <div class="te-wrap">
      <div class="te-inner">

        <!-- Header -->
        <div class="te-header">
          <div class="te-eyebrow">The Righteous Yield Life</div>
          <h2 class="te-heading">The Collection</h2>
          <p class="te-subheading">Faith-forward essentials crafted with intention — wear your values.</p>
        </div>

        <!-- Filters -->
        <?php if ( ! empty( $cats ) ): ?>
        <div class="te-filters">
          <button class="te-filter active" data-filter="all">Everything</button>
          <?php foreach ( $cats as $slug => $name ): ?>
          <button class="te-filter" data-filter="cat-<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Grid -->
        <div class="te-grid" id="teGrid">
          <?php foreach ( $products as $i => $product ):
            $pid     = $product->get_id();
            $purl    = get_permalink( $pid );
            $is_var  = $product->is_type( 'variable' );
            $is_in_stock = $product->is_in_stock();
            $buy_url = $is_var ? $purl : add_query_arg( 'add-to-cart', $pid, wc_get_checkout_url() );
            $btn_txt = $is_var ? 'Choose Size' : 'Buy Now';
            
            if ( ! $is_in_stock ) {
                $btn_txt = 'Sold Out';
                $buy_url = $purl;
            }
            
            $img     = wp_get_attachment_image_url( $product->get_image_id(), 'large' )
                    ?: 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600';
            $cat_terms = wp_get_post_terms( $pid, 'product_cat' );
            $cat_label = ! is_wp_error($cat_terms) && ! empty($cat_terms) ? $cat_terms[0]->name : '';
            $cat_slugs = ! is_wp_error($cat_terms) ? array_map(function($t) { return $t->slug; }, $cat_terms) : [];
            $cat_cls   = ! empty($cat_slugs) ? 'cat-'.implode(' cat-',$cat_slugs) : '';
            $on_sale   = $product->is_on_sale();
            
            $badge_text = '';
            $is_dynamic_badge = false;
            $badge_class = 'te-badge';

            if ( ! $is_in_stock ) {
                $badge_text = 'Sold Out';
                $badge_class .= ' sold-out';
            } else {
                if ( $badges_active ) {
                    $total_sales = (int) get_post_meta( $pid, 'total_sales', true );
                    $created_date = $product->get_date_created() ? $product->get_date_created()->getTimestamp() : 0;
                    $days_old = ( current_time('timestamp') - $created_date ) / DAY_IN_SECONDS;
                    
                    if ( $total_sales >= $badges_bestseller_sales ) {
                        $badge_text = 'Bestseller';
                        $is_dynamic_badge = true;
                    } elseif ( $created_date && $days_old <= $badges_new_days ) {
                        $badge_text = 'New Drop';
                        $is_dynamic_badge = true;
                    }
                }
                if ( ! $badge_text ) {
                    if ( $on_sale ) {
                        $badge_text = 'Sale';
                    } elseif ( $cat_label ) {
                        $badge_text = $cat_label;
                    }
                }
            }
            if ( $is_dynamic_badge ) {
                $badge_class .= ' dynamic-badge';
            }
          ?>
          <div class="te-card <?php echo esc_attr($cat_cls); ?>" data-te-item>
            <div class="te-card-img">
              <?php if ( $badge_text ): ?>
              <span class="<?php echo esc_attr($badge_class); ?>"><?php echo esc_html($badge_text); ?></span>
              <?php endif; ?>

              <a href="<?php echo esc_url($purl); ?>">
                <img src="<?php echo esc_url($img); ?>"
                     alt="<?php echo esc_attr($product->get_name()); ?>"
                     loading="lazy">
              </a>

              <div class="te-card-actions">
                <a href="<?php echo esc_url($buy_url); ?>" class="te-action-buy">
                  <?php echo esc_html($btn_txt); ?>
                </a>
                <a href="<?php echo esc_url($purl); ?>" class="te-action-view">View</a>
              </div>
            </div>

            <div class="te-card-info">
              <?php if ( $cat_label ): ?>
              <div class="te-card-cat"><?php echo esc_html($cat_label); ?></div>
              <?php endif; ?>
              <a href="<?php echo esc_url($purl); ?>" class="te-card-name">
                <?php echo esc_html($product->get_name()); ?>
              </a>
              <div class="te-card-footer">
                <div class="te-card-price"><?php echo wp_kses_post($product->get_price_html()); ?></div>
                <div class="te-card-footer-actions">
                  <?php if ( ! $is_in_stock ) : ?>
                  <button class="tryl-atc te-atc disabled" disabled style="opacity:0.5;cursor:not-allowed;">
                    <span>Out of Stock</span>
                  </button>
                  <?php elseif($is_var): 
                      $available_variations = $product->get_available_variations();
                  ?>
                  <div class="tryl-inline-var-wrapper" style="position:relative;">
                    <button class="tryl-atc te-atc te-atc-choose tryl-atc-inline-toggle" type="button">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                      <span>Size</span>
                    </button>
                    <div class="tryl-inline-var-dropdown" style="display:none; position:absolute; bottom:calc(100% + 8px); right:0; background:var(--te-card); border:1px solid var(--te-sand); box-shadow:0 8px 24px rgba(0,0,0,0.1); z-index:100; padding:8px; border-radius:8px; min-width:140px;">
                        <div style="font-size:0.6rem; color:var(--te-stone); text-transform:uppercase; letter-spacing:0.1em; margin-bottom:8px; padding-bottom:4px; border-bottom:1px solid var(--te-sand); text-align:center;">Select Option</div>
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
                            <button class="te-atc-variation tryl-atc-variation" data-pid="<?php echo $pid; ?>" data-vid="<?php echo $var['variation_id']; ?>" type="button" style="width:100%; text-align:left; padding:8px 12px; background:var(--te-cream); border:1px solid var(--te-sand); cursor:pointer; font-family:'Inter',sans-serif; font-size:0.7rem; font-weight:600; text-transform:uppercase; color:var(--te-ink); transition:all 0.2s; border-radius:6px;">
                                <?php echo esc_html( $label ); ?>
                            </button>
                        <?php endforeach; 
                        if ( ! $has_in_stock ) echo '<div style="font-size:0.65rem; color:var(--te-stone); text-align:center; padding:8px;">Sold Out</div>';
                        ?>
                        </div>
                    </div>
                  </div>
                  <?php else: ?>
                  <button class="tryl-atc te-atc" data-pid="<?php echo $pid; ?>">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span>Add</span>
                  </button>
                  <?php endif; ?>
                  <a href="<?php echo esc_url($purl); ?>" class="te-card-arrow" aria-label="View product">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="5" y1="12" x2="19" y2="12"/>
                      <polyline points="12 5 19 12 12 19"/>
                    </svg>
                  </a>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <?php
        $shop_url = get_option('tryl_nav_shop');
        if ( empty( $shop_url ) ) {
            $shop_url = 'https://therighteousyieldlife.com/the-shop-wip/';
        }
        ?>
        <!-- CTA -->
        <div class="te-cta">
          <a href="<?php echo esc_url( $shop_url ); ?>" class="te-cta-link">
            View All Items
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="5" y1="12" x2="19" y2="12"/>
              <polyline points="12 5 19 12 12 19"/>
            </svg>
          </a>
        </div>
        
        <div class="te-signature" style="text-align:center; padding-top:64px; font-size:.65rem; font-weight:600; letter-spacing:.15em; text-transform:uppercase; color:var(--te-stone); opacity: 0.8;">
          <?php echo wp_kses_post($signature); ?>
        </div>

      </div>
    </div>

    <script>
    (function(){
      gsap.registerPlugin(ScrollTrigger);

      // ── 1. CINEMATIC HEADER ENTRANCE
      // Eyebrow line draws in, then heading word-by-word, then subtext fades
      const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
      tl.from('.te-eyebrow',   { y: 18, opacity: 0, duration: .7 })
        .from('.te-heading',   { y: 36, opacity: 0, duration: .9, skewY: 1.5 }, '-=.3')
        .from('.te-subheading',{ y: 18, opacity: 0, duration: .7 }, '-=.4')
        .from('.te-filter',    { y: 10, opacity: 0, stagger: .06, duration: .5 }, '-=.3');

      // ── 2. SCROLLTRIGGER CARD REVEAL — staggered fade + lift
      gsap.from('.te-card', {
        scrollTrigger: {
          trigger: '#teGrid',
          start: 'top 85%',
          toggleActions: 'play none none none',
        },
        y: 48,
        opacity: 0,
        stagger: { amount: .8, from: 'start' },
        duration: .75,
        ease: 'power3.out',
        clearProps: 'transform,opacity',
      });

      // ── 3. CTA ENTRANCE
      gsap.from('.te-cta', {
        scrollTrigger: { trigger: '.te-cta', start: 'top 90%' },
        y: 24, opacity: 0, duration: .7, ease: 'power2.out',
      });

      // ── 4. HOVER PARALLAX on product images
      // On mouseenter/leave we GSAP-tween the img inside, not CSS transitions
      document.querySelectorAll('.te-card-img').forEach(wrap => {
        const img = wrap.querySelector('img');
        if (!img) return;

        wrap.addEventListener('mouseenter', () => {
          gsap.to(img, { scale: 1.09, duration: .55, ease: 'power2.out' });
        });
        wrap.addEventListener('mouseleave', () => {
          gsap.to(img, { scale: 1, duration: .55, ease: 'power2.inOut' });
        });

        // Subtle mouse-tracking parallax within the card
        wrap.addEventListener('mousemove', e => {
          const rect  = wrap.getBoundingClientRect();
          const cx    = rect.width  / 2;
          const cy    = rect.height / 2;
          const dx    = (e.clientX - rect.left - cx) / cx; // -1 to 1
          const dy    = (e.clientY - rect.top  - cy) / cy;
          gsap.to(img, {
            x: dx * 8,
            y: dy * 6,
            duration: .4,
            ease: 'power1.out',
            overwrite: 'auto',
          });
        });
        wrap.addEventListener('mouseleave', () => {
          gsap.to(img, { x: 0, y: 0, duration: .55, ease: 'power2.inOut', overwrite: 'auto' });
        });
      });

      // ── 5. QUICK-ACTION OVERLAY — GSAP instead of CSS transform
      document.querySelectorAll('.te-card').forEach(card => {
        const actions = card.querySelector('.te-card-actions');
        if (!actions) return;
        // Start hidden via GSAP (override CSS transform:translateY(100%))
        gsap.set(actions, { y: '100%', opacity: 0 });

        card.addEventListener('mouseenter', () => {
          gsap.to(actions, { y: '0%', opacity: 1, duration: .32, ease: 'power2.out' });
        });
        card.addEventListener('mouseleave', () => {
          gsap.to(actions, { y: '100%', opacity: 0, duration: .25, ease: 'power2.in' });
        });
      });

      // ── 6. FILTER CLICK — animate out, swap, animate in
      document.querySelectorAll('.te-filter').forEach(btn => {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.te-filter').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          const f = btn.dataset.filter;
          const allCards = [...document.querySelectorAll('[data-te-item]')];

          // Fade out visible cards that don't match
          const toHide = allCards.filter(c => !c.classList.contains(f) && f !== 'all' && c.style.display !== 'none');
          const toShow = allCards.filter(c =>  (f === 'all' || c.classList.contains(f)));

          gsap.to(toHide, {
            opacity: 0, y: 16, duration: .25, ease: 'power2.in',
            onComplete: () => {
              toHide.forEach(c => { c.style.display = 'none'; });
              toShow.forEach(c => {
                c.style.display = 'flex';
                c.style.flexDirection = 'column';
              });
              gsap.fromTo(toShow,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, stagger: .06, duration: .4, ease: 'power3.out', clearProps: 'transform,opacity' }
              );
            }
          });
          if (toHide.length === 0) {
            toShow.forEach(c => { c.style.display = 'flex'; c.style.flexDirection = 'column'; });
            gsap.fromTo(toShow,
              { opacity: 0, y: 20 },
              { opacity: 1, y: 0, stagger: .06, duration: .4, ease: 'power3.out', clearProps: 'transform,opacity' }
            );
          }
        });
      });

    })();
    </script>
    <?php
    return ob_get_clean();
}
}
add_shortcode( 'tryl_shop_editorial', 'tryl_editorial_shop_shortcode' );


// ─── SOFT CART & CHECKOUT OVERLAY (editorial palette) ────────────────────────
// Only activates when a query param ?skin=editorial is present,
// OR you can hook it permanently by removing the condition below.
if ( ! function_exists( 'tryl_editorial_cart_css' ) ) {
function tryl_editorial_cart_css() {
    if ( ! function_exists( 'is_cart' ) || ( ! is_cart() && ! is_checkout() ) ) return;
    // Remove the next line to make the editorial cart the permanent style
    if ( empty( $_GET['skin'] ) || $_GET['skin'] !== 'editorial' ) return;
    ?>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    :root, [data-theme="bright"] {
      --te-cream: #faf8f3;
      --te-warm: #f2ede3;
      --te-sand: #e8e0d0;
      --te-ink: #1c1a17;
      --te-stone: #6b6560;
      --te-sage: #4a7c59;
      --te-sage-l: #deeae2;
      --te-card: #ffffff;
    }
    [data-theme="mild"] {
      --te-cream: #e6e4df;
      --te-warm: #dcd9d1;
      --te-sand: #ccc8be;
      --te-ink: #33322e;
      --te-stone: #7a7671;
      --te-sage: #5c6e5c;
      --te-sage-l: #cdd9cd;
      --te-card: #f2f0eb;
    }
    [data-theme="dark"] {
      --te-cream: #1c1a17;
      --te-warm: #2a2723;
      --te-sand: #3f3b36;
      --te-ink: #faf8f3;
      --te-stone: #a39d96;
      --te-sage: #6b9c7a;
      --te-sage-l: #2c3b31;
      --te-card: #23211d;
    }
    :root {
      --radius:12px;
      /* Mini cart overrides for editorial skin */
      --mc-bg:var(--te-cream);--mc-text:var(--te-ink);--mc-accent:var(--te-sage);
      --mc-dark:var(--te-ink);--mc-muted:var(--te-stone);--mc-border:var(--te-sand);
      --mc-overlay:rgba(28,26,23,0.35);
      --mc-header-font:'Cormorant Garamond',serif;--mc-body-font:'Inter',sans-serif;
      --mc-radius:12px;--mc-btn-radius:40px;
      --mc-shadow:0 8px 48px rgba(28,26,23,0.1);
      --mc-btn-text:var(--te-cream);
    }
    body{background:var(--te-cream)!important;font-family:'Inter',sans-serif!important;color:var(--te-ink)!important;transition:background .3s,color .3s;}

    /* Headings */
    h1,h2,h3,.woocommerce h2,.woocommerce h3{
      font-family:'Cormorant Garamond',serif!important;
      font-weight:600!important;color:var(--te-ink)!important;
      border:none!important;letter-spacing:.01em!important;
    }

    /* Layout */
    .woocommerce{max-width:1140px!important;margin:48px auto!important;padding:0 28px!important;font-family:'Inter',sans-serif!important;}

    /* Table */
    .woocommerce table.shop_table{border:none!important;border-radius:0!important;border-collapse:collapse!important;}
    .woocommerce table.shop_table th{font-size:.62rem!important;font-weight:600!important;letter-spacing:.15em!important;text-transform:uppercase!important;color:var(--te-stone)!important;padding:0 0 14px!important;border-bottom:1px solid var(--te-sand)!important;background:transparent!important;}
    .woocommerce table.shop_table td{border:none!important;border-bottom:1px solid var(--te-sand)!important;padding:24px 10px!important;background:transparent!important;vertical-align:middle!important;}
    .woocommerce table.shop_table .product-thumbnail img{width:88px!important;height:88px!important;object-fit:contain!important;background:var(--te-warm)!important;border-radius:10px!important;padding:10px!important;border:none!important;}
    .woocommerce table.shop_table .product-name a{font-family:'Cormorant Garamond',serif!important;font-weight:600!important;font-size:1.15rem!important;color:var(--te-ink)!important;text-decoration:none!important;}
    .woocommerce table.shop_table .product-remove a{font-size:20px!important;color:var(--te-stone)!important;background:none!important;}
    .woocommerce table.shop_table .product-remove a:hover{color:var(--te-ink)!important;background:none!important;}
    .woocommerce .quantity input.qty{border:1px solid var(--te-sand)!important;border-radius:8px!important;height:42px!important;width:60px!important;text-align:center!important;background:var(--te-warm)!important;color:var(--te-ink)!important;font-family:'Inter',sans-serif!important;}

    /* Cart totals */
    .woocommerce .cart_totals{background:var(--te-warm)!important;border:none!important;border-radius:var(--radius)!important;padding:28px!important;}

    /* Buttons */
    .woocommerce button.button,
    .woocommerce a.checkout-button,
    .woocommerce #place_order,
    .woocommerce input.button{
      background:var(--te-ink)!important;color:var(--te-cream)!important;
      border:none!important;border-radius:40px!important;
      font-family:'Inter',sans-serif!important;font-weight:500!important;
      font-size:.72rem!important;letter-spacing:.12em!important;text-transform:uppercase!important;
      padding:15px 28px!important;cursor:pointer!important;
      transition:background .2s!important;
    }
    .woocommerce button.button:hover,
    .woocommerce a.checkout-button:hover,
    .woocommerce #place_order:hover{background:var(--te-sage)!important;}
    .woocommerce button.button[name="update_cart"]{
      background:transparent!important;color:var(--te-stone)!important;
      border:1px solid var(--te-sand)!important;border-radius:40px!important;
    }
    .woocommerce a.checkout-button{display:block!important;width:100%!important;text-align:center!important;margin-top:14px!important;}

    /* Checkout fields */
    .woocommerce-checkout .woocommerce-input-wrapper input,
    .woocommerce-checkout .woocommerce-input-wrapper select,
    .woocommerce-checkout .woocommerce-input-wrapper textarea{
      border:1px solid var(--te-sand)!important;border-radius:10px!important;
      padding:13px 16px!important;background:var(--te-warm)!important;color:var(--te-ink)!important;
      font-family:'Inter',sans-serif!important;
    }
    .woocommerce-checkout .woocommerce-input-wrapper input:focus{border-color:var(--te-sage)!important;outline:none!important;}
    .woocommerce form .form-row label{font-size:.68rem!important;font-weight:500!important;letter-spacing:.08em!important;text-transform:uppercase!important;color:var(--te-stone)!important;}

    /* Payment */
    #payment{background:var(--te-warm)!important;border:none!important;border-radius:var(--radius)!important;padding:24px!important;}
    #payment ul.payment_methods li{background:var(--te-card)!important;border:1px solid var(--te-sand)!important;border-radius:10px!important;padding:14px!important;margin-bottom:8px!important;list-style:none!important;}
    </style>
    <?php
}
}
add_action( 'wp_head', 'tryl_editorial_cart_css' );
