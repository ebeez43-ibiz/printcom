/* global wc_print_options */
jQuery(function ($) {

    console.log(wc_print_settings_js)
    // wc_print_settings_js is required to continue, ensure the object exists
    if (typeof wc_print_settings_js === 'undefined') {
        //return false;
    }


    /**
     * Object to handle AJAX calls
     */
    var print_import = {
        /**
         * Dom Elements
         */
        domElements: function () {
            print_import.els = {};
            console.log('#########SCREEEN##########', wc_print_settings_js.screen_id);
            if (wc_print_settings_js.screen_id == 'edit-shop_order') {
                $('.wp-list-table').before('<div class="import_errors"></div>');
                print_import.els.container = $('form#posts-filter');
            } else {
                print_import.els.container = $('div#wc_print_settings_import');

            }
            print_import.els.error_content = $('div#import_errors');
            print_import.els.import_trigger = $('a#import_print_product');
            print_import.els.sent_trigger = $('a.sent-print-order');


        },
        /**
         * Initialize event handlers and UI state.
         */
        init: function () {
            print_import.domElements();
            /*print_import.init_select2();
            $(document.body).on('click', '.cmb2_add_row', function (evt, row) {
                console.log(row);
                row.find('.cmb2_select').removeClass('select2-hidden-accessible');
                row.find(".select2-container").remove();
                row.find('.cmb2_select').select2();
            });*/
            print_import.els.import_trigger.click(function (event) {
                event.preventDefault();
                //event.stopPropagation();
                var sku = $('input#print_product_sku').val();
                console.log('######SKU#########', sku);
                if (typeof sku == "undefined" || sku == "") {
                    print_import.display_alert(wc_print_settings_js.empty_sku, 'error');
                } else {
                    $('.print-import-notice').remove();
                    print_import.import_product(sku);
                }
            });

            print_import.els.sent_trigger.click(function (event) {
                event.preventDefault();
                var order_id = $(this).data('order_id');
                print_import.sent_order(order_id);
            });

        },

        /**
         * Check if a node is masked for processing.
         *
         * @param {JQuery Object} $node
         * @return {bool} True if the DOM Element is UI masked, false if not.
         */
        is_masked: function ($node) {
            return $node.is('.processing') || $node.parents('.processing').length;
        },
        /**
         * Block a node visually for processing.
         *
         * @param {JQuery Object} $node
         */
        mask: function () {
            //if ( ! is_masked( $node ) ) {
            print_import.els.container.addClass('processing').block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
            //}
        },

        /**
         * Unblock a node after processing is complete.
         *
         * @param {JQuery Object} $node
         */
        unmask: function () {
            print_import.els.container.removeClass('processing').unblock();
        },
        init_select2: function () {
            $('.cmb2_select').select2();
        },

        sent_order: function (order_id) {
            print_import.mask();
            var ajaxData = {
                action: 'wc_print_sent_order',
                nonce: wc_print_settings_js.ajax_nonce,
                cache: false,
                order_id: order_id
            };

            // Make call to actual form post URL.
            $.ajax({
                type: 'POST',
                url: wc_print_settings_js.ajax_url,
                data: ajaxData,
                dataType: 'json',
                success: function (response) {
                    if (!response) {
                        return;
                    }
                    if (response.success) {
                        var success_message = response.message;
                        success_message += '<br/>';
                        success_message += response.front_url;
                        success_message += '<br/>';
                        success_message += response.admin_url;

                        print_import.display_alert(success_message, 'success');
                    } else {
                        print_import.display_alert(response.message, 'error');
                    }


                },
                complete: function () {
                    print_import.unmask();
                }
            });
        },

        import_product: function (sku) {
            print_import.mask();
            var ajaxData = {
                action: 'wc_print_import_product',
                nonce: wc_print_settings_js.ajax_nonce,
                cache: false,
                sku: sku
            };

            // Make call to actual form post URL.
            $.ajax({
                type: 'POST',
                url: wc_print_settings_js.ajax_url,
                data: ajaxData,
                dataType: 'json',
                success: function (response) {
                    if (!response) {
                        return;
                    }
                    if (response.success) {
                        var success_message = response.message;
                        success_message += '<br/>';
                        success_message += response.front_url;
                        success_message += '<br/>';
                        success_message += response.admin_url;

                        print_import.display_alert(success_message, 'success');
                    } else {
                        print_import.display_alert(response.message, 'error');
                    }


                },
                complete: function () {
                    print_import.unmask();
                }
            });
        },

        display_alert: function (message, type) {
            $('.print-import-notice').remove();
            var alert_class = type == 'error' ? ' notice-error ' : ' notice-success ';
            var alert = ' <p style="padding:20px" class="notice  print-import-notice  ' + alert_class + '">' + message + '</p>';
            print_import.els.error_content.append(alert);
            print_import.els.error_content.show();
        },
    };

    print_import.init();
});
