<?php
// Trying to make Sunshine compatible with other plugins in unique situations.

add_filter( 'jetpack_photon_skip_for_url', 'sunshine_photon_skip_for_url', 9, 4 );
function sunshine_photon_skip_for_url( $skip, $url, $args, $scheme ) {
	if ( str_contains( $url, 'uploads/sunshine' ) ) {
		SPC()->log( 'Skip URL for Jetpack photon for Sunshine image: ' . $file );
		return true;
	}
	return $skip;
}

add_filter( 'photon_validate_image_url', 'sunshine_photon_validate_image_url', 9, 3 );
function sunshine_photon_validate_image_url( $valid, $url, $parsed_url ) {
	if ( str_contains( $url, 'uploads/sunshine' ) ) {
		SPC()->log( 'Bypassing Jetpack photon image url validation for Sunshine image: ' . $file );
		return false;
	}
	return $valid;
}

add_filter( 'jetpack_photon_skip_image', 'sunshine_photon_skip_image', 9, 3 );
function sunshine_photon_skip_image( $valid, $url, $tag ) {
	if ( str_contains( $url, 'uploads/sunshine' ) ) {
		SPC()->log( 'Skip image for Jetpack photon for Sunshine image: ' . $file );
		return true;
	}
	return $valid;
}

// EWWW bypass optimizer if in a sunshine folder.
add_filter( 'ewww_image_optimizer_bypass', 'sunshine_ewww_image_optimizer_bypass', 10, 2 );
function sunshine_ewww_image_optimizer_bypass( $bypass, $file ) {
	if ( str_contains( $file, 'uploads/sunshine/' ) ) {
		SPC()->log( 'Bypassing EWWW optimizer for Sunshine image: ' . $file );
		return true;
	}
	return $bypass;
}

add_filter( 'ewww_image_optimizer_resize_dimensions', 'sunshine_ewww_image_optimizer_resize_dimensions', 10, 2 );
function sunshine_ewww_image_optimizer_resize_dimensions( $size, $file ) {
	if ( str_contains( $file, 'uploads/sunshine/' ) ) {
		SPC()->log( 'Bypassing EWWW optimizer resizing for Sunshine image: ' . $file );
		return array( 0, 0 );
	}
	return $size;
}

add_action( 'sunshine_after_image_process', 'sunshine_ewww_image_optimizer_editor_overwrite', 1 );
function sunshine_ewww_image_optimizer_editor_overwrite( $attachment_id ) {
	if ( ! defined( 'EWWWIO_EDITOR_OVERWRITE' ) ) {
		define( 'EWWWIO_EDITOR_OVERWRITE', true );
	}
}
