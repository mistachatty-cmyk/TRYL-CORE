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
