<div id="sunshine--cart--empty"><?php esc_html_e( 'You do not have anything in your cart yet!', 'sunshine-photo-cart' ); ?></div>
<?php
$last_viewed_gallery = SPC()->session->get( 'last_gallery' );
if ( $last_viewed_gallery ) {
	$return_gallery = sunshine_get_gallery( SPC()->session->get( 'last_gallery' ) );
	echo '<div id="sunshine--cart--gallery-return"><a href="' . esc_url( $return_gallery->get_permalink() ) . '">' . sprintf( __( 'Return to %s', 'sunshine-photo-cart' ), $return_gallery->get_name() ) . '</a></div>';
}
?>
