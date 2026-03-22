(function( $, window, document, params, undefined ){
	$( () => {
		$( document ).on( 'ajaxComplete', ( event, xhr, settings ) => {
			const data = settings.data;
			const args = new URLSearchParams( data );
			const action = args.get( 'action' );
		
			if ( action === 'woocommerce_get_customer_details' ) {
				// get the post ID from the URL
				const post = parseInt( ( new URLSearchParams( location.search ) ).get( 'post' ) );
				const data = {
					action: 'wmer_update_order_emails',
					post_id: post
				}

				$.ajax({
					url: params.ajax_url,
					data: data,
					type: 'POST',
					success: function( response ) {
						if ( response && response.success ) {
							console.log( response.data );
						}
					}
				});
			}
		})
	});
})( jQuery, window, document, woocommerce_admin_meta_boxes )