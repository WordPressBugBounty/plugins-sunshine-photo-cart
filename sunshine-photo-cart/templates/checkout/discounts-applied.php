<?php if ( ! empty( $discounts_applied ) ) {
	foreach ( $discounts_applied as $discount ) {
		sunshine_get_template( 'checkout/discount-applied', array( 'discount' => $discount ) );
	}
} ?>
