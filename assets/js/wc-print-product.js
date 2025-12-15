jQuery( function( $ ) {
    if ( typeof wc_print_product_params === 'undefined' ) {
        return false;
    }

    /**
     * Check if a node is masked for processing.
     *
     * @param {JQuery Object} $node
     * @return {bool} True if the DOM Element is UI masked, false if not.
     */
    var is_masked = function( $node ) {

        return $node.is( '.processing' ) || $node.parents( '.processing' ).length;
    };
    var $wc_print_product_content=$('.product .type-product');
    /**
     * Block a node visually for processing.
     *
     * @param {JQuery Object} $node
     */
    var mask = function( $node ) {
        //if ( ! is_masked( $node ) ) {
        $node.addClass('processing').block( {
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        } );
        //}
    };



    /**
     * Unblock a node after processing is complete.
     *
     * @param {JQuery Object} $node
     */
    var unmask = function( $node ) {
        $node.removeClass( 'processing' ).unblock();
    };
    /**
     * Object to handle AJAX calls for Calculator shipping changes.
     */
    var wc_print_product = {

        cache: function () {
            wc_print_product.els = {};
            // Doms elements
            wc_print_product.els.document       = $( document );
            wc_print_product.els.document_body  = $( document.body );
            wc_print_product.els.submit_cart    = $('button.wc_print_add_to_cart_button');
            wc_print_product.els.form_cart    = $('form.print_form_cart');
        },
        /**
         * Initialize event handlers and UI state.
         */
        init: function( ) {
            wc_print_product.cache();
            wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
            wc_print_product.dom_events();
            wc_print_product.calculate_product_price(true,true);
        },

        dom_events:function (){
            wc_print_product.els.document_body.on( 'change','.print_form_cart select#size',function(event){
                event.preventDefault();
                event.stopPropagation();
                $('#wc-print-other-size').prop( "checked" ,false);
                var selected_size = $(this).val();
                console.log(selected_size,"Size Changed");

                var $custom_width=  $('input#custom-options__width');
                var $custom_height = $('input#custom-options__height');
                $custom_width.val(0);
                $custom_height.val(0);
                wc_print_product.calculate_product_price(true,false);
            });
            wc_print_product.els.document_body.on( 'change','.wc-print-custom-size',function(event){
            var is_valid_size=  true;//wc_print_product.is_valid_size();
              console.log("######IS Valid Form#######" , is_valid_size);
              if(is_valid_size){
                  wc_print_product.calculate_product_price(true,false);
              }
            });
            wc_print_product.els.document_body.on( 'click','.delivery-promise-switch__option',function(event){
                if($(this).hasClass('delivery-promise-switch__option--active')){
                    return ;
                }
                $('.delivery-promise-switch__option').removeClass('delivery-promise-switch__option--active');
                $(this).toggleClass( "delivery-promise-switch__option--active" );
                $(this).find('.wc-print-options').prop( "checked" ,true);
                //$(this).find('.wc-print-options').trigger('change');
                wc_print_product.calculate_product_price(true,false);
            });
            wc_print_product.els.document_body.on( 'click','.choose-deliver-date',function(event){
                if($(this).hasClass('datepicker-date--active')){
                    return ;
                }
                var delivery_possibility = $(this).data('delivery_possibility');
                $('#_wc_print_delivery_cost').val(delivery_possibility.cost)
                $('#_wc_print_delivery_date').val(delivery_possibility.delivery_date)
                $('#_wc_print_delivery_latest_date').val(delivery_possibility.latest_dates)
                $('#_wc_print_delivery_method').val(delivery_possibility.method)
                $('#_wc_print_delivery_carrier').val(delivery_possibility.carrier)
                $('#_wc_print_delivery_submission').val(delivery_possibility.submission)
                $('#_wc_print_delivery_pickup_date').val(delivery_possibility.pickup_date);
                console.log('####################################')
                console.log(delivery_possibility);
                console.log('####################################')
                //$('.datepicker-date--active').removeClass('datepicker-date--active');
                //$(this).toggleClass( "datepicker-date--active" );
                //$(this).find('.wc-print-options').prop( "checked" ,true);
                //$(this).find('.wc-print-options').trigger('change');
                wc_print_product.calculate_product_price(true,false);
            });
            // On Page Load
            wc_print_product.els.document_body.on( 'change','.wc-print-options',function (){
                var slug =$(this).val();
                var name =$(this).attr('name');
                wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                wc_print_product.calculate_product_price(true,false);
            });
            wc_print_product.els.document_body.on( 'click','input.wc-print-radios',function (){
                var slug =$(this).val();
                var name =$(this).attr('name');
                wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                wc_print_product.calculate_product_price(true,false);
            });

            wc_print_product.els.document_body.on( 'click','.deliveryDate-flexslider-item-trigger',function(event){
                event.preventDefault();
                mask(wc_print_product.els.form_cart );
                var deliveryDateSelector =$(this).data('delivery_date');
                console.log('deliveryDate-flexslider-item',deliveryDateSelector);
                 $('.deliveryDate-flexslider-item').addClass('hide-datepicker');

                setTimeout(() => {
                   $('.deliveryDate-flexslider-item').addClass('hide-datepicker');
                        var $flexsliderItem=   $('#'+deliveryDateSelector);
                        $flexsliderItem.removeClass('hide-datepicker');
                         unmask( wc_print_product.els.form_cart  );
                }, 1000);

              
            });
        },

        is_valid_size: function (){
            $('#wc-print-error').remove();
            var is_valid = true;

            var $other_size= $('#wc-print-other-size').is(':checked');

            if (!$other_size) {
                return true;
            }
            var $custom_width=  $('input#custom-options__width');
            var $custom_height = $('input#custom-options__height');
            var width   = $custom_width.val();
            var height  = $custom_height.val();

            var min_height =$custom_height.attr('min');
            var max_height =$custom_height.attr('max');

            var min_width =$custom_width.attr('min');
            var max_width =$custom_width.attr('max');
            console.log("Size Changed" , width +'--->'+height);

            if(typeof width=="undefined" || width =="" || width ==0 ){
                var notice=  wc_print_product.build_notice('La largeur ne doit pas être vide ');
                wc_print_product.els.form_cart.before(notice);
                wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                $('html,body').animate({scrollTop: $('#wc-print-error').offset().top},'slow');
                is_valid = false;

            }

            if(typeof height=="undefined" || height =="" || height ==0 ){
                var notice=  wc_print_product.build_notice('La hauteur ne doit pas être vide ');
                wc_print_product.els.form_cart.before(notice);
                wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                $('html,body').animate({scrollTop: $('#wc-print-error').offset().top},'slow');
                is_valid = false;

            }

            if(width > max_width || width < min_width){
                var notice=  wc_print_product.build_notice('La largeur doit être comprise entre :'+min_width +' et ' +max_width);
                wc_print_product.els.form_cart.before(notice);
                wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                $('html,body').animate({scrollTop: $('#wc-print-error').offset().top},'slow');
                is_valid = false;
            }

            if(height > max_height || height < min_height){
                var notice=wc_print_product.build_notice('La hauteur doit être comprise entre:'+min_height +' et ' +max_height);
                wc_print_product.els.form_cart.before(notice);
                wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                $('html,body').animate({scrollTop: $('#wc-print-error').offset().top},'slow');
                is_valid = false;
            }

            return is_valid;
        },
        calculate_product_price:function (showError,reload) {
            $('#wc-print-error').remove();
            $('.wc-print-prices').remove();
            $('.selector__calculated-price').remove();
            $('#shippingModalModal').remove();
            wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
            wc_print_product.els.submit_cart.removeClass( 'added' );
            mask(wc_print_product.els.form_cart );

            console.log('############SERIALIZE_FORM###########',wc_print_product.buildFormData())

            var ajaxData = {};
            var product_id      =   wc_print_product.els.form_cart .data('product_id');
            var ajaxData = {
                action: 'wc_print_calculate_product_price',
                nonce: wc_print_product_params.ajax_nonce,
                product_id :product_id,
                cache:false,
                cart:wc_print_product.buildFormData()
            };

            // Make call to actual form post URL.
          $.ajax( {
                type: 'POST',
                url: wc_print_product_params.ajax_url,
                data: ajaxData,
                dataType: 'json',
                success:  function( response ) {

                    if ( ! response ) {
                        return;
                    }
                    $('#wc-print-variations').replaceWith(response.variations);
                    if(response.prices){
                        var prices = response.prices;
                        if(prices.pagePrice){
                            $('.product-page-price').html(prices.pagePrice);
                            wc_print_product.els.submit_cart.removeAttr( "disabled" );
                        }
                        if(prices.salesPrice){
                            wc_print_product.els.submit_cart.before('<input type="hidden"  class="wc-print-prices" name="_wc_print_sales_price"  value="'+prices.salesPrice+'"/>');
                        }
                        if(prices.productPrice){
                            wc_print_product.els.submit_cart.before('<input type="hidden"  class="wc-print-prices" id="_wc_print_product_price" name="_wc_print_product_price" value="'+prices.productPrice+'" />');
                        }
                        if(prices.totalPrice){
                            wc_print_product.els.submit_cart.before('<input type="hidden"   class="wc-print-prices" id="_wc_print_total_price" name="_wc_print_total_price"  value="'+prices.totalPrice+'" />');
                        }
                        if(prices.deliveryPromise){
                            wc_print_product.els.submit_cart.before('<input type="hidden"  class="wc-print-prices"  id="_wc_print_delivery_promise" name="_wc_print_delivery_promise"  value="'+prices.deliveryPromise+'" />');

                        }
                        if(prices.totalPriceHtml){
                            $('#wc-print-calculate-price').html(prices.totalPriceHtml)
                        }
                        if(prices.deliveryPromiseBlock){
                            $('#delivery-promise-wrapper').html(prices.deliveryPromiseBlock);
                        }
                    }


                    if(response.shippingPossibilities){
                        if(response.shippingPossibilities);
                        $('#shipping-possibilities-wrapper').replaceWith(response.shippingPossibilities);
                        // Calculate shipping price
                        if(reload){
                            console.log('#####Reload########');
                            wc_print_product.calculate_product_price(true,false);
                        }

                    }
                    if(response.fillAddress){
                        $('body').append(response.modal);
                        $('#shippingModalModal').modal('show');
                    }


                    if(showError && response.message ){
                        var  notice='<div id="wc-print-error" class="alert alert-danger  wc-print-error" role="alert">';
                        notice +='<ul class=wc-print-alert" role="alert">';
                        notice +='<li class="wc-print-error">'+response.message;
                        notice+='</li>';
                        notice+='</ul>';
                        notice+='</div>';
                        wc_print_product.els.form_cart.before(notice);
                        wc_print_product.els.submit_cart.attr( "disabled", 'disabled' );
                        $('html,body').animate({scrollTop: $('#wc-print-error').offset().top},'slow');
                    }
                    unmask( wc_print_product.els.form_cart  );
                },
                complete: function() {
                    
                },
                error: function (jqXHR, status, error) {
                    unmask( wc_print_product.els.form_cart  );
                    wc_print_product.build_notice(jqXHR.responseText);
                    
                  }
            } );

        },

        build_notice:function (message){
            $('#wc-print-error').remove();

            var  notice='<div id="wc-print-error" class="alert alert-danger  wc-print-error" role="alert">';
            notice +='<ul class=wc-print-alert" role="alert">';
            notice +='<li class="wc-print-error">'+message;
            notice+='</li>';
            notice+='</ul>';
            notice+='</div>';

            return notice;
        },
        stringifyObject :function ( obj ) {
            if ( _.isArray( obj ) || !_.isObject( obj ) ) {
                return obj.toString()
            }
            var seen = [];
            return JSON.stringify(
                obj,
                function( key, val ) {
                    if (val != null && typeof val == "object") {
                        if ( seen.indexOf( val ) >= 0 )
                            return
                        seen.push( val )
                    }
                    return val
                }
            );
        },
        set_ajax_storage:function(ajax_function,data,event){
            var seen = [];

            JSON.stringify(event, function(key, val) {
                if (val != null && typeof val == "object") {
                    if (seen.indexOf(val) >= 0) {
                        return;
                    }
                    seen.push(val);
                }
                return val;
            });

            var wc_print_ajax_event = wc_print_product.stringifyObject(event);
            sessionStorage.setItem("wc_print_ajax_function",ajax_function);
            sessionStorage.setItem("wc_print_ajax_data",ajax_function);
            sessionStorage.setItem("wc_print_ajax_event",wc_print_ajax_event);
        },
        serializeForm :function(form){
            var self = form,
                json = {},
                push_counters = {},
                patterns = {
                    "validate": /^[a-zA-Z][a-zA-Z0-9_]*(?:\[(?:\d*|[a-zA-Z0-9_]+)\])*$/,
                    "key":      /[a-zA-Z0-9_]+|(?=\[\])/g,
                    "push":     /^$/,
                    "fixed":    /^\d+$/,
                    "named":    /^[a-zA-Z0-9_]+$/
                };

            form.build = function(base, key, value){
                base[key] = value;
                return base;

            };
            form.push_counter = function(key){
                if(push_counters[key] === undefined){
                    push_counters[key] = 0;
                }
                return push_counters[key]++;
            };

            $.each(form.serializeArray(), function(){
                // skip invalid keys
                /*if(!patterns.validate.test(this.name)){
                 return;
                 }*/
                var k,
                    keys = this.name.match(patterns.key),
                    merge = this.value,
                    reverse_key = this.name;
                while((k = keys.pop()) !== undefined){
                    // adjust reverse_key
                    reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');
                    // push
                    if(k.match(patterns.push)){
                        merge = self.build([], self.push_counter(reverse_key), merge);
                    }
                    // fixed
                    else if(k.match(patterns.fixed)){
                        merge = self.build([], k, merge);
                    }
                    // named
                    else if(k.match(patterns.named)){
                        merge = self.build({}, k, merge);
                    }

                }
                json = $.extend(true, json, merge);
            });
            return json;
        },

        buildFormData:function(){
            var formData = {};
            //var formdata = new FormData();
            formData['selected_delivery']={};
            $('form#wc-print-add-to-cart-form').find('input').each(function () {
                var type = $(this).attr('type');
                formData[this.name]="";
            });

            $('form#wc-print-add-to-cart-form').find('select').each(function () {
                 formData[this.name]="";
            });

            $('form#wc-print-add-to-cart-form').find('input').each(function () {
                var type = $(this).attr('type');
                if (type!= 'radio' && type != 'checkbox') {

                    if($(this).hasClass('wc-print-delivery-input')){
                        console.log('####Shipping####  ' +this.name,$(this).val());
                        formData['selected_delivery'][this.name]=$(this).val();
                    }else{
                       formData[this.name]=$(this).val();  
                          console.log('####NORMAL####  ' +this.name,$(this).val());
                    }
                   
                }
               
            });

            $('form#wc-print-add-to-cart-form').find('input[type=radio]').filter(':checked').each(function () {
                var type = $(this).attr('type');
                 formData[this.name]=$(this).val();
            });

            $('form#wc-print-add-to-cart-form').find('input[type=checkbox]').filter(':checked').each(function () {
                var type = $(this).attr('type');
              
                 formData[this.name]=$(this).val();
            });
            $('form#wc-print-add-to-cart-form').find('select').each(function () {
                 var type = $(this).attr('type');
                 //formdata.append(this.name, $(this).val());
                 formData[this.name]=$(this).val();
            });

            return formData;
        },
        required: function(currenForm,requiredClass) {

            var requireds=currenForm.find('.'+requiredClass);
            var valid=true;
            requireds.each(function(i,input){
                input=$(input);
                var errorAppend=    $(input).parents('div.row');
                var inputLength =   1;
                var inputType   = input.attr('type');
                // checkbox & radio
                // if empty element
                if($.inArray(inputType, ['radio','checkbox']) !=-1){
                    var inputLength= $("input[type='"+inputType+"'][name='"+input.attr('name')+"']:checked").length;
                    if(inputLength==0) valid=false;
                }
                if ($(input).val() == '' || inputLength==0  ) {
                    input.addClass('error');
                    if(($(input).attr('id')=='datepicker-input')){
                        $(input).after(errorDate);
                    }else{
                        $(input).after(errorChart);
                    }
                    valid=false;

                } else {
                    $(input).removeClass('error');
                }
            });
            return valid;

        },

    };

    wc_print_product.init(  );
    window['wc_print_product']=wc_print_product;
} );
