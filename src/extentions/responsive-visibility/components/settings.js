import { createHigherOrderComponent } from "@wordpress/compose";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

const withResponsiveVisibilityControls = createHigherOrderComponent(
	(BlockEdit) => {
		return (props) => {
			const { attributes, setAttributes } = props;
			return (
				<>
					<BlockEdit {...props} />
					<InspectorControls>
						<PanelBody title={__("Responsive Visibility")}>
							<ToggleControl
								label={__("Hide on Desktop")}
								checked={attributes?.hideOnDesktop}
								onChange={(newValue) => {
									setAttributes({ hideOnDesktop: newValue });
								}}
							/>
							<ToggleControl
								label={__("Hide on Tablet")}
								checked={attributes?.hideOnTablet}
								onChange={(newValue) => {
									setAttributes({ hideOnTablet: newValue });
								}}
							/>
							<ToggleControl
								label={__("Hide on Mobile")}
								checked={attributes?.hideOnMobile}
								onChange={(newValue) => {
									setAttributes({ hideOnMobile: newValue });
								}}
							/>
						</PanelBody>
					</InspectorControls>
				</>
			);
		};
	},
	"withResponsiveVisibilityControls",
);

export default withResponsiveVisibilityControls;
