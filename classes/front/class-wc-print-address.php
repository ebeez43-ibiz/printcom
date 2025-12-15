<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class WC_Print_Ajax_Address
 */
class WC_Print_Ajax_Address{
    /**
     * @var
     */
    private static $instance;

    /**
     * @return WC_Print_Ajax_Address
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * WC_Print_Layout constructor.
     */
    public function __construct()
    {

    }

    public function init_hooks()
    {
        add_action( 'wp_ajax_wc_print_set_customer_address',                    array($this, 'set_customer_address') );

    }

    /**
     * @return void
     */
    public  function set_customer_address(){
        check_ajax_referer('wc_print_ajax_request', 'nonce');
        $response = array(
            'success' => true,
        );
        $address     = $_REQUEST['address'] ;
        $shipping_fields= array('first_name','last_name','city','postcode','address_1');
        $billing_fields= array('phone','email');
        foreach ($shipping_fields as $field){
            if(empty($address['shipping_'.$field])){
                $response['success']= false;
                $response['message']= __('Please complete the required fields','wc-print');
            }
        }
        foreach ($billing_fields as $field){
            if(empty($address['billing_'.$field])){
                $response['success']= false;
                $response['message']= __('Please complete the required fields','wc-print');
            }
        }
        if(!empty($address['billing_phone'])){
            if (! WC_Validation::is_phone( $address[ 'billing_phone'])){
                $response['success']= false;
                $response['message']=  __('Please enter a valid phone number','wc-print');;
            }
        }
        if(!empty($address['billing_email'])){
            if (! WC_Validation::is_email( $address[ 'billing_email'])){
                $response['success']= false;
                $response['message']=  __('Please enter a valid email address','wc-print');
            }
        }
        if($response['success']){
            $this->update_session($address);
        }
        WC()->session->set('_wc_print_customer_address',json_encode($address));
        wc_setcookie('_wc_print_customer_address', json_encode( $address ));

        /* if(!wc_print_customer_has_address()){
             $response['success']= false;
             $response['message']=  __('Data is not saved, please try again','wc-print');;

         }*/
        wp_send_json($response);wp_die();
    }

    /**
     * @param $data
     * @return void
     */
    public function update_session( $data ) {
        // Update both shipping and billing to the passed billing address first if set.
        $address_fields = array(
            'first_name',
            'last_name',
            'company',
            'email',
            'phone',
            'address_1',
            'address_2',
            'city',
            'postcode',
            'state',
            'country',
        );

        array_walk( $address_fields, array( $this, 'set_customer_address_fields' ), $data );
        WC()->customer->save();

        // Update cart totals now we have customer address.
        WC()->cart->calculate_totals();
    }



    /**
     * @param $field
     * @param $key
     * @param $data
     * @return void
     */
    public function set_customer_address_fields( $field, $key, $data ) {
        $billing_value  = null;
        $shipping_value = null;

        if ( isset( $data[ "billing_{$field}" ] ) && is_callable( array( WC()->customer, "set_billing_{$field}" ) ) ) {
            $billing_value  = $data[ "billing_{$field}" ];
            $shipping_value = $data[ "billing_{$field}" ];
        }

        if ( isset( $data[ "shipping_{$field}" ] ) && is_callable( array( WC()->customer, "set_shipping_{$field}" ) ) ) {
            $shipping_value = $data[ "shipping_{$field}" ];
        }

        if ( ! is_null( $billing_value ) && is_callable( array( WC()->customer, "set_billing_{$field}" ) ) ) {
            WC()->customer->{"set_billing_{$field}"}( $billing_value );
        }

        if ( ! is_null( $shipping_value ) && is_callable( array( WC()->customer, "set_shipping_{$field}" ) ) ) {
            WC()->customer->{"set_shipping_{$field}"}( $shipping_value );
        }
    }

    /**
     * @return void
     */
    public static function shipping_billing_address(){

        $shipping_fields    =  array('first_name','last_name','city','postcode','address_1');
        $billing_fields     =  array('first_name','last_name','city','postcode','address_1','phone','email');
        foreach ($shipping_fields as $field){
            add_filter('woocommerce_customer_get_shipping_'.$field, function ($value,$customer) use ($field) {
                if(empty($value)){
                    $customer_address= wc_print_address_from_session();
                    if(!empty($customer_address['shipping_'.$field])){
                        return $customer_address['shipping_'.$field];
                    }
                }
                return $value;
            },10,2);
        }
        foreach ($billing_fields as $field){
            add_filter('woocommerce_customer_get_billing_'.$field, function ($value,$customer) use ($field) {
                if(empty($value)){
                    $customer_address= wc_print_address_from_session();
                    if(!empty($customer_address['billing_'.$field])){
                        return $customer_address['billing_'.$field];
                    }
                    if(!empty($customer_address['shipping_'.$field])){
                        return $customer_address['shipping_'.$field];
                    }
                }

                return  $value;
            },10,2);
        }
    }



}
add_action('init', array(WC_Print_Ajax_Address::init(), 'init_hooks'));
