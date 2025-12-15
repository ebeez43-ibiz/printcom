<?php
/**
 * Class WC_Print_Order
 */
class WC_Print_Order {
    private static $instance;

    /**
     * @return WC_Print_Order
     */
    public static function init() {
        if ( ! self::$instance instanceof self ) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * WC_Print_Order constructor.
     */
    public function __construct() {
    }
    public function init_hooks() {
        add_action( 'woocommerce_checkout_create_order_line_item',  array( $this, 'add_meta_to_order_items' ), 10, 4 );
        add_filter( 'woocommerce_order_item_get_formatted_meta_data', array(
            $this,
            'order_item_get_formatted_meta_data'
        ), 10, 2 );
         add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'set_print_order' ), 10, 2 );
     }


     /**
     * @param $order_id
     * @param $items
     * @return void
     */
    public function set_print_order( $order_id ,$items) {

        if( empty( $order_id ) ) return;
        $order = wc_get_order( $order_id );
        if( ! empty( $order ) ) {
            $items                   = $order->get_items();
            foreach ( $items as $item ) {
                 if(WC_Print::is_print($item['product_id'])){
                     update_post_meta( $order_id, '_is_print_order', 'YES' );

                }
            // for non-multilingual setups we use the default site language (PDF language might be overridden by Pro tab setting)
            }
        }
    }
    /**
     * @param $item
     * @param $cart_item_key
     * @param $values
     * @param $order
     */
    public function add_meta_to_order_items( $item, $cart_item_key, $values, $order ) {
        $product                    = $values['data'];
        if(WC_Print::is_print($product->get_id())){

            if(!empty($values['wc_print_product_price'])){
                $item->add_meta_data( 'wc_print_product_price', $values['wc_print_product_price'] );
            }
            if(!empty($values['wc_print_sales_price'])){
                $item->add_meta_data( 'wc_print_sales_price', $values['wc_print_sales_price'] );
            }
            if(!empty($values['wc_print_total_price'])){
                $item->add_meta_data( 'wc_print_total_price', $values['wc_print_total_price'] );
            }
            $item->add_meta_data( 'wc_print_delivery_promise', $values['wc_print_delivery_promise'] );

            $attributes = get_post_meta($product->get_id(),'_print_product_attributes',true);
            foreach ( $attributes as $slug => $attribute ){
                if(!empty($values[$slug])){
                    //$attribute_name = $attribute['name'];
                    //$option_slug    = $values[$slug];
                    //$option_value= WC_Print::get_option_name($attributes,$slug,$option_slug);
                    $item->add_meta_data( $slug, wc_clean( $values[$slug]));
                }
            }
            $delivery_fields=WC_Print_Cart::delivery_fields() ;
            foreach ($delivery_fields as $field){
                $item->add_meta_data( 'wc_print_delivery_'.$field, $values['wc_print_delivery_'.$field] );
            }
        }
    }

    /**
     * @param $formatted_meta
     * @param $instance
     *
     * @return mixed
     */
    public function order_item_get_formatted_meta_data( $formatted_meta, $instance ) {

        $buffer_key = array();
        foreach ( $formatted_meta as $meta_id => $meta ) {
            $filter_formatted_meta[ $meta_id ] = $meta;
            $igniored=array('wc_print_delivery_submission','wc_print_delivery_latest_dates','wc_print_delivery_method','wc_print_delivery_carrier','wc_print_delivery_pickup_date');

            if(in_array($meta->key,$igniored)){
                unset($formatted_meta[$meta_id]);
            }
            if ( $meta->key == 'wc_print_sales_price' ) {
                $filter_formatted_meta[ $meta_id ]->display_key = __( 'Sales price', 'wc-print');
            }
            if ( $meta->key == 'wc_print_product_price' ) {
                $filter_formatted_meta[ $meta_id ]->display_key = __( 'Product price', 'wc-print');
            }
            if ( $meta->key == 'wc_print_delivery_promise' ) {
                $filter_formatted_meta[ $meta_id ]->display_key = __( 'Delivery promise', 'wc-print');
            }
            if ( $meta->key == 'wc_print_total_price' ) {
                $filter_formatted_meta[ $meta_id ]->display_key = __( 'Total price', 'wc-print');
                $filter_formatted_meta[ $meta_id ]->display_value = wc_price(  $filter_formatted_meta[ $meta_id ]->value );
            }
            if ( $meta->key == 'wc_print_delivery_delivery_date' ) {
                $filter_formatted_meta[ $meta_id ]->display_key = __( 'Delivery Date', 'wc-print');
            }
            if ( $meta->key == 'wc_print_delivery_cost' ) {
                $filter_formatted_meta[ $meta_id ]->display_key = __( 'delivery price', 'wc-print');
                $filter_formatted_meta[ $meta_id ]->display_value = wc_price(  $filter_formatted_meta[ $meta_id ]->value );
            }
            $filter_formatted_meta[ $meta_id ]->display_key = ucfirst (str_replace( '_',' ' ,$filter_formatted_meta[ $meta_id ]->display_key ));

            $buffer_key[] = $meta->key;
        }

        return $formatted_meta;
    }
}

add_action('init', array(WC_Print_Order::init(), 'init_hooks'));
