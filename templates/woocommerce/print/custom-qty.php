
<label class="custom-other-qty">
    <?php _e('Autre','wc-print'); ?>
</label>

<input type="checkbox" name="custom_qty" value="custom_qty" class="wc-print-custom-qty" id="wc-print-other-qty"  <?php echo !empty($cart['custom_qty'] ) ?  'checked="checked"' : ''?> />
<div class="selector-options__customgroup" >
        <span style="display: inline-block;width: 100%;">
            <input placeholder="Quantité" id="custom-options__qty" style="display: inline-block;width: 100%;min-width:100%;" value="<?php echo !empty($cart['custom_qty']) && !empty($cart['custom_copies']) ?  $cart['custom_copies'] : ''?>"  class="selector-options__input wc-print-custom-size"  name="custom_copies" type="number" min="1" >
        </span>
</div>

