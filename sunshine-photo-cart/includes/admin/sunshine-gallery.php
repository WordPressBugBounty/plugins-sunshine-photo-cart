<?php
class Sunshine_Admin_Meta_Boxes_Gallery extends Sunshine_Admin_Meta_Boxes {

	protected $post_type = 'sunshine-gallery';

	public function set_meta_boxes( $meta_boxes ) {
		$meta_boxes['sunshine-gallery'] = array(
			array(
				'id'       => 'sunshine-gallery-options', // Unique box id
				'name'     => __( 'Gallery Options', 'sunshine-photo-cart' ), // Label/name
				'context'  => 'advanced', // normal/side/advanced
				'priority' => 'high', // priority
			),
		);
		return $meta_boxes;
	}

	public function set_options( $options ) {

		$price_level_terms = get_terms(
			array(
				'taxonomy'   => 'sunshine-product-price-level',
				'hide_empty' => false,
			)
		);
		$price_levels      = array();
		foreach ( $price_level_terms as $price_level ) {
			$price_levels[ $price_level->term_id ] = $price_level->name;
		}

		$options['sunshine-gallery-options'] = array(
			'0' => array(
				'id'     => 'images',
				'name'   => __( 'Images', 'sunshine-photo-cart' ) . ' (<span class="sunshine-gallery-image-count">0</span>)',
				'icon'   => SUNSHINE_PHOTO_CART_PATH . 'assets/images/icons/galleries.svg',
				'fields' => array(
					array(
						'id'   => 'gallery_images',
						'type' => 'gallery_images',
					),
				),
			),
			'1000' => array(
				'id'     => 'general',
				'name'   => __( 'General Options', 'sunshine-photo-cart' ),
				'icon'   => SUNSHINE_PHOTO_CART_PATH . 'assets/images/icons/settings.svg',
				'fields' => array(
					array(
						'id'      => 'status',
						'name'    => __( 'Gallery Type', 'sunshine-photo-cart' ),
						'type'    => 'radio',
						'default' => 'default',
						'options' => array(
							'default'  => __( 'Default', 'sunshine-photo-cart' ),
							'password' => __( 'Password Protected', 'sunshine-photo-cart' ),
							'private'  => __( 'Private (only specified users)', 'sunshine-photo-cart' ),
						),
					),
					array(
						'id'         => 'password',
						'name'       => __( 'Password/Access Code', 'sunshine-photo-cart' ),
						'type'       => 'text',
						'after' => '<button type="button" id="sunshine-gallery-password-generate" class="button">' . __( 'Generate password', 'sunshine-photo-cart' ) . '</button>',
						'conditions' => array(
							array(
								'field'   => 'status',
								'compare' => '==',
								'value'   => 'password',
								'action'  => 'show',
							),
						),
					),
					array(
						'id'          => 'password_hint',
						'name'        => __( 'Password Hint', 'sunshine-photo-cart' ),
						'type'        => 'text',
						'conditions'  => array(
							array(
								'field'   => 'status',
								'compare' => '==',
								'value'   => 'password',
								'action'  => 'show',
							),
						),
						'description' => __( 'Optionally include a hint for the password', 'sunshine-photo-cart' ),
					),
					array(
						'id'         => 'private_users',
						'name'       => __( 'Allowed Customers', 'sunshine-photo-cart' ),
						'type'       => 'users',
						'select2'    => true,
						'options'    => 'users',
						'conditions' => array(
							array(
								'field'   => 'status',
								'compare' => '==',
								'value'   => 'private',
								'action'  => 'show',
							),
						),
					),
					array(
						'id'         => 'access_type',
						'name'       => __( 'Access Type', 'sunshine-photo-cart' ),
						'type'       => 'radio',
						'options'    => array(
							''      => __( 'Default', 'sunshine-photo-cart' ),
							'account' => __( 'Registered and logged in', 'sunshine-photo-cart' ),
							'email' => __( 'Provide email address', 'sunshine-photo-cart' ),
							'url'   => __( 'Direct URL', 'sunshine-photo-cart' ),
						),
						/*
						'conditions' => array(
							array(
								'field'         => 'access_type',
								'compare'       => '==',
								'value'         => 'email',
								'action'        => 'show',
								'action_target' => '#sunshine-admin-meta-box-tab-link-email',
							),
						),
						*/
					),
					array(
						'id'          => 'end_date',
						'name'        => __( 'Expiration', 'sunshine-photo-cart' ),
						'type'        => 'date_time',
						'description' => __( 'When will this gallery expire and no longer be accessible', 'sunshine-photo-cart' ),
					),
					array(
						'id'          => 'image_comments',
						'name'        => __( 'Comments', 'sunshine-photo-cart' ),
						'type'        => 'checkbox',
						'description' => __( 'Allow comments on images in this gallery', 'sunshine-photo-cart' ),
					),
					array(
						'id'          => 'image_comments_approval',
						'name'        => __( 'Comments require approval', 'sunshine-photo-cart' ),
						'type'        => 'checkbox',
						'description' => __( 'Should comments require approval before being shown', 'sunshine-photo-cart' ),
						'conditions'  => array(
							array(
								'field'   => 'image_comments',
								'compare' => '==',
								'value'   => '1',
								'action'  => 'show',
							),
						),
					),
					array(
						'id'          => 'disable_favorites',
						'name'        => __( 'Favorites', 'sunshine-photo-cart' ),
						'type'        => 'checkbox',
						'description' => __( 'Disable favorites for this gallery', 'sunshine-photo-cart' ),
					),
					array(
						'id'          => 'disable_gallery_sharing',
						'name'        => __( 'Gallery Sharing', 'sunshine-photo-cart' ),
						'type'        => 'checkbox',
						'description' => __( 'Disable gallery sharing', 'sunshine-photo-cart' ),
					),
					array(
						'id'          => 'disable_image_sharing',
						'name'        => __( 'Image Sharing', 'sunshine-photo-cart' ),
						'type'        => 'checkbox',
						'description' => __( 'Disable individual image sharing for this gallery', 'sunshine-photo-cart' ),
					),
				),
			),
			'2000' => array(
				'id'     => 'products',
				'name'   => __( 'Products', 'sunshine-photo-cart' ),
				'icon'   => SUNSHINE_PHOTO_CART_PATH . 'assets/images/icons/product.svg',
				'fields' => array(
					1 => array(
						'id'          => 'disable_products',
						'name'        => __( 'Disable Products', 'sunshine-photo-cart' ),
						'type'        => 'checkbox',
						'description' => __( 'Users will not be able to purchase any products for this gallery', 'sunshine-photo-cart' ),
						'conditions'  => array(
							array(
								'field'         => 'disable_products',
								'compare'       => '==',
								'value'         => '1',
								'action'        => 'hide',
								'action_target' => '#sunshine-admin-meta-box-tab-fields-products tr:not(#sunshine-meta-fields-disable_products)',
							),
						),
					),
					2 => array(
						'id'            => 'price_level',
						'name'          => __( 'Price Level', 'sunshine-photo-cart' ),
						'type'          => 'select',
						'options'       => $price_levels,
						'documentation' => 'https://www.sunshinephotocart.com/docs/setting-up-price-levels/',
						/*
						'upgrade'       => array(
							'addon' => 'price-levels',
							'label' => __( 'Upgrade to manage more price levels', 'sunshine-photo-cart' ),
							'url'   => '',
						),
						*/
					),
				),
			),
			'9000' => array(
				'id'     => 'email',
				'name'   => __( 'Emails', 'sunshine-photo-cart' ),
				'icon'   => SUNSHINE_PHOTO_CART_PATH . 'assets/images/icons/settings.svg',
				'fields' => array(
					array(
						'id'   => 'provided_emails',
						'name' => __( 'Provided Emails', 'sunshine-photo-cart' ),
						'type' => 'gallery_emails',
					),
				),
			),

		);

		return $options;
	}

	public function enqueue( $page ) {

		if ( get_post_type() != 'sunshine-gallery' ) {
			return;
		}

		wp_enqueue_script( 'plupload-all' );
		wp_enqueue_media();

	}

}

$sunshine_admin_meta_boxes_gallery = new Sunshine_Admin_Meta_Boxes_Gallery();

// add_action( 'admin_head', 'sunshine_remove_add_media' );
function sunshine_remove_add_media() {
	if ( get_post_type() == 'sunshine-gallery' ) {
		remove_action( 'media_buttons', 'media_buttons' );
	}
}

add_filter( 'manage_edit-sunshine-gallery_columns', 'sunshine_galleries_columns', 10 );
function sunshine_galleries_columns( $columns ) {
	unset( $columns['date'] );
	unset( $columns['title'] );
	$columns['featured_image'] = '';
	$columns['title']          = __( 'Title' );
	$columns['expires']        = __( 'Expires', 'sunshine-photo-cart' );
	$columns['images']         = __( 'Images', 'sunshine-photo-cart' );
	$columns['date']           = __( 'Date' );
	return $columns;
}

add_action( 'manage_sunshine-gallery_posts_custom_column', 'sunshine_galleries_columns_content', 99, 2 );
function sunshine_galleries_columns_content( $column, $post_id ) {
	global $post;
	$gallery = sunshine_get_gallery( $post );
	switch ( $column ) {
		case 'featured_image':
			$gallery->featured_image();
			break;
		case 'images':
			echo $gallery->get_image_count();
			break;
		case 'expires':
			echo $gallery->get_expiration_date_formatted();
			if ( $gallery->is_expired() ) {
				echo ' - <em>' . __( 'Expired', 'sunshine-photo-cart' ) . '</em>';
			}
			break;
		case 'gallery_date':
			echo 'DATE';
			break;
		default:
			break;
	}
}

function ajax_load_edit_image_modal() {
	// Check if the attachment ID is passed
	if ( isset( $_POST['attachment_id'] ) && ! empty( $_POST['attachment_id'] ) ) {
		$attachment_id = intval( $_POST['attachment_id'] );
		// Get the attachment
		$attachment = get_post( $attachment_id );

		if ( $attachment ) {
			// Capture the output of get_media_item()
			$modal_content = get_media_item( $attachment_id, array( 'delete' => false, 'send' => false, 'show_title' => false, 'toggle' => false ) );
			wp_send_json_success( $modal_content );
		}
	}
	wp_die( 'Invalid attachment ID' );
}
add_action( 'wp_ajax_load_edit_image_modal', 'ajax_load_edit_image_modal' );

// Handle the custom AJAX request to save the attachment fields
function save_attachment_fields_via_ajax() {
	sunshine_log( $_POST, 'save attachment fields' );
	// Verify required data
	if ( isset( $_POST['form_data'] ) && isset( $_POST['attachment_id'] ) ) {
		parse_str( $_POST['form_data'], $fields ); // Parse serialized form data
		$attachment_id = intval( $_POST['attachment_id'] );
		$fields_to_save = $fields['attachments'][ $attachment_id ];
		$attachment = get_post( $attachment_id, 'ARRAY_A' );
		if ( $attachment ) {
			$attachment = apply_filters( 'attachment_fields_to_save', $attachment, $fields_to_save );
			wp_send_json_success();
		}
	}

	// If something is wrong, return an error response
	wp_send_json_error();
}
add_action( 'wp_ajax_save_attachment_fields', 'save_attachment_fields_via_ajax' );


/* Custom Meta Box Field Display for gallery image upload */
add_action( 'sunshine_meta_gallery_images_display', 'sunshine_meta_gallery_images_display' );
function sunshine_meta_gallery_images_display() {
	global $post;
	$gallery      = sunshine_get_gallery( $post );
	$image_ids    = $gallery->get_image_ids();
	$total_images = $gallery->get_image_count();
	$images       = $gallery->get_images( array( 'posts_per_page' => apply_filters( 'sunshine_admin_gallery_images_load', 20 ) ) );
	$selected_dir = $gallery->get_image_directory();
	?>
<div id="sunshine-gallery-images-processing"><div class="status"></div></div>

<input type="hidden" name="selected_images" value="<?php echo join( ',', $image_ids ); ?>" />

<div id="sunshine-gallery-upload-container">

	<div id="plupload-upload-ui" class="hide-if-no-js">
		<div id="drag-drop-area">
			<div class="sunshine-drag-drop-inside">
				<p class="drag-drop-info">
					<span class="no-drag-drop"><?php _e( 'Drop files here', 'sunshine-photo-cart' ); ?> or </span><input id="plupload-browse-button" type="button" value="<?php esc_attr_e( 'Select Files from Computer', 'sunshine-photo-cart' ); ?>" class="button" />
					<br /><span class="recommend-size"><?php echo sprintf( __( 'Recommend image size larger than %s', 'sunshine-photo-cart' ), sunshine_get_large_dimension( 'w' ) . ' &times; ' . sunshine_get_large_dimension( 'h' ) ); ?></span>
				</p>
				<hr />
				<?php
				$import_label = __( 'Import', 'sunshine-photo-cart' );
				if ( $selected_dir ) {
					// Count number of items in this directory and compare to how many are in the folder now.
					$folder_count = sunshine_image_folder_count( sunshine_get_import_directory() . '/' . $selected_dir );
					$current_count = $gallery->get_image_count();
					if ( $folder_count > $current_count ) {
						$import_label = __( 'Update from folder', 'sunshine-photo-cart' );
						echo '<p id="sunshine-ftp-new-images" style="background: orange; color: #FFF; padding: 5px 20px;"><strong>' . __( 'New images are available in your FTP folder', 'sunshine-photo-cart' ) . '</strong></p>';
					}
				}
				?>
				<p class="import-info">
					<?php _e( 'FTP Folders', 'sunshine-photo-cart' ); ?>
					<select name="images_directory">
						<option value=""><?php _e( 'Select folder', 'sunshine-photo-cart' ); ?></option>
						<?php
						sunshine_directory_to_options( sunshine_get_import_directory(), $selected_dir );
						?>
					</select>
					<button class="button" id="import"><?php echo esc_html( $import_label ); ?></button> <a href="https://www.sunshinephotocart.com/docs/how-to-create-a-new-gallery-via-ftp/" target="_blank" class="dashicons dashicons-editor-help"></a>
				</p>
				<!-- JUST SO MANY ISSUES:
				1) Cannot have multiple URLs for a single image thus cannot be in multiple galleries
				2) If pulled in from outside a Sunshine gallery, we need to regenerate image for various sizes but don't want to remove outside image sizes so they still work wherever else the image is being used
				Just MUCH better to simply re-upload to this gallery than trying to move it over and have it work in original spot
				<hr />
				<p>
					<button class="button" id="media-browse-button"><?php _e( 'Choose from Media Library', 'sunshine-photo-cart' ); ?></button></a>
				</p>
				-->
			</div>
		</div>
	</div>

	<div id="sunshine-gallery-images">
		<ul id="sunshine-gallery-image-errors"></ul>
		<ul id="sunshine-gallery-image-list">
			<?php
			if ( ! empty( $images ) ) {
				foreach ( $images as $image ) {
					sunshine_admin_gallery_image_thumbnail( $image );
				}
			}
			?>
		</ul>
		<div id="sunshine-gallery-image-actions">
			<div id="sunshine-gallery-select-all"><a class="button" data-action="all"><?php _e( 'Select all images', 'sunshine-photo-cart' ); ?></a></div>
			<div id="sunshine-gallery-delete-images" style="display: none;"><a class="button delete"><?php _e( 'Delete selected images', 'sunshine-photo-cart' ); ?></a><span class="spinner"></span></div>
			<?php
			if ( $total_images > 20 ) {
				echo '<div id="sunshine-gallery-load-more">';
				echo sprintf( __( 'Showing %1$s of %2$s images', 'sunshine-photo-cart' ), '<span id="sunshine-gallery-images-loaded">20</span>', '<span class="sunshine-gallery-image-count">' . $total_images . '</span>' );
				echo ' &mdash; ';
				echo sprintf( __( 'Load %s more images', 'sunshine-photo-cart' ), '<select name="count"><option value="20">20</option><option value="50">50</option><option value="100">100</option><option value="999999999">All</option></select>' );
				echo ' <input type="button" name="loadmorego" id="sunshine-load-more-go" value="' . __( 'GO', 'sunshine-photo-cart' ) . '" class="button" /> &nbsp;&nbsp;&nbsp; ';
				echo '</div>';
			}
			?>
		</div>
	</div>
	<script>
	jQuery(document).ready(function($) {

		var total_images = <?php echo esc_js( $total_images ); ?>;
		var image_ids = $( 'input[name="selected_images"]' ).val().split(',');
		var offset = 20;
		var count = 20;

		/*
		NOT USED
		$( '#media-browse-button' ).click(function(e) {

			   e.preventDefault();

			   // Define image_frame as wp.media object
			   gallery_images = wp.media({
				 title: 'Select Images for Gallery',
				 multiple : true,
				 library : {
					  type : 'image',
				  }
			 });

			 gallery_images.on( 'close', function() {
				var selection = gallery_images.state().get( 'selection' );
				image_ids = new Array(); // Reset image id array first
				selection.each(function(attachment) {
				   image_ids.push( attachment['id'] );
				});
				var data = {
					action: 'sunshine_gallery_add_media_images',
					gallery_id: '<?php echo esc_js( $gallery->get_id() ); ?>',
					image_ids: image_ids,
					security: '<?php echo wp_create_nonce( 'sunshine_gallery_add_media_images' ); ?>'
				};
				$.post(ajaxurl, data, function(response) {
					if ( response.success ) {
						total_images = image_ids.length;
						$( '#sunshine-gallery-image-list' ).html( response.data.image_html );
						$( document ).trigger( 'refresh_images' );
					}
				});
			 });

			gallery_images.on( 'open', function() {
				  var selection = gallery_images.state().get( 'selection' );
				  image_ids.forEach(function(id) {
					var attachment = wp.media.attachment( parseInt( id ) );
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				  });

			});

			gallery_images.open();

		  });

		/**********
		IMAGE ACTIONS
		**********/

		// Function to open the Edit Image modal for a specific attachment ID
		function openEditImageModal(attachmentId) {
			// Perform AJAX request to load the Edit Image modal content
			$.post(ajaxurl, {
				action: 'load_edit_image_modal',
				attachment_id: attachmentId
			}, function(response) {
				if (response.success) {

					var modalHtml = '<form id="sunshine-edit-attachment" data-id="' + attachmentId + '" class="edit-attachment-frame">';

					modalHtml += '<div class="media-modal wp-core-ui">';
					modalHtml += '<div class="media-modal-content">';
					modalHtml += '<div class="edit-attachment-frame mode-select hide-router">';

						modalHtml += '<div class="edit-media-header">';
							modalHtml += '<button type="button" class="media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text">Close dialog</span></span></button>';
						modalHtml += '</div>';

					modalHtml += '<div class="media-frame-title"><h1>Attachment Details</h1></div>';

					modalHtml += '<div class="media-frame-content">';
					modalHtml += '<div class="attachment-details save-ready">';
					modalHtml += response.data;
					modalHtml += '</div>';
					modalHtml += '</div>';

					modalHtml += '</div>';
					modalHtml += '</div>';
					modalHtml += '</div>';

					modalHtml += '<div class="media-modal-backdrop"></div>';
					modalHtml += '</form>';

					// Append the modal to the body
					$( 'body' ).append( modalHtml );
					$( 'td.savesend' ).html( '<p><input type="submit" class="button button-primary button-large" value="<?php echo esc_js( 'Update' ); ?>"></p>' );

					// Close modal on click of the close button
					$('.media-modal-close').on('click', function() {
						$( '#sunshine-edit-attachment' ).remove(); // Close and remove the modal
					});
				} else {
					alert('Unable to load the edit modal.');
				}
			});
		}

		$( '.sunshine-image-editXXX' ).on( 'click', function(e) {
			e.preventDefault();
			var attachmentId = $( this ).data( 'image-id' );
			openEditImageModal( attachmentId );
		});

		$( document ).on( 'submit', '#sunshine-edit-attachment', function(e) {

			e.preventDefault();

			// Get all form data within the div
			var form_data = $( this ).serialize();
			var attachment_id = $( this ).data( 'id' );

			// Perform AJAX request to trigger the attachment_fields_to_save action
			$.ajax({
				url: ajaxurl, // WordPress's built-in AJAX handler URL
				type: 'POST',
				data: {
					action: 'save_attachment_fields', // Custom AJAX action
					form_data: form_data, // Serialized form data
					attachment_id: attachment_id // Assuming you have the attachment ID stored in a data attribute
				},
				success: function(response) {
					if (response.success) {
						console.log('Attachment fields saved successfully.');
					} else {
						console.log('Failed to save attachment fields.');
					}
				},
				error: function() {
					console.log('Error occurred while saving attachment fields.');
				}
			});
		});

		$( '#sunshine-load-more-go' ).on('click', function(){
			$( this ).html( '<?php echo esc_js( __( 'Loading', 'sunshine-photo-cart' ) ); ?> ' );
			count = parseInt( $( 'select[name="count"]' ).val() );
			var data = {
				action: 'sunshine_gallery_load_more',
				gallery_id: '<?php echo esc_js( $post->ID ); ?>',
				offset: offset,
				count: count,
				security: '<?php echo wp_create_nonce( 'sunshine_gallery_load_more' ); ?>'
			};
			$.post(ajaxurl, data, function( response ) {
				if ( response.success ) {
					//$( this ).data( 'offset', ( offset + 20 ) );
					$( '#sunshine-gallery-image-list' ).append( response.data.image_html );
					offset = offset + count;
					if ( offset > total_images ) {
						$( '#sunshine-gallery-load-more' ).remove();
					}
					$( '#sunshine-gallery-images-loaded' ).html( offset );
				}
			});
			return false;
		});

		<?php if ( SPC()->get_option( 'image_order' ) == 'menu_order' ) { ?>
			var itemList = $( '#sunshine-gallery-image-list' );
			itemList.sortable({
				update: function(event, ui) {
					$('#sunshine-gallery-images-processing div.status').html('<?php echo esc_js( __( 'Saving image order...', 'sunshine-photo-cart' ) ); ?>');
					$('#sunshine-gallery-images-processing').show();
					var images = itemList.sortable('toArray').toString();
					opts = {
						url: ajaxurl,
						type: 'POST',
						async: true,
						cache: false,
						dataType: 'json',
						data:{
							action: 'sunshine_gallery_image_sort',
							images: images,
							gallery_id: '<?php echo esc_js( $gallery->get_id() ); ?>',
							security: '<?php echo wp_create_nonce( 'sunshine_gallery_image_sort' ); ?>'
						},
						success: function(response) {
							$('#sunshine-gallery-images-processing').hide();
							return;
						},
						error: function(xhr,textStatus,e) {
							$('#sunshine-gallery-images-processing').hide();
							return;
						}
					};
					$.ajax(opts);
				}
			});
		<?php } ?>

		$( document ).on( 'click', 'a.sunshine-image-delete', function(){
			var image_id = $( this ).data( 'image-id' );
			var data = {
				action: 'sunshine_gallery_image_delete',
				image_id: image_id,
				gallery_id: '<?php echo esc_js( $gallery->get_id() ); ?>',
				security: '<?php echo wp_create_nonce( 'sunshine_gallery_image_delete' ); ?>'
			};
			$.postq( 'sunshinedeleteimage', ajaxurl, data, function(response) {
				if ( response.success ) {
					total_images--;
					$( '.sunshine-gallery-image-count' ).html( total_images );
					$( 'li#image-' + image_id ).fadeOut();
					jQuery( document ).trigger( 'refresh_images' );
				} else {
					alert('<?php echo esc_js( __( 'Sorry, the image could not be deleted for some reason', 'sunshine-photo-cart' ) ); ?>');
				}
			});
			return false;
		});

		<?php
		$post_thumbnail_id = get_post_thumbnail_id( $post );
		if ( $post_thumbnail_id ) {
			?>
			$( 'li#image-<?php echo intval( $post_thumbnail_id ); ?>' ).addClass( 'featured' );
		<?php } ?>

		$( document ).on( 'click', 'a.sunshine-image-featured', function(){
			var image_id = $( this ).data( 'image-id' );
			var data = {
				action: 'sunshine_gallery_image_featured',
				gallery_id: '<?php echo esc_js( $post->ID ); ?>',
				image_id: image_id,
				security: '<?php echo wp_create_nonce( 'sunshine_gallery_image_featured' ); ?>'
			};
			$.post( ajaxurl, data, function(response) {
				if ( response.success ) {
					$( '#sunshine-gallery-image-list li' ).removeClass( 'featured' );
					// Replace existing Featured Image thumbnail with this new one if it exists
					if ( response.data.image_url ) {
						$( 'li#image-' + image_id ).addClass( 'featured' );
						$( '.editor-post-featured-image__container img' ).attr( 'src', response.data.image_url );
					} else {
						$( '.editor-post-featured-image__container img' ).attr( 'src', '' );
					}
				} else {
					alert('<?php echo esc_js( __( 'Sorry, the image could not be set as featured', 'sunshine-photo-cart' ) ); ?>');
				}
			});
			return false;
		});

		// TODO: This isn't working and don't know why. Should remove the highlighted image in the list that is featured
		$( document ).on( 'click', '.editor-post-featured-image .is-destructive', function(){
			$( '#sunshine-gallery-image-list li' ).removeClass( 'featured' );
		});

		$( document ).on( 'click', '#sunshine-gallery-image-list li', function(){
			$( this ).toggleClass( 'selected' );
			// If total image count is > 0, show button to delete
			if ( $( '#sunshine-gallery-image-list li.selected' ).length > 0 ) {
				$( '#sunshine-gallery-delete-images' ).show();
			} else {
				$( '#sunshine-gallery-delete-images' ).hide();
			}
		});

		$( document ).on( 'click', '#sunshine-gallery-select-all a', function(){
			var select_action = $( this ).data( 'action' );
			if ( select_action == 'all' ) {
				$( '#sunshine-gallery-image-list li' ).each( function(){
					$( this ).addClass( 'selected' );
					$( '#sunshine-gallery-delete-images' ).show();
				});
				$( this ).data( 'action', 'none' ).html( '<?php echo esc_js( __( 'Select no images', 'sunshine-photo-cart' ) ); ?>' );
			} else {
				$( '#sunshine-gallery-image-list li' ).each( function(){
					$( this ).removeClass( 'selected' );
					$( '#sunshine-gallery-delete-images' ).hide();
				});
				$( this ).data( 'action', 'all' ).html( '<?php echo esc_js( __( 'Select all images', 'sunshine-photo-cart' ) ); ?>' );
			}
		});

		$( document ).on( 'click', '#sunshine-gallery-delete-images a', function(){
			$( '#sunshine-gallery-delete-images .spinner' ).addClass( 'is-active' );
			var delete_count = $( '#sunshine-gallery-image-list li.selected' ).length;
			var processed_delete_count = 0;
			$( '#sunshine-gallery-image-list li.selected' ).each( function(){
				var image_id = $( this ).data( 'image-id' );
				if ( image_id ) {
					var data = {
						action: 'sunshine_gallery_image_delete',
						image_id: image_id,
						gallery_id: '<?php echo esc_js( $gallery->get_id() ); ?>',
						security: '<?php echo wp_create_nonce( 'sunshine_gallery_image_delete' ); ?>'
					};
					$.postq( 'sunshinedeleteimages', ajaxurl, data, function( response ) {
						processed_delete_count++;
						if ( response.success ) {
							total_images--;
							$( 'li#image-' + image_id ).fadeOut();
							jQuery( document ).trigger( 'refresh_images' );
						}
						if ( processed_delete_count >= delete_count ) {
							$( '#sunshine-gallery-delete-images .spinner' ).removeClass( 'is-active' );
							$( '#sunshine-gallery-delete-images' ).hide();
						}
					});
				}
			});
			return false;
		});


		/**********
		UPLOADER
		**********/

		<?php
		$plupload_init = array(
			'runtimes'         => 'html5,silverlight,flash,html4',
			'browse_button'    => 'plupload-browse-button',
			'container'        => 'plupload-upload-ui',
			'drop_element'     => 'drag-drop-area',
			'file_data_name'   => 'sunshine_gallery_image',
			'multiple_queues'  => true,
			'max_file_size'    => wp_max_upload_size() . 'b',
			'url'              => admin_url( 'admin-ajax.php' ),
			// 'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
			// 'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
			'filters'          => array(
				array(
					'title'      => __( 'Allowed Files' ),
					'extensions' => join( ',', sunshine_allowed_file_extensions() ),
				),
			),
			'multipart'        => true,
			'urlstream_upload' => true,

			// additional post data to send to our ajax hook
			'multipart_params' => array(
				'security'   => wp_create_nonce( 'sunshine_gallery_upload' ),
				'action'     => 'sunshine_gallery_upload',            // the ajax action name
				'gallery_id' => $post->ID,
			),
		);
		?>

		// create the uploader and pass the config from above
		var uploader = new plupload.Uploader(<?php echo json_encode( $plupload_init ); ?>);
		uploader.init();

		// checks if browser supports drag and drop upload, makes some css adjustments if necessary
		uploader.bind('Init', function(up){
			var uploaddiv = $('#plupload-upload-ui');
			if( $(document.body).hasClass("mobile") ){
				uploaddiv.removeClass('drag-drop');
				$('#drag-drop-area').unbind('.wp-uploader');
			} else{
				uploaddiv.addClass('drag-drop');
					$('#drag-drop-area')
						.bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
						.bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });
			}

		});

		uploader.bind( 'UploadComplete', function(){
			$( '#sunshine-gallery-images-processing div.status' ).html( 'Upload complete!' );
			$( '#sunshine-gallery-images-processing' ).addClass( 'success' ).delay( 1000 ).fadeOut( 400 );
			var elem = document.getElementById( 'sunshine-gallery-images' );
			elem.scrollTop = elem.scrollHeight;
		});

		// a file was added in the queue
		var current_image_count = 0;
		uploader.bind( 'FilesAdded', function(up, files){
			$( '#sunshine-gallery-image-errors' ).html( '' );
			var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);
			var images_to_upload = files.length;
			plupload.each(files, function(file){
				if ( max > hundredmb && file.size > hundredmb && up.runtime != 'html5' ){
					alert( 'Your file was too large' );
				} else {
					current_image_count = 0;
					$( '#sunshine-gallery-images-processing').removeClass( 'success' );
					$( '#sunshine-gallery-images-processing div.status' ).html( 'Uploading <span class="processed">0</span> of <span class="total-files">' + images_to_upload + '</span> files...<span class="current-file"></span>' );
					$( '#sunshine-gallery-images-processing' ).show();
				}
			});

			up.refresh();
			up.start();
		});

		// a file was uploaded
		uploader.bind( 'FileUploaded', function(up, file, response) {
			var result = $.parseJSON( response.response );
			if ( result.success === true ) {
				current_image_count++;
				$( '#sunshine-gallery-images-processing span.processed' ).html( current_image_count );
				$( '#sunshine-gallery-images-processing div.status span.current-file' ).html( file.name + ' uploaded' );
				if ( result.data.image_html ) {
					var image_ids = $( 'input[name="selected_images"]' ).val();
					$( 'input[name="selected_images"]' ).val( image_ids + ',' + result.data.image_id );
					$( '#sunshine-gallery-image-list' ).append( result.data.image_html );
					total_images++;
					jQuery( document ).trigger( 'refresh_images' );
				} else {
					$( '#sunshine-gallery-images ul#files' ).append(
						$('<li/>', {
							'id': 'image-' + result.data.image_id,
							html: result.file.name
						})
					);
				}
			} else {
				$( '#sunshine-gallery-image-errors' ).append( '<li>' + result.data.file + ' could not be uploadeed: ' + result.data.error + '</li>' );
			}
		});

		uploader.bind( 'ChunkUploaded', function(up, file, info) {
			var percent = Math.round( 100 - ( ( (info.total - info.offset) / info.total ) * 100 ) );
			$( '#sunshine-gallery-images-processing div.status span.current-file').html( 'Uploading file "'+file.name+'" ('+percent+'%)' );
		});

		uploader.bind('Error', function(up, err) {
			if ( err.status == 504 ) {
				$( '#sunshine-gallery-image-errors' ).append( '<li>' + err.file.name + ': ' + err.message + ' (Your server could not process the image, contact your web host)</li>' );
			} else {
				$( '#sunshine-gallery-image-errors' ).append( '<li>' + err.file.name + ': ' + err.message + ' (' + err.code + ', ' + err.status + ')</li>' );
			}
		});

		/**********
		IMPORTING FOLDER
		**********/
		$( document ).on( 'click', '#import', function(){
			var images_to_upload = $( 'select[name="images_directory"] option:selected' ).data( 'count' );
			if ( !images_to_upload ) {
				return false;
			}
			var processed_images = 0;
			$( '#sunshine-gallery-images-processing').removeClass( 'success' );
			$( '#sunshine-gallery-images-processing div.status' ).html( 'Uploading <span class="processed">0</span> of <span class="total-files">' + images_to_upload + '</span> files...<span class="current-file"></span>' );
			$( '#sunshine-gallery-images-processing' ).show();

			for ( i = 1; i <= images_to_upload; i++ ) {
				var data = {
					'action': 'sunshine_gallery_import',
					'gallery_id': <?php echo esc_js( $post->ID ); ?>,
					'dir': $( 'select[name="images_directory"] option:selected' ).val(),
					'item_number': i
				};
				$.postq( 'sunshinegalleryimport', ajaxurl, data, function(response) {
					if ( response.success === true ) {
						$( '#sunshine-gallery-images-processing div.status span.current-file' ).html( response.data.file_name + ' uploaded' );
						if ( response.data.image_html ) {
							$( '#sunshine-gallery-image-list' ).append( response.data.image_html );
						} else {
							$( '#sunshine-gallery-images ul#files' ).append(
								$('<li/>', {
									'id': 'image-' + response.data.image_id,
									html: response.file_name
								})
							);
						}
					} else {
						$( '#sunshine-gallery-images-processing div.status span.current-file' ).html( response.data.file_name + ' not uploaded: ' + response.data.error );
					}
				}).fail( function( jqXHR ) {
					if ( jqXHR.status == 500 || jqXHR.status == 0 ){
						$( '#sunshine-gallery-image-errors' ).append( '<li><strong><?php echo esc_js( __( 'An image did not fully upload because it is too large for your server to handle. Thumbnails and watermarks may not have been applied.', 'sunshine-photo-cart' ) ); ?></strong></li>' );
						$( '#sunshine-gallery-images-processing div.status span.current-file' ).html( 'ERROR' );
					}
				}).always(function(){
					processed_images++;
					total_images++;
					$( '#sunshine-gallery-images-processing span.processed' ).html( processed_images );
					if ( processed_images >= images_to_upload ) {
						// When done
						$( '#sunshine-ftp-new-images' ).hide();
						$( '#sunshine-gallery-images-processing div.status' ).html( 'Image import complete!' );
						$( '#sunshine-gallery-images-processing' ).addClass( 'success' ).delay( 2000 ).fadeOut( 400 );
					}
					$( document ).trigger( 'refresh_images' );
				});
			}

			return false;
		});


		/**********
		REFRESHING IMAGES ACTION
		**********/
		$( document ).on( 'refresh_images', function() {

			$( 'input[name="selected_images"]' ).val( image_ids.join( ',' ) );
			$( '.sunshine-gallery-image-count' ).html( total_images );
			if ( total_images > 0 ) {
				$( '#sunshine-gallery-select-all' ).show();
			} else {
				$( '#sunshine-gallery-select-all' ).hide();
			}

		});

		$( document ).trigger( 'refresh_images' );

   });
  </script>

	<?php
}

function sunshine_admin_gallery_image_thumbnail( $image, $echo = true ) {

	if ( is_numeric( $image ) ) {
		$image = sunshine_get_image( $image );
	}

	$html  = '<li id="image-' . esc_attr( $image->get_id() ) . '" data-image-id="' . esc_attr( $image->get_id() ) . '">';
	$html .= '<div class="sunshine-image-container"><img src="' . $image->get_image_url() . '" data-image-id="' . esc_attr( $image->get_id() ) . '" alt="" /></div>';
	$html .= '<span class="sunshine-image-actions">';
	$html .= '<a href="post.php?post=' . esc_attr( $image->get_id() ) . '&action=edit" class="sunshine-image-edit dashicons dashicons-edit"  data-image-id="' . esc_attr( $image->get_id() ) . '" target="_blank"></a> ';
	$html .= '<a href="#" class="sunshine-image-delete dashicons dashicons-trash remove" data-image-id="' . esc_attr( $image->get_id() ) . '"></a> ';
	$html .= '<a href="#" class="sunshine-image-featured dashicons dashicons-star-filled" data-image-id="' . esc_attr( $image->get_id() ) . '"></a> ';
	$html .= '</span>';
	$html .= '<span class="sunshine-image-name">' . esc_html( $image->get_name( 'filename' ) ) . '</span>';
	$html = apply_filters( 'sunshine_admin_gallery_image_item', $html, $image );
	$html .= '</li>';

	if ( $echo ) {
		echo $html;
		return;
	}
	return $html;

}

// Ajax action to refresh the selected images
add_action( 'wp_ajax_sunshine_gallery_refresh_images', 'sunshine_gallery_get_refreshed_images' );
function sunshine_gallery_get_refreshed_images() {
	if ( isset( $_GET['image_ids'] ) && is_array( $_GET['image_ids'] ) ) {
		$image_html = '';
		foreach ( $_GET['image_ids'] as $image_id ) {
			$image_html .= sunshine_admin_gallery_image_thumbnail( intval( $image_id ), false );
		}
		wp_send_json_success(
			array(
				'image_html' => $image_html,
			)
		);
	} else {
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_sunshine_gallery_add_media_images', 'sunshine_gallery_add_media_images' );
function sunshine_gallery_add_media_images() {

	check_ajax_referer( 'sunshine_gallery_add_media_images', 'security' );

	$gallery_id = intval( $_POST['gallery_id'] );
	$image_ids  = array_map( 'intval', $_POST['image_ids'] );
	$gallery    = sunshine_get_gallery( $gallery_id );
	$gallery->set_image_ids( $image_ids );

	/*
	 Don't need this as we are not allowing things to be moved into this gallery
	// Set these images to this gallery
	$i = 0;
	foreach ( $image_ids as $image_id ) {
		wp_update_post(array(
			'ID' => $image_id,
			'post_parent' => $gallery_id,
			'menu_order' => $i++
		));
	}
	*/

	$image_html = '';
	foreach ( $image_ids as $image_id ) {
		$image_html .= sunshine_admin_gallery_image_thumbnail( $image_id, false );
	}
	wp_send_json_success(
		array(
			'image_html' => $image_html,
		)
	);

}

add_action( 'wp_ajax_sunshine_gallery_upload', 'sunshine_gallery_admin_ajax_upload' );
function sunshine_gallery_admin_ajax_upload() {

	check_ajax_referer( 'sunshine_gallery_upload', 'security' );

	$file           = $_FILES['sunshine_gallery_image'];
	$result         = array();
	$result['file'] = sanitize_file_name( $file['name'] );

	$file_info = wp_check_filetype( basename( $_FILES['sunshine_gallery_image']['name'] ) );
	$allowed_file_extensions = sunshine_allowed_file_extensions();
	if ( empty( $file_info['ext'] ) || ! in_array( strtolower( $file_info['ext'] ), $allowed_file_extensions ) ) {
		$result['error']  = __( 'Invalid file type', 'sunshine-photo-cart' );
		wp_send_json_error( $result );
		exit;
	}

	$gallery_id = intval( $_POST['gallery_id'] );

	sunshine_doing_upload( $gallery_id );

	$file_upload    = wp_handle_upload(
		$file,
		array(
			'test_form' => true,
			'action'    => 'sunshine_gallery_upload',
		)
	);
	$post_parent_id = $gallery_id;

	// Only add images to the gallery as attachment, otherwise we just upload the file into the folder.
	if ( strpos( $file_upload['type'], 'image' ) !== false ) {
		sunshine_insert_gallery_image( $file_upload['file'], $post_parent_id );
	}

}

function sunshine_insert_gallery_image( $file_path, $gallery_id, $result = 'json' ) {

	$file_type = wp_check_filetype( $file_path );
	$file_name = basename( $file_path );

	// Adds file as attachment to WordPress
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $file_type['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'comment_status' => 'inherit',
			'ping_status'    => 'inherit',
			// 'menu_order' => $menu_order
		),
		$file_path,
		$gallery_id
	);

	if ( ! is_wp_error( $attachment_id ) ) {

		// Use meta value to store all image IDs for gallery
		$gallery   = sunshine_get_gallery( $gallery_id );
		$image_ids = $gallery->add_image_id( $attachment_id );

		$sizes = get_intermediate_image_sizes();

		$attachment_image_meta = wp_generate_attachment_metadata( $attachment_id, $file_path );
		$image_meta            = $attachment_image_meta['image_meta'];
		$update_args           = array();
		if ( '' != trim( $image_meta['title'] ) ) {
			$update_args['post_title'] = trim( $image_meta['title'] );
		}
		if ( '' != trim( $image_meta['caption'] ) ) {
			$update_args['post_content'] = trim( $image_meta['caption'] );
		}
		if ( ! empty( $update_args ) ) {
			$update_args['ID'] = $attachment_id;
			wp_update_post( $update_args );
		}

		$created_timestamp = current_time( 'timestamp' );
		if ( ! empty( $image_meta['created_timestamp'] ) ) {
			$created_timestamp = $image_meta['created_timestamp'];
		}
		add_post_meta( $attachment_id, 'created_timestamp', $created_timestamp );
		add_post_meta( $attachment_id, 'sunshine_file_name', $file_name );

		do_action( 'sunshine_after_image_process', $attachment_id, $file_path );
		$attachment_meta_data = wp_update_attachment_metadata( $attachment_id, $attachment_image_meta );

		if ( 'json' === $result ) {
			$return['image_id']   = $attachment_id;
			$return['file_name']  = $file_name;
			$return['image_html'] = sunshine_admin_gallery_image_thumbnail( $attachment_id, false );
			wp_send_json_success( $return );
		} else {
			return $attachment_id;
		}
	}

	if ( 'json' === $result ) {
		wp_send_json_error();
	} else {
		return $attachment_id;
	}

}

add_action( 'wp_ajax_sunshine_gallery_image_sort', 'sunshine_gallery_image_sort' );
function sunshine_gallery_image_sort() {

	check_ajax_referer( 'sunshine_gallery_image_sort', 'security' );

	$images = sanitize_text_field( $_POST['images'] );
	$images = str_replace( 'image-', '', $images );
	$sorted_image_ids = explode( ',', $images );

	$gallery = sunshine_get_gallery( intval( $_POST['gallery_id'] ) );

	// The image ids passed are only what is currently visible. There could be more in the gallery because of pagination.
	// So we are only going to rebuild the same amount of gallery image ids that are passsed.

	// Get image ids first.
	$existing_image_ids = $gallery->get_image_ids();

	// Count how many image ids we were sent.
	$sorted_image_count = count( $sorted_image_ids );

	// Chop off that number of image ids from the start of the existing list.
	$existing_image_ids = array_slice( $existing_image_ids, $sorted_image_count );

	// Add the new list to the start of the existing list.
	$final_image_ids = array_merge( $sorted_image_ids, $existing_image_ids );

	$gallery->set_image_ids( $final_image_ids );

	wp_send_json_success();
}

add_action( 'wp_ajax_sunshine_gallery_load_more', 'sunshine_gallery_load_more' );
function sunshine_gallery_load_more() {

	check_ajax_referer( 'sunshine_gallery_load_more', 'security' );

	$gallery = sunshine_get_gallery( intval( $_POST['gallery_id'] ) );
	$images  = $gallery->get_images(
		array(
			'posts_per_page' => $_POST['count'],
			'offset'         => intval( $_POST['offset'] ),
		)
	);
	if ( empty( $images ) ) {
		wp_send_json_error();
	}
	$image_html = '';
	foreach ( $images as $image ) {
		$image_html .= sunshine_admin_gallery_image_thumbnail( $image, false );
	}
	wp_send_json_success(
		array(
			'image_html' => $image_html,
		)
	);
}


add_action( 'wp_ajax_sunshine_gallery_image_delete', 'sunshine_gallery_image_delete' );
function sunshine_gallery_image_delete() {

	check_ajax_referer( 'sunshine_gallery_image_delete', 'security' );

	$image_id   = intval( $_POST['image_id'] );
	$gallery_id = intval( $_POST['gallery_id'] );
	if ( ! empty( $image_id ) && ! empty( $gallery_id ) ) {
		$gallery = sunshine_get_gallery( $gallery_id );
		$result  = $gallery->delete_image( $image_id );
		if ( $result ) {
			wp_send_json_success( array( 'image_id' => $image_id ) );
		}
	}
	wp_send_json_error();
}

add_action( 'wp_ajax_sunshine_gallery_image_featured', 'sunshine_gallery_image_featured' );
function sunshine_gallery_image_featured() {

	check_ajax_referer( 'sunshine_gallery_image_featured', 'security' );

	$image_id   = intval( $_POST['image_id'] );
	$gallery_id = intval( $_POST['gallery_id'] );
	if ( ! empty( $image_id ) && ! empty( $gallery_id ) ) {
		$current_post_thumbnail_id = get_post_thumbnail_id( $gallery_id );
		if ( $image_id == $current_post_thumbnail_id ) {
			set_post_thumbnail( $gallery_id, 1 );
			wp_send_json_success(
				array(
					'image_id'  => $image_id,
					'image_url' => '',
				)
			);
		} elseif ( set_post_thumbnail( $gallery_id, $image_id ) ) {
			sunshine_make_cover_size_image( $gallery_id );
			wp_send_json_success(
				array(
					'image_id'  => $image_id,
					'image_url' => wp_get_attachment_image_url( $image_id, 'sunshine-thumbnail' ),
				)
			);
		}
	}
	wp_send_json_error();
}

add_action( 'save_post', 'sunshine_make_cover_size_image', 10, 2 );
function sunshine_make_cover_size_image( $post_id, $post = '' ) {

	if ( 'sunshine-gallery' != get_post_type( $post_id ) || SPC()->get_option( 'theme_gallery' ) != 'cover' ) {
		return;
	}

	$gallery = sunshine_get_gallery( $post_id );
	$featured_image_id = $gallery->get_featured_image_id();
	if ( empty( $featured_image_id ) ) {
		return;
	}

	$full_image_path = get_attached_file( $featured_image_id );
	$metadata = wp_get_attachment_metadata( $featured_image_id );

	// Check if image already exists, don't remake if it does.
	if ( ! empty( $metadata['sizes']['sunshine-cover']['file'] ) ) {
		$existing_image_path = str_replace(
			wp_basename( $full_image_path ),
			wp_basename( $metadata['sizes']['sunshine-cover']['file'] ),
			$full_image_path
		);
		if ( file_exists( $existing_image_path ) ) {
			return;
		}
	}

	// Make the new cover image and save to metadata.
	$image_data = image_make_intermediate_size( $full_image_path, 1800, 1800, false );
	$metadata['sizes']['sunshine-cover'] = $image_data;
	wp_update_attachment_metadata( $featured_image_id, $metadata );

}


add_action( 'save_post', 'sunshine_gallery_save_post', 10, 2 );
function sunshine_gallery_save_post( $post_id, $post = '' ) {

	if ( 'sunshine-gallery' != get_post_type( $post_id ) ) {
		return;
	}

	$gallery = sunshine_get_gallery( $post_id );

	// Set default password if none provided and status is password.
	if ( $gallery->get_status() == 'password' && empty( $gallery->get_password() ) ) {
		$gallery->update_meta_value( 'password', strtoupper( sunshine_random_string( 10 ) ) );
	}

	if ( SPC()->get_option( 'theme' ) != 'cover' ) {
		return;
	}

	// Create cover image if using Cover theme.
	$featured_image_id = $gallery->get_featured_image_id();
	if ( empty( $featured_image_id ) ) {
		return;
	}

	$full_image_path = get_attached_file( $featured_image_id );
	$metadata = wp_get_attachment_metadata( $featured_image_id );

	// Check if image already exists, don't remake if it does.
	if ( ! empty( $metadata['sizes']['sunshine-cover']['file'] ) ) {
		$existing_image_path = str_replace(
			wp_basename( $full_image_path ),
			wp_basename( $metadata['sizes']['sunshine-cover']['file'] ),
			$full_image_path
		);
		if ( file_exists( $existing_image_path ) ) {
			return;
		}
	}

	// Make the new cover image and save to metadata.
	$image_data = image_make_intermediate_size( $full_image_path, 1800, 1800, false );
	$metadata['sizes']['sunshine-cover'] = $image_data;
	wp_update_attachment_metadata( $featured_image_id, $metadata );

}

add_filter( 'sunshine_meta_gallery_images_validate', 'sunshine_meta_gallery_images_unique' );
function sunshine_meta_gallery_images_unique( $image_ids ) {
	return $image_ids;
}

// Attempt to not have an image uploaded as Featured Image automatically be part of the gallery
add_filter( 'wp_insert_attachment_data', 'sunshine_featured_image_upload_situation', 10, 2 );
function sunshine_featured_image_upload_situation( $data, $postarr ) {
	$screen = get_current_screen();
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'upload-attachment' && $screen->id == 'async-upload' ) {
		if ( ! empty( $data['post_parent'] ) && get_post_type( $data['post_parent'] ) == 'sunshine-gallery' ) {
			$data['post_parent'] = 0;
		}
	}
	return $data;
}

/*************
IMPORTING FROM FTP FOLDER
 **************/
function sunshine_get_import_directory() {
	$upload_dir = wp_upload_dir();
	return apply_filters( 'sunshine_import_directory', $upload_dir['basedir'] . '/sunshine/upload' );
}

function sunshine_directory_to_options( $path = __DIR__, $selected_dir = '', $level = 0 ) {
	$items = scandir( $path );
	if ( ! empty( $items ) ) {
		foreach ( $items as $item ) {
			if ( is_numeric( $item ) || is_numeric( str_replace( '-download', '', $item ) ) ) {
				continue; // Skip number folders, those were created by Sunshine
			}
			if ( strpos( $item, '.' ) === 0 ) {
				continue;
			}
			$fullpath = $path . '/' . $item;
			if ( is_dir( $fullpath ) ) {
				$count = sunshine_image_folder_count( $fullpath );
				// if ( $count > 0 ) {
					$name                   = str_repeat( '&nbsp;', $level * 3 ) . $item;
					$path_array             = array_reverse( explode( '/', $fullpath ) );
					$this_folder_path_array = array_slice( $path_array, 0, $level + 1 );
					$value                  = join( '/', array_reverse( $this_folder_path_array ) );
					echo '<option value="' . esc_attr( $value ) . '" data-count="' . intval( $count ) . '" ' . selected( $selected_dir, $value, 0 ) . '>' . esc_html( $name ) . ' (' . $count . ' ' . __( 'images', 'sunshine-photo-cart' ) . ')</option>';
				// }
				sunshine_directory_to_options( $fullpath, $selected_dir, $level + 1 );
			}
		}
	}
}

add_action( 'wp_ajax_sunshine_gallery_import', 'sunshine_ajax_gallery_import' );
function sunshine_ajax_gallery_import() {

	$gallery_id  = intval( $_POST['gallery_id'] );
	$gallery     = sunshine_get_gallery( $gallery_id );
	$item_number = intval( $_POST['item_number'] );
	$dir         = sanitize_text_field( $_POST['dir'] );

	// Check if the image already exists in the gallery
	$existing_file_names = array();
	$existing_image_ids  = $gallery->get_image_ids();
	if ( ! empty( $existing_image_ids ) ) {
		foreach ( $existing_image_ids as $existing_image_id ) {
			$existing_file_names[] = get_post_meta( $existing_image_id, 'sunshine_file_name', true );
		}
	}

	$folder = sunshine_get_import_directory() . '/' . $dir;
	$images = sunshine_get_images_in_folder( $folder );

	$file_path = $images[ $item_number - 1 ];
	$file_name = basename( $file_path );

	if ( is_array( $existing_file_names ) && in_array( $file_name, $existing_file_names ) ) {
		wp_send_json_error(
			array(
				'file_name' => $file_name,
				'error'     => __( 'Already uploaded to gallery', 'sunshine-photo-cart' ),
			)
		);
	}

	sunshine_doing_upload( $gallery_id );

	update_post_meta( $gallery_id, 'images_directory', $dir );

	// Make sure we have a unique file name in the directory we are moving it to
	$upload_dir    = wp_upload_dir();
	$new_file_name = wp_unique_filename( $upload_dir['path'], $file_name );

	// copy the file to the uploads dir
	$new_file_path = $upload_dir['path'] . '/' . $new_file_name;
	if ( false === @copy( $file_path, $new_file_path ) ) {
		wp_send_json_error(
			array(
				'file'  => $file_name,
				'error' => new WP_Error( 'upload_error', sprintf( __( 'The selected file could not be copied to %s.', 'sunshine-photo-cart' ), $upload_dir['path'] ) ),
			)
		);
	}

	// Set correct file permissions
	$stat  = stat( dirname( $new_file_path ) );
	$perms = $stat['mode'] & 0000666;
	@ chmod( $new_file_path, $perms );
	$url = $upload_dir['url'] . '/' . $new_file_name;

	sunshine_insert_gallery_image( $new_file_path, $gallery_id );

}

add_action( 'sunshine_meta_gallery_emails_display', 'sunshine_meta_gallery_emails_display' );
function sunshine_meta_gallery_emails_display() {
	global $post;
	$gallery = sunshine_get_gallery( $post->ID );
	$emails  = $gallery->get_emails();
	if ( ! empty( $emails ) ) {
		echo join( '<br />', $emails );
	} else {
		_e( 'No emails collected yet', 'sunshine-photo-cart' );
	}
}

// Can't get this to work with regeneration, so hold off for now.
// add_filter( 'wp_generate_attachment_metadata', 'sunshine_unique_image_filenames', 10, 2 );
function sunshine_unique_image_filenames( $metadata, $attachment_id ) {

	if ( SPC()->get_option( 'unique_filenames' ) && ( ! isset( $_POST['action'] ) || $_POST['action'] != 'sunshine_regenerate_image' ) ) {

		$file = get_attached_file( $attachment_id );
		$pathinfo = pathinfo( $file );
		$dirname = $pathinfo['dirname'];
		$ext = $pathinfo['extension'];
		$uploads = wp_upload_dir();
		$base_name = basename( $file, '.' . $ext );

		// Append unique string to the original file name
		$original_new_filename = $base_name . '-' . uniqid( time() . '_', true ) . '.' . $ext;
		$original_new_filename = wp_unique_filename( $dirname, $original_new_filename );
		$original_new_path = $dirname . '/' . $original_new_filename;
		if ( @rename( $file, $original_new_path ) ) {
			update_attached_file( $attachment_id, $original_new_path );
			$metadata['file'] = trailingslashit( $uploads['subdir'] ) . $original_new_filename;
		} else {
			SPC()->log( "Error: Unable to rename original file from {$file} to {$original_new_path}" );
		}

		// Append unique string to the resized image file names
		if ( !empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size => $size_data ) {
				$resized_base_name = basename( $size_data['file'], '.' . $ext );
				$new_filename = $resized_base_name . '-' . uniqid( time() . '_', true ) . '.' . $ext;
				$new_filename = wp_unique_filename( $dirname, $new_filename );

				// Rename the resized image
				$old_path = $dirname . '/' . $size_data['file'];
				$new_path = $dirname . '/' . $new_filename;

				if ( @rename( $old_path, $new_path ) ) {
					// Update the metadata with the new file name
					$metadata['sizes'][$size]['file'] = $new_filename;
				} else {
					SPC()->log( "Error: Unable to rename file from {$old_path} to {$new_path}" );
				}
			}
		}

	}

    return $metadata;
}

add_action( 'wp', 'sunshine_admin_gallery_check' );
function sunshine_admin_gallery_check() {
    global $pagenow;

    if ( empty( $_GET['s'] ) && $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'sunshine-gallery' ) {
        $query = new WP_Query( array(
            'post_type' => 'sunshine-gallery',
            'post_status' => array( 'any', 'trash' ),
        ) );
        if ( $query->found_posts == 0 ) {
			echo '<style>.wrap { display: none; }</style>';
			add_thickbox();
            add_action( 'admin_notices', 'sunshine_admin_no_galleries' );
        }
    }
}

add_filter( 'enter_title_here', 'sunshine_gallery_enter_title_here', 10, 2 );
function sunshine_gallery_enter_title_here( $title, $post ) {
	if ( 'sunshine-gallery' === $post->post_type ) {
		return __( 'Add gallery name', 'sunshine-photo-cart' );
	}
	return $title;
}

function sunshine_admin_no_galleries() {
	sunshine_get_template( 'admin/no-galleries' );
}

add_filter( 'display_post_states', 'sunshine_gallery_post_states', 10, 2 );
function sunshine_gallery_post_states( $post_states, $post ) {

	$gallery = sunshine_get_gallery( $post );
	$status = $gallery->get_status();

	if ( 'password' == $status ) {
		$post_states['sunshine_gallery_password'] = __( 'Password Protected', 'sunshine-photo-cart' );
	} elseif ( 'private' == $status ) {
		$post_states['sunshine_gallery_private'] = __( 'Private', 'sunshine-photo-cart' );
	}

	return $post_states;

}

//add_filter( 'as3cf_pre_upload_attachment', 'sunshine_s3_offload_pre_upload_attachment', 10, 3 );
function sunshine_s3_offload_pre_upload_attachment( $abort, $post_id, $metadata ) {

    // Get the post object for the attachment
    $post = get_post( $post_id );

    // Check if the post object is retrieved successfully
    if ( ! $post ) {
        return $abort;
    }

    // Get the current time and post time as DateTime objects
    $currentTime = new DateTime('now', new DateTimeZone('UTC'));
    $postTime = new DateTime( $post->post_date_gmt );

    // Calculate the difference in time
    $interval = $currentTime->diff( $postTime );

    // Check if the difference is less than 6 hours
    if ( $interval->h < 6 && $interval->days == 0 ) {
        return true; // The upload date is less than 6 hours old
    }

    // If not, return the original value of $abort
    return $abort;
}
