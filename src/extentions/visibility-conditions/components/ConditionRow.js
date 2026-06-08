import { SelectControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { typeOptions, valueFieldFor, defaultValueFor } from '../lib/conditions';
import ConditionValue from './ConditionValue';

const operatorOptions = [
	{ label: __( 'is', 'responsive-visibility' ), value: 'is' },
	{ label: __( 'is not', 'responsive-visibility' ), value: 'is_not' },
];

/**
 * One condition rule: pick a condition type, an is/is-not operator, and a value.
 *
 * @param {Object}   props
 * @param {Object}   props.rule     The rule ({ id, type, operator, value }).
 * @param {Function} props.onChange Receives a partial patch to merge into the rule.
 * @param {Function} props.onRemove Removes this rule.
 */
const ConditionRow = ( { rule, onChange, onRemove } ) => {
	const field = valueFieldFor( rule.type );

	// Switching condition type resets the value to that type's default.
	const changeType = ( type ) =>
		onChange( { type, value: defaultValueFor( type ) } );

	return (
		<div className="rv-condition-row">
			<SelectControl
				label={ __( 'Condition', 'responsive-visibility' ) }
				hideLabelFromVision
				value={ rule.type }
				options={ typeOptions }
				onChange={ changeType }
				__nextHasNoMarginBottom
			/>
			<SelectControl
				label={ __( 'Operator', 'responsive-visibility' ) }
				hideLabelFromVision
				value={ rule.operator }
				options={ operatorOptions }
				onChange={ ( operator ) => onChange( { operator } ) }
				__nextHasNoMarginBottom
			/>
			<ConditionValue
				field={ field }
				value={ rule.value }
				onChange={ ( value ) => onChange( { value } ) }
			/>
			<div className="rv-condition-row__remove">
				<Button variant="link" isDestructive onClick={ onRemove }>
					{ __( 'Remove', 'responsive-visibility' ) }
				</Button>
			</div>
		</div>
	);
};

export default ConditionRow;
