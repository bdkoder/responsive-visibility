import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import ConditionsPanel from './ConditionsPanel';

/**
 * Adds the "Visibility Conditions" panel to every block's Inspector. Thin wrapper — all the
 * UI lives in <ConditionsPanel>, which only knows the config object (easy to maintain/test).
 */
const withVisibilityConditionsControls = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		const { attributes, setAttributes } = props;

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<ConditionsPanel
						value={ attributes.rvConditions }
						onChange={ ( rvConditions ) =>
							setAttributes( { rvConditions } )
						}
					/>
				</InspectorControls>
			</>
		);
	},
	'withVisibilityConditionsControls'
);

export default withVisibilityConditionsControls;
