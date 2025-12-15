<?php

/**
 * @since 23/10/2024
 * @version 1.0.0
 */
class WC_Print_Product_Sync {
    /**
     * @var
     */
    private static $instance;
    /**
     * @var
     */
    public $log_file;
    /**
     * @var
     */
    public $date;
    /**
     * @var
     */
    public $settings;

    /**
     * @var
     */
    public $default_category_id;
    /**
     * @var
     */
    public $default_image_id;

    /**
     * WC_Print_Product_Sync constructor.
     */
    public function __construct() {
        $this->init();
    }

    /**
     * @return void
     */
    public function init(){

        ini_set("memory_limit","-1");
        $this->api = new WC_Print_Api();
        $this->date =date('Y_m_d_H_i_s');
        $this->log_file= WC_PRINT_IMPORT_DIR. '/logs/import-'.$this->date.'.log';
        $this->settings       =  get_option('wc_print_settings');
        $default_category   = get_term_by( 'slug', $this->settings['default_category'], 'product_cat' );
        $this->default_category_id= 0;
        if($default_category){
            $this->default_category_id=$default_category->term_id;
        }
        $this->default_image_id           =$this->settings['default_image_id'];

    }

    /**
     * @return array|WP_Error
     */
    public function get_remote_products (){
        $endpoint='products';
        $file_path  =   wc_print_get_file_path('products','products');
        if(wc_print_file_expired($file_path)){
            $products   =   $this->api->call(false,$endpoint,true,'GET');
            if(!is_wp_error($products)){
                file_put_contents($file_path,$products['body']);
            }else{
                file_put_contents($this->log_file, sprintf("###ERRRR %s, %s ,%s, %s\n", $this->date, $products->get_error_message(), "products", "PRICE"), FILE_APPEND);
            }
        }

        return $products;
    }
    /**
     * @param $sku
     * @return mixed|WP_Error
     */
    public function  get_remote_presets($sku){

        $endpoint   ='presets/'.$sku;
        $file       =   wc_print_get_file_path('presets',$sku);
        if(wc_print_file_expired($file)) {
            $presets    =   $this->api->call(false,$endpoint,true,'GET');
        
            if (!is_wp_error($presets)) {
                file_put_contents($file, $presets['body']);
                file_put_contents($this->log_file, sprintf("###SUCEES_PRESETS %s, %s ,%s, %s\n", $this->date, $sku, "products", "PRICE"), FILE_APPEND);
                return $presets;
            }
            if (is_wp_error($presets)) {
                file_put_contents($this->log_file, sprintf("###ERRRR presets %s, %s ,%s, %s\n", $this->date, $presets->get_error_message(), $sku, "PRICE"), FILE_APPEND);
            }
        }
        return $presets;
    }

    /**
     * @param $sku
     * @return mixed|WP_Error
     */
    public function get_remote_reseller($sku){
        $file = wc_print_get_file_path('resellers', $sku);
        if(wc_print_file_expired($file)) {
                $endpoint = 'products/' . $sku . '?view=reseller';
            $reseller = $this->api->call(false, $endpoint, true, 'GET');

            if (!is_wp_error($reseller)) {
                file_put_contents($file, $reseller['body']);
                file_put_contents($this->log_file, sprintf("###SUCEES_RESELLER %s, %s ,%s, %s\n", $this->date, $sku, "products", "PRICE"), FILE_APPEND);
                return $reseller;
            }
            if (is_wp_error($reseller)) {
                file_put_contents($this->log_file, sprintf("###ERRRR reseller %s, %s ,%s, %s\n", $this->date, $reseller->get_error_message(), $sku, "PRICE"), FILE_APPEND);
            }
        }

    }


    public function get_remote_excludes($sku){

        $file = wc_print_get_file_path('excludes', $sku);
        if(wc_print_file_expired($file)) {
            $endpoint = 'products/' . $sku . '?fields=excludes';
            $excludes= $this->api->call(false, $endpoint, true, 'GET');

            if (!is_wp_error($excludes)) {
                file_put_contents($file, $excludes['body']);
                file_put_contents($this->log_file, sprintf("###SUCEES_EXCLUDES %s, %s ,%s, %s\n", $this->date, $sku, "products", "PRICE"), FILE_APPEND);
                return $excludes;
            }
            if (is_wp_error($excludes)) {
                file_put_contents($this->log_file, sprintf("###ERRRR excludes %s, %s ,%s, %s\n", $this->date, $excludes->get_error_message(), $sku, "PRICE"), FILE_APPEND);
            }
        }

    }

    public function get_remote_includes($sku){

        $file = wc_print_get_file_path('includes', $sku);
        if(wc_print_file_expired($file)) {
            $endpoint = 'products/' . $sku . '?fields=excludes&fields=includes&fields=rangeSets';
            $includes = $this->api->call(false, $endpoint, true, 'GET');

            if (!is_wp_error($includes)) {
                file_put_contents($file, $includes['body']);
                file_put_contents($this->log_file, sprintf("###SUCEES_includes %s, %s ,%s, %s\n", $this->date, $sku, "products", "PRICE"), FILE_APPEND);
                return $includes;
            }
            if (is_wp_error($includes)) {
                file_put_contents($this->log_file, sprintf("###ERRRR includes %s, %s ,%s, %s\n", $this->date, $includes->get_error_message(), $sku, "PRICE"), FILE_APPEND);
            }
        }

    }

    /**
     * @param $sku
     * @return void
     */
    public function get_remote_prices($sku){

        $file   =    wc_print_get_file_path('prices',$sku);
        if(wc_print_file_expired($file)) {
            $price = $this->get_default_price($sku);
            if (is_wp_error($price)) {
                file_put_contents($this->log_file, sprintf("###ERRRR PRICE  %s, %s ,%s, %s\n", $this->date, $price->get_error_message(), $sku, "PRICE"), FILE_APPEND);
            } else {
                if (!empty($price)) {
                    file_put_contents($file, $price['body']);
                    file_put_contents($this->log_file, sprintf("###SUCEES_PRICE %s, %s ,%s, %s\n", $this->date, $sku, "products", "PRICE"), FILE_APPEND);
                }
            }
        }
    }

    /**
     * @param $sku
     * @return mixed|WP_Error
     */
    public function get_default_price($sku){

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
        $body['sku']=$sku;
        foreach ($configuration as $key => $config){
            if(!is_null($config) && in_array($key,$allowed_properties)){
                if(in_array($config,$allowed_options[$key])){
                    $options[$key]= $config;
                    if($config=='custom'){
                        $options['width']=$configuration['width'];
                        $options['height']=$configuration['height'];
                    }
                }

            }
        }
        if(empty($options['copies'])){
            $options['copies']=$configuration['copies'];
        }

        $body['options']=$options;
        $body['variants'][0]['copies']=$configuration['copies'];
        $body['maxDesigns']=1;
        $body['deliveryPromise']=0;
        $body['warnings']=[];
        $endpoint='products/'.$sku.'/price';
        $price=$this->api->call($body,$endpoint,true,'POST');
        return $price;

    }


    /**
     * @return int|void
     * @throws WC_Data_Exception
     */
    public  function sync_products(){
        ini_set("memory_limit","-1");
        file_put_contents($this->log_file, sprintf("###START %s, %s ,%s, %s\n", $this->date, "PRODUCT", "SKU", "PRICE"), FILE_APPEND);
        $this->get_remote_products();
        $products  = wc_print_get_entity('products','products');
        foreach ($products as $print){
            $sku    = $print['sku'];
            $this->sync_product($sku);
       }
        file_put_contents($this->log_file, sprintf("###END %s, %s ,%s, %s\n", $this->date, "PRODUCT", "SKU", "PRICE"), FILE_APPEND);
    }

    /**
     * import One prin Product
     * @param $sku
     * @return int
     */
    public function sync_product($sku){
        $this->get_remote_products();
        try {
            if($this->skip_sku($sku)){
                // continue;
                // echo "NOT CONFIGURED  PRODUCT -->".$sku ."\n";
            }

            $this->get_remote_presets($sku);
            $this->get_remote_reseller($sku);
            $this->get_remote_prices($sku);
            $this->get_remote_includes($sku);
            $this->get_remote_excludes($sku);
            $prices_details     =wc_print_get_entity('prices',$sku);
            $print_details      =wc_print_get_entity('resellers',$sku);
            $presets_details    =wc_print_get_entity('presets',$sku);
            $sync_response['success'] = false;
            if(empty($prices_details)){
                $sync_response['message']= __("Unable to load print prices",'wc-print');
                file_put_contents($this->log_file, sprintf("###ERROR %s, %s ,%s, %s\n", $this->date, $sku, $sync_response['message'], ""), FILE_APPEND);
                return $sync_response;
            }

            if(empty($print_details)){
                $sync_response['message']= __("Unable to load print resellers",'wc-print');
                file_put_contents($this->log_file, sprintf("###ERROR %s, %s ,%s, %s\n", $this->date, $sku, $sync_response['message'], ""), FILE_APPEND);
                return $sync_response;
            }

            if(empty($presets_details)){
                $sync_response['message']= __("Unable to load print presets",'wc-print');
                file_put_contents($this->log_file, sprintf("###ERROR %s, %s ,%s, %s\n", $this->date, $sku, $sync_response['message'], ""), FILE_APPEND);
                return $sync_response;
            }

            $title =        $print_details['titleSingle'];
            $sku    =       $print_details['sku'];
            $properties=    $print_details['properties'];
            $prices         =$prices_details['prices'];
            $productPrice   =!empty($prices['productPrice']) ? $prices['productPrice'] :0;
            $salesPrice     =!empty($prices['salesPrice']) ? $prices['salesPrice'] :0;
            $normalPrice    =!empty($prices['normalPrice']) ? $prices['salesPrice'] :0;
            $attributes     = array();
            $id_product     = wc_get_product_id_by_sku($sku);
            $data= array(
                'author'        => '', // optional
                'name'          => $title,
                'content'       => $title,
                'excerpt'       => $title,
                'regular_price' => $normalPrice, // product regular price
                'sale_price'    => $salesPrice, // product sale price (optional)
                'price'         => $productPrice, // product sale price (optional)
                'stock'         => '0', // Set a minimal stock quantity
                'manage_stock'  => false,
                'category_id'   => $this->default_category_id,
                'image_id'      => $this->default_image_id, // optional
                'gallery_ids'   => array(), // optional
                'sku'           =>$sku, // optional
                'tax_class'     => '', // optional
                'weight'        => '', // optional
                // For NEW attributes/values use NAMES (not slugs)
                //'attributes'    => $attributes,
            );
            if ($id_product) {
                $wc_product = new WC_Product($id_product);
            }else{
                $product_id= self::create_product_simple( $data);
                $wc_product= new WC_Product($product_id);
            }
            if( ! empty( $data['price'] ) )
                $wc_product->set_price( $data['price'] );

            if( ! empty( $data['regular_price'] ) )
                $wc_product->set_regular_price( $data['regular_price'] );

            if( ! empty( $data['sale_price'] ) )
                $wc_product->set_sale_price( $data['sale_price'] );

            foreach ($properties as $property){
                $attribute_values   = array();
                $attribute_name     =   $property['title'];
                $attribute_slug     =   $property['slug'];
                $options            =   $property['options'];
                foreach ($options as $option){
                    $slug   =   $option['slug'];
                    $attribute_values[$slug]=!empty($option['name']) ?$option['name'] :$option['slug'] ;
                }
                $attributes[$attribute_slug]['values'] = $attribute_values;
                $attributes[$attribute_slug]['name'] = $attribute_name;
            }
            // print_r($productPrice.'-->'.$salesPrice.'--->'.$normalPrice);
            //WCPrintProduct::create_attributes($product_variation_id,$attributes);
            $wc_product_id= $wc_product->get_id();
            update_post_meta($wc_product_id,'_stock_status','instock');
            update_post_meta($wc_product_id,'_is_print_product',1);
            update_post_meta($wc_product_id,'_print_product_attributes',$attributes);
            update_post_meta($wc_product_id,'_print_product_prices',$prices_details);
            update_post_meta($wc_product_id,'_print_product_configuration',$presets_details);
            update_post_meta($wc_product_id,'_print_product_resellers',$print_details);
            $wc_product->save();
            file_put_contents($this->log_file, sprintf("###PRODUCT %s, %s ,%s\n", $this->date, $product_id,$sku, $salesPrice), FILE_APPEND);

            $sync_response['success']       = true;
            $sync_response['product_id']    = $wc_product_id;
            $sync_response['product_name'] = $wc_product->get_name();
            $front_url        = get_permalink($wc_product_id )  ;
            $edit_link      = get_edit_post_link( $wc_product_id );
            $front_target ='<a  target="_blank" href="'. $front_url .'">'.__('Front page','wc-print') .'</a>';
            $bo_target ='<a target="_blank" href="'. $edit_link .'">'.__('Admin page','wc-print')  .'</a>';
            $sync_response['message']  =  sprintf(__('The product with SKU "%s" has been imported successfully', 'wc-print'), $sku);
            wc_print_store_fields($wc_product_id);
            $sync_response['front_url'] =$front_target;
            $sync_response['admin_url'] =$bo_target;
            return $sync_response;

        }catch (Exception $exception){
            file_put_contents($this->log_file, sprintf("###ERROR %s, %s ,%s\n", $this->date, $product_id,$sku, $salesPrice), FILE_APPEND);
            $sync_response['success'] = false;
            $sync_response['product_id'] = 0;
            $sync_response['message'] =$exception->getMessage();
            return $sync_response;

        }

    }

    /**
     * @param $data
     * @return int
     * @throws WC_Data_Exception
     */
    public static function create_product_simple( $data ){
        $postname = sanitize_title( $data['title'] );
        $author = empty( $data['author'] ) ? '1' : $data['author'];

        $post_data = array(
            'post_author'   => $author,
            'post_name'     => $postname,
            'post_title'    => $data['name'],
            'post_content'  => $data['content'],
            'post_excerpt'  => $data['excerpt'],
            'regular_price'  => $data['regular_price'],
            'price'         => $data['price'],
            'sale_price'    => $data['sale_price'],
            'post_status'   => 'publish',
            'ping_status'   => 'closed',
            'post_type'     => 'product',
            'guid'          => home_url( '/product/'.$postname.'/' ),
        );

        // Creating the product (post data)
        $product_id = wp_insert_post( $post_data );

        // Get an instance of the WC_Product_Variable object and save it
        $product = new WC_Product( $product_id );
        $product->save();

        ## ---------------------- Other optional data  ---------------------- ##
        ##     (see WC_Product and WC_Product_Variable setters methods)
        if( ! empty( $data['price'] ) )
            $product->set_price( $data['price'] );

        if( ! empty( $data['regular_price'] ) )
            $product->set_regular_price( $data['regular_price'] );

        if( ! empty( $data['sale_price'] ) )
            $product->set_sale_price( $data['sale_price'] );
        // THE PRICES (No prices yet as we need to create product variations)

        // IMAGES GALLERY
        if( ! empty( $data['gallery_ids'] ) && count( $data['gallery_ids'] ) > 0 )
            $product->set_gallery_image_ids( $data['gallery_ids'] );

        if(!empty($data['image_id']))
            $product->set_image_id( $data['image_id'] );

        if(!empty($data['category_id'])){
            $category_ids[]=$data['category_id'];
            $product->set_category_ids($category_ids );
        }
        // SKU
        if( ! empty( $data['sku'] ) )
            $product->set_sku( $data['sku'] );

        // STOCK (stock will be managed in variations)
        $product->set_stock_quantity( $data['stock'] ); // Set a minimal stock quantity
        $product->set_manage_stock($data['manage_stock']);
        $product->set_stock_status('instock');

        // Tax class
        if( empty( $data['tax_class'] ) )
            $product->set_tax_class( $data['tax_class'] );

        // WEIGHT
        if( ! empty($data['weight']) )
            $product->set_weight(''); // weight (reseting)
        else
            $product->set_weight($data['weight']);

        $product->validate_props(); // Check validation

        ## ---------------------- VARIATION ATTRIBUTES ---------------------- ##
        $product->save(); // Save the data
        return $product->get_id();
    }

    /**
     * Clear Folder cache
     * @return void
     */
    public  function cleanCacheFolders(){
        $init_folder=WC_PRINT_IMPORT_DIR . '/';

        $products='products/';
        $presets='presets/';
        $prices='prices/';
        $resellers='resellers/';
        $folders[]= $products;
        $folders[]= $resellers;
        $folders[]= $prices;
        $folders[]= $presets;


        foreach ($folders as $folder){
            $files = glob($init_folder.$folder.'*'); // get all file names
            foreach($files as $file){ // iterate files
                self::deleteDir($file);
            }
        }

    }

    /**
     * @param $dir
     * @return bool|void
     */
    public  static function deleteDir($dir) {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        if (is_dir($dir)) {
            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') continue;
                if (!self::deleteDir($dir.DIRECTORY_SEPARATOR.$item)) return false;
            }
        }

        //return rmdir($dir);
    }

    /**
     * @param $sku
     * @return bool|void
     */
    public function skip_sku($sku){

        $file     = wc_print_get_file_path('resellers',$sku);
        if(!file_exists($file )){
            return true;
        }
        $prices_file     = wc_print_get_file_path('prices',$sku);

        if(!file_exists($prices_file )){
            return true;
        }
        $presets_file     = wc_print_get_file_path('presets',$sku);
        if(!file_exists($presets_file )){
            return true;
        }
    }
}


