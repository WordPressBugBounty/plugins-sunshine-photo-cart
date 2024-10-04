<?php
if ( ! empty( $message ) ) {
	echo '<div id="custom-message">' . wpautop( $message ) . '</div>';
}
?>

<p><?php _e( 'Please click the button below to set a new password. If you did not make this request, you can safely ignore and delete this email', 'sunshine-photo-cart' ); ?></p>
<p><a href="<?php echo esc_url( $reset_password_url ); ?>" class="button"><?php _e( 'Click to reset password', 'sunshine-photo-cart' ); ?></a></p>
