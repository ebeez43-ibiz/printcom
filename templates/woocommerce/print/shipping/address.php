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
<div class="woocommerce-shipping-fields">
    <div class="shipping_address" id="print_shipping_address">
        <?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>
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
        <?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
    </div>

</div>

