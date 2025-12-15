jQuery( function( $ ) {
    console.log(wc_print_checkout_params)
    if ( typeof wc_print_checkout_params === 'undefined' ) {
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
    var $wc_print_checkout_content=$('.product .type-product');
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
    var wc_print_checkout = {

        cache: function () {
            wc_print_checkout.els = {};
            // Doms elements
            wc_print_checkout.els.document       = $( document );
            wc_print_checkout.els.document_body  = $( document.body );
            wc_print_checkout.els.submit_checkout    = $('button#place_order');
            wc_print_checkout.els.form_checkout    = $('form.woocommerce-checkout');
        },
        /**
         * Initialize event handlers and UI state.
         */
        init: function( ) {
            wc_print_checkout.cache();
            wc_print_checkout.els.submit_checkout.attr( "disabled", 'disabled' );
            //wc_print_checkout.display_checkout_shipping(false);
        },
        display_checkout_shipping:function (showError) {
            $('#wc-print-error').remove();
            $('#wc-print-checkout-shipping').html();
            wc_print_checkout.els.submit_checkout.attr( "disabled", 'disabled' );
            wc_print_checkout.els.submit_checkout.removeClass( 'added' );
            mask(wc_print_checkout.els.form_checkout );

            var ajaxData = {};
            var ajaxData = {
                action: 'wc_print_display_checkout_shipping',
                nonce: wc_print_checkout_params.ajax_nonce,
                cache:false,
                checkout:wc_print_checkout.serializeForm(wc_print_checkout.els.form_checkout )
            };
            // Make call to actual form post URL.
            $.ajax( {
                type: 'POST',
                url: wc_print_checkout_params.ajax_url,
                data: ajaxData,
                dataType: 'json',
                success:  function( response ) {
                    if ( ! response ) {
                        return;
                    }

                    if(response.success){
                        $('#wc-print-checkout-shipping').replaceWith(response.html);
                    }else{
                        if(showError){
                            var  notice='<div id="wc-print-error" class="woocommerce-message message-wrapper wc-print-error" role="alert">';
                            notice +='<ul class="woocommerce-error" role="alert">';
                            notice +='<li class="woocommerce-alert woocommerce-error">'+response.message;
                            notice+='</li>';
                            notice+='</ul>';
                            notice+='</div>';
                            wc_print_checkout.els.form_checkout.before(notice);
                            wc_print_checkout.els.submit_checkout.attr( "disabled", 'disabled' );
                            $('html,body').animate({scrollTop: $('#wc-print-error').offset().top},'slow');
                        }

                    }
                },
                complete: function() {
                    unmask( wc_print_checkout.els.form_checkout  );
                }
            } );
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

            var wc_print_ajax_event = wc_print_checkout.stringifyObject(event);
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

    wc_print_checkout.init(  );
    window['wc_print_checkout']=wc_print_checkout;
} );
