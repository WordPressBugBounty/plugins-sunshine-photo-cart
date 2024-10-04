<p>
	<?php
	printf(
		/* translators: 1: user display name 2: logout url */
		__( 'Hello %1$s (not %1$s? <a href="%2$s">Log out</a>)', 'sunshine-photo-cart' ),
		'<strong>' . esc_html( SPC()->customer->get_name() ) . '</strong>',
		esc_url( wp_logout_url() )
	);
	?>
</p>

<p>
	<?php
	$dashboard_desc = __( 'From your account dashboard you can view your <a href="%1$s">recent orders</a>, manage your <a href="%2$s">shipping address</a>, and <a href="%3$s">edit your password and account details</a>.', 'sunshine-photo-cart' );
	printf(
		$dashboard_desc,
		esc_url( sunshine_get_account_endpoint_url( 'orders' ) ),
		esc_url( sunshine_get_account_endpoint_url( 'addresses' ) ),
		esc_url( sunshine_get_account_endpoint_url( 'profile' ) )
	);
	?>
</p>
