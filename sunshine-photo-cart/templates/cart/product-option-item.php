<div class="sunshine--cart-item--product-option">
	<?php
	if ( $option->get_type() == 'checkbox' ) {
		echo $option->get_name();
	} else {
		echo $option->get_name() . ': ' . $option->get_item_name( $option_item_id );
	}
	?>
</div>
