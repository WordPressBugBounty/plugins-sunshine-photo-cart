<?php
class SPC_Tool_Regenerate extends SPC_Tool {

	private $remove_image_sizes = false;

	function __construct() {
		parent::__construct(
			__( 'Regenerate Images', 'sunshine-photo-cart' ),
			'regenerate-images',
			__( 'If you have changed thumbnail size, digital download size or watermark settings, you need to regenerate images.', 'sunshine-photo-cart' ),
			__( 'Regenerate Images', 'sunshine-photo-cart' )
		);

		add_action( 'wp_ajax_sunshine_regenerate_image', array( $this, 'regenerate_image' ) );

		add_filter( 'post_row_actions', array( $this, 'regenerate_gallery_images_link_row' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'regenerate_gallery_images_link_row' ), 10, 2 );

	}

	function regenerate_gallery_images_link_row( $actions, $post ) {
		if ( $post->post_type == 'sunshine-gallery' ) {
			$actions['regenerate'] = '<a href="' . wp_nonce_url( admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-tools&tool=regenerate-images&sunshine_gallery=' . $post->ID ), 'sunshine_tool_' . $this->get_key() ) . '">' . __( 'Regenerate Images', 'sunshine-photo-cart' ) . '</a>';
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}


	function process() {
		global $wpdb;

		$gallery_id = ( isset( $_GET['sunshine_gallery'] ) ) ? intval( $_GET['sunshine_gallery'] ) : '';
		if ( isset( $_GET['sunshine_gallery'] ) ) {
			$gallery = sunshine_get_gallery( intval( $_GET['sunshine_gallery'] ) );
			$title   = sprintf( __( 'Regenerating images for "%s"', 'sunshine-photo-cart' ), get_the_title( $_GET['sunshine_gallery'] ) );
			$count   = $gallery->get_image_count();
		} else {
			$title = __( 'Regenerating images', 'sunshine-photo-cart' );
			$args  = array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'nopaging'    => true,
				'meta_key'    => 'sunshine_file_name',
			);
			$query = new WP_Query( $args );
			$count = $query->found_posts;
		}

		?>
		<h3><?php echo $title; ?>...</h3>
		<div id="sunshine-progress-bar" style="">
			<div id="sunshine-percentage" style=""></div>
			<div id="sunshine-processed" style="">
				<span id="sunshine-processed-count">0</span> / <span id="processed-total"><?php echo $count; ?></span>
			</div>
		</div>
		<p align="center" id="abort"><a href="<?php echo admin_url( 'admin.php?page=sunshine-tools' ); ?>"><?php _e( 'Abort', 'sunshine-photo-cart' ); ?></a></p>
		<ul id="results"></ul>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			var processed = 0;
			var total = <?php echo esc_js( $count ); ?>;
			var percent = 0;
			function sunshine_regenerate_image( item_number ) {
				var data = {
					'action': 'sunshine_regenerate_image',
					'gallery': '<?php echo esc_js( $gallery_id ); ?>',
					'item_number': item_number,
					'security': "<?php echo wp_create_nonce( 'sunshine_regenerate_image' ); ?>"
				};
				$.postq( 'sunshineimageregenerate', ajaxurl, data, function(response) {
					if ( response.error ) {
						$( '#results' ).prepend( '<li><a href="post.php?action=edit&post=' + response.image_id + '" style="color: red;">' + response.file + '</a>: ' + response.error + '</li>' );
					} else {
						$( '#results' ).prepend( '<li><a href="post.php?action=edit&post=' + response.image_id + '">' + response.file + '</a></li>' );
					}
				}).fail( function( jqXHR ) {
					if ( jqXHR.status == 500 || jqXHR.status == 0 ){
						$( '#results' ).prepend( '<li><strong style="color: red;"><?php echo esc_js( __( 'Image did not fully upload because it is too large for your server to handle. Thumbnails and watermarks may not have been applied. Recommend increasing available memory.', 'sunshine-photo-cart' ) ); ?></strong></li>' );
					}
				}).always(function(){
					processed++;
					if ( processed >= total ) {
						$( '#abort' ).hide();
						$( '#sunshine-progress-bar' ).addClass( 'done' )
						$( '#sunshine-processed' ).html( '<?php echo esc_js( __( 'Done!', 'sunshine-photo-cart' ) ); ?>' );

					}
					$( '#sunshine-processed-count' ).html( processed );
					percent = Math.round( ( processed / total ) * 100 );
					$( '#sunshine-percentage' ).css( 'width', percent + '%' );
				});
			}
			for (i = 0; i < total; i++) {
				sunshine_regenerate_image( i );
			}
		});
		</script>

		<?php

	}

	function regenerate_image() {
		global $wpdb, $intermediate_image_sizes;

		if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine_regenerate_image' ) || ! current_user_can( 'sunshine_manage_options' ) ) {
			wp_send_json_error();
		}

		set_time_limit( 600 );

		$item_number = intval( $_POST['item_number'] );

		if ( ! empty( $_POST['gallery'] ) ) {
			$gallery   = sunshine_get_gallery( intval( $_POST['gallery'] ) );
			$image_ids = $gallery->get_image_ids();
			$image_id  = $image_ids[ $item_number ];
		} else {
			$args     = array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'offset'         => $item_number,
				'posts_per_page' => 1,
				'meta_key'       => 'sunshine_file_name',
			);
			$query    = new WP_Query( $args );
			$image_id = $query->posts[0]->ID;
		}

		$image = sunshine_get_image( $image_id );

		if ( function_exists( 'wp_get_original_image_path' ) ) {
			$file_path = wp_get_original_image_path( $image_id );
		} else {
			$file_path = get_attached_file( $image_id );
		}
		if ( is_wp_error( $file_path ) ) {
			SPC()->log( 'Could not find original file to regenerate from, image ID: ' . $image_id );
			wp_send_json(
				array(
					'status'   => 'error',
					'file'     => $image->get_name(),
					'image_id' => $image_id,
					'error'    => __(
						'Could not find original file to regenerate from',
						'sunshine-photo-cart'
					),
				)
			);
			return;
		}

		SPC()->log( 'Regenerating image: ' . $image_id );

		// If we have a remote file and we have the offload plugin active.
		if ( substr( $file_path, 0, 2 ) == 's3' && function_exists( 'as3cf_get_attachment_url' ) ) {

			$upload_info = wp_upload_dir();
			$upload_dir  = $upload_info['basedir'];
			$upload_url  = $upload_info['baseurl'];

			$remote_url = as3cf_get_attachment_url( $image_id );
			$orig_image = file_get_contents( $remote_url );

			// Make the new local version of the file the source file path.
			$file_path = $upload_dir . '/sunshine/' . $image->get_gallery_id() . '/' . basename( $file_path );
			$save = file_put_contents( $file_path, $orig_image );

		} else {

			$directory = dirname( $file_path );
			$file_info = pathinfo( $file_path );
			$filename = $file_info['filename']; // This will be 'lee-10'
			$extension = $file_info['extension']; // This will be 'jpg'

			// Find extra images and delete them.
			$pattern = $directory . '/' . $filename . '-*x*.' . $extension;
			$extra_images = glob( $pattern );
			foreach( $extra_images as $extra_image ) {
				wp_delete_file( $extra_image );
			}

		}

		// Regenerate everything.
		$new_metadata = wp_generate_attachment_metadata( $image_id, $file_path );
		$image_meta = $new_metadata['image_meta'];
		if ( ! empty( $image_meta['created_timestamp'] ) ) {
			$created_timestamp = $image_meta['created_timestamp'];
			update_post_meta( $image_id, 'created_timestamp', $created_timestamp );
		}

		do_action( 'sunshine_after_image_process', $image_id );
		wp_update_attachment_metadata( $image_id, $new_metadata );

		wp_send_json(
			array(
				'status'   => 'success',
				'file'     => $image->get_name(),
				'image_id' => $image_id,
			)
		);

	}

	public function remove_image_sizes( $sizes ) {
		if ( $this->remove_image_sizes ) {
			foreach ( $sizes as $key => $size ) {
				if ( strpos( $size, 'sunshine-' ) === false ) {
					unset( $sizes[ $key ] );
				}
			}
		}
		return $sizes;
	}

}

$spc_tool_regenerate = new SPC_Tool_Regenerate();
