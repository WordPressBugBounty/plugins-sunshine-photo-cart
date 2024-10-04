<?php if ( ! isset( $_GET['page'] ) || ( $_GET['page'] != 'sunshine-install' && $_GET['page'] != 'sunshine-update' ) ) { ?>

	<?php if ( ! SPC()->get_option( 'address1' ) ) { ?>
		<div id="sunshine-header--notice" class="sunshine-header-notice-todo">
			<strong>Setup Guide:</strong> Start configuring your store with your business information...
			<a href="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine' ); ?>" class="button alt">See settings</a>
		</div>
	<?php } elseif ( wp_count_posts( 'sunshine-product' )->publish <= 0 ) { ?>
		<div id="sunshine-header--notice" class="sunshine-header-notice-todo">
			<strong>Setup Guide:</strong> Create products and set prices to start selling
			<a href="<?php echo admin_url( 'edit.php?post_type=sunshine-product' ); ?>" class="button alt">Add products</a>
		</div>
	<?php } elseif ( empty( sunshine_get_active_payment_methods() ) ) { ?>
		<div id="sunshine-header--notice" class="sunshine-header-notice-todo">
			<strong>Setup Guide:</strong> Configure payment methods to start receiving money
			<a href="<?php echo admin_url( 'admin.php?page=sunshine&section=payment_methods' ); ?>" class="button alt">Select payment methods</a>
		</div>
	<?php } elseif ( empty( sunshine_get_active_shipping_methods() ) ) { ?>
		<div id="sunshine-header--notice" class="sunshine-header-notice-todo">
			<strong>Setup Guide:</strong> Configure shipping methods to get orders to customers
			<a href="<?php echo admin_url( 'admin.php?page=sunshine&section=shipping_methods' ); ?>" class="button alt">Setup shipping methods</a>
		</div>
	<?php } elseif ( ! SPC()->get_option( 'logo' ) ) { ?>
		<div id="sunshine-header--notice" class="sunshine-header-notice-todo">
			<strong>Setup Guide:</strong> Customize the look of Sunshine with your logo and other options
			<a href="<?php echo admin_url( 'admin.php?page=sunshine&section=display' ); ?>" class="button alt">Configure display options</a>
		</div>
	<?php } elseif ( ! SPC()->is_pro() ) { ?>
		<div id="sunshine-header--notice" class="sunshine-header-notice-upgrade">
			<?php _e( 'Unlock more professional level features for Sunshine Photo Cart by upgrading', 'sunshine-photo-cart' ); ?>
			<a href="https://www.sunshinephotocart.com/upgrade/?utm_source=plugin&utm_medium=link&utm_campaign=upgrade" target="_blank" class="button alt"><?php _e( 'Learn more', 'sunshine-photo-cart' ); ?></a>
		</div>
	<?php } ?>

<?php } ?>

<div id="sunshine-header">
	<a href="https://www.sunshinephotocart.com/?utm_source=plugin&utm_medium=link&utm_campaign=pluginheader" target="_blank" id="sunshine-logo"><img src="<?php echo SUNSHINE_PHOTO_CART_URL; ?>assets/images/logo.svg" alt="Sunshine Photo Cart by WP Sunshine" /></a>

	<?php
	if ( ! empty( $header_links ) ) {
		echo '<div id="sunshine-header--links">';
		foreach ( $header_links as $key => $link ) {
			echo '<a href="' . esc_url( $link['url'] ) . '?utm_source=plugin&utm_medium=link&utm_campaign=pluginheader" target="_blank" id="sunshine-header--link--' . esc_attr( $key ) . '">' . esc_html( $link['label'] ) . '</a>';
		}
		echo '</div>';
	}
	?>

	<?php if ( count( $tabs ) > 1 ) { ?>
	<nav id="sunshine-options--menu">
		<ul>
			<?php foreach ( $tabs as $key => $label ) { ?>
				<li <?php if ( $_GET['tab'] == $key ) { ?>class="sunshine-options--active"<?php } ?>><a href="<?php echo admin_url( 'options-general.php?page=sunshine&tab=' . $key ); ?>"><?php echo esc_html( $label ); ?></a></li>
			<?php } ?>
		</ul>
	</nav>
	<?php } ?>

</div>
