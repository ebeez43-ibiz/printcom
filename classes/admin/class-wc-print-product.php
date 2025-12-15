<?php

/**
 * Class WC_Print_Admin_Product
 */
class WC_Print_Admin_Product
{
    /**
     * @var
     */
    private static $instance;
    protected $product_table_type='product';
    protected $order_list_table='shop_order';
    /**
     * @return WC_Print_Admin_Product
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * WC_Print_Admin_Product constructor.
     */
    public function __construct()
    {

    }
    public function init_hooks()
    {

        add_action( 'admin_head',                           array($this,'admin_style' ));
        add_filter('woocommerce_product_data_tabs',         array($this, 'product_data_tab'));
        add_action('woocommerce_product_data_panels',       array($this,'display_product_data_fields'));
        add_action( 'woocommerce_admin_process_product_object', array($this,'save_product_data' ) );
        add_filter( 'manage_' . $this->product_table_type . '_posts_columns', array( $this, 'define_product_columns' ) , 999);
        add_action( 'manage_' . $this->product_table_type . '_posts_custom_column', array( $this, 'render_product_columns' ), 10, 2 );

        add_filter( 'manage_' . $this->order_list_table . '_posts_columns', array( $this, 'define_order_columns' ) , 999);
        add_action( 'manage_' . $this->order_list_table . '_posts_custom_column', array( $this, 'render_order_columns' ), 10, 2 );
    }

    /**
     *  Print Order Details
     * @param $columns
     * @return mixed
     */
    public function define_order_columns($columns){
        unset($columns['origin']);
        unset($columns['billing_address']);
        $columns['_wc_print_order_id']  = __( 'Print Order', 'wc-print');
        $columns['_wc_print_sent_order']  = __( 'Sent Order', 'wc-print');
        return $columns;
    }

    /**
     * Render Print Order Details
     * @param $column
     * @param $post_id
     * @return void
     */
    public function render_order_columns( $column, $post_id ) {



        if($column=='_wc_print_order_id') {
            $print_order= get_post_meta($post_id, '_is_print_order', true);
            if(!empty($print_order) && $print_order=='YES'){
                $print_number= get_post_meta($post_id, '_wc_print_order_number', true);
                $order_response= get_post_meta($post_id, '_wc_print_order_response', true);

                if(!empty($print_number)){
                    echo $print_number;
                }
            }

        }
        if($column=='_wc_print_sent_order') {
            $print_order= get_post_meta($post_id, '_is_print_order', true);
            $print_number= get_post_meta($post_id, '_wc_print_order_number', true);
            if(!empty($print_order) && $print_order=='YES'){
                if(empty($print_number)){
                    $icon = '<span class="dashicons dashicons-share-alt2"></span>';
                    echo '<a href="#" data-order_id="'.$post_id.'" class="button sent-print-order button-primary">'.$icon .'<a>';
                }else{
                    $order_url= 'https://app.print.com/orders/'.$print_number;
                    $dashicons= '<span class="dashicons dashicons-saved"></span>';
                    echo  '<a target="_blank" href="'.$order_url.'">'.$dashicons.$print_number.'</a>';

                }
            }

        }
    }

    /**
     * @param $columns
     * @return array
     */
    public function define_product_columns($columns){

        unset($columns['product_tag']);
        //unset($columns['product_type']);
        unset($columns['sku']);
        $columns['_print_sku']  = __( 'Print Sku', 'wc-print');
        $columns['_ibiz_additional_price']  = __( 'Ibiz Price', 'wc-print');
        return $columns;
    }

    /**
     * @param $column
     * @param $post_id
     */
    public function render_product_columns( $column, $post_id ) {

        if($column=='_ibiz_additional_price') {
            if(WC_Print::is_print($post_id)){
                $additional_price= get_post_meta($post_id, '_ibiz_additional_price', true);
                echo  !empty($additional_price)  ? wc_price($additional_price) : 0;
            }
        }
        if($column=='_print_sku' ) {
            if(WC_Print::is_print($post_id)) {
                $print_sku = get_post_meta($post_id, '_sku', true);
                echo !empty($print_sku) ? ($print_sku) : 0;
            }
        }
    }


    /**
     *
     */
    public function admin_style() {?>
        <style>
            #woocommerce-product-data ul.wc-tabs li.wc-print-additional-fields_options  a:before { font-family: "Dashicons"; content: '\f11d'; }
        </style>
        <?php
    }

    public function product_data_tab( $tabs) {
        $tabs['wc-print-additional-fields'] = array(
            'label' => __( 'Print Fields', 'wc-print'),
            'target' => 'wc_print_product_data_tab',
            'class'     => array( 'show_if_simple', 'show_if_variable'  ),
        );
        return $tabs;
    }

    /**
     * @return void
     */
    public function display_product_data_fields() {
        global $product_object;

        echo '<div id="wc_print_product_data_tab" class="panel woocommerce_options_panel">
        <div class="options_group">';

        ## ---- Content Start ---- ##

        echo '<p style="color: #ff0048">'.__('This is your Print content for the product ','wc-print'). '<strong>'.$product_object->get_name().'</strong>"…</p>';

        woocommerce_wp_text_input( array(
            'id'          => '_ibiz_additional_price',
            'value'       => $product_object->get_meta('_ibiz_additional_price'),
            'label'       => __('Additional price', 'wc-print'),
            'placeholder' => __('Ibiz Additional price', 'wc-print'),
            'description' => __('Ibiz Additional price.', 'wc-print'),
        ));
        woocommerce_wp_select( array(
            'id'            => '_ibiz_additional_price_type',
            'value'		    =>  $product_object->get_meta('_ibiz_additional_price_type'),
            'label'         => 'Additional price type',
            'options'       => array(''=>__('Choose type','wc-print'), 'percentage'=>__('Percentage','wc-print'), 'fixed_price'=>__('Fixed price','wc-print')),
            'cbvalue'		=> 'percentage',
            'desc_tip'		=> true,
            'description' 	=>  __("Define Ibiz Additional price type.")
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_print_sku',
            'value'       => $product_object->get_meta('_sku'),
            'label'       => __('Print SKU', 'wc-print'),
            'placeholder' => __('Print SKU', 'wc-print'),
            'description' => __('Print SKU.', 'wc-print'),
        ));
        echo '</div></div>';
    }

    /**
     * Save Product Custom  Metas
     * @param $post_id
     */
    public function save_product_data($product) {

        $product_metas= array('_ibiz_additional_price','_print_sku','_ibiz_additional_price_type');
        foreach ($product_metas as $meta) {
            $meta_value = isset( $_POST[$meta] ) ? sanitize_text_field($_POST[$meta]) : '';
            $product->update_meta_data( $meta, $meta_value );
        }
    }
}
add_action('init', array(WC_Print_Admin_Product::init(), 'init_hooks'));
