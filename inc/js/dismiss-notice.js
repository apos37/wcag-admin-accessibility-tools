jQuery( $ => {
	// console.log( 'Notice Dismissal JS Loaded...' );

	$( '#wcagaat-deprecated-shortcode-notice' ).on( 'click', '.notice-dismiss', function() {
		console.log( 'Dismiss clicked...' );
		 $.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'wcagaat_dismiss_deprecated_shortcode_notice',
				nonce: wcagaat_notice_dismissal.nonce,
			},
			success: function( response ) {
				console.log( 'Notice dismissal recorded:', response );
			}
		 } );
	} );

} );