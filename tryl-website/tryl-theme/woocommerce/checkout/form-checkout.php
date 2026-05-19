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
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout tryl-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

    <div class="tryl-checkout-grid">

        <div class="tryl-checkout-main">
            
            <!-- EXPRESS CHECKOUT (APPLE PAY / GOOGLE PAY) -->
            <div class="tryl-express-wrapper">
                <h3 class="tryl-express-title">Express Checkout</h3>
                <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
                <div class="tryl-express-divider"><span>Or continue below</span></div>
            </div>

            <div class="tryl-checkout-accordion">
                
                <!-- STEP 1: BILLING -->
                <div class="tryl-step active" id="step-billing">
                    <div class="tryl-step-header" onclick="trylToggleStep('step-billing')">
                        <div class="tryl-step-title">
                            <span class="tryl-step-num">1</span>
                            <h3>Contact & Billing</h3>
                        </div>
                        <span class="dashicons dashicons-arrow-down-alt2 tryl-step-icon"></span>
                    </div>
                    <div class="tryl-step-content" style="display: block;">
                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                        <button type="button" class="tryl-next-step button" onclick="trylToggleStep('step-shipping')">Continue to Shipping</button>
                    </div>
                </div>

                <!-- STEP 2: SHIPPING -->
                <div class="tryl-step" id="step-shipping">
                    <div class="tryl-step-header" onclick="trylToggleStep('step-shipping')">
                        <div class="tryl-step-title">
                            <span class="tryl-step-num">2</span>
                            <h3>Shipping Details</h3>
                        </div>
                        <span class="dashicons dashicons-arrow-down-alt2 tryl-step-icon"></span>
                    </div>
                    <div class="tryl-step-content">
                        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                        <button type="button" class="tryl-next-step button" onclick="trylToggleStep('step-payment')">Continue to Payment</button>
                    </div>
                </div>

                <!-- STEP 3: PAYMENT -->
                <div class="tryl-step" id="step-payment">
                    <div class="tryl-step-header" onclick="trylToggleStep('step-payment')">
                        <div class="tryl-step-title">
                            <span class="tryl-step-num">3</span>
                            <h3>Payment & Place Order</h3>
                        </div>
                        <span class="dashicons dashicons-arrow-down-alt2 tryl-step-icon"></span>
                    </div>
                    <div class="tryl-step-content">
                        <?php 
                        // Output Payment Gateway Section
                        woocommerce_checkout_payment(); 
                        ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="tryl-checkout-sidebar">
            <div class="tryl-order-summary-box">
                <h3 id="order_review_heading">Order Summary</h3>
                
                <!-- This div is what WooCommerce targets with AJAX updates -->
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
                        <span class="feature-label" id="label-gift">Gift Message &amp; Wrapping</span>
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

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>

<script>
// Ensure toggle checkout update triggers WooCommerce recalculation
if (!window.trylCheckoutSwitchesBound) {
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('tryl-update-checkout') && typeof jQuery !== 'undefined') {
            jQuery(document.body).trigger('update_checkout');
        }
    });
    window.trylCheckoutSwitchesBound = true;
}

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
</script>
