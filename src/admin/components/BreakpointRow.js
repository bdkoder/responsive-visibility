import { TextControl, Button } from '@wordpress/components';

/**
 * One breakpoint row: label, max-width, the computed "Hidden when" range, and remove.
 * Fully controlled by the `row` prop; all changes bubble up via onChange/onRemove.
 *
 * @param {Object}   props
 * @param {Object}   props.row       Row state { slug, label, maxWidth }.
 * @param {Object}   props.range     Computed range entry for this row (may be undefined).
 * @param {Object}   props.strings   Localized strings.
 * @param {boolean}  props.canRemove Whether removal is allowed.
 * @param {Function} props.onChange  Receives a partial patch for the row.
 * @param {Function} props.onRemove  Removes this row.
 */
export default function BreakpointRow( {
	row,
	range,
	strings,
	canRemove,
	onChange,
	onRemove,
} ) {
	const invalid = !! ( range && range.invalid );

	return (
		<tr className="rv-admin__row">
			<td>
				<TextControl
					label={ strings.label }
					hideLabelFromVision
					value={ row.label }
					placeholder={ strings.labelPlaceholder }
					onChange={ ( label ) => onChange( { label } ) }
					__nextHasNoMarginBottom
				/>
			</td>
			<td>
				<TextControl
					label={ strings.maxWidth }
					hideLabelFromVision
					type="number"
					min="1"
					max="99999"
					placeholder="∞"
					value={ row.maxWidth }
					onChange={ ( maxWidth ) => onChange( { maxWidth } ) }
					__nextHasNoMarginBottom
				/>
			</td>
			<td className="rv-admin__range">
				{ range && (
					<span
						className={
							invalid
								? 'rv-range-text is-invalid'
								: 'rv-range-text'
						}
					>
						{ range.range }
					</span>
				) }
				{ range && range.largest && (
					<span
						className="rv-largest-badge"
						style={ { background: range.color } }
					>
						{ strings.largest }
					</span>
				) }
			</td>
			<td className="rv-admin__actions-cell">
				<Button
					variant="secondary"
					isDestructive
					disabled={ ! canRemove }
					onClick={ onRemove }
				>
					{ `✕ ${ strings.remove }` }
				</Button>
			</td>
		</tr>
	);
}
