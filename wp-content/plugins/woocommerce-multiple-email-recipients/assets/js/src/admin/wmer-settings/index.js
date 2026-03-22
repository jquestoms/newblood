(function( $, window, document, params, undefined ){
	$( () => {

		const $labelsRow = $( '.wmer-additional-email-label' );

		function makeAdditionalLabelInput( maxLabels ) {
			const $currentLabels = $( 'input', $labelsRow );

			if ( $currentLabels.length + 1 < maxLabels ) {
				for ( let i = $currentLabels.length + 1; i < maxLabels; i++ ) {
					const $newInput = $( '<input>', {
						name: `wmer_customer_email_label[${i}]`,
						id: `wmer_customer_email_label_${i}`,
						type: 'text',
						value:'',
						placeholder: `${params.i18n.email_label_placeholder} ${i+1}`
					} ).css( { marginBottom: '5px', marginRight: '5px' } );
					$( '.wmer-additional-email-label .forminp' ).append( $newInput );
				}
			}

			$currentLabels.each( ( index, label ) => {
				$( label ).toggle( index + 1 < maxLabels );
			})

			$labelsRow.toggle( maxLabels > 1 );
		}

		$( '#multiple_email_recipients_customer_email_addresses' ).on( 'change', e => {
			const $qty = $( e.currentTarget ),
				  newValue = Number( $qty.val() ),
				  currentValue = Number( $qty.data( 'currentvalue' ) ),
				  alertShown = Boolean( $qty.data( 'alertshown' ) );

			if ( ! alertShown && newValue < currentValue ) {
				if ( ! confirm( params.i18n.warning_reduce_emails ) ) {
					$qty.val( currentValue );
				}

				$qty.data( 'alertshown', true );
			}

			makeAdditionalLabelInput( $qty.val() );
		});

	});
})( jQuery, window, document, wmer_params )