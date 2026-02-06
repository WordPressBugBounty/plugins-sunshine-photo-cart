<?php
/**
 * Stripe Payment Method Class
 *
 * Handles Stripe payment processing including payment intents, webhooks, and refunds.
 *
 * @package Sunshine_Photo_Cart
 * @subpackage Payment_Methods
 */
class SPC_Payment_Method_Stripe extends SPC_Payment_Method {

	/**
	 * Stripe API instance for interacting with Stripe API
	 *
	 * @var object
	 */
	private $stripe;

	/**
	 * Extra metadata for orders
	 *
	 * @var array
	 */
	private $extra_meta_data = array();

	/**
	 * Stripe mode (live or test)
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Stripe publishable key
	 *
	 * @var string
	 */
	private $publishable_key;

	/**
	 * Stripe secret key
	 *
	 * @var string
	 */
	private $secret_key;

	/**
	 * Currency code
	 *
	 * @var string
	 */
	private $currency;

	/**
	 * Stripe payment intent ID
	 *
	 * @var string
	 */
	private $payment_intent_id;

	/**
	 * Stripe client secret for payment intent
	 *
	 * @var string
	 */
	private $client_secret;

	/**
	 * Total amount in cents
	 *
	 * @var int
	 */
	private $total = 0;

	/**
	 * Make a Stripe API request
	 *
	 * @param string $endpoint The API endpoint
	 * @param array  $args Request arguments
	 * @param string $method HTTP method (GET, POST, etc.)
	 * @return array|WP_Error Response data or error
	 */
	private function make_stripe_request( $endpoint, $args = array(), $method = 'GET' ) {
		$url = 'https://api.stripe.com/v1/' . ltrim( $endpoint, '/' );

		$request_args = array(
			'method'      => $method,
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(
				'Authorization' => 'Bearer ' . $this->get_secret_key(),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
		);

		if ( ! empty( $args ) ) {
			if ( $method === 'GET' ) {
				$url = add_query_arg( $args, $url );
			} else {
				$request_args['body'] = http_build_query( $args );
			}
		}

		$response = wp_remote_request( $url, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'stripe_api_error', $body['error']['message'], $body['error'] );
		}

		return $body;
	}

	/**
	 * Build shipping address array for Stripe
	 *
	 * @return array Shipping address array
	 */
	private function build_shipping_address() {
		return array(
			'name'    => SPC()->cart->get_checkout_data_item( 'first_name' ) . ' ' . SPC()->cart->get_checkout_data_item( 'last_name' ),
			'address' => array(
				'city'        => SPC()->cart->get_checkout_data_item( 'shipping_city' ) ? SPC()->cart->get_checkout_data_item( 'shipping_city' ) : '',
				'country'     => SPC()->cart->get_checkout_data_item( 'shipping_country' ) ? SPC()->cart->get_checkout_data_item( 'shipping_country' ) : '',
				'line1'       => SPC()->cart->get_checkout_data_item( 'shipping_address1' ) ? SPC()->cart->get_checkout_data_item( 'shipping_address1' ) : '',
				'line2'       => SPC()->cart->get_checkout_data_item( 'shipping_address2' ) ? SPC()->cart->get_checkout_data_item( 'shipping_address2' ) : '',
				'postal_code' => SPC()->cart->get_checkout_data_item( 'shipping_postcode' ) ? SPC()->cart->get_checkout_data_item( 'shipping_postcode' ) : '',
				'state'       => SPC()->cart->get_checkout_data_item( 'shipping_state' ) ? SPC()->cart->get_checkout_data_item( 'shipping_state' ) : '',
			),
		);
	}

	/**
	 * Build base payment intent arguments
	 *
	 * @param string $stripe_customer_id Customer ID
	 * @param string $order_id Order ID
	 * @param bool   $for_update Whether this is for updating an existing payment intent
	 * @return array Payment intent arguments
	 */
	private function build_payment_intent_args( $stripe_customer_id = '', $order_id = '', $for_update = false ) {
		$args = array(
			'amount'   => $this->total,
			'currency' => $this->currency,
		);

		// Only include automatic_payment_methods when creating new payment intents.
		if ( ! $for_update ) {
			$args['automatic_payment_methods'] = array(
				'enabled' => 'true',
			);
		}

		$args['shipping'] = $this->build_shipping_address();

		if ( $this->get_application_fee_amount() ) {
			$args['application_fee_amount'] = $this->get_application_fee_amount();
		}

		// Only include customer when creating new payment intents (Stripe doesn't allow changing customer on existing intents)
		if ( ! empty( $stripe_customer_id ) && ! $for_update ) {
			$args['customer'] = $stripe_customer_id;
		}

		// Get customer info for metadata and receipt.
		if ( is_user_logged_in() ) {
			$customer_email = SPC()->customer->get_email();
			$customer_name  = SPC()->customer->get_name();
		} else {
			$customer_email = SPC()->cart->get_checkout_data_item( 'email' );
			$customer_name  = SPC()->cart->get_checkout_data_item( 'first_name' ) . ' ' . SPC()->cart->get_checkout_data_item( 'last_name' );
		}

		// Add receipt email if available.
		if ( ! empty( $customer_email ) ) {
			$args['receipt_email'] = $customer_email;
		}

		// Add description with customer name.
		if ( ! empty( $customer_name ) ) {
			$args['description'] = sprintf( 'Order for %s', trim( $customer_name ) );
		}

		// Build metadata with customer info.
		$metadata = array();
		if ( ! empty( $order_id ) ) {
			$metadata['order_id'] = $order_id;
			$order                = sunshine_get_order( $order_id );
			$args['description']  = $order->get_name();
			if ( ! empty( $customer_name ) ) {
				$args['description'] .= ', ' . trim( $customer_name );
			}
		}
		if ( ! empty( $customer_email ) ) {
			$metadata['customer_email'] = $customer_email;
		}
		if ( ! empty( $customer_name ) ) {
			$metadata['customer_name'] = trim( $customer_name );
		}

		if ( ! empty( $metadata ) ) {
			$args['metadata'] = $metadata;
		}

		return $args;
	}

	/**
	 * Handle idempotency key conflicts with retry logic
	 *
	 * @param array  $args Payment intent arguments
	 * @param string $idempotency_key Current idempotency key
	 * @return array|WP_Error Payment intent data or error
	 */
	private function create_payment_intent_with_retry( $args, $idempotency_key ) {
		$response = $this->make_stripe_request( 'payment_intents', $args, 'POST' );

		if ( is_wp_error( $response ) && $response->get_error_code() === 'stripe_api_error' ) {
			$error_data = $response->get_error_data();
			if ( isset( $error_data['code'] ) && $error_data['code'] === 'idempotency_key_in_use' ) {
				SPC()->log( 'Idempotency key conflict detected, retrying payment intent creation with new key' );
				sleep( 1 );

				$new_idempotency_key = $this->generate_idempotency_key();
				SPC()->session->set( 'stripe_idempotency_key', $new_idempotency_key );
				SPC()->log( 'Retrying with new idempotency key: ' . $new_idempotency_key );

				$retry_response = $this->make_stripe_request( 'payment_intents', $args, 'POST' );
				return $retry_response;
			}
		}

		return $response;
	}

	/**
	 * Initialize the Stripe payment method
	 *
	 * Sets up payment method properties and registers WordPress hooks.
	 *
	 * @return void
	 */
	public function init() {

		$this->id                    = 'stripe';
		$this->name                  = __( 'Stripe', 'sunshine-photo-cart' );
		$this->class                 = get_class( $this );
		$this->description           = __( 'Pay with credit card', 'sunshine-photo-cart' );
		$this->can_be_enabled        = true;
		$this->needs_billing_address = false;

		add_action( 'sunshine_stripe_connect_display', array( $this, 'stripe_connect_display' ) );
		add_action( 'sunshine_stripe_webhook_display', array( $this, 'stripe_webhook_display' ) );
		add_action( 'admin_init', array( $this, 'stripe_connect_return' ) );
		add_action( 'admin_init', array( $this, 'stripe_disconnect_return' ) );
		add_action( 'admin_init', array( $this, 'setup_payment_domain_manual' ) );

		if ( ! $this->is_active() || ! $this->is_allowed() ) {
			return;
		}

		add_action( 'wp_ajax_sunshine_stripe_log_payment', array( $this, 'log_payment' ) );
		add_action( 'wp_ajax_nopriv_sunshine_stripe_log_payment', array( $this, 'log_payment' ) );

		add_action( 'wp_ajax_sunshine_stripe_create_payment_intent', array( $this, 'create_payment_intent' ) );
		add_action( 'wp_ajax_nopriv_sunshine_stripe_create_payment_intent', array( $this, 'create_payment_intent' ) );

		add_filter( 'sunshine_checkout_post_process_order', array( $this, 'checkout_post_process_order' ), 10, 3 );

		add_action( 'admin_init', array( $this, 'set_payment_intent_manually' ), 1 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'sunshine_checkout_update_payment_method', array( $this, 'update_payment_method' ) );

		add_action( 'sunshine_checkout_init_order_success', array( $this, 'init_order' ) );

		add_action( 'wp', array( $this, 'check_order_paid' ) );
		add_action( 'wp', array( $this, 'process_webhook' ) );

		add_action( 'wp', array( $this, 'payment_return' ), 20 );

		add_action( 'sunshine_checkout_process_payment_stripe', array( $this, 'process_payment' ) );

		add_filter( 'sunshine_order_transaction_url', array( $this, 'transaction_url' ) );

		add_filter( 'sunshine_admin_order_tabs', array( $this, 'admin_order_tab' ), 10, 2 );
		add_action( 'sunshine_admin_order_tab_stripe', array( $this, 'admin_order_tab_content_stripe' ) );

		add_action( 'sunshine_order_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'sunshine_order_actions_options', array( $this, 'order_actions_options' ) );
		add_action( 'sunshine_order_process_action_stripe_refund', array( $this, 'process_refund' ) );

		add_action( 'sunshine_checkout_validation', array( $this, 'checkout_validation' ) );

	}

	/**
	 * Log payment results from Stripe
	 *
	 * Handles AJAX requests to log payment results and update order status.
	 *
	 * @return void
	 */
	public function log_payment() {
		if ( ! isset( $_POST['result'] ) ) {
			return;
		}
		$result   = wp_unslash( $_POST['result'] );
		$order_id = SPC()->session->get( 'checkout_order_id' );
		if ( $order_id ) {
			$order = sunshine_get_order( $order_id );

			if ( ! empty( $result['error'] ) ) {
				SPC()->log( 'Stripe payment error: ' . print_r( $result['error'], true ) );
				// Set order to failed.
				$order_id = SPC()->session->get( 'checkout_order_id' );
				if ( $order_id ) {
					$order = sunshine_get_order( $order_id );
					$order->set_status( 'failed' );
					SPC()->log( 'Stripe payment error: ' . print_r( $result['error'], true ) );
				}
				return;
			}

			SPC()->log( 'Stripe payment result logged: ' . print_r( $result, true ) );
			if ( ! empty( $result['error'] ) ) {
				$order->add_log( 'Stripe payment error: ' . $result['error']['message'] );
			}
		}
	}

	public function show_payment_intent_id() {
		if ( SPC()->session->get( 'stripe_payment_intent_id' ) ) {
			echo 'Payment intent ID: ' . esc_html( SPC()->session->get( 'stripe_payment_intent_id' ) );
		}
	}

	public function admin_notices() {

		$mode    = $this->get_mode_value();
		$webhook = SPC()->get_option( 'stripe_webhook_' . $mode );
		if ( empty( $webhook_id ) ) {
			SPC()->notices->add_admin( 'stripe_webhook_not_setup', __( 'Stripe webhook is not setup', 'sunshine-photo-cart' ), 'error' ) . ' <a href="' . admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) . '">' . __( 'Configure here', 'sunshine-photo-cart' ) . '</a>';
		}

	}

	public function set_payment_intent_manually() {
		if ( isset( $_GET['stripe_payment_intent_id'] ) ) {
			SPC()->session->set( 'stripe_payment_intent_id', sanitize_text_field( $_GET['stripe_payment_intent_id'] ) );
			SPC()->log( 'Set payment intent manually: ' . SPC()->session->get( 'stripe_payment_intent_id' ) );
		}
	}

	/**
	 * Check if Stripe payment method is allowed
	 *
	 * @return bool True if Stripe account ID is configured, false otherwise
	 */
	public function is_allowed() {
		$account_id = $this->get_option( 'account_id_' . $this->get_mode_value() );
		if ( ! empty( $account_id ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get admin options for Stripe payment method
	 *
	 * @param array $options Existing options array
	 * @return array Modified options array with Stripe-specific options
	 */
	public function options( $options ) {

		// TODO: Need to show URL the user must use for webhook URL and how to do so

		foreach ( $options as &$option ) {
			if ( $option['id'] == 'stripe_header' && $this->get_application_fee_percent() > 0 ) {
				/* translators: %s is the application fee percentage */
				$option['description'] = sprintf( __( 'Note: You are using the free Stripe payment gateway integration. This includes an additional %s%% fee for payment processing on each order that goes to Sunshine Photo Cart in addition to Stripe processing fees. This added fee is removed by using the Stripe Pro add-on.', 'sunshine-photo-cart' ), $this->get_application_fee_percent() ) . ' <a href="https://www.sunshinephotocart.com/addon/stripe/?utm_source=plugin&utm_medium=link&utm_campaign=stripe" target="_blank">' . __( 'Learn more', 'sunshine-photo-cart' ) . '</a>';
			}
		}

		$options[] = array(
			'name'        => __( 'Layout', 'sunshine-photo-cart' ),
			'id'          => $this->id . '_layout',
			'type'        => 'radio',
			'options'     => array(
				'tabs'      => __( 'Tabs', 'sunshine-photo-cart' ),
				'accordion' => __( 'Accordion', 'sunshine-photo-cart' ),
			),
			'description' => '<a href="https://docs.stripe.com/payments/payment-element#layout" target="_blank">' . __( 'See differences in layout options', 'sunshine-photo-cart' ) . '</a>',
			'default'     => 'tabs',
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
			'name'             => __( 'Stripe Connection (Live)', 'sunshine-photo-cart' ),
			'id'               => $this->id . '_connect_live',
			'type'             => 'stripe_connect',
			'conditions'       => array(
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
			'name'             => __( 'Stripe Connection (Test)', 'sunshine-photo-cart' ),
			'id'               => $this->id . '_connect_test',
			'type'             => 'stripe_connect',
			'conditions'       => array(
				array(
					'field'   => $this->id . '_mode',
					'compare' => '==',
					'value'   => 'test',
					'action'  => 'show',
				),
			),
			'hide_system_info' => true,
		);

		$options[] = array(
			'name'             => __( 'Stripe Webhook', 'sunshine-photo-cart' ),
			'id'               => $this->id . '_webhook',
			'type'             => 'stripe_webhook',
			'hide_system_info' => true,
		);
		$options[] = array(
			'name'             => __( 'Stripe Webhook Secret (Live)', 'sunshine-photo-cart' ),
			'id'               => $this->id . '_webhook_secret_live',
			'type'             => 'text',
			'conditions'       => array(
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
			'name'             => __( 'Stripe Webhook Secret (Test)', 'sunshine-photo-cart' ),
			'id'               => $this->id . '_webhook_secret_test',
			'type'             => 'text',
			'conditions'       => array(
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

			<p><a href="https://www.sunshinephotocart.com/?stripe_disconnect=1&account_id=<?php echo esc_attr( $account_id ); ?>&mode=<?php echo esc_html( $mode ); ?>&nonce=<?php echo esc_html( wp_create_nonce( 'sunshine_stripe_disconnect' ) ); ?>&return_url=<?php echo esc_url( admin_url( 'admin.php?sunshine_stripe_disconnect_return' ) ); ?>" class="sunshine-stripe-connect"><span><?php esc_html_e( 'Disconnect from', 'sunshine-photo-cart' ); ?></span> <span class="stripe">Stripe</span></a></p>

		<?php } else { ?>

			<p><a href="https://www.sunshinephotocart.com/?stripe_connect=1&nonce=<?php echo esc_attr( wp_create_nonce( 'sunshine_stripe_connect' ) ); ?>&return_url=<?php echo esc_url( admin_url( 'admin.php?sunshine_stripe_connect_return' ) ); ?>&mode=<?php echo esc_attr( $mode ); ?>" class="sunshine-stripe-connect"><span><?php esc_html_e( 'Connect to', 'sunshine-photo-cart' ); ?></span> <span class="stripe">Stripe</span></a></p>

			<?php
		}

	}

	function stripe_webhook_display( $field ) {

		echo '<p>Stripe Webhook URL: <code>' . esc_url( $this->get_webhook_url() ) . '</code></p>';
		echo '<p><a href="https://www.sunshinephotocart.com/docs/setting-up-stripe/#webhooks" target="_blank">' . esc_html__( 'Learn how to setup Stripe Webhooks', 'sunshine-photo-cart' ) . '</a></p>';

	}

	private function get_webhook_url() {
		$url = trailingslashit( get_bloginfo( 'url' ) );
		$url = add_query_arg(
			array(
				'sunshine_stripe_webhook' => 1,
			),
			$url
		);
		return $url;
	}

	function setup_payment_domain_manual() {

		if ( ! isset( $_GET['stripe_setup_payment_domain'] ) || ! current_user_can( 'sunshine_manage_options' ) ) {
			return;
		}

		$mode = sanitize_text_field( $_GET['stripe_setup_payment_domain'] );

		$result = $this->setup_payment_domain( $mode );
		if ( $result ) {
			SPC()->notices->add_admin( 'stripe_payment_domain_setup', __( 'Stripe payment domain setup successfully', 'sunshine-photo-cart' ), 'success' );
		} else {
			SPC()->notices->add_admin( 'stripe_payment_domain_setup_failed', __( 'Stripe payment domain setup failed', 'sunshine-photo-cart' ), 'error' );
		}

	}

	function setup_payment_domain( $mode = 'live' ) {

		$this->setup();

		$args = array(
			'domain_name' => $_SERVER['HTTP_HOST'],
		);

		$response = wp_remote_post(
			'https://api.stripe.com/v1/payment_method_domains',
			array(
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
			SPC()->log( 'Failed setting up payment domain: ' . $error_message );
			return false;
		} else {
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! empty( $data['error'] ) ) {
				SPC()->log( 'Failed setting up payment domain: ' . $data['error']['message'] );
				return false;
			}
			SPC()->log( 'Created stripe payment domain: ' . $data['id'] );
			SPC()->update_option( 'stripe_payment_domain_' . $mode, $data );
			return $data;
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

		$this->setup_payment_domain( $mode );

		wp_redirect( admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) );
		exit;

	}

	function stripe_disconnect_return() {

		if ( ! isset( $_GET['sunshine_stripe_disconnect_return'] ) || ! current_user_can( 'sunshine_manage_options' ) ) {
			return;
		}

		if ( empty( $_GET['status'] ) || empty( $_GET['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['nonce'] ), 'sunshine_stripe_disconnect' ) ) {
			SPC()->notices->add_admin( 'stripe_disconnect_fail', __( 'Stripe could not be disconnected', 'sunshine-photo-cart' ), 'error' );
			wp_safe_redirect( admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) );
			exit;
		}

		if ( isset( $_GET['mode'] ) && $_GET['mode'] == 'live' ) {
			$mode = 'live';
		} else {
			$mode = 'test';
		}

		SPC()->update_option( 'stripe_account_id_' . $mode, '' );
		SPC()->update_option( 'stripe_publishable_key_' . $mode, '' );
		SPC()->update_option( 'stripe_secret_key_' . $mode, '' );

		SPC()->notices->add_admin( 'stripe_disconnected_success', __( 'Stripe has successfully been disconnected', 'sunshine-photo-cart' ), 'success' );

		wp_safe_redirect( admin_url( 'admin.php?page=sunshine&section=payment_methods&payment_method=stripe' ) );
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
	}

	private function get_publishable_key( $mode = '' ) {
		return ( $mode == 'live' || $this->get_mode_value() == 'live' ) ? SPC()->get_option( $this->id . '_publishable_key_live' ) : SPC()->get_option( $this->id . '_publishable_key_test' );
	}

	private function get_secret_key( $mode = '' ) {
		return ( $mode == 'live' || $this->get_mode_value() == 'live' ) ? SPC()->get_option( $this->id . '_secret_key_live' ) : SPC()->get_option( $this->id . '_secret_key_test' );
	}

	private function get_account_id( $mode = '' ) {
		return ( $mode == 'live' || $this->get_mode_value() == 'live' ) ? SPC()->get_option( $this->id . '_account_id_live' ) : SPC()->get_option( $this->id . '_account_id_test' );
	}

	private function get_payment_intent_id() {
		if ( empty( $this->payment_intent_id ) ) {
			$this->payment_intent_id = SPC()->session->get( 'stripe_payment_intent_id' );
		}
		return $this->payment_intent_id;
	}

	private function set_payment_intent_id( $payment_intent_id ) {
		$this->payment_intent_id = $payment_intent_id;
		SPC()->session->set( 'stripe_payment_intent_id', $payment_intent_id );
	}

	private function get_client_secret() {
		if ( empty( $this->client_secret ) ) {
			$this->client_secret = SPC()->session->get( 'stripe_client_secret' );
		}
		return $this->client_secret;
	}

	private function set_client_secret( $client_secret ) {
		$this->client_secret = $client_secret;
		SPC()->session->set( 'stripe_client_secret', $client_secret );
	}

	private function get_webhook_secret() {
		return SPC()->get_option( 'stripe_webhook_secret_' . $this->get_mode_value() );
	}

	public function enqueue_scripts() {

		if ( ! is_sunshine_page( 'checkout' ) || empty( $this->get_publishable_key() ) || empty( $this->get_account_id() ) ) {
			return false;
		}

		wp_enqueue_script( 'sunshine-stripe', 'https://js.stripe.com/v3/' );
		wp_enqueue_script( 'sunshine-stripe-processing', SUNSHINE_PHOTO_CART_URL . 'assets/js/stripe-processing.js', array( 'jquery' ), SUNSHINE_PHOTO_CART_VERSION, true );
		wp_localize_script(
			'sunshine-stripe-processing',
			'spc_stripe_vars',
			array(
				'publishable_key' => $this->get_publishable_key(),
				'account_id'      => $this->get_account_id(),
				'layout'          => ( $this->get_option( 'layout' ) ) ? $this->get_option( 'layout' ) : 'tabs',
				'return_url'      => sunshine_get_page_url( 'checkout' ) . '?section=payment&stripe_payment_return',
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'security'        => wp_create_nonce( 'sunshine_stripe' ),
				'strings'         => array(
					'elements_not_available'        => __( 'Payment form not properly initialized. Please refresh the page and try again.', 'sunshine-photo-cart' ),
					'payment_element_not_available' => __( 'Payment form not properly initialized. Please refresh the page and try again.', 'sunshine-photo-cart' ),
					'payment_element_not_mounted'   => __( 'Payment form not properly mounted. Please refresh the page and try again.', 'sunshine-photo-cart' ),
					'payment_intent_not_created'    => __( 'Payment intent not created. Please refresh the page and try again.', 'sunshine-photo-cart' ),
					'payment_not_processed'         => __( 'Payment was not processed, please try again', 'sunshine-photo-cart' ),
					'payment_did_not_succeed'       => __( 'Payment did not succeed', 'sunshine-photo-cart' ),
					'payment_processing_failed'     => __( 'Payment processing failed:', 'sunshine-photo-cart' ),
				),
			)
		);

	}

	/**
	 * Set up Stripe payment intent
	 *
	 * Creates or updates a Stripe payment intent with current cart data.
	 * Handles idempotency key conflicts and retries.
	 *
	 * @return void
	 */
	public function setup_payment_intent() {

		// Prevent creating new payment intent during active payment processing
		// This guards against race conditions where payment method changes during checkout
		if ( doing_action( 'sunshine_checkout_process_payment_stripe' ) ) {
			return;
		}

		if ( empty( SPC()->cart ) ) {
			SPC()->cart->setup();
		}

		// Set the cart total in cents
		$cart_total = SPC()->cart->get_total();
		if ( $cart_total <= 0 || SPC()->cart->is_empty() ) {
			return; // Don't create if there is no amount to charge yet or if the cart is empty.
		}

		$this->total = round( 100 * $cart_total );

		// Get or create Stripe customer for both logged-in users and guests
		$stripe_customer_id = $this->get_stripe_customer_id();

		if ( $stripe_customer_id ) {
			// Validate existing customer ID
			$response = $this->make_stripe_request( "customers/$stripe_customer_id" );
			if ( is_wp_error( $response ) ) {
				// Handle error, unset customer ID if not found or other API issue
				SPC()->log( 'Stripe customer error: ' . $response->get_error_message() );
				$this->set_stripe_customer_id( '' );
				$stripe_customer_id = '';
			} else {
				SPC()->log( 'Using existing Stripe customer ID: ' . $stripe_customer_id );
			}
		}

		// If no customer ID, create one (for both logged-in users and guests)
		if ( empty( $stripe_customer_id ) ) {
			$stripe_customer_id = $this->create_stripe_customer();
			if ( ! $stripe_customer_id ) {
				SPC()->log( 'Failed to create Stripe customer' );
			}
		}

		$order_id = SPC()->session->get( 'checkout_order_id' );

		// Set up payment intent
		if ( empty( $this->get_payment_intent_id() ) ) {
			SPC()->log( 'Creating new payment intent...' );

			$args = $this->build_payment_intent_args( $stripe_customer_id, $order_id );

			// Generate idempotency key to prevent duplicate charges
			$idempotency_key = SPC()->session->get( 'stripe_idempotency_key' );
			if ( empty( $idempotency_key ) ) {
				$idempotency_key = $this->generate_idempotency_key();
				SPC()->session->set( 'stripe_idempotency_key', $idempotency_key );
			}
			$intent = $this->create_payment_intent_with_retry( $args, $idempotency_key );

			if ( is_wp_error( $intent ) ) {
				SPC()->log( 'Failed creating payment intent: ' . $intent->get_error_message() );
				return;
			}

			SPC()->log( 'Created new payment intent: ' . $intent['id'] );
			SPC()->session->set( 'stripe_payment_intent_id', $intent['id'] );
			$this->set_payment_intent_id( $intent['id'] );
			$this->client_secret = $intent['client_secret'];
			return;
		} else {
			// Check if customer ID has changed - if so, need to create new payment intent
			$existing_intent = $this->make_stripe_request( "payment_intents/$this->payment_intent_id" );
			if ( ! is_wp_error( $existing_intent ) && ! empty( $existing_intent['customer'] ) ) {
				if ( $existing_intent['customer'] != $stripe_customer_id ) {
					SPC()->log( 'Stripe customer changed from ' . $existing_intent['customer'] . ' to ' . $stripe_customer_id . ', creating new payment intent' );

					// Cancel the old payment intent
					$cancel_result = $this->make_stripe_request( "payment_intents/$this->payment_intent_id/cancel", array(), 'POST' );
					if ( is_wp_error( $cancel_result ) ) {
						SPC()->log( 'Warning: Could not cancel old payment intent: ' . $cancel_result->get_error_message() );
					} else {
						SPC()->log( 'Cancelled old payment intent: ' . $this->payment_intent_id );
					}

					// Clear old payment intent data
					$this->set_payment_intent_id( '' );
					SPC()->session->set( 'stripe_payment_intent_id', '' );
					SPC()->session->set( 'stripe_idempotency_key', '' );

					// Create new payment intent
					$args            = $this->build_payment_intent_args( $stripe_customer_id, $order_id );
					$idempotency_key = $this->generate_idempotency_key();
					SPC()->session->set( 'stripe_idempotency_key', $idempotency_key );

					$intent = $this->create_payment_intent_with_retry( $args, $idempotency_key );

					if ( is_wp_error( $intent ) ) {
						SPC()->log( 'Failed creating new payment intent: ' . $intent->get_error_message() );
						return;
					}

					SPC()->log( 'Created new payment intent with updated customer: ' . $intent['id'] );
					SPC()->session->set( 'stripe_payment_intent_id', $intent['id'] );
					$this->set_payment_intent_id( $intent['id'] );
					$this->client_secret = $intent['client_secret'];
					return;
				}
			}

			// Update existing payment intent (customer hasn't changed)
			$args = $this->build_payment_intent_args( $stripe_customer_id, $order_id, true );

			$intent = $this->make_stripe_request( "payment_intents/$this->payment_intent_id", $args, 'POST' );

			if ( is_wp_error( $intent ) ) {
				SPC()->log( 'Failed updating payment intent: ' . $intent->get_error_message() );
				return;
			}

			if ( isset( $intent['error'] ) ) {
				SPC()->log( 'Stripe API error updating payment intent (' . $this->payment_intent_id . '): ' . $intent['error']['message'] );
				if ( isset( $intent['error']['code'] ) && $intent['error']['code'] == 'payment_intent_unexpected_state' && $intent['error']['payment_intent']['status'] == 'succeeded' ) {
					// Payment has already succeeded.
					$current_checkout_order_id = SPC()->session->get( 'checkout_order_id' );
					if ( ! empty( $current_checkout_order_id ) ) {
						// If already succeeded and we have a checkout order id, then we actually need to process it and redirect to the order.
						$order = sunshine_get_order( $current_checkout_order_id );
						if ( $order->exists() && $order->get_payment_method() == $this->id && ! $order->is_paid() ) {
							SPC()->log( 'Found checkout order ID and payment intent succeeded, so processing order...' );
							SPC()->cart->process_order();
							wp_safe_redirect( $order->get_received_permalink() );
							exit;
						}
					}
				}
				$this->set_payment_intent_id( '' );
				$this->set_client_secret( '' );
				$this->setup_payment_intent();
				return;
			} elseif ( $intent['status'] != 'requires_payment_method' ) {
				SPC()->log( 'Stripe payment intent invalid status, resetting (' . $this->payment_intent_id . ')' );
				$this->set_payment_intent_id( '' );
				$this->set_client_secret( '' );
				$this->setup_payment_intent();
				return;
			}
			SPC()->log( 'Updated Stripe payment intent: ' . $intent['id'] );
			$this->set_payment_intent_id( $intent['id'] );
			$this->set_client_secret( $intent['client_secret'] );
			return;
		}

	}


	public function create_payment_intent() {

		// Testing: Add delay if test_delay parameter is provided
		if ( ! empty( $_POST['test_delay'] ) && is_numeric( $_POST['test_delay'] ) ) {
			$delay = intval( $_POST['test_delay'] );
			if ( $delay > 0 && $delay <= 60 ) { // Max 60 seconds for safety
				sleep( $delay );
			}
		}

		$this->setup();
		$this->setup_payment_intent();
		if ( empty( $this->payment_intent_id ) ) {
			SPC()->log( 'Failed creating Stripe payment intent' );
			wp_send_json_error();
		}
		wp_send_json_success(
			array(
				'payment_intent_id' => $this->payment_intent_id,
				'client_secret'     => $this->client_secret,
			)
		);

	}

	public function create_stripe_customer() {

		SPC()->log( 'Creating Stripe customer' );

		if ( is_user_logged_in() ) {
			$email = SPC()->customer->get_email();
		} else {
			$email = SPC()->cart->get_checkout_data_item( 'email' );
		}

		// Check Stripe API to see if we have a customer ID for this email address already
		if ( ! empty( $email ) ) {
			$customer = $this->make_stripe_request( 'customers', array( 'email' => $email ) );

			if ( is_wp_error( $customer ) ) {
				SPC()->log( 'Failed getting Stripe customer: ' . $customer->get_error_message() );
				return;
			}

			if ( ! empty( $customer['data'] ) && ! empty( $customer['data'][0]['id'] ) ) {
				SPC()->log( 'Found existing Stripe customer: ' . $customer['data'][0]['id'] );
				$this->set_stripe_customer_id( $customer['data'][0]['id'] );
				return $customer['data'][0]['id'];
			}
		}

		if ( is_user_logged_in() ) {
			SPC()->log( 'Creating Stripe customer for logged in user' );
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
		} else {
			$email = SPC()->cart->get_checkout_data_item( 'email' );
			if ( empty( $email ) ) {
				SPC()->log( 'No email found in checkout data, cannot create Stripe customer yet' );
				return false;
			}

			// Look up past orders by this email address to see if we have a Stripe customer id in one of them
			$orders = sunshine_get_orders(
				array(
					'meta_query' => array(
						array(
							'key'   => 'email',
							'value' => $email,
						),
					),
				)
			);

			if ( ! empty( $orders ) ) {
				foreach ( $orders as $order ) {
					$stripe_customer_id = $order->get_meta_value( 'stripe_customer_id' );
					if ( ! empty( $stripe_customer_id ) ) {
						SPC()->log( 'Found Stripe customer ID in past order: ' . $stripe_customer_id );

						// Check if it is a valid customer ID.
						$stripe_customer = $this->make_stripe_request( "customers/$stripe_customer_id" );
						if ( is_wp_error( $stripe_customer ) ) {
							SPC()->log( 'Stripe customer error: ' . $stripe_customer->get_error_message() );
							break;
						}

						// Verify this customer's email matches (prevent using wrong customer)
						if ( isset( $stripe_customer['email'] ) && $stripe_customer['email'] !== $email ) {
							SPC()->log( 'WARNING: Stripe customer email mismatch! Expected: ' . $email . ', Got: ' . $stripe_customer['email'] );
							SPC()->log( 'NOT reusing this customer ID, will create new one' );
							break;
						}

						SPC()->log( 'Reusing Stripe customer ID ' . $stripe_customer_id . ' for guest email ' . $email );
						$this->set_stripe_customer_id( $stripe_customer_id );
						return $stripe_customer_id;
					}
				}
			}

			SPC()->log( 'Creating Stripe customer for guest user' );
			$args = array(
				'email'    => $email,
				'name'     => SPC()->cart->get_checkout_data_item( 'first_name' ) . ' ' . SPC()->cart->get_checkout_data_item( 'last_name' ),
				'shipping' => $this->build_shipping_address(),
			);
		}

		$customer = $this->make_stripe_request( 'customers', $args, 'POST' );

		if ( is_wp_error( $customer ) ) {
			SPC()->log( 'Failed creating Stripe customer: ' . $customer->get_error_message() );
			return;
		}

		SPC()->log( 'Created Stripe customer: ' . $customer['id'] );
		$this->set_stripe_customer_id( $customer['id'] );
		return $customer['id'];
	}

	public function init_order( $order ) {

		if ( $order->get_payment_method() != $this->id ) {
			return;
		}

		$payment_intent_id = $this->get_payment_intent_id();

		if ( ! empty( $payment_intent_id ) ) {
			SPC()->log( 'init_order: Updating order meta with Stripe payment intent ID: ' . $payment_intent_id );
			$order->update_meta_value( 'stripe_payment_intent_id', $payment_intent_id );
			$order->update_meta_value( 'stripe_customer_id', $this->get_stripe_customer_id() );
		} else {
			SPC()->log( 'init_order: No Stripe payment intent ID found, cannot update order meta' );
		}

	}

	public function checkout_post_process_order( $do_post_process, $order, $data ) {
		if ( $data['payment_method'] == $this->id && $this->get_webhook_secret() ) {
			SPC()->log( 'Stripe webhook secret is set, so not doing post process' );
			return false;
		}
		return $do_post_process;
	}

	/**
	 * Process payment after successful Stripe payment
	 *
	 * Updates order with payment information and metadata from Stripe.
	 *
	 * @param SPC_Order $order The order object
	 * @return void
	 */
	public function process_payment( $order ) {

		// At this point we already have a paid order from the Stripe JS and we are just updating the order with info.
		SPC()->log( 'Processing Stripe payment for order: ' . $order->get_id() );

		$this->setup();

		// Get payment intent ID from POST data (added by JS), not session
		// This prevents race conditions where session might be cleared or changed
		$payment_intent_id = ! empty( $_POST['stripe_payment_intent_id'] ) ? sanitize_text_field( $_POST['stripe_payment_intent_id'] ) : '';

		// Fallback to session if not in POST (for backwards compatibility)
		if ( empty( $payment_intent_id ) ) {
			$payment_intent_id = $this->get_payment_intent_id();
		}

		if ( empty( $payment_intent_id ) ) {
			SPC()->log( 'No payment intent ID found - cannot process payment' );
			SPC()->cart->add_error( __( 'Could not complete order, contact site owner for more details', 'sunshine-photo-cart' ) );
			return;
		}

		// Verify payment intent succeeded in Stripe BEFORE proceeding
		$payment_intent_object = $this->make_stripe_request( "payment_intents/$payment_intent_id" );

		if ( is_wp_error( $payment_intent_object ) ) {
			SPC()->log( 'Error getting the Stripe payment intent (' . $payment_intent_id . '): ' . $payment_intent_object->get_error_message() );
			SPC()->cart->add_error( __( 'Could not complete order, contact site owner for more details', 'sunshine-photo-cart' ) );
			return;
		}

		// Verify the payment actually succeeded
		if ( empty( $payment_intent_object['status'] ) || $payment_intent_object['status'] !== 'succeeded' ) {
			SPC()->log( 'Stripe payment intent status is not succeeded: ' . ( $payment_intent_object['status'] ?? 'unknown' ) );
			SPC()->cart->add_error( __( 'Payment was not successful, please try again', 'sunshine-photo-cart' ) );
			return;
		}

		SPC()->log( 'Stripe payment intent verified as succeeded: ' . $payment_intent_id );

		$order->update_meta_value( 'paid_date', current_time( 'timestamp' ) );

		if ( ! empty( $payment_intent_object['source'] ) ) {
			$order->update_meta_value( 'source', sanitize_text_field( $payment_intent_object['source'] ) );
		}
		if ( ! empty( $payment_intent_object['application_fee_amount'] ) ) {
			$order->update_meta_value( 'application_fee_amount', sanitize_text_field( $payment_intent_object['application_fee_amount'] ) / 100 );
		}

		// Continue to update metadata in Stripe.
		SPC()->log( 'Updating metadata in Stripe for payment' );
		$args = array(
			'metadata[order_id]'   => $order->get_id(),
			'metadata[order_name]' => $order->get_name(),
			'metadata[site]'       => get_bloginfo( 'name' ),
			'metadata[order_url]'  => admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ),
			'description'          => $order->get_name() . ', ' . $order->get_customer_name(),
		);
		$args = apply_filters( 'sunshine_stripe_payment_intent_args', $args, $order );

		$updated_intent = $this->make_stripe_request( "payment_intents/$payment_intent_id", $args, 'POST' );

		if ( is_wp_error( $updated_intent ) ) {
			SPC()->log( 'Failed to perform update payment intent (' . $payment_intent_id . '): ' . $updated_intent->get_error_message() );
			SPC()->cart->add_error( __( 'Could not complete order, contact site owner for more details', 'sunshine-photo-cart' ) );
			return;
		}

		// Only clear session data after everything succeeded
		// This prevents race conditions where session is cleared before payment is fully verified
		$this->set_payment_intent_id( '' );
		$this->set_client_secret( '' );
		SPC()->session->set( 'stripe_idempotency_key', '' );
		// Clear guest customer ID for next checkout (logged-in users keep theirs in user meta)
		if ( ! is_user_logged_in() ) {
			SPC()->session->set( 'stripe_customer_id', '' );
		}
		SPC()->log( 'Stripe payment processing complete' );

		// DEBUG: Add artificial delay to test race condition
		if ( isset( $_GET['test_race_condition'] ) ) {
			$delay = intval( $_GET['test_race_condition'] );
			if ( $delay > 0 && $delay <= 30 ) {
				SPC()->log( '!!! TESTING RACE CONDITION: Sleeping for ' . $delay . ' seconds !!!' );
				sleep( $delay );
				SPC()->log( '!!! Sleep complete, continuing to redirect !!!' );
			}
		}

	}


	public function payment_return() {

		if ( ! isset( $_GET['stripe_payment_return'] ) || ! isset( $_GET['payment_intent'] ) || ! isset( $_GET['payment_intent_client_secret'] ) ) {
			return false;
		}

		SPC()->log( 'Returned from Stripe after async payment' );

		// Pause for a couple seconds if we are waiting on the webhook to complete.
		sleep( 2 );

		$payment_intent_id = sanitize_text_field( $_GET['payment_intent'] );
		$client_secret     = sanitize_text_field( $_GET['payment_intent_client_secret'] );

		$payment_intent_object = $this->make_stripe_request( "payment_intents/$payment_intent_id" );

		if ( is_wp_error( $payment_intent_object ) ) {
			SPC()->log( 'Check payment intent on checkout error: ' . $payment_intent_object->get_error_message() );
			return;
		}

		$order = $this->get_order_by_payment_intent( $payment_intent_id );
		if ( ! $order || ! $order->exists() ) {
			SPC()->log( 'Could not find order by payment intent in payment return: ' . $payment_intent_id );
			return;
		}

		if ( ! empty( $payment_intent_object['status'] ) && $payment_intent_object['status'] == 'succeeded' ) {
			$order = SPC()->cart->process_order();
			if ( $order ) {
				$url = apply_filters( 'sunshine_checkout_redirect', $order->get_received_permalink() );
				SPC()->log( 'Created new order after stripe asynchronous payment and is redirecting' );
				wp_safe_redirect( $url );
				exit;
			}
		}

		$order->set_status( 'failed' );
		$order->add_log( 'Order failed from stripe asynchronous payment' );
		SPC()->notices->add( __( 'Could not process order, please try another payment method', 'sunshine-photo-cart' ), 'error' );
		wp_safe_redirect( sunshine_get_page_url( 'checkout' ) );
		exit;

	}

	/**
	 * Process Stripe webhook events
	 *
	 * Handles incoming webhook events from Stripe, verifies signatures,
	 * and processes payment_intent.succeeded events.
	 *
	 * @return void
	 */
	public function process_webhook() {

		if ( ! isset( $_GET['sunshine_stripe_webhook'] ) ) {
			return;
		}

		$payload        = file_get_contents( 'php://input' );
		$sig_header     = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
		$webhook_secret = $this->get_webhook_secret();

		SPC()->log( 'Processing Stripe webhook with payload: ' . print_r( $payload, true ) );

		if ( empty( $webhook_secret ) ) {
			SPC()->log( 'Failed webhook check, missing webhook secret' );
			status_header( 400 );
			exit;
		}

		if ( empty( $payload ) ) {
			SPC()->log( 'Failed webhook check, missing payload' );
			status_header( 400 );
			exit;
		}

		if ( empty( $sig_header ) ) {
			SPC()->log( 'Failed webhook check, missing signature header' );
			status_header( 400 );
			exit;
		}

		// Parse the Stripe-Signature header
		$parts = array();
		foreach ( explode( ',', $sig_header ) as $part ) {
			list( $k, $v )       = explode( '=', $part, 2 );
			$parts[ trim( $k ) ] = $v;
		}

		$timestamp = $parts['t'] ?? '';
		$signature = $parts['v1'] ?? '';

		// Compute expected signature
		$signed_payload = $timestamp . '.' . $payload;
		$expected_sig   = hash_hmac( 'sha256', $signed_payload, $webhook_secret );

		// Compare securely
		if ( ! hash_equals( $expected_sig, $signature ) ) {
			SPC()->log( 'Failed webhook signature check' );
			status_header( 400 );
			exit;
		}

		// Webhook is verified — process it
		$event = json_decode( $payload, true );
		if ( $event['type'] == 'payment_intent.succeeded' ) {
			$payment_intent = $event['data']['object'];
			$order          = $this->get_order_by_payment_intent( $payment_intent['id'] );

			if ( ! $order || ! $order->exists() ) {
				SPC()->log( 'Could not find order by payment intent in webhook: ' . $payment_intent['id'] );
				status_header( 400 );
				exit;
			}

			SPC()->log( 'Processing Stripe webhook: payment_intent.succeeded for ' . $payment_intent['id'] . ' and order ' . $order->get_id() );

			$order->add_log( 'Stripe webhook: payment_intent.succeeded for ' . $payment_intent['id'] );

			SPC()->cart->post_process_order( $order );

			$order->update_meta_value( 'paid_date', current_time( 'timestamp' ) );
			$order->update_meta_value( 'stripe_customer_id', $payment_intent['customer'] );

			wp_update_post(
				array(
					'ID'        => $order->get_id(),
					'post_date' => current_time( 'mysql' ),
				)
			);

			if ( ! empty( $payment_intent['source'] ) ) {
				$order->update_meta_value( 'source', sanitize_text_field( $payment_intent['source'] ) );
			}
			if ( ! empty( $payment_intent['application_fee_amount'] ) ) {
				$order->update_meta_value( 'application_fee_amount', sanitize_text_field( $payment_intent['application_fee_amount'] ) / 100 );
			}
		}

		status_header( 200 );
		exit;

	}

	public function check_order_paid() {

		if ( ! is_sunshine_page( 'checkout' ) ) {
			return;
		}

		$checkout_order_id = SPC()->session->get( 'checkout_order_id' );
		$payment_intent_id = SPC()->session->get( 'stripe_payment_intent_id' );

		// Only run checks if we have session data or user just submitted payment
		// This prevents unnecessary queries on normal checkout page loads
		$should_check = false;

		// Check 1: Has session data (standard flow)
		if ( ! empty( $checkout_order_id ) && ! empty( $payment_intent_id ) ) {
			$should_check = true;
		}

		// Check 2: User just came from payment (has payment intent in URL from Stripe redirect)
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Stripe redirect, no nonce available
		if ( ! empty( $_GET['payment_intent'] ) && ! empty( $_GET['payment_intent_client_secret'] ) ) {
			$should_check = true;
		}

		// Skip all checks if not needed (normal checkout page browsing)
		if ( ! $should_check ) {
			return;
		}

		// Standard check with session variables
		$recovery_completed = false; // Track if we successfully recovered

		if ( ! empty( $checkout_order_id ) && ! empty( $payment_intent_id ) ) {
			$order = sunshine_get_order( $checkout_order_id );

			if ( $order->exists() && $order->get_payment_method() == $this->id && ! $order->is_paid() ) {
				// Try checking the payment intent with retries for race condition scenarios
				// This handles cases where Stripe's API hasn't updated yet after payment
				$max_attempts          = 3;
				$has_successful_charge = false;
				$status                = '';

				for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
					// Check the payment intent status
					$intent = $this->make_stripe_request( "payment_intents/$payment_intent_id" );

					if ( is_wp_error( $intent ) ) {
						SPC()->log( 'check_order_paid: Failed checking payment intent: ' . $intent->get_error_message() );
						return;
					}

					$status = ! empty( $intent['status'] ) ? $intent['status'] : '';

					// Check if payment has any charges (more reliable than status for race conditions)
					if ( ! empty( $intent['charges'] ) && ! empty( $intent['charges']['data'] ) ) {
						foreach ( $intent['charges']['data'] as $charge ) {
							if ( ! empty( $charge['status'] ) && $charge['status'] === 'succeeded' ) {
								$has_successful_charge = true;
								break 2; // Break out of both loops
							}
						}
					}

					// If status is succeeded or we found a charge, break
					if ( $status == 'succeeded' || $has_successful_charge ) {
						break;
					}

					// Wait before next attempt (but not on last attempt)
					if ( $attempt < $max_attempts ) {
						sleep( 1 );
					}
				}

				if ( $status == 'succeeded' || $has_successful_charge ) {
					SPC()->log( 'check_order_paid: Payment succeeded for order ' . $order->get_id() . ', auto-processing (attempt ' . $attempt . ')' );
					$recovery_completed = true;
					SPC()->cart->process_order();
					wp_safe_redirect( $order->get_received_permalink() );
					exit;
				}
			}
		}

		// Additional check: If session was cleared (race condition), look for recent pending orders
		// Skip if we already recovered successfully above (performance optimization)
		if ( $recovery_completed ) {
			return;
		}

		// This handles cases where user refreshed during payment processing
		if ( is_user_logged_in() ) {
			$customer_id = get_current_user_id();
		} else {
			// For guests, check by email if available
			$email = SPC()->cart->get_checkout_data_item( 'email' );
			if ( empty( $email ) ) {
				return;
			}
			$customer_id = null;
		}

		// Look for very recent pending orders (within last 5 minutes)
		$recent_orders_args = array(
			'post_status' => array( 'publish' ),
			'date_query'  => array(
				array(
					'after' => '5 minutes ago',
				),
			),
			'meta_query'  => array(
				'relation' => 'AND',
				array(
					'key'     => 'status',
					'value'   => 'pending',
					'compare' => '=',
				),
				array(
					'key'     => 'payment_method',
					'value'   => $this->id,
					'compare' => '=',
				),
			),
		);

		// Add customer/email filter
		if ( is_user_logged_in() ) {
			$recent_orders_args['meta_query'][] = array(
				'key'   => 'customer_id',
				'value' => $customer_id,
			);
		} else {
			$recent_orders_args['meta_query'][] = array(
				'key'   => 'email',
				'value' => $email,
			);
		}

		$recent_orders = sunshine_get_orders( $recent_orders_args );

		if ( ! empty( $recent_orders ) ) {
			$found_completed_payment = false;
			foreach ( $recent_orders as $order ) {
				$order_payment_intent_id = $order->get_meta_value( 'stripe_payment_intent_id' );
				if ( ! empty( $order_payment_intent_id ) ) {
					// Try checking with retries
					$max_attempts          = 3;
					$has_successful_charge = false;
					$status                = '';

					for ( $attempt = 1; $attempt <= $max_attempts; $attempt++ ) {
						// Check if this payment actually succeeded
						$intent = $this->make_stripe_request( "payment_intents/$order_payment_intent_id" );
						if ( ! is_wp_error( $intent ) ) {
							$status = ! empty( $intent['status'] ) ? $intent['status'] : '';

							// Check if payment has any charges (more reliable)
							if ( ! empty( $intent['charges'] ) && ! empty( $intent['charges']['data'] ) ) {
								foreach ( $intent['charges']['data'] as $charge ) {
									if ( ! empty( $charge['status'] ) && $charge['status'] === 'succeeded' ) {
										$has_successful_charge = true;
										break 2; // Break out of both loops
									}
								}
							}

							// If found, break
							if ( $status == 'succeeded' || $has_successful_charge ) {
								break;
							}

							// Wait before retry
							if ( $attempt < $max_attempts ) {
								sleep( 1 );
							}
						} else {
							SPC()->log( 'check_order_paid: Failed verifying orphaned order payment intent: ' . $intent->get_error_message() );
							break;
						}
					}

					if ( $status == 'succeeded' || $has_successful_charge ) {
						SPC()->log( 'check_order_paid: Found orphaned paid order ' . $order->get_id() . ', auto-processing (attempt ' . $attempt . ')' );
						$found_completed_payment = true;

						// Show success message to user
						SPC()->notices->add( __( 'Your payment was successful! Completing your order...', 'sunshine-photo-cart' ), 'success' );

						// Restore session variables temporarily
						SPC()->session->set( 'checkout_order_id', $order->get_id() );
						SPC()->session->set( 'stripe_payment_intent_id', $order_payment_intent_id );

						// Process the order
						SPC()->cart->process_order();
						wp_safe_redirect( $order->get_received_permalink() );
						exit;
					}
				}
			}

			// If we found pending orders but none with completed payments, warn user
			if ( ! $found_completed_payment && is_sunshine_page( 'checkout' ) ) {
				$pending_order = $recent_orders[0];
				SPC()->notices->add(
					sprintf(
					/* translators: %s is the order name/number */
						__( 'You have a recent pending order (#%s). If you recently made a payment, please wait a moment or contact support.', 'sunshine-photo-cart' ),
						$pending_order->get_name()
					),
					'info'
				);
			}
		}

	}

	public function get_stripe_customer_id() {
		if ( is_user_logged_in() ) {
			return SPC()->customer->sunshine_stripe_customer_id;
		} else {
			// For guests, store in session
			return SPC()->session->get( 'stripe_customer_id' );
		}
	}

	public function set_stripe_customer_id( $id ) {
		if ( is_user_logged_in() ) {
			SPC()->customer->update_meta( 'stripe_customer_id', $id );
		} else {
			// For guests, store in session for current checkout
			SPC()->session->set( 'stripe_customer_id', $id );
			SPC()->log( 'Set guest Stripe customer ID in session: ' . $id );
		}
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

	/**
	 * Generate a unique idempotency key for payment intent creation.
	 * This prevents duplicate charges if the same request is made multiple times.
	 * Key remains stable for the same checkout session to handle page refreshes.
	 *
	 * @return string Unique idempotency key
	 */
	private function generate_idempotency_key() {
		$unique_id  = uniqid();
		$cart_total = SPC()->cart->get_total();
		$user_id    = get_current_user_id();

		// Create a hash of cart items for more reliable uniqueness
		$cart_items_hash = $this->get_cart_items_hash();

		// Create a stable key based on order, amount, user, and cart contents
		$key_data = array(
			'unique_id' => $unique_id,
			'total'     => round( 100 * $cart_total ),
			'user_id'   => ( $user_id ? $user_id : 'guest' ),
			'cart'      => $cart_items_hash,
		);

		// Generate a hash-based key that's unique but deterministic for the same checkout
		$key_string = implode( '|', $key_data );
		return 'stripe_idempotency_' . hash( 'sha256', $key_string );
	}

	/**
	 * Generate a hash of the current cart items for idempotency key generation.
	 *
	 * @return string Hash of cart items
	 */
	private function get_cart_items_hash() {
		if ( empty( SPC()->cart ) || SPC()->cart->is_empty() ) {
			return 'empty-cart';
		}

		$cart_items  = SPC()->cart->get_cart_items();
		$item_hashes = array();

		foreach ( $cart_items as $item ) {
			// Use the existing hash from each cart item
			$item_hashes[] = $item->get_hash();
		}

		// Sort hashes to ensure consistent ordering regardless of cart item order
		sort( $item_hashes );

		// Combine all item hashes into one overall hash
		$combined_hashes = implode( '|', $item_hashes );
		return hash( 'sha256', $combined_hashes );
	}

	public function get_fields() {

		ob_start();

		if ( $this->get_mode_value() == 'test' ) {
			echo '<div class="sunshine--payment--test">' . esc_html__( 'This will be processed as a test payment and no real money will be exchanged', 'sunshine-photo-cart' ) . '</div>';
		}
		?>

		<div id="sunshine-stripe-payment">
			<div id="sunshine-stripe-payment-fields">
				<div id="sunshine-stripe-payment-loading" style="padding: 30px 20px; text-align: center; color: #666; font-size: 14px;">
					<?php esc_html_e( 'Loading secure payment form...', 'sunshine-photo-cart' ); ?>
				</div>
			</div>
			<div id="sunshine-stripe-payment-errors"></div>
		</div>

		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;

	}

	public function create_order_status( $status, $order ) {
		if ( $order->get_payment_method() == $this->id ) {
			return 'new'; // Straight to new.
		}
		return $status;
	}

	public function order_notify( $notify, $order ) {
		if ( $order->get_payment_method() == $this->id ) {
			return false;
		}
		return $notify;
	}

	public function update_payment_method( $payment_method ) {

		if ( empty( $payment_method ) || $payment_method->get_id() != $this->id ) {
			return;
		}

		$current_order_id = SPC()->session->get( 'checkout_order_id' );

		// Set things up here only when we select stripe.
		$this->setup();
		$this->setup_payment_intent();

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
			$order = sunshine_get_order( $orders[0] );
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
		echo '<tr><th>' . esc_html__( 'Transaction ID', 'sunshine-photo-cart' ) . '</th>';
		echo '<td>' . esc_html( $this->get_transaction_id( $order ) ) . '</td></tr>';
		$application_fee_amount = $order->get_meta_value( 'application_fee_amount' );
		if ( $application_fee_amount ) {
			echo '<tr>';
			echo '<th>' . esc_html__( 'Application Fee Amount (To Sunshine)', 'sunshine-photo-cart' ) . '</th>';
			echo '<td>' . wp_kses_post( sunshine_price( $application_fee_amount ) ) . ' (<a href="https://www.sunshinephotocart.com/upgrade/?utm_source=plugin&utm_medium=link&utm_campaign=stripe" target="_blank">' . esc_html__( 'Upgrade to remove this fee on future transactions', 'sunshine-photo-cart' ) . '</a>)' . '</td>';
			echo '</tr>';
		}
		echo '</table>';

	}

	function order_actions( $actions, $post_id ) {
		$order = sunshine_get_order( $post_id );
		if ( $order->get_payment_method() == $this->id ) {
			/* translators: %s is the payment method name */
			$actions[ $this->id . '_refund' ] = sprintf( __( 'Refund payment in %s', 'sunshine-photo-cart' ), $this->name );
		}
		return $actions;
	}

	function order_actions_options( $order ) {
		?>
		<div id="stripe-refund-order-actions" style="display: none;">
			<p><label><input type="checkbox" name="stripe_refund_notify" value="yes" checked="checked" /> <?php esc_html_e( 'Notify customer via email', 'sunshine-photo-cart' ); ?></label></p>
			<p><label><input type="checkbox" name="stripe_refund_full" value="yes" checked="checked" /> <?php esc_html_e( 'Full refund', 'sunshine-photo-cart' ); ?></label></p>
			<p id="stripe-refund-amount" style="display: none;"><label><input type="number" name="stripe_refund_amount" step=".01" size="6" style="width:100px" max="<?php echo esc_attr( $order->get_total_minus_refunds() ); ?>" value="<?php echo esc_attr( $order->get_total_minus_refunds() ); ?>" /> <?php esc_html_e( 'Amount to refund', 'sunshine-photo-cart' ); ?></label></p>
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

	/**
	 * Process refund for Stripe payment
	 *
	 * @param int $order_id The order ID to refund
	 * @return void
	 */
	function process_refund( $order_id ) {

		$order = sunshine_get_order( $order_id );

		$this->setup( $order->get_mode() );

		$payment_intent_id = $order->get_meta_value( 'stripe_payment_intent_id' );

		$payment_intent = $this->make_stripe_request( "payment_intents/$payment_intent_id" );

		if ( is_wp_error( $payment_intent ) ) {
			SPC()->log( 'Stripe refund error: ' . $payment_intent->get_error_message() );
			/* translators: %s is the error message */
			SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, sprintf( __( 'Failed to connect: %s', 'sunshine-photo-cart' ), $payment_intent->get_error_message() ) );
			$order->add_log( sprintf( 'Failed to connect to Stripe to retrieve payment intent (Order ID: %s)', $order_id ) );
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
			/* translators: %1$s is the maximum allowed refund amount, %2$s is the requested refund amount */
			$order->add_log( sprintf( __( 'Refund amount is higher than allowed (Total allowed: %1$s, Refund Requested: %2$s)', 'sunshine-photo-cart' ), ( $payment_intent['amount'] / 100 ), $refund_amount ) );
			return;
		}

		$args                   = array(
			'payment_intent' => $payment_intent_id,
			'amount'         => $refund_amount_stripe,
		);
		$application_fee_amount = $order->get_meta_value( 'application_fee_amount' );
		if ( $application_fee_amount ) {
			$args['refund_application_fee'] = 'true';
		}

		$refund_response = $this->make_stripe_request( 'refunds', $args, 'POST' );

		if ( is_wp_error( $refund_response ) ) {
			/* translators: %s is the error message */
			SPC()->notices->add_admin( 'stripe_refund_fail_' . $payment_intent_id, sprintf( __( 'Could not refund payment: %s', 'sunshine-photo-cart' ), $refund_response->get_error_message() ), 'error' );
			$order->add_log( sprintf( 'Could not refund payment in Stripe: %s', $refund_response->get_error_message() ) );
			return;
		}

		$order->set_status( 'refunded' );
		$order->add_refund( $refund_amount );
		/* translators: %s is the refund amount formatted as price */
		SPC()->notices->add_admin( 'stripe_refund_success_' . $payment_intent_id, sprintf( __( 'Refund has been processed for %s', 'sunshine-photo-cart' ), sunshine_price( $refund_amount ) ) );

		if ( ! empty( $_POST['stripe_refund_notify'] ) ) {
			$order->notify( false );
			SPC()->notices->add_admin( 'stripe_refund_notify_' . $payment_id, __( 'Customer sent email about refund', 'sunshine-photo-cart' ) );
		}

	}

	public function mode( $mode, $order ) {
		if ( $order->get_payment_method() == 'stripe' ) {
			return $this->get_mode_value();
		}
		return $mode;
	}

	public function checkout_validation( $section ) {
		if ( $section == 'payment' && SPC()->cart->get_total() > 0 && SPC()->cart->get_checkout_data_item( 'payment_method' ) == 'stripe' ) {
			if ( empty( $_POST['stripe_payment_intent_id'] ) ) {
				SPC()->cart->add_error( __( 'Invalid payment', 'sunshine-photo-cart' ) );
			}
		}
	}

}
