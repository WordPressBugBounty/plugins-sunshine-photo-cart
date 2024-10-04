<?php
// Trying to make Sunshine compatible with other plugins in unique situations.

add_filter( 'jetpack_photon_skip_for_url', 'sunshine_photon_skip_for_url', 9, 4 );
function sunshine_photon_skip_for_url( $skip, $url, $args, $scheme ) {
	if ( str_contains( $url, 'uploads/sunshine' ) ) {
		return true;
	}
	return $skip;
}

add_filter( 'photon_validate_image_url', 'sunshine_photon_validate_image_url', 9, 3 );
function sunshine_photon_validate_image_url( $valid, $url, $parsed_url ) {
	if ( str_contains( $url, 'uploads/sunshine' ) ) {
		return false;
	}
	return $valid;
}

add_filter( 'jetpack_photon_skip_image', 'sunshine_photon_skip_image', 9, 3 );
function sunshine_photon_skip_image( $valid, $url, $tag ) {
	if ( str_contains( $url, 'uploads/sunshine' ) ) {
		return true;
	}
	return $valid;
}
