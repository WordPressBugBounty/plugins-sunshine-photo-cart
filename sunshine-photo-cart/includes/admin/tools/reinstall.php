<?php
class SPC_Tool_Reinstall extends SPC_Tool {

	function __construct() {
		parent::__construct(
			__( 'Install process', 'sunshine-photo-cart' ),
			'reinstall',
			__( 'Run the initial install process which sets the default pages, settings and permissions. This will not reset your settings, but add things that may be missing.', 'sunshine-photo-cart' ),
			__( 'Run install process', 'sunshine-photo-cart' )
		);
	}

	function process() {
		if ( ! current_user_can( 'sunshine_manage_options' ) ) {
			return false;
		}
		maybe_sunshine_create_custom_tables();
		sunshine_base_install();
		echo '<p>' . __( 'Re-install process successfully run', 'sunshine-photo-cart' ) . '</p>';
	}

}

$SPC_Tool_Reinstall = new SPC_Tool_Reinstall();
