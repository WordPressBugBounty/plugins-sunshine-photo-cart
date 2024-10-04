<div class="sunshine--cart-item--product-option">
	<?php
	if ( ! empty( $option['value'] ) ) {
        echo $option['name'] . ': ' . $option['value'];
	} elseif ( ! empty( $option['name'] ) ) {
        echo $option['name'];
	}
	?>
</div>
