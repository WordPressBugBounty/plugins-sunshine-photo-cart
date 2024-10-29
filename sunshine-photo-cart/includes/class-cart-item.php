<?php

class SPC_Cart_Item {

	protected $item = array();
	protected $object_id;
	protected $image_id;
	protected $image;
	protected $image_url;
	protected $type;
	protected $name = '';
	protected $product_id;
	public $product;
	protected $category_id;
	public $category;
	protected $gallery_id;
	public $gallery;
	protected $qty = 0;
	protected $price_level;
	protected $price = 0.00;
	protected $options;
	protected $options_total;
	protected $discount       = 0.00;
	protected $discount_price = 0.00;
	protected $taxable        = false;
	protected $tax            = 0.00;
	protected $taxable_total  = 0.00;
	protected $tax_total      = 0.00;
	protected $subtotal       = 0.00;
	protected $total          = 0.00;
	protected $comments       = '';
	protected $meta           = array();
	protected $hash;

	function __construct( $item = array() ) {

		if ( ! empty( $item ) && ! is_array( $item ) ) {
			return;
		}

		$this->item = $item;

		if ( ! empty( $item['price_level'] ) ) {
			$this->price_level = $item['price_level'];
		} else {
			$this->price_level = sunshine_get_default_price_level_id();
		}

		if ( ! empty( $item['object_id'] ) ) {
			$this->object_id = $item['object_id'];
		}

		if ( ! empty( $item['image_id'] ) ) {
			$this->image_id = $item['image_id'];
			$image          = sunshine_get_image( $item['image_id'] );
			if ( $image->exists() ) {
				$this->image   = $image;
				$this->gallery = $this->image->gallery;
				if ( $this->gallery ) {
					$this->gallery_id = $this->image->gallery->get_id();
				}
			}
		}

		if ( ! empty( $item['image_url'] ) ) {
			$this->image_url = $item['image_url'];
		}

		if ( ! empty( $item['product_id'] ) && ! empty( $item['price_level'] ) ) {
			$this->product_id = $item['product_id'];
			$product          = sunshine_get_product( $item['product_id'], $item['price_level'] );
			if ( $product->exists() ) {
				$this->product = $product;
				$this->name    = $product->get_display_name();
				$this->type    = $product->get_type();
				$this->taxable = $product->is_taxable();
				$this->price   = $product->get_regular_price();
				$this->set_options_total();
			}
		}

		if ( ! empty( $item['gallery_id'] ) ) {
			$this->gallery_id = $item['gallery_id'];
			$this->gallery    = sunshine_get_gallery( $item['gallery_id'] );
		}

		if ( ! empty( $item['qty'] ) ) {
			$this->qty = intval( $item['qty'] );
		}

		if ( ! empty( $item['price'] ) ) {
			$this->price = floatval( $item['price'] );
		}

		if ( ! empty( $item['comments'] ) ) {
			$this->comments = $item['comments'];
		}
		$this->comments = apply_filters( 'sunshine_cart_item_comments', $this->comments, $item );

		if ( ! empty( $item['hash'] ) ) {
			$this->hash = $item['hash'];
		}

		if ( ! empty( $item['discount'] ) ) {
			$this->discount = $item['discount'];
		} else {
			$discount = apply_filters( 'sunshine_cart_item_discount', $this->discount, $this );
			if ( $discount ) {
				$this->discount       = floatval( $discount );
				$this->discount_price = floatval( $this->price - $this->discount );
			}
		}

		if ( ! empty( $item['meta'] ) ) {
			$this->meta = $item['meta'];
		}

		$this->set_total();

		if ( ! empty( $item['subtotal'] ) ) {
			$this->subtotal = $item['subtotal'];
		}

		// Do all the magic to determine actual price and tax based on store settings.
		if ( ! empty( $item['tax'] ) ) {
			$this->tax = $item['tax'];
		} else {
			$tax_rate = SPC()->cart->get_tax_rate();
			// Only do if we have a price, product is taxable and we have a matched tax rate.
			if ( $this->price && $this->taxable && ! empty( $tax_rate ) ) {
				$price_has_tax = SPC()->get_option( 'price_has_tax' );
				if ( 'yes' === $price_has_tax ) {
					// Take out tax from current price and lower price adjusting for tax amount.
					$new_price       = $this->price / ( $tax_rate['rate'] + 1 );
					$new_price       = number_format( ceil( $new_price * 100 ) / 100, 2 );
					$this->tax       = $this->price - $new_price;
					$this->tax_total = $this->tax * $this->qty;
					$this->price     = $new_price;

					if ( $this->options_total ) {
						$new_options_total   = $this->options_total / ( $tax_rate['rate'] + 1 );
						$new_price           = number_format( ceil( $new_options_total * 100 ) / 100, 2 );
						$this->tax          += $this->options_total - $new_options_total;
						$this->tax_total     = $this->tax * $this->qty;
						$this->options_total = $new_options_total;
					}

					$this->taxable_total = ( $this->price + $this->options_total - $this->discount ) * $this->qty;
					$this->total         = ( ( $this->price + $this->options_total - $this->discount ) * $this->qty );
					$this->subtotal      = ( $this->price + $this->options_total ) * $this->qty;

				} else {
					// Need to calculate from price whenever tax is not included
					$this->tax           = ( $this->price + $this->options_total ) * $tax_rate['rate'];
					$this->tax_total     = $this->tax * $this->qty;
					$this->taxable_total = ( $this->price + $this->options_total - $this->discount ) * $this->qty;
				}
			}
		}

		if ( ! empty( $item['total'] ) ) {
			$this->total = $item['total'];
		}

	}

	public function get_item_data() {
		return $this->item;
	}

	public function get_options_total() {
		return $this->options_total;
	}

	public function set_options_total() {

		if ( ! empty( $this->product ) && ! empty( $this->item['options'] ) ) {
			$this->options = $this->item['options'];
			foreach ( $this->options as $option_id => $id ) {
				$this->options_total += floatval( $this->product->get_option_item_price( $option_id, $id, $this->price_level ) );
			}
		}

	}

	public function set_total() {
		$this->subtotal = max( 0, ( floatval( $this->price ) + floatval( $this->options_total ) ) * $this->qty );
		$this->total    = max( 0, floatval( $this->subtotal - ( $this->discount * $this->qty ) ) );
	}

	public function get_image_id() {
		return $this->image_id;
	}

	public function get_image() {
		return $this->image;
	}

	public function get_gallery_id() {
		return $this->gallery_id;
	}

	public function get_gallery() {
		return $this->gallery;
	}

	public function get_gallery_name() {
		if ( ! empty( $this->gallery ) ) {
			return $this->gallery->get_name();
		}
		return false;
	}

	public function get_gallery_hierarchy( $separator = '>' ) {

		$result  = '';
		$gallery = $this->get_gallery();
		if ( empty( $gallery ) ) {
			return false;
		}
		if ( $gallery->exists() && $gallery->get_parent_gallery_id() > 0 ) {
			$ancestors      = get_ancestors( $gallery->get_id(), 'sunshine-gallery', 'post_type' );
			$ancestor_items = array( $gallery->get_name() );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor_items[] = get_the_title( $ancestor_id );
			}
			$ancestor_items = array_reverse( $ancestor_items );
			$result         = join( ' ' . $separator . ' ', $ancestor_items );
		} else {
			$result = $gallery->get_name();
		}

		return $result;

	}

	public function get_product_id() {
		return $this->product_id;
	}

	public function get_product() {
		return $this->product;
	}

	public function get_category_id() {
		$product = $this->get_product();
		if ( $product && $product->get_category_id() ) {
			return $product->get_category_id();
		}
		return false;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_image_url( $size = 'sunshine-thumbnail' ) {
		if ( ! empty( $this->image_url ) ) {
			return $this->image_url;
		}
		$image_url = '';
		if ( ! empty( $this->image ) ) {
			$image_url = $this->image->get_image_url( $size );
		}
		if ( empty( $image_url ) && $this->product->exists() ) {
			$image_url = $this->product->get_image_url( $size );
		}
		$image_url = apply_filters( 'sunshine_cart_item_image_url', $image_url, $this );
		return $image_url;
	}

	public function get_image_html( $size = 'sunshine-thumbnail', $use_placeholder = true, $args = array() ) {
		$image_url  = $this->get_image_url( $size );
		$image_name = strip_tags( $this->get_image_name() );
		$atts       = '';
		if ( ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				$atts .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}
		if ( $image_url ) {
			return '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $image_name ) . '" ' . $atts . ' />';
		} elseif ( $use_placeholder ) {
			return sunshine_image_placeholder_html( $args );
		}
	}

	public function get_image_name( $show = 'title' ) {
		$image_name = '';
		if ( ! empty( $this->item['image_name'] ) ) {
			$image_name = $this->item['image_name'];
		} elseif ( ! empty( $this->image ) && ! empty( $this->image->gallery ) ) {
			$image_name = $this->image->get_name( $show ) . ' &mdash; <a href="' . $this->image->gallery->get_permalink() . '">' . $this->image->gallery->get_name() . '</a>';
		}
		$image_name = apply_filters( 'sunshine_cart_item_image_name', $image_name, $this );
		return $image_name;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_name_raw() {
		return strip_tags( html_entity_decode( $this->get_name() ) );
	}

	public function get_qty() {
		return $this->qty;
	}

	public function can_edit_qty() {
		return apply_filters( 'sunshine_cart_item_can_edit_qty', true, $this );
	}

	public function max_qty() {
		return apply_filters( 'sunshine_cart_item_max_qty', null, $this );
	}

	public function get_max_qty() {
		$max_qty = null;
		$product = $this->get_product();
		if ( $product->exists() ) {
			$product_max_qty = $product->get_max_qty();
			if ( $product_max_qty ) {
				$max_qty = $product_max_qty;
			}
		}
		return apply_filters( 'sunshine_cart_item_max_qty', $max_qty, $this );
	}

	public function get_price() {
		$price = $this->price + $this->options_total;
		if ( $this->discount > 0 ) {
			$price = max( 0, $price - $this->discount );
		}
		return $price;
	}

	public function get_price_formatted() {
		$reg_price = $this->get_regular_price();
		$price     = $this->get_price();
		if ( $this->discount > 0 ) {
			$price_formatted = '<s>' . sunshine_price( $reg_price ) . '</s> ' . sunshine_get_price_to_display( $price, $this->tax );
		} else {
			$price_formatted = sunshine_get_price_to_display( $price, $this->tax );
		}
		return $price_formatted;
	}

	public function get_regular_price() {
		return $this->price;
	}

	public function get_regular_price_formatted() {
		$price           = $this->get_price();
		$price_formatted = sunshine_get_price_to_display( $price, $this->tax );
		return $price_formatted;
	}

	public function is_taxable() {
		return $this->taxable;
	}

	public function get_tax() {
		return $this->tax;
		/*
		// This gets calculated earlier on setup, don't need to do every time
		if ( ! $this->is_taxable() ) {
			return 0;
		}
		$matched_tax_rate = SPC()->cart->get_tax_rate();
		if ( ! $matched_tax_rate ) {
			return 0;
		}
		$this->tax = $this->get_subtotal() * $this->get_qty() * $matched_tax_rate['rate'];
		return $this->tax;
		*/
	}

	public function get_tax_total() {
		return $this->tax_total;
	}

	public function get_taxable_total() {
		return $this->taxable_total;
	}

	public function get_discount() {
		return $this->discount;
	}

	public function get_discount_total() {
		return $this->discount * $this->qty;
	}

	public function get_comments() {
		return $this->comments;
	}

	public function get_shipping() {
		return $this->shipping;
	}

	public function get_subtotal() {
		return $this->subtotal;
	}

	public function get_subtotal_formatted() {
		$reg_subtotal = $this->get_regular_price() * $this->qty;
		$subtotal     = $this->get_subtotal();
		if ( $this->discount > 0 ) {
			$price_formatted = '<s>' . sunshine_price( $reg_subtotal ) . '</s> ' . sunshine_get_price_to_display( $subtotal - $this->get_discount_total() );
		} else {
			$price_formatted = sunshine_get_price_to_display( $subtotal, $this->tax_total );
		}
		return $price_formatted;
	}

	public function get_regular_subtotal_formatted() {
		$subtotal           = $this->get_subtotal();
		$subtotal_formatted = sunshine_get_price_to_display( $subtotal, $this->tax_total );
		return $subtotal_formatted;
	}


	public function get_total() {
		return $this->total;
	}

	public function get_total_formatted() {
		$total_formatted = sunshine_get_price_to_display( $this->total, $this->tax );
		if ( $this->discount > 0 ) {
			$total_formatted = '<s>' . sunshine_price( $this->subtotal ) . '</s> ' . $total_formatted;
		}
		return $total_formatted;
	}

	public function get_hash() {
		return $this->hash;
	}

	public function get_file_names() {

		$file_names = array();

		if ( $this->get_type() == 'download' ) {
			return $file_names;
		}

		if ( ! empty( $this->image ) ) {
			$image_file_name = $this->image->get_file_name();
			if ( $image_file_name ) {
				$file_names[] = $image_file_name;
			}
		}
		if ( ! empty( $this->item['options']['images'] ) ) {
			foreach ( $this->item['options']['images'] as $image_id ) {
				$file_names[] = sunshine_get_image_file_name( $image_id );
			}
		}

		return $file_names;

	}

	public function get_remove_url() {
		$url = SPC()->get_option( 'page_cart' );
		$url = add_query_arg( 'delete_cart_item', $this->get_hash(), $url );
		$url = add_query_arg( 'nonce', wp_create_nonce( 'sunshine_delete_cart_item' ), $url );
		return $url;
	}

	public function classes() {
		if ( ! empty( $this->product ) && is_a( $this->product, 'SPC_Product' ) ) {
			return $this->product->classes();
		}
		return false;
	}

	public function get_options() {
		return $this->options;
	}

	public function get_option( $key ) {
		if ( ! empty( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		}
		return false;
	}

	public function update_option( $key, $value ) {
		$this->options[ $key ] = $value;
		SPC()->cart->update_cart();
	}

	public function get_extra() {
		do_action( 'sunshine_cart_item_extra', $this );
	}

	public function get_options_formatted() {
		$options = $this->get_options();
		if ( ! empty( $options ) ) {
			$html = '';
			foreach ( $options as $option_id => $value ) {
				$option = new SPC_Product_Option( $option_id );
				if ( ! $option->get_id() ) {
					continue;
				}
				if ( $option_id == 'images' ) {
					$html .= sunshine_get_template_html(
						'cart/product-option-images',
						array(
							'item'   => $this,
							'option' => $option,
							'images' => $value,
						)
					);
				}
				if ( ! is_numeric( $option_id ) ) {
					continue;
				}
				$html .= sunshine_get_template_html(
					'cart/product-option-item',
					array(
						'item'           => $this,
						'option'         => $option,
						'option_item_id' => $value,
					)
				);
			}
			return $html;
		}
		return false;
	}

	public function get_meta_value( $key ) {
		if ( ! empty( $this->meta[ $key ] ) ) {
			return $this->meta[ $key ];
		}
		return false;
	}

	public function update_meta_value( $key, $value ) {
		$this->meta[ $key ] = $value;
		$cart               = SPC()->cart->get_cart();
		foreach ( $cart as $item ) {
			if ( $item['hash'] == $this->get_hash() ) {
				SPC()->cart->update_meta_value_by_hash( $item['hash'], $key, $value );
				break;
			}
		}
	}

}
