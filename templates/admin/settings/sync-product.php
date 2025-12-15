
<div class="cmb-form" method="post" id="wc_print_settings_import"  style="margin-top: 40px;">
    <div class="cmb-tabs clearfix">
        <ul class="cmb-tab-nav">
            <li class="cmb-tab-import_product cmb-tab-active" data-panel="import_product"><a href="#"><i class="dashicons-admin-users dashicons"></i>
                    <span><?php _e('Import Product','wc-print') ?></span>
                </a>
            </li>
        </ul>
        <div class="cmb2-wrap form-table cmb-tabs-panel">
            <div id="cmb2-metabox-wc_print_settings_metabox" class="cmb2-metabox cmb-field-list"></div>
        </div>
        <div class="cmb2-wrap form-table cmb-tabs-panel cmb2-wrap-tabs">
            <div id="cmb2-metabox-wc_print_settings_metabox" class="cmb2-metabox cmb-field-list">
                <div class="show cmb-tab-panel cmb-tab-panel-import_product">
                    <div class="cmb-row cmb-type-text cmb2-id-print_product_sku table-layout" data-fieldtype="text">
                        <div class="cmb-th">
                            <label for="print_product_sku"><?php _e('Print product SKU','wc-print') ?></label>
                        </div>
                        <div class="cmb-td">
                            <input type="text" class="regular-text" name="print_product_sku"  id="print_product_sku" value="" data-hash="">
                            <p class="cmb2-metabox-description"><?php _e('Print product SKU','wc-print') ?></p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="import_errors" style="display: none">

    </div>
    <a href="#" class="button-primary" id="import_print_product" style="margin: 0 auto;display: block;width: 200px;text-align: center;"> <?php _e('Import','wc-print') ?> </a>
</div>


<script>
    jQuery(document).ready(function ($) {
        // Upload file button click event
        $('#import_print_product').on('click', function (e) {
            e.preventDefault();
            var sku = $(this).val();

        });
    });
</script>
