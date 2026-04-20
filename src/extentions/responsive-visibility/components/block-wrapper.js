import { createHigherOrderComponent } from "@wordpress/compose";
import classNames from "classnames";
import { useSelect } from "@wordpress/data";

function rvClassForSlug( slug ) {
	const legacyMap = {
		mobile:  "mobile-hidden",
		tablet:  "tablet-hidden",
		desktop: "desktop-hidden",
	};
	return legacyMap[ slug ] || `rv-hidden--${ slug }`;
}

// Maps default slugs to their WordPress editor device type string.
const DEVICE_TYPE_MAP = {
	desktop: "Desktop",
	tablet:  "Tablet",
	mobile:  "Mobile",
};

const withBlockWrapperClass = createHigherOrderComponent( ( BlockListBlock ) => {
	return ( props ) => {
		const { attributes } = props;
		const { deviceType } = useSelect( ( select ) => {
			return {
				deviceType: select( "core/editor" ).getDeviceType(),
			};
		}, [] );

		const classObj = {};

		// New system: hiddenBreakpoints array.
		( attributes?.hiddenBreakpoints || [] ).forEach( ( slug ) => {
			const cssClass       = rvClassForSlug( slug );
			const expectedDevice = DEVICE_TYPE_MAP[ slug ];
			if ( expectedDevice ) {
				// Standard breakpoint — show stripe only in matching device preview.
				classObj[ cssClass ] = deviceType === expectedDevice;
			} else {
				// Custom breakpoint — no matching device type, always show stripe.
				classObj[ cssClass ] = true;
			}
		} );

		// Legacy: old boolean attributes for backward compatibility.
		if ( attributes?.hideOnDesktop ) {
			classObj[ "desktop-hidden" ] =
				classObj[ "desktop-hidden" ] || deviceType === "Desktop";
		}
		if ( attributes?.hideOnTablet ) {
			classObj[ "tablet-hidden" ] =
				classObj[ "tablet-hidden" ] || deviceType === "Tablet";
		}
		if ( attributes?.hideOnMobile ) {
			classObj[ "mobile-hidden" ] =
				classObj[ "mobile-hidden" ] || deviceType === "Mobile";
		}

		const wrapperProps = {
			...props.wrapperProps,
			className: classNames( props?.wrapperProps?.className, classObj ),
		};
		return <BlockListBlock { ...props } wrapperProps={ wrapperProps } />;
	};
}, "withBlockWrapperClass" );

export default withBlockWrapperClass;
