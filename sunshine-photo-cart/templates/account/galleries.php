<?php if ( empty( $galleries ) ) { ?>

	<p><?php _e( 'You have no galleries assigned to your user account', 'sunshine-photo-cart' ); ?></p>

<?php } else { ?>

	<?php sunshine_get_template( 'galleries/galleries', array( 'galleries' => $galleries ) ); ?>

<?php } ?>
