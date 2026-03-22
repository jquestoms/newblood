(function( $, window, document, wp, params, undefined) {
	$( () => {
		const queryArgs = Object.fromEntries( location.search.replace( '?', '' ).split( '&' ).map( a => a.split( '=' ) ) ),
			  tab       = queryArgs.tab,
			  section   = queryArgs.section;

		if ( 'email' === tab && section ) {
			const email = params.emails[ section ];

			if ( email.is_customer ) {

				$(`#${email.id}_enabled`).on('change', ( e ) => {
					const $this = $( e.currentTarget );
					$(`#${email.id}_multiple_email_recipients_enabled`)
						.closest('tr')
						.toggle( $this.prop('checked') );
				}).trigger( 'change' );

			} else {

				$( `input[name="${email.id}_recipient"], .wmer-field-wrapper` ).each( ( index, element ) => {
					$( element ).after(
						$( '<button>', { class: 'button wmer-action add_recipient', type: 'button' } ).text( params.i18n.add_recipient )
					)
				});
	
				$(`input[name^="${email.id}_wmer_recipient`).each( ( index, input ) => {
					$( input ).after(
						$( '<button>', { class: 'button wmer-button-icon wmer-minus-button', type: 'button' } )
					)
				});
	
				$( document ).on( 'click', '.wmer-action.add_recipient', () => {
					const count = $( '.wmer-field-wrapper' ).length,
						  template = wp.template( 'wmer-recipient' ),
						  $wrapper = $( template( { id: email.id, count: count + 1 } ) ).hide();
	
					$( '.wmer-addons>*' ).last().after(
						$wrapper
					)
	
					$wrapper.show()
					$( 'span.woocommerce-help-tip', $wrapper ).tipTip({
						attribute: 'data-tip',
						fadeIn:    50,
						fadeOut:   50,
						delay:     200,
						keepAlive: true
					})
	
					$( document.body ).triggerHandler( 'wc-enhanced-select-init' )
				});
	
				$( document ).on( 'click', '.wmer-minus-button', e => {
					const $this      = $( e.currentTarget ),
						  $wrapper   = $this.closest( '.wmer-field-wrapper' ),
						  $recipient = $( 'input[type="text"]', $wrapper );
	
					if ( '' === $recipient.val() || confirm( params.i18n.warning_remove_recipient ) ) {
						$this.closest( '.wmer-field-wrapper' ).remove();
					}
	
					renumberBlocks();
				});
	
				const renumberBlocks = () => {
					$(`.wmer-field-wrapper`).each( ( index, wrapper ) => {
						const $wrapper = $( wrapper );
	
						$( `input[name^="${email.id}_wmer_recipient"]`, $wrapper )
							.attr( 'id', `${email.id}_wmer_recipient_${index+1}`)
							.attr( 'name', `${email.id}_wmer_recipient_${index+1}`);
	
						$( `select[name^="${email.id}_wmer_recipient_categories"]`, $wrapper )
							.attr( 'id', `${email.id}_wmer_recipient_categories_${index+1}`)
							.attr( 'name', `${email.id}_wmer_recipient_categories_${index+1}[]`);
	
						$( `select[name^="${email.id}_wmer_recipient_products"]`, $wrapper )
							.attr( 'id', `${email.id}_wmer_recipient_products_${index+1}`)
							.attr( 'name', `${email.id}_wmer_recipient_products_${index+1}[]`);
					})
	
				};
	
				const $recipientCell = $( `input[name="${email.id}_recipient"]` ).closest( 'td' );
	
				if ( $recipientCell.length ) {
					$recipientCell.addClass( 'wmer-addons' );
					
					$(`input[name^="${email.id}_wmer_recipient"]`).each( ( index, element ) => {
						const $wrapper  = $( '<div>', { class: 'wmer-field-wrapper' } ),
							  $fieldSet = $( element ).closest( 'fieldset' ),
							  $row      = $fieldSet.closest( 'tr' );
	
						$recipientCell.append( $wrapper );
						
						$wrapper.append(
							$( '<span>', { class: 'woocommerce-help-tip' } ).attr( 'data-tip', params.i18n.recipient_tip ).tipTip({
								attribute: 'data-tip',
								fadeIn:    50,
								fadeOut:   50,
								delay:     200,
								keepAlive: true
							})
						).append( $fieldSet.detach() );
	
						$wrapper.append( $('fieldset', $row.next() ).detach() );
						$row.next().remove();
	
						$wrapper.append( $('fieldset', $row.next() ).detach() );
						$row.next().remove();
	
						$wrapper.append(
							$( '<button>', { class: 'button wmer-action add_recipient', type: 'button' } ).text( params.i18n.add_recipient )
						)
	
						$row.remove();
					});
				}

			}

		}
	});

	
})( jQuery, window, document, wp, wmer_params );
