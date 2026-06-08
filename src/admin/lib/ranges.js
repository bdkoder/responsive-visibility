/**
 * Pure range calculator — the JS mirror of PHP Breakpoints::dynamic_css().
 * Shared by RangeBar and the per-row "Hidden when" hint. Keep in lockstep with PHP:
 * first -> "≤ max", middle -> "min – max", last (count > 1) -> "≥ min",
 * a lone non-last blank -> invalid.
 */

export const PALETTE = [
	'#2271b1',
	'#3a9b7a',
	'#c1842d',
	'#8c5bd0',
	'#c0506a',
	'#4a8db5',
];

const toMax = ( value ) => {
	if ( value === null || value === undefined || value === '' ) {
		return null;
	}
	const parsed = Number( value );
	return Number.isNaN( parsed ) ? null : parsed;
};

/**
 * @param {Array}  rows    Rows of { label, maxWidth }.
 * @param {Object} strings Localized strings (untitled, needMax).
 * @return {Array} Sorted entries with { index, label, color, range, start, end, invalid, largest }.
 */
export function computeRanges( rows, strings = {} ) {
	const entries = rows.map( ( row, index ) => ( {
		index,
		label: ( row.label || '' ).trim() || strings.untitled || 'Untitled',
		max: toMax( row.maxWidth ),
	} ) );

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

	sorted.forEach( ( entry, i ) => {
		entry.color = PALETTE[ i % PALETTE.length ];
		entry.invalid = false;
		entry.largest = false;

		if ( i === count - 1 && count > 1 ) {
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
}
