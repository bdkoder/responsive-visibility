import { Component } from '@wordpress/element';

const strings = ( window.rvAdmin && window.rvAdmin.strings ) || {};

/**
 * Catches any render-time exception so a bug can never leave the settings page
 * blank — it shows a recoverable notice instead. Saved data is never touched.
 */
export default class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = { hasError: false };
	}

	static getDerivedStateFromError() {
		return { hasError: true };
	}

	componentDidCatch( error ) {
		// eslint-disable-next-line no-console
		console.error( 'Responsive Visibility admin error:', error );
	}

	render() {
		if ( this.state.hasError ) {
			return (
				<div className="notice notice-error rv-admin__boundary">
					<p>
						{ strings.error ||
							'Something went wrong loading this screen. Please reload the page.' }
					</p>
				</div>
			);
		}
		return this.props.children;
	}
}
