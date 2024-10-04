<?php
function sunshine_get_image_file_name( $image_id ) {
	return get_post_meta( $image_id, 'sunshine_file_name', true );
}

function sunshine_get_image( $image ) {
	return new SPC_Image( $image );
}

function sunshine_get_images( $args = array() ) {
	global $wpdb;

	$final_images = array();
	$args = wp_parse_args( $args, array(
		'post_type' => 'attachment',
		//'post_status' => 'any',
		'meta_key' => 'sunshine_file_name',
		'nopaging' => 1,
	));
	$images = get_posts( $args );
	//$query = new WP_Query( $args );
	//sunshine_log( $query->request );
	if ( ! empty( $images ) ) {
		foreach ( $images as $image ) {
			$final_images[ $image->ID ] = sunshine_get_image( $image->ID );
		}
	}

	if ( ! empty( $args['s'] ) ) {

		$post_parent = ( ! empty( $args['post_parent__in'] ) ) ? "AND {$wpdb->prefix}posts.post_parent IN (" . join( ',', $args['post_parent__in'] ) . ")" : "";

		$query = "
			SELECT {$wpdb->prefix}posts.*
			FROM {$wpdb->prefix}posts
			INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id )
			INNER JOIN {$wpdb->prefix}postmeta AS sunshine_meta ON ( {$wpdb->prefix}posts.ID = sunshine_meta.post_id AND sunshine_meta.meta_key = 'sunshine_file_name' )
			WHERE 1=1
			{$post_parent}
			AND {$wpdb->prefix}posts.post_type = 'attachment'
			AND (
				({$wpdb->prefix}posts.post_title LIKE %s)
				OR ({$wpdb->prefix}postmeta.meta_value LIKE %s)
				OR ({$wpdb->prefix}posts.post_excerpt LIKE %s)
				OR ({$wpdb->prefix}posts.post_content LIKE %s)
			)
			OR (
				({$wpdb->prefix}postmeta.meta_key = 'sunshine_file_name' AND {$wpdb->prefix}postmeta.meta_value LIKE %s)
				OR
				({$wpdb->prefix}postmeta.meta_key = '_wp_attachment_metadata' AND {$wpdb->prefix}postmeta.meta_value LIKE %s)
			)
			AND (
				{$wpdb->prefix}posts.post_type = 'attachment'
				AND ({$wpdb->prefix}posts.post_status = 'publish' OR {$wpdb->prefix}posts.post_status = 'private')
			)
			AND {$wpdb->prefix}posts.ID > 0
			GROUP BY {$wpdb->prefix}posts.ID
			ORDER BY {$wpdb->prefix}posts.post_title LIKE %s DESC, {$wpdb->prefix}posts.post_date DESC
		";

		// Preparing the SQL statement
		$query = $wpdb->prepare(
			$query,
			"%{$args['s']}%",
			"%{$args['s']}%",
			"%{$args['s']}%",
			"%{$args['s']}%",
			"%{$args['s']}%",
			"%\\\"{$args['s']}\\\"%",
			"%{$args['s']}%"
		);

		// Running the query
		$results = $wpdb->get_results( $query );
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$final_images[ $result->ID ] = sunshine_get_image( $result->ID );
			}
		}

	}

	if ( ! empty( $final_images ) ) {
		foreach ( $final_images as $key => $image ) {
			if ( empty( $image->gallery ) || ! $image->gallery->can_access() ) {
				unset( $final_images[ $key ] );
			}
		}
		return $final_images;
	}

	return false;

}

add_filter( 'posts_where', 'sunshine_search_where', 10, 2 );
function sunshine_search_where( $where, $wp_query_obj ) {
	global $pagenow, $wpdb;

	if ( ! empty( $wp_query_obj->query_vars['sunshine_search'] ) ) {
		$where = preg_replace(
			'/\(\s*' . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
			'(' . $wpdb->posts . '.post_title LIKE $1) OR (' . $wpdb->postmeta . '.meta_value LIKE $1)',
			$where
		);
	}

	return $where;
}

add_action( 'wp_ajax_nopriv_sunshine_get_image_data', 'sunshine_get_image_data' );
add_action( 'wp_ajax_sunshine_get_image_data', 'sunshine_get_image_data' );
function sunshine_get_image_data() {

	check_ajax_referer( 'sunshinephotocart', 'security' );

	if ( empty( $_POST['image_id'] ) ) {
		wp_send_json_error();
	}

	$image = sunshine_get_image( intval( $_POST['image_id'] ) );
	if ( empty( $image ) ) {
		wp_send_json_error();
	}

	wp_send_json_success(array(
		'id' => $image->get_id(),
		'url' => $image->get_image_url(),
	));
	exit;

}
