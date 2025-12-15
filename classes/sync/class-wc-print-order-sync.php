<?php

/**

 * Class WC_Print_Order_Sync

 */

class WC_Print_Order_Sync {

    private static $instance;

    /**

     * @return WC_Print_Order_Sync

     */
    public static function init() {
        if ( ! self::$instance instanceof self ) {

            self::$instance = new self;

        }
        return self::$instance;

    }

    /**
     * WC_Print_Order_Sync constructor.
     */
    public function __construct() {

    }
    public function init_hooks() {
        //add_action('woocommerce_order_status_changed', array( $this, 'order_status_changed' ),10,4 );
    }

    /**
     * @param $order_id
     * @param $status_from
     * @param $status_to
     * @param WC_Order $order

     */

    public function order_status_changed($order_id, $status_from, $status_to, WC_Order $order){

        $order_transition  =  get_post_meta($order_id,'_wc_print_delivery_'.$status_from.'_'.$status_to,true);
        $memory_limit       = ini_get( 'memory_limit' );
        ini_set("memory_limit","-1");
        $options            =   get_option('wc_print_settings');
        $cancel_trigger     =   $options['cancel_trigger'];
        $delivery_trigger   =   $options['delivery_trigger'];

        switch('wc-'.$status_to)

        {
            // https://api.print.com/orders/6000738534/6000738534-1/cancel
            case $cancel_trigger:
                $responseJson=get_post_meta($order_id,'_wc_print_order_response',true);
                $api= new WC_Print_Api();
                if(!empty($responseJson['order'])){
                    $print_order    =   $responseJson['order'];
                    $orderNumber    =  $print_order['orderNumber'];
                    foreach ($print_order['items'] as $print_item){
                        $orderItemNumber=$print_item['orderItemNumber'];
                        $endpoint="orders/".$orderNumber."/".$orderItemNumber."/cancel";
                        $cancelOrder= $api->call(array(),$endpoint,true,'POST',true);
                        if(!is_wp_error($cancelOrder)){
                            $order->add_order_note( sprintf( __( 'Print API Error: %s', 'wc-print'), $cancelOrder->get_error_message() ) );
                        }else{
                            $message =  sprintf(__( 'Order canceled in Print %s.', 'wc-print'), $orderNumber);
                            $order->add_order_note( $message );
                        }
                    }
                    update_post_meta($order_id,'_wc_print_delivery_'.$status_from.'_'.$status_to,true);
                }
                break;
            case $delivery_trigger:
                $this->delivery_trigger();
                break;
        }
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    public function sent_order(WC_Order $order){

        $order_id = $order->get_id();
        // create a  customer class instance
        $billingAddress  = self::get_billing_address($order);
        $shippingAddress = self::get_shipping_address($order);
        $printOrder['billingAddress']=$billingAddress;
        $printOrder['customerReference ']=$order->get_order_number();
        $totals                  = $order->get_order_item_totals();
        $items                   = $order->get_items();
        $response['success'] = false;
        $response['message'] = __('No order has been sent','wc-print');
        foreach ( $items as $item ) {
            $printOrderItem= array();
            $printOrderItem['customerReference'] = $item->get_name();
            $itemOptions= array();
            // get product
            if(WC_Print::is_print($item['product_id'])){
                $product = wc_get_product( $item['product_id']);
                $hideprefix = null;
                $meta_datas =  $item->get_meta_data();
                $attributes = get_post_meta($product->get_id(),'_print_product_attributes',true);
                $attribute_names = array();
                $attribute_values= array();
                foreach ( $attributes as $slug => $attribute ){
                    $attr_name= $attribute['name'];
                    $attribute_names[] = $attr_name;
                    $attribute_keys[] = $slug;
                    $attribute_values[$attr_name] = $slug;
                }
                $deliveryMethod = $deliveryDate = "";
                foreach ($meta_datas as $meta ){
                    if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) ) {
                        continue;

                    }
                    $meta->key    = rawurldecode( (string) $meta->key );
                    $meta->value   = rawurldecode( (string) $meta->value );
                    if(in_array($meta->key,array_values($attribute_keys))){
                        $itemOptions[$meta->key]= $meta->value;

                    }
                    if($meta->key =='wc_print_delivery_delivery_date'){
                        $deliveryDate = $meta->value;
                    }
                    if($meta->key =='wc_print_delivery_method'){
                        $deliveryMethod = $meta->value;
                    }
                    if($meta->key =='wc_print_delivery_promise'){
                        $deliveryPromise = $meta->value;
                    }
                }
                $printOrderItem['deliveryPromise']  = !empty($deliveryPromise) ? $deliveryPromise : 0;
                $printOrderItem['options']=$itemOptions;
                //$printOrderItem['senderAddress']=self::get_sender_address();
                $printOrderItemShipments['address']=$shippingAddress;
                if(!empty($deliveryDate)){
                    $printOrderItemShipments['deliveryDate'] =$deliveryDate;
                }
                if(!empty($deliveryMethod)){
                    $printOrderItemShipments['method'] =$deliveryMethod;
                }

                $printOrderItemShipments['copies']=intval($itemOptions['copies']);
                $printOrderItem['shipments'][]=$printOrderItemShipments;
                $printOrderItem['sku']=$product->get_sku();
                $printOrder['items'][]=$printOrderItem;

            }
        }
        if(!empty($printOrder) && count($printOrder['items']) > 0){
            $api= new WC_Print_Api();
            $remoteOrder= $api->call($printOrder,'orders',true,'POST');
            if(!is_wp_error($remoteOrder)){
                $placeOrder= json_decode($remoteOrder['body'],true);
                if(!empty($placeOrder) && !empty($placeOrder['order'])){
                    update_post_meta($order_id,'_wc_print_order_response',$placeOrder['order']);
                    $print_order    =   $placeOrder['order'];
                    update_post_meta($order_id,'_wc_print_order_sent',1);
                    update_post_meta($order_id,'_wc_print_order_date_sent',date('Y-m-d H:i:s'));
                    $orderNumber    =   $print_order['orderNumber'];
                    update_post_meta($order_id,'_wc_print_order_number',$orderNumber);
                    $message        =   sprintf(__( 'Order Sent to Print API Number %s.', 'wc-print'), $orderNumber);
                    $order->add_order_note( $message );
                    $response['success'] = true;
                    $response['message'] = $message;
                }else{
                    $message        =   __( 'Impossible de trouver les details de la commande print.', 'wc-print');
                    $order->add_order_note( $message );
                    $response['success'] = false;
                    $response['message'] = $message;
                }

            }else{
                $message= sprintf( __( 'Print API Error: %s', 'wc-print'), $remoteOrder->get_error_message() );
                $response['success'] = false;
                $response['message'] = $message;
                $order->add_order_note( $message);
            }
        }
        return  $response;
    }

    /**
     * @return array
     */
    public static function get_sender_address(){
        $settings       =       get_option('wc_print_settings');
        $shipping_address_2 = !empty($settings['shipping_address_2']) ? $settings['shipping_address_2']: "";
        $sender_address['firstName']   =   $settings['shipping_first_name'];
        $sender_address['lastName']   =    $settings['shipping_last_name'];
        $sender_address['country']   =    'NL';
        $sender_address['city']   =        $settings['shipping_city'];
        $sender_address['companyName']=     $settings['shipping_company'];
        $address['postcode']=       $settings['shipping_postcode'];
        $sender_address['telephone']=      $settings['shipping_phone'];
        $sender_address['fullstreet']=     $settings['shipping_address_1'].' '. $settings['shipping_address_2'];
        $sender_address['email'] =       $settings['shipping_email'];
        return  $sender_address;
    }
    /**
     * @param WC_Order $order
     * @return array
     */
    public static function get_billing_address(WC_Order $order){

        $billingAddress = array();
        // require

        if ( ! empty( $order->get_billing_city() ) ) {
            $billingAddress['city'] = $order->get_billing_city();
        } else {
            $billingAddress['city'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        }
        if ( ! empty( $order->get_billing_company() ) ) {
            $billingAddress['companyName'] = $order->get_billing_company();
        }
        $billingAddress['country']       = $order->get_billing_country();// require
        $billingAddress['email']          = $order->get_billing_email();// require

        if(!empty($order->get_billing_address_2())){
            $billingAddress['extraAddressLine']        = $order->get_billing_address_2();
        }
        $billingAddress['firstName']        =   $order->get_billing_first_name();// require
        $billingAddress['fullstreet']       =   $order->get_billing_address_1();// require
        $billingAddress['lastName']        =    $order->get_billing_last_name(); // require
        $billingAddress['postcode']       =     $order->get_billing_postcode();
        $billingAddress['telephone']      =     $order->get_billing_phone();
        return $billingAddress;
    }
    /**
     * @param WC_Order $order
     * @return array
     */
    public static function get_shipping_address(WC_Order $order){

        $billingAddress = array();
        // require
        if ( ! empty( $order->get_shipping_city() ) ) {
            $shippingAddress['city'] = $order->get_shipping_city();
        } else {
            $shippingAddress['city'] = $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name();
        }
        if ( ! empty( $order->get_shipping_company() ) ) {
            $shippingAddress['companyName'] = $order->get_shipping_company();
        }
        $shippingAddress['country']       = $order->get_shipping_country();// require
        $shippingAddress['email']          = $order->get_billing_email();// require
        if(!empty($order->get_shipping_address_2())){
            $shippingAddress['extraAddressLine']        = $order->get_shipping_address_2();
        }

        $shippingAddress['firstName']        =   $order->get_shipping_first_name();// require
        $shippingAddress['fullstreet']       =   $order->get_shipping_address_1();// require
        $shippingAddress['lastName']        =    $order->get_shipping_last_name(); // require
        $shippingAddress['postcode']       =     $order->get_shipping_postcode();
        if(!empty($order->get_shipping_phone())){
            $shippingAddress['telephone']      =     $order->get_shipping_phone();

        }else{
            $shippingAddress['telephone']      =     $order->get_billing_phone();
        }
        return $shippingAddress;
    }
}


