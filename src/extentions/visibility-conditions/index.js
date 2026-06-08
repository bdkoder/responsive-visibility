import { addFilter } from '@wordpress/hooks';
import addVisibilityConditionAttributes from './components/attributes';
import withVisibilityConditionsControls from './components/settings';

// Add condition attributes to every block.
addFilter(
	'blocks.registerBlockType',
	'responsive-visibility/visibility-conditions/with-attributes',
	addVisibilityConditionAttributes
);

// Add the "Visibility Conditions" Inspector panel.
addFilter(
	'editor.BlockEdit',
	'responsive-visibility/visibility-conditions/with-inspector-controls',
	withVisibilityConditionsControls
);
