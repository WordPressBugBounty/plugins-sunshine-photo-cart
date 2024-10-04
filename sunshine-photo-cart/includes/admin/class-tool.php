<?php
class SPC_Tool {

	protected $name;
	protected $key;
	protected $description;
	protected $button_label;

	function __construct( $name, $key, $description = '', $button_label = '' ) {

		$this->name         = $name;
		$this->key          = $key;
		$this->description  = $description;
		$this->button_label = $button_label;

		add_filter( 'sunshine_tools', array( $this, 'register' ) );

	}

	function get_name() {
		return $this->name;
	}

	function get_key() {
		return $this->key;
	}

	function get_description() {
		return $this->description;
	}

	function get_button_label() {
		return $this->button_label;
	}

	function register( $tools ) {
		$tools[ $this->get_key() ] = $this;
		return $tools;
	}

	function pre_process() { }

	function process() { }

}
