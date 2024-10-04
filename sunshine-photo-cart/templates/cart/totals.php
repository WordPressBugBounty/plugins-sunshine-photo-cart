<table id="sunshine--cart--totals--items">
	<tr class="sunshine--cart--subtotal">
		<th><?php _e( 'Subtotal', 'sunshine-photo-cart' ); ?></th>
		<td><?php echo SPC()->cart->get_subtotal_formatted(); ?></td>
	</tr>
	<?php if ( ! empty( SPC()->cart->get_shipping_method() ) ) { ?>
	<tr class="sunshine--cart--shipping">
		<th><?php _e( 'Shipping', 'sunshine-photo-cart' ); ?></th>
		<td><?php echo SPC()->cart->get_shipping_formatted(); ?></td>
	</tr>
	<?php } ?>
	<?php if ( SPC()->cart->get_tax() && SPC()->get_option( 'display_price' ) != 'with_tax' ) { ?>
	<tr class="sunshine--cart--tax">
		<th><?php _e( 'Tax', 'sunshine-photo-cart' ); ?></th>
		<td><?php echo SPC()->cart->get_tax_formatted(); ?></td>
	</tr>
	<?php } ?>
	<?php if ( SPC()->cart->get_discount() > 0 ) { ?>
	<tr class="sunshine--cart--discount">
		<th>
			<?php _e( 'Discounts', 'sunshine-photo-cart' ); ?>
			<?php
			$discount_names = SPC()->cart->get_discount_names();
			if ( ! empty( $discount_names ) ) {
				echo '<div class="sunshine--cart--discount--names">' . join( '<br />', $discount_names ) . '</div>';
			}
			?>
		</th>
		<td><?php echo SPC()->cart->get_discount_formatted(); ?></td>
	</tr>
	<?php } ?>
	<?php if ( SPC()->cart->get_fees() ) { ?>
		<?php foreach ( SPC()->cart->get_fees() as $fee ) { ?>
			<tr class="sunshine--cart--fee">
				<th><?php esc_html_e( $fee['name'] ); ?></th>
				<td><?php echo sunshine_price( $fee['amount'] ); ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
	<tr class="sunshine--cart--total">
		<th><?php _e( 'Order Total', 'sunshine-photo-cart' ); ?></th>
		<td>
			<?php echo SPC()->cart->get_total_formatted(); ?>
			<?php if ( SPC()->cart->get_total() > 0 && SPC()->cart->get_tax() && SPC()->get_option( 'display_price' ) == 'with_tax' ) { ?>
				<span class="sunshine--cart--total--tax--explain">(<?php echo sprintf( __( 'includes %s tax', 'sunshine-photo-cart' ), SPC()->cart->get_tax_formatted() ); ?>)</span>
			<?php } ?>
		</td>
	</tr>
</table>
