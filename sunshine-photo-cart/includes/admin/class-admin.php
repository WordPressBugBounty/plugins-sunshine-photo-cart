<?php

class Sunshine_Admin {

	protected $notices;
	protected $tabs = array();
	private $needs_setup = false;

	public function __construct() {

		add_action( 'wp_loaded', array( $this, 'needs_setup' ) );

		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_action( 'in_admin_header', array( $this, 'in_admin_header' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
		add_action( 'sunshine_header_links', array( $this, 'header_links' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_filter( 'display_post_states', array( $this, 'post_states' ), 10, 2 );

		add_action( 'admin_init', array( $this, 'update_check' ) );
		add_action( 'admin_init', array( $this, 'warnings' ) );

		add_filter( 'jpeg_quality', array( $this, 'image_quality' ) );
		// add_filter( 'wp_image_editors', array( $this, 'force_imagick' ) );
		add_filter( 'intermediate_image_sizes', array( $this, 'image_sizes' ), 99999, 1 );
		add_filter( 'big_image_size_threshold', array( $this, 'big_image_size_threshold' ), 999, 4 );

		add_action( 'save_post', array( $this, 'flush_rewrite_page_save' ) );

		// Filtering out images from galleries in media library if needed.
		add_filter( 'ajax_query_attachments_args', array( $this, 'clean_media_library' ) );
		add_filter( 'pre_get_posts', array( $this, 'media_library_list' ) );

		// Show the links on the Plugins page.
		add_filter( 'plugin_action_links_sunshine-photo-cart-v3/sunshine-photo-cart.php', array( $this, 'plugin_action_links' ) );

		// Add link to main Sunshine page in admin bar top left.
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_view_client_galleries' ), 768 );

		// Don't let them delete the core Order Statuses.
		add_action( 'admin_head', array( $this, 'order_status_admin_customizations' ) );

		// Post updated status messages.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );

		// Show notice if logging is enabled so it doesn't stay on forever.
		add_action( 'admin_notices', array( $this, 'logging_notice' ), 5 );

		// Check that Sunshine has been installed.
		add_action( 'admin_notices', array( $this, 'install_notice' ), 5 );

		// Promo notices
		add_action( 'admin_notices', array( $this, 'promo_notice' ), 5 );

		// Fal;back image size because we do all custom image sizes.
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'custom_fallback_image_size' ), 10, 3 );

		// Sorting.
		add_action( 'admin_footer', array( $this, 'sortable' ), 9999 );
		add_action( 'wp_ajax_sunshine_term_sort', array( $this, 'term_sort' ) );
		add_action( 'wp_ajax_sunshine_post_sort', array( $this, 'post_sort' ) );
		add_filter( 'pre_get_posts', array( $this, 'pre_get_posts_sort' ) );
		add_filter( 'create_term', array( $this, 'create_term' ), 10, 3 );
		add_filter( 'edit_term', array( $this, 'create_term' ), 10, 3 );
		add_filter( 'terms_clauses', array( $this, 'term_clauses' ), 10, 3 );

		// Logging deletion of data.
		add_action( 'before_delete_post', array( $this, 'delete_post' ), 10, 2 );

		add_action( 'current_screen', array( $this, 'load_theme_functions' ), 999 );

		// Menu.
		add_action( 'admin_menu', array( $this, 'menu' ) );

		add_action( 'admin_init', array( $this, 'deactivate_old_addons' ) );

		// Attachments.
		add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields' ), 10, 2 );
		add_filter( 'attachment_fields_to_save', array( $this, 'save_attachment_fields' ), 10, 2 );

		add_action( 'add_meta_boxes', array( $this, 'attachment_meta_box_setup' ) );

		// Debugging helpers.
		add_action( 'admin_notices', array( $this, 'show_post_debug' ) );

		// Tell people about caching plugin issues.
		add_action( 'admin_notices', array( $this, 'plugin_conflicts' ) );

		// Clear log.
		add_action( 'admin_init', array( $this, 'clear_log' ) );

		add_filter( 'install_plugins_tabs', array( $this, 'plugin_search_tabs' ) );
		add_action( 'admin_init', array( $this, 'plugin_search_tabs_go' ) );

	}

	public function deactivate_old_addons() {
		// Get the list of all active plugins
		$active_plugins = get_option( 'active_plugins' );

		$addons = array();

		// Loop through each active plugin
		foreach ( $active_plugins as $plugin_path ) {
			// Get plugin data using WordPress function
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path );

			// Check if the plugin folder name starts with 'sunshine-'
			if ( strpos( $plugin_path, 'sunshine-' ) === 0 && $plugin_path != 'sunshine-photo-cart' ) {
				// Get the version of the plugin
				$version = $plugin_data['Version'];

				// Check if the version is less than 3.0
				if ( version_compare( $version, '2.99', '<' ) ) {
					// Add plugin name to error list
					$addons[] = $plugin_data['Name'];
					// Deactivate the plugin
					deactivate_plugins( $plugin_path );
				}
			}
		}

		if ( ! empty( $addons ) && is_array( $addons ) ) {
			$message = '<p>The following add-on plugins for Sunshine Photo Cart have been deactivated because they are no longer compatible with Sunshine 3. Do not worry, no information is lost and you can get the updated versions of each easily! <a href="https://www.sunshinephotocart.com/docs/sunshine-3-update-and-deactivated-add-ons/" target="_blank">Learn what to do here</a></p>';
			$message .= '<p>' . join( '<br />', $addons ) . '</p>';
			SPC()->notices->add_admin( 'old_addons', $message, 'error' );
		}

		return $addons;

	}


	public function is_sunshine() {

		$screen = get_current_screen();

		$is_sunshine = false;

		if ( ! empty( $screen->post_type ) && in_array( $screen->post_type, SPC()->get_post_types() ) ) {
			$is_sunshine = true;
		}
		if ( strpos( $screen->id, 'sunshine' ) !== false ) {
			$is_sunshine = true;
		}
		if ( strpos( $screen->id, 'sunshine_addons' ) !== false ) {
			$is_sunshine = false;
		}

		return $is_sunshine;

	}

	public function load_theme_functions() {
		if ( $this->is_sunshine() ) {
			// Load Sunshine theme functions file.
			$theme = SPC()->get_option( 'theme' );
			$theme_functions_file = SUNSHINE_PHOTO_CART_PATH . 'themes/' . $theme . '/functions.php';
			if ( $theme && file_exists( $theme_functions_file ) ) {
				include_once $theme_functions_file;
			}
		}
	}

	public function is_page( $page ) {
		$screen = get_current_screen();

		if ( $screen->id == 'sunshine-gallery_page_sunshine-' . $page ) {
			return true;
		}

		return false;
	}

	function needs_setup() {
		if ( ! SPC()->get_option( 'address1' ) ) {
			$this->needs_setup = true;
		} elseif ( empty( sunshine_get_products() ) ) {
			$this->needs_setup = true;
		} elseif ( empty( sunshine_get_active_payment_methods() ) ) {
			$this->needs_setup = true;
		} elseif ( empty( sunshine_get_active_shipping_methods() ) ) {
			$this->needs_setup = true;
		} elseif ( ! SPC()->get_option( 'logo' ) ) {
			$this->needs_setup = true;
		}
	}

	public function admin_body_class( $classes ) {
		if ( $this->is_sunshine() ) {
			$classes .= ' sunshine';
		}
		return $classes;
	}

	public function in_admin_header() {
		if ( ! $this->is_sunshine() ) {
			return;
		}
		// Exclusions
		$screen = get_current_screen();
		if ( ( ( $screen->post_type == 'sunshine-gallery' || $screen->post_type == 'sunshine-product' ) && $screen->base == 'post' ) || $screen->base == 'sunshine-gallery_page_sunshine-install' || $screen->base == 'sunshine-gallery_page_sunshine-update' ) {
			return;
		}

		$header_links = apply_filters( 'sunshine_header_links', array() );

		sunshine_get_template( 'admin/header', array( 'tabs' => $this->tabs, 'header_links' => $header_links ) );

	}

	public function header_links( $links ) {
		$links = array(
			'documentation' => array(
				'url'   => 'https://www.sunshinephotocart.com/docs/',
				'label' => __( 'Documentation', 'sunshine-photo-cart' ),
			),
			'review'        => array(
				'url'   => 'https://wordpress.org/support/plugin/sunshine-photo-cart/reviews/#new-post',
				'label' => __( 'Write a Review', 'sunshine-photo-cart' ),
			),
			'upgrade'       => array(
				'url'   => 'https://www.sunshinephotocart.com/upgrade/',
				'label' => __( 'Upgrade', 'sunshine-photo-cart' ),
			),
		);
		if ( SPC()->is_pro() ) {
			unset( $links['upgrade'] );
			$links['support'] = array(
				'url'   => 'https://www.sunshinephotocart.com/support-ticket/',
				'label' => __( 'Submit Support Ticket', 'sunshine-photo-cart' ),
			);
		}
		return $links;
	}

	public function admin_footer() {
		if ( ! $this->is_sunshine() || SPC()->is_pro() ) {
			return;
		}
		// Exclusions
		$screen = get_current_screen();
		if ( ( ( $screen->post_type == 'sunshine-gallery' || $screen->post_type == 'sunshine-product' ) && $screen->base == 'post' ) || $screen->base == 'sunshine-gallery_page_sunshine-install' || $screen->base == 'sunshine-gallery_page_sunshine-update' ) {
			return;
		}

		sunshine_get_template( 'admin/upgrade-footer' );

	}

	public function admin_enqueue_scripts() {

		wp_enqueue_style( 'sunshine-icons', SUNSHINE_PHOTO_CART_URL . 'assets/css/icons.css' );
		wp_enqueue_style( 'sunshine-admin', SUNSHINE_PHOTO_CART_URL . 'assets/css/admin.css' );

		wp_register_script( 'ajaxq', SUNSHINE_PHOTO_CART_URL . 'assets/js/ajaxq.js', array( 'jquery' ), SUNSHINE_PHOTO_CART_VERSION );
		wp_register_script( 'chartjs', SUNSHINE_PHOTO_CART_URL . 'assets/js/chart.min.js', '', '3.8' );

		wp_register_script( 'select2', SUNSHINE_PHOTO_CART_URL . 'assets/js/select2/select2.min.js', array( 'jquery' ), '4.0.13' );
		wp_register_style( 'select2', SUNSHINE_PHOTO_CART_URL . 'assets/js/select2/select2.min.css', '4.0.13' );

		// Need this on all pages as we might need it for global actions like notices.
		wp_enqueue_script( 'sunshine-admin', SUNSHINE_PHOTO_CART_URL . 'assets/js/admin.js', array( 'jquery' ), SUNSHINE_PHOTO_CART_VERSION, true );
		wp_localize_script(
			'sunshine-admin',
			'sunshine_admin',
			array(
				'addon_security' => wp_create_nonce( 'sunshine_addon_toggle' ),
			),
		);

		if ( $this->is_sunshine() ) {

			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2' );
			wp_enqueue_style( 'farbtastic' );
			wp_enqueue_script( 'farbtastic' );
			wp_enqueue_script( 'ajaxq' );
			wp_enqueue_media();
		}

		if ( $this->is_page( 'reports' ) ) {
			wp_enqueue_script( 'chartjs' );
		}

	}

	public function post_states( $post_states, $post ) {

		if ( SPC()->get_option( 'page' ) == $post->ID ) {
			$post_states['sunshine_page'] = __( 'Sunshine Main Page', 'sunshine-photo-cart' );
		} elseif ( SPC()->get_option( 'page_account' ) == $post->ID ) {
			$post_states['sunshine_page_account'] = __( 'Sunshine Account', 'sunshine-photo-cart' );
		} elseif ( SPC()->get_option( 'page_cart' ) == $post->ID ) {
			$post_states['sunshine_page_cart'] = __( 'Sunshine Cart', 'sunshine-photo-cart' );
		} elseif ( SPC()->get_option( 'page_checkout' ) == $post->ID ) {
			$post_states['sunshine_page_checkout'] = __( 'Sunshine Checkout', 'sunshine-photo-cart' );
		} elseif ( SPC()->get_option( 'page_favorites' ) == $post->ID ) {
			$post_states['sunshine_page_favorites'] = __( 'Sunshine Favorites', 'sunshine-photo-cart' );
		} elseif ( SPC()->get_option( 'page_terms' ) == $post->ID ) {
			$post_states['sunshine_page_terms'] = __( 'Sunshine Terms & Conditions', 'sunshine-photo-cart' );
		}

		return $post_states;

	}

	public function update_check() {

		if ( isset( $_GET['summary'] ) ) {
			do_action( 'sunshine_send_summary' );
		}

		if ( version_compare( SPC()->version, SUNSHINE_PHOTO_CART_VERSION, '<' ) || isset( $_GET['sunshine_force_update'] ) ) {
			//sunshine_update();
		}

	}

	/* TODO: Check for various setting warnings and what not to notify the user of */
	public function warnings() {

		// When checking, might need to do transients so it is not getting checked on every admin page load

		// If shortcode option enabled but any of the existing pages do not have the shortcode
	}

	public function image_quality( $quality ) {
		if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'sunshine_gallery_upload' || $_POST['action'] == 'sunshine_gallery_import' ) && ! empty( SPC()->get_option( 'image_quality' ) ) ) {
			$quality = SPC()->get_option( 'image_quality' );
			if ( $quality ) {
				return $quality;
			}
		}
		return $quality;
	}

	function force_imagick( $editors ) {
		if ( extension_loaded( 'imagick' ) ) {
			$editors = array( 'WP_Image_Editor_Imagick' );
		}
		return $editors;
	}

	function plugin_action_links( $links ) {
		if ( ! SPC()->is_pro() ) {
			$upgrade_page = '<a href="https://www.sunshinephotocart.com/pricing/?utm_source=plugin&utm_medium=link&utm_campaign=upgrade" target="_blank"><b style="color: orange;">' . __( 'Upgrade', 'sunshine-photo-cart' ) . '</b></a>';
			array_unshift( $links, $upgrade_page );
		}
		return $links;
	}

	function admin_footer_text( $footer_text ) {
		global $typenow;

		if ( $typenow == 'sunshine-gallery' || $typenow == 'sunshine-product' || $typenow == 'sunshine-order' || $typenow == 'sunshine-product' || isset( $_GET['page'] ) && strpos( $_GET['page'], 'sunshine-photo-cart' ) !== false ) {
			$rate_text = sprintf(
				__( 'Thank you for using <a href="%1$s" target="_blank">Sunshine Photo Cart</a>! Please <a href="%2$s" target="_blank">rate us</a> on <a href="%2$s" target="_blank">WordPress.org</a>', 'sunshine-photo-cart' ),
				'https://www.sunshinephotocart.com?utm_source=plugin&utm_medium=link&utm_campaign=rate',
				'https://wordpress.org/support/view/plugin-reviews/sunshine-photo-cart?filter=5#postform'
			);

			return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . '</span>';
		}

		return $footer_text;

	}

	function flush_rewrite_page_save( $post_id ) {
		if ( $post_id == SPC()->get_option( 'page' ) ) {
			flush_rewrite_rules();
		}
	}

	function image_sizes( $image_sizes ) {
		if ( isset( $_POST['action'] ) && strpos( $_POST['action'], 'sunshine_') === 0 ) {
			$image_sizes = array( 'sunshine-thumbnail', 'sunshine-large' );
			$image_sizes = apply_filters( 'sunshine_image_sizes', $image_sizes );
		}
		return $image_sizes;
	}

	function big_image_size_threshold( $threshold, $imagesize, $file, $attachment_id ) {
		$attachment_parent_id = wp_get_post_parent_id( $attachment_id );
		if ( 'sunshine-gallery' == get_post_type( $attachment_parent_id ) ) {
			return false;
		}
		return $threshold;
	}


	function clean_media_library( $query ) {

		if ( isset( $_POST['action'] ) && $_POST['action'] = 'query-attachments' && isset( $_POST['post_id'] ) && get_post_type( $_POST['post_id'] ) == 'sunshine-gallery' ) {
			return $query;
		}

		if ( ! SPC()->get_option( 'show_media_library' ) ) {
			$args        = array(
				'post_type'   => 'sunshine-gallery',
				'nopaging'    => true,
				'post_status' => 'publish,private,trash',
				'fields'      => 'ids',
			);
			$gallery_ids = get_posts( $args );
			if ( ! empty( $gallery_ids ) ) {
				$query['post_parent__not_in'] = $gallery_ids;
			}
		}
		return $query;

	}

	function media_library_list( $query ) {
		if ( $query->is_main_query() && ! SPC()->get_option( 'show_media_library' ) ) {
			$screen = get_current_screen();
			if ( ! empty( $screen ) && $screen->base == 'upload' ) {
				$args        = array(
					'post_type'   => 'sunshine-gallery',
					'nopaging'    => true,
					'post_status' => array( 'publish', 'private', 'trash', 'draft', 'future' ),
					'fields'      => 'ids',
				);
				$gallery_ids = get_posts( $args );
				if ( ! empty( $gallery_ids ) ) {
					$query->set( 'post_parent__not_in', $gallery_ids );
				}
			}
		}
	}

	function admin_bar_view_client_galleries() {
		global $wp_admin_bar;
		if ( is_admin() ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'sunshine-client-galleries',
					'title'  => __( 'View Sunshine Galleries', 'sunshine-photo-cart' ),
					'href'   => get_permalink( SPC()->get_option( 'page' ) ),
					'parent' => 'site-name',
				)
			);
		}
	}

	function order_status_admin_customizations() {
		$screen = get_current_screen();
		if ( $screen->id == 'edit-sunshine-order-status' ) {
			$core_statuses  = sunshine_core_order_statuses();
			?>
			<script>
			jQuery( document ).ready( function($) {
				//$( '.bulkactions' ).remove();
				//$( '.inline-edit-row label:nth-child(2)' ).remove();
				<?php
				foreach ( $core_statuses as $slug ) {
					$term = get_term_by( 'slug', $slug, 'sunshine-order-status' );
				?>
					$( '#the-list #tag-<?php echo esc_js( $term->term_id ); ?> input' ).remove();
				<?php } ?>
						<?php
						if ( isset( $_GET['tag_ID'] ) ) {
							$current_status = get_term( intval( $_GET['tag_ID'] ) );
							if ( in_array( $current_status->slug, $core_statuses ) ) {
								?>
								$( '#delete-link' ).remove();
								$( '.form-table tr:nth-child(2) p.description' ).html( '<?php echo esc_js( __( 'The slug for core order statuses is not editable as it could break Sunshine functionality', 'sunshine-photo-cart' ) ); ?>' );
								$( '.form-field input[name="slug"]' ).attr( 'disabled', 'disabled' );
								<?php
							}
						}
						?>
			});
			</script>
			<?php
		}
	}

	function post_updated_messages( $messages ) {
		global $post;
		$messages['sunshine-order'][1]   = __( 'Order Updated', 'sunshine-photo-cart' );
		$messages['sunshine-gallery'][1] = sprintf( __( '<strong>Gallery updated</strong>, <a href="%s">view gallery</a>', 'sunshine-photo-cart' ), get_permalink( $post->ID ) );
		$messages['sunshine-gallery'][6] = sprintf( __( '<strong>Gallery created</strong>, <a href="%s">view gallery</a>', 'sunshine-photo-cart' ), get_permalink( $post->ID ) );
		return $messages;
	}

	function logging_notice() {
		if ( SPC()->get_option( 'enable_log' ) ) {
			SPC()->notices->add_admin( 'log', sprintf( __( 'Sunshine logging is enabled. <a href="%s">Please disable when no longer in use.</a>', 'sunshine-photo-cart' ), admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine' ) ), 'notice' );
		}
	}

	function install_notice() {
		if ( ! SPC()->get_option( 'install_time' ) ) {
			SPC()->notices->add_admin( 'install', sprintf( __( 'Sunshine installation process was skipped. <a href="%s">Please complete the installation process.</a>', 'sunshine-photo-cart' ), admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-install' ) ), 'notice' );
		}
	}

	function promo_notice() {
		$install_time = SPC()->get_option( 'install_time' );
		if ( ! SPC()->get_option( 'license_sunshine-photo-cart-pro' ) && ( ( time() - $install_time ) >= ( DAY_IN_SECONDS * 30 ) )  && ( empty( $_GET['page'] ) || $_GET['page'] != 'sunshine-install' ) ) {
			$discount_text = '<p>You have been using Sunshine Photo Cart for a while and that\'s great! However, it appears you are not enjoying the awesome features of having a Sunshine Pro bundle license (all the add-ons and 1-on-1 priority support).</p><p><strong>I am doing a limited-time offer of 50% off the first year of a Sunshine Pro annual license!</strong> Sunshine <em>rarely</em> does any kind of discounts.</p>
				<p><a href="https://www.sunshinephotocart.com/checkout/?promo=1&edd_action=add_to_cart&download_id=44&discount=9BF9253697&utm_source=plugin&utm_medium=notice&utm_content=9BF9253697&utm_campaign=PluginProUpgrade" target="_blank" class="button-primary notice-dismiss-button">Upgrade me please!</a> &nbsp;
				<a href="https://www.sunshinephotocart.com/upgrade/?discount=9BF9253697&utm_source=plugin&utm_medium=notice&utm_content=9BF9253697&utm_campaign=PluginProUpgrade" class="button" target="_blank">Learn more about Pro</a>
				</p>';
			SPC()->notices->add_admin( '9BF9253697', $discount_text, 'notice', true );
		}
	}


	function sortable() {
		$screen = get_current_screen();

		// Sortable product categories
		if ( $screen->id == 'edit-sunshine-product-category' || $screen->id == 'edit-sunshine-product-option' ) {
			?>
			<script>
			jQuery( document ).ready(function($){
				var item_list = jQuery( '#the-list' );
				item_list.sortable({
					update: function(event, ui) {
						item_list.addClass( 'sunshine-loading' );
						var category_order = item_list.sortable( 'toArray' ).toString();
						jQuery.ajax({
							type: 'POST',
							url: ajaxurl,
							dataType: 'json',
							data: {
								action: 'sunshine_term_sort',
								categories: category_order
							},
							success: function( result, textStatus, XMLHttpRequest) {
								item_list.removeClass( 'sunshine-loading' );
								return;
							},
							error: function( MLHttpRequest, textStatus, errorThrown ) {
								alert( 'Sorry, there was an error with your request' ); // TODO: Better error
							}
						});
					}
				});
			});
			</script>
			<?php
		}

		if ( $screen->id == 'edit-sunshine-product' || ( $screen->id == 'edit-sunshine-gallery' && SPC()->get_option( 'gallery_order' ) == 'menu_order' ) ) {
			?>
			<script>
			jQuery( document ).ready(function($){
				var item_list = jQuery( '#the-list' );
				item_list.sortable({
					update: function(event, ui) {
						item_list.addClass( 'sunshine-loading' );
						var post_order = item_list.sortable( 'toArray' ).toString();
						jQuery.ajax({
							type: 'POST',
							url: ajaxurl,
							dataType: 'json',
							data: {
								action: 'sunshine_post_sort',
								posts: post_order
							},
							success: function( result, textStatus, XMLHttpRequest) {
								item_list.removeClass( 'sunshine-loading' );
								return;
							},
							error: function( MLHttpRequest, textStatus, errorThrown ) {
								alert( 'Sorry, there was an error with your request' ); // TODO: Better error
							}
						});
					}
				});
			});
			</script>
			<?php
		}

	}

	public function term_sort() {
		$categories = sanitize_text_field( $_POST['categories'] );
		$categories = str_replace( 'tag-', '', $categories );
		$categories = explode( ',', $categories );
		$i          = 1;

		foreach ( $categories as $category_id ) {
			update_term_meta( $category_id, 'order', $i );
			$i++;
		}
	}

	public function post_sort() {
		$posts = sanitize_text_field( $_POST['posts'] );
		$posts = str_replace( 'post-', '', $posts );
		$posts = explode( ',', $posts );
		$i        = 1;
		foreach ( $posts as $post_id ) {
			wp_update_post(
				array(
					'ID'         => $post_id,
					'menu_order' => $i,
				)
			);
			$i++;
		}
	}

	public function pre_get_posts_sort( $query ) {

		if ( $query->get( 'post_type' ) == 'sunshine-product' ) {
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}

		return $query;

	}

	public function create_term( $term_id = 0, $tt_id = 0, $taxonomy = '' ) {
		if ( $taxonomy == 'sunshine-product-category' || $taxonomy == 'sunshine-product-option' ) {
			$order = get_term_meta( $term_id, 'order', true );
			if ( empty( $order ) ) {
				add_term_meta( $term_id, 'order', 1 );
			}
		}
	}

	public function term_clauses( $pieces, $taxonomies, $args ) {
		global $wpdb;
		if ( in_array( 'sunshine-product-category', $taxonomies ) || in_array( 'sunshine-product-option', $taxonomies ) ) {
			$pieces['join']   .= ' INNER JOIN ' . $wpdb->termmeta . ' AS tm ON t.term_id = tm.term_id ';
			$pieces['where']  .= ' AND tm.meta_key = "order"';
			$pieces['orderby'] = ' ORDER BY tm.meta_value + 0 ';
		}
		return $pieces;
	}

	public function delete_post( $post_id, $post ) {

		if ( ! in_array( $post->post_type, SPC()->get_post_types() ) ) {
			return;
		}

		SPC()->log( $post->post_title . ' (ID: ' . $post_id . ') has been permanently deleted' );

	}

	public function custom_fallback_image_size( $response, $attachment, $meta ) {

		// Check if a thumbnail is already available.
		if ( empty( $response['sizes']['thumbnail'] ) ) {
			$thumbnail = wp_get_attachment_image_src( $attachment->ID, 'sunshine-thumbnail' );
		    if ( $thumbnail ) {
		        // If a sunshine-thumbnail is available, set it as the thumbnail image.
		        $response['sizes']['thumbnail'] = array(
		            'height' => $thumbnail[2],
		            'width' => $thumbnail[1],
		            'url' => $thumbnail[0],
		        );
		    }
		}

	    return $response;

	}

	function menu() {
		global $menu, $submenu;

		/* TODO: Re-eval this whole thing */
		$counter     = '';
		$orders      = sunshine_get_orders( array( 'status' => 'new' ) );
		$order_count = count( $orders );
		if ( $order_count > 0 ) {
			$notifications = sprintf( _n( '%s order', '%s orders', $order_count, 'sunshine-photo-cart' ), number_format_i18n( $order_count ) );
			$counter       = sprintf( '<span class="sunshine-menu-count" aria-hidden="true">%1$d</span><span class="screen-reader-text">%2$s</span>', $order_count, $notifications );
			//$menu[47][0]  .= ' ' . $counter;
		}

		$sunshine_admin_submenu = array();

		// $sunshine_admin_submenu[110] = array( __( 'Add-Ons','sunshine-photo-cart' ), __( 'Add-Ons','sunshine-photo-cart' ), 'sunshine_manage_options', 'sunshine_addons', 'sunshine_addons' );
		$sunshine_admin_submenu[110] = array( __( 'Customers', 'sunshine-photo-cart' ), __( 'Customers', 'sunshine-photo-cart' ), 'sunshine_manage_options', 'sunshine-customers', 'sunshine_customers_page' );
		$sunshine_admin_submenu[120] = array( __( 'Reports', 'sunshine-photo-cart' ), __( 'Reports', 'sunshine-photo-cart' ), 'sunshine_manage_options', 'sunshine-reports', 'sunshine_reports_page' );
		$sunshine_admin_submenu[130] = array( __( 'Tools', 'sunshine-photo-cart' ), __( 'Tools', 'sunshine-photo-cart' ), 'sunshine_manage_options', 'sunshine-tools', 'sunshine_tools_page' );
		$sunshine_admin_submenu[996] = array( __( 'Add-ons', 'sunshine-photo-cart' ), __( 'Add-ons', 'sunshine-photo-cart' ), 'sunshine_manage_options', 'sunshine-addons', 'sunshine_addons_page' );
		//$sunshine_admin_submenu[997] = array( __( 'System Info', 'sunshine-photo-cart' ), __( 'System Info', 'sunshine-photo-cart' ), 'sunshine_manage_options', 'sunshine-system-info', 'sunshine_system_info_page' );

		if ( $this->needs_setup || ( isset( $_GET['page'] ) && $_GET['page'] == 'sunshine-install' ) ) {
			$sunshine_admin_submenu[998] = array( __( 'Setup Guide', 'sunshine-photo-cart' ), '<span class="sunshine-menu-highlight-link">' . __( 'Setup Guide', 'sunshine-photo-cart' ) . '</span>', 'sunshine_manage_options', 'sunshine-install', 'sunshine_install_page' );
		}

		if ( ! SPC()->is_pro() ) {
			$sunshine_admin_submenu[999] = array( __( 'Upgrade', 'sunshine-photo-cart' ), '<span class="sunshine-menu-highlight-link">' . __( 'Upgrade', 'sunshine-photo-cart' ) . '</span>', 'sunshine_manage_options', 'https://www.sunshinephotocart.com/upgrade/?utm_source=plugin&utm_medium=link&utm_campaign=menu' );
		}

		$sunshine_admin_submenu = apply_filters( 'sunshine_admin_menu', $sunshine_admin_submenu );
		ksort( $sunshine_admin_submenu );
		foreach ( $sunshine_admin_submenu as $key => $item ) {
			$page = add_submenu_page( 'edit.php?post_type=sunshine-gallery', $item[0], $item[1], $item[2], $item[3], ( ! empty( $item[4] ) ) ? $item[4] : '', $key );
		}

	}

	public function attachment_fields( $form_fields, $post ) {

		$parent_id = wp_get_post_parent_id( $post->ID );
		if ( $parent_id && get_post_type( $parent_id ) === 'sunshine-gallery' ) {

			unset( $form_fields['url'] );
			unset( $form_fields['menu_order'] );
			unset( $form_fields['image_url'] );
			unset( $form_fields['align'] );
			unset( $form_fields['image-size'] );

			$metadata = wp_get_attachment_metadata( $post->ID );

			if ( isset( $metadata['image_meta']['keywords'] ) && is_array( $metadata['image_meta']['keywords'] ) ) {
				$keywords = join( ', ', $metadata['image_meta']['keywords'] );
			}

			$form_fields['keywords'] = array(
				'label' => __( 'Keywords', 'sunshine-photo-cart' ),
				'input' => 'text',
				'value' => $keywords
			);

			// Define the options for the dropdown
			$price_levels = sunshine_get_price_levels();

			$image_price_level = get_post_meta( $post->ID, 'sunshine_price_level', true );

			// Build the dropdown HTML
			$html = '<select name="attachments[' . $post->ID . '][sunshine_price_level]">';
			$html .= '<option value="" ' . selected( $image_price_level, '', false ) . '>' . __( 'Use gallery price level', 'sunshine-photo-cart' ) . '</option>';
			foreach ( $price_levels as $price_level ) {
				$html .= '<option value="' . esc_attr( $price_level->get_id() ) . '" ' . selected( $image_price_level, $price_level->get_id(), false ) . '>' . $price_level->get_name() . '</option>';
			}
			$html .= '</select>';

			// Add the dropdown field
			$form_fields['sunshine_price_level'] = array(
				'label' => __( 'Price Level', 'sunshine-photo-cart' ),
				'input' => 'html',
				'html'  => $html,
			);

			// Retrieve the existing meta value for the 'sunshine_disable_purchase' field
			$disable_purchase = get_post_meta( $post->ID, 'sunshine_disable_purchase', true );

			// Add the checkbox field
			$form_fields['sunshine_disable_purchase'] = array(
				'label' => __( 'Disable Purchasing', 'textdomain' ),
				'input' => 'html',
				'html'  => "<input type='checkbox' name='attachments[{$post->ID}][sunshine_disable_purchase]' value='1' " . checked( $disable_purchase, 1, false ) . " />",
			);

		}

		return $form_fields;
	}

	public function save_attachment_fields( $post, $attachment ) {

		sunshine_log( $attachment, 'Saving attachment fields' );

		if ( isset( $attachment['keywords'] ) ) {
			$metadata = wp_get_attachment_metadata( $post['ID'] );
			$keywords = explode( ', ', $attachment['keywords'] );
			$keywords = array_map( 'trim', $keywords );  // Remove start and end spaces
			$metadata['image_meta']['keywords'] = $keywords;
			wp_update_attachment_metadata( $post['ID'], $metadata );
		}

		if ( isset( $attachment['sunshine_disable_purchase'] ) ) {
			update_post_meta( $post['ID'], 'sunshine_disable_purchase', sanitize_text_field( $attachment['sunshine_disable_purchase'] ) );
		} else {
			delete_post_meta( $post['ID'], 'sunshine_disable_purchase' );
		}

		if ( isset( $attachment['sunshine_price_level'] ) ) {
			update_post_meta( $post['ID'], 'sunshine_price_level', intval( $attachment['sunshine_price_level'] ) );
		} else {
			delete_post_meta( $post['ID'], 'sunshine_price_level' );
		}

		return $post;
	}

	public function attachment_meta_box_setup() {
		add_meta_box(
			'sunshine_image_meta_data',  // Unique ID
			__( 'Metadata', 'sunshine-photo-cart' ),
			array( $this, 'attachment_meta_box_display' ),     // Callback function
			'attachment',                      // Post type
			'side',                            // Context
			'default'                          // Priority
		);
	}

	public function attachment_meta_box_display( $post ) {
		$metadata = wp_get_attachment_metadata( $post->ID );
		if ( ! empty( $metadata['image_meta'] ) ) {
			foreach ( $metadata['image_meta'] as $key => $value ) {
				echo '<p><strong>' . $key . ':</strong><br />';
				if ( is_array( $value ) ) {
					echo join( ', ', $value );
				} else {
					echo $value;
				}
				echo '</p>';
			}
		}
	}

	public function show_post_debug() {

		if ( isset( $_GET['sunshine_debug'] ) && isset( $_GET['post'] ) ) {
			$meta = get_post_meta( intval( $_GET['post'] ) );
			echo '<!-- SUNSHINE DEBUG META -->';
			echo '<div class="notice">';
			sunshine_dump_var( $meta );
			echo '</div>';
		}

	}

	public function plugin_conflicts() {
		if (
			function_exists( 'w3tc_flush_post' ) ||
			function_exists( 'wp_cache_post_change' ) ||
			function_exists( 'rocket_clean_post' ) ||
			has_action( 'cachify_remove_post_cache' ) ||
			has_action( 'litespeed_purge_post' ) ||
			function_exists( 'wpfc_clear_post_cache_by_id' ) ||
			class_exists( 'WPO_Page_Cache' ) ||
			has_action( 'cache_enabler_clear_page_cache_by_post' ) ||
			has_action( 'breeze_clear_all_cache' ) ||
			class_exists( '\comet_cache' ) ||
			function_exists( 'sg_cachepress_purge_cache' ) ||
			is_callable( 'wpecommon::purge_varnish_cache' )
		) {
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'sunshine-install' ) {
				return;
			}
			$text = 'Sunshine Photo Cart has noticed you have caching on your site. <strong>This could prevent your galleries from working properly</strong> and it is highly recommended to enable some custom rules to ensure customers get the best experience. <a href="https://www.sunshinephotocart.com/docs/caching/" target="_blank" class="button-primary">Learn more</a>';
			SPC()->notices->add_admin( 'cache_plugin', $text, 'notice', true );
		}

	}

	public function clear_log() {

		if ( ! isset( $_GET['sunshine_clear_log'] ) || ! wp_verify_nonce( $_GET['sunshine_clear_log'], 'sunshine_clear_log' ) ) {
			return;
		}

		// Reset the file to be blank.
		file_put_contents( SPC()->log_file, '' );
		SPC()->notices->add_admin( 'clear_log_file', __( 'Log file has been cleared', 'sunshine-photo-cart' ) );

	}

	public function plugin_search_tabs( $views ) {
	    $views['sunshine-photo-cart'] = 'For Sunshine Photo Cart';
		return $views;
	}

	public function plugin_search_tabs_go() {
		global $pagenow;
		if ( 'plugin-install.php' == $pagenow && isset( $_GET['tab'] ) && $_GET['tab'] == 'sunshine-photo-cart' ) {
			wp_redirect( admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-addons' ) );
			exit;
		}
	}

}

$sunshine_admin = new Sunshine_Admin();
