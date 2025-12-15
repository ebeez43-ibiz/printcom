<?php
/**
 * Checkout shipping information form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="modal fade woocommerce" id="shippingModalModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?php esc_attr_e('Shipping address','woocommerce') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="wc-print-address" name="checkout" method="post" class="wc-print-form " action="#" style="padding: 40px" >
                <div class="woocommerce-shipping-fields">
                    <div class="shipping_address" id="print_shipping_address">
                        <div class="woocommerce-shipping-fields__field-wrapper">
                            <?php
                            $billing_fields = $checkout->get_checkout_fields( 'billing' );
                            foreach ( $billing_fields as $key => $field ) {
                                if(in_array($key,array('billing_email','billing_phone'))){
                                    woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
                                }
                            }
                            $fields = $checkout->get_checkout_fields( 'shipping' );
                            foreach ( $fields as $key => $field ) {
                                woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <p class="wc-setup-actions step">
                    <button class="button-primary button button-large" value="<?php esc_attr_e( 'Save', 'woocommerce' ); ?>" name="save_step">
                        <?php esc_html_e( 'Save', 'woocommerce' ); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>

