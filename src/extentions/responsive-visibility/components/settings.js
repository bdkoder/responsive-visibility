import { createHigherOrderComponent } from "@wordpress/compose";
import { InspectorControls } from "@wordpress/block-editor";
import { PanelBody, ToggleControl, ExternalLink } from "@wordpress/components";
import { __, sprintf } from "@wordpress/i18n";
import { useEffect } from "@wordpress/element";

const DEFAULT_BREAKPOINTS = [
	{ slug: "mobile",  label: "Mobile",  max_width: 767  },
	{ slug: "tablet",  label: "Tablet",  max_width: 1024 },
	{ slug: "desktop", label: "Desktop", max_width: null },
];

const withResponsiveVisibilityControls = createHigherOrderComponent(
	(BlockEdit) => {
		return (props) => {
			const { attributes, setAttributes } = props;

			const breakpoints =
				window?.rvBreakpoints?.breakpoints || DEFAULT_BREAKPOINTS;
			const settingsUrl = window?.rvBreakpoints?.settingsUrl || "";

			// Migrate old boolean attrs to hiddenBreakpoints on first block open.
			useEffect(() => {
				if (
					( ! attributes.hiddenBreakpoints ||
						attributes.hiddenBreakpoints.length === 0 ) &&
					( attributes.hideOnDesktop ||
						attributes.hideOnTablet ||
						attributes.hideOnMobile )
				) {
					const migrated = [];
					if ( attributes.hideOnDesktop ) migrated.push( "desktop" );
					if ( attributes.hideOnTablet )  migrated.push( "tablet" );
					if ( attributes.hideOnMobile )  migrated.push( "mobile" );
					setAttributes( { hiddenBreakpoints: migrated } );
				}
			}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

			const hiddenBreakpoints = attributes?.hiddenBreakpoints || [];

			const toggleBreakpoint = ( slug, isHidden ) => {
				if ( isHidden ) {
					setAttributes( {
						hiddenBreakpoints: [ ...hiddenBreakpoints, slug ],
					} );
				} else {
					setAttributes( {
						hiddenBreakpoints: hiddenBreakpoints.filter(
							( s ) => s !== slug
						),
					} );
				}
			};

			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody
							title={ __( "Responsive Visibility", "responsive-visibility" ) }
						>
							{ breakpoints.map( ( bp ) => (
								<ToggleControl
									key={ bp.slug }
									label={ sprintf(
										/* translators: %s: device label e.g. Mobile */
										__( "Hide on %s", "responsive-visibility" ),
										bp.label
									) }
									checked={ hiddenBreakpoints.includes( bp.slug ) }
									onChange={ ( value ) =>
										toggleBreakpoint( bp.slug, value )
									}
								/>
							) ) }
							{ settingsUrl && (
								<div
									style={ {
										marginTop: "12px",
										paddingTop: "12px",
										borderTop: "1px solid #e0e0e0",
									} }
								>
									<ExternalLink href={ settingsUrl }>
										{ __(
											"⚙ Customize breakpoints",
											"responsive-visibility"
										) }
									</ExternalLink>
									<p
										style={ {
											margin: "4px 0 0",
											fontSize: "12px",
											color: "#757575",
										} }
									>
										{ __(
											"Set custom pixel values for each device in plugin settings.",
											"responsive-visibility"
										) }
									</p>
								</div>
							) }
						</PanelBody>
					</InspectorControls>
				</>
			);
		};
	},
	"withResponsiveVisibilityControls",
);

export default withResponsiveVisibilityControls;
