<div class="date-picker__summary" id="shipping-possibilities-wrapper">
    <?php 
    
    if(!empty($possibilities)) :
        $date_format= "F j, Y";

        ?>
        <input type="hidden" id="_wc_print_delivery_cost"       class="wc-print-delivery-input"         name="cost"                  value="<?php echo $selected_delivery['cost']?>">
        <input type="hidden" id="_wc_print_delivery_date"   class="wc-print-delivery-input"         name="delivery_date"             value="<?php echo $selected_delivery['delivery_date']?>">
        <input type="hidden" id="_wc_print_delivery_latest_date"    class="wc-print-delivery-input" name="latest_dates"              value="<?php echo $selected_delivery['latest_dates']?>">
        <input type="hidden" id="_wc_print_delivery_method"         class="wc-print-delivery-input" name="method"                    value="<?php echo $selected_delivery['method']?>">
        <input type="hidden" id="_wc_print_delivery_carrier"       class="wc-print-delivery-input"  name="carrier"                   value="<?php echo $selected_delivery['carrier']?>">
        <input type="hidden" id="_wc_print_delivery_pickup_date"   class="wc-print-delivery-input"  name="pickup_date"              value="<?php echo $selected_delivery['pickup_date']?>">
        <input type="hidden" id="_wc_print_delivery_submission"    class="wc-print-delivery-input"  name="submission"                value="<?php echo $selected_delivery['submission']?>">
        <div class="date-picker__optionswrapper row flexslider carousel" id="deliveryDate-flexslider">
            <div class="date-picker__summarytitle">
                <strong class="selector__title">
                    <h3 class="cmsShorttext" ><?php _e("Choisir une date d'expédition",'wc-print') ?></h3>
                </strong>
            </div>
            <ul class="slides" style="margin: 0px">
                  
                <?php foreach ($possibilities as $possibilityIndex => $item) :
                    if($possibilityIndex == 3):
                        $iterator = 1;
                        $item['deliveryDate']       =   $item['latestDeliveryDates'][0];
                        $delivery_cost              =   $item['price']['cost'];?>
                        <?php   $selected_class     =   wc_print_selected_delivery($item,$selected_delivery)  ? ' datepicker-date--active ' : '';
                        $delivery_possibility       =   wc_print_format_delivery($item); ?>
                        <li   class=" <?php echo $selected_class?> deliveryDate-flexslider-datepicker  datepicker-date choose-deliver-date col-md-6 mbl-5"  data-delivery_possibility='<?php echo  json_encode($delivery_possibility)?>'>
                            <div class="datepicker-date-carrier__logo">
                                <?php $logo= strtolower($item['carrier']).'.png' ;
                                $img=  WC_PRINT_PLUGIN_URL.'/assets/img/carriers/'.$logo.'?v='.time() ?>
                                <img width="40" src="<?php echo $img ?>" alt="<?php echo $item['carrier'] ?>">
                            </div>
                            <div class="datepicker-date__urgency">
                                    <span class="cmsShorttext" >
                                        <?php echo strtoupper($item['urgency']) . ' '. strtoupper($item['carrier']) ?>
                                    </span>
                            </div>
                            <div class="datepicker-date__dates">
                                <span class="datepicker-date__date"><?php echo  esc_html( date_i18n( $date_format, strtotime( $item['latestDeliveryDates'][0]) ) ) ?></span>
                            </div>
                     
                        
                            <?php   if ( current_user_can( 'manage_options' ) ) : ?>
                                <div class="datepicker-date__prices">
                                    <span ><?php echo wc_price($delivery_cost)?></span>
                                </div>
                            <?php endif  ?>
                        </li>
                       
                    <?php endif;
                endforeach;?>
            </ul>
        </div>

    <?php endif ?>
</div>
