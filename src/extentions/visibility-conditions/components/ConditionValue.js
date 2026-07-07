import { SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Renders the value control for a condition, driven entirely by the PHP-provided schema
 * ({ control: 'select' | 'number' | 'text', options?, placeholder?, default? }).
 *
 * Uses only stable @wordpress/components (no experimental NumberControl) so it survives
 * Gutenberg upgrades. A new condition type needs no change here — it just supplies a schema.
 *
 * @param {Object}   props
 * @param {Object}   props.field    Value-field schema, or null when no value is needed.
 * @param {string}   props.value    Current value.
 * @param {Function} props.onChange Receives the next value.
 */
const ConditionValue = ( { field, value, onChange } ) => {
	if ( ! field ) {
		return null;
	}

	if ( 'select' === field.control ) {
		// Ensure an explicit empty choice exists when nothing is selected yet.
		const hasEmpty = field.options.some(
			( option ) => '' === option.value
		);
		const options = hasEmpty
			? field.options
			: [
					{
						label: __( 'Select…', 'responsive-visibility' ),
						value: '',
					},
					...field.options,
			  ];

		return (
			<SelectControl
				label={ __( 'Value', 'responsive-visibility' ) }
				hideLabelFromVision
				value={ value }
				options={ options }
				help={ field.help || '' }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	return (
		<TextControl
			label={ __( 'Value', 'responsive-visibility' ) }
			hideLabelFromVision
			type={ 'number' === field.control ? 'number' : 'text' }
			value={ value }
			placeholder={ field.placeholder || '' }
			help={ field.help || '' }
			onChange={ onChange }
			__nextHasNoMarginBottom
		/>
	);
};

export default ConditionValue;
