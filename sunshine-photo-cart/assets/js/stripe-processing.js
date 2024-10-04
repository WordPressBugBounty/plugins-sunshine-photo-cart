var stripe = Stripe( spc_stripe_vars.publishable_key, { stripeAccount: spc_stripe_vars.account_id, locale: 'auto' } );

const options = {
	clientSecret: spc_stripe_vars.client_secret,
};

const elements = stripe.elements( options );
var paymentElement;

jQuery( document ).on( 'change', 'input[name="payment_method"]', function(){

	var sunshine_selected_payment_method = jQuery( 'input[name="payment_method"]:checked' ).val();
	if ( sunshine_selected_payment_method == 'stripe' ) {

		jQuery( '#sunshine-stripe-payment' ).show();

		if ( ! paymentElement ) {
			const payment_options = {
			  layout: {
			    type: spc_stripe_vars.layout,
			  }
			};
			paymentElement = elements.create( 'payment', payment_options );
		}
		paymentElement.mount( '#sunshine-stripe-payment-fields' );

	} else {
		jQuery( '#sunshine-stripe-payment' ).hide();
	}

});

jQuery( document ).on(
	'click',
	'.sunshine--checkout--section-edit',
	function() {
		if ( paymentElement ) {
			paymentElement.unmount();
		}
	}
);

jQuery( document ).on(
	'click',
	'#sunshine--checkout--submit',
	function(event){

		var sunshine_selected_payment_method = jQuery( 'input[name="payment_method"]:checked' ).val();
		if ( sunshine_selected_payment_method != 'stripe' ) {
			return;
		}

		event.preventDefault();
		sunshine_checkout_updating();

		jQuery( '#sunshine-stripe-payment-errors' ).html();

		// Ajax request to create order
		const data = {
			'action': 'sunshine_stripe_init_order',
			'security': spc_stripe_vars.security,
		}

		jQuery.ajax(
			{
				type: 'POST',
				url: spc_stripe_vars.ajax_url,
				data: data,
				success: function( result, textStatus, XMLHttpRequest) {

					if ( result.success ) {
						jQuery( '#sunshine-stripe-payment-errors' ).html();

						stripe.confirmPayment({
							elements,
							confirmParams: {
								return_url: spc_stripe_vars.return_url,
							},
							redirect: 'if_required'
						})
						.then(function(result) {
							if ( result.error ) {
								jQuery( '#sunshine-stripe-payment-errors' ).html( '<div style="background:red;padding:15px;color:#FFF;margin:10px 0;">' + result.error.message + '</div>' );
								sunshine_checkout_updating_done();
								return false;
							}
							jQuery( '#sunshine--checkout form' ).prepend( '<input type="hidden" name="stripe_order_id" value="' + result.paymentIntent.id + '" />' ).submit();
						});

					}

				},
				error: function( MLHttpRequest, textStatus, errorThrown ) {
					alert( 'Sorry, there was an error with the attempt to process with Stripe' );
				}
			}
		);

	}
);
