<?php
// TODO: This whole file needs security nonce checks!

add_action( 'sunshine_modal_display_add_to_cart', 'sunshine_modal_display_add_to_cart' );
function sunshine_modal_display_add_to_cart() {

	if ( empty( $_POST['imageId'] ) ) {
		wp_send_json_error( __( 'No image ID provided', 'sunshine-photo-cart' ) );
	}

	$image = sunshine_get_image( intval( $_POST['imageId'] ) );
	if ( empty( $image->get_id() ) ) {
		wp_send_json_error( __( 'Not a valid image ID', 'sunshine-photo-cart' ) );
	}

	$result = array( 'html' => sunshine_get_template_html( 'image/add-to-cart', array( 'image' => $image ) ) );
	wp_send_json_success( $result );

}

add_action( 'wp_ajax_nopriv_sunshine_product_details', 'sunshine_modal_product_details' );
add_action( 'wp_ajax_sunshine_product_details', 'sunshine_modal_product_details' );
function sunshine_modal_product_details() {

	if ( empty( $_POST['image_id'] ) || empty( $_POST['product_id'] ) ) {
		SPC()->log( 'Show product details failed' );
		wp_send_json_error( __( 'Show product options failed', 'sunshine-photo-cart' ) );
	}

	$image = sunshine_get_image( intval( $_POST['image_id'] ) );
	if ( empty( $image ) ) {
		SPC()->log( 'Show product details failed: No image found: ' . intval( $_POST['image_id'] ) );
		wp_send_json_error( __( 'Show product details failed - no image found', 'sunshine-photo-cart' ) );
	}

	$product = sunshine_get_product( intval( $_POST['product_id'] ), $image->get_price_level() );
	if ( empty( $product ) ) {
		SPC()->log( 'Show product details failed: No product found: ' . intval( $_POST['product_id'] ) );
		wp_send_json_error( __( 'Show product details failed - no product found', 'sunshine-photo-cart' ) );
	}

	//$options = $product->get_options( $image->get_price_level() );

	$result = array(
		'html' => sunshine_get_template_html(
			'image/product-details',
			array(
				'product' => $product,
				//'options' => $options,
				'image'   => $image,
			)
		),
	);

	$result = apply_filters( 'sunshine_product_details', $result, $product, $image );

	wp_send_json_success( $result );

}

add_action( 'wp_ajax_nopriv_sunshine_modal_add_item_to_cart', 'sunshine_modal_add_item_to_cart' );
add_action( 'wp_ajax_sunshine_modal_add_item_to_cart', 'sunshine_modal_add_item_to_cart' );
function sunshine_modal_add_item_to_cart() {

	if ( empty( $_POST['product_id'] ) ) {
		SPC()->log( 'Add to cart - No product provided' );
		wp_send_json_error( __( 'No product provided', 'sunshine-photo-cart' ) );
	}

	if ( ! isset( $_POST['qty'] ) ) {
		$qty = 1;
	} else {
		$qty = intval( $_POST['qty'] );
	}

	if ( ! isset( $_POST['image_id'] ) ) {
		$image_id = 0;
	} else {
		$image_id = intval( $_POST['image_id'] );
	}

	if ( ! isset( $_POST['gallery_id'] ) ) {
		$gallery_id = '';
	} else {
		$gallery_id = intval( $_POST['gallery_id'] );
	}

	if ( empty( $_POST['options'] ) ) {
		$options = array();
	} else {
		$options = array_map( 'sanitize_text_field', $_POST['options'] );
	}

	$image = $product = $gallery = $price_level = '';

	if ( ! empty( $gallery_id ) ) {
		$gallery = sunshine_get_gallery( $gallery_id );
		$price_level = $gallery->get_price_level();
	}

	if ( ! empty( $image_id ) ) {
		$image       = sunshine_get_image( $image_id );
		$price_level = $image->get_price_level();
	}

	$product_id = intval( $_POST['product_id'] );
	$product = sunshine_get_product( $product_id, $price_level );

	$comments = ( ! empty( $_POST['comments'] ) ) ? sanitize_textarea_field( $_POST['comments'] ) : '';

	// Add each image individually for multi-image products.
	if ( $product->get_type() !== 'multi-image' && ! empty( $options['images'] ) ) {
		$image_ids = explode( ',', $options['images'] );
		unset( $options['images'] ); // Don't need to pass this as an option.
		foreach ( $image_ids as $image_id ) {
			$add_to_cart_result = SPC()->cart->add_item( $product_id, $image_id, $gallery_id, $price_level, ( ! empty( $options ) ) ? $options : '', intval( $qty ), $comments );
		}
	} else {
		if ( ! empty( $options['images'] ) ) {
			$image_ids         = explode( ',', $options['images'] );
			$options['images'] = $image_ids;
		}
		$add_to_cart_result = SPC()->cart->add_item( $product_id, $image_id, $gallery_id, $price_level, ( ! empty( $options ) ) ? $options : '', intval( $qty ), $comments );
	}

	//sunshine_log( 'subtotal: ' . SPC()->cart->get_subtotal() );

	// Add item to cart.
	if ( ! empty( $add_to_cart_result ) ) {
		$result = array(
			'item'            => $add_to_cart_result,
			'count'           => SPC()->cart->get_item_count(),
			'total_formatted' => SPC()->cart->get_total_formatted(),
			'mini_cart'       => sunshine_get_template_html( 'cart/mini-cart' ),
			'type'            => $product->get_type(),
		);
		wp_send_json_success( $result );
	} else {
		SPC()->log( 'Item not added to cart' );
		wp_send_json_error( __( 'Item not added to cart', 'sunshine-photo-cart' ) );
	}

}

// Add to cart from URL.
add_action( 'wp', 'sunshine_add_to_cart_url' );
function sunshine_add_to_cart_url() {
	if ( isset( $_GET['sunshine_action'] ) ) {
		$action = sanitize_text_field( $_GET['sunshine_action'] );
		if ( 'add_to_cart' == $action ) {

			$qty = 1;
			if ( ! empty( $_GET['qty'] ) ) {
				$qty = intval( $_GET['qty'] );
				if ( $qty < 1 ) {
					$qty = 1;
				}
			}

			$product_id = '';
			if ( ! empty( $_GET['product_id'] ) ) {
				$product_id = intval( $_GET['product_id'] );
				$product = sunshine_get_product( $product_id );
			}

			$image_id = '';
			$price_level = '';
			$gallery_id = '';
			if ( ! empty( $_GET['image_id'] ) ) {
				$image_id = intval( $_GET['image_id'] );
				$image = sunshine_get_image( $image_id );
				$price_level = $image->get_price_level();
				$gallery_id = $image->get_gallery_id();
			}

			if ( ! empty( $product_id ) ) {
				$add_to_cart_result = SPC()->cart->add_item( $product_id, $image_id, $gallery_id, $price_level, '', $qty );
				if ( $add_to_cart_result ) {
					SPC()->notices->add( sprintf( __( '%s added to cart', 'sunshine-photo-cart' ), $product->get_name() ) );
				}
			}

		}

		$url = remove_query_arg( array_keys( $_GET ) );
		wp_safe_redirect( $url );
		exit;

	}
}
