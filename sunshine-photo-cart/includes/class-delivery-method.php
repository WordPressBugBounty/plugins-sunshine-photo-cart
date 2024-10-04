<?php
class SPC_Delivery_Method {

	protected $id;
	protected $active;
	protected $name;
	protected $description;
	protected $needs_shipping = true;
	protected $class;
	protected $can_be_enabled = false;

	public function __construct() {
		$this->init();
		add_filter( 'sunshine_delivery_methods', array( $this, 'register' ) );
		add_filter( 'sunshine_options_shipping', array( $this, 'options' ) );
	}

	public function init() { }

	public function register( $delivery_methods = array() ) {
		if ( ! empty( $this->id ) && $this->is_enabled() ) {
			$delivery_methods[ $this->id ] = array(
				'id'          => $this->id,
				'name'        => $this->name,
				'description' => $this->description,
				'class'       => $this->class,
			);
		}
		return $delivery_methods;
	}

	private function set_id( $id ) {
		$this->id = sanitize_key( $id );
	}

	public function get_id() {
		return $this->id;
	}

	public function set_name( $name ) {
		$this->name = sanitize_text_field( $name );
	}

	public function get_name() {
		return apply_filters( 'sunshine_delivery_method_' . $this->id . '_name', $this->name );
	}

	public function set_description( $description ) {
		$this->description = esc_html( $description );
	}

	public function get_description() {
		return apply_filters( 'sunshine_delivery_method_' . $this->id . '_description', $this->description );
	}

	public function needs_shipping() {
		return $this->needs_shipping;
	}

	public function options( $options ) {
		if ( $this->can_be_enabled ) {
			$options[] = array(
				'id'    => $this->id . '_enabled',
				'name'  => sprintf( __( 'Enable %s', 'sunshine-photo-cart' ), $this->get_name() ),
				'type'  => 'checkbox',
				'class' => ( isset( $_GET['instance_id'] ) ) ? 'hidden' : '',
			);
		}
		return $options;
	}

	public function is_enabled() {
		return SPC()->get_option( $this->id . '_enabled' );
	}

}
