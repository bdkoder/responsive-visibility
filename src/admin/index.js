import { createRoot } from '@wordpress/element';
import App from './components/App';
import ErrorBoundary from './components/ErrorBoundary';
import './style.scss';

window.addEventListener( 'load', () => {
	const root = document.getElementById( 'rv-admin-root' );
	if ( root ) {
		createRoot( root ).render(
			<ErrorBoundary>
				<App />
			</ErrorBoundary>
		);
	}
} );
