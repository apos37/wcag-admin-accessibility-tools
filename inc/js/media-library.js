jQuery( $ => {
    // console.log( 'Media Library JS Loaded...' );

    $( document ).on( 'click', '.alt-text-edit', function( e ) {
        e.preventDefault();

        var $link = $( this );
        var id = $link.data( 'id' );
        var $cell = $link.closest( 'td' );
        var $display = $cell.find( '.alt-text-display' );
        var current = $display.text().trim();

        var $editArea = $cell.find( '.alt-text-editing' );
        var $input = $( '<input type="text" class="alt-text-input" />' ).val( current );
        var $button = $( `<button class="button alt-text-save">${media_alt_edit.text.update}</button>` );

        $display.hide();
        $link.closest( '.row-actions' ).hide();

        $editArea.empty().append( $input ).append( $button ).show();

        $input.on( 'keypress', function( e ) {
            if ( e.which === 13 ) {
                e.preventDefault();
                $button.trigger( 'click' );
            }
        } );

        $button.on( 'click', function( e ) {
            e.preventDefault();

            var newVal = $input.val();

            $.post( media_alt_edit.ajaxurl, {
                action: 'update_alt_text',
                post_id: id,
                alt_text: newVal,
                nonce: media_alt_edit.nonce
            }, function( response ) {
                if ( response.success ) {
                    $display.text( response.data ).show();
                    $editArea.hide().empty();
                    $cell.find( '.row-actions' ).show();
                } else {
                    alert( response.data );
                }
            } );
        } );
    } );
} );
