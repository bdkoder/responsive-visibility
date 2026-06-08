/**
 * Elementor-style spectrum bar. Each valid segment grows proportionally to its pixel
 * span; the largest (uncapped) segment flex-fills and shows a ▶.
 *
 * @param {Object} props
 * @param {Array}  props.computed Sorted range entries from computeRanges().
 */
export default function RangeBar( { computed } ) {
	const scaleMax =
		computed.reduce(
			( max, entry ) =>
				entry.end !== null ? Math.max( max, entry.end ) : max,
			0
		) || 1024;

	return (
		<div className="rv-bp-bar">
			{ computed
				.filter( ( entry ) => ! entry.invalid )
				.map( ( entry ) => {
					const grow =
						entry.end === null
							? Math.round( scaleMax * 0.45 ) || 400
							: Math.max( 1, entry.end - entry.start );

					return (
						<div
							key={ entry.index }
							className="rv-bp-bar__seg"
							style={ {
								flex: `${ grow } 0 0`,
								background: entry.color,
							} }
						>
							<strong>
								{ entry.label }
								{ entry.end === null ? ' ▶' : '' }
							</strong>
							<span>{ entry.range }</span>
						</div>
					);
				} ) }
		</div>
	);
}
