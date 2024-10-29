<div id="sunshine--image--cart-review">
	<!--<a href="#" class="sunshine--modal--close"><?php _e( 'Return to gallery', 'sunshine-photo-cart' ); ?></a>-->
	<?php sunshine_get_template( 'cart/mini-cart' ); ?>
</div>
<div id="sunshine--image--add-to-cart">
	<div id="sunshine--image--add-to-cart--header">
		<div id="sunshine--image--add-to-cart--header--image">
			<?php $image->output(); ?>
			<span><?php echo $image->get_name(); ?></span>
		</div>
	</div>
	<div id="sunshine--image--add-to-cart--content">

		<div id="sunshine--image--add-to-cart--nav" class="sunshine--modal--tablist--nav" role="tablist">
			<?php
			$categories = sunshine_get_product_categories( $image->get_price_level(), sunshine_get_allowed_product_types_for_image() );
			if ( ! empty( $categories ) && count( $categories ) > 1 ) {
				?>
				<ul id="sunshine--image--add-to-cart--categories">
					<?php
					$i = 0;
					foreach ( $categories as $category ) {
						$i++;
						?>
						<li aria-controls="sunshine--image--add-to-cart--category-<?php echo $category->get_id(); ?>" role="tab" data-id="<?php echo $category->get_id(); ?>"><?php echo $category->get_name(); ?></li>
					<?php } ?>
				</ul>
			<?php } ?>
			<?php if ( SPC()->store_enabled() ) { ?>
				<a href="<?php echo $image->gallery->get_store_url(); ?>" id="sunshine--image--add-to-cart--store" class="sunshine--store-open"><?php _e( 'Store', 'sunshine-photo-cart' ); ?></a>
			<?php } ?>
		</div>

		<div id="sunshine--image--add-to-cart--products">

			<?php do_action( 'sunshine_before_product_list', $image ); ?>

			<?php
			if ( ! empty( $categories ) ) {
				sunshine_get_template(
					'image/product-list',
					array(
						'image'      => $image,
						'categories' => $categories,
					)
				);
			}
			?>

			<?php do_action( 'sunshine_after_product_list', $image ); ?>

		</div>

	</div>

</div>
