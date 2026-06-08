/**
 * Extends @wordpress/scripts' default webpack config to add the React admin entry
 * (src/admin/index.js -> build/admin/index.js) alongside the auto-detected block
 * entries. Everything else stays default.
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

const baseEntry =
	typeof defaultConfig.entry === 'function' ? defaultConfig.entry() : defaultConfig.entry;

module.exports = {
	...defaultConfig,
	entry: {
		...baseEntry,
		'admin/index': path.resolve( __dirname, 'src/admin/index.js' ),
	},
};
