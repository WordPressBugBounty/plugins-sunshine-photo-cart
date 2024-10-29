function sunshine_open_modal( data, label = '' ) {

	// Add the main structure for the modal
	jQuery( 'body' ).addClass( 'sunshine--modal--open' ).append( '<div id="sunshine--modal--overlay" class="sunshine--loading"></div>' );

	data.action = 'sunshine_modal_display';
	data.security = sunshine_photo_cart.security;

	// Run action and place that content
	jQuery.ajax({
		type: 'POST',
		url: sunshine_photo_cart.ajax_url,
		data: data,
		success: function( result, textStatus, XMLHttpRequest) {
			jQuery( '#sunshine--modal--overlay' ).removeClass( 'sunshine--loading' );
			if ( result.success ) {
				if ( result.data.html ) {
					if ( ! data.close ) {
						data.close = '';
					}
					jQuery( 'body' ).append( '<div id="sunshine--modal" role="dialog" aria-label="' + label + '" data-close="' + data.close + '"><button id="sunshine--modal--close"></button><div id="sunshine--modal--content">' + result.data.html + '</div></div>' );
				}
				jQuery( '#sunshine--modal' ).addClass( 'sunshine--modal--' + data.hook );
				jQuery( document ).trigger( data.hook );
			} else {
				var content = 'Could not load content';
				if ( result.data.reason ) {
					content += ' (' + result.data.reason + ')'
				}
				jQuery( '#sunshine--modal--overlay' ).append( '<div id="sunshine--modal--error">' + content + '</div>' );
			}
		},
		error: function( MLHttpRequest, textStatus, errorThrown ) {
			alert( sunshine_photo_cart.lang.error );
		}
	});

}

function sunshine_close_modal() {
	jQuery( 'body' ).removeClass( 'sunshine--modal--open' );
	jQuery( '#sunshine--modal--overlay, #sunshine--modal' ).remove();
}

function sunshine_add_favorite( image_id ) {
	jQuery.ajax({
		type: 'POST',
		url: sunshine_photo_cart.ajax_url,
		data: {
			action: 'sunshine_add_to_favorites',
			image_id: image_id,
		},
		success: function( result, textStatus, XMLHttpRequest) {
			if ( result.success ) {
				if ( result.data.action == 'ADD' ) {

					jQuery( document ).trigger( 'sunshine_add_favorite', [ image_id ] );

					jQuery( '#sunshine--image-' + image_id + ', .sunshine--image-' + image_id ).addClass( 'sunshine--image--is-favorite' );
					if ( ! jQuery( '.sunshine--main-menu .sunshine--favorites .sunshine--favorites--count' ).length ) {
						jQuery( '.sunshine--main-menu .sunshine--favorites' ).append( '<span class="sunshine--count sunshine--favorites--count">' + result.data.count + '</span>' );
					}

				} else if ( result.data.action == 'DELETE' ) {

					jQuery( document ).trigger( 'sunshine_delete_favorite', [ image_id ] );

					jQuery( '#sunshine--image-' + image_id + ', .sunshine--image-' + image_id ).removeClass( 'sunshine--image--is-favorite' );
					if ( result.data.count == 0 ) {
						jQuery( '.sunshine--main-menu .sunshine--favorites--count' ).remove();
					}
					jQuery( '.sunshine--favorites #sunshine--image-' + image_id ).fadeOut( 300, function(){
						jQuery( this ).remove();
						if ( typeof $sunshine_images_masonry !== 'undefined' ) { // Masonry reload
							$sunshine_images_masonry.imagesLoaded( function() {
								$sunshine_images_masonry.masonry({transitionDuration: 0});
							});
						}
					});

				}
				jQuery( '#sunshine--action-menu li.sunshine--favorites a, #sunshine--image-' + image_id + ' li.sunshine--favorites a' ).toggleClass( 'sunshine-favorite' );
				jQuery( '.sunshine--favorites--count' ).html( parseInt( result.data.count ) );
			} else {
				alert( result.data );
			}
		},
		error: function( MLHttpRequest, textStatus, errorThrown ) {
			alert( sunshine_photo_cart.lang.error );
		}
	});
}

jQuery( document ).ready(function($){

    // Modal
    $( document ).on( 'click', '.sunshine--open-modal', function(e){

        // Get the action needed to populate the content area
        const data = $( this ).data();
		let label = $( this ).text();
		sunshine_open_modal( data, label );

		if ( ! data.allowhref ) {
			e.preventDefault()
			return false; // Disable link, whatever it is
		}

    });

    $( 'body' ).on( 'click', '#sunshine--modal--close, .sunshine--modal--close, #sunshine--modal--overlay', function(){
		if ( $( '#sunshine--modal' ).data( 'close' ) == 'refresh' ) {
			document.location.reload();
			return false;
		}
        sunshine_close_modal();
        return false;
    });

    /* Modal category toggle */
    $( 'body' ).on( 'click', '#sunshine--image--add-to-cart--categories li', function(){
        $( '.sunshine--image--add-to-cart--category' ).hide();
        $( '#sunshine--image--add-to-cart--categories li' ).removeClass().attr( 'aria-selected', 'false' );
        $( this ).addClass( 'active' );
        let category_id = $( this ).data( 'id' );
        $( '#sunshine--image--add-to-cart--category-' + category_id ).show().attr( 'aria-selected', 'true' );
        return false;
    });

    // Show product options
    $( document ).on( 'click', '#sunshine--modal--content .sunshine--product--show-details', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        // Remove any other product options in case they exist some how
        $( '#sunshine--product--details' ).remove();

        let product_id = $( this ).data( 'product-id' );
        let image_id = $( this ).data( 'image-id' );

        $( '#sunshine--image--add-to-cart--content' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_product_details',
                product_id: product_id,
                image_id: image_id,
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
					if ( result.data.url ) {
						window.location.href = result.data.url;
					} else {
						$( '#sunshine--image--add-to-cart--nav, #sunshine--image--add-to-cart--products' ).hide();
	                    $( '#sunshine--image--add-to-cart--content' ).append( result.data.html );
	                    $( document ).trigger( 'sunshine_product_details', [ product_id, image_id ] );
					}
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--image--add-to-cart--content' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });

    // Remove the product options
    $( 'body' ).on( 'click', '#sunshine--product--details--close', function(){
        $( '#sunshine--image--add-to-cart--nav, #sunshine--image--add-to-cart--products' ).show();
        $( '#sunshine--product--details' ).remove();
    });

    // Trigger product row to be clickable
    $( document ).on( 'click', '.sunshine--image--add-to-cart--product-item', function() {
        $( 'button', this ).trigger( 'click' );
    });

    // Add to cart from qty button in product row (not used right now)
    $( document ).on( 'change keyup', '.sunshine_modal_display_add_to_cart .sunshine--image--add-to-cart--product-row input[name="qty"]', function(){

        let image_id = $( this ).data( 'image-id' );
        let product_id = $( this ).data( 'product-id' );
        let gallery_id = $( this ).data( 'gallery-id' );
        let qty = parseInt( $( this ).val() );

        // Mini cart set to loading
        $( '#sunshine--image--cart-review' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_add_item_to_cart',
                image_id: image_id,
                product_id: product_id,
                gallery_id: gallery_id,
                qty: qty
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    $( '.sunshine--mini-cart' ).replaceWith( result.data.mini_cart );
                    $( '.sunshine--cart--count' ).html( result.data.count );
                    if ( qty > 0 ) {
                        $( '#sunshine, #sunshine--image-' + image_id ).addClass( 'sunshine--image--in-cart' );
                    } else {
                        $( '#sunshine, #sunshine--image-' + image_id ).removeClass( 'sunshine--image--in-cart' );
                    }
                } else {
					alert( sunshine_photo_cart.lang.error );
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--image--cart-review' ).removeClass( 'sunshine--loading' );
        });

    });

    $( document ).on( 'click', 'button.sunshine--qty--up', function(){
        let qty_input = $( this ).siblings( 'input' );
		let max = qty_input.attr( 'max' );
        let qty = parseInt( qty_input.val() );
        qty += 1;
		if ( qty > max ) {
			qty = max;
		}
        qty_input.val( qty );
        qty_input.trigger( 'change' );
    });

    $( document ).on( 'click', 'button.sunshine--qty--down', function(){
        let qty_input = $( this ).siblings( 'input' );
        let qty = parseInt( qty_input.val() );
        qty -= 1;
        if ( qty < 1 ) {
            return;
        }
        qty_input.val( qty );
        qty_input.trigger( 'change' );
    });

    // Add to cart from product details screen
    $( document ).on( 'click', '#sunshine--product--details--action button', function(e){

        let has_errors = false;

        $( '.sunshine--product-options--item' ).removeClass( 'sunshine--option-required' );

        // Check all required fields
        $( '.sunshine--product-options--item input:required, .sunshine--product-options--item input[type="hidden"]' ).each(function(){
            let required_name = $( this ).attr( 'name' );
            let type = $( this ).attr( 'type' );
            let option_value;
            if ( type == 'radio' ) {
                option_value = $( 'input[name="' + required_name + '"]:checked' ).val();
            } else {
                option_value = $( 'input[name="' + required_name + '"]' ).val();
            }
            if ( !option_value ) {
                $( this ).closest( '.sunshine--product-options--item' ).addClass( 'sunshine--option-required' );
                has_errors = true;
            }
        });

        if ( ! has_errors ) {

            let image_id = $( this ).data( 'image-id' );
            let gallery_id = $( this ).data( 'gallery-id' );
            let product_id = $( this ).data( 'product-id' );
            let qty = parseInt( $( '#sunshine--modal--content .sunshine--qty' ).val() );
			let comments;

            let options = {};
            let option_id;

            // Go through each radio option item, hidden and text field and get values
            $( '.sunshine--product-options--item input[type="radio"]:checked, .sunshine--product-options--item input[type="hidden"], .sunshine--product-options--item input[type="text"]' ).each(function( item ){
                option_id = $( this ).attr( 'name' )
                if ( $( this ).data( 'option-id' ) ) {
                    option_id = $( this ).data( 'option-id' );
                }
                options[ option_id ] = $( this ).val();
            });

			if ( $( 'input[name="comments"]' ).length ) {
				comments = $( 'input[name="comments"]' ).val();
			}

            // Go through each checkbox option item and get what is selected
            $( '.sunshine--product-options--item input[type="checkbox"]:checked' ).each(function( item ){
                if ( options[ $( this ).data( 'option-id' ) ] == undefined ) {
                    options[ $( this ).data( 'option-id' ) ] = [];
                }
                options[ $( this ).data( 'option-id' ) ] = $( this ).val();
            });

            // Mini cart set to loading
            $( '#sunshine--modal--content' ).addClass( 'sunshine--loading' );

			var data = {
				action: 'sunshine_modal_add_item_to_cart',
				image_id: image_id,
				gallery_id: gallery_id,
				product_id: product_id,
				options: options,
				comments: comments,
				qty: qty
			};

            // Then run ajax request
            $.ajax({
                type: 'POST',
                url: sunshine_photo_cart.ajax_url,
                data: data,
                success: function( result, textStatus, XMLHttpRequest) {
                    if ( result.success ) {
                        $( '.sunshine--mini-cart' ).replaceWith( result.data.mini_cart );
                        $( '.sunshine--cart--count' ).html( result.data.count );
                        if ( qty > 0 ) {
                            $( '#sunshine, #sunshine--image-' + image_id ).addClass( 'sunshine--image--in-cart' );
                        } else {
                            $( '#sunshine, #sunshine--image-' + image_id ).removeClass( 'sunshine--image--in-cart' );
                        }
                        $( '<div class="sunshine--success"></div>' ).appendTo( '#sunshine--modal--content' ).delay( 1000 ).fadeOut( 500, function(){ $( this ).remove(); } );
                        $( '#sunshine--image--add-to-cart--products, #sunshine--image--add-to-cart--nav' ).show();
                        $( '#sunshine--product--details' ).remove();
						if ( result.data.type ) {
							$( document ).trigger( 'sunshine_after_add_to_cart', [ data, result.data ] );
						}
                    } else {
                        alert( result.data );
                    }
                },
                error: function( MLHttpRequest, textStatus, errorThrown ) {
					alert( sunshine_photo_cart.lang.error );
                }
            }).always(function(){
                $( '#sunshine--modal--content' ).removeClass( 'sunshine--loading' );
            });

        }

    });

    // Show image select from store
    $( document ).on( 'click', '.sunshine--multi-image-select--open', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        // Remove any other multi-image-select window in case they exist some how
        if ( $( '.sunshine--multi-image-select' ).length ) {
            $( '.sunshine--multi-image-select' ).css( 'visibility', 'hidden' );
        }

		let ref = $( this ).data( 'ref' );
		let key = $( this ).data( 'key' );
        let gallery_id = $( this ).data( 'gallery-id' );
		let source_product_id = $( this ).data( 'source-product-id' );
        let product_id = $( this ).data( 'product-id' );
		let image_count = $( this ).data( 'image-count' );
		let target = $( this ).data( 'target' );
		let value_target = $( this ).data( 'value-target' );
		let selected_target = $( this ).data( 'selected-target' );
		let id = $( this ).data( 'id' );
		let selected = $( this ).data( 'selected' );

        $( '#sunshine--modal--content' ).addClass( 'sunshine--loading' );

		if ( $( '#' + id ).length ) {
			$( '#' + id ).css( 'visibility', 'visible' );
			$( '#sunshine--modal--content' ).removeClass( 'sunshine--loading' );
			return;
		}

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_multi_image_select_images',
				ref: ref,
				key: key,
                gallery_id: gallery_id,
				source_product_id: source_product_id,
                product_id: product_id,
				image_count: image_count,
				value_target: value_target,
				selected_target: selected_target,
				id: id,
				selected: selected,
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    $( '#' + target ).append( result.data.html );
                    $( document ).trigger( 'sunshine_modal_select_images' );
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--modal--content' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });

	$( document ).on( 'sunshine_after_add_to_cart', function( e, data, result ) {

		if ( data && data.options && data.options.images ) {
			setTimeout( function() {
				sunshine_close_modal();
			}, 1500 );
		}

		return false;

	});


	// PACKAGE/MULTIPLE PRODUCTS: When clicking thumbnail or placeholder, trigger the related button near it
	$( 'body' ).on( 'click', '.sunshine--multi-image-select--selected-images--item', function() {
		$( this ).closest( '.sunshine--product-options--item' ).find( '.sunshine--multi-image-select--open' ).click();
	});

    // Choose source
    $( 'body' ).on( 'change', '.sunshine--multi-image-select--sources select', function(){

		$( '.sunshine--multi-image-select--list' ).addClass( 'sunshine--loading' );

		let gallery_id = $( 'option:selected', this ).val();
		let product_id = $( this ).closest( '.sunshine--multi-image-select' ).data( 'product-id' );
		let image_count = $( this ).closest( '.sunshine--multi-image-select' ).data( 'image-count' );
		let selected_target = $( this ).closest( '.sunshine--multi-image-select' ).data( 'value-target' );
		let selected = $( 'input[name="' + selected_target + '"]' ).val();
		selected = selected.split( ',' );

		// Hide all existing source lists.
		$( '.sunshine--multi-image-select--source--list' ).hide();

		// See if source is already on the page, show if so.
		if ( $( '#sunshine--multi-image-select--source-' + gallery_id ).length ) {
			$( '#sunshine--multi-image-select--source-' + gallery_id ).show();
			$( '.sunshine--multi-image-select--list' ).removeClass( 'sunshine--loading' );
			return;
		}

		$.ajax({
			type: 'POST',
			url: sunshine_photo_cart.ajax_url,
			data: {
				action: 'sunshine_multi_image_select_gallery_images',
				gallery_id: gallery_id,
				product_id: product_id,
				image_count: image_count,
				selected: selected,
			},
			success: function( result, textStatus, XMLHttpRequest) {
				if ( result.success ) {
					$( '.sunshine--multi-image-select--list' ).append( result.data.html );
				}
			},
			error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
			}
		}).always(function(){
			$( '.sunshine--multi-image-select--list' ).removeClass( 'sunshine--loading' );
		});

    });

	$( 'body' ).on( 'change', '.sunshine--multi-image-select--image input', function(){

		if ( $(this).is(':checked') ) {
			var main_el = $( this ).closest( '.sunshine--multi-image-select' );
			currentTotal = 0;
			main_el.find('select[name*=qty]').not('#sunshine--multi-image-select--source-favorites select').each(function() {
			    var value = parseInt($(this).val());
			    if (!isNaN(value) && value >= 0) {
			        currentTotal += value;
			    }
			});
			var maxImages = main_el.data( 'image-count' );
			if ( maxImages && currentTotal >= maxImages  ) {
				$( this ).prop( 'checked', false );
				alert( 'You have already selected the maximum number of images' );
				return;
			}
		}

		var $select = $(this).closest('figure').find('select');
		var selectedValue = $(this).is(':checked') ? '1' : '0';
		$select.val(selectedValue).trigger('change');
	});

	// On selecting an image within multi-image-select
    $( 'body' ).on( 'change', '.sunshine--multi-image-select--image select[name*=qty]', function(){

		var main_el = $( this ).closest( '.sunshine--multi-image-select' );
		var key = main_el.data( 'key' );
		var ref = main_el.data( 'ref' );
		var maxImages = main_el.data( 'image-count' );
		var value_target = main_el.data( 'value-target' );
		var $checkbox = $(this).closest('figure').find('input[type="checkbox"]');

        var currentTotal = 0;

        // Calculate the current total of selected quantities
		main_el.find('select[name*=qty]').not('#sunshine--multi-image-select--source-favorites select').each(function() {
		    var value = parseInt($(this).val());
		    if (!isNaN(value) && value >= 0) {
		        currentTotal += value;
		    }
		});

		// Alert if over the limit
		if ( maxImages && currentTotal > maxImages ) {
			$checkbox.prop('checked', false);
			// Set to max allowed left?
			alert( sunshine_photo_cart.lang.max_images );
			return;
		}

		if ($(this).val() === '0') {
			$checkbox.prop('checked', false);
		} else {
			$checkbox.prop('checked', true);
		}

        // Update the hidden input
        var selectedIds = [];
        main_el.find('.sunshine--multi-image-select--source--list figure').not('#sunshine--multi-image-select--source-favorites figure').each(function() {
            var id = $(this).find('input[type="checkbox"]').val();
			if ( selectedIds.includes( id ) ) {
				return true;
			}
            var count = parseInt($(this).find('select').val());
            for (var i = 0; i < count; i++) {
                selectedIds.push(id);
            }
        });

		$( 'input[name="' + value_target + '"]' ).val( selectedIds.join( ',' ) );

		$.ajax({
			type: 'POST',
			url: sunshine_photo_cart.ajax_url,
			data: {
				action: 'sunshine_multi_image_select_images_item',
				selected_image_ids: selectedIds,
				key: key,
				ref: ref,
			},
			success: function( result, textStatus, XMLHttpRequest) {
				if ( result.success ) {
					let $selected_target = $( '#sunshine--multi-image-select--selected-images--' + main_el.data( 'key' ) );
					$selected_target.html( '' ); // Reset the thumbnail display.

					// Show each of the selected images.
					var selected_images_shown = 0;
					for ( var image of result.data ) {
						$selected_target.append( '<div class="sunshine--multi-image-select--selected-images--item" data-image="' + image.id + '"><img src="' + image.url + '" alt="" /></div>' );
						selected_images_shown++;
					}

					// Add the placeholders. If no limit, just add 1 as an indicator, otherwise add exact amount.
					if ( maxImages == 0 || maxImages == '' ) {
						$selected_target.append( '<div class="sunshine--multi-image-select--selected-images--item"></div>' );
					} else {
						for ( i = selected_images_shown; i < maxImages; i++ ) {
							$selected_target.append( '<div class="sunshine--multi-image-select--selected-images--item"></div>' );
						}
					}

					// Adjust the options in all selects
					if ( maxImages > 0 ) {
						main_el.find('.sunshine--multi-image-select--source--list select').each(function() {
				            var $select = $(this);
				            var currentValue = parseInt($select.val());
				            var maxOptionValue = maxImages - currentTotal + currentValue;
							var maxSingleImage = $select.data( 'max' );
							if ( maxSingleImage ) {
								maxOptionValue = maxSingleImage;
							}

							/*
							if ( maxOptionValue <= 0 ) {
								$( this ).attr( 'disabled', true );
							} else {
								$( this ).attr( 'disabled', false );
							}
							*/

				            // Remove options that are not currently selected and exceed the new max limit
				            $select.find('option').each(function() {
				                var optionValue = parseInt($(this).val());
				                if (optionValue > maxOptionValue && optionValue !== currentValue) {
				                    $(this).remove();
				                }
				            });

				            // Add back options if needed
				            for (let i = 0; i <= maxOptionValue; i++) {
				                if (!$select.find(`option[value=${i}]`).length) {
				                    $select.append(`<option value="${i}">${i}</option>`);
				                }
				            }

				        });

						main_el.find( '.sunshine--multi-image-select--counts--selected' ).html( currentTotal );
						if ( currentTotal >= maxImages ) {
							main_el.addClass( 'sunshine--completed' );
						} else {
							main_el.removeClass( 'sunshine--completed' );
						}
					}

				}
			},
			error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
			}
		});

    });

	/*
    // Show selected images
    $( 'body' ).on( 'click', '.sunshine--multi-image-select--show-selected', function(){
        $( '.sunshine--multi-image-select--image' ).hide();
        $( '.sunshine--multi-image-select--list' ).find( 'input[type=checkbox]:checked' ).parent().show();
        return false;
    });
	*/

    $( 'body' ).on( 'click', '.sunshine--multi-image-select--close', function(){
        $( '.sunshine--multi-image-select' ).css( 'visibility', 'hidden' );
        return false;
    });

	// Saving images for multi-image product from cart.
    $( 'body' ).on( 'click', '.sunshine--modal--multi_image_product_images_edit .sunshine--multi-image-select--close', function(){
        let selected_image_ids = [];
		$( '#sunshine--modal input:checked' ).each(function () {
			selected_image_ids = $( 'input[name="selected_images"]' ).val();
			if ( selected_image_ids ) {
				selected_image_ids = selected_image_ids.split( ',' );
			}
  		});

        // Ajax request to update images
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_multi_image_select_save',
                images: selected_image_ids,
            },
            success: function( result, textStatus, XMLHttpRequest) {
                location.reload();
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            location.reload();
        });
        return false;
    });

    // Add comment to image
    $( 'body' ).on( 'submit', '#sunshine--image--comments--add-form', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        let security = $( 'input[name="sunshine_image_comment_nonce"]' ).val();
        let image_id = $( 'input[name="sunshine_image_id"]' ).val();
        let name = $( 'input[name="sunshine_comment_name"]' ).val();
        let email = $( 'input[name="sunshine_comment_email"]' ).val();
        let content = $( 'textarea[name="sunshine_comment_content"]' ).val();

        // Mini cart set to loading
        $( '#sunshine--image--comments--add-form' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_add_comment',
                image_id: image_id,
                name: name,
                email: email,
                content: content,
                security: security
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    $( '#sunshine--image--comments--list' ).append( result.data.html );
                    // Reset the form fields
                    $( 'textarea[name="sunshine_comment_content"]' ).val( '' );

                    // TODO: Increase comment counts where needed on page
                    $( '.sunshine--image-' + image_id + ' .sunshine--comments .sunshine--count, #sunshine--image-' + image_id + ' .sunshine--comments .sunshine--count' ).html( result.data.count );

                    if ( result.data.count > 0 ) {
                        $( '.sunshine--image-' + image_id + ', #sunshine--image-' + image_id ).addClass( 'sunshine--image--has-comments' );
                    }
                } else {
                    $( '#sunshine--image--comments--list' ).append( '<div class="sunshine--error">' + result.data + ' </div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--image--comments--add-form' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });

    // Login
    $( 'body' ).on( 'submit', '#sunshine--modal--content #sunshine--account--login-form', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        let email = $( 'input[name="sunshine_login_email"]' ).val();
        let password = $( 'input[name="sunshine_login_password"]' ).val();
        let security = $( 'input[name="sunshine_login"]' ).val();

        // Form set to loading
        $( '#sunshine--account--login-form' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_login',
                email: email,
                password: password,
                security: security
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
					if ( result.data.redirect ) {
						window.location = result.data.redirect;
					} else {
						// Refresh the page, should now be logged in
	                    document.location.reload();
					}
                } else {
                    $( '#sunshine--account--login-form' ).prepend( '<div class="sunshine--error">' + result.data + ' </div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--account--login-form' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });


    // Sign up
    $( 'body' ).on( 'submit', '#sunshine--account--signup-form', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        let email = $( 'input[name="sunshine_signup_email"]' ).val();
        let password = $( 'input[name="sunshine_signup_password"]' ).val();
        let security = $( 'input[name="sunshine_signup"]' ).val();

		// Get all extra fields
		var data = {
			action: 'sunshine_modal_signup',
			security: security,
		};
		$( '#sunshine--account--signup-form .sunshine--form--field input' ).each(function( item ){
			if ( $( this ).attr( 'type' ) == 'checkbox' ) {
				data[ $( this ).attr( 'name' ) ] = ( $(this).is(':checked') ) ? 1 : 0;
			} else {
				data[ $( this ).attr( 'name' ) ] = $( this ).val();
			}
		});

        // Form set to loading
        $( '#sunshine--account--signup-form' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: data,
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    // Refresh the page, email should have been triggered
                    document.location.reload();
                } else {
                    $( '#sunshine--account--signup-form' ).prepend( '<div class="sunshine--error">' + result.data + ' </div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--account--signup-form' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });


    // Password Reset
    $( 'body' ).on( 'submit', '#sunshine--account--reset-password-form', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        let email = $( 'input[name="sunshine_reset_password_email"]' ).val();
        let security = $( 'input[name="sunshine_reset_password_nonce"]' ).val();

        // Form set to loading
        $( '#sunshine--account--reset-password-form' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_reset_password',
                email: email,
                security: security
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    // Refresh the page, email should have been triggered
                    document.location.reload();
                } else {
                    $( '#sunshine--account--reset-password-form' ).prepend( '<div class="sunshine--error">' + result.data + ' </div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--account--reset-password-form' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });

    $( document ).on( 'click', '.sunshine--add-to-favorites[data-image-id]', function(event) {
		sunshine_add_favorite( $( this ).data( 'image-id' ) );
		event.preventDefault();
        return false;
    });

    // Share favorite recipient toggle
    $( 'body' ).on( 'change', 'input[name="sunshine_favorites_share_recipients[]"]', function(){
        $( '#sunshine-favorites-share-custom-recipient' ).hide();
        $( 'input[name="sunshine_favorites_share_recipients[]"]:checked' ).each(function(){
            if ( $( this ).val() == 'custom' ) {
                $( '#sunshine-favorites-share-custom-recipient' ).show();
            }
        });
    });

    // Login
    $( 'body' ).on( 'submit', '#sunshine--modal--content #sunshine--favorites--share', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        var recipients = [];
        let email;
        $( 'input[name="sunshine_favorites_share_recipients[]"]:checked' ).each(function(){
            recipients.push( $( this ).val() );
            if ( $( this ).val() == 'custom' ) {
                email = $( 'input[name="sunshine_favorites_share_custom_recipient_email"]' ).val();
            }
        });
        if ( recipients.length == 0 ) {
            $( '#sunshine--favorites--share' ).prepend( '<div class="sunshine--error">At least one recipient is required</div>');
            return false;
        }

        let note = $( 'textarea[name="sunshine_favorites_share_note"]' ).val();
        let security = $( 'input[name="sunshine_favorites_share"]' ).val();

        // Form set to loading
        $( '#sunshine--favorites--share' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_favorites_share_process',
                recipients: recipients,
                email: email,
                note: note,
                security: security
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    $( '<div class="sunshine--success"></div>' ).appendTo( '#sunshine--modal--content' ).delay( 1000 ).fadeOut( 500, function(){ sunshine_close_modal(); } );
                } else {
                    $( '#sunshine--favorites--share' ).prepend( '<div class="sunshine--error">' + result.data + '</div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--favorites--share' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });


    $( '#sunshine--pagination--load-more' ).on( 'click', function(){
        let button = $( this );
        let gallery = $( this ).data( 'gallery' );
        let page = $( this ).data( 'page' );
        let total = $( this ).data( 'total' );

        button.addClass( 'sunshine--loading' );

        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_gallery_pagination',
                gallery: gallery,
                page: page,
				security: sunshine_photo_cart.security,
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
					page++;
					button.data( 'page', page );
					if ( page >= total ) {
						button.hide();
					}
                    if ( typeof $sunshine_images_masonry !== 'undefined' ) { // Masonry reload
                        var $content = $( result.data.html );
                        $sunshine_images_masonry.append( $content ).masonry( 'appended', $content );
                        $sunshine_images_masonry.imagesLoaded( function() {
                            $sunshine_images_masonry.masonry({transitionDuration: 0});
                        });
                    } else { // All others
                        $( '#sunshine--image-items' ).append( result.data.html );
                    }
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            },
            complete: function(){
                button.removeClass( 'sunshine--loading' );
            }
        });

    });

    $( 'body' ).on( 'submit', '#sunshine--modal--content #sunshine--download--required-data', function(e){

        e.preventDefault();

        // Remove error just in case it exists
        $( '.sunshine--error' ).remove();

        let download_action = $( 'input[name="download_action"]' ).val();
        let email = $( 'input[name="sunshine_download_email"]' ).val();
        let passcode = $( 'input[name="sunshine_download_passcode"]' ).val();
        let security = $( 'input[name="sunshine_download_required_data_security"]' ).val();
        let image_id = $( 'input[name="image_id"]' ).val();
        let gallery_id = $( 'input[name="gallery_id"]' ).val();
		let download_print_release_approval = $( 'input[name="download_print_release_approval"]' ).val();

        // Form set to loading
        $( '#sunshine--download--required-data' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_download_required_data',
                download_action: download_action,
                image_id: image_id,
                gallery_id: gallery_id,
                passcode: passcode,
                email: email,
				download_print_release_approval: download_print_release_approval,
                security: security
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
                    $( '#sunshine--modal--content' ).html( result.data.html );
                    $( document ).trigger( 'download_free_image' );
                } else {
                    $( '#sunshine--download--required-data' ).prepend( '<div class="sunshine--error">' + result.data + '</div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--download--required-data' ).removeClass( 'sunshine--loading' );
        });

        return false;

    });

    $( document ).on( 'download_free_image download_credit_image download_history_image download_free_gallery download_order_files download_order_item download', function(){
        var download_trigger = document.getElementById( "sunshine--download--trigger" );
        if ( download_trigger ) {
            download_trigger.click();
        }
    });

	$( document ).on( 'click', '#sunshine--download--gallery-add-to-cart--button button', function(){
		var gallery_id = $( this ).data( 'gallery' );
		var product_id = $( this ).data( 'product' );

        // Form set to loading
        $( '#sunshine--modal--content' ).addClass( 'sunshine--loading' );

        // Then run ajax request
        $.ajax({
            type: 'POST',
            url: sunshine_photo_cart.ajax_url,
            data: {
                action: 'sunshine_modal_gallery_add_to_cart',
                gallery_id: gallery_id,
                product_id: product_id,
            },
            success: function( result, textStatus, XMLHttpRequest) {
                if ( result.success ) {
					$( '.sunshine--cart--count' ).html( result.data.count );
					$( '<div class="sunshine--success"></div>' ).appendTo( '#sunshine--modal--content' ).delay( 1000 ).fadeOut( 500, function(){ sunshine_close_modal(); } );
                } else {
                    $( '#sunshine--modale--content' ).prepend( '<div class="sunshine--error">' + result.data + '</div>');
                }
            },
            error: function( MLHttpRequest, textStatus, errorThrown ) {
				alert( sunshine_photo_cart.lang.error );
            }
        }).always(function(){
            $( '#sunshine--modal--content' ).removeClass( 'sunshine--loading' );
        });

        return false;

	});

	$( '.sunshine--cart-item--qty input' ).on( 'change', function() {
	    $( '#sunshine--cart--update-button input' ).prop( 'disabled', false );
	});


});

/* Infinite scroll on galleries */

// Select the pagination button
const sunshine_pagination_button = document.querySelector('#sunshine--pagination--load-more');

// Check if button exists
if (sunshine_pagination_button) {
	// Create Intersection Observer
	const sunshine_observer = new IntersectionObserver((entries) => {
		entries.forEach(entry => {
			if (entry.isIntersecting) {
				// Get the parent element
				const parentElement = entry.target.closest('.sunshine--pagination--auto');
				if (parentElement) {

					// Increment the current page
					let currentPage = parseInt(sunshine_pagination_button.getAttribute('data-page'), 10);
					const totalPage = parseInt(sunshine_pagination_button.getAttribute('data-total'), 10);

						sunshine_pagination_button.setAttribute('data-page', currentPage);

						// Trigger the button click
						sunshine_pagination_button.click();
						currentPage++;

				}
			}
		});
	}, { rootMargin: '0px' });

	// Start observing
	sunshine_observer.observe(sunshine_pagination_button);
}
