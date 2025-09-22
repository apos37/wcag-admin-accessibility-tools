jQuery( $ => {
    console.log( 'Password Protected Eye JS Loaded...' );

    // Select the password field by its NAME attribute, as its ID is dynamic.
    const passwordField = $( 'input[name="post_password"]' );
    const toggleButton = $( '.wcagaat-password-toggle' );
    const toggleIcon = toggleButton.find( '.dashicons' );

    // Check if both elements exist on the page
    if ( passwordField.length && toggleButton.length ) {
        toggleButton.on( 'click', function() {
            // Check current type and toggle it
            if ( passwordField.attr( 'type' ) === 'password' ) {
                passwordField.attr( 'type', 'text' );
                toggleIcon.removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
            } else {
                passwordField.attr( 'type', 'password' );
                toggleIcon.removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
            }
        });
    }
} );
