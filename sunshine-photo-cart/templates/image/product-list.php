<?php
// foreach category
foreach ( $categories as $category ) {
	$products = sunshine_get_products( $image->get_price_level(), $category->get_id(), sunshine_get_allowed_product_types_for_image() );
	if ( ! empty( $products ) ) {
		?>

		<div class="sunshine--image--add-to-cart--category" id="sunshine--image--add-to-cart--category-<?php echo esc_attr( $category->get_id() ); ?>" aria-selected="true">
			<div class="sunshine--image--add-to-cart--category-name"><?php echo esc_html( $category->get_name() ); ?></div>
			<?php if ( $category->get_description() ) { ?>
				<div class="sunshine--image--add-to-cart--category-description"><?php echo wp_kses_post( $category->get_description() ); ?></div>
			<?php } ?>
			<div class="sunshine--image--add-to-cart--product-list">
				<?php
				foreach ( $products as $product ) {
					sunshine_get_template(
						'image/product-item',
						array(
							'product' => $product,
							'image'   => $image,
						)
					);
				}
				?>
			</div>
		</div>

		<?php
	}
}
?>
