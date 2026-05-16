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

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
    echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
    return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout tryl-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

    <div class="tryl-checkout-grid">

        <div class="tryl-checkout-main">
            
            <?php if ( get_option( 'tryl_checkout_features_active' ) === '1' ) : ?>
            <section class="feature-dashboard">
                <div class="dashboard-header">Extra Features</div>
                <p class="dashboard-desc">Manage optional extras and view upcoming features.</p>
                
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

                <div class="feature-item">
                    <span class="feature-label" id="label-dropoff">Drop-off Pickup Points</span>
                    <div style="display: flex; align-items: center;">
                        <label class="nike-switch disabled" for="tryl_dropoff">
                            <input type="checkbox" id="tryl_dropoff" role="switch" aria-labelledby="label-dropoff" disabled aria-disabled="true">
                            <span class="nike-switch-inner" aria-hidden="true"></span>
                        </label>
                        <span class="badge-soon">Coming Soon</span>
                    </div>
                </div>
            </section>
            <script>
            if (!window.trylCheckoutSwitchesBound) {
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('tryl-update-checkout') && typeof jQuery !== 'undefined') {
                        jQuery(document.body).trigger('update_checkout');
                    }
                });
                window.trylCheckoutSwitchesBound = true;
            }
            </script>
            <?php endif; ?>

            <?php if ( get_option( 'tryl_order_bump_active' ) === '1' ) : ?>
            <section class="feature-dashboard order-bump" style="border-color: var(--ry-accent, #31d190); background: rgba(49, 209, 144, 0.05); margin-bottom: 32px;">
                <div class="feature-item" style="border: none; padding: 0;">
                    <div>
                        <span class="feature-label" id="label-bump" style="color: var(--ry-accent, #31d190); font-weight: 800; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.05em;">Limited Time Offer</span>
                        <span style="font-size: 0.85rem; color: var(--txt); font-weight: 500;"><?php echo esc_html( get_option( 'tryl_order_bump_label', 'Add a Premium Sticker Pack for $4.00' ) ); ?></span>
                    </div>
                    <label class="nike-switch" for="tryl_order_bump">
                        <input type="checkbox" id="tryl_order_bump" role="switch" aria-labelledby="label-bump" name="tryl_order_bump" value="1" class="tryl-update-checkout">
                        <span class="nike-switch-inner" aria-hidden="true" style="background-color: var(--border);"></span>
                    </label>
                </div>
            </section>
            <?php endif; ?>

            <?php if ( $checkout->get_checkout_fields() ) : ?>
                <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

                <div class="tryl-checkout-fields" id="customer_details">
                    <?php do_action( 'woocommerce_checkout_billing' ); ?>
                    <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                </div>

                <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
            <?php endif; ?>
        </div>

        <div class="tryl-checkout-sidebar">
            <?php do_action( 'woocommerce_checkout_order_review' ); ?>
        </div>

    </div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
