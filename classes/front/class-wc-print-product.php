<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class WC_Print_Ajax_Product
 */
class WC_Print_Ajax_Product
{
    /**
     * @var
     */
    private static $instance;
    /**
     * @var array
     */
    private $auth_txt_settings;

    /**
     * @return WC_Print_Ajax_Product
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * WC_Print_Ajax_Product constructor.
     */
    public function __construct()
    {
        add_filter('woocommerce_is_purchasable', array($this, 'is_purchasable'), 10, 2);
        add_action( 'wp_ajax_wc_print_calculate_product_price',                 array($this, 'calculate_product_price') );
        add_action( 'wp_ajax_nopriv_wc_print_calculate_product_price',           array($this, 'calculate_product_price' ) );
        add_action( 'woocommerce_single_product_summary', array($this,'print_content'), 20 );
        add_filter('woocommerce_post_class',array($this,'product_classes'), 100,2 );
    }

        public function product_classes($classes, $product ){

        if (  ! is_a( $product, 'WC_Product' ) ) {
            return $classes;
        }
        if ( ! is_product() ) {
            return $classes;
        }

        if(WC_Print::is_print($product->get_id())) {
            $classes[] = 'is-wc-print-product';
        }
        // Add new class


        return $classes;
    }

    public function print_content(){
        global $product;
        $product_id= $product->get_id();
        $is_print= WC_Print::is_print($product_id);
        if(!$is_print){
            return;
        }
        wc_get_template( 'print/product/add-to-cart.php', array( 'product' => $product,'is_print'=>$is_print ) );

    }
    /**
     * Init Hook
     */
    public function init_hooks()
    {

    }
    public function is_purchasable($purchasable, $product)
    {
        if (WC_Print::is_print($product->get_id())) {
            return true;
        }
        return $purchasable;

    }

    public function update_product($sku){
            $product_Sync = new WC_Print_Product_Sync();

            $product_Sync->get_remote_presets($sku);
            $product_Sync->get_remote_reseller($sku);
            $product_Sync->get_remote_prices($sku);
            $product_Sync->get_remote_includes($sku);
            $product_Sync->get_remote_excludes($sku);
    }

    /**
     * @return void
     */
    public function calculate_product_price() {
        //ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
        try {
            $response = array(
                'success' => false,
                'price' => false,
                'fillAddress'=>false,
            );
            check_ajax_referer('wc_print_ajax_request', 'nonce');
            $product_id = intval( $_REQUEST['product_id'] );
            $cart       = $_REQUEST['cart'] ;

            if(isset($cart['selected_delivery[0]'])  && !empty($cart['selected_delivery[0]'])){
                $cart['selected_delivery'] = $cart['selected_delivery[0]'];
                unset($cart['elected_delivery[0]']);
            }
            /*echo '<pre>';
            print_r($cart);
            echo '</pre>';*/
            //WC()->session->set('_wc_print_tunnel_request',json_encode($cart));
            $product    = wc_get_product( $product_id );
            if ( false === $product ) {
                /* translators: %s: product id */
                $response['message'] = __( 'Product creation failed, could not find original product', 'wc-print') ;
                wp_send_json($response);wp_die();
            }
            $is_print = WC_Print::is_print($product_id);
            if (!$is_print) {
                $response['message'] = __( 'You are trying to configure a product that is not part of the Print category.', 'wc-print') ;
                wp_send_json($response);wp_die();
            }
            $delivery_price= 0;
            $product_sku= $product->get_sku();
            $post_object = get_post( $product->get_id() );
            //ob_start();
            setup_postdata( $GLOBALS['post'] =& $post_object );

            $response['variations']=wc_print_render_template('print/product/options.php',array('product'=>$product) );
            $response['product_sku'] = $product_sku;

            $this->update_product($product_sku);


            $api_price   = wc_print_remote_price($product_id,$product_sku,$cart);

            if(is_wp_error($api_price)){
                $response['message'] =$api_price->get_error_message();
                $response['error_price'] =true;
                wp_send_json($response);wp_die();
            }else{
                $response['prices']=$api_price;
            }
            $shipping_possibilities = wc_print_remote_shipping($product_id,$product_sku,$cart);

            if(is_wp_error($shipping_possibilities)){
                 $response['message'] =$shipping_possibilities->get_error_message();
                 $response['error_shipping'] =true;
                  wp_send_json($response);wp_die();
            }else{
                $possibilities= json_decode($shipping_possibilities['body'],true);
                $selected_delivery= !empty($cart['selected_delivery']) ? $cart['selected_delivery'] : array();
                $response['shippingPossibilities']=wc_print_delivery_date($possibilities,$selected_delivery);
            }

            wp_send_json($response);wp_die();
        }catch (Exception $exception){
            $response = array(
                'success' => false,
                'price' => false,
                'message' =>$exception->getMessage(),

            );
            wp_send_json($response);wp_die();
        }
    }

}
add_action('woocommerce_loaded', function(){
    new WC_Print_Ajax_Product();
});

