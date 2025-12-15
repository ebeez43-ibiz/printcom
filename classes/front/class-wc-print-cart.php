<?php

/**
 * Class WC_Print_Cart
 */
class WC_Print_Cart
{
    private static $instance;

    /**
     * @return WC_Print_Cart
     */
    public static function init()
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * WC_Print_Cart constructor.
     */
    public function __construct()
    {
    }

    /**
     * WC_Print_Cart Init Hooks
     */
    public function init_hooks()
    {
        add_action('woocommerce_before_calculate_totals', array($this, 'price_to_cart_item'), 10);
        add_filter('woocommerce_add_cart_item', array($this, 'add_price_cart_item'), 10, 2);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 3);
        add_filter('woocommerce_cart_item_price', array($this, 'cart_item_price'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_meta_on_cart'), 10, 4);
        add_action('woocommerce_cart_product_subtotal', array($this, 'cart_product_subtotal'), 1, 4);
        add_filter('woocommerce_cart_item_permalink', array($this, 'set_cart_item_permalink'), 10, 3);
        add_filter('woocommerce_cart_needs_shipping_address', array($this, 'cart_needs_shipping_address'), 10, 1);
        add_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), 999, 3);
    }

    /**
     * @param $product_subtotal
     * @param $product
     * @param $quantity
     * @param $cart
     * @return mixed|string
     */
    public function cart_product_subtotal($product_subtotal, $product, $quantity, $cart)
    {
        if (WC_Print::is_print($product->get_id())) {
            $price = self::get_print_price_from_cart($product->get_id());
            if ($price) {
                $row_price = $price * $quantity;
                return wc_price($row_price);
            }
        }
        return $product_subtotal;
    }

    /**
     * @param $print_id
     * @return false|mixed
     */
    public static function get_print_price_from_cart($print_id)
    {
        $in_cart = false;
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            if ($product_id == $print_id) {
                return $cart_item['wc_print_total_price'];
            }
        }

        return false;
    }

    /**
     * @param $price
     * @param $cart_item
     * @param $cart_item_key
     * @return mixed
     */
    public function cart_item_price($price, $cart_item, $cart_item_key)
    {

        $product_id = $cart_item["variation_id"] == 0 ? $cart_item["product_id"] : $cart_item["variation_id"];
        if (WC_Print::is_print($product_id) && !empty($cart_item["wc_print_total_price"])) {
            $cart_item["data"]->set_price($cart_item["wc_print_product_price"]);
            $cart_item["data"]->set_sale_price($cart_item["wc_print_total_price"]);
            return $cart_item["wc_print_total_price"];
        }
        return $price;

    }

    /**
     * Set Item Price
     * @param $cart_item_data
     * @param $cart_item_key
     * @return mixed
     */
    public function add_price_cart_item($cart_item_data, $cart_item_key)
    {

        $product_id = $cart_item_data["variation_id"] == 0 ? $cart_item_data["product_id"] : $cart_item_data["variation_id"];
        if (WC_Print::is_print($product_id)) {
            $cart_item_data["data"]->set_price($cart_item_data["wc_print_total_price"]);
            $cart_item_data["data"]->set_sale_price($cart_item_data["wc_print_total_price"]);
        }
        return $cart_item_data;
    }

    /**
     * @param $cart_object
     */
    public function price_to_cart_item($cart_object)
    {
        foreach (WC()->cart->get_cart() as $key => $value) {
            $product_id = $value["variation_id"] == 0 ? $value["product_id"] : $value["variation_id"];
            if (WC_Print::is_print($product_id)) {
                if (isset($value["wc_print_total_price"])) {
                    $value["data"]->set_price($value["wc_print_total_price"]);
                    $value["data"]->set_sale_price($value["wc_print_total_price"]);
                }
            }
        }
        WC()->cart->set_session();

    }

    /**
     * @param $passed
     * @param $product_id
     *
     * @return bool
     */
    public function add_to_cart_validation($passed, $product_id, $quantity)
    {

        $is_print = WC_Print::is_print($product_id);
        if ($is_print == true) {
            $product_info = $_POST;
            $default_options = WC_Print::default_configs_options($product_id);
            $required_attributes = WC_Print::get_formatted_attribute_options($product_id, $default_options, true);
            foreach ($required_attributes as $slug => $attribute) {
                if (empty($product_info[$slug])) {
                    // wc_add_notice( sprintf(__( 'Please select the option %s', 'wc-print'),$attribute['name']), 'error' );
                    //return false;
                }
            }
            $cart_message = __('Please select one or more options to get the product price', 'wc-print');
            if (empty($product_info['_wc_print_total_price'])) {
                wc_add_notice($cart_message, 'error');
                return false;
            }
            if (empty($product_info['_wc_print_sales_price'])) {
                wc_add_notice($cart_message, 'error');
                return false;
            }
            if (empty($product_info['_wc_print_product_price'])) {
                $passed = false;
                wc_add_notice($cart_message, 'error');
                return false;
            }

            foreach (self::delivery_fields() as $field) {
                if (empty($product_info[$field])) {
                    $passed = false;
                    wc_add_notice(__('Please select a delivery date to get the product price', 'wc-print'), 'error');
                    return false;

                }
            }

        }
        return $passed;
    }

    /**
     * @return string[]
     */
    public static function delivery_fields()
    {
        return array('cost', 'delivery_date', 'latest_dates', 'method', 'carrier', 'pickup_date', 'submission');

    }
    // Render meta on cart and checkout

    /**
     * @param $cart_data
     * @param null $cart_item
     *
     * @return array
     */
    public function display_meta_on_cart($cart_data, $cart_item = null)
    {
        $custom_items = array();
        /* Woo 2.4.2 updates */
        if (!empty($cart_data)) {
            $custom_items = $cart_data;
        }
        $product_id = $cart_item["variation_id"] == 0 ? $cart_item["product_id"] : $cart_item["variation_id"];
        if (WC_Print::is_print($product_id)) {
            $attributes = get_post_meta($product_id, '_print_product_attributes', true);
            foreach ($attributes as $slug => $attribute) {
                if (!empty($cart_item[$slug])) {
                    $attribute_name = $attribute['name'];
                    $option_slug = $cart_item[$slug];
                    $option_value = WC_Print::get_option_name($attributes, $slug, $option_slug);
                    $custom_items[] = array(
                        "name" => $attribute_name,
                        "value" => wc_clean($option_value)
                    );
                }
            }

            foreach (self::delivery_fields() as $field) {
                $custom_items[] = array(
                    "name" => str_replace('_', ' ', $field),
                    "value" => wc_clean($cart_item['wc_print_delivery_' . $field])
                );
            }

        }
        return $custom_items;
    }

    /**
     * @param $needs_shipping
     * @return bool
     */
    public function cart_needs_shipping_address($needs_shipping)
    {
        return true;
    }

    /**
     * @param $item_permalink
     * @param $cart_item
     * @param $cart_item_key
     * @return mixed
     */
    public function set_cart_item_permalink($item_permalink, $cart_item, $cart_item_key)
    {
        $product_id = $cart_item['product_id'];
        if (WC_Print::is_print($product_id)) {
            //return '#';
        }
        return $item_permalink;
    }

    /**
     * @param $cart_item_data
     * @param $product_id
     *
     * @return mixed
     */
    public function add_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        $product_info = $_POST;
        if (!WC_Print::is_print($product_id)) {
            return;
        }

        if (!empty($product_info)) {
            $attributes = get_post_meta($product_id, '_print_product_attributes', true);
            foreach ($attributes as $slug => $attribute) {
                if (!empty($product_info[$slug])) {
                    $cart_item_data[$slug] = $product_info[$slug];
                }
            }
            $cart_item_data['wc_print_sales_price'] = $product_info['_wc_print_sales_price'] ?? 0;
            $cart_item_data['wc_print_total_price'] = $product_info['_wc_print_total_price'] ?? 0;
            $cart_item_data['wc_print_product_price'] = $product_info['_wc_print_product_price'] ??0;
            $cart_item_data['wc_print_delivery_promise'] = $product_info['_wc_print_delivery_promise']??0;
            $cart_item_data['wc_print_product_id'] = $product_info['_wc_print_product_id'];
            /* below statement make sure every add to cart action as unique line item */
            $cart_item_data['unique_key'] = md5(microtime() . rand());

            foreach (self::delivery_fields() as $field) {
                $cart_item_data['wc_print_delivery_' . $field] = !empty($product_info[$field]) ? $product_info[$field] : "" ;
            }

        }
        return $cart_item_data;
    }
}

add_action('init', array(WC_Print_Cart::init(), 'init_hooks'));
