/**
 * Read access to the condition schema that PHP localizes as `window.rvConditions`
 * (see Conditions::js_registry) plus small pure helpers for the editor UI.
 *
 * PHP is the single source of truth for which conditions exist and how each value control
 * looks; this file never hardcodes a condition. Add a condition in PHP and it appears here.
 */

const registry = window.rvConditions || { groups: {}, types: [] };

let ruleSequence = 0;

/**
 * { label, value } options for the condition-type dropdown, in PHP-defined order.
 */
export const typeOptions = registry.types.map( ( type ) => ( {
	label: type.label,
	value: type.type,
} ) );

/**
 * Full schema for one condition type, or null if unknown.
 *
 * @param {string} type Condition type slug.
 */
export const getConditionType = ( type ) =>
	registry.types.find( ( item ) => item.type === type ) || null;

/**
 * The value-control schema for a type ({ control, options, … }), or null when the
 * condition needs no value.
 *
 * @param {string} type Condition type slug.
 */
export const valueFieldFor = ( type ) => {
	const found = getConditionType( type );
	return found ? found.value : null;
};

/**
 * The default value for a freshly-selected condition type.
 *
 * @param {string} type Condition type slug.
 */
export const defaultValueFor = ( type ) => {
	const field = valueFieldFor( type );
	return field && field.default !== undefined ? field.default : '';
};

/**
 * Build a new, ready-to-edit rule using the first available condition type.
 */
export const newRule = () => {
	const firstType = registry.types[ 0 ] ? registry.types[ 0 ].type : '';
	return {
		id: `rule-${ ++ruleSequence }`,
		type: firstType,
		operator: 'is',
		value: defaultValueFor( firstType ),
	};
};
