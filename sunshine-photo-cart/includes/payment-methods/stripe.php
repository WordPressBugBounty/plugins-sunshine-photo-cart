<?php
class SPC_Payment_Method_Stripe extends SPC_Payment_Method {

	private $stripe; // Stripe instance to do interact with API
	private $extra_meta_data = array();
	private $mode;
	private $publishable_key;
	private $secret_key;
	private $currency;
	private $payment_intent_id;
	private $client_secret;
	private $total = 0;

	public function init() {

		$this->id                    = 'stripe';
		$this->name                  = __( 'Stripe', 'sunshine-photo-cart' );
		$this->class                 = get_class( $this );
		$this->description           = __( 'Pay with credit card', 'sunshine-photo-cart' );
		$this->can_be_enabled        = true;
		$this->needs_billing_address = false;

		add_action( 'sunshine_stripe_connect_display', array( $this, 'stripe_connect_display' ) );
		add_action( 'admin_init', array( $this, 'stripe_connect_return' ) );
		add_action( 'admin_init', array( $this, 'stripe_disconnect_return' ) );

		if ( ! $this->is_active() || ! $this->is_allowed() ) {
			return;
		}

		add_action( 'wp', array( $this, 'init_setup' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		//add_filter( 'sunshine_checkout_allow_order_notify', array( $this, 'order_notify' ), 10, 2 );
		add_action( 'wp_ajax_nopriv_sunshine_stripe_init_order', array( $this, 'init_order' ) );
		add_action( 'wp_ajax_sunshine_stripe_init_order', array( $this, 'init_order' ) );
		add_action( 'wp', array( $this, 'payment_return' ), 20 );

		add_action( 'sunshine_checkout_create_order', array( $this, 'create_order' ), 10, 2 );

		//add_action( 'template_redirect', array( $this, 'payment_return_listener' ), 999 );
		add_action( 'template_redirect', array( $this, 'webhooks' ) );

		add_filter( 'sunshine_order_transaction_url', array( $this, 'transaction_url' ) );

		add_filter( 'sunshine_admin_order_tabs', array( $this, 'admin_order_tab' ), 10, 2 );
		add_action( 'sunshine_admin_order_tab_stripe', array( $this, 'admin_order_tab_content_stripe' ) );

		add_action( 'sunshine_order_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'sunshine_order_actions_options', array( $this, 'order_actions_options' ) );
		add_action( 'sunshine_order_process_action_stripe_refund', array( $this, 'process_refund' ) );

		add_action( 'sunshine_checkout_validation', array( $this, 'checkout_validation' ) );

	}

	public function is_allowed() {
		$account_id = $this->get_option( 'account_id_' . $this->get_mode() );
		if ( ! empty( $account_id ) ) {
			return true;
		}
		return false;
	}

	/* ADMIN */
	public function options( $options ) {

		// TODO: Need to show URL the user must use for webhook URL and how to do so

		foreach ( $options as &$option ) {
			if ( $option['id'] == 'stripe_header' && $this->get_application_fee_percent() > 0 ) {
				$option['description'] = sprintf( __( 'Note: You are using the free Stripe payment gateway integration. This includes an additional %s%% fee for payment processing on each order that goes to Sunshine Photo Cart in addition to Stripe processing fees. This added fee is removed by using the Stripe Pro add-on.', 'sunshine-photo-cart' ), $this->get_application_fee_percent() ) . ' <a href="https://www.sunshinephotocart.com/addon/stripe/?utm_source=plugin&utm_medium=link&utm_campaign=stripe" target="_blank">' . __( 'Learn more', 'sunshine-photo-cart' ) . '</a>';
			}
		}

		$options[] = array(
			'name'    => __( 'Layout', 'sunshine-photo-cart' ),
			'id'      => $this->id . '_layout',
			'type'    => 'radio',
			'options' => array(
				'tabs' => __( 'Tabs', 'sunshine-photo-cart' ),
				'accordion' => __( 'Accordion', 'sunshine-photo-cart' ),
			),
			'description' => '<a href="https://docs.stripe.com/payments/payment-element#layout" target="_blank">' . __( 'See differences in layout options', 'sunshine-photo-cart' ) . '</a>',
			'default' => 'tabs',
		);

		$options[] = array(
			'name'    => __( 'Mode', 'sunshine-photo-cart' ),
			'id'      => $this->id . '_mode',
			'type'    => 'radio',
			'options' => array(
				'live' => __( 'Live', 'sunshine-photo-cart' ),
				'test' => __( 'Test', 'sunshine-photo-cart' ),
			),
			'default' => 'live',
		);

		$options[] = array(
			'name'       => __( 'Stripe Connection (Live)', 'sunshine-photo-cart' ),
			'id'         => $this->id . '_connect_live',
			'type'       => 'stripe_connect',
			'conditions' => array(
				array(
					'field'   => $this->id . '_mode',
					'compare' => '==',
					'value'   => 'live',
					'action'  => 'show',
				),
			),
			'hide_system_info' => true,
		);
		$options[] = array(
			'name'       => __( 'Stripe Connection (Test)', 'sunshine-photo-cart' ),
			'id'         => $this->id . '_connect_test',
			'type'       => 'stripe_connect',
			'conditions' => array(
				array(
					'field'   => $this->id . '_mode',
					'compare' => '==',
					'value'   => 'test',
					'action'  => 'show',
				),
			),
			'hide_system_info' => true,
		);

		return $options;

	}

	function stripe_connect_display( $field ) {

		if ( $field['id'] == 'stripe_connect_live' ) {
			$mode = 'live';
		} else {
			$mode = 'test';
		}

		$account_id = SPC()->get_option( 'stripe_account_id_' . $mode );

		if ( $account_id ) {
			?>

			<p><a href="https://www.sunshinephotocart.com/?stripe_disconnect=1&account_id=<?php echo $account_id; ?>&mode=<?php echo $mode; ?>&return_url=<?php echo admin_url( 'admin.php?sunshine_stripe_disconnect_return' ); ?>" class="sunshine-stripe-connect"><span><?php _e( 'Disconnect from', 'sunshine-photo-cart' ); ?></span> <span class="stripe">Stripe</span></a></p>

		<?php } else { ?>

			<p><a href="https://www.sunshinephotocart.com/?stripe_connect=1&nonce=<?php echo wp_create_nonce( 'sunshine_stripe_connect' ); ?>&return_url=<?php echo admin_url( 'admin.php?sunshine_stripe_connect_return' ); ?>&mode=<?php echo $mode; ?>" class="sunshine-stripe-connect"><span><?php _e( 'Connect to', 'sunshine-photo-cart' ); ?></span> <span class="stripe">Stripe</span></a></p>

			<?php
		}
	}

	function stripe_connect_return() {

		if ( ! isset( $_GET['sunshine_stripe_connect_return'] ) || ! current_user_can( 'sunshine_manage_options' ) ) {
			return false;
		}

		if ( isset( $_GET['error'] ) || empty( $_GET['account_id'] ) || empty( $_GET['publishable_key'] ) || empty( $_GET['secret_key'] ) || ! wp_verify_nonce( $_GET['nonce'], 'sunshine_stripe_connect' ) ) {
			SPC()->notices->add_admin( 'stripe_connect_fail', __( 'Stripe could not be connected', 'sunshine-photo-cart' ), 'error' );
			wp_redirect( admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) );
			exit;
		}

		if ( isset( $_GET['mode'] ) && $_GET['mode'] == 'live' ) {
			$mode = 'live';
		} else {
			$mode = 'test';
		}

		// Set some return values from Stripe Connect
		SPC()->update_option( 'stripe_account_id_' . $mode, sanitize_text_field( $_GET['account_id'] ) );
		SPC()->update_option( 'stripe_publishable_key_' . $mode, sanitize_text_field( $_GET['publishable_key'] ) );
		SPC()->update_option( 'stripe_secret_key_' . $mode, sanitize_text_field( $_GET['secret_key'] ) );
		SPC()->update_option( 'stripe_mode', $mode );

		SPC()->notices->add_admin( 'stripe_connected', __( 'Stripe has successfully been connected', 'sunshine-photo-cart' ), 'success' );

		wp_redirect( admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) );
		exit;

	}

	private function get_webhook_url() {
		$url = get_bloginfo( 'url' );
		$url = add_query_arg(
			array(
				'sunshine_stripe_webhook' => 1,
			),
			$url
		);
		return $url;
	}

	function stripe_disconnect_return() {

		if ( ! isset( $_GET['sunshine_stripe_disconnect_return'] ) || empty( $_GET['status'] ) ) {
			return;
		}

		if ( isset( $_GET['mode'] ) && $_GET['mode'] == 'live' ) {
			$mode = 'live';
		} else {
			$mode = 'test';
		}

		/*
		// TODO: Remove webhook
		if ( SPC()->get_option( 'stripe_webhook_' . $mode ) ) {
			$this->setup();
			try {
				$this->stripe->webhookEndpoints->delete(
					SPC()->get_option( 'stripe_webhook_' . $mode ),
					array()
				);
			} catch ( Exception $e ) {
				SPC()->notices->add_admin( 'stripe_delete_webhook', sprintf( __( 'Failed deleting webhook: %s', 'sunshine-photo-cart' ), $e->getError()->message ), 'error' );
			}
		}
		*/

		SPC()->update_option( 'stripe_account_id_' . $mode, '' );
		SPC()->update_option( 'stripe_publishable_key_' . $mode, '' );
		SPC()->update_option( 'stripe_secret_key_' . $mode, '' );
		SPC()->update_option( 'stripe_webhook_' . $mode, '' );
		SPC()->update_option( 'stripe_webhook_secret_' . $mode, '' );

		SPC()->notices->add_admin( 'stripe_disconnected_success', __( 'Stripe has successfully been disconnected', 'sunshine-photo-cart' ), 'success' );

		wp_redirect( admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) );
		exit;

	}


	/* PUBLIC */
	public function init_setup() {

		if ( ! is_sunshine_page( 'checkout' ) || ! $this->is_allowed() ) {
			return;
		}

		$this->setup();
		$this->setup_payment_intent();

	}

	private function setup( $mode = '' ) {

		$this->currency = SPC()->get_option( 'currency' );
		return;

		if ( empty( $mode ) ) {
			$mode = $this->get_mode();
		}

		if ( empty( $this->get_publishable_key( $mode ) ) || empty( $this->get_secret_key( $mode ) ) ) {
			return false;
		}

		//\Stripe\Stripe::setAppInfo( 'WordPress Sunshine Photo Cart', SUNSHINE_PHOTO_CART_VERSION, get_bloginfo( 'url' ) );

		//$this->stripe = new \Stripe\StripeClient( $this->get_secret_key( $mode ) );


	}

	private function get_mode() {
		return $this->get_option( 'mode' );
	}

	private function get_publishable_key( $mode = '' ) {
		return ( $mode == 'live' || $this->get_mode() == 'live' ) ? SPC()->get_option( $this->id . '_publishable_key_live' ) : SPC()->get_option( $this->id . '_publishable_key_test' );
	}

	private function get_secret_key( $mode = '' ) {
		return ( $mode == 'live' || $this->get_mode() == 'live' ) ? SPC()->get_option( $this->id . '_secret_key_live' ) : SPC()->get_option( $this->id . '_secret_key_test' );
	}

	private function get_account_id( $mode = '' ) {
		return ( $mode == 'live' || $this->get_mode() == 'live' ) ? SPC()->get_option( $this->id . '_account_id_live' ) : SPC()->get_option( $this->id . '_account_id_test' );
	}

	private function get_payment_intent_id() {
		return $this->payment_intent_id;
	}

	private function get_client_secret() {
		return $this->client_secret;
	}

	public function enqueue_scripts() {

		if ( ! is_sunshine_page( 'checkout' ) || empty( $this->get_publishable_key() ) || empty( $this->get_account_id() ) || empty( $this->get_payment_intent_id() ) ) {
			return false;
		}

		wp_enqueue_script( 'sunshine-stripe', 'https://js.stripe.com/v3/' );
		wp_enqueue_script( 'sunshine-stripe-processing', SUNSHINE_PHOTO_CART_URL . 'assets/js/stripe-processing.js', array( 'jquery' ), SUNSHINE_PHOTO_CART_VERSION, true );
		wp_localize_script(
			'sunshine-stripe-processing',
			'spc_stripe_vars',
			array(
				'publishable_key'   => $this->get_publishable_key(),
				'account_id'        => $this->get_account_id(),
				'client_secret'     => $this->get_client_secret(),
				'payment_intent_id' => $this->get_payment_intent_id(),
				'layout'			=> ( $this->get_option( 'layout' ) ) ? $this->get_option( 'layout' ) : 'tabs',
				'return_url'        => sunshine_get_page_url( 'checkout' ) . '?section=payment&stripe_payment_return',
				'ajax_url'          => admin_url( 'admin-ajax.php' ),
				'security'          => wp_create_nonce( 'sunshine_stripe' ),
			)
		);

	}

	public function create_order_status( $status, $order ) {
		if ( $order->get_payment_method() == $this->id ) {
			return 'new'; // Straight to new
		}
		return $status;
	}

	public function setup_payment_intent() {

		if ( empty( SPC()->cart ) ) {
			SPC()->cart->setup();
		}

		// Set the cart total in cents
		$cart_total  = SPC()->cart->get_total();
		if ( $cart_total <= 0 ) {
			return; // Don't create if there is no amount to charge yet.
		}

		$this->total = round( 100 * $cart_total );

		//sunshine_log( 'stripe total: ' . $this->total );

		// See if we have a valid customer
		$stripe_customer_id = $this->get_stripe_customer_id();
		if ( $stripe_customer_id ) {
		    $response = wp_remote_get("https://api.stripe.com/v1/customers/$stripe_customer_id", array(
		        'headers' => array(
		            'Authorization' => 'Bearer ' . $this->get_secret_key(),
		            'Content-Type'  => 'application/x-www-form-urlencoded',
		        )
		    ));

		    if ( is_wp_error( $response ) ) {
		        // Handle WordPress HTTP error
		        $this->set_stripe_customer_id( '' );
		        $stripe_customer_id = '';
		    } else {
		        $stripe_customer = json_decode( wp_remote_retrieve_body( $response ), true );
		        if ( isset( $stripe_customer['error'] ) ) {
		            // Handle Stripe error, unset customer ID if not found or other API issue
		            $this->set_stripe_customer_id('');
		            $stripe_customer_id = '';
		        }
		    }
		}

		// Set up payment intent
		if ( empty( SPC()->session->get( 'stripe_payment_intent_id' ) ) ) {

			$args = array(
				'amount'                    => $this->total,
				'currency'                  => $this->currency,
				'automatic_payment_methods' => array(
					'enabled' => 'true',
				),
			);
			if ( $this->get_application_fee_amount() ) {
				$args['application_fee_amount'] = $this->get_application_fee_amount();
			}
			$args['shipping']['name']    = SPC()->cart->get_checkout_data_item( 'first_name' ) . ' ' . SPC()->cart->get_checkout_data_item( 'last_name' );
			$args['shipping']['address'] = array(
				'city'        => ( SPC()->cart->get_checkout_data_item( 'shipping_city' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_city' ) : '',
				'country'     => ( SPC()->cart->get_checkout_data_item( 'shipping_country' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_city' ) : '',
				'line1'       => ( SPC()->cart->get_checkout_data_item( 'shipping_address1' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_address1' ) : '',
				'line2'       => ( SPC()->cart->get_checkout_data_item( 'shipping_address2' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_address2' ) : '',
				'postal_code' => ( SPC()->cart->get_checkout_data_item( 'shipping_postcode' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_postcode' ) : '',
				'state'       => ( SPC()->cart->get_checkout_data_item( 'shipping_state' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_state' ) : '',
			);
			if ( $stripe_customer_id ) {
				$args['customer'] = $stripe_customer_id;
			}

			$response = wp_remote_post('https://api.stripe.com/v1/payment_intents', array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Authorization' => 'Bearer ' . $this->get_secret_key(),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'        => http_build_query( $args ),
				)
			);

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				SPC()->log( 'Failed making payment intent: ' . $error_message );
				return;
			} else {
				$intent = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $intent['error'] ) ) {
					SPC()->log( 'Stripe API error creating new payment intent: ' . $intent['error']['message'] );
					return;
				}
				SPC()->log( 'Created stripe payment intent: ' . $intent['id'] );
				SPC()->session->set( 'stripe_payment_intent_id', $intent['id'] );
				$this->payment_intent_id = $intent['id'];
				$this->client_secret = $intent['client_secret'];
				return;
			}

		} elseif ( ! isset( $_GET['stripe_payment_return'] ) ) { // Don't force create during redirect process

			$args = array(
				'amount'                 => $this->total,
				'currency'               => $this->currency,
			);
			if ( $this->get_application_fee_amount() ) {
				$args['application_fee_amount'] = $this->get_application_fee_amount();
			}
			$args['shipping']['name']    = SPC()->cart->get_checkout_data_item( 'first_name' ) . ' ' . SPC()->cart->get_checkout_data_item( 'last_name' );
			$args['shipping']['address'] = array(
				'city'        => ( SPC()->cart->get_checkout_data_item( 'shipping_city' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_city' ) : '',
				'country'     => ( SPC()->cart->get_checkout_data_item( 'shipping_country' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_city' ) : '',
				'line1'       => ( SPC()->cart->get_checkout_data_item( 'shipping_address1' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_address1' ) : '',
				'line2'       => ( SPC()->cart->get_checkout_data_item( 'shipping_address2' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_address2' ) : '',
				'postal_code' => ( SPC()->cart->get_checkout_data_item( 'shipping_postcode' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_postcode' ) : '',
				'state'       => ( SPC()->cart->get_checkout_data_item( 'shipping_state' ) ) ? SPC()->cart->get_checkout_data_item( 'shipping_state' ) : '',
			);
			if ( $stripe_customer_id ) {
				$args['customer'] = $stripe_customer_id;
			}

			$payment_intent_id = SPC()->session->get( 'stripe_payment_intent_id' );

			$response = wp_remote_post("https://api.stripe.com/v1/payment_intents/{$payment_intent_id}", array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Authorization' => 'Bearer ' . $this->get_secret_key(),
						'Content-Type'  => 'application/x-www-form-urlencoded',
					),
					'body'        => http_build_query( $args ),
				)
			);

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				SPC()->log( 'Failed updating payment intent: ' . $error_message );
				return;
			} else {
				$intent = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $intent['error'] ) ) {
					SPC()->log( 'Stripe API error updating payment intent (' . $payment_intent_id . '): ' . $intent['error']['message'] );
					SPC()->session->set( 'stripe_payment_intent_id', '' );
					$this->setup_payment_intent();
					return;
				} elseif ( $intent['status'] != 'requires_payment_method' ) {
					SPC()->log( 'Stripe payment intent invalid status, resetting (' . $payment_intent_id . ')' );
					SPC()->session->set( 'stripe_payment_intent_id', '' );
					$this->setup_payment_intent();
					return;
				}
				SPC()->log( 'Updated Stripe payment intent: ' . $intent['id'] );
				$this->payment_intent_id = $intent['id'];
				$this->client_secret = $intent['client_secret'];
				return;
			}

			// If something messed up, let's just create a new one
			$args = array(
				'amount'                    => $this->total,
				'currency'                  => $this->currency,
				'automatic_payment_methods' => array(
					'enabled' => 'true',
				),
			);
			if ( $this->get_application_fee_amount() ) {
				$args['application_fee_amount'] = $this->get_application_fee_amount();
			}
			if ( $stripe_customer_id ) {
				$args['customer'] = $stripe_customer_id;
				// $args['setup_future_usage'] = 'on_session'; // on_session = person must be present. We are only storing this for easier future use when checking out on website not for automated recurring billing
			}

			$response = wp_remote_post('https://api.stripe.com/v1/payment_intents', array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(
					'Authorization' => 'Bearer ' . $this->get_secret_key(),
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'        => http_build_query( $args ),
				)
			);

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				SPC()->log( 'Failed making payment intent: ' . $error_message );
				return;
			} else {
				$intent = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $intent['error'] ) ) {
					SPC()->log( 'Stripe API error: ' . $intent['error']['message'] );
					return;
				}
				SPC()->log( 'Fallback create Stripe payment intent: ' . $intent['id'] );
				SPC()->session->set( 'stripe_payment_intent_id', $intent['id'] );
				$this->payment_intent_id = $intent['id'];
				$this->client_secret = $intent['client_secret'];
				return;
			}

		}

	}

	public function payment_return() {

		if ( ! isset( $_GET['stripe_payment_return'] ) || ! isset( $_GET['payment_intent'] ) || ! isset( $_GET['payment_intent_client_secret'] ) ) {
			return false;
		}

		SPC()->log( 'Returned from Stripe after async payment' );

		$payment_intent_id = sanitize_text_field( $_GET['payment_intent'] );
		$client_secret = sanitize_text_field( $_GET['payment_intent_client_secret'] );

		$response = wp_remote_get("https://api.stripe.com/v1/payment_intents/$payment_intent_id", array(
	        'headers' => array(
	            'Authorization' => 'Bearer ' . $this->get_secret_key(),
	            'Content-Type'  => 'application/x-www-form-urlencoded',
	        )
	    ));

	    if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
	        // Handle error or invalid response
	        return;
	    }

	    $payment_intent_object = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $payment_intent_object['error'] ) ) {
			SPC()->log( 'Check payment intent on checkout error: ' . $payment_intent_object['error'] );
			return;
		}

		if ( ! empty( $payment_intent_object['status'] ) && $payment_intent_object['status'] == 'succeeded' ) {
			$order = SPC()->cart->create_order();
			if ( $order ) {
				$url = apply_filters( 'sunshine_checkout_redirect', $order->get_received_permalink() );
				SPC()->log( 'Created new order after stripe asynchronous payment and is redirecting' );
				wp_safe_redirect( $url );
				exit;
			}
		}

		SPC()->notices->add( __( 'Could not process order, please try another payment method', 'sunshine-photo-cart' ), 'error' );
		wp_safe_redirect( sunshine_get_page_url( 'checkout' ) );
		exit;

	}

	public function get_stripe_customer_id() {
		return SPC()->customer->sunshine_stripe_customer_id;
	}

	public function set_stripe_customer_id( $id ) {
		return SPC()->customer->sunshine_stripe_customer_id = $id;
	}

	private function get_application_fee_percent() {
		return floatval( apply_filters( 'sunshine_stripe_application_fee_percent', 5 ) );
	}

	private function get_application_fee_amount() {

		$percentage = $this->get_application_fee_percent();

		// Some countries do not allow us to use application fees. If we are in one of those
		// countries, we should set the percentage to 0. This is a temporary fix until we
		// have a better solution or until all countries allow us to use application fees.
		$country                               = SPC()->get_option( 'country' );
		$countries_to_disable_application_fees = array(
			'IN', // India.
			'MX', // Mexico.
			'MY', // Malaysia.
		);
		if ( in_array( $country, $countries_to_disable_application_fees ) ) {
			$percentage = 0;
		}

		if ( $percentage <= 0 ) {
			return 0;
		}

		$percentage = floatval( $percentage );

		return round( $this->total * ( $percentage / 100 ) );

	}

	public function get_fields() {

		ob_start();

		if ( $this->get_mode() == 'test' ) {
			echo '<div class="sunshine--payment--test">' . __( 'This will be processed as a test payment and no real money will be exchanged', 'sunshine-photo-cart' ) . '</div>';
		}
		?>

		<div id="sunshine-stripe-payment">
			<div id="sunshine-stripe-payment-fields"></div>
			<div id="sunshine-stripe-payment-errors"></div>
		</div>

		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;

	}

	public function order_notify( $notify, $order ) {
		if ( $order->get_payment_method() == $this->id ) {
			return false;
		}
		return $notify;
	}

	public function init_order() {

		if ( empty( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'sunshine_stripe' ) ) {
			wp_send_json_error( array( 'reasons' => __( 'Failed to pass security', 'sunshine-photo-cart' ) ) );
		}

		// Have to setup here because it is special case during ajax request
		$this->setup();
		$this->setup_payment_intent();

		// We are creating the order but not yet paid
		$reasons = array();

		if ( empty( $this->payment_intent_id ) ) {
			$reasons[] = __( 'No Stripe payment intent id', 'sunshine-photo-cart' );
		}

		// See if customer has stripe customer id
		if ( is_user_logged_in() ) {
			if ( empty( $this->get_stripe_customer_id() ) ) {

				$args = array(
					'email'    => SPC()->customer->get_email(),
					'name'     => SPC()->customer->get_name(),
					'shipping' => array(
						'name'    => SPC()->customer->get_name(),
						'address' => array(
							'city'        => SPC()->customer->get_shipping_city(),
							'country'     => SPC()->customer->get_shipping_country(),
							'line1'       => SPC()->customer->get_shipping_address(),
							'line2'       => SPC()->customer->get_shipping_address2(),
							'postal_code' => SPC()->customer->get_shipping_postcode(),
							'state'       => SPC()->customer->get_shipping_state(),
						),
					),
				);

				$body = http_build_query( $args );

				$response = wp_remote_post('https://api.stripe.com/v1/customers', array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Authorization' => 'Bearer ' . $this->get_secret_key(),
						'Content-Type'  => 'application/x-www-form-urlencoded',
					),
					'body'        => $body,
					'cookies'     => array()
					)
				);

				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					SPC()->log( 'Failed creating Stripe customer: ' . $error_message );
					return;
				} else {
					$customer = json_decode(wp_remote_retrieve_body($response), true);
					if ( isset( $customer['error'] ) ) {
						SPC()->log( 'Stripe create customer error: ' . $customer['error']['message'] );
						wp_send_json_error( array( 'reasons' => 'Could not create customer in Stripe: ' . $customer['error']['message'] ) );
					}
					SPC()->customer->update_meta( 'stripe_customer_id', $customer['id'] );
					$this->setup_payment_intent(); // Reset payment intent so it includes new stripe customer id
				}

			} else {

				$customer_id = $this->get_stripe_customer_id(); // Retrieve the Stripe customer ID
				$args = array(
					'email'    => SPC()->customer->get_email(),
					'name'     => SPC()->customer->get_name(),
					'shipping[name]'         => SPC()->customer->get_name(),
					'shipping[address][city]'        => SPC()->customer->get_shipping_city(),
					'shipping[address][country]'     => SPC()->customer->get_shipping_country(),
					'shipping[address][line1]'       => SPC()->customer->get_shipping_address(),
					'shipping[address][line2]'       => SPC()->customer->get_shipping_address2(),
					'shipping[address][postal_code]' => SPC()->customer->get_shipping_postcode(),
					'shipping[address][state]'       => SPC()->customer->get_shipping_state(),
				);

				$body = http_build_query($args);

				$response = wp_remote_post("https://api.stripe.com/v1/customers/$customer_id", array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(
						'Authorization' => 'Bearer ' . $this->get_secret_key(),
						'Content-Type'  => 'application/x-www-form-urlencoded',
					),
					'body'        => $body,
					'cookies'     => array()
				));

				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					SPC()->log( 'Could not update customer in Stripe: ' . $error_message );
					wp_send_json_error( array( 'reasons' => 'Could not update customer in Stripe: ' . $error_message ) );
					return;
				} else {
					$result = json_decode( wp_remote_retrieve_body( $response ), true );
					if ( isset( $result['error'] ) ) {
						SPC()->log( 'Stripe API error: ' . $result['error']['message'] );
						wp_send_json_error( array( 'reasons' => 'Stripe API error: ' . $result['error']['message'] ) );
						return;
					}
				}

			}

		}

		wp_send_json_success();

	}


	function webhooks() {

		if ( ! isset( $_GET['sunshine_stripe_webhook'] ) ) {
			return;
		}

		SPC()->log( 'Received Stripe webhook' );

		$this->setup();

		$payload = @file_get_contents( 'php://input' );

		$payload_data = maybe_unserialize( $payload );
		SPC()->log( 'Payload: ' . print_r( $payload_data, 1 ) );

		// sunshine_log( $payload_data, 'webhook payload data' );
		if ( isset( $payload_data['livemode'] ) && $payload_data['livemode'] ) {
			$mode = 'live';
		} else {
			$mode = 'test';
		}

		$endpoint_secret = $this->get_option( 'stripe_webhook_secret_' . $mode );
		//$endpoint_secret = 'whsec_e44d4ef0b753a2ce80c9eacf0f00a183f68462f29dff87a6bb6418f7c9613e21'; // TODO: Remove, for Local testing

		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event      = null;

		try {
			$event = \Stripe\Webhook::constructEvent(
				$payload,
				$sig_header,
				$endpoint_secret
			);
		} catch ( \UnexpectedValueException $e ) {
			// Invalid payload
			http_response_code( 400 );
			exit();
		} catch ( \Stripe\Exception\SignatureVerificationException $e ) {
			// Invalid signature
			http_response_code( 400 );
			exit();
		}

		SPC()->log( 'Event: ' . print_r( $event, 1 ) );

		// Handle the event

		switch ( $event->type ) {
			case 'payment_intent.succeeded': // Successful payment!
				// Do we need to actually do anything here?
				break;
			case 'charge.refunded':
				/*
				foreach ( $event->data->object->refunds->data as $refund ) {
					$order = $this->get_order_by_payment_intent( $refund->payment_intent );
					if ( $order ) {
						$order->set_status( 'refunded' );
						$order->add_refund( ( $refund->amount / 100 ), $refund->reason );
						$order->update();
					} else {
						echo 'Unknown order';
					}
				}
				*/
			default:
				echo 'Received unknown event type ' . $event->type;
		}

		http_response_code( 200 );

	}

	public function create_order( $order, $data ) {

		if ( empty( $data['payment_method'] ) || $data['payment_method'] != $this->id ) {
			return;
		}

		$payment_intent_id = SPC()->session->get( 'stripe_payment_intent_id' );

		$order->update_meta_value( 'paid_date', current_time( 'timestamp' ) );
		$order->update_meta_value( 'stripe_payment_intent_id', $payment_intent_id );

		SPC()->session->set( 'stripe_payment_intent_id', '' ); // Remove session payment intent so we don't use again.

		$this->setup();

		$response = wp_remote_get( "https://api.stripe.com/v1/payment_intents/$payment_intent_id", array(
	        'headers' => array(
	            'Authorization' => 'Bearer ' . $this->get_secret_key(),
	            'Content-Type'  => 'application/x-www-form-urlencoded',
	        )
	    ));

	    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
	        // Handle error or invalid response
	        return;
	    }

	    $payment_intent_object = json_decode( wp_remote_retrieve_body( $response ), true );
	    if ( isset( $payment_intent_object['error'] ) ) {
			SPC()->log( 'Create order get payment intent error' );
	        // Handle Stripe error
	        return;
	    }

	    if ( ! empty( $payment_intent_object['source'] ) ) {
	        $order->update_meta_value( 'source', sanitize_text_field( $payment_intent_object['source'] ) );
	    }
	    if ( ! empty( $payment_intent_object['application_fee_amount'] ) ) {
	        $order->update_meta_value( 'application_fee_amount', sanitize_text_field( $payment_intent_object['application_fee_amount'] ) / 100 );
	    }

	    // Continue to update metadata
	    $args = array(
	        'metadata' => array(
	            'order_id'  => $order->get_id(),
	            'site'      => get_bloginfo('name'),
	            'order_url' => admin_url('post.php?post=' . $order->get_id() . '&action=edit'),
	        ),
	    );
	    $body = http_build_query( array( 'metadata' => json_encode($args['metadata'] ) ) );

	    $update_response = wp_remote_post("https://api.stripe.com/v1/paymentIntents/$payment_intent_id", array(
	        'method'      => 'POST',
	        'headers'     => array(
	            'Authorization' => 'Bearer ' . $this->get_secret_key(),
	            'Content-Type'  => 'application/x-www-form-urlencoded',
	        ),
	        'body'        => $body,
	    ));

	    if ( is_wp_error( $update_response ) || 200 !== wp_remote_retrieve_response_code( $update_response ) ) {
	        // Handle error or invalid response during update
	        return;
	    }

	    $updated_intent = json_decode( wp_remote_retrieve_body( $update_response ), true);
	    if ( isset( $updated_intent['error'] ) ) {
	        // Handle Stripe error during update
	        return;
	    }

	}

	private function get_order_by_payment_intent( $payment_intent_id ) {
		$args   = array(
			'post_type'  => 'sunshine-order',
			'meta_query' => array(
				array(
					'key'   => 'stripe_payment_intent_id',
					'value' => $payment_intent_id,
				),
			),
		);
		$orders = get_posts( $args );
		if ( ! empty( $orders ) ) {
			$order = new SPC_Order( $orders[0] );
			return $order;
		}
		return false;

	}

	public function get_transaction_id( $order ) {
		return $order->get_meta_value( 'stripe_payment_intent_id' );
	}

	public function get_transaction_url( $order ) {
		if ( $order->get_payment_method() == 'stripe' ) {
			$payment_intent_id = $this->get_transaction_id( $order );
			if ( $payment_intent_id ) {
				$mode             = $order->get_mode();
				$transaction_url  = ( $mode == 'test' || $mode == 'sandbox' ) ? 'https://dashboard.stripe.com/test/payments/' : 'https://dashboard.stripe.com/payments/';
				$transaction_url .= $payment_intent_id;
				return $transaction_url;
			}
		}
		return false;
	}


	public function admin_order_tab( $tabs, $order ) {
		if ( $order->get_payment_method() == $this->id ) {
			$tabs['stripe'] = __( 'Stripe', 'sunshine-photo-cart' );
		}
		return $tabs;
	}

	public function admin_order_tab_content_stripe( $order ) {

		echo '<table class="sunshine-data">';
		echo '<tr><th>' . __( 'Transaction ID', 'sunshine-photo-cart' ) . '</th>';
		echo '<td>' . $this->get_transaction_id( $order ) . '</td></tr>';
		$application_fee_amount = $order->get_meta_value( 'application_fee_amount' );
		if ( $application_fee_amount ) {
			echo '<tr>';
			echo '<th>' . __( 'Application Fee Amount (To Sunshine)', 'sunshine-photo-cart' ) . '</th>';
			echo '<td>' . sunshine_price( $application_fee_amount ) . ' (<a href="https://www.sunshinephotocart.com/upgrade/?utm_source=plugin&utm_medium=link&utm_campaign=stripe" target="_blank">' . __( 'Upgrade to remove this fee on future transactions', 'sunshine-photo-cart' ) . '</a>)' . '</td>';
			echo '</tr>';
		}
		echo '</table>';

	}


	function order_actions( $actions, $post_id ) {
		$order = new SPC_Order( $post_id );
		if ( $order->get_payment_method() == $this->id ) {
			$actions[ $this->id . '_refund'] = sprintf( __( 'Refund payment in %s', 'sunshine-photo-cart' ), $this->name );
		}
		return $actions;
	}

	function order_actions_options( $order ) {
		?>
		<div id="stripe-refund-order-actions" style="display: none;">
			<p><label><input type="checkbox" name="stripe_refund_notify" value="yes" checked="checked" /> <?php _e( 'Notify customer via email', 'sunshine-photo-cart' ); ?></label></p>
			<p><label><input type="checkbox" name="stripe_refund_full" value="yes" checked="checked" /> <?php _e( 'Full refund', 'sunshine-photo-cart' ); ?></label></p>
			<p id="stripe-refund-amount" style="display: none;"><label><input type="number" name="stripe_refund_amount" step=".01" size="6" style="width:100px" max="<?php echo esc_attr( $order->get_total_minus_refunds() ); ?>" value="<?php echo esc_attr( $order->get_total_minus_refunds() ); ?>" /> <?php _e( 'Amount to refund', 'sunshine-photo-cart' ); ?></label></p>
		</div>
		<script>
			jQuery( 'select[name="sunshine_order_action"]' ).on( 'change', function(){
				let selected_action = jQuery( 'option:selected', this ).val();
				if ( selected_action == 'stripe_refund' ) {
					jQuery( '#stripe-refund-order-actions' ).show();
				} else {
					jQuery( '#stripe-refund-order-actions' ).hide();
				}
			});
			jQuery( 'input[name="stripe_refund_full"]' ).on( 'change', function(){
				if ( !jQuery(this).prop( "checked" ) ) {
					jQuery( '#stripe-refund-amount' ).show();
				} else {
					jQuery( '#stripe-refund-amount' ).hide();
				}
			});
		</script>
		<?php
	}

	function process_refund( $order_id ) {

		$order = new SPC_Order( $order_id );

		$this->setup( $order->get_mode() );

		$payment_intent_id = $order->get_meta_value( 'stripe_payment_intent_id' );

		$response = wp_remote_get("https://api.stripe.com/v1/payment_intents/$payment_intent_id", array(
			'timeout'     => 45,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $this->get_secret_key( $order->get_mode() ),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			)
		));

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			SPC()->log( 'Stripe refund error: ' . $error_message );
			SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, sprintf( __( 'Failed to connect: %s', 'sunshine-photo-cart' ), $error_message ) );
			$order->add_log( sprintf( 'Failed to connect to Stripe to retrieve payment intent (Order ID: %s)', $order_id ) );
			return;
		}

		$payment_intent = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( isset( $payment_intent['error'] ) ) {
			SPC()->log( 'Stripe refund error: ' . $payment_intent['error']['message'] );
			SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, __( 'Order not found in Stripe to process refund', 'sunshine-photo-cart' ) );
			$order->add_log( sprintf( 'Failed to connect to Stripe to process refund (Order ID: %s)', $order_id ) );
			return;
		}

		$refund_amount = $order->get_total_minus_refunds();

		if ( ! empty( $_POST['stripe_refund_amount'] ) && $_POST['stripe_refund_amount'] < $refund_amount ) {
			$refund_amount = sanitize_text_field( $_POST['stripe_refund_amount'] );
		}

		$refund_amount_stripe = $refund_amount * 100; // Lose decimals because Stripe

		// Don't allow refund for more than the charged amount
		if ( $refund_amount_stripe > $payment_intent['amount'] ) {
			SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, __( 'Refund amount is higher than allowed', 'sunshine-photo-cart' ), 'error' );
			$order->add_log( sprintf( __( 'Refund amount is higher than allowed (Total allowed: %1$s, Refund Requested: %2$s)', 'sunshine-photo-cart' ), ( $payment_intent['amount'] / 100 ), $refund_amount ) );
			return;
		}

		$args = array(
			'payment_intent'         => $payment_intent_id,
			'amount'                 => $refund_amount_stripe,
		);
		$application_fee_amount = $order->get_meta_value( 'application_fee_amount' );
		if ( $application_fee_amount ) {
			$args['refund_application_fee'] = 'true';
		}

		$body = http_build_query( $args );

	    $response = wp_remote_post( "https://api.stripe.com/v1/refunds", array(
	        'method'      => 'POST',
	        'timeout'     => 45,
	        'headers'     => array(
	            'Authorization' => 'Bearer ' . $this->get_secret_key( $order->get_mode() ),
	            'Content-Type'  => 'application/x-www-form-urlencoded',
	        ),
	        'body' => $body,
	    ));

	    if ( is_wp_error( $response ) ) {
	        $error_message = $response->get_error_message();
	        SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, sprintf( __( 'Could not refund payment: %s', 'sunshine-photo-cart' ), $error_message), 'error' );
	        $order->add_log( sprintf( 'Could not refund payment in Stripe: %s', $error_message ) );
	        return;
	    }

	    $refund_response = json_decode( wp_remote_retrieve_body( $response ), true );
	    if ( isset( $refund_response['error'] ) ) {
	        SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, sprintf( __( 'Could not refund payment: %s', 'sunshine-photo-cart' ), $refund_response['error']['message'] ), 'error' );
	        $order->add_log( sprintf( 'Could not refund payment in Stripe: %s', $refund_response['error']['message'] ) );
	        return;
	    }

		$order->set_status( 'refunded' );
		$order->add_refund( $refund_amount );
		SPC()->notices->add_admin( 'stripe_refund_success_' . $payment_intent_id, sprintf( __( 'Refund has been processed for %s', 'sunshine-photo-cart' ), sunshine_price( $refund_amount ) ) );

		if ( ! empty( $_POST['stripe_refund_notify'] ) ) {
			$order->notify( false );
			SPC()->notices->add_admin( 'stripe_refund_notify_' . $payment_id, __( 'Customer sent email about refund', 'sunshine-photo-cart' ) );
		}

	}

	public function mode( $mode, $order ) {
		if ( $order->get_payment_method() == 'stripe' ) {
			return ( $this->get_mode() == 'live' ) ? 'live' : 'test';
		}
		return $mode;
	}

	public function checkout_validation( $section ) {
		if ( $section == 'payment' && SPC()->cart->get_total() > 0 && SPC()->cart->get_checkout_data_item( 'payment_method' ) == 'stripe' ) {
			if ( empty( $_POST['stripe_order_id'] ) ) {
				SPC()->cart->add_error( __( 'Invalid payment', 'sunshine-photo-cart' ) );
			}
		}
	}


}
