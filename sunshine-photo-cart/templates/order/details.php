<div id="sunshine--order--details">
	<div id="sunshine--order--data">
		<dl>
			<dt><?php _e( 'Date', 'sunshine-photo-cart' ); ?></dt>
			<dd><?php echo $order->get_date(); ?></d>
			<dt><?php _e( 'Payment Method', 'sunshine-photo-cart' ); ?></dt>
			<dd><?php echo $order->get_payment_method_name(); ?></dd>
			<?php if ( $order->get_delivery_method_name() ) { ?>
				<dt><?php _e( 'Delivery', 'sunshine-photo-cart' ); ?></dt>
				<dd>
					<?php echo $order->get_delivery_method_name(); ?>
					<?php if ( $order->get_shipping_method() ) { ?>
						(<?php echo $order->get_shipping_method_name(); ?>)
					<?php } ?>
				</dd>
			<?php } ?>
			<?php if ( $order->get_vat() ) { ?>
				<dt><?php echo ( SPC()->get_option( 'vat_label' ) ) ? SPC()->get_option( 'vat_label' ) : __( 'EU VAT Number', 'sunshine-photo-cart' ); ?></dt>
				<dd><?php echo $order->get_vat(); ?></d>
			<?php } ?>
		</dl>
	</div>
	<?php if ( $order->has_shipping_address() ) { ?>
	<div id="sunshine--order--shipping">
		<h3><?php _e( 'Shipping', 'sunshine-photo-cart' ); ?></h3>
			<address><?php echo $order->get_shipping_address_formatted(); ?></address>
	</div>
	<?php } ?>
	<?php if ( $order->has_billing_address() ) { ?>
	<div id="sunshine--order--billing">
		<h3><?php _e( 'Billing', 'sunshine-photo-cart' ); ?></h3>
		<address><?php echo $order->get_billing_address_formatted(); ?></address>
	</div>
	<?php } ?>

	<?php if ( $order->get_customer_notes() ) { ?>
		<div id="sunshine--order--notes">
			<h3><?php _e( 'Notes', 'sunshine-photo-cart' ); ?></h3>
			<?php echo wp_kses_post( $order->get_customer_notes() ); ?>
		</div>
	<?php } ?>

</div>
