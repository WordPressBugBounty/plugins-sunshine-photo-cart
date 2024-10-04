<?php

add_filter( 'sunshine_account_require_login_message', 'sunshine_account_require_login_message_favorites', 10, 2 );
function sunshine_account_require_login_message_favorites( $message, $vars ) {

	if ( ! empty( $vars['after'] ) && $vars['after'] == 'sunshine_add_favorite' ) {
		$message = __( 'You will need an account to remember your favorites the next time you visit', 'sunshine-photo-cart' );
	}

	return $message;

}

add_action( 'sunshine_after_login', 'sunshine_after_login_add_to_favorites' );
add_action( 'sunshine_after_signup', 'sunshine_after_login_add_to_favorites' );
function sunshine_after_login_add_to_favorites( $user_id ) {
	$image_id = SPC()->session->get( 'add_to_favorites' );
	if ( $image_id ) {
		SPC()->customer->add_favorite( $image_id );
		SPC()->session->delete( 'add_to_favorites' );
		SPC()->notices->add( __( 'Image added to favorites', 'sunshine-photo-cart' ) );
		$image = sunshine_get_image( $image_id );
		SPC()->log( $image->get_name() . ' in ' . $image->get_gallery()->get_name() . ' added to favorites by ' . SPC()->customer->get_name() );
	}
}

add_action( 'wp_ajax_sunshine_add_to_favorites', 'sunshine_add_to_favorites' );
function sunshine_add_to_favorites() {
	if ( ! is_user_logged_in() ) {
		return false;
		exit;
	}
	$action = '';
	if ( isset( $_POST['image_id'] ) ) {
		$image_id = intval( $_POST['image_id'] );
		$image    = sunshine_get_image( $image_id );
		if ( $image->can_view() ) {
			if ( SPC()->customer->has_favorite( $image_id ) ) {
				$count  = SPC()->customer->delete_favorite( $image_id );
				$action = 'DELETE';
				SPC()->log( $image->get_name() . ' in ' . $image->get_gallery()->get_name() . ' removed from favorites by ' . SPC()->customer->get_name() );
			} else {
				$count  = SPC()->customer->add_favorite( $image_id );
				$action = 'ADD';
				SPC()->log( $image->get_name() . ' in ' . $image->get_gallery()->get_name() . ' added to favorites by ' . SPC()->customer->get_name() );
			}
			wp_send_json_success(
				array(
					'action' => $action,
					'count'  => SPC()->customer->get_favorite_count(),
				)
			);
		}
	}
	SPC()->log( 'Failed adding to favorites - User ID: ' . get_current_user_id() . ', Image ID: ' . $image_id );
	wp_send_json_error( __( 'Could not add image to favorites', 'sunshine-photo-cart' ) );
}

// add_action( 'before_delete_post', 'sunshine_cleanup_favorites' );
function sunshine_cleanup_favorites( $post_id ) {
	global $wpdb, $post_type;
	if ( $post_type != 'sunshine-gallery' ) {
		return;
	}
	$args   = array(
		'post_type'   => 'attachment',
		'post_parent' => $post_id,
		'nopaging'    => true,
	);
	$images = get_posts( $args );
	foreach ( $images as $image ) {
		$image_ids[] = $image->ID;
	}
	if ( ! empty( $image_ids ) ) {
		$delete_ids = implode( $image_ids, ', ' );
		$query      = "
			DELETE FROM $wpdb->usermeta
			WHERE meta_key = 'sunshine_favorite'
			AND meta_value in ($delete_ids)
		";
		$wpdb->query( $query );
	}
}

add_action( 'init', 'sunshine_clear_favorites', 100 );
function sunshine_clear_favorites() {
	global $sunshine;
	if ( isset( $_GET['clear_favorites'] ) && wp_verify_nonce( $_GET['clear_favorites'], 'sunshine_clear_favorites' ) ) {
		SPC()->customer->clear_favorites();
		SPC()->notices->add( __( 'Favorites cleared', 'sunshine-photo-cart' ) );
		SPC()->log( 'Favorites cleared' );
		wp_safe_redirect( sunshine_get_page_permalink( 'favorites' ) );
		exit;
	}
}

add_filter( 'user_row_actions', 'sunshine_user_favorites_link_row', 5, 2 );
function sunshine_user_favorites_link_row( $actions, $user ) {
	if ( current_user_can( 'sunshine_manage_options', $user->ID ) && in_array( 'sunshine_customer', $user->roles ) ) {
		$actions['sunshine_customer'] = '<a href="' . admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers&customer=' . $user->ID ) . '">' . __( 'Customer Profile', 'sunshine-photo-cart' ) . '</a>';
	}
	return $actions;
}

add_action( 'show_user_profile', 'sunshine_admin_user_show_favorites' );
add_action( 'edit_user_profile', 'sunshine_admin_user_show_favorites' );
function sunshine_admin_user_show_favorites( $user ) {
	if ( current_user_can( 'manage_options' ) ) {
		$favorites = get_user_meta( $user->ID, 'sunshine_favorite' );
		if ( $favorites ) {
			echo '<h3 id="sunshine--favorites">' . __( 'Sunshine Favorites', 'sunshine-photo-cart' ) . ' (' . count( $favorites ) . ')</h3>';
			?>
				<p><a href="#sunshine--favorites-file-list" id="sunshine--favorites-file-list-link"><?php _e( 'Image File List', 'sunshine-photo-cart' ); ?></a></p>
				<div id="sunshine--favorites-file-list" style="display: none;">
				<?php
				foreach ( $favorites as $image_id ) {
					$image_file_list[ $image_id ] = get_post_meta( $image_id, 'sunshine_file_name', true );
				}
				foreach ( $image_file_list as &$file ) {
					$file = str_replace( array( '.jpg', '.JPG' ), '', $file );
				}
				?>
					<textarea rows="4" cols="50" onclick="this.focus();this.select()" readonly="readonly"><?php echo esc_textarea( join( ', ', $image_file_list ) ); ?></textarea>
					<p><?php _e( 'Copy and paste the file names above into Lightroom\'s search feature (Library filter) to quickly find and create a new collection to make processing this order easier. Make sure you are using the "Contains" (and not "Contains All") search parameter.', 'sunshine-photo-cart' ); ?></p>
				</div>
				<script>
				jQuery(document).ready(function($){
					$('#sunshine--favorites-file-list-link').click(function(){
						$('#sunshine--favorites-file-list').slideToggle();
						return false;
					});
				});
				</script>

			<?php
			echo '<ul>';
			foreach ( $favorites as $favorite ) {
				$attachment = get_post( $favorite );
				$image      = wp_get_attachment_image_src( $attachment->ID, 'sunshine-thumbnail' );
				$url        = get_permalink( $attachment->ID );
				?>
			<li style="list-style: none; float: left; margin: 0 20px 20px 0;">
				<a href="<?php echo $url; ?>"><img src="<?php echo $image[0]; ?>" height="100" alt="" /></a><br />
				<?php echo get_the_title( $attachment->ID ); ?>
			</li>
				<?php
			}
			echo '</ul><br clear="all" />';
		}
	}

}


add_action( 'wp', 'sunshine_favorites_check_availability' );
function sunshine_favorites_check_availability() {
	if ( empty( SPC()->customer ) || empty( SPC()->customer->get_favorite_ids() ) || ! is_sunshine_page( 'favorites' ) ) {
		return;
	}
	$removed_items = false;
	foreach ( SPC()->customer->get_favorite_ids() as $favorite_id ) {
		$image     = get_post( $favorite_id );
		$image_url = get_attached_file( $favorite_id );
		if ( ! $image || ! file_exists( $image_url ) ) {
			SPC()->customer->delete_favorite( $favorite_id );
			$removed_items = true;
		}
	}
	if ( $removed_items ) {
		SPC()->notices->add( __( 'Images in your favorites have been removed because they are no longer available', 'sunshine-photo-cart' ) );
		wp_safe_redirect( sunshine_get_page_permalink( 'favorites' ) );
		exit;
	}
}

add_action( 'sunshine_modal_display_share_favorites', 'sunshine_modal_share_favorites' );
function sunshine_modal_share_favorites() {

	$images = SPC()->customer->get_favorites();
	$result = array( 'html' => sunshine_get_template_html( 'favorites/share', array( 'images' => $images ) ) );
	wp_send_json_success( $result );

}

add_action( 'wp_ajax_sunshine_modal_favorites_share_process', 'sunshine_modal_favorites_share_process' );
function sunshine_modal_favorites_share_process() {

	sunshine_modal_check_security( 'sunshine_favorites_share' );

	do_action( 'sunshine_favorites_share', $_POST );

	SPC()->log( 'Favorites shared' );

	wp_send_json_success();

}

add_action( 'sunshine_add_favorite', 'sunshine_add_favorite' );
function sunshine_add_favorite( $image_id ) {
	$favorite_count = get_post_meta( $image_id, 'favorite_count', true );
	$favorite_count++;
	update_post_meta( $image_id, 'favorite_count', $favorite_count );
}

add_action( 'sunshine_delete_favorite', 'sunshine_delete_favorite' );
function sunshine_delete_favorite( $image_id ) {
	$favorite_count = get_post_meta( $image_id, 'favorite_count', true );
	$favorite_count--;
	update_post_meta( $image_id, 'favorite_count', $favorite_count );
}

function sunshine_get_favorites_by_key( $key, $ids = true ) {

	$args  = array(
		'meta_key'   => 'sunshine_favorite_key',
		'meta_value' => $key,
	);
	$users = get_users( $args );
	if ( ! empty( $users ) ) {
		$customer = new SPC_Customer( $users[0]->ID );
		if ( $ids ) {
			$images = $customer->get_favorite_ids();
		} else {
			$images = $customer->get_favorites();
		}
		return $images;
	}

}
