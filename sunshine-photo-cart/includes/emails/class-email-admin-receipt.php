<?php
class SPC_Email_Admin_Receipt extends SPC_Email {

	function init() {

		$this->id                = 'admin-receipt';
		$this->class             = get_class( $this );
		$this->name              = __( 'Admin Order Receipt', 'sunshine-photo-cart' );
		$this->description       = __( 'Email receipt sent to admins after successful order', 'sunshine-photo-cart' );
		$this->subject           = sprintf( __( 'New order: %s from %s', 'sunshine-photo-cart' ), '[order_name]', '[sitename]' );
		$this->custom_recipients = true;

		$this->add_search_replace(
			array(
				'order_id'   => '',
				'order_number' => '',
				'order_name' => '',
				'first_name' => '',
				'last_name'  => '',
				'status'     => '',
			)
		);

		add_action( 'sunshine_order_notify', array( $this, 'trigger' ), 10, 2 );

	}

	public function trigger( $order, $admin = true ) {

		if ( ! $admin ) {
			return;
		}

		$this->set_template( $this->id );
		$this->set_subject( $this->get_subject() );
		$this->set_reply_to( $order->get_email() );

		$args = array(
			'order' => $order,
		);
		$this->add_args( $args );

		$search_replace = array(
			'order_id'   => $order->get_id(),
			'order_number' => $order->get_order_number(),
			'order_name' => $order->get_name(),
			'first_name' => $order->get_customer_first_name(),
			'last_name'  => $order->get_customer_last_name(),
			'status'     => $order->get_status_name(),
		);
		$this->add_search_replace( $search_replace );

		// Send email
		$result = $this->send();

	}

}
