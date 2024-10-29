<?php
function sunshine_show_checkout_fields( $active_section = '' ) {
	SPC()->cart->show_checkout_fields( $active_section );
}

add_action( 'wp_footer', 'sunshine_checkout_scripts', 999 );
function sunshine_checkout_scripts() {

	if ( ! is_sunshine_page( 'checkout' ) ) {
		return;
	}
	?>
	<script id="sunshine-checkout-js">

	const sunshine_active_section = "<?php echo esc_js( SPC()->cart->get_active_section() ); ?>";

	function sunshine_checkout_updating() {
		jQuery( '#sunshine--checkout' ).addClass( 'sunshine--loading' );
	}
	function sunshine_checkout_updating_done() {
		jQuery( '#sunshine--checkout' ).removeClass( 'sunshine--loading' );
	}

	jQuery( document ).ready(function($){

		function sunshine_reload_checkout( section = '' ) {

			sunshine_checkout_updating();

			$.ajax({
				type: 'GET',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_update',
					section: section,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-update' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					if ( result.success ) {
						if ( result.data.refresh ) {
							window.location.href = window.location.protocol + "//" + window.location.host + window.location.pathname;
						}
						$( '#sunshine--checkout' ).replaceWith( result.data.html );
						$( '#sunshine--checkout input, #sunshine--checkout select' ).trigger( 'conditional' );
						$( document ).trigger( 'sunshine_reload_checkout', [ result.data.section ] );
						$( '.sunshine--checkout--section-active' ).find( 'input:visible, select:visible' ).first().focus();
					} else {
						var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
						if ( section ) {
							newURL += "?section=" + section;
						}
						window.location.href = newURL;
					}
					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});

			return false;

		}

		$( 'body' ).on( 'submit', '#sunshine--checkout--steps form', function(e){
			sunshine_checkout_updating();
			var section = $( 'input[name="sunshine_checkout_section"]' ).val();
			// Process all sections except payment, that gets normal submit.
			if ( section != 'payment' ) {
				e.preventDefault();
				var form_data = new FormData( $( '#sunshine--checkout form' )[0] );
				form_data.append( 'action', 'sunshine_checkout_process_section' );
				form_data.append( 'security', "<?php echo wp_create_nonce( 'sunshine-checkout-process-section' ); ?>" );
				$.ajax({
					async: true,
					type: 'POST',
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					data: form_data,
					cache: false,
					processData: false,
					contentType: false,
					success: function( result, textStatus, XMLHttpRequest ) {
						if ( result.success ) {
							if ( result.data.next_section ) {
								sunshine_reload_checkout();
								$( document ).trigger( 'sunshine_checkout_load_' + result.data.next_section );
							}
						} else {
							var baseURL = window.location.protocol + "//" + window.location.host + window.location.pathname;
							var newURL = baseURL + "?section=" + section;
							//window.location.href = newURL;
						}
					},
					error: function(MLHttpRequest, textStatus, errorThrown) {
						alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					}
				});
			}
		});

		// Auto click the first payment option.
		$( document ).on( 'sunshine_reload_checkout', function( event, section ) {
			if ( section == 'payment' ) {
				setTimeout(function() {
					var payment_methods = $( '#sunshine--form--field--payment_method' ).find( 'input' );
					if ( payment_methods.length ) {
						//payment_methods[0].click();
						//$( payment_methods[0] ).trigger( 'change' );
					}
				}, 500 );
			}
		});

		$( document ).trigger( 'sunshine_reload_checkout', [ sunshine_active_section ] );

		$( 'body' ).on( 'click', '.sunshine--checkout--section-edit', function(e) {
			e.preventDefault();

			// Change URL
			var section = $( this ).data( 'section' );

			// Change content
			sunshine_reload_checkout( section );
			return false;

		});

		$( document ).on( 'change', 'input[name="delivery_method"]', function(){
			var sunshine_selected_delivery_method = $( 'input[name="delivery_method"]:checked' ).val();
			sunshine_checkout_updating();
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_select_delivery_method',
					delivery_method: sunshine_selected_delivery_method,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-select-delivery-method' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					if ( result.data.summary ) {
						$( '#sunshine--checkout--summary' ).html( result.data.summary );
					}
					if ( result.data.needs_shipping ) {
						$( '#sunshine--checkout--shipping, #sunshine--checkout--shipping_method' ).show();
					} else {
						$( '#sunshine--checkout--shipping, #sunshine--checkout--shipping_method' ).hide();
					}
					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});
		});

		$( document ).on( 'change', 'input[name="shipping_method"]', function(){
			var sunshine_selected_shipping_method = $( 'input[name="shipping_method"]:checked' ).val();
			sunshine_checkout_updating();
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_select_shipping_method',
					shipping_method: sunshine_selected_shipping_method,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-select-shipping-method' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					if ( result.data.summary ) {
						$( '#sunshine--checkout--summary' ).html( result.data.summary );
					}
					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});
		});

		$( document ).on( 'change', 'input[name="use_credits"]', function(){
			var sunshine_use_credits = $( 'input[name="use_credits"]:checked' ).val() || false;
			sunshine_checkout_updating();
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_use_credits',
					use_credits: sunshine_use_credits,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-use-credits' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					if ( result.success ) {
						sunshine_reload_checkout();
					}
					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});
		});

		$( document ).on( 'change', 'input[name="payment_method"]', function(){
			var sunshine_selected_payment_method = $( 'input[name="payment_method"]:checked' ).val();
			sunshine_checkout_updating();
			$( '.sunshine--checkout--payment-method--extra' ).hide();
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_select_payment_method',
					payment_method: sunshine_selected_payment_method,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-select-payment-method' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					$( '#sunshine--checkout--payment-method--extra--' + sunshine_selected_payment_method ).show();
					var sunshine_checkout_payment_event = new CustomEvent( 'sunshine_checkout_payment_change', { detail: sunshine_selected_payment_method } );
					document.dispatchEvent( sunshine_checkout_payment_event );

					if ( result.data && result.data.summary ) {
						$( '#sunshine--checkout--summary' ).html( result.data.summary );
					}
					if ( result.data && result.data.submit_label ) {
						$( '#sunshine--checkout--submit' ).html( result.data.submit_label );
					}

					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});
		});


		sunshine_state_change_security = '<?php echo wp_create_nonce( 'sunshine-checkout-update-state' ); ?>';

		$( document ).on( 'change', 'select[name="shipping_country"]', function(){
			sunshine_checkout_updating();
			var sunshine_selected_shipping_country = $( this ).val();
			var sunshine_selected_shipping_country_required;
			if ( $( this ).prop( 'required' ) ) {
				sunshine_selected_shipping_country_required = true;
			}
			setTimeout( function () {
				$.ajax({
					type: 'POST',
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					data: {
						action: 'sunshine_checkout_update_state',
						country: sunshine_selected_shipping_country,
						type: 'shipping',
						required: sunshine_selected_shipping_country_required,
						security: sunshine_state_change_security
					},
					success: function(output, textStatus, XMLHttpRequest) {
						if ( output ) {
							$( '#sunshine--checkout--shipping .sunshine--form--fields' ).html( '' );
							$( '#sunshine--checkout--shipping .sunshine--form--fields' ).html( output );
							//sunshine_mark_filled();
						}
						$( document ).trigger( 'sunshine_shipping_country_change', [ sunshine_selected_shipping_country ] );
						sunshine_checkout_updating_done();
					},
					error: function(MLHttpRequest, textStatus, errorThrown) {
						alert('Sorry, there was an error with your request');
					}
				});
			}, 500);
			return false;
		});

		$( document ).on( 'change', 'select[name="billing_country"]', function(){
			sunshine_checkout_updating();
			var sunshine_selected_billing_country = $( this ).val();
			var sunshine_selected_billing_country_required;
			if ( $( this ).prop( 'required' ) ) {
				sunshine_selected_billing_country_required = true;
			}
			setTimeout( function () {
				$.ajax({
					type: 'POST',
					url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
					data: {
						action: 'sunshine_checkout_update_state',
						country: sunshine_selected_billing_country,
						type: 'billing',
						required: sunshine_selected_billing_country_required,
						security: sunshine_state_change_security
					},
					success: function(output, textStatus, XMLHttpRequest) {
						if ( output ) {
							$( '#sunshine--checkout--payment div[id*="billing_"]' ).remove();
							//$( '#sunshine-checkout-payment .sunshine--form--fields' ).append( output );
							$( output ).insertAfter( '#sunshine--checkout--field--different_billing' );
							//sunshine_mark_filled();
						}
						sunshine_checkout_updating_done();
					},
					error: function(MLHttpRequest, textStatus, errorThrown) {
						alert('Sorry, there was an error with your request');
					}
				});
			}, 500);
			return false;
		});

		$( document ).on( 'submit', '#sunshine--checkout--discount-form', function(e){
			e.preventDefault();
			$( '#sunshine--checkout--discount-form--error' ).remove();
			$( '#sunshine--checkout--discount-form' ).removeClass( 'error' );
			var sunshine_discount_code = $( 'input[name="discount"]' ).val();
			if ( ! sunshine_discount_code ) {
				return false;
			}
			sunshine_checkout_updating();
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_discount_code',
					discount_code: sunshine_discount_code,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-discount-code' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					if ( result.success ) {
						sunshine_reload_checkout();
					} else {
						$( '#sunshine--checkout--discount-form' ).addClass( 'error' ).after( '<div id="sunshine--checkout--discount-form--error">' + result.data.message + '</div>' );
					}
					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});
		});

		$( document ).on( 'click', '.sunshine--checkout--discount-applied button', function(){
			var sunshine_discount_code = $( this ).data( 'id' );
			if ( !sunshine_discount_code ) {
				return false;
			}
			sunshine_checkout_updating();
			$.ajax({
				type: 'POST',
				url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
				data: {
					action: 'sunshine_checkout_discount_code_delete',
					discount_code: sunshine_discount_code,
					security: "<?php echo wp_create_nonce( 'sunshine-checkout-discount-code-delete' ); ?>"
				},
				success: function( result, textStatus, XMLHttpRequest ) {
					if ( result.success ) {
						sunshine_reload_checkout();
					}
					sunshine_checkout_updating_done();
				},
				error: function(MLHttpRequest, textStatus, errorThrown) {
					alert( '<?php echo esc_js( __( 'Sorry, there was an error with your request', 'sunshine-photo-cart' ) ); ?>' );
					sunshine_checkout_updating_done();
				}
			});
		});

		// Field conditions
		function sunshine_get_condition_field_value( field_id ) {
			var field = $( '#sunshine--form--field--' + field_id );
			var field_type = field.data( 'type' );
			var value;
			if ( field_type == 'text' || field_type == 'email' || field_type == 'tel' || field_type == 'password' ) { // Text input box
				value = $( 'input', field ).val();
			} else if ( field_type == 'checkbox' ) {
				value = $( 'input:checked', field ).val();
				if ( typeof value === 'undefined' ) {
					value = 'no';
				}
			} else if ( field_type == 'radio' ) {
				value = $( 'input:checked', field ).val();
				if ( typeof value === 'undefined' ) {
					value = 0;
				}
			} else if ( field_type == 'select' ) {
				value = $( 'select option:selected', field ).val();
			}
			return value;
		}

		function sunshine_set_field_disabled() {
			$( '.sunshine--form--field' ).each(function(){
				if ( $( this ).is( ':visible' ) ) {
					$( 'input, select, textarea', this ).prop( 'disabled', false );
				} else {
					$( 'input, select, textarea', this ).prop( 'disabled', true );
				}
			});
		}

		<?php
		$sections = SPC()->cart->get_checkout_fields();
		$i        = 0;
		foreach ( $sections as $section_id => $section ) {
			if ( empty( $section['fields'] ) ) {
				continue;
			}
			foreach ( $section['fields'] as $field ) {
				if ( ! empty( $field['conditions'] ) && is_array( $field['conditions'] ) ) {
					foreach ( $field['conditions'] as $condition ) {
						if ( empty( $condition['compare'] ) || empty( $condition['value'] ) || empty( $condition['field'] ) || empty( $condition['action'] ) ) {
							continue;
						}
						if ( ! in_array( $condition['action'], array( 'show', 'hide' ) ) ) {
							continue;
						}
						if ( ! in_array( $condition['compare'], array( '==', '!=', '<', '>', '<=', '>=' ) ) ) {
							continue;
						}
						$i++;
						?>
							var condition_field_value_<?php echo $i; ?> = sunshine_get_condition_field_value( '<?php echo esc_js( $condition['field'] ); ?>' );
							function condition_field_action_<?php echo $i; ?>( value ) {
								<?php
								$action_target     = ( isset( $condition['action_target'] ) ) ? $condition['action_target'] : '#sunshine--form--field--' . $field['id'];
								$true_action       = ( $condition['action'] == 'show' ) ? 'show' : 'hide';
								$false_action      = ( $condition['action'] == 'show' ) ? 'hide' : 'show';
								$comparison_string = '';
								if ( is_array( $condition['value'] ) ) { // If value is an array, need to compare against each array value
									$comparison_strings = array();
									foreach ( $condition['value'] as $value ) {
										$comparison_strings[] = '( value ' . esc_js( $condition['compare'] ) . ' "' . esc_js( $value ) . '" )';
									}
									$comparison_string = join( ' || ', $comparison_strings );
								} else {
									$comparison_string = 'value ' . esc_js( $condition['compare'] ) . ' "' . esc_js( $condition['value'] ) . '"';
								}
								?>
								if ( <?php echo $comparison_string; ?> ) {
									$( '<?php echo esc_js( $action_target ); ?>' ).<?php echo $true_action; ?>();
								} else {
									$( '<?php echo esc_js( $action_target ); ?>' ).<?php echo $false_action; ?>();
								}
								sunshine_set_field_disabled();
							}

							// Default action
							condition_field_action_<?php echo $i; ?>( condition_field_value_<?php echo $i; ?> );

							// On change action
							$( 'body' ).on( 'change conditional', '#<?php echo esc_js( $condition['field'] ); ?>, #sunshine--form--field--<?php echo esc_js( $condition['field'] ); ?> input[type="radio"]', function(){
								condition_field_value_<?php echo $i; ?> = sunshine_get_condition_field_value( '<?php echo esc_js( $condition['field'] ); ?>' );
								condition_field_action_<?php echo $i; ?>( condition_field_value_<?php echo $i; ?> );
							});
						<?php
					}
				}
			}
		}
		?>

		/* Credit card input formatting */
		$( document ).on( 'keyup', 'input.sunshine--credit-card--number', function() {
			$( this ).val( function (index, value) {
				value = value.replace(/\W/gi, '');
				// Determine card type and change max CVV maxlength to 3 for non-AMEX
				if ( value.substring(0,2) == '34' || value.substring(0,2) == '37' ) {
					$( this ).attr( 'maxlength', 17 );
					$( '.sunshine--credit-card--cvc' ).attr( 'maxlength', 4 );
					if ( value.length >= 10 ) {
						value = value.substring(0,4) + ' ' + value.substring(4,10) + ' ' + value.substring(10);
					} else if ( value.length >= 4 ) {
						value = value.substring(0,4) + ' ' + value.substring(4);
					}
					value = value.substring(0,17);
				} else {
					$( this ).attr( 'maxlength', 19 );
					$( '.sunshine--credit-card--cvc' ).attr( 'maxlength', 3 );
					value = value.replace(/(.{4})/g, '$1 ');
					value = value.substring(0,19);
				}
				return value;
			});
		});
		$( document ).on( 'keyup', 'input.sunshine--credit-card--expiration', function(e) {
			if ( e.keyCode != 8 ) {
				$( this ).val( function ( index, value ) {
					let first_digit = value.substring( 0, 1 );
					if ( first_digit > 1 && first_digit < 10 ) {
						let rest = value.substring( 1 );
						value = '0' + first_digit + '/' + rest;
					}
					if ( value.length == 2 ) {
						value += '/';
					}
					console.log( 'exdate: ' + value );
					return value;
				});
			}
		});

	});
	</script>

	<?php
	$active_section      = ( SPC()->cart->get_active_section() == 'shipping' ) ? 'shipping' : 'billing';
	$google_maps_api_key = SPC()->get_option( 'google_maps_api_key' );
	if ( $google_maps_api_key ) {
		$default_country = SPC()->get_option( 'country' );
		?>
	<script src="https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr( $google_maps_api_key ); ?>&callback=sunshine_load_address_autocomplete&loading=async&libraries=places&v=weekly" async></script>
	<script id="sunshine--checkout--autocomplete">
		var sunshine_autocomplete, sunshine_autocomplete_listener;

		function sunshine_load_address_autocomplete() {
			<?php if ( $active_section == 'shipping' ) { ?>
				sunshine_init_address_autocomplete( 'shipping' );
			<?php } ?>
		}

		function sunshine_init_address_autocomplete( section, default_country = '<?php echo esc_js( $default_country ); ?>' ) {
			if ( sunshine_autocomplete ) {
				google.maps.event.removeListener( sunshine_autocomplete_listener );
				google.maps.event.clearInstanceListeners( sunshine_autocomplete );
				jQuery( ".pac-container" ).remove();
			}
			  address1Field = document.querySelector("#" + section + "_address1");
			  address2Field = document.querySelector("#" + section + "_address2");
			  postalField = document.querySelector("#" + section + "_postcode");
			  sunshine_autocomplete = new google.maps.places.Autocomplete(address1Field, {
				componentRestrictions: { country: [ default_country ] },
				fields: ["address_components", "geometry"],
				types: ["address"],
			  });
			  //address1Field.focus();
			  sunshine_autocomplete_listener = sunshine_autocomplete.addListener( "place_changed", function(){
				sunshine_autopopulate_address( section );
			} );
		}

		jQuery( document ).on( 'sunshine_shipping_country_change', function( event, country ){
			sunshine_init_address_autocomplete( 'shipping', country );
		});

		jQuery( document ).on( 'sunshine_reload_checkout', function( event, section ) {
			if ( section == 'shipping' ) {
				sunshine_init_address_autocomplete( 'shipping', jQuery( '#shipping_country' ).val() );
			}
		});


		function sunshine_autopopulate_address( section ) {
			const place = sunshine_autocomplete.getPlace();
			let address1 = "";
			let postcode = "";

			for (const component of place.address_components) {
				const componentType = component.types[0];

				switch (componentType) {
				  case "street_number": {
					address1 = `${component.long_name} ${address1}`;
					break;
				  }

				  case "route": {
					address1 += component.short_name;
					break;
				  }

				  case "postal_code": {
					postcode = `${component.long_name}${postcode}`;
					break;
				  }

				  case "postal_code_suffix": {
					postcode = `${postcode}-${component.long_name}`;
					break;
				  }
				  case "locality":
					jQuery( '#' + section + '_city' ).val( component.short_name );
					jQuery( '#' + section + '_city' ).closest( '.sunshine--checkout--field' ).addClass( 'filled' );
					break;
				  case "administrative_area_level_1": {
					  jQuery( '#' + section + '_state' ).val( component.short_name );
					  jQuery( '#' + section + '_state' ).closest( '.sunshine--checkout--field' ).addClass( 'filled' );
					break;
				  }
				  case "country":
					jQuery( '#' + section + '_country' ).val( component.short_name );
					jQuery( '#' + section + '_country' ).closest( '.sunshine--checkout--field' ).addClass( 'filled' );
					break;
			  }
		  }

		  jQuery( '#' + section + '_address1' ).val( address1 );
		  jQuery( '#' + section + '_address1' ).closest( '.sunshine--checkout--field' ).addClass( 'filled' );

		  jQuery( '#' + section + '_postcode' ).val( postcode );
		  jQuery( '#' + section + '_postcode' ).closest( '.sunshine--checkout--field' ).addClass( 'filled' );
		}
	</script>

		<?php
	}
}

add_action( 'wp_ajax_sunshine_checkout_update', 'sunshine_checkout_update_summary' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_update', 'sunshine_checkout_update_summary' );
function sunshine_checkout_update_summary() {

	if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine-checkout-update' ) ) {
		wp_send_json_error();
	}

	SPC()->cart->setup();

	SPC()->log( 'Checkout update' );

	$html    = sunshine_get_template_html( 'checkout/checkout' );
	$refresh = SPC()->session->get( 'checkout_refresh' );
	SPC()->session->set( 'checkout_refresh', false );
	wp_send_json_success(
		array(
			'html'    => $html,
			'section' => SPC()->cart->get_active_section(),
			'refresh' => $refresh,
		)
	);

}

add_action( 'wp_ajax_sunshine_checkout_process_section', 'sunshine_checkout_process_section' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_process_section', 'sunshine_checkout_process_section' );
function sunshine_checkout_process_section() {

	if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine-checkout-process-section' ) || empty( $_POST['sunshine_checkout_section'] ) ) {
		wp_send_json_error();
	}

	// Do validation.
	$current_section = sanitize_text_field( $_POST['sunshine_checkout_section'] );
	SPC()->log( 'Processing checkout section: ' . $current_section );
	SPC()->cart->setup();
	$next_section = SPC()->cart->process_section( $current_section, $_POST );
	SPC()->log( 'Going to next checkout section: ' . $next_section );

	wp_send_json_success( array( 'next_section' => $next_section ) );
}


add_action( 'wp_ajax_sunshine_checkout_select_delivery_method', 'sunshine_checkout_select_delivery_method' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_select_delivery_method', 'sunshine_checkout_select_delivery_method' );
function sunshine_checkout_select_delivery_method() {

	if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine-checkout-select-delivery-method' ) ) {
		wp_send_json_error();
	}

	$selected_delivery_method = sanitize_text_field( $_REQUEST['delivery_method'] );
	$delivery_methods         = sunshine_get_delivery_methods();
	if ( array_key_exists( $selected_delivery_method, $delivery_methods ) ) {

		$this_delivery_method = sunshine_get_delivery_method_by_id( $selected_delivery_method );
		SPC()->cart->set_delivery_method( $selected_delivery_method );
		SPC()->cart->update();

		SPC()->log( 'Checkout update: Delivery method set to ' . $this_delivery_method->get_name() );

		$result = array(
			'needs_shipping' => $this_delivery_method->needs_shipping(),
			'summary'        => sunshine_get_template_html( 'checkout/summary' ),
		);
		wp_send_json_success( $result );

	}

	wp_send_json_error();

}

add_action( 'wp_ajax_sunshine_checkout_select_shipping_method', 'sunshine_checkout_select_shipping_method' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_select_shipping_method', 'sunshine_checkout_select_shipping_method' );
function sunshine_checkout_select_shipping_method() {

	if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine-checkout-select-shipping-method' ) ) {
		wp_send_json_error();
	}

	SPC()->cart->update();

	$selected_shipping_method_instance = sanitize_text_field( $_REQUEST['shipping_method'] );
	$active_shipping_methods           = sunshine_get_active_shipping_methods();
	if ( array_key_exists( $selected_shipping_method_instance, $active_shipping_methods ) ) {

		$this_shipping_method = sunshine_get_shipping_method_by_instance( $selected_shipping_method_instance );
		SPC()->cart->set_shipping_method( $selected_shipping_method_instance );
		SPC()->cart->update();

		SPC()->log( 'Checkout update: Shipping method set to ' . $this_shipping_method->get_name() );

		$result = array(
			'summary' => sunshine_get_template_html( 'checkout/summary' ),
		);
		wp_send_json_success( $result );

	}

	wp_send_json_error();

}

add_action( 'wp_ajax_sunshine_checkout_use_credits', 'sunshine_checkout_use_credits' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_use_credits', 'sunshine_checkout_use_credits' );
function sunshine_checkout_use_credits() {

	if ( ! wp_verify_nonce( $_POST['security'], 'sunshine-checkout-use-credits' ) ) {
		wp_send_json_error();
	}

	SPC()->cart->setup();

	if ( ! empty( $_POST['use_credits'] ) && $_POST['use_credits'] == 'yes' ) {
		SPC()->log( 'Checkout update: Use credits' );
		SPC()->cart->set_use_credits( true );
	} else {
		SPC()->log( 'Checkout update: Disable credits' );
		SPC()->cart->set_use_credits( false );
	}

	$result = array(
		'total'             => SPC()->cart->get_total(),
		'total_formatted'   => SPC()->cart->get_total_formatted(),
		'credits'           => SPC()->cart->get_credits_applied(),
		'credits_formatted' => SPC()->cart->get_credits_applied_formatted(),
	);
	wp_send_json_success( $result );
	exit;

}

add_action( 'wp_ajax_sunshine_checkout_select_payment_method', 'sunshine_checkout_select_payment_method' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_select_payment_method', 'sunshine_checkout_select_payment_method' );
function sunshine_checkout_select_payment_method() {

	if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine-checkout-select-payment-method' ) ) {
		wp_send_json_error();
	}

	$selected_payment_method = sanitize_text_field( $_REQUEST['payment_method'] );
	$active_payment_methods  = sunshine_get_active_payment_methods();
	if ( array_key_exists( $selected_payment_method, $active_payment_methods ) ) {
		SPC()->cart->set_payment_method( $selected_payment_method );
		$payment_method = SPC()->cart->get_payment_method();
		SPC()->log( 'Checkout update: Set payment method to ' . $payment_method->get_name() );
		$result       = array(
			'summary' => sunshine_get_template_html( 'checkout/summary' ),
		);
		$submit_label = $payment_method->get_submit_label();
		if ( $submit_label ) {
			$result['submit_label'] = $submit_label;
		}
		wp_send_json_success( $result );
	}

	wp_send_json_error();

}


add_action( 'wp_ajax_sunshine_checkout_update_state', 'sunshine_checkout_update_state' );
add_action( 'wp_ajax_nopriv_sunshine_checkout_update_state', 'sunshine_checkout_update_state' );
function sunshine_checkout_update_state() {

	if ( ! wp_verify_nonce( $_REQUEST['security'], 'sunshine-checkout-update-state' ) ) {
		wp_send_json_error();
	}

	SPC()->cart->setup();

	if ( isset( $_POST['country'] ) ) {

		$type = sanitize_key( $_POST['type'] );
		if ( $type == 'shipping' ) {
			$prefix = 'shipping_';
		} else {
			$prefix = 'billing_';
		}

		$country = sanitize_text_field( $_POST['country'] );

		SPC()->log( 'Checkout update state' );

		SPC()->cart->set_checkout_data_item( $prefix . '_country', $country );

		$output         = '';
		$address_fields = SPC()->countries->get_address_fields( $country, $prefix );
		foreach ( $address_fields as $address_field ) {
			$output .= SPC()->cart->get_checkout_field_html( $address_field['id'], $address_field );
		}
	}
	echo $output;
	exit;
}


function sunshine_get_sections_completed() {
	$completed = SPC()->session->get( 'checkout_sections_completed' );
	if ( empty( $completed ) ) {
		return array();
	}
	return $completed;
}

function sunshine_checkout_section_completed( $section ) {
	$completed = SPC()->session->get( 'checkout_sections_completed' );
	if ( is_array( $completed ) && in_array( $section, $completed ) ) {
		return true;
	}
	return false;
}

function sunshine_checkout_is_section_active( $section ) {
	if ( $section == SPC()->cart->active_section ) {
		return true;
	}
	return false;
}


function sunshine_value_comparison( $var1, $var2, $comparison ) {
	switch ( $comparison ) {
		case '=':
		case '==':
			return $var1 == $var2;
		case '!=':
			return $var1 != $var2;
		case '>=':
			return $var1 >= $var2;
		case '<=':
			return $var1 <= $var2;
		case '>':
			return $var1 > $var2;
		case '<':
			return $var1 < $var2;
		default:
			return false;
	}
}
