<?php

/**
 * Class WC_Print_Checkout
 */
class WC_Print_Checkout
{
    /**
     * @var
     */
    private static $instance;

    /**
     * WC_Print_Checkout constructor.
     */
    public function __construct()
    {
        add_action('wp_ajax_woocommerce_update_order_review', array($this, 'maybe_process_ajax_update_order_review'), 1);
        add_action('wp_ajax_nopriv_woocommerce_update_order_review', array($this, 'maybe_process_ajax_update_order_review'), 1);
        add_action('wc_ajax_update_order_review', array($this, 'maybe_process_ajax_update_order_review'), 1);
        add_action('woocommerce_after_checkout_validation', array($this, 'validate_checkout_process'), 10, 2);
        add_action('woocommerce_review_order_after_cart_contents', array($this, 'display_shipping_content'), 10);

        add_action( 'wp_ajax_wc_print_display_checkout_shipping',                 array($this, 'display_checkout_shipping') );
        add_action( 'wp_ajax_nopriv_wc_print_display_checkout_shipping',           array($this, 'display_checkout_shipping' ) );

    }

    /**
     * @return WC_Print_Checkout
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * @return void
     */
    public function display_checkout_shipping(){

        check_ajax_referer('wc_print_ajax_request', 'nonce');
        $response = array(
            'success' => false,
            'html' => false,
        );
        $print_product_id= wc_print_product_from_cart();
        if(! $print_product_id){
            wp_send_json($response);wp_die();
        }
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            if ($print_product_id != $product_id){
                continue;
            }
            $product_sku =$product->get_sku();
            $shipping_address_1         =  WC()->customer->get_shipping_address_1();
            $shipping_address_2         =  WC()->customer->get_shipping_address_2();
            $address['lastName']        =  WC()->customer->get_shipping_first_name();
            $address['firstName']       =  WC()->customer->get_shipping_last_name();
            $address['country']         =  WC()->countries->get_base_country();
            $address['city']            =  WC()->customer->get_shipping_city();;
            $address['companyName']     =  WC()->customer->get_shipping_company();
            $address['postcode']        =  WC()->customer->get_shipping_postcode();
            $address['telephone']       =  WC()->customer->get_billing_phone();
            $address['fullstreet']      =  $shipping_address_1.' '.$shipping_address_2;
            $address['email']           =   WC()->customer->get_billing_email();
            $attributes                 =   get_post_meta($product_id,'_print_product_attributes',true);
            $options                    =   array();

            foreach ( $attributes as $slug => $attribute ){
                if(!empty($cart_item[$slug])){
                    $options[$slug]=$cart_item[$slug];
                }
            }
            if(!empty($options['size']) && $options['size']=='custom' ){
                if(!empty($cart_item['height'])){
                    $options['height']= intval($cart_item['height']) * 10;
                }
                if(!empty($cart_item['width'])){
                    $options['width']=intval ($cart_item['width']) *10;
                }
            }
            $options['copies']=!empty( $cart_item['copies']) ?  intval($cart_item['copies']) : 1;
            $item['options']    =   $options;

            $item['sku']            =  $product_sku;
            $variants[0]["copies"]= !empty( $cart_item['copies']) ?  intval($cart_item['copies'])  : 1;
            $item['variants']   =   $variants;
            $item['maxDesigns']= 1;
            $item['deliveryPromise']= intval($cart_item['wc_print_delivery_promise']);
            $shipments= !empty( $cart_item['copies']) ?  intval($cart_item['copies'])  : 1;
            $item['shipments'][0]["copies"] =$shipments;

            $body['item']       =   $item;
            $body['address']    =   $address;
            $body['dateFrom']   =   date('Y-m-d');
            $body['dateTo'] = strtotime("+1 months",strtotime($body['dateFrom']));
            $body['numberOfDays']=10;
            $body['ensureRates']=false;
            $body['respectUrgency']=false;

            $api= new WC_Print_Api();
            $shipping_possibilities= $api->call($body,'products/'.$product_sku.'/shipping-possibilities',true,'POST');
            if (!is_wp_error($shipping_possibilities)){
                $possibilities= json_decode($shipping_possibilities['body'],true);
                $selected_delivery['delivery_date']         =   $cart_item['wc_print_delivery_delivery_date'];
                $selected_delivery['cost']                  =   $cart_item['wc_print_delivery_cost'];
                $selected_delivery['latest_dates']          =   $cart_item['wc_print_delivery_latest_dates'];
                $selected_delivery['method']                =   $cart_item['wc_print_delivery_method'];
                $selected_delivery['carrier']               =   $cart_item['wc_print_delivery_carrier'];
                $selected_delivery['pickup_date']           =   $cart_item['wc_print_delivery_pickup_date'];
                $selected_delivery['submission']            =   $cart_item['wc_print_delivery_submission'];
                $response['html']= wc_print_delivery_date($possibilities,$selected_delivery);
                $response['success']=true;
            }else{
                $response['message']= $shipping_possibilities->get_error_message();
            }
        }
        wp_send_json($response);wp_die();
    }

    /**
     * @return void
     */
    public function display_shipping_content(){

        echo '<div id="wc-print-checkout-shipping"></div>';
    }

    /**
     *
     */
    public function validate_checkout_process($fields, $errors)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
            $product = $values['data'];
            $product_id = $product->get_id();
            if (WC_Print::is_print($product_id)) {
                 $product_name= $product->get_name();
                 $checkout_message=sprintf(__("You have not configured product %s.", 'wc-print'),$product_name);
                if (empty($values['wc_print_product_price'])) {
                    wc_add_notice($checkout_message, 'error');
                    return false;
                }
                if (empty($values['wc_print_sales_price'])) {
                    wc_add_notice($checkout_message, 'error');
                    return false;
                }
                if (empty($values['wc_print_total_price'])) {
                    wc_add_notice($checkout_message, 'error');
                    return false;
                }
                foreach (WC_Print_Cart::delivery_fields() as $field){
                    if (empty($values[ 'wc_print_delivery_'.$field])) {
                        wc_add_notice(sprintf(__('Please select a delivery date for product "%s" to get the price.', 'wc-print'),$product_name),'error' );
                        return false;
                    }
                }
                /*$default_options            =   WC_Print::default_configs_options($product_id);
                $required_attributes        =   WC_Print::get_formatted_attribute_options($product_id,$default_options,true);
                foreach ( $required_attributes as $slug => $attribute ) {
                    if ( empty( $values[$slug] )) {
                        //wc_add_notice( sprintf(__( 'Please select the option %s for product "%s"', 'wc-print'),$attribute['name'],$product->get_name()), 'error' );
                    }
                }*/
            }
        }
    }

    /**
     *
     */
    public function maybe_process_ajax_update_order_review()
    {
        if (!WC()->cart->is_empty()) return; // No need to add anything, standard action will be used

        if (!isset($_POST['post_data'])) return;

        $form = array();

        parse_str($_POST['post_data'], $form);

        // if( empty($form['ops_checkout']) ) return;

        // Add a condition
        ob_start();
        woocommerce_order_review();
        $woocommerce_order_review = ob_get_clean();

        // Get checkout payment fragment
        ob_start();
        woocommerce_checkout_payment();
        $woocommerce_checkout_payment = ob_get_clean();

        wp_send_json(array(
            'result' => 'success',
            'messages' => array(),
            'reload' => 'false',
            'fragments' => apply_filters('woocommerce_update_order_review_fragments', array(
                '.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
                '.woocommerce-checkout-payment' => $woocommerce_checkout_payment,
            )),
        ));
        wp_die();
    }
}

add_action('woocommerce_loaded', function(){
    new WC_Print_Checkout();
});
