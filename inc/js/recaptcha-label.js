jQuery( document ).ready( function ( $ ) {
    let timer = null;

    const labelFields = function () {
        $( 'textarea.g-recaptcha-response' ).not( '[aria-label]' ).attr( 'aria-label', wcagaat_recaptcha.label );
    };

    labelFields();

    new MutationObserver( function () {
        clearTimeout( timer );
        timer = setTimeout( labelFields, 250 );
    } ).observe( document.body, { childList: true, subtree: true } );
} );