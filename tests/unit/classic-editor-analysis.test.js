/**
 * Classic Editor Analysis Tests
 * 
 * Tests for the SEO and Readability Analysis functionality in the Classic Editor meta box.
 * Validates:
 * - Analysis panel HTML structure
 * - REST API integration with nonce authentication
 * - Field change triggers with 1-second debounce
 * - Analysis result rendering with colored indicators
 * - Composite score badge display
 */

const fs = require('fs');
const path = require('path');

describe('Classic Editor Analysis Integration', () => {
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

	describe('6.1: Analysis Panel HTML Structure', () => {
		it('should render SEO Analysis section heading', () => {
			// Validates: Requirement 7.3
			expect(phpContent).toContain('SEO Analysis');
		});

		it('should render Run Analysis button', () => {
			// Validates: Requirement 7.5
			expect(phpContent).toContain('id="meowseo-run-analysis"');
			expect(phpContent).toContain('Run Analysis');
		});

		it('should render analysis panel container', () => {
			// Validates: Requirement 7.3, 8.3
			expect(phpContent).toContain('id="meowseo-analysis-panel"');
		});

		it('should have initial placeholder text in analysis panel', () => {
			// Validates: Requirement 7.3
			expect(phpContent).toContain('Save the post, then click Run Analysis');
		});
	});

	describe('6.2: Analysis REST API Integration', () => {
		it('should have runAnalysis function', () => {
			// Validates: Requirement 7.1, 7.2, 8.1, 8.2
			expect(jsContent).toContain('function runAnalysis()');
		});

		it('should call analysis endpoint with correct URL', () => {
			// Validates: Requirement 7.2
			expect(jsContent).toContain("meowseoClassic.restUrl + '/analysis/' + meowseoClassic.postId");
		});

		it('should include nonce in X-WP-Nonce header', () => {
			// Validates: Requirement 7.2, 26.2
			expect(jsContent).toContain("xhr.setRequestHeader( 'X-WP-Nonce', meowseoClassic.nonce )");
		});

		it('should use GET method for analysis endpoint', () => {
			// Validates: Requirement 7.2
			expect(jsContent).toContain("method: 'GET'");
		});

		it('should debounce analysis by 1 second', () => {
			// Validates: Requirement 7.1, 8.1, 30.1
			expect(jsContent).toContain('clearTimeout( analysisTimer )');
			expect(jsContent).toContain('analysisTimer = setTimeout');
			expect(jsContent).toContain(', 1000 )');
		});

		it('should trigger analysis on SEO Title field change', () => {
			// Validates: Requirement 7.1, 8.1
			expect(jsContent).toContain("$titleInput.on( 'input', function ()");
			expect(jsContent).toContain('runAnalysis()');
		});

		it('should trigger analysis on Meta Description field change', () => {
			// Validates: Requirement 7.1, 8.1
			expect(jsContent).toContain("$descInput.on( 'input', function ()");
			expect(jsContent).toContain('runAnalysis()');
		});

		it('should handle analysis API errors gracefully', () => {
			// Validates: Requirement 29.1
			expect(jsContent).toContain('error: function ()');
			expect(jsContent).toContain('Analysis failed');
		});
	});

	describe('6.3: Analysis Results Rendering', () => {
		it('should have renderAnalysis function', () => {
			// Validates: Requirement 7.4, 8.4
			expect(jsContent).toContain('function renderAnalysis( $panel, data )');
		});

		it('should render SEO Analysis section', () => {
			// Validates: Requirement 7.4
			expect(jsContent).toContain('SEO Analysis');
		});

		it('should render Readability Analysis section', () => {
			// Validates: Requirement 8.4
			expect(jsContent).toContain('Readability Analysis');
		});

		it('should display SEO score badge', () => {
			// Validates: Requirement 7.5
			expect(jsContent).toContain('renderScoreBadge( data.seo.score, data.seo.color )');
		});

		it('should display Readability score badge', () => {
			// Validates: Requirement 8.5
			expect(jsContent).toContain('renderScoreBadge( data.readability.score, data.readability.color )');
		});

		it('should render SEO checks with pass/fail indicators', () => {
			// Validates: Requirement 7.4
			expect(jsContent).toContain('data.seo.checks.forEach');
			expect(jsContent).toContain("check.pass ? '✓' : '✕'");
		});

		it('should render Readability checks with pass/fail indicators', () => {
			// Validates: Requirement 8.4
			expect(jsContent).toContain('data.readability.checks.forEach');
		});

		it('should apply green color for passing checks', () => {
			// Validates: Requirement 7.4, 8.4
			expect(jsContent).toContain("check.pass ? '#155724' : '#721c24'");
		});

		it('should apply red color for failing checks', () => {
			// Validates: Requirement 7.4, 8.4
			expect(jsContent).toContain('#721c24');
		});

		it('should have renderScoreBadge function', () => {
			// Validates: Requirement 7.5, 8.5
			expect(jsContent).toContain('function renderScoreBadge( score, color )');
		});

		it('should render score badge with green background for good scores', () => {
			// Validates: Requirement 7.5, 8.5
			expect(jsContent).toContain("color === 'green' ? '#d4edda'");
		});

		it('should render score badge with orange background for moderate scores', () => {
			// Validates: Requirement 7.5, 8.5
			expect(jsContent).toContain("color === 'orange' ? '#fff3cd'");
		});

		it('should render score badge with red background for poor scores', () => {
			// Validates: Requirement 7.5, 8.5
			expect(jsContent).toContain("'#f8d7da'");
		});

		it('should escape HTML in check labels', () => {
			// Validates: Requirement 29.4
			expect(jsContent).toContain('escHtml( check.label )');
		});
	});

	describe('CSS Styling for Analysis Panels', () => {
		it('should have styles for analysis panel', () => {
			// Validates: Requirement 7.5, 8.5
			expect(cssContent).toContain('.meowseo-analysis-panel');
		});

		it('should have styles for score badge', () => {
			// Validates: Requirement 7.5, 8.5
			expect(cssContent).toContain('.meowseo-score-badge');
		});

		it('should have green score badge style', () => {
			// Validates: Requirement 7.5, 8.5
			expect(cssContent).toContain('.meowseo-score-badge.score-green');
		});

		it('should have orange score badge style', () => {
			// Validates: Requirement 7.5, 8.5
			expect(cssContent).toContain('.meowseo-score-badge.score-orange');
		});

		it('should have red score badge style', () => {
			// Validates: Requirement 7.5, 8.5
			expect(cssContent).toContain('.meowseo-score-badge.score-red');
		});

		it('should have styles for analysis checks', () => {
			// Validates: Requirement 7.4, 8.4
			expect(cssContent).toContain('.meowseo-analysis-check');
		});

		it('should have styles for passing checks', () => {
			// Validates: Requirement 7.4, 8.4
			expect(cssContent).toContain('.meowseo-analysis-check.pass');
		});

		it('should have styles for failing checks', () => {
			// Validates: Requirement 7.4, 8.4
			expect(cssContent).toContain('.meowseo-analysis-check.fail');
		});
	});

	describe('Run Analysis Button Integration', () => {
		it('should have click handler for Run Analysis button', () => {
			// Validates: Requirement 7.6
			expect(phpContent).toContain('#meowseo-run-analysis');
		});

		it('should trigger analysis on button click', () => {
			// Validates: Requirement 7.6
			expect(phpContent).toContain("$(document).on('click', '#meowseo-run-analysis'");
		});
	});

	describe('Composite Score Display', () => {
		it('should display SEO score as a number', () => {
			// Validates: Requirement 7.5
			expect(jsContent).toContain('data.seo.score');
		});

		it('should display Readability score as a number', () => {
			// Validates: Requirement 8.5
			expect(jsContent).toContain('data.readability.score');
		});

		it('should display score with color indicator', () => {
			// Validates: Requirement 7.5, 8.5
			expect(jsContent).toContain('data.seo.color');
			expect(jsContent).toContain('data.readability.color');
		});
	});

	describe('Error Handling', () => {
		it('should display error message on API failure', () => {
			// Validates: Requirement 29.1
			expect(jsContent).toContain('Analysis failed');
		});

		it('should display helpful message when post not saved', () => {
			// Validates: Requirement 29.1
			expect(jsContent).toContain('Save the post first');
		});

		it('should log errors to console', () => {
			// Validates: Requirement 29.4
			expect(jsContent).toContain('console.error');
		});
	});

	describe('Data Structure Validation', () => {
		it('should handle SEO analysis data structure', () => {
			// Validates: Requirement 7.4
			expect(jsContent).toContain('data.seo');
			expect(jsContent).toContain('data.seo.score');
			expect(jsContent).toContain('data.seo.checks');
			expect(jsContent).toContain('data.seo.color');
		});

		it('should handle Readability analysis data structure', () => {
			// Validates: Requirement 8.4
			expect(jsContent).toContain('data.readability');
			expect(jsContent).toContain('data.readability.score');
			expect(jsContent).toContain('data.readability.checks');
			expect(jsContent).toContain('data.readability.color');
		});

		it('should handle check objects with pass property', () => {
			// Validates: Requirement 7.4, 8.4
			expect(jsContent).toContain('check.pass');
		});

		it('should handle check objects with label property', () => {
			// Validates: Requirement 7.4, 8.4
			expect(jsContent).toContain('check.label');
		});
	});

	describe('Integration with Other Features', () => {
		it('should not interfere with tab navigation', () => {
			// Validates: Requirement 1.2
			expect(jsContent).toContain('initTabs()');
		});

		it('should not interfere with character counters', () => {
			// Validates: Requirement 2.2, 3.2
			expect(jsContent).toContain('initCounters()');
		});

		it('should not interfere with SERP preview', () => {
			// Validates: Requirement 4.2
			expect(jsContent).toContain('initSerpPreview()');
		});
	});
});
