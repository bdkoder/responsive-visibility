import {
	PanelBody,
	ToggleControl,
	SelectControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { newRule } from '../lib/conditions';
import ConditionRow from './ConditionRow';

const DEFAULT_CONFIG = {
	enable: false,
	action: 'show',
	relation: 'all',
	rules: [],
};

/**
 * The "Visibility Conditions" Inspector panel. Presentational: it receives the saved config
 * object and reports changes back, so it stays easy to reuse and test.
 *
 * @param {Object}   props
 * @param {Object}   props.value    The saved `rvConditions` object (may be partial).
 * @param {Function} props.onChange Receives the full next config object.
 */
const ConditionsPanel = ( { value, onChange } ) => {
	const config = { ...DEFAULT_CONFIG, ...( value || {} ) };
	const rules = Array.isArray( config.rules ) ? config.rules : [];

	const patch = ( next ) => onChange( { ...config, ...next } );

	const addRule = () => patch( { rules: [ ...rules, newRule() ] } );

	const updateRule = ( index, rulePatch ) =>
		patch( {
			rules: rules.map( ( rule, i ) =>
				i === index ? { ...rule, ...rulePatch } : rule
			),
		} );

	const removeRule = ( index ) =>
		patch( { rules: rules.filter( ( _, i ) => i !== index ) } );

	const relationHelp =
		'show' === config.action
			? __(
					'The block shows only when the conditions are met.',
					'responsive-visibility'
			  )
			: __(
					'The block is hidden when the conditions are met.',
					'responsive-visibility'
			  );

	return (
		<PanelBody
			title={ __( 'Visibility Conditions', 'responsive-visibility' ) }
			initialOpen={ false }
		>
			<ToggleControl
				label={ __( 'Enable conditions', 'responsive-visibility' ) }
				checked={ !! config.enable }
				onChange={ ( enable ) => patch( { enable } ) }
				__nextHasNoMarginBottom
			/>

			{ config.enable && (
				<>
					<SelectControl
						label={ __( 'Action', 'responsive-visibility' ) }
						value={ config.action }
						options={ [
							{
								label: __(
									'Show this block',
									'responsive-visibility'
								),
								value: 'show',
							},
							{
								label: __(
									'Hide this block',
									'responsive-visibility'
								),
								value: 'hide',
							},
						] }
						onChange={ ( action ) => patch( { action } ) }
						__nextHasNoMarginBottom
					/>

					<SelectControl
						label={ __( 'When', 'responsive-visibility' ) }
						value={ config.relation }
						options={ [
							{
								label: __(
									'All conditions match',
									'responsive-visibility'
								),
								value: 'all',
							},
							{
								label: __(
									'Any condition matches',
									'responsive-visibility'
								),
								value: 'any',
							},
						] }
						help={ relationHelp }
						onChange={ ( relation ) => patch( { relation } ) }
						__nextHasNoMarginBottom
					/>

					{ rules.map( ( rule, index ) => (
						<ConditionRow
							key={ rule.id || index }
							rule={ rule }
							onChange={ ( rulePatch ) =>
								updateRule( index, rulePatch )
							}
							onRemove={ () => removeRule( index ) }
						/>
					) ) }

					<Button variant="secondary" onClick={ addRule }>
						{ __( '+ Add condition', 'responsive-visibility' ) }
					</Button>
				</>
			) }
		</PanelBody>
	);
};

export default ConditionsPanel;
