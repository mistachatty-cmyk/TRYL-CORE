<?php
/**
 * Custom TRYL Cart Template
 * Modern, card-based layout matching Nike aesthetic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wc_get_header( 'shop' );

do_action( 'woocommerce_before_cart' ); ?>

<div class="woocommerce">
    <div class="tryl-cart-wrapper">
        <div class="tryl-cart-container">
            <?php
            if ( wc_get_page_id( 'cart' ) > 0 && apply_filters( 'woocommerce_display_cart_notices', true ) ) :
                do_action( 'woocommerce_before_cart_notices' );
                wc_print_notices();
                do_action( 'woocommerce_after_cart_notices' );
            endif;
            ?>

            <form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
                <?php do_action( 'woocommerce_before_cart_table' ); ?>

                <div class="tryl-cart-items">
                    <?php
                    foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                        $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                        $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                        if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                            ?>
                            <div class="tryl-cart-item" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
                                <div class="tryl-cart-item-image">
                                    <?php
                                    $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
                                    if ( ! $product_id || ! $_product->is_visible() ) {
                                        echo $thumbnail;
                                    } else {
                                        printf( '<a href="%s">%s</a>', esc_url( $_product->get_permalink( $cart_item ) ), $thumbnail );
                                    }
                                    ?>
                                </div>
                                
                                <div class="tryl-cart-item-details">
                                    <div class="tryl-cart-item-title">
                                        <?php
                                        if ( ! $product_id || ! $_product->is_visible() ) {
                                            echo esc_html( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' );
                                        } else {
                                            echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $_product->get_permalink( $cart_item ) ), $_product->get_name() ), $cart_item, $cart_item_key ) );
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="tryl-cart-item-meta">
                                        <?php
                                        do_action( 'woocommerce_cart_item_meta_start', $cart_item_key, $cart_item, $_product );
                                        
                                        wc_display_item_meta( $cart_item );
                                        
                                        do_action( 'woocommerce_cart_item_meta_end', $cart_item_key, $cart_item, $_product );
                                        ?>
                                    </div>
                                    
                                    <div class="tryl-cart-item-actions">
                                        <div class="tryl-cart-item-quantity">
                                            <div class="quantity">
                                                <label class="screen-reader-text" for="cart_item_<?php echo esc_attr( $cart_item_key ); ?>"><?php esc_html_e( 'Quantity', 'woocommerce' ); ?></label>
                                                <input type="number" class="input-text qty text" title="Qty" value="<?php echo esc_attr( $cart_item['quantity'] ); ?>" min="0" step="<?php echo esc_attr( isset( $_product->backorders_allowed() ? '' : 'any' ) ? '' : '1' ); ?>" pattern="[0-9]*" inputmode="numeric" name="cart[<?php echo esc_attr( $cart_item_key ); ?>][qty]" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>" size="4" placeholder="" />
                                            </div>
                                            <button type="submit" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>" class="tryl-update-cart button"><?php esc_html_e( 'Update', 'woocommerce' ); ?></button>
                                        </div>
                                        
                                        <a href="<?php echo esc_url( WC()->cart->get_remove_url( $cart_item_key ) ); ?>" class="tryl-remove-item button" title="<?php esc_attr_e( 'Remove this item', 'woocommerce' ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">&times;</a>
                                    </div>
                                    
                                    <div class="tryl-cart-item-price">
                                        <?php
                                        echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>

                <?php do_action( 'woocommerce_after_cart_table' ); ?>
            </form>

            <div class="tryl-cart-actions">
                <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="tryl-continue-shopping button"><?php esc_html_e( 'Continue Shopping', 'woocommerce' ); ?></a>
                
                <?php
                if ( wc_coupons_enabled() ) { ?>
                    <div class="tryl-cart-coupon">
                        <label for="coupon_code"><?php esc_html_e( 'Coupon:', 'woocommerce' ); ?></label>
                        <input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'Coupon code', 'woocommerce' ); ?>" />
                        <button type="submit" class="button" name="apply_coupon" value="<?php esc_attr_e( 'Apply coupon', 'woocommerce' ); ?>"><?php esc_html_e( 'Apply coupon', 'woocommerce' ); ?></button>
                        <?php do_action( 'woocommerce_cart_coupon' ); ?>
                    </div>
                <?php } ?>
                
                <button type="submit" class="button checkout wc-forward" name="proceed" value="<?php esc_attr_e( 'Proceed to checkout', 'woocommerce' ); ?>"><?php esc_html_e( 'Proceed to checkout', 'woocommerce' ); ?></button>
            </div>
        </div>
    </div>
</div>

<?php
do_action( 'woocommerce_after_cart' );
wc_get_footer( 'shop' );
?>