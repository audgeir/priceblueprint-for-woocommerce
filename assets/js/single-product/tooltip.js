/**
 * Binds accessible show/hide behavior for attribute help tooltips.
 *
 * Desktop: shows on hover/focus of the trigger, hides on mouseleave/blur.
 * Mobile (no hover): shows/hides on tap of the trigger; a tap outside the
 * trigger or tooltip also hides it. Escape hides it from any state.
 *
 * @param {HTMLElement} configurator
 */
export function bindTooltips( configurator ) {
	configurator.querySelectorAll( '.prbp-tooltip-trigger' ).forEach( function ( trigger ) {
		const tooltip = trigger.nextElementSibling;
		if ( ! tooltip || ! tooltip.classList.contains( 'prbp-tooltip-content' ) ) {
			return;
		}

		function show() {
			tooltip.classList.add( 'prbp-tooltip-content--visible' );
		}

		function hide() {
			tooltip.classList.remove( 'prbp-tooltip-content--visible' );
		}

		trigger.addEventListener( 'mouseenter', show );
		trigger.addEventListener( 'mouseleave', hide );
		trigger.addEventListener( 'focus', show );
		trigger.addEventListener( 'blur', hide );

		// Tap-to-toggle for touch devices, where mouseenter never fires.
		// stopPropagation keeps this same click from also being seen by the
		// document-level outside-click listener below, which would otherwise
		// hide the tooltip on the same tap that just opened it.
		trigger.addEventListener( 'click', function ( event ) {
			event.stopPropagation();
			if ( tooltip.classList.contains( 'prbp-tooltip-content--visible' ) ) {
				hide();
			} else {
				show();
			}
		} );

		document.addEventListener( 'click', function ( event ) {
			if ( ! trigger.contains( event.target ) && ! tooltip.contains( event.target ) ) {
				hide();
			}
		} );

		trigger.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key ) {
				hide();
			}
		} );
	} );
}
