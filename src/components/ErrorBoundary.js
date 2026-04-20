/**
 * ErrorBoundary Component
 *
 * Catches errors in React components and displays a user-friendly error UI
 * instead of hardcoded HTML error messages.
 *
 * @package
 * @since 1.0.0
 */

import { Component } from '@wordpress/element';

/**
 * ErrorBoundary Component for Settings
 *
 * Catches errors in the settings app and displays a user-friendly error UI
 * instead of hardcoded HTML error messages.
 */
class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			hasError: false,
			error: null,
		};
	}

	static getDerivedStateFromError( error ) {
		// Update state so the next render will show the fallback UI
		return {
			hasError: true,
			error,
		};
	}

	componentDidCatch( error, errorInfo ) {
		// Log error to console for debugging
		console.error( 'MeowSEO Error Boundary:', error, errorInfo );
	}

	handleReload = () => {
		window.location.reload();
	};

	handleReset = () => {
		// Clear any potentially corrupted state
		this.setState( {
			hasError: false,
			error: null,
		} );
	};

	render() {
		if ( this.state.hasError ) {
			// Display user-friendly error UI with recovery options
			return (
				<div className="notice notice-error meowseo-error-boundary">
					<h2>MeowSEO Error</h2>
					<p>
						An error occurred and the interface could not load
						properly.
					</p>
					<p>
						<strong>Error details:</strong>{ ' ' }
						{ this.state.error?.message || 'Unknown error' }
					</p>
					<div className="meowseo-error-actions">
						<button
							className="button button-primary"
							onClick={ this.handleReload }
						>
							Reload Page
						</button>
						<button
							className="button button-secondary"
							onClick={ this.handleReset }
							style={ { marginLeft: '10px' } }
						>
							Try Again
						</button>
					</div>
					<details style={ { marginTop: '20px' } }>
						<summary>Technical Details (for debugging)</summary>
						<pre
							style={ {
								background: '#f5f5f5',
								padding: '10px',
								overflow: 'auto',
								fontSize: '12px',
							} }
						>
							{ this.state.error?.stack ||
								'No stack trace available' }
						</pre>
					</details>
				</div>
			);
		}

		return this.props.children;
	}
}

export default ErrorBoundary;
