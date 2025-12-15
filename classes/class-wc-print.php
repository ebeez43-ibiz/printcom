<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Copyright WC_Print
 * Class WC_Print
 *
 * Main WP WooCommerce Print  class, add filters and handling all other files
 *
 * @class        WC_Print
 * @version        1.0.0
 * @author        imprimerie.be
 */
final class  WC_Print
{
    /**
     * Instance of Tennis_Live_Scores.
     *
     * @since 1.0.1
     * @access private
     * @var object $instance The instance of WP_ CNC.
     */
    private static $_instance;
    /**
     * Version.
     *
     * @since 1.0.1
     * @var string $version Plugin version number.
     */
    public $version = '1.0.1';
    /**
     * @var WC_Print_Layout
     */
    public $layout = null;

    /**
     * @var WC_Print_Ajax
     */
    public $ajax = null;

    /**
     * @var WC_Print_Content
     */
    public $content = null;
    /**
     * Session instance.
     *
     * @var WC_Print_Session|WC_Print_Session_Handler
     */
    public $session = null;

    /**
     * Query instance.
     *
     * @var WC_Print_Query
     */
    public $query = null;

    /**
     * File.
     *
     * @since 1.0.5
     * @var string $file Plugin __FILE__ path.
     */
    public $file = __FILE__;
    /**
     * @var
     */
    private $plugin_path;

    public function __construct()
    {

        if (!function_exists('is_plugin_active_for_network')) :
            require_once(ABSPATH . '/wp-admin/includes/plugin.php');
        endif;

        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        do_action('wc_print_loaded');
    }

    /**
     * Define Constants
     */
    public function define_constants()
    {
        define('WC_PRINT_PLUGIN_NAME', 'woocommerce-print');
        define('WC_PRINT_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . WC_PRINT_PLUGIN_NAME);
        defined('WC_PRINT_PLUGIN_VERSION') || define('WC_PRINT_PLUGIN_VERSION', '1.0');
        defined('WC_PRINT_VERSION') || define('WC_PRINT_VERSION', '1.0');
        define('WC_PRINT_TEMPLATE_DIR', WC_PRINT_PLUGIN_DIR . '/templates');
        define('WC_PRINT_IMPORT_DIR', WC_PRINT_PLUGIN_DIR . '/import/');
        define('WC_PRINT_ADMIN_TEMPLATE', WC_PRINT_PLUGIN_DIR . '/templates/admin/');
        define('WC_PRINT_VIEWS_DIR', WC_PRINT_PLUGIN_DIR . '/templates');
        define('WC_PRINT_TXT_DOMAIN', 'wc-print');
        define('WC_PRINT_ADMIN_VIEW_PATH', WC_PRINT_PLUGIN_DIR . '/admin/');

    }

    /**
     * Init.
     *
     * Initialize plugin parts.
     *
     * @since 1.0.1
     */
    public function includes()
    {
        require_once WC_PRINT_PLUGIN_DIR . '/classes/front/class-wc-print-api.php';
        if ($this->is_request('frontend')) {
            require_once WC_PRINT_PLUGIN_DIR . '/classes/front/class-wc-print-layout.php';
            require_once WC_PRINT_PLUGIN_DIR . '/classes/front/class-wc-print-product.php';
            require_once WC_PRINT_PLUGIN_DIR . '/classes/front/class-wc-print-checkout.php';
            require_once WC_PRINT_PLUGIN_DIR . '/classes/front/class-wc-print-cart.php';
            require_once WC_PRINT_PLUGIN_DIR . '/classes/front/class-wc-print-order.php';

        }
        // Admin Settings
        if ($this->is_request('admin')) {
            require_once WC_PRINT_PLUGIN_DIR . '/classes/admin/class-wc-print-product.php';
            require_once WC_PRINT_PLUGIN_DIR . '/classes/admin/class-wc-print-settings.php';
        }
        require_once WC_PRINT_PLUGIN_DIR . '/classes/sync/class-wc-print-order-sync.php';
        require_once WC_PRINT_PLUGIN_DIR . '/classes/sync/class-wc-print-product-sync.php';
    }

    /**
     * @param $type
     * @return bool|void
     */
    private function is_request($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
        }
    }
    /**
     *
     */
    private function init_hooks()
    {
        //register_activation_hook( WC_PRINT_PLUGIN_FILE, array( 'WC_Print_Install', 'install' ) );
        add_action('init', array($this, 'init'), 0);
        //add_action('init', array( $this, 'start_session'), 1);
    }

    /**
     * Instance.
     *
     * An global instance of the class. Used to retrieve the instance
     * to use on other files/plugins/themes.
     *
     * @return object Instance of the class.
     * @since 1.0.1
     *
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) :
            self::$_instance = new self();
        endif;

        return self::$_instance;

    }

    /**
     * @param $product_id
     * @return bool
     */
    public static function is_print($product_id)
    {
        $is_print = get_post_meta($product_id, '_is_print_product', true);
        return !empty($is_print) && $is_print == 1 ? true : false;

    }

    /**
     * @param $name
     * @return array|string|string[]
     */
    public static function print_attribute($name)
    {
        $name = str_replace('attribute_pa_', '', $name);
        $name = str_replace('pa_', '', $name);
        return str_replace('-', '', $name);
    }


    public static function get_excludes($sku){
        $json= self::get_entity('excludes',$sku);
        $excludes = [];
        foreach ($json['excludes'] as $items){
            $first_elm          = $items[0] ;
            $first_excludes=[];
            $first_property     = $first_elm['property'];
            foreach ($first_elm['options'] as $value){
                $first_excludes[$first_property.'_'.$value] = $first_property;
            }
            foreach ($first_excludes as  $property_key => $first_property){
                foreach ($items as $item){
                    $property   =$item['property'];
                    if($first_property!=$property){
                        //echo '<pre>' .$property_key.'@'.$property.'</pre>';
                        $prev_option = isset($excludes[$property_key][$property]) ? $excludes[$property_key][$property] : array();
                        $excludes[$property_key][$property] = array_merge($item['options'],$prev_option);
                    }
                }
            }
        }
        return $excludes;
    }
    /**
     * @param $product_id
     * @return array
     */
    public static function default_configs_options_v1($product_id)
    {
        $sku = get_post_meta($product_id,'_sku',true);

        if(empty($sku)){
            return  array();
        }
        $presets                =   wc_print_get_entity('presets',$sku);
        $resellers              =   wc_print_get_entity('resellers',$sku);
        $allowed_options        = array();
        foreach ($resellers['properties'] as $property){
            $property_slug=$property['slug'];
            $allowed_options[$property_slug]=array();
            $allowed_properties[]=$property_slug;
            foreach ($property['options'] as $option){
                $allowed_options[$property_slug][]=$option['slug'];
            }
        }
        $configuration=$presets['items'][0]['configuration'];
        foreach ($configuration as $key => $config){
            if(!is_null($config) && in_array($key,$allowed_properties)){
                if(in_array($config,$allowed_options[$key])){
                    $default_options[$key]= $config;
                    if($config=='custom'){
                        $default_options['width']=$configuration['width'];
                        $default_options['height']=$configuration['height'];
                    }
                }

            }
        }
        if(empty($default_options['copies'])){
            $default_options['copies']=$configuration['copies'];
        }
        return $default_options;
    }


    /**
     * @param $product_id
     * @return array
     */
    public static function default_configs_options($product_id)
    {
        $default_config     = get_post_meta($product_id, '_print_product_configuration', true);
        $configuration      = $default_config['items'][0]['configuration'];
        $default_options = array();
        foreach ($configuration as $key => $config) {
            if (!is_null($config)) {
                $default_options[$key] = $config;
            }
        }

        return $default_options;
    }
    /**
     * @param $product_id
     * @param $default
     * @return array
     */
    public static function get_formatted_attribute_options($product_id, $defaults, $required = true)
    {
        //customSizes
        $attributes = get_post_meta($product_id, '_print_product_attributes', true);
        $options = array();
        foreach ($attributes as $slug => $attribute) {
            if ($required) {
                if (!empty($defaults[$slug])) {
                    $options[$slug]['values'] = $attribute['values'];
                    $options[$slug]['name'] = $attribute['name'];
                }
            }
            if (!$required) {
                if (empty($defaults[$slug])) {
                    $options[$slug]['values'] = $attribute['values'];
                    $options[$slug]['name'] = $attribute['name'];
                }
            }

        }
          if($required){
            if(empty($options['copies']['values'])){
                $wc_products= wc_get_product($product_id);
                $resellers= self::get_entity('resellers',$wc_products->get_sku());
                $options['copies']['values']=self::get_copies_options($resellers);
                $options['copies']['name']='Quantité';
            }
        }
        return $options;

    }

    public static function get_copies_options($resellers){

         $copies_options =array();
         foreach ($resellers['properties'] as $property){
            $property_slug=$property['slug'];
            if($property_slug=='copies'){
                if(!empty($property['rangeSets'])){
                    $rangeSets= $property['rangeSets'][0];
                    if(!empty($rangeSets['summary'])){
                         $summaries=$rangeSets['summary'];
                          foreach ($summaries as $summary){
                            $copies_options[$summary] = $summary;
                        }
                    }

                }

            }

        }
       return  $copies_options;
    }

    public static  function get_entity($entity,$sku){
        $file=WC_PRINT_IMPORT_DIR.$entity.'/'.$sku.'.json';
        if(file_exists($file)){
            $json   = file_get_contents(  $file);
            $array  = json_decode($json,true);
            return $array;
        }

        return  array();

    }

    /**
     * @param $attributes
     * @param $attribute_slug
     * @param $value_slug
     * @return mixed
     */
    public static function get_option_name($attributes, $attribute_slug, $value_slug)
    {
        foreach ($attributes as $slug => $attribute) {
            if ($attribute_slug == $slug) {
                $options = $attribute['values'];
                foreach ($options as $option_slug => $name) {
                    if ($value_slug == $option_slug) {
                        return $name;
                    }
                }
            }

        }
        return $value_slug;
    }

    /**
     * @param $attributes
     * @param $attribute_slug
     * @param $value_slug
     * @return mixed
     */
    public static function get_option_slug($attributes, $attribute_slug, $value_name)
    {
        foreach ($attributes as $slug => $attribute) {
            if ($attribute_slug == $slug) {
                $options = $attribute['values'];
                foreach ($options as $option_slug => $name) {
                    if ($value_name == $name) {
                        return $option_slug;
                    }
                }
            }

        }
        return $value_name;
    }

    public function start_session()
    {
        if (!session_id()) {
            @session_start();
        }
    }

    /**
     * @return string
     */
    public function get_plugin_path()
    {

        if ($this->plugin_path) {
            return $this->plugin_path;
        }
        return $this->plugin_path = untrailingslashit(plugin_dir_path($this->get_file()));
    }

    public function init()
    {
        // Before init action.
        do_action('before_wc_print_init');

        // Set up localisation.
        $this->load_plugin_textdomain();

        // Init action.
        do_action('wc_print_init');
    }

    /**
     * Textdomain.
     *
     * Load the textdomain based on WP language.
     *
     * @since 1.0.1
     */
    public function load_plugin_textdomain()
    {
        // Load textdomain
         $locale = determine_locale();
         unload_textdomain( 'wc-print' );
         load_textdomain( 'wc-print', WP_LANG_DIR . '/woocommerce-print/wc-print-' . $locale . '.mo' );
         load_plugin_textdomain( 'wc-print', false, plugin_basename( dirname( WC_PRINT_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * @return string|void
     */
    public function ajax_url()
    {
        return admin_url('admin-ajax.php', 'relative');
    }

}
