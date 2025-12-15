<?php $selected= 0 ?>
<div id="delivery-promise-wrapper" style="display:none">
    <div class="delivery-promise-switch selector__panel" data-v-bed239d6="">
        <div class="delivery-promise-switch__title"><strong class="selector__title">
                <span class="cmsShorttext"  data-v-69b9e0e6=""><?php _e('Additional guarantee','wc-print') ?></span></strong>
        </div>
        <div class="delivery-promise-switch__switch">
            <div class="<?php echo ($selected==0 or empty($selected)) ? 'delivery-promise-switch__option--active ' : ''?> delivery-promise-switch__option delivery-promise-switch__option--promise">
                <input  type="radio" name="deliveryPromise" class=" wc-print-options deliveryPromiseRadio" value="0" checked="checked" >
                <p class="delivery-promise-switch__text">
                    <strong><?php _e('Standard','wc-print') ?><br>
                        <span class="cmsShorttext" data-v-69b9e0e6="">
                        <?php _e('Free','wc-print') ?>
                        </span>
                    </strong>
                </p>

            </div>
        </div>
        <div class="modal is-inactive" tabindex="0" data-v-e69b05e4="" data-v-ef311074=""><!----></div>
    </div>
</div>
