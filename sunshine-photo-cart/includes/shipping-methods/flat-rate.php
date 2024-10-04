<?php

class SPC_Shipping_Method_Flat_Rate extends SPC_Shipping_Method {

	public function init() {
		$this->id            = 'flat_rate';
		$this->name          = __( 'Flat Rate', 'sunshine-photo-cart' );
		$this->class         = 'SPC_Shipping_Method_Flat_Rate';
		$this->description   = '';
		$this->can_be_cloned = true;
	}

	public function set_price() {

		if ( empty( $this->instance_id ) ) {
			return;
		}

		$price_type = SPC()->get_option( $this->id . '_price_type_' . $this->instance_id );
		if ( empty( $price_type ) ) {
			parent::set_price();
			return;
		}

		$product_shipping = 0;
		$cart_items = SPC()->cart->get_cart_items();
		if ( ! empty( $cart_items ) ) {
			foreach ( SPC()->cart->get_cart_items() as $item ) {
				// Add product shipping fee if exists.
				$item_shipping = $item->product->get_shipping();
				if ( $item_shipping ) {
					$product_shipping += floatval( $item_shipping * $item->get_qty() );
				}
			}
		}

		$price = floatval( SPC()->get_option( $this->id . '_price_' . $this->instance_id ) );
		$total = 0;
		if ( $price_type == 'cart' ) {
			$this->price = floatval( $price ) + floatval( $product_shipping );
		} elseif ( ! empty( $this->instance_id ) ) {

			if ( ! empty( $cart_items ) ) {

				foreach ( SPC()->cart->get_cart_items() as $item ) {

					// Determine how we calculate shipping fee: line item or qty
					if ( $price_type == 'line' ) {
						$this->price += floatval( $price );
					} elseif ( $price_type == 'qty' ) {
						$this->price += floatval( $price * $item->get_qty() );
					}

				}

				$this->price += $product_shipping;

			}

		}

		if ( $this->price && $this->is_taxable() ) {
			$tax_rate = SPC()->cart->get_tax_rate();
			if ( $tax_rate ) {
				if ( SPC()->get_option( 'price_has_tax' ) == 'yes' ) {
					$new_total = round( $this->price / ( $tax_rate['rate'] + 1 ), 2 );
					$this->tax = $this->price - $new_total;
					$this->price = $new_total;
				} else {
					$this->tax = round( $this->price * $tax_rate['rate'], 2 );
				}
			}
		}

	}

	public function is_allowed() {

		if ( empty( $this->instance_id ) ) {
			return false;
		}

		$allowed = apply_filters( 'sunshine_shipping_flat_rate_allowed', true, $this );

		return $allowed;

	}


}

$sunshine_shipping_flat_rate = new SPC_Shipping_Method_Flat_Rate();
