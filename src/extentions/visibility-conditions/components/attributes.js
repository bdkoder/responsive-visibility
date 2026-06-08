/**
 * Condition attributes — kept separate from device/breakpoint visibility.
 *
 * `rvConditions` holds the whole conditions config for a block and is evaluated server-side
 * in Conditions::should_hide(). Empty/disabled by default, so existing blocks are untouched.
 *
 *   rvConditions = {
 *     enable:   boolean,
 *     action:   'show' | 'hide',   // show/hide the block WHEN the rules are met
 *     relation: 'all' | 'any',
 *     rules:    [ { id, type, operator: 'is' | 'is_not', value } ],
 *   }
 *
 * @param {Object} settings Block settings being filtered.
 * @return {Object} Settings with the condition attribute added.
 */
const addVisibilityConditionAttributes = ( settings ) => {
	return {
		...settings,
		attributes: {
			...settings.attributes,
			rvConditions: {
				type: 'object',
				default: {
					enable: false,
					action: 'show',
					relation: 'all',
					rules: [],
				},
			},
		},
	};
};

export default addVisibilityConditionAttributes;
