<span class="sunshine--checkout--discount-applied">
	<?php
	if ( $discount->is_auto() ) {
		echo $discount->get_name();
	} else {
		echo $discount->get_code();
	?>
	<button type="button" data-id="<?php echo esc_attr( $discount->get_code() ); ?>">×</button>
	<?php } ?>
</span>
