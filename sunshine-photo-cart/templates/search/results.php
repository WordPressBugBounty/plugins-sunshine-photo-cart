<header id="sunshine--page-header">
	<h1><?php echo sprintf( __( 'You searched for "%s"', 'sunshine-photo-cart' ), SPC()->frontend->search_term ); ?></h1>
	<?php sunshine_action_menu(); ?>
</header>

<?php
sunshine_search_form();
if ( ! empty( $images ) ) {
	sunshine_get_template( 'gallery/images', array( 'images' => $images ) );
} else {
	sunshine_get_template( 'search/no-images' );
}
?>
