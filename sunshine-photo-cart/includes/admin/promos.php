<?php
add_filter( 'sunshine_admin_meta_sunshine-product', 'sunshine_product_promos', 10 );
function sunshine_product_promos( $options ) {

	if ( SPC()->get_option( 'promos_hide' ) && SPC()->has_plan() ) {
		return $options;
	}

	$options['sunshine-product-options'][100] = array(
		'id'     => 'product-options',
		'name'   => __( 'Options', 'sunshine-photo-cart' ),
		'fields' => array(
			array(
				'id'            => 'options_promo',
				'type'          => 'promo',
				'url' => 'https://www.sunshinephotocart.com/addon/product-options/',
				'description'   => sunshine_get_template_html( 'admin/promo/product-options' ),
			),
		),
	);

	$options['sunshine-product-options'][450] = array(
		'id'     => 'download',
		'name'   => __( 'Download', 'sunshine-photo-cart' ),
		'fields' => array(
			array(
				'id'            => 'download_promo',
				'type'          => 'promo',
				'url' => 'https://www.sunshinephotocart.com/addon/digital-downloads/',
				'description'   => sunshine_get_template_html( 'admin/promo/digital-downloads' ),
			),
		),
	);

	$options['sunshine-product-options'][300] = array(
		'id'     => 'multi-image',
		'name'   => __( 'Multi-Image', 'sunshine-photo-cart' ),
		'fields' => array(
			array(
				'id'            => 'multi_image_promo',
				'type'          => 'promo',
				'url' => 'https://www.sunshinephotocart.com/addon/multi-image-products/',
				'description'   => sunshine_get_template_html( 'admin/promo/multi-image-products' ),
			),
		),
	);

	$options['sunshine-product-options'][500] = array(
		'id'     => 'package',
		'name'   => __( 'Package', 'sunshine-photo-cart' ),
		'fields' => array(
			array(
				'id'            => 'package_promo',
				'type'          => 'promo',
				'url' => 'https://www.sunshinephotocart.com/addon/packages/',
				'description'   => sunshine_get_template_html( 'admin/promo/packages' ),
			),
		),
	);


	return $options;

}

add_filter( 'sunshine_admin_meta_sunshine-gallery', 'sunshine_gallery_promos', 10 );
function sunshine_gallery_promos( $options ) {

	if ( SPC()->get_option( 'promos_hide' ) && SPC()->has_plan() ) {
		return $options;
	}

	if ( ! is_sunshine_addon_active( 'bulk-galleries' ) ) {
		$options['sunshine-gallery-options'][0]['fields'][1000] = array(
			'id'   => 'volume_galleries_promo',
			'description'   => sunshine_get_template_html( 'admin/promo/volume-galleries' ),
			'url' => 'https://www.sunshinephotocart.com/addon/bulk-galleries/',
			'type' => 'promo',
		);
	}

	if ( ! is_sunshine_addon_active( 'price-levels' ) ) {
		$options['sunshine-gallery-options'][2000]['fields'][] = array(
			'id'   => 'price_levels_promo',
			'description'   => sunshine_get_template_html( 'admin/promo/price-levels' ),
			'url' => 'https://www.sunshinephotocart.com/addon/price-levels/',
			'type' => 'promo',
		);
	}

	return $options;

}
