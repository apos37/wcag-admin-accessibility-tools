jQuery( document ).ready( function ( $ ) {
    $( 'li.menu-item-has-children' ).each( function () {
        const menuItem = $( this );
        const trigger = menuItem.children( 'a' ).first();
        const submenu = menuItem.children( 'ul.sub-menu.x-dropdown' ).first();

        if ( ! trigger.length || ! submenu.length ) {
            return;
        }

        const syncState = function () {
            trigger.attr( 'aria-expanded', submenu.hasClass( 'x-active' ) ? 'true' : 'false' );
        };

        syncState();

        new MutationObserver( syncState ).observe( submenu[ 0 ], { attributes: true, attributeFilter: [ 'class' ] } );
    } );

    let lastPointerType = '';

    window.addEventListener( 'pointerdown', function ( event ) {
        lastPointerType = event.pointerType;
    }, true );

    window.addEventListener( 'click', function ( event ) {
        const pointerType = lastPointerType;
        lastPointerType = '';

        const link = event.target.closest( 'li.menu-item-has-children > a' );

        if ( ! link || ! event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey ) {
            return;
        }

        if ( pointerType === 'touch' || pointerType === 'pen' || ! link.parentElement.querySelector( ':scope > ul.x-dropdown' ) ) {
            return;
        }

        const href = link.getAttribute( 'href' );

        if ( ! href || href.charAt( 0 ) === '#' || link.target === '_blank' ) {
            return;
        }

        window.location.assign( link.href );
    } );

    $( '.x-modal[role="dialog"]' ).each( function () {
        const modal = $( this );
        const modalId = this.id;
        let wasActive = modal.hasClass( 'x-active' );

        if ( ! modalId ) {
            return;
        }

        new MutationObserver( function () {
            const isActive = modal.hasClass( 'x-active' );

            if ( wasActive && ! isActive ) {
                $( '.x-anchor[data-x-toggle][aria-controls]' ).filter( function () {
                    return $( this ).attr( 'aria-controls' ) === modalId && $( this ).is( ':visible' );
                } ).first().trigger( 'focus' );
            }

            wasActive = isActive;
        } ).observe( this, { attributes: true, attributeFilter: [ 'class' ] } );
    } );
} );