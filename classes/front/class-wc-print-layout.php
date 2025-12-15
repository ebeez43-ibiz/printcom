<?php

/**
 *
 */
class WC_Print_Layout
{
    /**
     * @var WC_Print_Layout
     */
    private static $instance;

    /**
     * Contains an array of script handles registered by WC.
     *
     * @var array
     */
    private static $scripts = array();

    /**
     * Contains an array of script handles registered by WC.
     *
     * @var array
     */
    private static $styles = array();
	/**
     * @return WC_Print_Layout
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
        add_filter( 'woocommerce_locate_template', array($this,'render_template'), 10, 3 );
        add_filter( 'wc_get_template_part', array($this,'get_template_part'), 10, 3 );
	    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
	    //add_filter( 'woocommerce_get_price_html',  array($this,'add_price_suffix'), 10, 2 );
        add_filter('woocommerce_quantity_input_args',  array($this,'quantity_input_args'), 10, 2 );

    }

    /**
     * @param $args
     * @param $product
     * @return mixed
     */
    public function quantity_input_args($args, $product){
        $args['product_id'] = $product->get_id();
        $args['sku']        = $product->get_sku();
        return $args;
    }

    /**
     * @param $price
     * @param $product
     * @return mixed|string
     */
    public function add_price_suffix($price, $product){
		$product_id = $product->get_id();
		if ( WC_Print::is_print($product_id)) {
			$price=$price .' <small style="font-size: 10px;color:red;margin-left:5px">'.__('The most sold','wc-print').'</small>';
		}

		return $price;
	}

    public function get_template_part($template, $slug, $name ){
        $template_name ="{$slug}-{$name}.php";
        if( file_exists( WC_PRINT_PLUGIN_DIRE . 'templates/woocommerce/' . $template_name ) ) {
            $template= WC_PRINT_PLUGIN_DIRE . 'templates/woocommerce/' . $template_name ;
        }
        return $template;
    }

    /**
     * @param $located
     * @param $template_name
     * @param $template_path
     * @return mixed|string
     */
    public function render_template( $located, $template_name, $template_path ) {

        if( file_exists( WC_PRINT_PLUGIN_DIRE . 'templates/woocommerce/' . $template_name ) ) {
			$located = WC_PRINT_PLUGIN_DIRE . 'templates/woocommerce/' . $template_name;
		}
		return $located;
	}
    /**
     * enqueue Account Scripts
     */
    public function enqueue_scripts() {
        global $wp_query;
	    global  $post;
        if(is_null($post)){
             return;
        }
         $is_print= WC_Print::is_print($post->ID);
        /**
         * Print Product 1 Checkout Page
         */
        if ($is_print or is_checkout() or is_cart()) {
             wp_register_style( 'wc-print-css', WC_PRINT_PLUGIN_URL . '/assets/css/wc-print-product.css',array(), time() );
             wp_enqueue_style( 'wc-print-css' );
             wp_enqueue_script( 'wc_print_blockui_js', WC_PRINT_PLUGIN_URL . '/assets/js/jquery-blockui/jquery.blockUI.min.js',array(), time()  );
         }

        if(is_checkout()){
            wp_enqueue_script( 'wc_print_checkout_js', WC_PRINT_PLUGIN_URL . '/assets/js/wc-print-checkout.js',array(), time()  );
            wp_localize_script( 'wc_print_checkout_js', 'wc_print_checkout_params', array(
                'i18n_remove_warning'              => __( 'Are you sure you want to remove this section?', 'wc-print'),
                'i18n_confirm_appt_delete'         => __( 'Are you sure you want to remove this section?', 'wc-print'),
                'i18n_please_wait'                 => __( 'Please Wait', 'wc-print'),
                'i18n_wrong_username_pass'         => __( 'Wrong username or pass', 'wc-print'),
                'ajax_url'                         => WC()->ajax_url(),
                'assets_url'                       => WC_PRINT_PLUGIN_URL . '/assets/',
                'wc_ajax_url'                      => WC_AJAX::get_endpoint( "%%endpoint%%" ),
                'ajax_nonce'                        => wp_create_nonce( "wc_print_ajax_request" ),
            ) );
        }

        if($is_print){
            //self::enqueue_script( 'selectWoo' );
            //self::enqueue_style( 'select2' );
            //wp_enqueue_script( 'wc_print_bootstrap_js', WC_PRINT_PLUGIN_URL . '/assets/js/bootstrap.min.js',array(), time()  );
           // wp_register_style( 'wc-print-modal', WC_PRINT_PLUGIN_URL . '/assets/css/modal.css',array(), time() );
           // wp_enqueue_style( 'wc-print-modal' );

            wp_enqueue_script( 'wc_print_product_js', WC_PRINT_PLUGIN_URL . '/assets/js/wc-print-product.js',array(), time()  );
            wp_localize_script( 'wc_print_product_js', 'wc_print_product_params', array(
            'i18n_remove_warning'              => __( 'Are you sure you want to remove this section?', 'wc-print'),
            'i18n_confirm_appt_delete'         => __( 'Are you sure you want to remove this section?', 'wc-print'),
            'i18n_please_wait'                 => __( 'Please Wait', 'wc-print'),
            'i18n_wrong_username_pass'         => __( 'Wrong username or pass', 'wc-print'),
            'ajax_url'                         => WC()->ajax_url(),
            'assets_url'                       => WC_PRINT_PLUGIN_URL . '/assets/',
            'wc_ajax_url'                      => WC_AJAX::get_endpoint( "%%endpoint%%" ),
            'ajax_nonce'                        => wp_create_nonce( "wc_print_ajax_request" ),
        ) );
        }
     }


}
add_action('init', array(WC_Print_Layout::init(), 'init_hooks'));


