/**
 * MeowSEO Admin Settings Entry Point
 *
 * Renders the settings page interface.
 *
 * @package
 * @since 1.0.0
 */

import { render } from '@wordpress/element';
import ErrorBoundary from './components/ErrorBoundary';
import SettingsApp from './settings/SettingsApp';

// Import styles
import './editor.css';

// Render settings app with error boundary
const settingsRoot = document.getElementById( 'meowseo-settings-root' );

if ( settingsRoot ) {
	render(
		<ErrorBoundary>
			<SettingsApp />
		</ErrorBoundary>,
		settingsRoot
	);
}
