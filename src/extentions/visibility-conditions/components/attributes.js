// Condition attributes — kept separate from device/breakpoint visibility.
// loginVisibility: "" (everyone) | "logged-in" | "logged-out". Evaluated server-side
// in Render::render_block(). Future Tier-1 conditions (role, post, …) add their attrs here.
const addVisibilityConditionAttributes = ( settings ) => {
	return {
		...settings,
		attributes: {
			...settings.attributes,
			loginVisibility: {
				type: 'string',
				default: '',
			},
		},
	};
};

export default addVisibilityConditionAttributes;
