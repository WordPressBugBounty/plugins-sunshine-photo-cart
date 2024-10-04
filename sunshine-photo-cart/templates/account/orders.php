<?php if ( $orders ) { ?>
	<table id="sunshine--orders">
		<thead>
			<tr>
				<th><?php _e( 'Order #', 'sunshine-photo-cart' ); ?></th>
				<th><?php _e( 'Date', 'sunshine-photo-cart' ); ?></th>
				<th><?php _e( 'Total', 'sunshine-photo-cart' ); ?></th>
				<th><?php _e( 'Status', 'sunshine-photo-cart' ); ?></th>
				<th><?php _e( 'Invoice', 'sunshine-photo-cart' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $orders as $order ) { ?>
				<tr>
					<td><a href="<?php echo $order->get_permalink(); ?>"><?php echo $order->get_name(); ?></a></td>
					<td><?php echo $order->get_date( get_option( 'date_format' ) ); ?></td>
					<td><?php echo $order->get_total_formatted(); ?></td>
					<td><?php echo $order->get_status_name(); ?></td>
					<td><a href="<?php echo $order->get_invoice_permalink(); ?>"><?php _e( 'View invoice', 'sunshine-photo-cart' ); ?></a></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
<?php } else { ?>
	<?php _e( 'You do not have any orders yet', 'sunshine-photo-cart' ); ?>
<?php } ?>
