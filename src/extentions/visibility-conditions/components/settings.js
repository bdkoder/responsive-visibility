import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

const withVisibilityConditionsControls = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			const { attributes, setAttributes } = props;
			const loginVisibility = attributes?.loginVisibility || '';

			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody
							title={ __(
								'Visibility Conditions',
								'responsive-visibility'
							) }
							initialOpen={ false }
						>
							<SelectControl
								label={ __(
									'Show this block to',
									'responsive-visibility'
								) }
								value={ loginVisibility }
								options={ [
									{
										label: __(
											'Everyone',
											'responsive-visibility'
										),
										value: '',
									},
									{
										label: __(
											'Logged-in users only',
											'responsive-visibility'
										),
										value: 'logged-in',
									},
									{
										label: __(
											'Logged-out visitors only',
											'responsive-visibility'
										),
										value: 'logged-out',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { loginVisibility: value } )
								}
								__nextHasNoMarginBottom
							/>
							{ loginVisibility && (
								<p
									style={ {
										margin: '4px 0 0',
										fontSize: '12px',
										color: '#757575',
									} }
								>
									{ sprintf(
										/* translators: %s: audience that will NOT see the block, e.g. logged-out visitors */
										__(
											'This block is removed on the front end for %s.',
											'responsive-visibility'
										),
										loginVisibility === 'logged-in'
											? __(
													'logged-out visitors',
													'responsive-visibility'
											  )
											: __(
													'logged-in users',
													'responsive-visibility'
											  )
									) }
								</p>
							) }
						</PanelBody>
					</InspectorControls>
				</>
			);
		};
	},
	'withVisibilityConditionsControls'
);

export default withVisibilityConditionsControls;
