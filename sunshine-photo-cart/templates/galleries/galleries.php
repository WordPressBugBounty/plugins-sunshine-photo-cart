<?php
if ( empty( $galleries ) ) {
	$galleries = sunshine_get_galleries( array( 'post_parent' => 0 ), 'view' );
}

if ( ! empty( $galleries ) ) {
?>

	<div id="sunshine--gallery-items" class="sunshine--layout--<?php echo esc_attr( SPC()->get_option( 'gallery_layout', 'standard' ) ); ?> sunshine--col-<?php echo esc_attr( SPC()->get_option( 'columns' ) ); ?>">

	<?php
	foreach ( $galleries as $gallery ) {
		sunshine_get_template( 'galleries/gallery-item', array( 'gallery' => $gallery ) );
	}
	?>

	</div>

<?php
}
