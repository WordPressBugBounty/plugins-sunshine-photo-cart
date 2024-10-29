<?php

function sunshine_get_gallery( $gallery ) {
	return new SPC_Gallery( $gallery );
}

// conditional_method = access/view
// access = has permission (hides private, password, expired)
// view = can know about it's existence but not yet see (hides private, expired)
function sunshine_get_galleries( $custom_args = array(), $conditional_method = 'access' ) {

	if ( SPC()->get_option( 'gallery_order' ) == 'date_new_old' ) {
		$order   = 'date';
		$orderby = 'DESC';
	} elseif ( SPC()->get_option( 'gallery_order' ) == 'date_old_new' ) {
		$order   = 'date';
		$orderby = 'ASC';
	} elseif ( SPC()->get_option( 'gallery_order' ) == 'title' ) {
		$order   = 'title';
		$orderby = 'ASC';
	} else {
		$order   = 'menu_order';
		$orderby = 'ASC';
	}
	$args = array(
		'post_type'      => 'sunshine-gallery',
		// 'post_parent' => 0,
		'orderby'        => $order,
		'order'          => $orderby,
		'posts_per_page' => -1,
		// 'update_post_meta_cache' => false,
		'post_status'    => array( 'publish' ),
	);

	$args = wp_parse_args( $custom_args, $args );
	$args = apply_filters( 'sunshine_get_galleries_args', $args );

	$galleries = new WP_Query( $args );

	if ( $galleries->have_posts() ) {
		$final_galleries = array();
		foreach ( $galleries->posts as $gallery ) {
			$gallery = sunshine_get_gallery( $gallery );
			if ( $conditional_method === 'all' ) {
				$final_galleries[ $gallery->get_id() ] = $gallery;
			} elseif ( $conditional_method === 'view' && $gallery->can_view() ) {
				if ( $gallery->get_access_type() == 'url' && ! current_user_can( 'sunshine_manage_options' ) ) {
					continue;
				}
				$final_galleries[ $gallery->get_id() ] = $gallery;
			} elseif ( $conditional_method === 'access' && $gallery->can_access() ) {
				if ( $gallery->get_access_type() == 'url' && ! current_user_can( 'sunshine_manage_options' ) ) {
					continue;
				}
				$final_galleries[ $gallery->get_id() ] = $gallery;
			}
		}
		return $final_galleries;
	}

	return false;

}

function sunshine_get_gallery_descendants( $gallery_id ) {
	$children  = array();
	$galleries = get_posts(
		array(
			'numberposts'      => -1,
			'post_status'      => 'publish',
			'post_type'        => 'sunshine-gallery',
			'post_parent'      => $gallery_id,
			'suppress_filters' => false,
		)
	);
	// now grab the grand children.
	foreach ( $galleries as $child ) {
		$gchildren = sunshine_get_gallery_descendants( $child->ID );
		if ( ! empty( $gchildren ) ) {
			$children = array_merge( $children, $gchildren );
		}
	}
	$children = array_merge( $children, $galleries );
	return $children;
}

function sunshine_get_gallery_descendant_ids( $gallery_id ) {
	$galleries = sunshine_get_gallery_descendants( $gallery_id );
	$ids       = array();
	foreach ( $galleries as $gallery ) {
		$ids[] = $gallery->ID;
	}
	return $ids;
}

function sunshine_get_image_dimensions( $size = 'thumbnail' ) {
	return SPC()->get_option( $size . '_size' );
}
function sunshine_get_thumbnail_dimension( $side = 'w' ) {
	$dimensions = sunshine_get_image_dimensions( 'thumbnail' );
	if ( ! empty( $dimensions ) && ! empty( $dimensions[ $side ] ) ) {
		return $dimensions[ $side ];
	}
	if ( $side == 'w' ) {
		return 400;
	} elseif ( $side == 'h' ) {
		return 300;
	}
	return false;
}

function sunshine_get_large_dimension( $side = 'w' ) {
	$dimensions = sunshine_get_image_dimensions( 'large' );
	if ( ! empty( $dimensions ) && ! empty( $dimensions[ $side ] ) ) {
		return $dimensions[ $side ];
	}
	if ( $side == 'w' ) {
		return 400;
	} elseif ( $side == 'h' ) {
		return 300;
	}
	return false;
}
