<?php
if ( ! empty( $message ) ) {
	echo '<div id="custom-message">' . wpautop( $message ) . '</div>';
}
?>
<p><?php _e( 'A new customer has signed up in Sunshine Photo Cart', 'sunshine-photo-cart' ); ?>: <?php echo $customer->get_email(); ?></p>
<p>
	<a href="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers&customer=' . $customer->get_id() ); ?>" class="button">
		<?php _e( 'View Customer', 'sunshine-photo-cart' ); ?>
	</a>
</p>
