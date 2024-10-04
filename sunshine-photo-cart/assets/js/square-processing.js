async function sunshine_square_init_card( payments ) {
	const card = await payments.card();
	await card.attach( '#sunshine-square-payment-fields' );
	return card;
}

// Call this function to send a payment token, buyer name, and other details
// to the project server code so that a payment can be created with
// Payments API
async function sunshine_square_create_payment( token ) {

	sunshine_checkout_updating();
	const data = {
			'action': 'sunshine_square_init_order',
			'source_id': token,
			'security': spc_square_vars.security,
	}

	jQuery.ajax(
		{
			type: 'POST',
			url: spc_square_vars.ajax_url,
			data: data,
			success: function( result, textStatus, XMLHttpRequest) {

				if ( result.success ) {
					jQuery( '#sunshine--checkout form' ).append( '<input type="hidden" name="square_payment_id" value="' + result.data.payment_id + '" />' ).submit();
				} else {
					jQuery( '#sunshine-square-payment-errors' ).html( '' );
					jQuery( '#sunshine-square-payment-errors' ).prepend( '<div style="background: red; color: #FFF; padding: 7px 12px;">' + result.data.reasons + '</div>' );
					sunshine_checkout_updating_done();
					return false;
				}

			},
			error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( 'Sorry, there was an error with the attempt to process with Stripe' );
			}
		}
	);

}

 // This function tokenizes a payment method.
 // The ‘error’ thrown from this async function denotes a failed tokenization,
 // which is due to buyer error (such as an expired card). It is up to the
 // developer to handle the error and provide the buyer the chance to fix
 // their mistakes.
 async function sunshine_square_tokenize( paymentMethod ) {
	 const tokenResult = await paymentMethod.tokenize();
	if ( tokenResult.status === 'OK' ) {
		return tokenResult.token;
	} else {
		let errorMessage = `Tokenization failed - status: ${tokenResult.status}`;
		if ( tokenResult.errors ) {
			errorMessage += ` and errors: ${JSON.stringify(
				tokenResult.errors
			)}`;
		}
		throw new Error( errorMessage );
	}
 }

 // Helper method for displaying the Payment Status on the screen.
 // status is either SUCCESS or FAILURE;
 function sunshine_square_display_payment_results( status ) {
    const statusContainer = document.getElementById(
		'sunshine-square-payment-errors'
    );
	 if ( status === 'SUCCESS' ) {
		 statusContainer.classList.remove( 'is-failure' );
		 statusContainer.classList.add( 'is-success' );
	 } else {
		 statusContainer.classList.remove( 'is-success' );
		 statusContainer.classList.add( 'is-failure' );
	 }
	 statusContainer.style.visibility = 'visible';
 }

var sunshine_square_card;
document.addEventListener(
	'sunshine_checkout_payment_change',
	async function ( e ) {
		if ( ! window.Square ) {
			throw new Error( 'Square.js failed to load properly' );
		}

		// Show Square or not
		if ( e.detail != 'square' ) {
			jQuery( '#sunshine-square-payment' ).hide();
			return;
		} else {
			jQuery( '#sunshine-square-payment' ).show();
		}

		if ( sunshine_square_card ) {
			return;
		}

		const sunshine_square_payments = window.Square.payments( spc_square_vars.application_id, spc_square_vars.location_id );
		try {
			sunshine_square_card = await sunshine_square_init_card( sunshine_square_payments );
		} catch (e) {
			return;
		}

		async function sunshine_square_handle_submission( event, paymentMethod ) {
			event.preventDefault();
			var currentTarget = event.target;

			try {
				const sunshine_square_token      = await sunshine_square_tokenize( paymentMethod );
				const sunshine_square_payment_id = await sunshine_square_create_payment( sunshine_square_token );
			} catch (e) {
				sunshine_square_display_payment_results( 'FAILURE' );
				sunshine_checkout_updating_done();
			}

		}

		const cardButton = document.getElementById(
			'sunshine--checkout--submit'
		);
		cardButton.addEventListener(
			'click',
			async function (event) {
				sunshine_checkout_updating();
				await sunshine_square_handle_submission( event, sunshine_square_card );
			}
		);

	}
);
