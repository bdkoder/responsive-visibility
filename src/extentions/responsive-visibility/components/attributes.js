const resposiveVisibilityBlockAttributes = (settings, name) => {
	return {
		...settings,
		attributes: {
			...settings.attributes,
			hideOnDesktop: {
				type: "boolean",
				default: false,
			},
			hideOnTablet: {
				type: "boolean",
				default: false,
			},
			hideOnMobile: {
				type: "boolean",
				default: false,
			},
			hiddenBreakpoints: {
				type: "array",
				default: [],
				items: {
					type: "string",
				},
			},
		},
	};
};

export default resposiveVisibilityBlockAttributes;
