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
		},
	};
};

export default resposiveVisibilityBlockAttributes;
