jQuery( $ => {
    // console.log( 'Admin Bar JS Loaded...' );

    /**
     * Check the page for issues on load
     */
    function countPageIssues() {
        checkAltBubbles( true );
        checkColorContrast( true );
        checkVagueLinkTexts( true );
        checkHeadings( true );
        checkUnderlineIssues( true );
        updateTotalIssues();
    }
    $( document ).ready( countPageIssues );


    /**
     * Update the total issues count in the admin bar
     */
    function updateTotalIssues() {
        let total = 0;

        $( '.wcagaat-count' ).each( function() {
            const text = $( this ).text().replace( /[^\d]/g, '' );
            const num = parseInt( text );
            if ( !isNaN( num ) ) {
                total += num;
            }
        } );

        const $label = $( '#wp-admin-bar-wcagaat .ab-label' );

        if ( total > 0 ) {
            let $badge = $label.siblings( '.ab-issues' );
            if ( $badge.length === 0 ) {
                $badge = $( '<span class="wcagaat-count-indicator" title="' + wcagaat_admin_bar.text.total + '"></span>' );
                $label.after( $badge );
            }
            $badge.text( total );
        } else {
            $label.siblings( '.ab-issues' ).remove();
        }
    }

    
    /**
     * Toggle the checkboxes in the admin bar
     */
    $( '#wp-admin-bar-wcagaat input[type="checkbox"]' ).on( 'change', function() {
        const $this = $( this );
        const tool = $this.data( 'tool' );
        const state = $this.is( ':checked' );

        // Uncheck all others and remove their effects
        $( '#wp-admin-bar-wcagaat input[type="checkbox"]' ).not( this ).each( function() {
            const $input = $( this );
            const otherTool = $input.data( 'tool' );

            if ( $input.is( ':checked' ) ) {
                $input.prop( 'checked', false );

                switch ( otherTool ) {
                    case 'alt-text': removeAltBubbles(); break;
                    case 'contrast': removeColorContrast(); break;
                    case 'vague-link-text': removeVagueLinkTexts(); break;
                    case 'heading-hierarchy': removeHeadings(); break;
                    case 'underline-links': removeUnderlineIssues(); break;
                }
            }
        } );

        // For the current checkbox, run check or remove accordingly
        if ( state ) {
            switch ( tool ) {
                case 'alt-text': checkAltBubbles( false, true ); break;
                case 'contrast': checkColorContrast(); break;
                case 'vague-link-text': checkVagueLinkTexts(); break;
                case 'heading-hierarchy': checkHeadings(); break;
                case 'underline-links': checkUnderlineIssues(); break;
            }
        } else {
            switch ( tool ) {
                case 'alt-text': removeAltBubbles(); break;
                case 'contrast': removeColorContrast(); break;
                case 'vague-link-text': removeVagueLinkTexts(); break;
                case 'heading-hierarchy': removeHeadings(); break;
                case 'underline-links': removeUnderlineIssues(); break;
            }
        }
    } );


    /**
     * Image Alt Text
     */
    function checkAltBubbles( countOnly = false, logInConsole = false ) {
        let count = 0;

        $( 'img' ).not( '#wpadminbar img' ).each( function() {
            const img = $( this );
            const alt = img.attr( 'alt' );

            if ( ( alt === undefined || alt === null ) && !img.parent().hasClass( 'wcagaat-missing-wrapper' ) ) {
                if ( !countOnly ) {
                    img.wrap( `<div class="wcagaat-missing-wrapper" data-label="⚠️ ${wcagaat_admin_bar.text.missing}"></div>` );
                }
                count++;
                
                if ( logInConsole ) {
                    console.log( 'Missing alt text for image:', img[0] );
                    const path = img.parents().map( function() {
                        const el = $( this );
                        let sel = el.prop( 'tagName' ).toLowerCase();
                        if ( el.attr( 'id' ) ) sel += '#' + el.attr( 'id' );
                        else if ( el.attr( 'class' ) ) sel += '.' + el.attr( 'class' ).split( /\s+/ )[0];
                        return sel;
                    } ).get().reverse().join( ' > ' );
                    console.log( 'Path:', path );
                }
            }
        } );

        if ( countOnly ) {
            $( '.wcagaat-count[data-tool="alt-text"]' ).text( count > 0 ? `(${count})` : '(0)' );
        }
    }

    function removeAltBubbles() {
        $( '.wcagaat-missing-wrapper' ).each( function() {
            const wrapper = $( this );
            const img = wrapper.find( 'img' );
            img.unwrap();
        } );

        // $( '.wcagaat-count[data-tool="alt-text"]' ).text( '' );
    }


    /**
     * Poor Color Contrast
     */
    function checkColorContrast( countOnly = false ) {
        const useAAA = wcagaat_admin_bar.doing_aaa;
        let count = 0;

        $( '*:visible' ).not( '#wpadminbar *, #wcagaat-mode-switch *, .wcagaat-skip-link, .skip-link', ).each( function() {
            const $el = $( this );
            if ( $el.children().length ) return;

            if ( getComputedStyle( this ).visibility === 'hidden' ) return;

            const text = $el.text().trim();
            if ( !text ) return;

            const fg = getComputedStyle( this ).color;
            const bg = getEffectiveBackgroundColor( this );
            if ( !fg || !bg ) return;

            const ratio = getContrastRatio( fg, bg );
            if ( ratio < 4.5 ) {
                const isLarge = isLargeText( this );
                let failAA = false;
                let failAAA = false;

                if ( isLarge ) {
                    failAA = ratio < 3;
                    failAAA = ratio < 4.5;
                } else {
                    failAA = ratio < 4.5;
                    failAAA = ratio < 7;
                }

                // If AAA checking enabled, only show if it fails AAA
                if ( useAAA && !failAAA ) {
                    return;
                }

                // If AAA disabled, show if it fails AA
                if ( !useAAA && !failAA ) {
                    return;
                }

                console.log( 'Color Contrast Issue:', $el[0], ' | Foreground:', fg, ' | Background:', bg );

                if ( !countOnly ) {

                    const fgHex = rgbToHex( fg );
                    const bgHex = rgbToHex( bg );
                    const url = `https://webaim.org/resources/contrastchecker/?fcolor=${fgHex}&bcolor=${bgHex}`;

                    $el.addClass( 'wcagaat-poor-contrast' );

                    const offset = $el.offset();

                    // Badge text with ratio and fail level
                    let levelText = '';
                    if ( useAAA ) {
                        levelText = failAAA ? 'AAA' : ( failAA ? 'AA' : '' );
                    } else {
                        levelText = failAA ? 'AA' : '';
                    }

                    let shouldBe = '';

                    if ( isLarge ) {
                        shouldBe = useAAA ? '4.5' : '3';
                    } else {
                        shouldBe = useAAA ? '7' : '4.5';
                    }

                    const badge = $( '<a>' )
                        .addClass( 'wcagaat-contrast-badge' )
                        .attr( 'href', url )
                        .attr( 'target', '_blank' )
                        .attr( 'title', `${levelText} fail for ${isLarge ? 'large' : 'normal'} text, should be ≥ ${shouldBe}` )
                        .css({
                            top: offset.top,
                            left: offset.left
                        })
                        .text( ratio.toFixed(2) );

                    $( 'body' ).append( badge );
                }

                count++;
            }
        } );

        if ( countOnly ) {
            $( '.wcagaat-count[data-tool="contrast"]' ).text( count > 0 ? `(${count})` : '(0)' );
        }
    }

    function isLargeText( el ) {
        const style = getComputedStyle( el );
        const fontSize = parseFloat( style.fontSize );
        const fontWeight = style.fontWeight;

        const isBold = ( fontWeight === 'bold' || parseInt( fontWeight ) >= 700 );
        // 14pt ≈ 18.66px; 18pt ≈ 24px (approximate)
        return ( ( fontSize >= 24 ) || ( fontSize >= 18.66 && isBold ) );
    }

    function removeColorContrast() {
        $( '.wcagaat-poor-contrast' ).removeClass( 'wcagaat-poor-contrast' ).removeAttr( 'data-contrast-ratio' );
        $( '.wcagaat-contrast-badge' ).remove();
        // $( '.wcagaat-count[data-tool="contrast"]' ).text( '' );
    }

    function getEffectiveBackgroundColor( el ) {
        let bg = getComputedStyle( el ).backgroundColor;
        if ( !bg || bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)' ) {
            const parent = el.parentElement;
            if ( parent && parent !== document ) {
                return getEffectiveBackgroundColor( parent );
            }
            return 'rgb(255,255,255)'; // fallback
        }

        const rgba = parseRGBA( bg );
        if ( rgba.a < 1 ) {
            const parentBg = getEffectiveBackgroundColor( el.parentElement );
            const parentRgba = parseRGBA( parentBg );
            const composite = compositeColors( rgba, parentRgba );
            return `rgb(${composite.r}, ${composite.g}, ${composite.b})`;
        }

        return `rgb(${rgba.r}, ${rgba.g}, ${rgba.b})`;
    }


    function getContrastRatio( fg, bg ) {
        const l1 = getLuminance( fg );
        const l2 = getLuminance( bg );
        return ( Math.max( l1, l2 ) + 0.05 ) / ( Math.min( l1, l2 ) + 0.05 );
    }

    function getLuminance( color ) {
        const { r, g, b } = parseRGBA( color );
        const rgb = [ r, g, b ];
        const a = rgb.map( c => {
            c = c / 255;
            return c <= 0.03928 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
        } );
        return 0.2126 * a[0] + 0.7152 * a[1] + 0.0722 * a[2];
    }

    function parseRGBA( color ) {
        const match = color.match( /rgba?\((\d+), ?(\d+), ?(\d+)(?:, ?([\d.]+))?\)/ );
        if ( match ) {
            return {
                r: parseInt( match[1] ),
                g: parseInt( match[2] ),
                b: parseInt( match[3] ),
                a: match[4] !== undefined ? parseFloat( match[4] ) : 1
            };
        }
        return { r: 255, g: 255, b: 255, a: 1 };
    }

    function compositeColors( fg, bg ) {
        const alpha = fg.a + bg.a * (1 - fg.a);
        const r = Math.round( (fg.r * fg.a + bg.r * bg.a * (1 - fg.a)) / alpha );
        const g = Math.round( (fg.g * fg.a + bg.g * bg.a * (1 - fg.a)) / alpha );
        const b = Math.round( (fg.b * fg.a + bg.b * bg.a * (1 - fg.a)) / alpha );
        return { r, g, b };
    }

    function rgbToHex( rgb ) {
        const result = rgb.match( /\d+/g );
        if ( !result || result.length < 3 ) return '000000';
        return result.slice( 0, 3 ).map( x => {
            const hex = parseInt( x ).toString( 16 );
            return hex.length === 1 ? '0' + hex : hex;
        } ).join( '' ).toUpperCase();
    }


    /**
     * Vague Link Text
     */
    function checkVagueLinkTexts( countOnly = false ) {
        const vaguePhrases = wcagaat_admin_bar.vague_link_text
            .split( ',' )
            .map( phrase => phrase.trim().toLowerCase() )
            .filter( phrase => phrase.length > 0 );

        let count = 0;

        $( 'a:visible' ).not( '#wpadminbar a' ).each( function() {
            const $link = $( this );
            const linkText = $link.text().trim();

            if ( !linkText ) return;

            if ( $link.attr( 'aria-label' ) ) return;

            if ( vaguePhrases.includes( linkText.toLowerCase() ) ) {
                if ( !$link.hasClass( 'wcagaat-vague-link-text' ) ) {
                    if ( !countOnly ) {
                        $link.addClass( 'wcagaat-vague-link-text' );
                        $link.attr( 'title', 'Vague link text: "' + linkText + '"' );
                    }
                    count++;
                }
            }
        } );

        if ( countOnly ) {
            $( '.wcagaat-count[data-tool="vague-link-text"]' ).text( count > 0 ? `(${count})` : '(0)' );
        }
    }

    function removeVagueLinkTexts() {
        $( '.wcagaat-vague-link-text' ).each( function() {
            const $link = $( this );
            $link.removeClass( 'wcagaat-vague-link-text' );
            $link.removeAttr( 'title' );
        } );

        // $( '.wcagaat-count[data-tool="vague-link-text"]' ).text( '' );
    }


    /**
     * Heading Hierarchy
     */
    function checkHeadings( countOnly = false ) {
        const headings = $( 'h1, h2, h3, h4, h5, h6' ).filter( ':visible' );
        let lastLevel = 0;
        let errorCount = 0;

        headings.each( function() {
            const heading = $( this );
            const tag = heading.prop( 'tagName' ).toUpperCase();
            const level = parseInt( tag.replace( 'H', '' ) );

            if ( heading.find( '.wcagaat-heading-label' ).length > 0 ) return;

            if ( lastLevel && level > lastLevel + 1 ) {
                errorCount++;

                if ( !countOnly ) {
                    const label = $( '<span>' )
                        .addClass( 'wcagaat-heading-label wcagaat-error' )
                        .attr( 'title', `Skipped heading level (last was H${lastLevel})` )
                        .text( tag );
                    heading.append( label );
                    heading.addClass( 'wcagaat-heading-error' );
                }
            } else if ( !countOnly ) {
                const label = $( '<span>' )
                    .addClass( 'wcagaat-heading-label' )
                    .text( tag );
                heading.append( label );
            }

            lastLevel = level;
        } );

        if ( countOnly ) {
            $( '.wcagaat-count[data-tool="heading-hierarchy"]' ).text( errorCount > 0 ? `(${errorCount})` : '(0)' );
        }
    }

    function removeHeadings() {
        $( '.wcagaat-heading-label' ).remove();
        $( '.wcagaat-heading-error' ).removeClass( 'wcagaat-heading-error' );
        // $( '.wcagaat-count[data-tool="heading-hierarchy"]' ).text( '' );
    }


    /**
     * Links Missing Underlines
     */
    function checkUnderlineIssues( countOnly = false ) {
        let count = 0;

        $( 'a:visible' ).each( function() {
            const link = this;
            const $link = $( link );

            // Ignore this link if it has more than one span or any div
            const $spans = $link.children( 'span' );
            const $divs = $link.children( 'div' );
            if ( $spans.length > 1 || $divs.length > 0 ) {
                return; 
            }

            // Other exclusion logic
            if (
                link.className.match( /button/i ) ||
                $link.hasClass( 'btn' ) ||
                $link.closest( 'nav' ).length ||
                $link.closest( '.menu' ).length ||
                $link.closest( '#wpadminbar' ).length ||
                $link.closest( 'button' ).length ||
                $link.closest( '#forum-navigation' ).length ||
                $link.closest( '.skip-link' ).length
            ) {
                return;
            }

            const text = $link.text().trim();
            if ( !text ) return;

            const computed = window.getComputedStyle( link );
            const decoration = computed.textDecorationLine || computed.textDecoration;

            if ( decoration !== 'underline' && !looksLikeButton( computed ) ) {
                console.log( 'Link missing underline:', link );
                count++;

                if ( !countOnly && !$link.hasClass( 'wcagaat-underline-issue' ) ) {
                    $link.addClass( 'wcagaat-underline-issue' );
                    const label = $( '<span>' )
                        .addClass( 'wcagaat-underline-label' )
                        .attr( 'title', 'Link is not underlined' )
                        .text( '⚠️' );
                    $link.append( label );
                }
            }
        } );

        if ( countOnly ) {
            $( '.wcagaat-count[data-tool="underline-links"]' ).text( count > 0 ? `(${count})` : '(0)' );
        }
    }

    function looksLikeButton( computed ) {
        const bg = computed.backgroundColor;
        const borderRadius = parseFloat( computed.borderRadius );
        const paddingTop = parseFloat( computed.paddingTop );
        const paddingBottom = parseFloat( computed.paddingBottom );
        const paddingLeft = parseFloat( computed.paddingLeft );
        const paddingRight = parseFloat( computed.paddingRight );

        const hasBackground = bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent';
        const hasRoundedCorners = borderRadius > 3;
        const hasPadding = ( paddingTop + paddingBottom + paddingLeft + paddingRight ) > 10;

        return hasBackground && hasRoundedCorners && hasPadding;
    }

    function removeUnderlineIssues() {
        $( '.wcagaat-underline-issue' ).removeClass( 'wcagaat-underline-issue' );
        $( '.wcagaat-underline-label' ).remove();
        // $( '.wcagaat-count[data-tool="underline-links"]' ).text( '' );
    }

} );
