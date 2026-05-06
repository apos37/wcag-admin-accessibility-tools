jQuery( $ => {
	// console.log( 'Assistant JS Loaded...' );

	// Vars
	const activeTools = wcagaat_assistant.active_tools;
	const location = wcagaat_assistant.location;
	const toolCount = activeTools.length;
	const modes = wcagaat_assistant.modes;
	const modeKeys = Object.keys( modes );

	let currentPrefs = wcagaat_assistant.current_prefs;
	let sizeLevel = parseInt( currentPrefs.text_resizer ) || 0;
	let currentMode = currentPrefs.mode || 'default';

	// console.log( 'Current Prefs:', currentPrefs );


	/**
	 * On load
	 */
	if ( activeTools.includes( 'text_resizer' ) ) {
		if ( sizeLevel !== 0 ) {
			updateTextSize( sizeLevel );
		}
	}

	if ( activeTools.includes( 'modes' ) ) {
		maybeSwapLogo();

		$( window ).on( 'load', function() {
			setTimeout( maybeSuggestDarkMode, 500 );
		} );
	}


	/**
	 * ASSISTANT
	 */
	if ( toolCount > 1 ) {
		
		// Toggle Panel Visibility
		const $trigger = $( '#wcagaat-assistant-trigger' );
		const $panel = $( '#wcagaat-assistant-panel' );

		$trigger.on( 'click', function( e ) {
			e.stopPropagation();
			const isExpanded = $( this ).attr( 'aria-expanded' ) === 'true';
			
			$( this ).attr( 'aria-expanded', ! isExpanded );
			$panel.prop( 'hidden', isExpanded );
		});

		// Close panel if clicking outside
		$( document ).on( 'click', function( e ) {
			if ( ! $( e.target ).closest( '#wcagaat-assistant-container' ).length ) {
				$trigger.attr( 'aria-expanded', 'false' );
				$panel.prop( 'hidden', true );
			}
		});

		// Keyboard Accessibility: Escape key closes panel
		$( document ).on( 'keydown', function( e ) {
			if ( e.key === 'Escape' ) {
				$trigger.attr( 'aria-expanded', 'false' );
				$panel.prop( 'hidden', true );
				$trigger.focus();
			}
		});


		/**
		 * Text Resizer
		 */
		if ( activeTools.includes( 'text_resizer' ) ) {
			$( '.wcagaat-text-resizer-button' ).on( 'click', function() {
				const direction = $( this ).data( 'size' );
				if ( direction === 'larger' && sizeLevel < 5 ) {
					sizeLevel++;
				} else if ( direction === 'smaller' && sizeLevel > -2 ) {
					sizeLevel--;
				}
				updateTextSize( sizeLevel );
			});
		}

		
		/**
		 * Readable Font
		 */
		if ( activeTools.includes( 'readable_font' ) ) {
			$( '#wcagaat-readable-font-toggle' ).on( 'click', function() {
				toggleReadableFont();
			});
		}

		
		/**
		 * Modes
		 */
		if ( activeTools.includes( 'modes' ) ) {
			$( '#wcagaat-mode-dropdown' ).on( 'change', function() {
				setMode( $( this ).val() );
			} );			
		}


		/**
		 * Reset
		 */
		$( '#wcagaat-assistant-reset' ).on( 'click', function( e ) {
			e.preventDefault();

			// 1. Reset Text Resizer
			sizeLevel = 0;
			updateTextSize( 0 );

			// 2. Reset Readable Font
			if ( currentPrefs.readable_font ) {
				toggleReadableFont();
			}

			// 3. Reset Display Mode
			if ( currentMode !== 'default' ) {
				setMode( 'default' );
				$( '#wcagaat-mode-dropdown' ).val( 'default' );
			}

			// 4. Trigger the AJAX delete
			savePref( 'reset', true );
		});

	} else if ( toolCount === 1 && activeTools[0] === 'text_resizer' ) {

		$( '.wcagaat-text-resizer-button' ).on( 'click', function() {
			const direction = $( this ).data( 'size' );
			if ( direction === 'larger' && sizeLevel < 5 ) {
				sizeLevel++;
			} else if ( direction === 'smaller' && sizeLevel > -2 ) {
				sizeLevel--;
			}
			updateTextSize( sizeLevel );
		});
		
		
	} else if ( toolCount === 1 && activeTools[0] === 'readable_font' ) {
		
		$( '#wcagaat-readable-font-toggle' ).on( 'click', function() {
			toggleReadableFont();
		} );

	} else if ( toolCount === 1 && activeTools[0] === 'modes' ) {

		$( '#wcagaat-mode-toggle' ).on( 'click', function( event ) {
			event.preventDefault();
			const currentIndex = modeKeys.indexOf( currentMode );
			const nextIndex = ( currentIndex + 1 ) % modeKeys.length;
			setMode( modeKeys[ nextIndex ], true );
		} );
	}

	
	/**
	 * Resize text
	 */
	function updateTextSize( level ) {
		const newSize = 100 + ( level * 10 );

		$( 'html' ).css( 'font-size', `${newSize}%` );
		$( '.wcagaat-text-resizer-percent' ).text( `${newSize}%` );
		$( '#wcagaat-text-resizer-solo, #wcagaat-text-resizer-container' ).attr( 'data-current', level );

		savePref( 'text_resizer', level );
	};


	/**
	 * Toggle readable font
	 */
	function toggleReadableFont() {
		currentPrefs.readable_font = ! currentPrefs.readable_font;
		$( 'body' ).toggleClass( 'wcagaat-readable-font' );

		const newState = currentPrefs.readable_font ? 'on' : 'off';
		const newLabel = currentPrefs.readable_font ? 'On' : 'Off';

		$( '#wcagaat-assistant-panel #wcagaat-readable-font-toggle' ).text( newLabel );
		$( '[id="wcagaat-readable-font-toggle"]' ).attr( 'data-state', newState );
		$( '#wcagaat-readable-font-solo' ).attr( 'data-current', newState );
		$( '.wcagaat-readable-font-state' ).text( newLabel );

		savePref( 'readable_font', currentPrefs.readable_font );
	}


	/**
	 * Set mode
	 */
	function setMode( newModeKey, solo = false ) {
		const modes = wcagaat_assistant.modes;
		const newMode = modes[ newModeKey ];

		if ( ! newMode ) {
			return;
		}

		const newLabel = newMode.label;
		const newSwitch = newMode.switch;
		const newActive = newMode.active;
		const newIcon = newMode.icon;

		$( 'body' ).removeClass( `wcagaat-${ currentMode }-mode` ).addClass( `wcagaat-${ newModeKey }-mode` );
		
		currentMode = newModeKey;

		if ( solo ) {
			const $switch = $( '#wcagaat-mode-switch' );
			if ( $switch.length ) {
				$switch.attr( 'data-current', newModeKey );

				const $button = $switch.find( '#wcagaat-mode-toggle' );
				
				// Get next mode for aria-label
				const nextIndex   = ( modeKeys.indexOf( currentMode ) + 1 ) % modeKeys.length;
				const nextSwitch  = modes[ modeKeys[ nextIndex ] ].switch;

				$button.attr( 'aria-label', nextSwitch );
				$button.attr( 'title', nextSwitch );
				$button.find( 'i' ).remove();
				$button.prepend( $( newIcon ) ); // Inject HTML icon safely
				$button.find( '.screen-reader-text' ).text( newActive );

				$( '#wcagaat-mode-live' ).text( newActive );
			}
		}

		maybeSwapLogo();
		savePref( 'mode', newModeKey );
	}


	/**
	 * Swap logos based on current mode
	 */
	function maybeSwapLogo() {
		const lightLogo = wcagaat_assistant.light_mode_logo;
		const darkLogo = wcagaat_assistant.dark_mode_logo;

		if ( ! lightLogo || ! darkLogo ) {
			return;
		}

		const lightPath = new URL( lightLogo, window.location.origin ).pathname;
		const darkPath = new URL( darkLogo, window.location.origin ).pathname;

		$( 'img' ).each( function() {
			const $img = $( this );
			const srcPath = new URL( $img.attr( 'src' ), window.location.origin ).pathname;

			if ( $( 'body' ).hasClass( 'wcagaat-dark-mode' ) && srcPath === lightPath ) {
				$img.attr( 'src', darkLogo );
			} else if ( srcPath === darkPath ) {
				$img.attr( 'src', lightLogo );
			}
		} );
	}


	/**
	 * Suggest dark mode if user prefers it and it's not already set
	 */
	function maybeSuggestDarkMode() {
		if ( wcagaat_assistant.has_mode ) {
			return;
		}

		if ( ! window.matchMedia ) {
			return;
		}

		const prefersDark = window.matchMedia( '( prefers-color-scheme: dark )' ).matches;
		if ( ! prefersDark ) {
			return;
		}

		showDarkModePrompt();
	}


	/**
	 * Show custom dark mode prompt
	 */
	function showDarkModePrompt() {
		if ( $( '#wcagaat-dark-mode-modal' ).length ) {
			return;
		}

		const text = wcagaat_assistant.text;

		const modalHtml = `
			<div id="wcagaat-dark-mode-modal" role="dialog" aria-modal="true" aria-labelledby="wcagaat-dark-mode-title">
				<div class="wcagaat-dark-mode-backdrop"></div>
				<div class="wcagaat-dark-mode-content">
					<h2 id="wcagaat-dark-mode-title">${ text.enable_dark_mode }</h2>
					<p>${ text.suggest_dark_mode }</p>
					<div class="wcagaat-dark-mode-actions">
						<button type="button" class="wcagaat-dark-mode-yes">${ text.yes }</button>
						<button type="button" class="wcagaat-dark-mode-no">${ text.no }</button>
					</div>
				</div>
			</div>
		`;

		$( 'body' ).append( modalHtml );

		const $modal = $( '#wcagaat-dark-mode-modal' );
		$modal.find( '.wcagaat-dark-mode-yes' ).on( 'click', function() {
			const solo = toolCount === 1;
			if ( ! solo ) {
				$( '#wcagaat-mode-dropdown' ).val( 'dark' );
			}
			setMode( 'dark', solo );
			$modal.remove();
		} );

		$modal.find( '.wcagaat-dark-mode-no' ).on( 'click', function() {
			savePref( 'mode', 'default' );
			$modal.remove();
		} );

		$( document ).on( 'keydown.wcagaatDarkMode', function( e ) {
			if ( e.key === 'Escape' ) {
				savePref( 'mode', 'default' );
				$modal.remove();
				$( document ).off( 'keydown.wcagaatDarkMode' );
			}
		} );
	}


	/**
	 * Helper function to save preferences via AJAX
	 */
	function savePref( pref, value ) {
		const $resetLink = $( '#wcagaat-assistant-reset' );
		const isReset = pref === 'reset';
		const originalHtml = isReset ? $resetLink.html() : null;

		if ( isReset ) {
			$resetLink
				.addClass( 'is-saving' )
				.attr( 'aria-disabled', 'true' )
				.attr( 'aria-busy', 'true' )
				.html(
					'<span class="wcagaat-spinner" aria-hidden="true"></span>' +
					'<span class="wcagaat-status-text">' + wcagaat_assistant.text.resetting + '</span>'
				);
		}
		
		const args = {
			type: 'post',
			dataType: 'json',
			url: wcagaat_assistant.ajaxurl,
			data: {
				action: 'wcagaat_update_user_prefs',
				nonce: wcagaat_assistant.nonce,
				pref: pref,
				value: value,
			},
			success: function( response ) {
				if ( response.type === 'error' ) {
					console.warn( `WCAGAAT: Failed to save ${pref}` );

					if ( isReset ) {
						$resetLink
							.html(
								'<span class="wcagaat-btn-icon">✕</span>' +
								'<span class="wcagaat-status-text">' + wcagaat_assistant.text.reset_fail + '</span>'
							);

						setTimeout( function() {
							$resetLink
								.removeClass( 'is-saving' )
								.attr( 'aria-disabled', 'false' )
								.attr( 'aria-busy', 'false' )
								.html( originalHtml );
						}, 1500 );
					}

					return;
				}

				if ( isReset ) {
					$resetLink
						.html(
							'<span class="wcagaat-btn-icon">✓</span>' +
							'<span class="wcagaat-status-text">' + wcagaat_assistant.text.reset_success + '</span>'
						);

					setTimeout( function() {
						$resetLink
							.removeClass( 'is-saving' )
							.attr( 'aria-disabled', 'false' )
							.attr( 'aria-busy', 'false' )
							.html( originalHtml );
					}, 1500 );
				}
			},
			error: function() {
				if ( isReset ) {
					$resetLink
						.html(
							'<span class="wcagaat-btn-icon">✕</span>' +
							'<span class="wcagaat-status-text">' + wcagaat_assistant.text.reset_fail + '</span>'
						);

					setTimeout( function() {
						$resetLink
							.removeClass( 'is-saving' )
							.attr( 'aria-disabled', 'false' )
							.attr( 'aria-busy', 'false' )
							.html( originalHtml );
					}, 1500 );
				}
			}
		};
		$.ajax( args );
	}

} );
