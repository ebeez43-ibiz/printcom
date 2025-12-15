<?php
/**
 * Created by PhpStorm.
 * User: Achraf
 * Date: 28/02/2021
 * Time: 16:40
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_print_get_template( $template_name, $args = array()) {

	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args );
	}

	$located = wc_print_locate_template( $template_name);
	if ( ! file_exists( $located ) ) {
		_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'wc-print'), '<code>' . $located . '</code>' ), '2.1' );
		return;
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'wc_print_get_template', $located, $template_name, $args );

	do_action( 'wc_print_before_template_part', $template_name, $located, $args );


	include( $located );

	do_action( 'wc_print_after_template_part', $template_name, $located, $args );
}
function wc_print_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {


	ob_start();
	wc_print_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}
function wc_print_locate_template( $template_name ) {

	$template_path = WC_PRINT_PLUGIN_DIRE.'/templates/';

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/
	if ( ! $template ) {
		$template = $template_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'wc_print_locate_template', $template, $template_name, $template_path );
}
if(!function_exists('is_rest_api_request')) {
	function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) ); // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return apply_filters( 'woocommerce_is_rest_api_request', $is_rest_api_request );
	}
}

function  wc_print_address_from_session(){
    $session_address= WC()->session->get('_wc_print_customer_address');
    $customer_address = !empty( $session_address ) ? json_decode( $session_address ,true)  : array(); // @codingStandardsIgnoreLine

    if(empty($customer_address)){
        $customer_address = ! empty( $_COOKIE['_wc_print_customer_address'] ) ? json_decode( wp_unslash($_COOKIE['_wc_print_customer_address']) ,true)  : array(); // @codingStandardsIgnoreLine

    }
    return $customer_address;
}

function   wc_print_render_template($template, array $data){
    ob_start();
    wc_get_template( $template, $data );
    return ob_get_clean();
}

function  wc_print_delivery_date($apiPossibilities,$selected_delivery){
    ob_start();
    $cheapDeliveries=[];
    if(!empty($apiPossibilities)){
        $cheapDeliveries= wc_print_cheap_shipping_possibilities($apiPossibilities);
        if(empty($selected_delivery)){
          $selected_delivery= wc_print_default_selected_delivery($cheapDeliveries);
        }
    }
    wc_get_template( 'print/shipping/possibilities.php', array('possibilities'=>$cheapDeliveries,'selected_delivery'=>$selected_delivery) );
    return ob_get_clean();
}


function wc_print_cheap_shipping_possibilities($possibilities){
    $cheapDeliveries= [];
    foreach ($possibilities['results'] as $possibilityIndex => $possibility) {
        $cheapPossibilities         =  $possibility['possibilities'];
        $deliveryDate               =  $possibility['deliveryDate'];
        uasort( $cheapPossibilities  ,  'wc_print_sort_coust_callback'  );
        $cheapDeliveries[$deliveryDate] = $cheapPossibilities[0];
    }

  
    return array_values($cheapDeliveries) ;

}

function wc_print_default_selected_delivery($deliveries){


        
        $item  = $deliveries[3];
        $deliveryDate          =   $item['latestDeliveryDates'][0];
        $date_format= "Y-m-d";
        $default['delivery_date']       =  date_i18n( $date_format, strtotime( $deliveryDate ));
        $default['cost']                =  $item['price']['cost'];
        $default['latest_dates']        =  date_i18n( $date_format, strtotime(  $item['latestDeliveryDates'][0] )  );
        $default['method']              =  $item['method'];
        $default['carrier']             =  $item['carrier'];
        $default['pickup_date']         =  date_i18n( $date_format, strtotime(  $item['pickupDate'] )  );
        $default['submission']          =  date_i18n( $date_format, strtotime(  $item['submission'] )  );

    
        return $default;
}
/**
 * @return false
 */
function wc_print_product_from_cart(){
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        if (WC_Print::is_print($product_id)) {
            return $product_id;
        }
    }
    return false ;
}

/**
 *  Check If Customer Has Saved Aderess
 * @return bool
 */
function wc_print_customer_has_address(){

    $customer_address   = wc_print_address_from_session();
    $shipping_fields    = array('first_name','last_name','city','postcode','address_1');
    $billing_fields     = array('phone','email');
    $saved = true;
    foreach ($shipping_fields as $field ){
        if(empty($customer_address[$field])){
            $saved = false;
        }
    }
    foreach ($billing_fields as $field ){
        if(empty($customer_address[$field])){
            $saved = false;
        }
    }

    if($saved){
        return  true;
    }

    if( empty(WC()->customer->get_shipping_first_name())){
        return false;
    }

    if( empty(WC()->customer->get_shipping_last_name())){
        return false;
    }
    if( empty(WC()->customer->get_shipping_city())){
        return false;
    }
    if( empty(WC()->customer->get_shipping_postcode())){
        return false;
    }
    if( empty(WC()->customer->get_billing_phone())){
        return false;
    }
    if( empty(WC()->customer->get_shipping_address_1())){
        return false;
    }
    if( empty(WC()->customer->get_billing_email())){
        return false;
    }

    return  true;
}

function  wc_print_address_modal(){
    ob_start();
    $address['fist_name']   =  WC()->customer->get_shipping_first_name();
    $address['last_name']   =  WC()->customer->get_shipping_last_name();
    $address['city']        =  WC()->customer->get_shipping_city();
    $address['company']     =  WC()->customer->get_shipping_company();
    $address['postcode']    =  WC()->customer->get_shipping_postcode();
    $address['phone']       =  WC()->customer->get_billing_phone();
    $address['address_1']   =  WC()->customer->get_shipping_address_1();
    $address['address_2']   =  WC()->customer->get_shipping_address_2();
    $address['email']       =  WC()->customer->get_billing_email();
    $checkout = WC()->checkout();
    wc_get_template( 'print/shipping/address-modal.php', array('address'=>$address,'checkout'=>$checkout) );
    return ob_get_clean();
}
/**
 * @param $prices
 * @return float
 */
function wc_print_fees_margin($sales_price){

    $settings              =   get_option('wc_print_settings');
    $commission            =       !empty($settings['commission']) ?    intval($settings['commission']) : 0;
    $commission_type       =       !empty($settings['commission_type']) ? $settings['commission_type'] : 'percentage';
    $fees_margin = 0;
    if(!empty($commission)){
        switch ($commission_type){
            case  'percentage' :
                $fees_margin = round( ($commission)  * $sales_price) /100;
                break;
            case  'fixed_price' :
                 $fees_margin = $commission;
                break;
        }
    }
    return  !empty($fees_margin) ? floatval($fees_margin) : 0;
}


/**
 * @param $product_id
 * @param $sales_price
 * @return float|int|mixed
 */
function wc_print_product_product_margin($product_id,$sales_price){

     $additional_price  = get_post_meta($product_id,'_ibiz_additional_price',true);
     $price_type        = get_post_meta($product_id,'_ibiz_additional_price_type',true);
     $price_type        = !empty($price_type) ? $price_type :'percentage';
     $product_margin = 0;
     if(!empty($additional_price)){
         switch ($price_type){
             case  'percentage' :
                 $product_margin = round( (intval($additional_price)  * $sales_price) /100);
                 break;
             case  'fixed_price' :
                    $product_margin = $additional_price;
                 break;
         }
     }

     return  !empty($product_margin) ? floatval($product_margin) : 0;

}

function wc_print_remote_shipping($product_id,$product_sku,$cart){
    $settings       =       get_option('wc_print_settings');
    $address['firstName']   =   $settings['shipping_first_name'];
    $address['lastName']   =    $settings['shipping_last_name'];
    $address['country']   =      $settings['shipping_country'];;
    $address['city']   =        $settings['shipping_city'];
    $address['companyName']=     $settings['shipping_company'];
    $address['postcode']=       $settings['shipping_postcode'];
    $address['telephone']=      $settings['shipping_phone'];
    $address['fullstreet']=     $settings['shipping_address_1'].' '. $settings['shipping_address_2'];
    $address['email'] =       $settings['shipping_email'];
    /*$address['city']    =   WC()->customer->get_shipping_last_name();
    $address['firstName']   =   WC()->customer->get_shipping_first_name();
    $address['country']     =   WC()->countries->get_base_country();
    $address['city']        =   WC()->customer->get_shipping_city();
    $address['companyName']=    WC()->customer->get_shipping_company();
    $address['postcode']    =   WC()->customer->get_shipping_postcode();
    $address['telephone']   =   WC()->customer->get_billing_phone();
    $address['fullstreet']  =   WC()->customer->get_shipping_address_1().' '.WC()->customer->get_shipping_address_2();
    $address['email']       =   WC()->customer->get_billing_email();*/
    $attributes             =   get_post_meta($product_id,'_print_product_attributes',true);
    $options                =   array();

    foreach ( $attributes as $slug => $attribute ){
        if(!empty($cart[$slug])){
            $options[$slug]=$cart[$slug];
        }
    }
    if(!empty($options['size']) && $options['size']=='custom' ){
        if(!empty($cart['height'])){
            $options['height']= intval($cart['height']) * 10;
        }
        if(!empty($cart['width'])){
            $options['width']=intval ($cart['width']) *10;
        }
    }
    if(!empty($cart['custom_qty']) && !empty($cart['custom_copies'])){
        $cart['copies'] = $cart['custom_copies'];
    }
    $options['copies']=!empty( $cart['copies']) ?  intval($cart['copies']) : 1;
    $options['urgency']= "standard";
    $item['options']    =   $options;

    $item['sku']            =  $product_sku;
    $variants[0]["copies"]= !empty( $cart['copies']) ?  intval($cart['copies'])  : 1;
    $item['variants']   =   [];
    $item['maxDesigns']= 50;
    $item['deliveryPromise']= intval($cart['deliveryPromise']);
    $shipments= !empty( $cart['copies']) ?  intval($cart['copies'])  : 1;
    $item['shipments'][0]["copies"] =$shipments;
    $item['shipments'][0]["method"] ='';
    $body['item']=$item;
    $body['address']=$address;
    $toDay = date('Y-m-d');
    $body['dateFrom']=date('Y-m-d',strtotime("-1 days",strtotime($toDay)));
    $body['dateTo'] = date('Y-m-d',strtotime("+1 months",strtotime($toDay)));
    $body['numberOfDays']=30;
    $body['ensureRates']=false;
    $body['respectUrgency']=false;



    $api= new WC_Print_Api();
    return $api->call($body,'products/'.$product_sku.'/shipping-possibilities',true,'POST');
}

/**
 * @param $product_id
 * @param $product_sku
 * @param $cart
 * @return false[]
 */
function  wc_print_remote_price($product_id,$product_sku,$cart){

    try {

        $attributes = get_post_meta($product_id,'_print_product_attributes',true);
        $options=array();
        $response['sku']=$product_sku;
        $response = array(
            'success' => false,
            'price' => false,
    );
    foreach ( $attributes as $slug => $attribute ){
        if(!empty($cart[$slug])){
            $options[$slug]=$cart[$slug];
        }
    }
    if(!empty($cart['custom_size']) && $cart['custom_size']=='custom' ){
        
        $options['size']='custom';
        if(!empty($cart['custom_height'])){
            $options['height']= intval($cart['custom_height']) * 10;
        }
        if(!empty($cart['custom_width'])){
            $options['width']=intval ($cart['custom_width']) *10;
        }

    }
     if(!empty($cart['custom_qty']) && !empty($cart['custom_copies'])){
            $cart['copies'] = $cart['custom_copies'];
        }
    $options['copies']      =!empty( $cart['copies']) ?  intval($cart['copies']) : 1;
    $body['options']        =   $options;
    //$variants[0]["copies"]  =   !empty( $cart['copies']) ?  intval($cart['copies'])  : 1;
    $variants=[];
    $body['variants']       =   $variants;
    $body['sku']            =   $product_sku;
    $deliveryPromise        =  intval($cart['deliveryPromise']);
    $body['deliveryPromise']= $deliveryPromise;
    $delivery_price = 0;
    // Delivery Date
    if(!empty($cart['selected_delivery'])){
        $selected_delivery          =       $cart['selected_delivery'];
        $body['pickupDate']         =       $selected_delivery['pickup_date'];
        $shipments['copies']        =       !empty( $cart['copies']) ?  intval($cart['copies'])  : 1;
        $shipments['deliveryDate']  =       $selected_delivery['delivery_date'];
        $shipments['latestDeliveryDate']=   $selected_delivery['latest_dates'];
        $shipments['method']=               $selected_delivery['method'];
        $body['submissionDate']=            $selected_delivery['submission'];
        $body['shipments'][0]               =  $shipments;
        $delivery_price                     =  $selected_delivery['cost'];
    }
  
    $api= new WC_Print_Api();
    $price_call = $api->call($body,'products/'.$product_sku.'/price',true,'POST');
    if(!is_wp_error($price_call)){
        $json_prices =json_decode($price_call['body'],true);

       
        if(!empty($json_prices['prices'])){
            $response['deliveryPromise']=  $deliveryPromise;
            $response['success']=true;
            $havePrice=false;
            $totalPrice =0;
            $prices=$json_prices['prices'];
            if( !empty($prices['productPrice'])){
                $response['productPrice']=$prices['productPrice'];
            }
            if(!empty($prices['normalPrice'])){
                $response['normalPrice']=$prices['normalPrice'];
            }
            if(!empty($prices['salesPrice'])){
                $response['salesPrice']= $prices['salesPrice'];
                $havePrice=true;
            }
            if($havePrice && !empty($response['salesPrice'])){
                $response['deliveryPrice']  = $delivery_price;
                $product_margin          =       wc_print_product_product_margin($product_id,$response['salesPrice']);
                $fees_margin             =       wc_print_fees_margin($response['salesPrice']);

                $total_price      =   $response['salesPrice'];
                $total_price      +=  $fees_margin;
                $total_price      +=  $product_margin;
                $total_price      +=  floatval($delivery_price);
                $response['totalPrice']     =   $total_price;
                $response['productMargin']  =   $product_margin;
                $response['feesMargin']     =   $fees_margin;
                $response['deliveryPrice']   =   $delivery_price;
                $salesPriceLabel= __('Price',"woocommerce") .' : '.(wc_price( $response['totalPrice']));
                $salesPriceHtml= '<div class="price price--is-pretty" ><div class="price__output" ><span >'.$salesPriceLabel.'</span></div></div>';

                //$deliveryPriceLabel= __('Shipping costs',"woocommerce") .' : '.(wc_price( $response['deliveryPrice']));
                //$deliveryPriceHtml= '<div class="price price--is-pretty" ><div class="price__output" ><span >'.$deliveryPriceLabel.'</span></div></div>';

                $totalPriceHtml='<div class="selector-price selector__calculated-price">';
                $totalPriceHtml.= $salesPriceHtml;
                //$totalPriceHtml.= $deliveryPriceHtml;
                $totalPriceHtml.='</div>';
                $response['totalPriceHtml']=$totalPriceHtml;
                $response['pagePrice']=wc_price($total_price);
            }
            $templateData= array( 'prices' =>$prices,'selected'=>$cart['deliveryPromise']);
            $response['deliveryPromiseBlock']=wc_print_render_template('print/delivery-promise.php',$templateData);
            $response['body']=$json_prices;
            return $response;
        }else{
            return  new WP_Error('invalid_price',__('Bad query parameters.','wc-print'));
        }
    }else{
       return  $price_call;
    }

    } catch (Exception $e) {

        return  new WP_Error('internal_error',$e->getMessage());
    }


}

function  wc_print_format_delivery($possibility){

   
    $date_format= "Y-m-d";
    $default['delivery_date']       =  $possibility['deliveryDate'];
    $default['cost']                =  $possibility['price']['cost'];
    $default['latest_dates']       =  date_i18n( $date_format, strtotime(  $possibility['latestDeliveryDates'][0] )  );
    $default['method']              =  $possibility['method'];
    $default['carrier']             =  $possibility['carrier'];

    $default['pickup_date']       =  date_i18n( $date_format, strtotime(  $possibility['pickupDate'] )  );
    $default['submission']         =  date_i18n( $date_format, strtotime(  $possibility['submission'] )  );
    return $default;

}

function wc_print_dropdown_output($args,$tunnel_request)
{
    $sku        =   $args['sku'];
    $name       =   $args['name'];
    
    if(!empty($tunnel_request['printingmethod']) && $name== 'copies'){
        $printingmethod= $tunnel_request['printingmethod'];
        $range_sets =wp_print_copies_range_sets($sku,$printingmethod);
       
        if(!empty($range_sets)){
            $args['options']= $range_sets;
        }

     
    }
    $options    =     $args['options'];
    if(in_array($name,['size','copies'])){
            return wc_print_select_output($args,$tunnel_request);
    }else{
        if(count($options)  > 8){
            return wc_print_select_output($args,$tunnel_request);
        }else{
            return wc_print_radio_output($args,$tunnel_request);
        }
    }
}
function wc_print_select_output($args,$tunnel_request)
    {
       
      

        $options = $args['options'];
        $product = $args['product'];
        $attribute = $args['attribute'];
        $name       = $args['name'];
        $id         = $args['id'] ? $args['id'] : sanitize_title($name);
        $defaults = $args['defaults'] ? $args['defaults'] : array();
        $class = !empty($args['class']) ? $args['class'] : "";
        $show_option_none = !empty($args['show_option_none']) ? true : false;
        $attr_name = WC_Print::print_attribute($name);
        $selected = !empty($args['selected']) ? $args['selected'] : wc_print_default_select($options, $defaults, $attr_name);


        $required = "";//!empty($args['required']) ? ' required ' : "";
        $show_option_none_text = !empty($args['show_option_none']) ? $args['show_option_none'] : __('Choose an option', 'woocommerce');
        // Start building output HTML
        // Open <SELECT> and set 'Choose an option' <OPTION> text
        $trigger_class= $name=='size' ? " wc-print-options-size " : "wc-print-options";
        $class = $class . $trigger_class;
        $html =
            PHP_EOL .
            '<div class="btn-group row"> <select ' . esc_attr($required) .
            'id="' . esc_attr($id) . '" ' .
            'class="' . esc_attr($class) . '" ' .
            'name="' . esc_attr($attr_name) . '" ' .

            'data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute)) . '" ' .
            'data-show_option_none="' . ($show_option_none ? 'yes' : 'no') . '">' .
            PHP_EOL .
            '<option value="">' . esc_html($show_option_none_text) . '</option>';

        // Build <OPTION>s within <SELECT>
        $is_size_field = false;
        if (!empty($options)) {
            if($name =='size' && isset($options['custom'])){
                unset($options['custom']);
                $is_size_field = true;
            }
             /*if($args['id']=='copies'){
                echo '<pre>'.$tunnel_request['printingmethod'].'</pre>';
                echo '<pre>';
                print_r($options);
                echo '</pre>';
            }*/
            // $selected =
            foreach ($options as $slug => $opt_name) {   // And build <OPTION> into $html

                $is_exclude = wc_print_exclude_option($args,$slug,$tunnel_request);
                $disabled=  $is_exclude ? ' disabled ="disabled"' : '';
                if(!$is_exclude){
                    $html .= '<option    value="' . esc_attr($slug) . '"' . selected($selected, $slug, FALSE) . '>' .
                        esc_html(apply_filters('wc_print_option_name', $opt_name)) . '</option>';
                }


            }
        }

        // Close <SELECT> and return HTML
        $html .= '</select></div>';
        if($is_size_field){
            $sku =$args['sku'];
            $customConfig = wc_print_custom_sizes_config($sku);
            $html .= wc_print_render_template('print/custom-sizes.php',array('cart' =>$tunnel_request,'customConfig'=>$customConfig));
        }
      

        if($name =='copies'){
            $html .= wc_print_render_template('print/custom-qty.php',array('cart' =>$tunnel_request));
        }
        return $html . PHP_EOL;
    }

    /**
     * @param $args
     * @return string
     */
function  wc_print_radio_output($args,$tunnel_request)
{
    $options =  $args['options'];
    if (!empty($options)) {
        $product =  $args['product'];
        $attribute = $args['attribute'];
        $name = $args['name'];
        $id = $args['id'] ? $args['id'] : sanitize_title($name);
        $defaults = $args['defaults'] ? $args['defaults'] : array();
        $class = !empty($args['class']) ? $args['class'] : "";
        $show_option_none = !empty($args['show_option_none']) ? true : false;
        $attr_name = WC_Print::print_attribute($name);
        $selected = !empty($args['selected']) ? $args['selected'] : wc_print_default_select($options, $defaults, $attr_name);
        $required = "";//!empty($args['required']) ? ' required ' : "";
        $show_option_none_text = !empty($args['show_option_none']) ? $args['show_option_none'] : __('Choose an option', 'woocommerce');
        // Start building output HTML
        // Open <SELECT> and set 'Choose an option' <OPTION> text
        $md = !empty($options) ? ceil(12 / count($options)) : 12;
        $html =
            PHP_EOL .
            '<div class="btn-group row">';

        $class = $class . " btn-check wc-print-radios ";
        // Build <OPTION>s within <SELECT>
        foreach ($options as $slug => $name) {
            $is_exclude = wc_print_exclude_option($args,$slug,$tunnel_request);
            $disabled=  $is_exclude ? ' disabled ="disabled"' : '';
            $html .='<span class="btn-group-item">';
            $for = esc_attr($id) . '-' . sanitize_text_field($name);
            // And build <OPTION> into $html
            $html .= '<input '.$disabled.'  type="radio"   value="' . esc_attr($slug) . '" ' . checked($selected, $slug, FALSE) .
                ' id="' . esc_attr($for) . '" ' .
                ' class="' . esc_attr($class) . '" ' .
                ' name="' . esc_attr($attr_name) . '" '
                . ' />  <label class="btn btn-secondary col-md-' . $md . '" for="' . $for . '">' . $name . '</label>';
            $html .='</span>';

        }
        return $html . PHP_EOL . '</div>';
    } else {
        // $for = sanitize_text_field($name);
        //$html .= '<input type="text" data-default="' . $attr_name . '-' . $selected . '" value="' . esc_attr($selected) . '"' .
        //   'id="' . esc_attr($for) . '" ' .
        //   'class="' . esc_attr($class) . '" ' .
        //  'name="' . esc_attr($attr_name) . '" '
        // . ' />';
    }

    // Close <SELECT> and return HTML

}

/**
 * @param $options
 * @param $defaults
 * @param $attr_name
 * @return mixed|string
 */

function wc_print_default_select($options, $defaults, $attr_name)
{
    $selected = !empty($defaults[$attr_name]) ? $defaults[$attr_name] : '';
    if (empty($selected)) {
        if (!empty($options)) {
            $first = array_values($options);
            $selected = $first[0];

        }
    }

    return $selected;
}
function wc_print_display_field($args, $attribute,$tunnel_request){

    $slug= $args['id'];
    $attribute_name     =   $attribute['name'];
    $options            =   $attribute['values'];
    $args['attribute'] = $attribute_name;
    $args['options'] = $options;
    $html= '';
    if(!empty($options) ):
        $html.='<tr>';
        $html.='<th class="label">';
        $html.='<label class="'.esc_attr( sanitize_title( $attribute_name ) ).'">';
        $html.=wc_attribute_label( $attribute_name ).'</label>';
       $html.='</th>';
       $html.='<td class="value">';
       $selected = false;

        if(!empty($tunnel_request[$slug])){
            $selected = $tunnel_request[$slug];
           
        }
        $args['selected']=$selected;
        $args['required'] = true;
        $html.= wc_print_dropdown_output($args,$tunnel_request);
        $html.='</td>';
        $html.='</tr>';
  endif;
  return $html;

}

function wc_print_exclude_option($args,$option_key,$tunnel_request){

     // Extract all needed content from $args
    $excludes           =   $args['excludes'];
    $property_name      =   $args['name'];
    $defaults           =   $args['defaults'];
    foreach ($tunnel_request as $input_key => $input_val){
        if (is_array($input_val)){
            continue;
        }
        // Exclude Default Value;
        if( isset($defaults[$property_name]) && $defaults[$property_name] == $option_key){
             continue;
        }

        // get Excluded Selected Value
        if(isset($excludes[$input_key .'_'. $input_val])){
            $current_excludes= $excludes[$input_key .'_'. $input_val];
            if(isset($current_excludes[$property_name])){
                $excludes_values=  array_values($current_excludes[$property_name]);
                if(in_array($option_key,$excludes_values) ){
                    
                    return true;

                }
            }
        }

    }
    return false;
}


function wc_print_custom_sizes_config($sku){
    $customSizes = array();
    $customSizes['minHeight'] = 0;
    $customSizes['maxHeight'] = 10000;

    $customSizes['minWidth'] = 0;
    $customSizes['maxWidth'] = 10000;
    $file_path=  WC_PRINT_IMPORT_DIR.'resellers/'.$sku.'.json';
    if(file_exists($file_path)){
        $json   = file_get_contents($file_path);
        $resellers  = json_decode($json,true);
        foreach ($resellers['properties'] as $property){
            if($property['slug'] == 'size'){
                if(!empty($property['options'])){
                    foreach ($property['options'] as $option){
                        if($option['slug'] =='custom'){
                            return $option['customSizes'];
                        }
                    }
                }
            }
        }
    }
    return  $customSizes;
}



/**
 * @param $entity
 * @param $sku
 * @return mixed
 */
function wc_print_get_entity($entity,$sku){

    $file_path= wc_print_get_file_path($entity,$sku);
    if(!file_exists($file_path)){
        return  array();
    }
    $json   = file_get_contents($file_path);
    $array  = json_decode($json,true);
    return $array;
}

/**
 * @param $entity
 * @param $sku
 * @return mixed
 */
function wc_print_get_file_path($entity,$sku){
    return WC_PRINT_IMPORT_DIR.$entity.'/'.$sku.'.json';
}

/**
 * @param $file_path
 * @return bool
 */
function wc_print_file_expired($file_path){
   // date ("F d Y H:i:s.", filemtime($file_path));
    $max_age =36000;  // 10H 60s x 60 x 1O

 
    if(!file_exists($file_path)){
        return  true;
    }

    $filemtime = filemtime( $file_path );
    $time_difference = time() - $filemtime;
   
    if ( $time_difference < $max_age ) {
            return true;
    }
    

    return  false;
}

function wc_print_store_fields($product_id){
    //update_option('_wc_print_fields',array());
    $wc_print_fields           =   get_option('_wc_print_fields');
    $wc_print_fields            =  !empty($wc_print_fields) ? $wc_print_fields :array();
    $wc_print_fields_keys       = array_keys($wc_print_fields);
    $default_options            =   WC_Print::default_configs_options($product_id);
    $required_attributes        =   WC_Print::get_formatted_attribute_options($product_id,$default_options,true);
    $not_required_attributes    =   WC_Print::get_formatted_attribute_options($product_id,$default_options,false);
    foreach ( $required_attributes as $slug => $attribute ) {
        $field_name     =   $attribute['name'];
        if(!in_array($slug,$wc_print_fields_keys)){
            $wc_print_fields[$slug] = $field_name;
        }
    }

    foreach ($not_required_attributes as $slug => $attribute ) {
        if(!in_array($slug,$wc_print_fields_keys)){
            $field_name     =   $attribute['name'];
            $wc_print_fields[$slug] = $field_name;
        }
    }
    $wc_print_fields = $wc_print_fields;
    update_option('_wc_print_fields',$wc_print_fields);
}

function wp_print_copies_range_sets($sku,$printingmethod){

    $resellers= wc_print_get_entity('resellers',$sku);
    $copies_options =array();
    foreach ($resellers['properties'] as $property){
        $property_slug=$property['slug'];
        if($property_slug=='copies'){
            if(!empty($property['rangeSets'])){
                foreach ($property['rangeSets'] as $rangeSet){
                    if($rangeSet['printingmethod'] == $printingmethod){
                         if(!empty($rangeSet['summary'])){
                            $summaries=$rangeSet['summary'];
                            foreach ($summaries as $summary){
                                $copies_options[$summary] = $summary;
                            }
                        }
                    }
                }
            }
        }
    }
    return  $copies_options;
}

function  wc_print_selected_delivery($delivery_item,$selected_delivery){

    $date_format= "Y-m-d";
    $delivery_item['deliveryDate']       =  date_i18n( $date_format, strtotime( $delivery_item['deliveryDate'] ));


    if(
    $selected_delivery['carrier']== $delivery_item['carrier'] &&
    $selected_delivery['method']==  $delivery_item['method'] &&
    $selected_delivery['delivery_date']==$delivery_item['deliveryDate']){
        return true;
    }

    return false;

}

function wc_print_sort_coust_callback($a,$b){
        
        return $a['price']['cost'] < $b['price']['cost'] ? -1 : 1;
}
