<div id="sunshine--order--status" class="sunshine--order--status--<?php echo esc_attr( $order->get_status() ); ?>">
	<div id="sunshine--order--status--name"><?php echo $order->get_status_name(); ?></div>
	<div id="sunshine--order--status--description"><?php echo $order->get_status_description(); ?></div>
</div>
