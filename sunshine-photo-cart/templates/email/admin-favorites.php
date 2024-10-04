<?php
if ( ! empty( $message ) ) {
	echo '<div id="custom-message">' . wpautop( $message ) . '</div>';
}
?>

<?php
if ( ! empty( $note ) ) {
	echo '<div id="custom-note">';
	echo '<p><strong>' . __( 'A custom note from the sender:', 'sunshine-photo-cart' ) . '</strong></p>';
	echo wpautop( $note );
	echo '</div>';
}
?>

<table id="photo-list">
	<tr>
		<?php
		$i = 0;
		foreach ( $favorites as $image ) {
			$i++;
			?>
			<td>
				<a href="<?php echo $image->get_permalink(); ?>"><img src="<?php echo $image->get_image_url(); ?>" alt="<?php echo esc_attr( $image->get_name() ); ?>" /></a>
				<span class="image-name"><?php echo $image->get_file_name(); ?></span>
			</td>
			<?php
			if ( $i == 6 ) {
				break; }
			?>
			<?php if ( $i % 3 == 0 ) { ?>
				</tr><tr>
			<?php } ?>
		<?php } ?>
	</tr>
</table>

<p align="center">
	<a href="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers&customer=' . SPC()->customer->ID ); ?>" class="button">
		<?php _e( 'View all favorites', 'sunshine-photo-cart' ); ?>
	</a>
</p>

<?php
foreach ( $favorites as $image ) {
	$file_names[] = $image->get_file_name();
}
?>
<p style="font-size: 12px; color: #666;">Image file names: <?php echo esc_html( join( ', ', $file_names ) ); ?></p>
