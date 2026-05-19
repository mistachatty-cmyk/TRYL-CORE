<?php
/**
 * Custom TRYL Order Received (Thank You) Page
 */

defined( 'ABSPATH' ) || exit;
?>

<style>
.tryl-thankyou-wrapper {
    max-width: 800px;
    margin: 60px auto;
    padding: 0 24px;
    font-family: var(--tryl-body-font, 'Inter', sans-serif);
    text-align: center;
}
.tryl-ty-icon {
    width: 80px;
    height: 80px;
    background: var(--ry-accent, #31d190);
    color: var(--ry-bg, #0d1b0f);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 32px;
    box-shadow: 0 10px 30px rgba(49, 209, 144, 0.3);
}
.tryl-ty-icon svg { width: 40px; height: 40px; }
.tryl-ty-title {
    font-family: var(--tryl-header-font, 'Barlow Condensed', sans-serif);
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    text-transform: uppercase;
    color: var(--ry-text, #0d1b0f);
    margin: 0 0 16px;
    line-height: 1.1;
}
.tryl-ty-msg {
    font-size: 1.1rem;
    color: var(--ry-muted, #6b7c6b);
    margin-bottom: 48px;
    line-height: 1.6;
}
.tryl-ty-box {
    background: var(--ry-card, #fff);
    border: 1px solid var(--ry-border, #d4e0d4);
    border-radius: 12px;
    padding: 40px;
    text-align: left;
    margin-bottom: 40px;
}
.tryl-ty-box h2 {
    font-family: var(--tryl-header-font, sans-serif);
    font-size: 1.5rem;
    text-transform: uppercase;
    margin-top: 0;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--ry-bg, #f5f8f5);
}
.tryl-ty-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}
.tryl-ty-detail-item strong {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--ry-muted, #6b7c6b);
    margin-bottom: 8px;
}
.tryl-ty-detail-item span {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--ry-text, #0d1b0f);
}
.tryl-ty-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.tryl-ty-btn {
    padding: 16px 32px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;
    font-size: 0.85rem; border-radius: 4px; text-decoration: none; transition: all 0.3s;
}
.tryl-ty-btn-primary { background: var(--ry-text, #0d1b0f); color: var(--ry-bg, #fff); }
.tryl-ty-btn-primary:hover { background: var(--ry-accent, #31d190); color: var(--ry-bg, #0d1b0f); transform: translateY(-2px); }
.gsap-ty-anim { opacity: 0; transform: translateY(20px); }
</style>

<div class="tryl-thankyou-wrapper">
    <?php if ( $order ) : ?>
        <div class="tryl-ty-icon gsap-ty-anim">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
        </div>
        <h1 class="tryl-ty-title gsap-ty-anim">Order Confirmed</h1>
        <p class="tryl-ty-msg gsap-ty-anim">Thank you, <?php echo esc_html( $order->get_billing_first_name() ); ?>. Your order has been received and is being processed by our team.</p>
        
        <div class="tryl-ty-box gsap-ty-anim">
            <h2>Order Details</h2>
            <div class="tryl-ty-details">
                <div class="tryl-ty-detail-item"><strong>Order Number</strong><span>#<?php echo esc_html( $order->get_order_number() ); ?></span></div>
                <div class="tryl-ty-detail-item"><strong>Date</strong><span><?php echo wc_format_datetime( $order->get_date_created() ); ?></span></div>
                <div class="tryl-ty-detail-item"><strong>Email</strong><span><?php echo esc_html( $order->get_billing_email() ); ?></span></div>
                <div class="tryl-ty-detail-item"><strong>Total</strong><span><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></span></div>
            </div>
            <?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
            <?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>
        </div>
    <?php else : ?>
        <h1 class="tryl-ty-title gsap-ty-anim">Order Received</h1>
        <p class="tryl-ty-msg gsap-ty-anim">Thank you. Your order has been received.</p>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (typeof gsap !== "undefined") {
        gsap.to(".gsap-ty-anim", { opacity: 1, y: 0, duration: 0.8, stagger: 0.15, ease: "power3.out", delay: 0.2 });
    }
});
</script>