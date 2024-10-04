<div class="sunshine--mini-cart" aria-live="polite">
<?php
if ( ! SPC()->cart->is_empty() ) {
	?>
	<a href="<?php echo sunshine_get_page_permalink( 'cart' ); ?>" aria-label="<?php esc_html_e( 'View cart', 'sunshine-photo-cart' ); ?>"><span class="sunshine--mini-cart--quantity"><?php echo sprintf( _n( '%s item', '%s items', SPC()->cart->get_item_count(), 'sunshine-photo-cart' ), '<span class="sunshine--mini-cart--quantity--count">' . SPC()->cart->get_item_count() . '</span>' ); ?></span> <span class="sunshine--mini-cart--separator">&mdash;</span> <span class="sunshine--mini-cart--total"><?php echo SPC()->cart->get_subtotal_formatted(); ?></span></a>
	<?php
} else {
	echo '<div class="sunshine--mini-cart--empty">' . __( 'Your cart is empty', 'sunshine-photo-cart' ) . '</div>';
}
?>
</div>
