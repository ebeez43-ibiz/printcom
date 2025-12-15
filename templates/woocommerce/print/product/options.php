<?php
global $product;
$product_id                 = $product->get_id();
$product_sku                = $product->get_sku();
$excludes                   =   WC_Print::get_excludes($product_sku);
$default_options            =   WC_Print::default_configs_options($product_id);
$required_attributes        =   WC_Print::get_formatted_attribute_options($product_id,$default_options,true);
$not_required_attributes    =   WC_Print::get_formatted_attribute_options($product_id,$default_options,false);

$attributes_fields      =  array_merge($required_attributes,$not_required_attributes);
$wc_print_settings      =   get_option('wc_print_settings');
$hidden_fields          =  !empty($wc_print_settings['wc_print_hidden_fields']) ? $wc_print_settings['wc_print_hidden_fields'] :array();
$tunnel_request=       isset($_REQUEST['cart']) ? $_REQUEST['cart'] : $default_options  ;



?>
    <table class="variations" id="wc-print-variations" cellspacing="0" role="presentation">
        <tbody>
        <?php
        foreach ( $attributes_fields as $slug => $attribute ) :
            // Display Size
             if($slug =='size'):
                $args= array(
                        'product'   => $product,
                        'defaults'  => $default_options,
                        'id'        => $slug,
                        'name'     =>  $slug,
                        'sku'      => $product_sku,
                        'required'  => true,
                        'excludes' =>$excludes
                    );
                    echo wc_print_display_field($args, $attribute,$tunnel_request);
                endif;
            endforeach;
         $hidden_fields[]='size';
         $hidden_fields[]='copies';
         $hidden_fields[]='printingmethod';
         $hidden_fields[]='copies';
            // printingmethod
            foreach ( $attributes_fields as $slug => $attribute ) :
                if(!in_array($slug,$hidden_fields)):
                    $args= array(
                        'product'   => $product,
                        'defaults'  => $default_options,
                        'id'        => $slug,
                        'name'     =>  $slug,
                        'sku'      => $product_sku,
                        'required'  => true,
                        'excludes' =>$excludes
                    );
                    echo wc_print_display_field($args, $attribute,$tunnel_request);
                endif;
             endforeach;
             foreach ( $attributes_fields as $slug => $attribute ) :
                    if($slug=='printingmethod'):
                        $args= array(
                            'product'   => $product,
                            'defaults'  => $default_options,
                            'id'        => $slug,
                            'name'     =>  $slug,
                            'sku'      => $product_sku,
                            'required'  => true,
                            'excludes' =>$excludes
                        );
                        echo wc_print_display_field($args, $attribute,$tunnel_request);
                    endif;
             endforeach; 
            foreach ( $attributes_fields as $slug => $attribute ) :
                if($slug=='copies'):
                    $args= array(
                        'product'   => $product,
                        'defaults'  => $default_options,
                        'id'        => $slug,
                        'name'     =>  $slug,
                        'sku'      => $product_sku,
                        'required'  => true,
                        'excludes' =>$excludes
                    );
                    echo wc_print_display_field($args, $attribute,$tunnel_request);
                endif;
            endforeach; ?>
        </tbody>
    </table>
<?php
