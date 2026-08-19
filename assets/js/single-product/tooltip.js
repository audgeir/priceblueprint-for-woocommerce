/**
 * Binds accessible show/hide behavior for attribute help tooltips.
 *
 * Desktop: shows on hover/focus of the trigger, hides on mouseleave/blur.
 * Mobile (no hover): shows/hides on tap of the trigger; a tap outside the
 * trigger or tooltip also hides it. Escape hides whichever tooltip is
 * currently open, regardless of how it was opened.
 *
 * @param {HTMLElement} configurator
 */
export function bindTooltips( configurator ) {
	const pairs = [];

	configurator.querySelectorAll( '.prbp-tooltip-trigger' ).forEach( function ( trigger ) {
		const tooltip = trigger.nextElementSibling;
		if ( ! tooltip || ! tooltip.classList.contains( 'prbp-tooltip-content' ) ) {
			return;
		}

		let openedByFocus = false;

		function show() {
			tooltip.classList.add( 'prbp-tooltip-content--visible' );
		}

		function hide() {
			tooltip.classList.remove( 'prbp-tooltip-content--visible' );
			openedByFocus = false;
		}

		function isVisible() {
			return tooltip.classList.contains( 'prbp-tooltip-content--visible' );
		}

		trigger.addEventListener( 'mouseenter', show );
		trigger.addEventListener( 'mouseleave', hide );

		trigger.addEventListener( 'focus', function () {
			if ( ! isVisible() ) {
				openedByFocus = true;
			}
			show();
		} );

		trigger.addEventListener( 'blur', hide );

		// Tap-to-toggle for touch devices, where mouseenter never fires.
		// A click on a not-yet-focused trigger is preceded by a browser-fired
		// focus event in the same interaction (mousedown -> focus -> mouseup ->
		// click), which already opened the tooltip via the focus handler above.
		// Without the openedByFocus guard, this handler would immediately
		// toggle that same tooltip back closed on every first tap/click.
		trigger.addEventListener( 'click', function () {
			if ( openedByFocus ) {
				openedByFocus = false;
				return;
			}
			if ( isVisible() ) {
				hide();
			} else {
				show();
			}
		} );

		pairs.push( { trigger, tooltip, hide, isVisible } );
	} );

	if ( pairs.length === 0 ) {
		return;
	}

	// Single delegated listener: closes every open tooltip except the one
	// whose trigger or content was just clicked (that pair's own handlers
	// above already decide its own next state).
	document.addEventListener( 'click', function ( event ) {
		pairs.forEach( function ( pair ) {
			if ( ! pair.trigger.contains( event.target ) && ! pair.tooltip.contains( event.target ) ) {
				pair.hide();
			}
		} );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			pairs.forEach( function ( pair ) {
				if ( pair.isVisible() ) {
					pair.hide();
				}
			} );
		}
	} );
}
