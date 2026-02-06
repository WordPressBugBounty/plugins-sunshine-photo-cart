<div id="sunshine--order--details">
	<div id="sunshine--order--data">
		<dl>
			<dt><?php esc_html_e( 'Date', 'sunshine-photo-cart' ); ?></dt>
			<dd><?php echo esc_html( $order->get_date() ); ?></d>
			<dt><?php esc_html_e( 'Payment Method', 'sunshine-photo-cart' ); ?></dt>
			<dd><?php echo esc_html( $order->get_payment_method_name() ); ?></dd>
			<?php if ( $order->get_delivery_method_name() ) { ?>
				<dt><?php esc_html_e( 'Delivery', 'sunshine-photo-cart' ); ?></dt>
				<dd>
					<?php if ( $order->get_shipping_method_name() ) { ?>
						<?php echo esc_html( $order->get_shipping_method_name() ); ?>
					<?php } else { ?>
						<?php echo esc_html( $order->get_delivery_method_name() ); ?>
					<?php } ?>
				</dd>
			<?php } ?>
			<?php if ( $order->get_vat() ) { ?>
				<dt><?php echo ( SPC()->get_option( 'vat_label' ) ) ? esc_html( SPC()->get_option( 'vat_label' ) ) : esc_html__( 'EU VAT Number', 'sunshine-photo-cart' ); ?></dt>
				<dd><?php echo esc_html( $order->get_vat() ); ?></d>
			<?php } ?>
		</dl>
	</div>
	<?php if ( $order->has_shipping_address() ) { ?>
	<div id="sunshine--order--shipping">
		<h3><?php esc_html_e( 'Shipping', 'sunshine-photo-cart' ); ?></h3>
			<address><?php echo wp_kses_post( $order->get_shipping_address_formatted() ); ?></address>
	</div>
	<?php } ?>
	<?php if ( $order->has_billing_address() ) { ?>
	<div id="sunshine--order--billing">
		<h3><?php esc_html_e( 'Billing', 'sunshine-photo-cart' ); ?></h3>
		<address><?php echo wp_kses_post( $order->get_billing_address_formatted() ); ?></address>
	</div>
	<?php } ?>

	<?php if ( $order->get_customer_notes() ) { ?>
		<div id="sunshine--order--notes">
			<h3><?php esc_html_e( 'Notes', 'sunshine-photo-cart' ); ?></h3>
			<?php echo wp_kses_post( $order->get_customer_notes() ); ?>
		</div>
	<?php } ?>

</div>
