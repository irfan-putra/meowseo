/**
 * Classic Editor Tab Navigation Tests
 * 
 * Tests for the tab navigation functionality in the Classic Editor meta box.
 * Validates:
 * - Tab switching behavior
 * - localStorage persistence
 * - Active state management
 * - CSS class application
 */

const fs = require('fs');
const path = require('path');

describe('Classic Editor Tab Navigation', () => {
	let jsContent;
	let cssContent;
	let phpContent;

	beforeAll(() => {
		// Read the JavaScript file
		const jsPath = path.join(__dirname, '../../assets/js/classic-editor.js');
		jsContent = fs.readFileSync(jsPath, 'utf8');

		// Read the CSS file
		const cssPath = path.join(__dirname, '../../assets/css/classic-editor.css');
		cssContent = fs.readFileSync(cssPath, 'utf8');

		// Read the PHP file
		const phpPath = path.join(__dirname, '../../includes/modules/meta/class-classic-editor.php');
		phpContent = fs.readFileSync(phpPath, 'utf8');
	});

	describe('3.1: Tab Navigation HTML Structure', () => {
		it('should render tab navigation container with correct ID', () => {
			// Validates: Requirement 1.1
			expect(phpContent).toContain('<div id="meowseo-tab-nav">');
		});

		it('should render all four tab buttons with correct data attributes', () => {
			// Validates: Requirement 1.1
			expect(phpContent).toContain('data-tab="general"');
			expect(phpContent).toContain('data-tab="social"');
			expect(phpContent).toContain('data-tab="schema"');
			expect(phpContent).toContain('data-tab="advanced"');
		});

		it('should render tab buttons with correct labels', () => {
			// Validates: Requirement 1.1
			expect(phpContent).toContain('>General</button>');
			expect(phpContent).toContain('>Social</button>');
			expect(phpContent).toContain('>Schema</button>');
			expect(phpContent).toContain('>Advanced</button>');
		});

		it('should render all four tab panel containers', () => {
			// Validates: Requirement 1.1
			expect(phpContent).toContain('id="meowseo-tab-general"');
			expect(phpContent).toContain('id="meowseo-tab-social"');
			expect(phpContent).toContain('id="meowseo-tab-schema"');
			expect(phpContent).toContain('id="meowseo-tab-advanced"');
		});

		it('should apply meowseo-tab-panel class to all panels', () => {
			// Validates: Requirement 1.1
			expect(phpContent).toContain('class="meowseo-tab-panel"');
		});
	});

	describe('3.2: JavaScript Tab Switching', () => {
		it('should have initTabs function', () => {
			// Validates: Requirement 1.2
			expect(jsContent).toContain('function initTabs()');
		});

		it('should add click handlers to tab buttons', () => {
			// Validates: Requirement 1.2
			expect(jsContent).toContain('$nav.on( \'click\', \'button\'');
		});

		it('should have activate function that manages tab state', () => {
			// Validates: Requirement 1.2, 1.5
			expect(jsContent).toContain('function activate( tab )');
			expect(jsContent).toContain('removeClass( \'meowseo-active\' )');
			expect(jsContent).toContain('addClass( \'meowseo-active\' )');
		});

		it('should show/hide tab panels on activation', () => {
			// Validates: Requirement 1.2
			expect(jsContent).toContain('$panels.removeClass( \'meowseo-active\' )');
			expect(jsContent).toContain('$( \'#meowseo-tab-\' + tab ).addClass( \'meowseo-active\' )');
		});

		it('should add/remove active class on tab buttons', () => {
			// Validates: Requirement 1.5
			expect(jsContent).toContain('$nav.find( \'button\' ).removeClass( \'meowseo-active\' )');
			expect(jsContent).toContain('$nav.find( \'button[data-tab="\' + tab + \'"]\' ).addClass( \'meowseo-active\' )');
		});

		it('should call initTabs on document ready', () => {
			// Validates: Requirement 1.2
			expect(jsContent).toContain('initTabs();');
		});
	});

	describe('3.3: Tab State Persistence', () => {
		it('should define localStorage key constant', () => {
			// Validates: Requirement 1.3
			expect(jsContent).toContain('STORAGE_KEY');
			expect(jsContent).toContain('meowseo_active_tab');
		});

		it('should save active tab to localStorage on switch', () => {
			// Validates: Requirement 1.3
			expect(jsContent).toContain('localStorage.setItem( STORAGE_KEY, tab )');
		});

		it('should restore active tab from localStorage on page load', () => {
			// Validates: Requirement 1.4
			expect(jsContent).toContain('localStorage.getItem( STORAGE_KEY )');
			expect(jsContent).toContain('|| \'general\'');
		});

		it('should activate saved tab on initialization', () => {
			// Validates: Requirement 1.4
			expect(jsContent).toContain('var saved = localStorage.getItem( STORAGE_KEY )');
			expect(jsContent).toContain('activate( saved )');
		});

		it('should default to general tab if no saved state', () => {
			// Validates: Requirement 1.4
			expect(jsContent).toContain('|| \'general\'');
		});
	});

	describe('CSS Styling for Active State', () => {
		it('should have styles for tab navigation container', () => {
			// Validates: Requirement 1.5
			expect(cssContent).toContain('#meowseo-tab-nav');
		});

		it('should have styles for tab buttons', () => {
			// Validates: Requirement 1.5
			expect(cssContent).toContain('#meowseo-tab-nav button');
		});

		it('should have styles for active tab button', () => {
			// Validates: Requirement 1.5
			expect(cssContent).toContain('.meowseo-active');
		});

		it('should have styles for tab panels', () => {
			// Validates: Requirement 1.5
			expect(cssContent).toContain('.meowseo-tab-panel');
		});

		it('should hide inactive tab panels', () => {
			// Validates: Requirement 1.2
			expect(cssContent).toContain('display: none');
		});

		it('should show active tab panel', () => {
			// Validates: Requirement 1.2
			expect(cssContent).toContain('.meowseo-tab-panel.meowseo-active');
			expect(cssContent).toContain('display: block');
		});

		it('should style active tab button with visual indicator', () => {
			// Validates: Requirement 1.5
			expect(cssContent).toContain('#meowseo-tab-nav button.meowseo-active');
		});
	});

	describe('Integration and Bootstrap', () => {
		it('should initialize tabs in document ready handler', () => {
			expect(jsContent).toContain('$( function () {');
			expect(jsContent).toContain('initTabs();');
		});

		it('should use jQuery wrapper', () => {
			expect(jsContent).toContain('( function ( $ ) {');
			expect(jsContent).toContain('} )( jQuery );');
		});

		it('should use strict mode', () => {
			expect(jsContent).toContain('\'use strict\';');
		});
	});

	describe('Tab Content Structure', () => {
		it('should have General tab with expected content', () => {
			expect(phpContent).toContain('id="meowseo-tab-general"');
			expect(phpContent).toContain('meowseo_title');
			expect(phpContent).toContain('meowseo_description');
		});

		it('should have Social tab with expected content', () => {
			expect(phpContent).toContain('id="meowseo-tab-social"');
			expect(phpContent).toContain('meowseo_og_title');
			expect(phpContent).toContain('meowseo_twitter_title');
		});

		it('should have Schema tab with expected content', () => {
			expect(phpContent).toContain('id="meowseo-tab-schema"');
			expect(phpContent).toContain('meowseo_schema_type');
		});

		it('should have Advanced tab with expected content', () => {
			expect(phpContent).toContain('id="meowseo-tab-advanced"');
			expect(phpContent).toContain('meowseo_canonical');
			expect(phpContent).toContain('meowseo_robots_noindex');
		});
	});

	describe('Accessibility and Semantics', () => {
		it('should use button elements for tab navigation', () => {
			expect(phpContent).toContain('<button type="button"');
		});

		it('should use semantic div containers for panels', () => {
			expect(phpContent).toContain('<div id="meowseo-tab-');
		});

		it('should have proper nesting structure', () => {
			expect(phpContent).toContain('<div id="meowseo-tabs">');
			expect(phpContent).toContain('<div id="meowseo-tab-nav">');
		});
	});
});
