/**
 * Responsive Visibility — Breakpoints settings page.
 *
 * Source for assets/js/settings.{js,min.js} (built by scripts/build-admin.mjs).
 * Enqueued only on Settings → Responsive Visibility. Localized strings arrive on
 * window.rvSettings (wp_localize_script). Static styling lives in settings.css;
 * only computed values (segment flex-grow + colour) are written inline here.
 */
( () => {
	'use strict';

	const strings = window.rvSettings || {};

	// Segment colours, cycled by sorted index. Bar segment and its row badge share
	// the same index so they read as one unit.
	const palette = [ '#2271b1', '#3a9b7a', '#c1842d', '#8c5bd0', '#c0506a', '#4a8db5' ];

	const elements = {
		form: document.getElementById( 'rv-settings-form' ),
		tableBody: document.getElementById( 'rv-breakpoints-body' ),
		addButton: document.getElementById( 'rv-add-breakpoint' ),
		rangeBar: document.getElementById( 'rv-range-bar' ),
	};

	// Bail out unless we are actually on the settings page.
	if ( ! elements.form || ! elements.tableBody || ! elements.addButton || ! elements.rangeBar ) {
		return;
	}

	const escapeHtml = ( value ) => {
		const div = document.createElement( 'div' );
		div.textContent = value;
		return div.innerHTML;
	};

	const colorAt = ( index ) => palette[ index % palette.length ];

	const controller = {
		rows() {
			return [ ...elements.tableBody.querySelectorAll( '.rv-breakpoint-row' ) ];
		},

		toggleRemoveButtons() {
			const disabled = controller.rows().length <= 1;
			elements.tableBody
				.querySelectorAll( '.rv-remove-row' )
				.forEach( ( button ) => {
					button.disabled = disabled;
				} );
		},

		// Read each row into a plain object.
		readRows() {
			return controller.rows().map( ( row ) => {
				const maxInput = row.querySelector( 'input[name="rv_max_width[]"]' );
				const label = row.querySelector( 'input[name="rv_label[]"]' ).value.trim();
				const raw = maxInput.value.trim();
				const parsed = parseInt( raw, 10 );

				return {
					row,
					maxInput,
					label: label || strings.untitled || 'Untitled',
					max: raw === '' || Number.isNaN( parsed ) ? null : parsed,
				};
			} );
		},

		// Sort by max-width ascending (blanks last) and assign each entry the media-query
		// range it will produce. Mirrors PHP Breakpoints::dynamic_css() exactly:
		// first -> "≤ max", middle -> "min – max", last (count > 1) -> "≥ min".
		computeRanges( entries ) {
			const sorted = [ ...entries ].sort( ( a, b ) => {
				if ( a.max === null && b.max === null ) {
					return 0;
				}
				if ( a.max === null ) {
					return 1;
				}
				if ( b.max === null ) {
					return -1;
				}
				return a.max - b.max;
			} );

			const count = sorted.length;
			let prevMin = 0;

			sorted.forEach( ( entry, index ) => {
				const isLast = index === count - 1;
				entry.invalid = false;
				entry.largest = false;

				if ( isLast && count > 1 ) {
					entry.start = prevMin;
					entry.end = null;
					entry.range = `≥ ${ prevMin }px`;
					entry.largest = true;
				} else if ( entry.max === null ) {
					entry.start = prevMin;
					entry.end = null;
					entry.range = strings.needMax || 'set a max-width';
					entry.invalid = true;
				} else if ( prevMin === 0 ) {
					entry.start = 0;
					entry.end = entry.max;
					entry.range = `≤ ${ entry.max }px`;
					prevMin = entry.max + 1;
				} else {
					entry.start = prevMin;
					entry.end = entry.max;
					entry.range = `${ prevMin } – ${ entry.max }px`;
					prevMin = entry.max + 1;
				}
			} );

			return sorted;
		},

		// Write the "Hidden when" cell of each row + flag invalid blanks.
		renderCells( sorted ) {
			sorted.forEach( ( entry, index ) => {
				const cell = entry.row.querySelector( '.rv-range-cell' );
				const badge = entry.largest
					? `<span class="rv-largest-badge" style="background:${ colorAt( index ) };">${ escapeHtml( strings.largest || '∞ Largest device' ) }</span>`
					: '';

				cell.innerHTML = `<span class="rv-range-text">${ escapeHtml( entry.range ) }</span>${ badge }`;
				cell.classList.toggle( 'is-invalid', entry.invalid );
				entry.maxInput.classList.toggle( 'is-invalid', entry.invalid );
				entry.maxInput.title = entry.invalid ? strings.multiBlank || '' : '';
			} );
		},

		// Rebuild the Elementor-style spectrum bar.
		renderBar( sorted ) {
			const scaleMax =
				sorted.reduce( ( max, entry ) => ( entry.end !== null ? Math.max( max, entry.end ) : max ), 0 ) || 1024;

			elements.rangeBar.innerHTML = '';

			sorted.forEach( ( entry, index ) => {
				if ( entry.invalid ) {
					return;
				}

				const grow =
					entry.end === null
						? Math.round( scaleMax * 0.45 ) || 400
						: Math.max( 1, entry.end - entry.start );
				const arrow = entry.end === null ? ' ▶' : '';

				const segment = document.createElement( 'div' );
				segment.className = 'rv-bp-bar__seg';
				segment.style.flex = `${ grow } 0 0`;
				segment.style.background = colorAt( index );
				segment.innerHTML = `<strong>${ escapeHtml( entry.label ) }${ arrow }</strong><span>${ escapeHtml( entry.range ) }</span>`;
				elements.rangeBar.appendChild( segment );
			} );
		},

		// Recompute ranges, then repaint cells + bar.
		refresh() {
			const sorted = controller.computeRanges( controller.readRows() );
			controller.renderCells( sorted );
			controller.renderBar( sorted );
		},

		addRow() {
			const row = document.createElement( 'tr' );
			row.className = 'rv-breakpoint-row';
			row.innerHTML = [
				`<td><input type="hidden" name="rv_slug[]" value="" /><input type="text" name="rv_label[]" class="regular-text" required placeholder="${ escapeHtml( strings.labelPH || '' ) }" /></td>`,
				'<td><input type="number" name="rv_max_width[]" class="small-text rv-max-width" min="1" max="99999" placeholder="∞" value="1400" /> <span class="rv-unit">px</span></td>',
				'<td class="rv-range-cell"></td>',
				`<td><button type="button" class="button rv-remove-row">${ escapeHtml( strings.removeTxt || 'Remove' ) }</button></td>`,
			].join( '' );

			elements.tableBody.appendChild( row );
			controller.toggleRemoveButtons();
			controller.refresh();
			row.querySelector( 'input[type="text"]' ).focus();
		},

		removeRow( target ) {
			if ( controller.rows().length <= 1 ) {
				return;
			}
			target.closest( 'tr' ).remove();
			controller.toggleRemoveButtons();
			controller.refresh();
		},

		confirmReset( event ) {
			if ( ! window.confirm( strings.resetConfirm || 'Reset to defaults?' ) ) {
				event.preventDefault();
			}
		},

		// Block save (but not reset) when more than one max-width is left blank.
		guardBlankSave( event ) {
			if ( event.submitter && event.submitter.name === 'rv_reset_breakpoints' ) {
				return;
			}

			const blanks = [ ...elements.tableBody.querySelectorAll( 'input[name="rv_max_width[]"]' ) ].filter(
				( input ) => input.value.trim() === ''
			);

			if ( blanks.length > 1 ) {
				event.preventDefault();
				window.alert( strings.multiBlank || '' );
				blanks[ 1 ].focus();
			}
		},

		bindEvents() {
			elements.addButton.addEventListener( 'click', controller.addRow );
			elements.tableBody.addEventListener( 'input', controller.refresh );
			elements.tableBody.addEventListener( 'click', ( event ) => {
				if ( event.target.classList.contains( 'rv-remove-row' ) ) {
					controller.removeRow( event.target );
				}
			} );
			elements.form.addEventListener( 'submit', controller.guardBlankSave );

			const resetButton = elements.form.querySelector( '[name="rv_reset_breakpoints"]' );
			if ( resetButton ) {
				resetButton.addEventListener( 'click', controller.confirmReset );
			}
		},

		init() {
			controller.bindEvents();
			controller.toggleRemoveButtons();
			controller.refresh();
		},
	};

	controller.init();
} )();
