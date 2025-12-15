
 <label class="format-largeur-x-hauteur">
     <?php _e('Autre','wc-print'); ?>
 </label>
 <?php  $minWidth = $customConfig['minWidth']/10 ; // To Mm
        $maxWidth = $customConfig['maxWidth']/10 ;

 $minHeight = $customConfig['minHeight']/10 ;
 $maxHeight= $customConfig['maxHeight']/10 ;
 ?>
    <input type="checkbox" name="custom_size" value="custom" class="wc-print-custom-size" id="wc-print-other-size"  <?php echo !empty($cart['custom_size'] && $cart['custom_size']=="custom" ) ?  'checked="checked"' : ''?> />
    <div class="selector-options__customgroup" >
        <span style="display: inline-block;width: 40%;">
            <input placeholder="Largeur" id="custom-options__width" style="display: inline-block;width: 100%;min-width:100%;" value="<?php echo !empty($cart['custom_width']) ?  $cart['custom_width'] : ''?>"  class="selector-options__input wc-print-custom-size"  name="custom_width" type="number" min="<?php echo $minWidth?>" max="<?php echo $maxWidth ?>" >
            <small>Min <?php echo $minWidth?> et max <?php echo $maxWidth ?> cm </small>
        </span>
        
        <span style="display: inline-block;width: 40%;">
            <input placeholder="Hauteur"    id="custom-options__height" style="display: inline-block;width: 100%;min-width:100%" value="<?php echo !empty($cart['custom_height']) ?  $cart['custom_height'] : ''?>" class="selector-options__input  wc-print-custom-size" name="custom_height" type="number" min="<?php echo $minHeight ?>" max="<?php echo $maxHeight ?>" >
             <small>Min <?php echo $minHeight?> et max <?php echo $maxHeight ?> cm</small>
        </span>
        
    </div>

