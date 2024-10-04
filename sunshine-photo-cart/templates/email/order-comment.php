<h2><?php echo sprintf( 'A comment on %s ', $order->get_name() ); ?></h2>

<?php
if ( ! empty( $message ) ) {
	echo '<div id="custom-message">' . wpautop( wp_kses_post( $message ) ) . '</div>';
}
?>

<?php
if ( ! empty( $comment ) ) {
	echo '<div id="order-comment">' . wpautop( wp_kses_post( $comment ) ) . '</div>';
}
?>

<div id="order-actions">
	<a href="<?php echo $order->get_permalink(); ?>" class="button"><?php _e( 'View order', 'sunshine-photo-cart' ); ?></a> <?php _e( 'or reply to this email', 'sunshine-photo-cart' ); ?></a>
</div>
