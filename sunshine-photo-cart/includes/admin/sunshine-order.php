<?php

// Single order display
class SPC_Admin_Order {

	function __construct() {

		// Sorting/filtering
		add_filter( 'views_edit-sunshine-order', array( $this, 'views_edit' ), 999 );
		add_action( 'restrict_manage_posts', array( $this, 'filter_by_customer' ) );
		add_action( 'wp_ajax_sunshine_customer_search', array( $this, 'customer_search' ) );
		add_filter( 'bulk_actions-edit-sunshine-order', array( $this, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-sunshine-order', array( $this, 'handle_bulk_actions' ), 10, 3 );
		add_filter( 'parse_query', array( $this, 'parse_query' ) );

		// Searching.
		add_filter( 'posts_join', array( $this, 'order_search_join' ) );
		add_filter( 'posts_search', array( $this, 'order_search_where' ) );
		add_filter( 'posts_distinct', array( $this, 'order_search_distinct' ), 10, 2 );

		// Columns on main order page
		add_filter( 'manage_sunshine-order_posts_columns', array( $this, 'column_headers' ), 9999 );
		add_action( 'manage_sunshine-order_posts_custom_column', array( $this, 'column_data' ), 10, 2 );
		add_action( 'manage_edit-sunshine-order_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'sortable_query' ) );

		// Invoice
		add_action( 'admin_init', array( $this, 'invoice' ) );

		// Order meta boxes
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ), 9999 );

		/* Order edit tabs */
		add_action( 'sunshine_admin_order_tab_items', array( $this, 'items_tab' ) );
		add_action( 'sunshine_admin_order_tab_images', array( $this, 'images_tab' ) );
		add_action( 'sunshine_admin_order_tab_comments', array( $this, 'comments_tab' ) );
		add_action( 'sunshine_admin_order_tab_notes', array( $this, 'notes_tab' ) );
		add_action( 'sunshine_admin_order_tab_log', array( $this, 'log_tab' ) );
		add_action( 'sunshine_admin_order_tab_refunds', array( $this, 'refunds_tab' ) );

		// Save order
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'wp_ajax_sunshine_order_save_notes', array( $this, 'save_notes' ) );
		add_action( 'wp_ajax_sunshine_order_add_comment', array( $this, 'add_comment' ) );

		// Order actions
		add_action( 'admin_init', array( $this, 'process_order_action' ) );
		add_action( 'sunshine_order_process_action_resend_order_email', array( $this, 'resend_order_email' ) );

		// Change customer stats when orders are trashed/changed
		add_action( 'trashed_post', array( $this, 'trash' ) );
		add_action( 'untrashed_post', array( $this, 'untrash' ) );
		add_action( 'before_delete_post', array( $this, 'delete' ) );

	}

	function order_search_join( $join ) {
		global $pagenow, $wpdb;
	    if ( is_admin() && $pagenow == 'edit.php' && ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'sunshine-order' && ! empty( $_GET['s'] ) ) {
	        $join .= ' LEFT JOIN ' . $wpdb->postmeta . ' AS sunshine_order_meta ON ' . $wpdb->posts . '.ID = sunshine_order_meta.post_id ';
			$join .= ' LEFT JOIN ' . $wpdb->prefix . 'sunshine_order_items AS sunshine_order_items ON ' . $wpdb->posts . '.ID = sunshine_order_items.order_id ';
			$join .= ' LEFT JOIN ' . $wpdb->prefix . 'sunshine_order_itemmeta AS sunshine_order_itemmeta ON sunshine_order_items.order_item_id = sunshine_order_itemmeta.order_item_id ';
	    }
	    return $join;
	}

	function order_search_where( $where ) {
		global $pagenow, $wpdb;
	    if ( is_admin() && $pagenow == 'edit.php' && ! empty( $_GET['post_type'] ) && $_GET['post_type'] == 'sunshine-order' && ! empty( $_GET['s'] ) ) {
	        $where = preg_replace(
	       "/\(\s*" . $wpdb->posts . ".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
	       "(" . $wpdb->posts . ".post_title LIKE $1) OR (sunshine_order_meta.meta_value LIKE $1) OR (sunshine_order_itemmeta.meta_value LIKE $1)", $where );
	    }
	    return $where;
	}

	function order_search_distinct( $distinct, $query ) {
	    global $pagenow;

	    if ( is_admin() && 'edit.php' === $pagenow && $query->is_search() && $query->get( 'post_type' ) === 'sunshine-order' ) {
	        $distinct = "DISTINCT";
	    }

	    return $distinct;
	}


	function views_edit( $views ) {

		$views = array(); // Reset to stop other plugins from adding here

		$counts = wp_count_posts( 'sunshine-order' );

		$class        = ( ! isset( $_GET['sunshine-order-status'] ) ) ? 'current' : '';
		$views['all'] = '<a class="' . $class . '" href="?post_type=sunshine-order">' . __( 'All' ) . ' <span class="count">(' . array_sum( (array) $counts ) . ')</span></a>';

		$statuses = sunshine_get_order_statuses( 'object' );
		foreach ( $statuses as $status ) {
			$class                       = ( isset( $_GET['sunshine-order-status'] ) && $_GET['sunshine-order-status'] == $status->get_key() ) ? 'current' : '';
			$views[ $status->get_key() ] = '<a class="' . $class . '" href="?post_type=sunshine-order&amp;sunshine-order-status=' . $status->get_key() . '">' . $status->get_name() . ' <span class="count">(' . $status->get_count() . ')</span></a>';
		}

		if ( $counts->trash > 0 ) {
			$views['trash'] = '<a class="' . $class . '" href="?post_type=sunshine-order&post_status=trash">' . __( 'Trash' ) . ' <span class="count">(' . $counts->trash . ')</span></a>';
		}

		return $views;

	}

	function filter_by_customer() {
		if ( isset( $_GET['post_type'] ) && post_type_exists( $_GET['post_type'] ) && in_array( strtolower( $_GET['post_type'] ), array( 'sunshine-order' ) ) ) {
			$mode = ( isset( $_GET['mode'] ) ) ? sanitize_text_field( $_GET['mode'] ) : '';
			$selected_payment_method = ( isset( $_GET['payment_method'] ) ) ? sanitize_text_field( $_GET['payment_method'] ) : '';
			?>
			<select name="mode">
				<option value="" <?php selected( $mode, '' ); ?>><?php esc_html_e( 'All modes', 'sunshine-photo-cart' ); ?></option>
				<option value="live" <?php selected( $mode, 'live' ); ?>><?php esc_html_e( 'Live', 'sunshine-photo-cart' ); ?></option>
				<option value="test" <?php selected( $mode, 'test' ); ?>><?php esc_html_e( 'Test', 'sunshine-photo-cart' ); ?></option>
			</select>
			<select name="payment_method">
				<option value="" <?php selected( $mode, '' ); ?>><?php esc_html_e( 'All payment methods', 'sunshine-photo-cart' ); ?></option>
				<?php
				$payment_methods = sunshine_get_payment_methods();
				if ( ! empty( $payment_methods ) ) {
					foreach ( $payment_methods as $payment_method ) {
						echo '<option value="' . esc_attr( $payment_method->get_id() ) . '" ' . selected( $selected_payment_method, $payment_method->get_id(), false ) . '>' . $payment_method->get_name() . '</option>';
					}
				}
				?>
			</select>

			<select name="customer">
				<option value="" selected="selected"></option>
			</select>
			<script>
			jQuery( 'select[name="customer"]' ).select2({
				width: 200,
				minimumInputLength: 3,
				placeholder: '<?php echo esc_js( __( 'Filter by customer', 'sunshine-photo-cart' ) ); ?>',
				  ajax: {
					url: ajaxurl,
					delay: 1000,
					data: function( params ) {
						return {
							search: params.term,
							action: 'sunshine_customer_search',
						};
					},
					cache: true
				  }
			});
			</script>
			<?php
		}

	}

	function customer_search() {
		$data = array();
		if ( isset( $_GET['search'] ) ) {
			$customers = get_users(
				array(
					'search'         => '*' . sanitize_text_field( $_GET['search'] ) . '*',
					'search_columns' => array( 'user_login', 'user_email', 'first_name', 'last_name' ),
				)
			);
			if ( ! empty( $customers ) ) {
				foreach ( $customers as $customer ) {
					$customer = new SPC_Customer( $customer );
					$data[]   = array(
						'id'   => $customer->get_id(),
						'text' => $customer->get_name(),
					);
				}
			}
		}
		return wp_send_json( array( 'results' => $data ) );
	}

	function parse_query( $query ) {
		global $pagenow;
		if ( is_admin() && $query->is_main_query() && $pagenow == 'edit.php' && isset( $query->query_vars['post_type'] ) && $query->query_vars['post_type'] == 'sunshine-order' ) {
			if ( ! empty( $_GET['customer'] ) ) {
				$query->set( 'author', intval( $_GET['customer'] ) );
			}
			if ( ! empty( $_GET['mode'] ) ) {
				$meta_query[] = array(
					'key' => 'mode',
					'value' => sanitize_text_field( $_GET['mode'] ),
				);
			}
			if ( ! empty( $_GET['payment_method'] ) ) {
				$meta_query[] = array(
					'key' => 'payment_method',
					'value' => sanitize_text_field( $_GET['payment_method'] ),
				);
			}
			if ( ! empty( $meta_query ) ) {
				$meta_query['relation'] = 'AND';
				$query->set( 'meta_query', $meta_query );
			}
		}
		return $query;
	}


	function bulk_actions( $actions ) {

		unset( $actions['edit'] );

		$actions['sunshine_order_view_items'] = __( 'View ordered products', 'sunshine' );

		$statuses = sunshine_get_order_statuses( 'object' );
		foreach ( $statuses as $status ) {
			$actions[ 'sunshine_order_status_' . $status->get_key() ] = sprintf( __( 'Change status to: %s', 'sunshine' ), $status->get_name() );
		}
		return $actions;

	}

	function handle_bulk_actions( $redirect_to, $action, $ids ) {
		$changed = 0;
		if ( false !== strpos( $action, 'sunshine_order_status_' ) ) {
			$new_status = str_replace( 'sunshine_order_status_', '', $action );
			foreach ( $ids as $order_id ) {
				$order = new SPC_Order( $order_id );
				$order->set_status( $new_status );
				$order->notify( false );
				$changed++;
			}
		} elseif ( $action == 'sunshine_order_view_items' ) {

			// Get template
			if ( file_exists( TEMPLATEPATH . '/sunshine/templates/admin/order-items.php' ) ) {
				$template_path = TEMPLATEPATH . '/sunshine/templates/admin/order-items.php';
			} else {
				$template_path = SUNSHINE_PHOTO_CART_PATH . 'templates/admin/order-items.php';
			}

			ob_start();
				include $template_path;
				$output = ob_get_contents();
			ob_end_clean();

			echo $output;

			exit();
		}

		$redirect_to = add_query_arg(
			array(
				'post_type'   => 'sunshine-order',
				'bulk_action' => $action,
				'changed'     => $changed,
				'ids'         => join( ',', $ids ),
			),
			$redirect_to
		);

		return esc_url_raw( $redirect_to );

	}


	function column_headers( $columns ) {
		$columns               = array(); // Reset so we can defeat all the other plugins trying to add stuff here that we don't want
		$columns['cb']         = __( 'Select All' );
		$columns['title']      = __( 'Order' );
		$columns['order_date'] = __( 'Date', 'sunshine-photo-cart' );
		$columns['customer']   = __( 'Customer', 'sunshine-photo-cart' );
		$columns['status']     = __( 'Status', 'sunshine-photo-cart' );
		$columns['payment_method'] = __( 'Payment Method', 'sunshine-photo-cart' );
		$columns['total']      = __( 'Order Total', 'sunshine-photo-cart' );
		$columns['galleries']  = __( 'Galleries', 'sunshine-photo-cart' );
		$columns['invoice']    = __( 'Invoice', 'sunshine-photo-cart' );
		return $columns;
	}

	function column_data( $column, $post_id ) {
		$order = new SPC_Order( $post_id );
		switch ( $column ) {
			case 'order_date':
				echo $order->get_date();
				break;
			case 'customer':
				if ( $order->get_customer_id() ) {
					echo '<a href="' . admin_url( 'user-edit.php?user_id=' . $order->get_customer_id() ) . '">' . $order->get_customer_name() . '</a>';
				} else {
					echo $order->get_customer_name();
				}
				break;
			case 'status':
				echo '<span class="sunshine-order-status-' . esc_attr( $order->get_status() ) . '">' . $order->get_status_name() . '</span>';
				if ( $order->get_mode() == 'test' ) {
					echo '<span class="sunshine-order-status-test">' . __( 'Test', 'sunshine-photo-cart' ) . '</span>';
				}
				break;
			case 'payment_method':
				echo $order->get_payment_method_name();
				break;
			case 'total':
				echo $order->get_total_formatted();
				break;
			case 'galleries':
				$galleries = array();
				foreach ( $order->get_cart() as $order_item ) {
					if ( ! empty( $order_item->get_gallery_id() ) ) {
						$gallery = $order_item->get_gallery();
						$galleries[ $gallery->get_id() ] = '<a href="' . admin_url( 'post.php?action=edit&post=' . $gallery->get_id() ) . '">' . $gallery->get_name() . '</a>';
					}
				}
				echo join( '<br />', $galleries );
				break;
			case 'invoice':
				echo '<a href="' . admin_url( 'post.php?sunshine_invoice=1&post=' . $post_id ) . '" class="invoice">' . __( 'View invoice', 'sunshine-photo-cart' ) . '</a>';
				break;
		}
	}

	function sortable_columns( $columns ) {
		$columns['order_date'] = 'date';
		$columns['total']      = 'total';
		$columns['payment_method'] = 'payment_method';
		unset( $columns['title'] );
		return $columns;
	}

	function sortable_query( $query ) {
		if ( ! is_admin() ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'order_date' == $orderby ) {
			$query->set( 'orderby', 'date' );
		} elseif ( 'total' == $orderby ) {
			$query->set( 'meta_key', 'total' );
			$query->set( 'orderby', 'meta_value_num' );
		} elseif ( 'payment_method' == $orderby ) {
			$query->set( 'meta_key', 'payment_method' );
		}

	}

	function meta_boxes() {
		global $wp_meta_boxes;

		// Remove any other meta box another plugin may have tried to add
		unset( $wp_meta_boxes['sunshine-order'] );

		add_meta_box(
			'sunshine-order-sidebar',
			__( 'Order Actions', 'sunshine-photo-cart' ),
			array( $this, 'order_sidebar' ),
			'sunshine-order',
			'side',
			'core',
		);

		add_meta_box(
			'sunshine-order-data',
			__( 'Order Data', 'sunshine-photo-cart' ),
			array( $this, 'order_data' ),
			'sunshine-order',
			'normal',
			'high',
		);

		do_action( 'sunshine_order_meta_boxes' );

	}

	function order_sidebar() {
		global $post;
		$order         = new SPC_Order( $post );
		$order_actions = $this->get_order_actions();
		?>

		<div id="sunshine-order-buttons">
			<button type="submit" class="button update button-primary" name="save"><?php echo esc_attr__( 'Update', 'sunshine-photo-cart' ); ?></button>
			<?php
			if ( current_user_can( 'delete_post', $post->ID ) ) {
				if ( ! EMPTY_TRASH_DAYS ) {
					$delete_text = __( 'Delete permanently', 'sunshine-photo-cart' );
				} else {
					$delete_text = __( 'Move to Trash', 'sunshine-photo-cart' );
				}
				?>
				<a class="delete" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo esc_html( $delete_text ); ?></a>
				<?php
			}
			?>
			<a href="<?php echo admin_url( 'post.php?sunshine_invoice=1&post=' . $post->ID ); ?>" class="invoice"><?php _e( 'View invoice', 'sunshine-photo-cart' ); ?></a>
			<?php do_action( 'sunshine_admin_order_buttons', $order ); ?>
		</div>

		<div id="sunshine-order-actions">

			<h3><?php _e( 'Order Actions', 'sunshine-order-cart' ); ?></h3>

			<?php do_action( 'sunshine_order_actions_start', $order ); ?>

			<select name="sunshine_order_action">
				<option value=""><?php esc_html_e( 'Choose an action...', 'sunshine-photo-cart' ); ?></option>
				<?php foreach ( $order_actions as $action => $title ) { ?>
					<option value="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( $title ); ?></option>
				<?php } ?>
			</select>
			<?php do_action( 'sunshine_order_actions_options', $order ); ?>
			<button class="button"><span><?php esc_html_e( 'Apply', 'sunshine-photo-cart' ); ?></span></button>

			<?php do_action( 'sunshine_order_actions_end', $order ); ?>

		</div>

		<?php
	}

	function get_order_actions() {
		global $post;
		$post_id = '';
		if ( ! empty( $_POST['post_ID'] ) ) {
			$post_id = intval( $_POST['post_ID'] );
		} elseif ( ! empty( $_GET['post'] ) ) {
			$post_id = intval( $_GET['post'] );
		} elseif ( ! empty( $post_id ) ) {
			$post_id = $post->ID;
		}
		$actions = array(
			'resend_order_email' => __( 'Resend order email to customer', 'sunshine-photo-cart' ),
		);
		return apply_filters( 'sunshine_order_actions', $actions, $post_id );
	}

	function order_data() {
		global $post;
		$order = new SPC_Order( $post );
		?>
		<?php
		if ( $order->get_mode() == 'test' ) {
			?>
			<div id="sunshine-order-test"><?php _e( 'Test Order', 'sunshine-photo-cart' ); ?></div>
		<?php } ?>
		<h2><?php echo sprintf( __( '%1$s &mdash; %2$s', 'sunshine-photo-cart' ), $order->get_name(), $order->get_total_formatted() ); ?></h2>
		<ul id="sunshine-order-basics">
			<li id="sunshine-order-date">
				<span><?php _e( 'Date', 'sunshine-photo-cart' ); ?></span>
				<?php echo $order->get_date(); ?>
			</li>
			<li id="sunshine-order-payment-method">
				<span><?php _e( 'Payment Method', 'sunshine-photo-cart' ); ?></span>
				<?php
					$payment_method = SPC()->payment_methods->get_payment_method_by_id( $order->get_payment_method() );
					if ( $payment_method ) {
						$transaction_url = $payment_method->get_transaction_url( $order );
						if ( $transaction_url ) {
							echo '<a href="' . esc_url( $transaction_url ) . '" target="_blank">' . $payment_method->get_name() . '</a>';
						} else {
							echo $payment_method->get_name();
						}
					}
				?>
			</li>
			<li id="sunshine-order-shipping">
				<span><?php _e( 'Delivery/Shipping Method', 'sunshine-photo-cart' ); ?></span>
				<?php echo $order->get_delivery_method_name(); ?>
				<?php if ( $order->get_shipping_method_name() ) { ?>
					(<?php echo $order->get_shipping_method_name(); ?>)
				<?php } ?>
			</li>
		</ul>
		<div id="sunshine-order-addresses">
			<div id="sunshine-order-general">
				<h3><?php _e( 'Customer', 'sunshine-photo-cart' ); ?></h3>
				<p>
					<?php if ( $order->get_customer_id() ) { ?>
						<a href="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers&customer=' . $order->get_customer_id() ); ?>"><?php echo $order->get_customer_name(); ?></a>
						<?php
					} else {
						echo $order->get_customer_name();
					}
					?>
					<br />
					<a href="mailto:<?php echo $order->get_email(); ?>"><?php echo $order->get_email(); ?></a>
					<?php
					if ( $order->get_phone() ) {
						echo '<br />' . $order->get_phone();
					}
					?>
				</p>
				<h3><?php _e( 'Order Status', 'sunshine-photo-cart' ); ?></h3>
				<p>
					<select id="order-status" name="order_status">
						<?php
						$current_order_status = $order->get_status();
						$order_statuses       = sunshine_get_order_statuses( 'object' );
						foreach ( $order_statuses as $order_status ) {
							echo '<option value="' . $order_status->get_key() . '" ' . selected( $current_order_status, $order_status->get_key(), false ) . '>' . $order_status->get_name() . '</option>';
						}
						?>
					</select>
				</p>
				<p id="order-status-change-notify" style="display: none;"><label><input type="checkbox" name="order_status_change_notify" value="yes" /> <?php _e( 'Notify customer of status change', 'sunshine-photo-cart' ); ?></label></p>
				<script>
					jQuery( 'select[name="order_status"]' ).change(function() {
						if ( jQuery('select[name="order_status"]').val() != '<?php echo esc_js( $current_order_status ); ?>' ) {
							jQuery( '#order-status-change-notify' ).show();
						} else {
							jQuery( '#order-status-change-notify' ).hide();
						}
					});
				</script>
				<?php do_action( 'sunshine_admin_after_order_general', $order ); ?>
			</div>
			<div id="sunshine-order-shipping">
				<h3><?php _e( 'Shipping', 'sunshine-photo-cart' ); ?></h3>
				<?php if ( $order->has_shipping_address() ) { ?>
					<p><?php echo $order->get_shipping_address_formatted(); ?></p>
				<?php } else { ?>
					<p><?php _e( 'No shipping address collected for this order', 'sunshine-photo-cart' ); ?>
				<?php } ?>
				<?php do_action( 'sunshine_admin_after_order_shipping', $order ); ?>
			</div>
			<div id="sunshine-order-billing">
				<h3><?php _e( 'Billing', 'sunshine-photo-cart' ); ?></h3>
				<?php if ( $order->has_billing_address() ) { ?>
					<p><?php echo $order->get_billing_address_formatted(); ?></p>
				<?php } else { ?>
					<p><?php _e( 'No billing address collected for this order', 'sunshine-photo-cart' ); ?>
				<?php } ?>
				<?php if ( $order->get_vat() ) { ?>
					<p><strong><?php echo ( SPC()->get_option( 'vat_label' ) ) ? SPC()->get_option( 'vat_label' ) : __( 'EU VAT Number', 'sunshine-photo-cart' ); ?></strong><br />
					<?php echo $order->get_vat(); ?></p>
				<?php } ?>

				<?php do_action( 'sunshine_admin_after_order_billing', $order ); ?>
			</div>

			<?php if ( $order->get_customer_notes() ) { ?>
				<div id="sunshine-order-notes">
					<h3><?php _e( 'Customer Notes', 'sunshine-photo-cart' ); ?></h3>
					<?php echo wp_kses_post( $order->get_customer_notes() ); ?>
				</div>
			<?php } ?>

		</div>

		<?php
		$tabs = array(
			'items'    => __( 'Items', 'sunshine-photo-cart' ),
			'images'   => __( 'Images', 'sunshine-photo-cart' ),
			'comments' => __( 'Comments', 'sunshine-photo-cart' ),
			'notes'    => __( 'Notes', 'sunshine-photo-cart' ),
			'log'      => __( 'Log', 'sunshine-photo-cart' ),
		);
		if ( $order->has_refunds() ) {
			$tabs['refunds'] = __( 'Refunds', 'sunshine-photo-cart' );
		}
		$admin_order_tabs = apply_filters( 'sunshine_admin_order_tabs', $tabs, $order );

		echo '<nav class="nav-tab-wrapper" id="sunshine-admin-order-tabs">';
		$i = 1;
		foreach ( $admin_order_tabs as $key => $label ) {
			echo '<a class="nav-tab ' . ( ( $i == 1 ) ? 'nav-tab-active' : '' ) . '" id="sunshine-admin-order-tab-' . esc_attr( $key ) . '" data-tab="' . esc_attr( $key ) . '" title="' . esc_attr( $label ) . '" href="#' . esc_attr( $key ) . '">' . esc_html( $label ) . '</a>';
			$i++;
		}
		echo '</nav>';
		?>
		<script>
			jQuery( '#sunshine-admin-order-tabs a' ).on( 'click', function(){
				jQuery( '.sunshine-admin-order-tab-content' ).hide();
				jQuery( '#sunshine-admin-order-tab-content-' + jQuery( this ).data( 'tab' ) ).show();
				jQuery( '#sunshine-admin-order-tabs a' ).removeClass( 'nav-tab-active' );
				jQuery( this ).addClass( 'nav-tab-active' );
				return false;
			});
		</script>
		<?php

		echo '<div id="sunshine-admin-order-tab-content">';
		foreach ( $admin_order_tabs as $key => $label ) {
			echo '<div class="sunshine-admin-order-tab-content" id="sunshine-admin-order-tab-content-' . esc_attr( $key ) . '">';
			do_action( 'sunshine_admin_order_tab_' . $key, $order );
			echo '</div>';
		}
		echo '</div>';

	}

	function items_tab( $order ) {
		$items = $order->get_items();
		if ( empty( $items ) ) {
			return;
		}
		?>

		<table id="sunshine-admin-cart-items">
		<thead>
			<tr>
				<th class="sunshine-cart-image"><?php esc_html_e( 'Image', 'sunshine-photo-cart' ); ?></th>
				<th class="sunshine-cart-image"><?php esc_html_e( 'Image Data', 'sunshine-photo-cart' ); ?></th>
				<th class="sunshine-cart-name"><?php esc_html_e( 'Product', 'sunshine-photo-cart' ); ?></th>
				<th class="sunshine-cart-qty"><?php esc_html_e( 'Qty', 'sunshine-photo-cart' ); ?></th>
				<th class="sunshine-cart-price"><?php esc_html_e( 'Item Price', 'sunshine-photo-cart' ); ?></th>
				<th class="sunshine-cart-total"><?php esc_html_e( 'Item Total', 'sunshine-photo-cart' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $items as $item ) { ?>
			<tr class="sunshine-cart-item <?php echo $item->classes(); ?>" id="sunshine-cart-item-<?php echo esc_attr( $item->get_id() ); ?>">
				<td class="sunshine-cart-item-image" data-label="<?php esc_attr_e( 'Image', 'sunshine-photo-cart' ); ?>">
					<?php echo $item->get_image_html(); ?>
				</td>
				<td class="sunshine-cart-item-image-data" data-label="<?php esc_attr_e( 'Image Data', 'sunshine-photo-cart' ); ?>">
					<?php
					$gallery = $item->get_gallery();
					if ( $gallery ) {
						?>
					<div class="sunshine-cart-item-name-gallery">
						<?php
							if ( $gallery->get_parent_gallery_id() > 0 ) {
								$ancestors      = get_ancestors( $gallery->get_id(), 'sunshine-gallery', 'post_type' );
								$ancestor_links = array( '<a href="' . admin_url( 'post.php?action=edit&post=' . $gallery->get_id() ). '">' . esc_html( $gallery->get_name() ) . '</a>' );
								foreach ( $ancestors as $ancestor_id ) {
									$ancestor_links[] = '<a href="' . admin_url( 'post.php?action=edit&post=' . $ancestor_id ) . '">' . esc_html( get_the_title( $ancestor_id ) ) . '</a>';
								}
								$ancestor_links = array_reverse( $ancestor_links );
								echo join( ' > ', $ancestor_links );
							} else {
								echo '<a href="' . admin_url( 'post.php?action=edit&post=' . $gallery->get_id() ) . '">' . esc_html( $gallery->get_name() ) . '</a>';
							}
						?>
						<?php //echo $item->get_gallery_name(); ?>
					</div>
					<?php } ?>
					<div class="sunshine-cart-item-name-image"><?php echo $item->get_image_name(); ?></div>
					<div class="sunshine-cart-item-filename"><?php echo join( '<br />', $item->get_file_names() ); ?></div>
				</td>
				<td class="sunshine-cart-item-name" data-label="<?php esc_attr_e( 'Product', 'sunshine-photo-cart' ); ?>">
					<div class="sunshine-cart-item-name-product"><?php echo $item->get_name(); ?></div>
					<div class="sunshine-cart-item-product-options"><?php echo $item->get_options_formatted(); ?></div>
					<div class="sunshine-cart-item-comments"><?php echo $item->get_comments(); ?></div>
					<div class="sunshine-cart-item-extra"><?php echo $item->get_extra(); ?></div>
				</td>
				<td class="sunshine-cart-item-qty" data-label="<?php esc_attr_e( 'Qty', 'sunshine-photo-cart' ); ?>">
					<?php echo $item->get_qty(); ?>
				</td>
				<td class="sunshine-cart-item-price" data-label="<?php esc_attr_e( 'Price', 'sunshine-photo-cart' ); ?>">
					<?php echo $item->get_price_formatted(); ?>
				</td>
				<td class="sunshine-cart-item-total" data-label="<?php esc_attr_e( 'Total', 'sunshine-photo-cart' ); ?>">
					<?php echo $item->get_total_formatted(); ?>
				</td>
			</tr>
		<?php } ?>
		</tbody>
		</table>

		<table id="sunshine-admin-order-totals">
			<tr class="sunshine-subtotal">
				<th><?php _e( 'Subtotal', 'sunshine-photo-cart' ); ?></th>
				<td><?php echo $order->get_subtotal_formatted(); ?></td>
			</tr>
			<?php if ( ! empty( $order->get_shipping_method() ) ) { ?>
			<tr class="sunshine-shipping">
				<th><?php echo sprintf( __( 'Shipping via %s', 'sunshine-photo-cart' ), $order->get_shipping_method_name() ); ?></th>
				<td><?php echo $order->get_shipping_formatted(); ?></td>
			</tr>
			<?php } ?>
			<?php if ( ! empty( $order->get_discount() ) ) { ?>
			<tr class="sunshine-discount">
				<th>
					<?php _e( 'Discounts', 'sunshine-photo-cart' ); ?><br />
					<span><?php echo join( '<br />', $order->get_discount_names() ); ?></span>
				</th>
				<td><?php echo $order->get_discount_formatted(); ?></td>
			</tr>
			<?php } ?>
			<?php if ( $order->get_tax() ) { ?>
			<tr class="sunshine-tax">
				<th><?php _e( 'Tax', 'sunshine-photo-cart' ); ?></th>
				<td><?php echo $order->get_tax_formatted(); ?></td>
			</tr>
			<?php } ?>
			<?php if ( $order->get_fees() ) { ?>
				<?php foreach ( $order->get_fees() as $fee ) { ?>
					<tr class="sunshine--cart--fee">
						<th><?php esc_html_e( $fee['name'] ); ?></th>
						<td><?php echo sunshine_price( $fee['amount'] ); ?></td>
					</tr>
				<?php } ?>
			<?php } ?>
			<?php if ( $order->get_credits() > 0 ) { ?>
			<tr class="sunshine-credits">
				<th><?php _e( 'Credits Applied', 'sunshine-photo-cart' ); ?></th>
				<td><?php echo $order->get_credits_formatted(); ?></td>
			</tr>
			<?php } ?>
			<?php if ( $order->get_refunds() ) { ?>
			<tr class="sunshine-refunds">
				<th><?php _e( 'Refunds', 'sunshine-photo-cart' ); ?></th>
				<td><?php echo $order->get_refund_total_formatted(); ?></td>
			</tr>
			<?php } ?>
			<tr class="sunshine-total">
				<th><?php _e( 'Order Total', 'sunshine-photo-cart' ); ?></th>
				<td><?php echo $order->get_total_formatted(); ?></td>
			</tr>
			<?php do_action( 'sunshine_admin_order_totals', $order ); ?>
		</table>

		<?php
	}

	function images_tab( $order ) {
		$items       = $order->get_items();
		$file_names = array();
		foreach ( $items as $item ) {
			$file_names = array_merge( $file_names, $item->get_file_names() );
		}
		if ( ! empty( $file_names ) ) {
			$file_names = array_unique( $file_names );
			asort( $file_names );
			echo '<input type="text" id="filenames" style="width:70%" value="' . esc_attr( join( ',', $file_names ) ) . '" />';
			echo ' <a id="copy-filenames" class="button">' . __( 'Copy to clipboard', 'sunshine-photo-cart' ) . '</a><br /><br />';
			_e( 'Copy and paste the file names above into Lightroom search feature (Library filter) to quickly find and create a new collection to make processing this order easier. Make sure you are using the "Contains" (and not "Contains All") search parameter.', 'sunshine-photo-cart' );
			echo '<script>
				jQuery("#copy-filenames").click(function(){
					jQuery("#filenames").select();
					document.execCommand( "copy" );
					jQuery( this ).html( "Copied!" );
					return false;
				});
				</script>';
		}
	}

	function comments_tab( $order ) {

		echo '<div id="sunshine--order--comments">';
		$comments = $order->get_comments();
		if ( ! empty( $comments ) ) {
			foreach ( $comments as $comment ) {
				sunshine_get_template( 'order/comment', array( 'comment' => $comment ) );
			}
		}
		echo '</div>';

		?>
		<p><textarea name="comment"></textarea></p>
		<p><label><input type="checkbox" name="notify" value="1" checked="checked" /> <?php _e( 'Notify customer?', 'sunshine-photo-cart' ); ?></label></p>
		<p><button class="button"><?php _e( 'Add comment', 'sunshine-photo-cart' ); ?></button></p>
		<script>
		jQuery( document ).ready(function($){
			$( 'body' ).on( 'click', '#sunshine-admin-order-tab-content-comments button', function(e) {

				e.preventDefault();

				$( '#sunshine-admin-order-tab-content-comments' ).addClass( 'sunshine-loading' );

				let comment = $( 'textarea[name="comment"]' ).val();
				let notify = $( 'input[name="notify"]:checked' ).val();
				if ( comment ) {
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'sunshine_order_add_comment',
							comment: comment,
							notify: notify,
							order_id: '<?php echo esc_js( $order->get_id() ); ?>',
							security: '<?php echo wp_create_nonce( 'sunshine_order_add_comment' ); ?>'
						},
						success: function( result, textStatus, XMLHttpRequest) {
							if ( result.success ) {
								$( 'textarea[name="comment"]' ).val( '' );
								console.log( result.data );
								$( '#sunshine--order--comments' ).append( result.data.comment );
							}
						},
						error: function( MLHttpRequest, textStatus, errorThrown ) {
							alert( 'Sorry, there was an error with your request: ' + errorThrown + MLHttpRequest + textStatus ); // TODO: Better error
						},
					}).always(function(){
						$( '#sunshine-admin-order-tab-content-comments' ).removeClass( 'sunshine-loading' );
					});
				}

				return false;

			});
		});
		</script>
		<?php

	}

	function add_comment() {
		global $current_user;

		if ( ! isset( $_POST['order_id'] ) || ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sunshine_order_add_comment' ) ) {
			wp_send_json_error();
			return;
		}

		$args       = array(
			'comment_post_ID'  => intval( $_POST['order_id'] ),
			'comment_approved' => 1,
			'comment_content'  => sanitize_textarea_field( $_POST['comment'] ),
			'user_id'          => $current_user->ID,
		);
		$comment_id = wp_insert_comment( $args );
		$comment    = get_comment( $comment_id );

		// notify customer
		if ( ! empty( $_POST['notify'] ) ) {
			do_action( 'sunshine_order_add_comment', $comment, intval( $_POST['order_id'] ) );
		}

		$comment_html = sunshine_get_template_html( 'order/comment', array( 'comment' => $comment ) );
		wp_send_json_success( array( 'comment' => $comment_html ) );

	}


	function notes_tab( $order ) {
		?>
		<p><?php _e( 'This is for internal use only and is not visible to your customer', 'sunshine-photo-cart' ); ?></p>
		<p><textarea name="notes" rows="10"><?php echo esc_attr( $order->get_notes() ); ?></textarea></p>
		<p><button class="button"><?php _e( 'Save notes', 'sunshine-photo-cart' ); ?></button></p>
		<script>
		jQuery( document ).ready(function($){
			$( 'body' ).on( 'click', '#sunshine-admin-order-tab-content-notes button', function(e) {

				e.preventDefault();

				$( '#sunshine-admin-order-tab-content-notes' ).addClass( 'sunshine-loading' );

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'sunshine_order_save_notes',
						notes: $( 'textarea[name="notes"]' ).val(),
						order_id: '<?php echo esc_js( $order->get_id() ); ?>',
						security: '<?php echo wp_create_nonce( 'sunshine_order_save_notes' ); ?>'
					},
					success: function( result, textStatus, XMLHttpRequest) {
						// TODO: Success message
					},
					error: function( MLHttpRequest, textStatus, errorThrown ) {
						alert( 'Sorry, there was an error with your request: ' + errorThrown + MLHttpRequest + textStatus ); // TODO: Better error
					},
				}).always(function(){
					$( '#sunshine-admin-order-tab-content-notes' ).removeClass( 'sunshine-loading' );
				});

				return false;

			});
		});
		</script>
		<?php
	}

	function save_notes() {

		if ( ! isset( $_POST['order_id'] ) || ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sunshine_order_save_notes' ) ) {
			wp_send_json_error();
			return;
		}

		update_post_meta( intval( $_POST['order_id'] ), 'notes', sanitize_textarea_field( $_POST['notes'] ) );
		wp_send_json_success();

	}

	function log_tab( $order ) {

		$log = $order->get_log();
		if ( ! empty( $log ) ) {
			echo '<ol>';
			foreach ( $log as $entry ) {
				echo '<li><span class="log-date">' . date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $entry->comment_date_gmt ) ) . '</span> <span class="log-content">' . $entry->comment_content . '</span></li>';
			}
			echo '</ol>';
		} else {
			echo '<p>' . __( 'No log entries yet', 'sunshine-photo-cart' ) . '</p>';
		}

	}

	public function refunds_tab( $order ) {

		$refunds = $order->get_refunds();

		echo '<ol>';
		foreach ( $refunds as $refund ) {
			echo '<li><span class="log-date">' . date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $refund['date'] ) . '</span> ' . sunshine_price( $refund['amount'] );
			if ( ! empty( $refund['reason'] ) ) {
				echo ': ' . $refund['reason'];
			}
			echo '</li>';
		}
		echo '</ol>';

	}


	function save_post( $post_id ) {

		if ( get_post_type( $post_id ) != 'sunshine-order' || ! empty( $_POST['sunshine_order_action'] ) ) {
			return;
		}

		// Update order status
		if ( ! empty( $_POST['order_status'] ) ) {
			$order                = new SPC_Order( $post_id );
			$current_order_status = $order->get_status();
			$new_order_status     = sanitize_key( $_POST['order_status'] );
			if ( $current_order_status != $new_order_status ) {
				$order->set_status( $new_order_status );
				if ( ! empty( $_POST['order_status_change_notify'] ) && $_POST['order_status_change_notify'] == 'yes' ) {
					do_action( 'sunshine_admin_order_status_update', $order );
					//$order->notify( false );
					SPC()->notices->add_admin( 'order_status_change', sprintf( __( 'Order status notification email sent to %s', 'sunshine-photo-cart' ), $order->get_email() ), 'success' );
					$order->add_log( sprintf( __( 'Order status notification email sent to %s', 'sunshine-photo-cart' ), $order->get_email() ) );
				}
			}
		}

	}

	function process_order_action() {

		if ( empty( $_POST['sunshine_order_action'] ) || empty( $_POST['post_ID'] ) ) {
			return;
		}

		$available_actions = $this->get_order_actions();
		if ( ! array_key_exists( $_POST['sunshine_order_action'], $available_actions ) ) {
			return false;
		}

		do_action( 'sunshine_order_process_action_' . sanitize_key( $_POST['sunshine_order_action'] ), intval( $_POST['post_ID'] ) );

	}

	function resend_order_email( $order_id ) {

		$order = new SPC_Order( $order_id );
		$order->notify( false );
		SPC()->notices->add_admin( 'resend_order_email', __( 'Order email successfully resent', 'sunshine-photo-cart' ), 'success' );
		$order->add_log( sprintf( __( 'Order email resent to customer for %s', 'sunshine-photo-cart' ), $order->get_name() ) );

	}

	function invoice() {

		if ( isset( $_GET['sunshine_invoice'] ) && isset( $_GET['post'] ) && current_user_can( 'sunshine_manage_options' ) ) {

			$order = new SPC_Order( intval( $_GET['post'] ) );
			if ( empty( $order ) ) {
				wp_die( __( 'Invalid order ID', 'sunshine-photo-cart' ) );
				exit;
			}

			echo sunshine_get_template( 'invoice/admin', array( 'order' => $order ) );
			exit;

		}

	}

	public function trash( $post_id ) {
		if ( get_post_type( $post_id ) == 'sunshine-order' ) {
			$customer_id = get_post_field( 'post_author', $post_id );
			$customer = new SPC_Customer( $customer_id );
			$customer->recalculate_stats();
		}
	}

	function untrash( $post_id ) {
		if ( get_post_type( $post_id ) == 'sunshine-order' ) {
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'publish',
				)
			);
			$customer_id = get_post_field( 'post_author', $post_id );
			$customer = new SPC_Customer( $customer_id );
			$customer->recalculate_stats();
		}
	}

	function delete( $post_id ) {
		global $wpdb;
		$wpdb->delete(
			$wpdb->prefix . 'sunshine_order_items',
			array( 'order_id' => $post_id ),
			array( '%d' ),
		);
		// Data in sunshine_order_itemmeta will automatically be deleted because of foreign key cascade.
	}

}


$SPC_Admin_Order = new SPC_Admin_Order();


add_action( 'wp', 'sunshine_admin_order_check' );
function sunshine_admin_order_check() {
    global $pagenow;

    if ( empty( $_GET['s'] ) && ! empty( $_GET['customer'] ) && $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'sunshine-order' ) {
        $query = new WP_Query( array(
            'post_type' => 'sunshine-order',
            'post_status' => array( 'any', 'trashx' ),
        ) );
        if ( $query->found_posts == 0 ) {
			echo '<style>.wrap { display: none; }</style>';
			add_thickbox();
            add_action( 'admin_notices', 'sunshine_admin_no_orders' );
        }
    }
}

function sunshine_admin_no_orders() {
	sunshine_get_template( 'admin/no-orders' );
}

?>
