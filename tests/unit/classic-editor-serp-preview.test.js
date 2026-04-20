/**
 * Classic Editor SERP Preview Tests
 * 
 * Tests for the SERP Preview functionality in the Classic Editor meta box.
 * Validates:
 * - SERP Preview HTML structure
 * - Real-time updates with debouncing
 * - Title and description truncation
 * - URL format display
 */

const fs = require('fs');
const path = require('path');

describe('Classic Editor SERP Preview', () => {
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

	describe('5.1: SERP Preview HTML Structure', () => {
		it('should render SERP Preview container', () => {
			// Validates: Requirement 4.1
			expect(phpContent).toContain('class="meowseo-serp-preview"');
		});

		it('should render URL element', () => {
			// Validates: Requirement 4.1
			expect(phpContent).toContain('class="serp-url"');
		});

		it('should render title element with ID', () => {
			// Validates: Requirement 4.1
			expect(phpContent).toContain('id="meowseo-serp-title"');
			expect(phpContent).toContain('class="serp-title"');
		});

		it('should render description element with ID', () => {
			// Validates: Requirement 4.1
			expect(phpContent).toContain('id="meowseo-serp-desc"');
			expect(phpContent).toContain('class="serp-desc"');
		});

		it('should display URL in breadcrumb format with separator', () => {
			// Validates: Requirement 4.6
			// The PHP code should format URL as "domain.com › slug"
			expect(phpContent).toContain('› ');
		});

		it('should parse URL to extract host and slug', () => {
			// Validates: Requirement 4.6
			expect(phpContent).toContain('wp_parse_url');
			expect(phpContent).toContain('$host');
			expect(phpContent).toContain('$slug');
		});

		it('should display post title as fallback in SERP title', () => {
			// Validates: Requirement 4.2
			expect(phpContent).toContain('get_the_title( $post )');
		});

		it('should display post excerpt as fallback in SERP description', () => {
			// Validates: Requirement 4.3
			expect(phpContent).toContain('get_the_excerpt( $post )');
		});
	});

	describe('5.2: Real-time SERP Preview Updates', () => {
		it('should have updateSerpPreview function', () => {
			// Validates: Requirement 4.2, 4.3
			expect(jsContent).toContain('function updateSerpPreview()');
		});

		it('should have initSerpPreview function', () => {
			// Validates: Requirement 4.2, 4.3
			expect(jsContent).toContain('function initSerpPreview()');
		});

		it('should call updateSerpPreview on title input', () => {
			// Validates: Requirement 4.2
			expect(jsContent).toContain('$titleInput.on( \'input\'');
			expect(jsContent).toContain('updateSerpPreview()');
		});

		it('should call updateSerpPreview on description input', () => {
			// Validates: Requirement 4.3
			expect(jsContent).toContain('$descInput.on( \'input\'');
			expect(jsContent).toContain('updateSerpPreview()');
		});

		it('should update SERP title element', () => {
			// Validates: Requirement 4.2
			expect(jsContent).toContain('$( \'#meowseo-serp-title\' )');
			expect(jsContent).toContain('.text(');
		});

		it('should update SERP description element', () => {
			// Validates: Requirement 4.3
			expect(jsContent).toContain('$( \'#meowseo-serp-desc\' )');
			expect(jsContent).toContain('.text(');
		});

		it('should have truncate function', () => {
			// Validates: Requirement 4.4, 4.5
			expect(jsContent).toContain('function truncate( str, max )');
		});

		it('should truncate title at 60 characters', () => {
			// Validates: Requirement 4.4
			expect(jsContent).toContain('truncate( title, 60 )');
		});

		it('should truncate description at 155 characters', () => {
			// Validates: Requirement 4.5
			expect(jsContent).toContain('truncate( desc, 155 )');
		});

		it('should add ellipsis when truncating', () => {
			// Validates: Requirement 4.4, 4.5
			expect(jsContent).toContain('+ \'…\'');
		});

		it('should debounce SERP preview updates by 100ms', () => {
			// Validates: Requirement 30.2
			expect(jsContent).toContain('serpPreviewTimer');
			expect(jsContent).toContain('setTimeout');
			expect(jsContent).toContain('100');
			expect(jsContent).toContain('clearTimeout( serpPreviewTimer )');
		});

		it('should use clearTimeout to cancel pending updates', () => {
			// Validates: Requirement 30.2
			expect(jsContent).toContain('clearTimeout( serpPreviewTimer )');
		});

		it('should fall back to post title when SEO title is empty', () => {
			// Validates: Requirement 4.2
			expect(jsContent).toContain('meowseoClassic.postTitle');
		});

		it('should fall back to post excerpt when description is empty', () => {
			// Validates: Requirement 4.3
			expect(jsContent).toContain('meowseoClassic.postExcerpt');
		});

		it('should call initSerpPreview on document ready', () => {
			// Validates: Requirement 4.2, 4.3
			expect(jsContent).toContain('initSerpPreview();');
		});
	});

	describe('CSS Styling for SERP Preview', () => {
		it('should have styles for SERP preview container', () => {
			// Validates: Requirement 4.1
			expect(cssContent).toContain('.meowseo-serp-preview');
		});

		it('should have styles for SERP URL', () => {
			// Validates: Requirement 4.1
			expect(cssContent).toContain('.serp-url');
		});

		it('should have styles for SERP title', () => {
			// Validates: Requirement 4.1
			expect(cssContent).toContain('.serp-title');
		});

		it('should have styles for SERP description', () => {
			// Validates: Requirement 4.1
			expect(cssContent).toContain('.serp-desc');
		});

		it('should style SERP preview to match Google appearance', () => {
			// Validates: Requirement 4.1
			expect(cssContent).toContain('font-family: arial');
		});

		it('should have proper spacing and layout', () => {
			// Validates: Requirement 4.1
			expect(cssContent).toContain('padding');
			expect(cssContent).toContain('margin');
		});

		it('should handle text overflow for URL', () => {
			// Validates: Requirement 4.6
			expect(cssContent).toContain('overflow: hidden');
			expect(cssContent).toContain('text-overflow: ellipsis');
		});
	});

	describe('Truncation Logic', () => {
		it('should return empty string for falsy input', () => {
			// Validates: Requirement 4.4, 4.5
			expect(jsContent).toContain('if ( ! str ) return \'\';');
		});

		it('should check string length against max', () => {
			// Validates: Requirement 4.4, 4.5
			expect(jsContent).toContain('str.length > max');
		});

		it('should use substring to truncate', () => {
			// Validates: Requirement 4.4, 4.5
			expect(jsContent).toContain('substring( 0, max )');
		});

		it('should return original string if under max length', () => {
			// Validates: Requirement 4.4, 4.5
			expect(jsContent).toContain(': str');
		});
	});

	describe('Integration with Character Counters', () => {
		it('should update SERP preview when title counter updates', () => {
			// Validates: Requirement 4.2
			const titleInputHandler = jsContent.match(/\$titleInput\.on\s*\(\s*['"]input['"]\s*,\s*function\s*\(\)\s*\{[^}]+\}/s);
			expect(titleInputHandler).toBeTruthy();
			expect(titleInputHandler[0]).toContain('updateSerpPreview');
		});

		it('should update SERP preview when description counter updates', () => {
			// Validates: Requirement 4.3
			const descInputHandler = jsContent.match(/\$descInput\.on\s*\(\s*['"]input['"]\s*,\s*function\s*\(\)\s*\{[^}]+\}/s);
			expect(descInputHandler).toBeTruthy();
			expect(descInputHandler[0]).toContain('updateSerpPreview');
		});
	});

	describe('Localized Script Data', () => {
		it('should provide postTitle in localized data', () => {
			// Validates: Requirement 4.2
			expect(phpContent).toContain('\'postTitle\'');
			expect(phpContent).toContain('get_the_title');
		});

		it('should provide postExcerpt in localized data', () => {
			// Validates: Requirement 4.3
			expect(phpContent).toContain('\'postExcerpt\'');
			expect(phpContent).toContain('get_the_excerpt');
		});

		it('should provide siteUrl in localized data', () => {
			// Validates: Requirement 4.6
			expect(phpContent).toContain('\'siteUrl\'');
			expect(phpContent).toContain('home_url()');
		});
	});

	describe('Performance Optimization', () => {
		it('should declare timer variable for debouncing', () => {
			// Validates: Requirement 30.2
			expect(jsContent).toContain('var serpPreviewTimer');
		});

		it('should use 100ms debounce delay', () => {
			// Validates: Requirement 30.2
			const setTimeoutMatch = jsContent.match(/setTimeout\s*\(\s*function\s*\(\)\s*\{[\s\S]+?\},\s*100\s*\)/);
			expect(setTimeoutMatch).toBeTruthy();
			expect(setTimeoutMatch[0]).toContain('100');
		});

		it('should clear previous timeout before setting new one', () => {
			// Validates: Requirement 30.2
			expect(jsContent).toContain('clearTimeout( serpPreviewTimer )');
			const updateFunction = jsContent.match(/function updateSerpPreview\(\)\s*\{[^}]+\}/s);
			expect(updateFunction).toBeTruthy();
			expect(updateFunction[0]).toContain('clearTimeout');
		});
	});

	describe('Initial State', () => {
		it('should initialize SERP preview on page load', () => {
			// Validates: Requirement 4.1
			expect(jsContent).toContain('initSerpPreview()');
		});

		it('should display initial values without debounce on load', () => {
			// Validates: Requirement 4.1
			const initFunction = jsContent.match(/function initSerpPreview\(\)\s*\{[^}]+\}/s);
			expect(initFunction).toBeTruthy();
			// Should update immediately on init, not use setTimeout
			expect(initFunction[0]).toContain('$( \'#meowseo-serp-title\' )');
			expect(initFunction[0]).toContain('$( \'#meowseo-serp-desc\' )');
		});
	});

	describe('URL Format Validation', () => {
		it('should extract path parts from URL', () => {
			// Validates: Requirement 4.6
			expect(phpContent).toContain('explode');
			expect(phpContent).toContain('$path_parts');
		});

		it('should get last path segment as slug', () => {
			// Validates: Requirement 4.6
			expect(phpContent).toContain('end( $path_parts )');
		});

		it('should combine host and slug with breadcrumb separator', () => {
			// Validates: Requirement 4.6
			expect(phpContent).toContain('$host');
			expect(phpContent).toContain('$slug');
			expect(phpContent).toContain('› ');
		});

		it('should handle empty slug gracefully', () => {
			// Validates: Requirement 4.6
			expect(phpContent).toContain('! empty( $path_parts )');
		});
	});

	describe('Accessibility', () => {
		it('should use semantic HTML elements', () => {
			expect(phpContent).toContain('<div class="serp-url">');
			expect(phpContent).toContain('<div class="serp-title"');
			expect(phpContent).toContain('<div class="serp-desc"');
		});

		it('should have descriptive label for preview', () => {
			expect(phpContent).toContain('Search Preview');
		});

		it('should escape HTML in URL display', () => {
			expect(phpContent).toContain('esc_html( $display_url )');
		});

		it('should escape HTML in title display', () => {
			expect(phpContent).toContain('esc_html( $title');
		});

		it('should escape HTML in description display', () => {
			expect(phpContent).toContain('esc_html( $description');
		});
	});
});
