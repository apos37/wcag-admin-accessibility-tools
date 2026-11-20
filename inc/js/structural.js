jQuery( $ => {
    const settings = window.wcagaat_structural || {};
    const noticeText = ( settings.notice_text || '' ).trim();
    const noticeClass = 'wcagaat-external-notice';
    const defaultPattern = /external|new (tab|window)|opens in|leaves (this )?site|outside (this )?site/i;

    function normalizeHost( host ) {
        return host.toLowerCase().replace( /^www\./, '' );
    }

    function isExternal( link ) {
        if ( ! /^https?:$/.test( link.protocol ) ) {
            return false;
        }
        return normalizeHost( link.hostname ) !== normalizeHost( window.location.hostname );
    }

    function getAccessibleText( link ) {
        let text = ( link.getAttribute( 'aria-label' ) || '' ) + ' ' + link.textContent;
        ( link.getAttribute( 'aria-describedby' ) || '' ).split( /\s+/ ).forEach( id => {
            const described = id ? document.getElementById( id ) : null;
            if ( described ) {
                text += ' ' + described.textContent;
            }
        } );
        $( link ).find( 'img[alt]' ).each( function() {
            text += ' ' + $( this ).attr( 'alt' );
        } );
        return text.replace( /\s+/g, ' ' ).trim();
    }

    function hasNotice( link ) {
        if ( link.querySelector( '.' + noticeClass ) ) {
            return true;
        }
        const text = getAccessibleText( link );
        if ( defaultPattern.test( text ) ) {
            return true;
        }
        return noticeText !== '' && text.toLowerCase().includes( noticeText.toLowerCase() );
    }

    function addNotices() {
        $( 'a[href]' ).not( '#wpadminbar a' ).each( function() {
            const link = this;
            if ( ! isExternal( link ) || ! getAccessibleText( link ) || hasNotice( link ) ) {
                return;
            }
            $( link ).append( $( '<span>' ).addClass( noticeClass + ' wcagaat-sr-only' ).text( ' ' + noticeText ) );
        } );
    }

    window.wcagaatExternal = { isExternal, hasNotice, getAccessibleText };

    if ( ! settings.apply_notice ) {
        return;
    }

    let timer = null;
    addNotices();
    new MutationObserver( () => {
        clearTimeout( timer );
        timer = setTimeout( addNotices, 250 );
    } ).observe( document.body, { childList: true, subtree: true } );
} );