<?php
/**
 * Simple product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/simple.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;


if ( ! $product->is_purchasable() ) {
	return;
}
$product_id = $product->get_id();

echo wc_get_stock_html( $product ); // WPCS: XSS ok.


if ( $product->is_in_stock() ) :
    $class_submit="single_add_to_cart_button";
    $class_form="";
     $id ='';
    if($is_print)    :
        $id = ' id= "wc-print-add-to-cart-form" ';
        $class_submit="wc_print_add_to_cart_button";
        $class_form="print_form_cart";

    endif?>
	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form <?php echo $id ?> data-product_id="<?php echo $product_id ?>" class="cart <?php echo $class_form?>" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
        <?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );
        $checkout = WC()->checkout(); ?>
        <div id="product-option">
            <?php      wc_get_template( 'print/product/options.php', array('product'=>$product) );
            //wc_get_template( 'print/shipping/address.php', array( 'checkout' => $checkout ) );
            ?>
        </div>

        <div id="product-delivery-dates">
            <?php echo   wc_print_delivery_date(array(),array()); ?>
        </div>
        <div id="product-prices">
            <?php
            $prices_details = get_post_meta($product_id,'_print_product_prices',true);
            if(!empty($prices_details) && !empty($prices_details['prices'])){
                $templateData= array( 'prices' =>$prices_details['prices'],'selected'=>0);
                echo wc_print_render_template('print/delivery-promise.php',$templateData);
            }
            ?>

        </div>
		<?php woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // WPCS: CSRF ok, input var ok.
			)
		);
		do_action( 'woocommerce_after_add_to_cart_quantity' );
		?>
        <div id="wc-print-calculate-price">

        </div>
        <input type="hidden" id="wc_print_product_id" name="_wc_print_product_id" value="<?php echo esc_attr( $product_id ); ?>" />
		<button type="submit"  name="add-to-cart" value="<?php echo esc_attr( $product_id); ?>" class=" <?php echo $class_submit?> single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

        <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>



    <?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
