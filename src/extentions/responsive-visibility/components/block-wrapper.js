import { createHigherOrderComponent } from "@wordpress/compose";
import classNames from "classnames";
import { useSelect } from "@wordpress/data";

const withBlockWrapperClass = createHigherOrderComponent((BlockListBlock) => {
	return (props) => {
		const { attributes } = props;
		const { deviceType } = useSelect((select) => {
			return {
				deviceType: select("core/editor").getDeviceType(),
			};
		}, []);
		const wrapperProps = {
			...props.wrapperProps,
			className: classNames(props?.wrapperProps?.className, {
				"desktop-hidden": attributes?.hideOnDesktop && deviceType === "Desktop",
				"tablet-hidden": attributes?.hideOnTablet && deviceType === "Tablet",
				"mobile-hidden": attributes?.hideOnMobile && deviceType === "Mobile",
			}),
		};
		return <BlockListBlock {...props} wrapperProps={wrapperProps} />;
	};
}, "withBlockWrapperClass");

export default withBlockWrapperClass;
