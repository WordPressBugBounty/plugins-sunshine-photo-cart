<?php
function sunshine_customers_page() {
?>
    <div class="wrap">

        <?php
        if ( isset( $_GET['customer'] ) ) {
            sunshine_customer_page( sanitize_text_field( $_GET['customer'] ) );
            return;
        }
        ?>

        <h1><?php _e( 'Customers', 'sunshine-photo-cart' ); ?></h1>
        <form id="sunshine-customer-list" method="get" action="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers' ); ?>">
			<input type="hidden" name="post_type" value="sunshine-gallery" />
			<input type="hidden" name="page" value="sunshine-customers" />
	        <?php
	        $customers_table = new SPC_Customers_Table();
	        $customers_table->prepare_items();
	        $customers_table->views();
	        $customers_table->search_box( __( 'Search Customers', 'sunshine-photo-cart' ), 'sunshine-customers' );
	        $customers_table->display();
	        ?>
        </form>

    </div>
<?php
}

function sunshine_customer_page( $customer_id ) {
    $customer = new SPC_Customer( $customer_id );
?>
    <h1>
		<a href="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers' ); ?>"><?php _e( 'Customers', 'sunshine-photo-cart' ); ?></a> > <?php echo $customer->get_name(); ?>
		<a href="<?php echo admin_url( 'user-edit.php?user_id=' . $customer_id ); ?>" class="button" style="float:right;"><?php _e( 'Edit user', 'sunshine-photo-cart' ); ?></a>
		<a href="mailto:<?php echo $customer->get_email(); ?>" class="button" style="float:right; margin-right: 10px;"><?php _e( 'Send Email', 'sunshine-photo-cart' ); ?></a>
	</h1>

    <form method="post" action="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-customers&customer=' . $customer_id ); ?>">

        <?php wp_nonce_field( 'sunshine_customer_save', 'sunshine_customer_save' ); ?>
        <div id="sunshine-customer-page">

            <?php
            $tabs = array( 10 => 'profile', 20 => 'orders', 30 => 'favorites', 40 => 'activity' );
            $tabs = apply_filters( 'sunshine_customer_tabs', $tabs );
            ksort( $tabs );
            foreach ( $tabs as $tab ) {
                echo '<div class="sunshine-customer-tab" id="sunshine-customer-tab-' . esc_attr( $tab ) . '">';
                do_action( 'sunshine_customer_tab_' . $tab, $customer );
                echo '</div>';
            }
            ?>

        </div>
    </form>

<?php
}

add_action( 'admin_init', 'sunshine_customer_save' );
function sunshine_customer_save() {

	if ( ! isset( $_POST ) ) {
		return;
	}

    $post_data = wp_unslash( $_POST );

    if ( empty( $post_data['sunshine_customer_save'] ) || ! wp_verify_nonce( $post_data['sunshine_customer_save'], 'sunshine_customer_save' ) ) {
        return;
    }

    $customer_id = sanitize_text_field( $_GET['customer'] );
    $customer = new SPC_Customer( $customer_id );

    if ( isset( $_POST['credits'] ) ) {
        $customer->set_credits( sanitize_text_field( $post_data['credits'] ) );
    }

	do_action( 'sunshine_customer_save', $post_data, $customer );

    SPC()->notices->add_admin( 'customer_update', __( 'Customer has been updated', 'sunshine-photo-cart' ) );

}

add_action( 'sunshine_customer_tab_profile', 'sunshine_customer_tab_profile' );
function sunshine_customer_tab_profile( $customer ) {
?>

    <h2 id="profile"><?php _e( 'Profile', 'sunshine-photo-cart' ); ?></h2>

    <h3><?php _e( 'Shipping Address', 'sunshine-photo-cart' ); ?></h3>
    <?php if ( $customer->has_shipping_address() ) { ?>
        <p><?php echo $customer->get_shipping_address_formatted(); ?></p>
    <?php } else { ?>
        <p><?php _e( 'No shipping address collected for this customer', 'sunshine-photo-cart' ); ?>
    <?php } ?>

    <h3><?php _e( 'Billing Address', 'sunshine-photo-cart' ); ?></h3>
    <?php if ( $customer->has_billing_address() ) { ?>
        <p><?php echo $customer->get_billing_address_formatted(); ?></p>
    <?php } else { ?>
        <p><?php _e( 'No billing address collected for this customer', 'sunshine-photo-cart' ); ?>
    <?php } ?>

    <h3><?php _e( 'Credits', 'sunshine-photo-cart' ); ?></h3>
    <p>
        <input type="number" name="credits" value="<?php echo esc_attr( $customer->get_credits() ); ?>" min="0" step=".01" /> <button type="submit" id="sunshine-customer-save-credits" class="button" size="10"><?php _e( 'Save credits', 'sunshine-photo-cart' ); ?></button>
    </p>

	<?php
	$galleries = $customer->get_galleries();
	if ( $galleries ) {
	?>
		<h3><?php _e( 'Assigned Private Galleries', 'sunshine-photo-cart' ); ?></h3>
		<ul>
			<?php foreach ( $galleries as $gallery ) { ?>
				<li><a href="<?php echo admin_url( 'post.php?post=' . $gallery->get_id() . '&action=edit' ); ?>"><?php echo $gallery->get_name(); ?></a></li>
			<?php } ?>
		</ul>
	<?php } ?>

<?php
}

add_action( 'sunshine_customer_tab_orders', 'sunshine_customer_tab_orders' );
function sunshine_customer_tab_orders( $customer ) {
?>

    <h2 id="orders"><?php _e( 'Orders', 'sunshine-photo-cart' ); ?> (<?php echo $customer->get_order_count(); ?>)</h2>

    <?php if ( $customer->get_order_count() ) { ?>
    <div id="sunshine-customer-order-stats">
        <div class="sunshine-customer-order-stat">
            <h3><?php _e( 'Total', 'sunshine-photo-cart' ); ?></h3>
            <p><?php echo sunshine_price( $customer->get_order_totals() ); ?></p>
        </div>
        <div class="sunshine-customer-order-stat">
            <h3><?php _e( 'Avg Order', 'sunshine-photo-cart' ); ?></h3>
            <p><?php echo sunshine_price( $customer->get_order_totals() / $customer->get_order_count() ); ?></p>
        </div>
    </div>
    <?php } ?>

    <?php
    $orders = $customer->get_orders();
    if ( ! empty( $orders ) ) {
    ?>
    <table id="sunshine-orders-table">
        <?php foreach ( $orders as $order ) { ?>
        <tr>
            <td><a href="<?php echo admin_url( 'post.php?action=edit&post=' . $order->get_id() ); ?>"><?php echo $order->get_name(); ?></a></td>
            <td><?php echo $order->get_date(); ?></td>
            <td><?php echo $order->get_status_name(); ?></td>
            <td><?php echo $order->get_total_formatted(); ?></td>
        </tr>
        <?php } ?>
    </table>
    <?php } else { ?>
        <p><?php _e( 'No orders', 'sunshine-photo-cart' ); ?></p>
    <?php } ?>

<?php
}

add_action( 'sunshine_customer_tab_favorites', 'sunshine_customer_tab_favorites' );
function sunshine_customer_tab_favorites( $customer ) {
    $images = $customer->get_favorites();
    $galleries = array();
    if ( !empty( $images ) ) {
        foreach ( $images as $image ) {
			$gallery_id = $image->get_gallery_id();
			if ( ! empty( $gallery_id ) ) {
				$galleries[ $gallery_id ] = $image->get_gallery_name();
			}
        }
    }
?>

    <h2 id="favorites"><?php _e( 'Favorites', 'sunshine-photo-cart' ); ?> (<?php echo $customer->get_favorites_count(); ?>)</h2>
    <?php
    if ( count( $galleries ) > 1 ) {
        echo '<p><select name="gallery"><option value="">' . __( 'Filter by gallery', 'sunshine-photo-cart' ) . '</option>';
        foreach ( $galleries as $id => $name ) {
            echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $name ) . '</option>';
        }
        echo '</select></p>';
    }

	if ( ! empty( $images ) ) {
		$file_names = array();
		foreach ( $images as $image ) {
			$file_names[] = $image->get_file_name();
		}
		echo '<input type="text" id="filenames" style="width:70%" value="' . esc_attr( join( ',', $file_names ) ) . '" />';
		echo ' <a id="copy-filenames" class="button">' . __( 'Copy to clipboard', 'sunshine-photo-cart' ) . '</a><br /><br />';
		echo '<script>
			jQuery("#copy-filenames").click(function(){
				jQuery("#filenames").select();
				document.execCommand( "copy" );
				jQuery( this ).html( "Copied!" );
				return false;
			});
			</script>';
	}
    ?>

    <div id="sunshine-customer-favorites">
    <?php
    if ( !empty( $images ) ) {
        foreach ( $images as $image ) {
            echo '<div class="sunshine-customer-favorite-image" data-gallery="' . esc_attr( $image->get_gallery_id() ) . '">';
            $image->output();
            echo $image->get_file_name();
			if ( $image->get_gallery_id() ) {
				echo '<br /><em><a href="' . admin_url( 'edit.php?post_id=' . $image->get_gallery_id() ) . '">' . $image->get_gallery_name() . '</a></em>';
			}
            echo '</div>';
        }
    } else {
        echo '<p>' . __( 'No favorites', 'sunshine-photo-cart' ) . '</p>';
    }
    ?>
    </div>
    <script>
    jQuery( document ).ready(function($){
        $( 'select[name="gallery"]' ).on( 'change', function(){
            $( '.sunshine-customer-favorite-image' ).hide();
            var gallery = $( 'option:selected', this ).val();
            $( '.sunshine-customer-favorite-image[data-gallery=' + gallery + ']' ).show();
        });
    });
    </script>
<?php
}

add_action( 'sunshine_customer_tab_activity', 'sunshine_customer_tab_activity' );
function sunshine_customer_tab_activity( $customer ) {
?>

	<?php if ( ! is_sunshine_addon_active( 'analytics' ) ) { ?>

		<h2 id="activity">Get the Advanced Reports & Customer Activity Add-on!</h2>

		<p style="font-size: 18px;">Upgrade to see exactly what and when this customer interacts with your galleries including:</p>
		<ul style="font-size: 18px;" class="sunshine-check-list">
			<li>Viewing galleries and images</li>
			<li>Adding to favorites</li>
			<li>Sharing an image</li>
			<li>Adding to cart</li>
			<li>Making a purchase</li>
		</ul>
		<p style="font-size: 18px;"><strong>ALSO!</strong> Get additional advanced reports to see your most popular products, images, galleries, and more!</p>
		<?php if ( SPC()->is_pro() ) { ?>
			<p align="center"><a href="<?php echo admin_url( 'edit.php?post_type=sunshine-gallery&page=sunshine-addons' ); ?>" class="button-primary">See your add-ons</a></p>
		<?php } else { ?>
			<p align="center"><a href="https://www.sunshinephotocart.com/addon/analytics/" target="_blank" class="button-primary">View details</a></p>
		<?php } ?>

	<?php } ?>


<?php
}
