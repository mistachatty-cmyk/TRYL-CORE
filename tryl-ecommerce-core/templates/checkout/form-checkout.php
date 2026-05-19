<?php
/**
 * Custom TRYL Checkout Form
 * Two-column layout: billing/shipping left, order review + payment right.
 * Preserves all WooCommerce hooks for Stripe/PayPal/plugin compatibility.
 */

defined( 'ABSPATH' ) || exit;

$checkout = WC()->checkout();

if ( ! $checkout ) {
    return;
}

// Unhook payment from order review so we can place it in our Step 3 Accordion
remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
    return;
}

$checkout_layout = get_option('tryl_checkout_layout_style', 'accordion');
?>

<?php if ( $checkout_layout === 'snap' ) : ?>
<style>
/* ── STITCH UI CHECKOUT SKIN ── */
.snap-checkout-wrap { display: flex; flex-direction: column; min-height: 100vh; font-family: var(--tryl-body-font, sans-serif); background: #fff; margin: -48px -32px; }
@media(min-width: 900px) { .snap-checkout-wrap { flex-direction: row; } }
.snap-left { width: 100%; background: #fff; padding: 40px 20px; }
@media(min-width: 900px) { .snap-left { width: 55%; padding: 64px 8% 64px 10%; border-right: 1px solid var(--border, #eee); } }
.snap-right { width: 100%; background: #f0f3ff; padding: 40px 20px; }
[data-theme="dark"] .snap-right { background: #1a1a1a; }
@media(min-width: 900px) { .snap-right { width: 45%; padding: 64px 10% 64px 8%; } }

/* Typography */
.snap-h1 { font-family: var(--tryl-header-font, sans-serif); font-size: 2.5rem; font-weight: 900; text-transform: uppercase; color: var(--dark, #111); margin-bottom: 48px; letter-spacing: -0.02em; }
.snap-section-title { font-family: var(--tryl-header-font, sans-serif); font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; color: var(--dark, #111); margin-bottom: 16px; display: block; }

/* Form Styles */
.woocommerce-checkout form .form-row { margin-bottom: 16px; }
.woocommerce-checkout form .form-row label { font-size: 0.75rem; font-weight: 600; color: var(--muted, #666); text-transform: none; letter-spacing: normal; }
.woocommerce-checkout form .form-row label.checkbox { display: inline-flex; align-items: center; }
.woocommerce-checkout form .form-row input.input-text,
.woocommerce-checkout form .form-row select,
.woocommerce-checkout form .form-row textarea {
    width: 100%; border: 1px solid var(--border, #e5e5e5); padding: 14px; border-radius: 2px; font-size: 0.875rem; background: #fff; color: var(--dark, #111); transition: all 0.2s;
}
.woocommerce-checkout form .form-row input.input-text:focus,
.woocommerce-checkout form .form-row select:focus,
.woocommerce-checkout form .form-row textarea:focus { border-color: var(--accent, #31d190); box-shadow: 0 0 0 1px var(--accent, #31d190); outline: none; }

/* Payment Box */
#payment { background: transparent !important; padding: 0 !important; }
#payment ul.payment_methods { border-bottom: none !important; padding: 0 !important; }
#payment ul.payment_methods li { background: #fafafa !important; border: 1px solid var(--border, #eee) !important; border-radius: 4px !important; padding: 20px !important; margin-bottom: 12px !important; }
[data-theme="dark"] #payment ul.payment_methods li { background: #222 !important; border-color: #333 !important; }

#place_order {
    width: 100%; background: var(--dark, #111); color: #fff; padding: 20px; font-family: var(--tryl-header-font, sans-serif); font-weight: 900; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.2em; border: none; border-radius: 2px; margin-top: 24px; transition: background 0.3s;
}
#place_order:hover { background: var(--accent, #31d190); color: #fff; }

/* Right Side Order Summary */
#order_review_heading { display: none; }
.snap-summary-title { font-family: var(--tryl-header-font, sans-serif); font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; color: var(--dark, #111); padding-bottom: 16px; border-bottom: 1px solid rgba(0,0,0,0.1); margin-bottom: 32px; display: block; }
[data-theme="dark"] .snap-summary-title { border-color: rgba(255,255,255,0.1); color: #fff; }

.woocommerce-checkout-review-order-table { width: 100%; border: none !important; }
.woocommerce-checkout-review-order-table th, .woocommerce-checkout-review-order-table td { border: none !important; background: transparent !important; padding: 12px 0 !important; }
.woocommerce-checkout-review-order-table thead { display: none; }
.woocommerce-checkout-review-order-table tbody td.product-name { font-family: var(--tryl-header-font, sans-serif); font-weight: 900; font-size: 0.85rem; text-transform: uppercase; letter-spacing: -0.02em; display: flex; align-items: center; gap: 16px; color: var(--dark, #111); }
[data-theme="dark"] .woocommerce-checkout-review-order-table tbody td.product-name { color: #fff; }
.woocommerce-checkout-review-order-table tbody td.product-total { font-weight: 900; text-align: right; color: var(--dark, #111); }
[data-theme="dark"] .woocommerce-checkout-review-order-table tbody td.product-total { color: #fff; }
.woocommerce-checkout-review-order-table tfoot th { font-size: 0.8rem; font-weight: 700; color: var(--muted, #666); }
.woocommerce-checkout-review-order-table tfoot td { text-align: right; font-weight: 700; color: var(--dark, #111); }
[data-theme="dark"] .woocommerce-checkout-review-order-table tfoot td { color: #fff; }
.woocommerce-checkout-review-order-table tfoot tr.order-total th, .woocommerce-checkout-review-order-table tfoot tr.order-total td { padding-top: 24px !important; border-top: 1px solid rgba(0,0,0,0.1) !important; font-size: 1.5rem !important; font-weight: 900 !important; font-family: var(--tryl-header-font, sans-serif) !important; }
[data-theme="dark"] .woocommerce-checkout-review-order-table tfoot tr.order-total th, [data-theme="dark"] .woocommerce-checkout-review-order-table tfoot tr.order-total td { border-color: rgba(255,255,255,0.1) !important; }

.tryl-order-thumb { width: 60px; height: 75px; object-fit: cover; border: 1px solid rgba(0,0,0,0.1); border-radius: 2px; background: #fff; filter: grayscale(100%); transition: filter 0.5s; }
.woocommerce-checkout-review-order-table tbody tr:hover .tryl-order-thumb { filter: grayscale(0%); }
</style>
<?php endif; ?>

<form name="checkout" method="post" class="checkout woocommerce-checkout tryl-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

    <?php if ( $checkout_layout === 'snap' ) : ?>

        <!-- ================= SNAP UI CHECKOUT ================= -->
        <div class="snap-checkout-wrap">
            <!-- LEFT COLUMN: FORM -->
            <div class="snap-left">
                <h1 class="snap-h1">Checkout</h1>
                
                <div id="tryl-express-checkout-wrap" style="display: none; margin-bottom: 48px;">
                    <span class="snap-section-title">Express Checkout</span>
                    <div style="margin-top: 16px;">
                        <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
                    </div>
                    <div style="display: flex; align-items: center; margin: 32px 0;">
                        <div style="flex-grow: 1; border-top: 1px solid var(--border, #eee);"></div>
                        <span style="padding: 0 16px; font-size: 0.65rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.15em; color: var(--muted, #666);">Or continue below</span>
                        <div style="flex-grow: 1; border-top: 1px solid var(--border, #eee);"></div>
                    </div>
                </div>

                <div class="snap-sections">
                    <div style="margin-bottom: 48px;">
                        <span class="snap-section-title">1. Contact & Shipping</span>
                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                    </div>
                    <div>
                        <span class="snap-section-title">2. Payment</span>
                        <p style="font-size: 0.75rem; color: var(--muted, #666); margin-bottom: 16px;">Secure Encrypted Checkout</p>
                        <?php woocommerce_checkout_payment(); ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: ORDER SUMMARY -->
            <div class="snap-right">
                <span class="snap-summary-title">Order Summary</span>
                
                <div id="order_review" class="woocommerce-checkout-review-order">
                    <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                </div>

                <!-- Addons from TRYL (Eco packaging, etc) -->
                <?php if ( get_option( 'tryl_checkout_features_active' ) === '1' ) : ?>
                <div style="margin-top: 32px; padding-top: 32px; border-top: 1px solid rgba(0,0,0,0.1);">
                    <div class="feature-item" style="display: flex; justify-content: space-between; margin-bottom: 16px;">
                        <span class="feature-label" style="font-size: 0.85rem; font-weight: 700;">Eco-Friendly Packaging</span>
                        <label class="nike-switch" for="tryl_eco_packaging">
                            <input type="checkbox" id="tryl_eco_packaging" name="tryl_eco_packaging" value="1">
                            <span class="nike-switch-inner"></span>
                        </label>
                    </div>
                    <div class="feature-item" style="display: flex; justify-content: space-between;">
                        <span class="feature-label" style="font-size: 0.85rem; font-weight: 700;">Gift Message & Wrapping</span>
                        <label class="nike-switch" for="tryl_gift_wrapping">
                            <input type="checkbox" id="tryl_gift_wrapping" name="tryl_gift_wrapping" value="1" class="tryl-update-checkout">
                            <span class="nike-switch-inner"></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( get_option( 'tryl_order_bump_active' ) === '1' ) : ?>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(0,0,0,0.1);">
                    <div class="feature-item" style="display: flex; justify-content: space-between;">
                        <div>
                            <span class="feature-label" id="label-bump" style="color: var(--ry-accent, #31d190); font-weight: 800; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Limited Time Offer</span>
                            <span style="font-size: 0.85rem; color: var(--txt); font-weight: 500;"><?php echo esc_html( get_option( 'tryl_order_bump_label', 'Add a Premium Sticker Pack for $4.00' ) ); ?></span>
                        </div>
                        <label class="nike-switch" for="tryl_order_bump">
                            <input type="checkbox" id="tryl_order_bump" role="switch" aria-labelledby="label-bump" name="tryl_order_bump" value="1" class="tryl-update-checkout">
                            <span class="nike-switch-inner" aria-hidden="true" style="background-color: var(--border);"></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ( get_option('tryl_checkout_newsletter_optin', '0') === '1' && get_option('tryl_newsletter_provider', 'none') !== 'none' ) : ?>
                <div style="margin-top: 16px; padding-top: 16px;">
                    <div class="feature-item" style="display: flex; justify-content: space-between;">
                        <span class="feature-label" style="font-size: 0.85rem; font-weight: 700;"><?php echo esc_html(get_option('tryl_checkout_optin_label', 'Keep me updated on news and exclusive offers')); ?></span>
                        <label class="nike-switch" for="tryl_newsletter_optin">
                            <input type="checkbox" id="tryl_newsletter_optin" name="tryl_newsletter_optin" value="1" checked>
                            <span class="nike-switch-inner"></span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else : ?>

        <!-- ================= CLASSIC ACCORDION CHECKOUT ================= -->
        <div class="tryl-checkout-grid">
            <div class="tryl-checkout-main">
                <div class="tryl-express-wrapper" id="tryl-express-checkout-wrap" style="display: none;">
                    <h3 class="tryl-express-title">Express Checkout</h3>
                    <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
                    <div class="tryl-express-divider"><span>Or continue below</span></div>
                </div>
                
                <div class="tryl-checkout-accordion">
                    <div class="tryl-step active" id="step-billing">
                        <div class="tryl-step-header" onclick="trylToggleStep('step-billing')">
                            <div class="tryl-step-title"><span class="tryl-step-num">1</span><h3>Contact & Billing</h3></div>
                            <span class="dashicons dashicons-arrow-down-alt2 tryl-step-icon"></span>
                        </div>
                        <div class="tryl-step-content" style="display: block;">
                            <?php do_action( 'woocommerce_checkout_billing' ); ?>
                            <button type="button" class="tryl-next-step button" onclick="trylToggleStep('step-shipping')">Continue to Shipping</button>
                        </div>
                    </div>
                    <div class="tryl-step" id="step-shipping">
                        <div class="tryl-step-header" onclick="trylToggleStep('step-shipping')">
                            <div class="tryl-step-title"><span class="tryl-step-num">2</span><h3>Shipping Details</h3></div>
                            <span class="dashicons dashicons-arrow-down-alt2 tryl-step-icon"></span>
                        </div>
                        <div class="tryl-step-content">
                            <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                            <button type="button" class="tryl-next-step button" onclick="trylToggleStep('step-payment')">Continue to Payment</button>
                        </div>
                    </div>
                    <div class="tryl-step" id="step-payment">
                        <div class="tryl-step-header" onclick="trylToggleStep('step-payment')">
                            <div class="tryl-step-title"><span class="tryl-step-num">3</span><h3>Payment & Place Order</h3></div>
                            <span class="dashicons dashicons-arrow-down-alt2 tryl-step-icon"></span>
                        </div>
                        <div class="tryl-step-content">
                            <?php woocommerce_checkout_payment(); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tryl-checkout-sidebar">
                <div class="tryl-order-summary-box">
                    <h3 id="order_review_heading">Order Summary</h3>
                    <div id="order_review" class="woocommerce-checkout-review-order">
                        <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                    </div>
                    
                    <div class="tryl-sidebar-addons">
                        <?php if ( get_option( 'tryl_checkout_features_active' ) === '1' ) : ?>
                        <div class="feature-item">
                            <span class="feature-label" id="label-eco">Eco-Friendly Packaging</span>
                            <label class="nike-switch" for="tryl_eco_packaging">
                                <input type="checkbox" id="tryl_eco_packaging" role="switch" aria-labelledby="label-eco" name="tryl_eco_packaging" value="1">
                                <span class="nike-switch-inner" aria-hidden="true"></span>
                            </label>
                        </div>
                        <div class="feature-item">
                            <span class="feature-label" id="label-gift">Gift Message & Wrapping</span>
                            <label class="nike-switch" for="tryl_gift_wrapping">
                                <input type="checkbox" id="tryl_gift_wrapping" role="switch" aria-labelledby="label-gift" name="tryl_gift_wrapping" value="1" class="tryl-update-checkout">
                                <span class="nike-switch-inner" aria-hidden="true"></span>
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ( get_option( 'tryl_order_bump_active' ) === '1' ) : ?>
                        <div class="feature-item" style="border: none; padding-top: 24px;">
                            <div>
                                <span class="feature-label" id="label-bump" style="color: var(--ry-accent, #31d190); font-weight: 800; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Limited Time Offer</span>
                                <span style="font-size: 0.85rem; color: var(--txt); font-weight: 500;"><?php echo esc_html( get_option( 'tryl_order_bump_label', 'Add a Premium Sticker Pack for $4.00' ) ); ?></span>
                            </div>
                            <label class="nike-switch" for="tryl_order_bump">
                                <input type="checkbox" id="tryl_order_bump" role="switch" aria-labelledby="label-bump" name="tryl_order_bump" value="1" class="tryl-update-checkout">
                                <span class="nike-switch-inner" aria-hidden="true" style="background-color: var(--border);"></span>
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ( get_option('tryl_checkout_newsletter_optin', '0') === '1' && get_option('tryl_newsletter_provider', 'none') !== 'none' ) : ?>
                    <div class="tryl-sidebar-addons" style="margin-top: 16px; padding-top: 16px;">
                        <div class="feature-item" style="border: none; padding: 0;">
                            <span class="feature-label" id="label-newsletter" style="color: var(--txt); font-weight: 600;"><?php echo esc_html(get_option('tryl_checkout_optin_label', 'Keep me updated on news and exclusive offers')); ?></span>
                            <label class="nike-switch" for="tryl_newsletter_optin">
                                <input type="checkbox" id="tryl_newsletter_optin" role="switch" aria-labelledby="label-newsletter" name="tryl_newsletter_optin" value="1" checked>
                                <span class="nike-switch-inner" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const stripeWrapper = document.getElementById('wc-stripe-payment-request-wrapper');
    const expressWrap = document.getElementById('tryl-express-checkout-wrap');
    
    if (stripeWrapper && expressWrap) {
        const checkStripe = () => {
            if (stripeWrapper.style.display !== 'none' && stripeWrapper.innerHTML.trim() !== '') {
                expressWrap.style.display = 'block';
            } else {
                expressWrap.style.display = 'none';
            }
        };
        const observer = new MutationObserver(checkStripe);
        observer.observe(stripeWrapper, { attributes: true, childList: true, subtree: true });
        setTimeout(checkStripe, 500);
        setTimeout(checkStripe, 1500);
    }
});

// Ensure toggle checkout update triggers WooCommerce recalculation
if (!window.trylCheckoutSwitchesBound) {
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('tryl-update-checkout') && typeof jQuery !== 'undefined') {
            jQuery(document.body).trigger('update_checkout');
        }
    });
    window.trylCheckoutSwitchesBound = true;
}

<?php if ( $checkout_layout === 'accordion' ) : ?>
function trylToggleStep(stepId) {
    // Close all steps
    document.querySelectorAll('.tryl-step').forEach(step => {
        step.classList.remove('active');
        if (typeof gsap !== 'undefined') {
            gsap.to(step.querySelector('.tryl-step-content'), { height: 0, opacity: 0, duration: 0.3, onComplete: () => {
                step.querySelector('.tryl-step-content').style.display = 'none';
            }});
        } else {
            step.querySelector('.tryl-step-content').style.display = 'none';
        }
    });

    // Open target step
    const target = document.getElementById(stepId);
    if (target) {
        target.classList.add('active');
        const content = target.querySelector('.tryl-step-content');
        content.style.display = 'block';
        if (typeof gsap !== 'undefined') {
            gsap.fromTo(content, { height: 0, opacity: 0 }, { height: 'auto', opacity: 1, duration: 0.4 });
        }
    }
}

// Auto-expand all if WooCommerce throws a validation error so user sees the red text
if (typeof jQuery !== 'undefined') {
    jQuery(document).on('checkout_error', function() {
        document.querySelectorAll('.tryl-step').forEach(step => {
            step.classList.add('active');
            const content = step.querySelector('.tryl-step-content');
            content.style.display = 'block';
            content.style.height = 'auto';
            content.style.opacity = '1';
        });
    });
}
<?php endif; ?>
</script>
