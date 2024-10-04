<?php do_action( 'sunshine_before_product', $product, $image ); ?>

<div id="sunshine--product-<?php echo esc_attr( $product->get_id() ); ?>" class="sunshine--image--add-to-cart--product-item">
	<div class="sunshine--image--add-to-cart--product-list--name">
		<?php $product->get_image_html(); ?>
		<?php echo $product->get_name(); ?>
	</div>
	<div class="sunshine--image--add-to-cart--product-list--price">
		<?php echo $product->get_price_formatted(); ?>
	</div>
	<div class="sunshine--image--add-to-cart--product-list--action">
		<button class="sunshine--product--show-details" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-image-id="<?php echo esc_attr( $image->get_id() ); ?>"><span><?php esc_html_e( 'See options', 'sunshine-photo-cart' ); echo ': ' . $product->get_name(); ?></span></button>
	</div>
</div>

<?php do_action( 'sunshine_after_product', $product, $image ); ?>
