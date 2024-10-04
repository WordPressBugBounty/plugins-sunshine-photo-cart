<?php
class SPC_Customer extends WP_User {

	private $favorite_ids = array();
	private $favorites    = array();
	private $favorite_key;
	private $credits = 0;

	public function __construct( $user_id ) {
		parent::__construct( $user_id );
		$all_meta = get_user_meta( $user_id );
		if ( ! empty( $all_meta['first_name'][0] ) ) {
			$this->data->first_name = $all_meta['first_name'][0];
		}
		if ( ! empty( $all_meta['last_name'][0] ) ) {
			$this->data->last_name = $all_meta['last_name'][0];
		}
		if ( ! empty( $all_meta['sunshine_favorites'][0] ) ) {
			$this->favorite_ids = maybe_unserialize( $all_meta['sunshine_favorites'][0] );
		}
		if ( ! empty( $all_meta['sunshine_favorite_key'][0] ) ) {
			$this->favorite_key = $all_meta['sunshine_favorite_key'][0];
		}
		if ( ! empty( $all_meta['sunshine_credits'][0] ) ) {
			$this->credits = $all_meta['sunshine_credits'][0];
		}
	}

	public function get_id() {
		return $this->ID;
	}

	public function add_meta( $key, $value ) {
		if ( $this->ID > 0 ) {
			if ( is_serialized( $value ) ) {
				return false;
			}
			add_user_meta( $this->ID, SPC()->prefix . $key, $value );
		}
	}

	public function update_meta( $key, $value ) {
		if ( $this->ID > 0 ) {

			if ( is_serialized( $value ) ) {
				return false;
			}

			$prefix = SPC()->prefix;
			// Disable prefix to use core first/last name.
			if ( $key == 'first_name' || $key == 'last_name' ) {
				$prefix = '';
			}
			update_user_meta( $this->ID, $prefix . $key, $value );
		}
	}

	public function delete_meta( $key ) {
		if ( $this->ID > 0 ) {
			delete_user_meta( $this->ID, SPC()->prefix . $key );
		}
	}

	public function get_meta( $key, $single = true ) {
		if ( $this->ID > 0 ) {
			return get_user_meta( $this->ID, SPC()->prefix . $key, $single );
		}
		return false;
	}

	public function get_name( $fallback = '' ) {
		if ( $this->get_first_name() ) {
			return $this->get_first_name() . ' ' . $this->get_last_name();
		}
		if ( ! empty( $fallback ) ) {
			return $fallback;
		}
		if ( empty( $fallback ) && $this->display_name ) {
			return $this->display_name;
		}
	}
	public function get_first_name() {
		return ( ! empty( $this->data->first_name ) ) ? $this->data->first_name : '';
	}
	public function get_last_name() {
		return ( ! empty( $this->data->last_name ) ) ? $this->data->last_name : '';
	}

	public function get_email() {
		return ( ! empty( $this->data->user_email ) ) ? $this->data->user_email : '';
	}

	public function set_cart( $contents ) {
		$this->update_meta( 'cart', $contents );
	}

	public function get_cart() {
		return $this->get_meta( 'cart' );
	}

	public function get_cart_items() {
		$cart = $this->get_cart();
		if ( ! empty( $cart ) ) {
			$final_contents = array();
			foreach ( $cart as $key => $item ) {
				 $cart_item = new SPC_Cart_Item( $item );
				 if ( ! empty( $cart_item->get_product() ) ) {
					 $final_contents[] = $cart_item;
				}
			}
			return $final_contents;
		}
		return false;
	}


	public function set_credits( $credits ) {
		$this->update_meta( 'credits', floatval( $credits ) );
	}

	public function get_credits() {
		return $this->credits;
	}

	public function decrease_credits( $amount ) {
		$credits = $this->get_credits();
		$credits -= $amount;
		$this->set_credits( max( $credits, 0 ) );
	}

	public function increase_credits( $amount ) {
		$credits = $this->get_credits();
		$credits += $amount;
		$this->set_credits( max( $credits, 0 ) );
	}

	public function get_favorite_ids() {
		return $this->favorite_ids;
	}

	public function get_favorites() {
		if ( ! empty( $this->favorites ) ) {
			return $this->favorites;
		}
		if ( ! empty( $this->favorite_ids ) ) {
			foreach ( $this->favorite_ids as $favorite_id ) {
				$image = sunshine_get_image( $favorite_id );
				if ( $image->exists() && $image->get_gallery() ) {
					$this->favorites[] = $image;
				} else {
					$this->delete_favorite( $favorite_id );
				}
			}
			return $this->favorites;
		}
		return false;
	}

	public function get_favorites_count() {
		return count( $this->get_favorite_ids() );
	}

	public function add_favorite( $image_id ) {

		$image_id = intval( $image_id );
		if ( ! in_array( $image_id, $this->favorite_ids, true ) ) {
			$this->favorite_ids[] = $image_id;
			$this->update_meta( 'favorites', $this->favorite_ids ); // TODO: Use the class function here and throughout for get/set/delete user meta
			$this->increase_favorites_count();
			do_action( 'sunshine_add_favorite', $image_id, $this );
		}

	}

	public function delete_favorite( $image_id ) {

		$key = array_search( $image_id, $this->favorite_ids, true );
		if ( $key !== false ) {
			unset( $this->favorite_ids[ $key ] );
			$this->update_meta( 'favorites', $this->favorite_ids ); // TODO: Use the class function here and throughout for get/set/delete user meta
			$this->decrease_favorites_count();
			do_action( 'sunshine_delete_favorite', $image_id, $this );
		}

	}

	public function has_favorites() {
		if ( ! empty( $this->favorite_ids ) ) {
			return true;
		}
		return false;
	}

	public function has_favorite( $image_id ) {
		if ( ! empty( $this->favorite_ids ) && in_array( $image_id, $this->favorite_ids ) ) {
			return true;
		}
		return false;
	}

	public function get_favorite_count() {
		if ( empty( $this->favorite_ids ) ) {
			return 0;
		}
		return count( $this->favorite_ids );
	}

	public function clear_favorites() {
		$favorite_ids = $this->get_favorite_ids();
		if ( ! empty( $favorite_ids ) ) {
			foreach ( $favorite_ids as $image_id ) {
				do_action( 'sunshine_delete_favorite', $image_id, $this );
			}
			$this->update_meta( 'favorites', '' );
		}
	}

	public function get_favorite_key() {
		if ( empty( $this->favorite_key ) ) {
			$this->favorite_key = wp_generate_password( 20, false );
			$this->update_meta( 'favorite_key', $this->favorite_key );
		}
		return $this->favorite_key;
	}

	/* SHIPPING */
	public function get_shipping_address1() {
		$key = SPC()->prefix . 'shipping_address1';
		return $this->{$key};
	}
	public function get_shipping_address2() {
		$key = SPC()->prefix . 'shipping_address2';
		return $this->{$key};
	}
	public function get_shipping_city() {
		$key = SPC()->prefix . 'shipping_city';
		return $this->{$key};
	}
	public function get_shipping_state() {
		$key = SPC()->prefix . 'shipping_state';
		return $this->{$key};
	}
	public function get_shipping_postcode() {
		$key = SPC()->prefix . 'shipping_postcode';
		return $this->{$key};
	}
	public function get_shipping_country() {
		$key = SPC()->prefix . 'shipping_country';
		return $this->{$key};
	}
	public function get_shipping_address_formatted() {
		$args = array(
			'address1'   => $this->get_shipping_address1(),
			'address2'   => $this->get_shipping_address2(),
			'city'       => $this->get_shipping_city(),
			'state'      => $this->get_shipping_state(),
			'postcode'   => $this->get_shipping_postcode(),
			'country'    => $this->get_shipping_country(),
		);
		return SPC()->countries->get_formatted_address( $args );
	}

	public function has_shipping_address() {
		if ( $this->get_shipping_address1() ) {
			return true;
		}
		return false;
	}


	/* BILLING */ // TODO: Use prefix like shipping
	public function get_billing_address1() {
		$key = SPC()->prefix . 'billing_address1';
		return $this->{$key};
	}
	public function get_billing_address2() {
		$key = SPC()->prefix . 'billing_address2';
		return $this->{$key};
	}
	public function get_billing_city() {
		$key = SPC()->prefix . 'billing_city';
		return $this->{$key};
	}
	public function get_billing_state() {
		$key = SPC()->prefix . 'billing_state';
		return $this->{$key};
	}
	public function get_billing_postcode() {
		$key = SPC()->prefix . 'billing_postcode';
		return $this->{$key};
	}
	public function get_billing_country() {
		$key = SPC()->prefix . 'billing_country';
		return $this->{$key};
	}
	public function get_billing_address_formatted() {
		$args = array(
			'address1'   => $this->get_billing_address1(),
			'address2'   => $this->get_billing_address2(),
			'city'       => $this->get_billing_city(),
			'state'      => $this->get_billing_state(),
			'postcode'   => $this->get_billing_postcode(),
			'country'    => $this->get_billing_country(),
		);
		return SPC()->countries->get_formatted_address( $args );
	}
	public function has_billing_address() {
		if ( $this->get_billing_address1() ) {
			return true;
		}
		return false;
	}

	/* OTHER FIELDS */
	public function get_phone() {
		$this->sunshine_phone;
	}
	public function get_vat() {
		$this->vat;
	}

	public function get_galleries() {
		$args = array(
			'posts_per_page' => -1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => 'private_users',
					'value' => '"' . $this->ID . '"',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'status',
					'value' => 'private',
				),
			)
		);
		$args = apply_filters( 'sunshine_customer_get_galleries_args', $args );
		$galleries = sunshine_get_galleries( $args );
		return $galleries;
	}

	/* ORDERS */
	public function get_orders() {
		$orders = array();
		$args   = array(
			'post_type'      => 'sunshine-order',
			'posts_per_page' => -1,
			'author'         => $this->ID,
		);
		$query  = new WP_Query( $args );
		while ( $query->have_posts() ) :
			$query->the_post();
			$orders[] = new SPC_Order( $query->posts[ $query->current_post ] );
		endwhile;
		wp_reset_postdata();
		return $orders;
	}

	/*
	public function get_order_totals() {
		$orders = $this->get_orders();
		if ( !empty( $orders ) ) {
			$orders_total = 0;
			foreach ( $orders as $order ) {
				$orders_total += $order->get_total();
			}
			return $orders_total;
		}
		return 0;
	}
	*/

	public function increase_order_count( $amount = 1 ) {
		$order_count = absint( $this->get_meta( 'order_count' ) );
		$order_count += $amount;
		$this->update_meta( 'order_count', $order_count );
	}

	public function decrease_order_count( $amount = 1 ) {
		$order_count = absint( $this->get_meta( 'order_count' ) );
		$order_count -= $amount;
		$this->update_meta( 'order_count', $order_count );
	}

	public function set_order_count( $amount ) {
		$this->update_meta( 'order_count', absint( $amount ) );
	}

	public function get_order_count() {
		$count = $this->get_meta( 'order_count' );
		return ( $count ) ? $count : 0;
	}

	public function increase_order_totals( $amount ) {
		$order_totals = floatval( $this->get_meta( 'order_totals' ) );
		$order_totals += $amount;
		$this->update_meta( 'order_totals', $order_totals );
	}

	public function decrease_order_totals( $amount ) {
		$order_totals = floatval( $this->get_meta( 'order_totals' ) );
		$order_totals -= $amount;
		$this->update_meta( 'order_totals', $order_totals );
	}

	public function set_order_totals( $amount ) {
		$this->update_meta( 'order_totals', floatval( $amount ) );
	}

	public function get_order_totals() {
		return $this->get_meta( 'order_totals' );
	}

	public function increase_favorites_count( $amount = 1 ) {
		$favorites_count = absint( $this->get_meta( 'favorites_count' ) );
		$favorites_count += $amount;
		$this->update_meta( 'favorites_count', $favorites_count );
	}

	public function decrease_favorites_count( $amount = 1 ) {
		$favorites_count = absint( $this->get_meta( 'favorites_count' ) );
		$favorites_count -= $amount;
		$this->update_meta( 'favorites_count', $favorites_count );
	}

	public function set_favorites_count( $amount ) {
		$this->update_meta( 'favorites_count', absint( $amount ) );
	}

	public function recalculate_stats() {
		if ( ! $this->ID ) {
			return;
		}

		$this->set_favorites_count( $this->get_favorites_count() );

		$args   = array(
			'post_type'      => 'sunshine-order',
			'posts_per_page' => -1,
			'author'         => $this->ID,
		);
		$query  = new WP_Query( $args );
		$this->set_order_count( $query->found_posts );

		$order_totals = 0;
		while ( $query->have_posts() ) : $query->the_post();
			$order = new SPC_Order( get_the_ID() );
			$order_totals += $order->get_total_minus_refunds();
		endwhile;
		wp_reset_postdata();

		$this->set_order_totals( $order_totals );

		do_action( 'sunshine_customer_recalculate_stats', $this );

	}

	public function get_actions() {
		$actions = $this->get_meta( 'action', false );
		if ( empty( $actions ) ) {
			$actions = array();
		}
		// Sort items by time from most recent to oldest.
		usort(
			$actions,
			function ( $action1, $action2 ) {
				return $action2['time'] <=> $action1['time'];
			}
		);
		return $actions;
	}

	/*
	public function add_action( $type, $object_id = '', $data = array() ) {

		do_action( 'sunshine_customer_add_action', $type, $object_id, $data );

		if ( ! $this->ID ) {
			return;
		}

		//$this->add_meta( 'action', $action );
		SPC()->log( 'Customer action added: ' . $type );

	}
	*/

}
