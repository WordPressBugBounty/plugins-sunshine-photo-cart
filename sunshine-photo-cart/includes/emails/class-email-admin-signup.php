<?php
class SPC_Email_Admin_Signup extends SPC_Email {

	function init() {

		$this->id                = 'admin-signup';
		$this->class             = get_class( $this );
		$this->name              = __( 'Signup (Admin)', 'sunshine-photo-cart' );
		$this->description       = __( 'Signup notification with customer information', 'sunshine-photo-cart' );
		$this->subject           = sprintf( __( 'Customer signup at %s', 'sunshine-photo-cart' ), '[sitename]' );
		$this->custom_recipients = true;

		add_action( 'sunshine_after_signup', array( $this, 'trigger' ) );

	}

	public function trigger( $customer ) {

		$this->set_template( $this->id );
		$this->set_subject( $this->get_subject() );

		$args = array(
			'customer' => $customer,
		);
		$this->add_args( $args );

		// Send email
		$result = $this->send();

	}

}
