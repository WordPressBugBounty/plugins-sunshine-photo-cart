<?php
$product_image_qty = SPC()->cart->get_product_with_image_count( $product->get_id(), $image->get_id() );
$product_qty = SPC()->cart->get_product_count( $product->get_id() );
$max_qty = $product->get_max_qty();
$can_purchase = $product->can_purchase();
?>
<div id="sunshine--product--details" class="<?php $product->classes(); ?>" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-image-id="<?php echo esc_attr( $image->get_id() ); ?>">

	<button id="sunshine--product--details--close"><?php _e( 'Return to products', 'sunshine-photo-cart' ); ?></button>

	<div id="sunshine--product--details--title"><?php echo $product->get_name(); ?></div>
	<?php if ( $product->has_image() ) { ?>
		<div id="sunshine--product--details--image"><?php echo $product->get_image_html( 'large' ); ?></div>
	<?php } ?>

	<?php if ( $product->get_description() ) { ?>
		<div clidass="sunshine--product--details--description"><?php echo $product->get_description(); ?></div>
	<?php } ?>

	<?php do_action( 'sunshine_product_details_before_price', $product, $image ); ?>

	<div id="sunshine--product--details--price">
		<?php echo $product->get_price_formatted(); ?>
	</div>

	<?php do_action( 'sunshine_product_details_after_price', $product, $image ); ?>

	<?php if ( $can_purchase && ( ! $max_qty || $max_qty > 1 ) ) { ?>
	<div id="sunshine--product--details--qty">
		<button class="sunshine--qty--down"><span><?php esc_html_e( 'Decrease quantity', 'sunshine-photo-cart' ); ?></span></button>
		<input type="text" name="qty" class="sunshine--qty" min="1" <?php echo ( $max_qty ) ? 'max="' . esc_attr( $max_qty ) . '"' : ''; ?>  pattern="[0-9]+" value="1" aria-label="<?php esc_attr_e( 'Qty', 'sunshine-photo-cart' ); ?>" />
		<button class="sunshine--qty--up"><span><?php esc_html_e( 'Increase quantity', 'sunshine-photo-cart' ); ?></span></button>
	</div>
	<?php } ?>

	<?php if ( SPC()->get_option( 'product_comments' ) ) { ?>
	<div id="sunshine--product--details--comments">
		<input type="text" name="comments" placeholder="<?php echo esc_attr( __( 'Add comments for this item', 'sunshine-photo-cart' ) ); ?>" />
	</div>
	<?php } ?>

	<?php if ( $can_purchase ) { ?>
		<div id="sunshine--product--details--action">
			<button class="sunshine--button" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>" data-gallery-id="<?php echo esc_attr( $image->gallery->get_id() ); ?>" data-image-id="<?php echo esc_attr( $image->get_id() ); ?>"><?php _e( 'Add to cart', 'sunshine-photo-cart' ); ?></button>
		</div>
	<?php } ?>

	<?php if ( $can_purchase && $max_qty ) { ?>
		<div id="sunshine--product--details--cart-qty"><?php echo sprintf( __( 'You can only add (%s) %s to cart', 'sunshine-photo-cart' ), $product->get_max_qty(), $product->get_name() ); ?></div>
	<?php } ?>

</div>
