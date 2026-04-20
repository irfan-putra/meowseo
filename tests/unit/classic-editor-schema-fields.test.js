/**
 * Classic Editor Schema Tab Fields Tests
 * 
 * Tests for the schema tab field implementation in the Classic Editor meta box.
 * Validates all 6 subtasks:
 * - 9.1: Schema Type selector
 * - 9.2: Article schema fields
 * - 9.3: FAQ schema fields
 * - 9.4: HowTo schema fields
 * - 9.5: LocalBusiness schema fields
 * - 9.6: Product schema fields
 */

const fs = require('fs');
const path = require('path');

describe('Classic Editor Schema Tab Fields', () => {
	let jsContent;
	let phpContent;

	beforeAll(() => {
		// Read the JavaScript file
		const jsPath = path.join(__dirname, '../../assets/js/classic-editor.js');
		jsContent = fs.readFileSync(jsPath, 'utf8');

		// Read the PHP file
		const phpPath = path.join(__dirname, '../../includes/modules/meta/class-classic-editor.php');
		phpContent = fs.readFileSync(phpPath, 'utf8');
	});

	describe('9.1: Schema Type Selector', () => {
		it('should render schema type dropdown', () => {
			// Validates: Requirement 14.1
			expect(phpContent).toContain('id="meowseo_schema_type"');
			expect(phpContent).toContain('<select');
		});

		it('should include "None" option', () => {
			// Validates: Requirement 14.2
			expect(phpContent).toContain('value=""');
			expect(phpContent).toContain('— None —');
		});

		it('should include "Article" option', () => {
			// Validates: Requirement 14.2
			expect(phpContent).toContain('value="Article"');
		});

		it('should include "FAQ Page" option', () => {
			// Validates: Requirement 14.2
			expect(phpContent).toContain('value="FAQPage"');
			expect(phpContent).toContain('FAQ Page');
		});

		it('should include "HowTo" option', () => {
			// Validates: Requirement 14.2
			expect(phpContent).toContain('value="HowTo"');
		});

		it('should include "Local Business" option', () => {
			// Validates: Requirement 14.2
			expect(phpContent).toContain('value="LocalBusiness"');
			expect(phpContent).toContain('Local Business');
		});

		it('should include "Product" option', () => {
			// Validates: Requirement 14.2
			expect(phpContent).toContain('value="Product"');
		});

		it('should have initSchemaFields function in JavaScript', () => {
			// Validates: Requirement 14.3, 14.4
			expect(jsContent).toContain('function initSchemaFields()');
		});

		it('should toggle schema field groups on selection', () => {
			// Validates: Requirement 14.3, 14.4
			expect(jsContent).toContain('$groups.hide()');
			expect(jsContent).toContain('$groups.filter( \'[data-type="\' + val + \'"]\' ).show()');
		});

		it('should have change handler on schema type selector', () => {
			// Validates: Requirement 14.3, 14.4
			expect(jsContent).toContain('$select.on( \'change\', syncSchema )');
		});

		it('should call initSchemaFields on document ready', () => {
			// Validates: Requirement 14.3, 14.4
			expect(jsContent).toContain('initSchemaFields();');
		});
	});

	describe('9.2: Article Schema Fields', () => {
		it('should render Article field group with data-type attribute', () => {
			// Validates: Requirement 15.1
			expect(phpContent).toContain('data-type="Article"');
		});

		it('should render Article Type selector', () => {
			// Validates: Requirement 15.1
			expect(phpContent).toContain('id="meowseo_schema_article_type"');
			expect(phpContent).toContain('name="meowseo_schema_article_type"');
		});

		it('should include "Article" article type option', () => {
			// Validates: Requirement 15.1
			expect(phpContent).toContain('value="Article"');
		});

		it('should include "NewsArticle" article type option', () => {
			// Validates: Requirement 15.1
			expect(phpContent).toContain('value="NewsArticle"');
		});

		it('should include "BlogPosting" article type option', () => {
			// Validates: Requirement 15.1
			expect(phpContent).toContain('value="BlogPosting"');
		});

		it('should hide Article fields by default', () => {
			// Validates: Requirement 15.2
			expect(phpContent).toContain('data-type="Article" style="display:none"');
		});

		it('should save article_type to schema_config', () => {
			// Validates: Requirement 15.3
			expect(phpContent).toContain('config.article_type');
		});
	});

	describe('9.3: FAQ Schema Fields', () => {
		it('should render FAQ field group with data-type attribute', () => {
			// Validates: Requirement 16.1
			expect(phpContent).toContain('data-type="FAQPage"');
		});

		it('should render FAQ items container', () => {
			// Validates: Requirement 16.1
			expect(phpContent).toContain('id="meowseo-faq-items"');
		});

		it('should render question input fields', () => {
			// Validates: Requirement 16.1
			expect(phpContent).toContain('name="meowseo_faq_question[]"');
		});

		it('should render answer textarea fields', () => {
			// Validates: Requirement 16.1
			expect(phpContent).toContain('name="meowseo_faq_answer[]"');
		});

		it('should render "Add Question" button', () => {
			// Validates: Requirement 16.2
			expect(phpContent).toContain('id="meowseo-add-faq"');
			expect(phpContent).toContain('Add Question');
		});

		it('should render "Remove" button for each FAQ item', () => {
			// Validates: Requirement 16.3
			expect(phpContent).toContain('class="button meowseo-remove-faq"');
		});

		it('should have click handler for Add Question button', () => {
			// Validates: Requirement 16.2
			expect(phpContent).toContain('$(document).on(\'click\', \'#meowseo-add-faq\'');
		});

		it('should have click handler for Remove FAQ button', () => {
			// Validates: Requirement 16.3
			expect(phpContent).toContain('$(document).on(\'click\', \'.meowseo-remove-faq\'');
		});

		it('should hide FAQ fields by default', () => {
			// Validates: Requirement 16.4
			expect(phpContent).toContain('data-type="FAQPage" style="display:none"');
		});

		it('should save faq_items to schema_config', () => {
			// Validates: Requirement 16.5
			expect(phpContent).toContain('config.faq_items');
		});
	});

	describe('9.4: HowTo Schema Fields', () => {
		it('should render HowTo field group with data-type attribute', () => {
			// Validates: Requirement 17.1
			expect(phpContent).toContain('data-type="HowTo"');
		});

		it('should render HowTo Name field', () => {
			// Validates: Requirement 17.1
			expect(phpContent).toContain('id="meowseo_schema_howto_name"');
			expect(phpContent).toContain('name="meowseo_schema_howto_name"');
		});

		it('should render HowTo Description field', () => {
			// Validates: Requirement 17.1
			expect(phpContent).toContain('id="meowseo_schema_howto_description"');
			expect(phpContent).toContain('name="meowseo_schema_howto_description"');
		});

		it('should render HowTo steps container', () => {
			// Validates: Requirement 17.2
			expect(phpContent).toContain('id="meowseo-howto-steps"');
		});

		it('should render step name input fields', () => {
			// Validates: Requirement 17.2
			expect(phpContent).toContain('name="meowseo_howto_step_name[]"');
		});

		it('should render step text textarea fields', () => {
			// Validates: Requirement 17.2
			expect(phpContent).toContain('name="meowseo_howto_step_text[]"');
		});

		it('should render "Add Step" button', () => {
			// Validates: Requirement 17.3
			expect(phpContent).toContain('id="meowseo-add-step"');
			expect(phpContent).toContain('Add Step');
		});

		it('should render "Remove" button for each step', () => {
			// Validates: Requirement 17.4
			expect(phpContent).toContain('class="button meowseo-remove-step"');
		});

		it('should have click handler for Add Step button', () => {
			// Validates: Requirement 17.3
			expect(phpContent).toContain('$(document).on(\'click\', \'#meowseo-add-step\'');
		});

		it('should have click handler for Remove Step button', () => {
			// Validates: Requirement 17.4
			expect(phpContent).toContain('$(document).on(\'click\', \'.meowseo-remove-step\'');
		});

		it('should hide HowTo fields by default', () => {
			// Validates: Requirement 17.5
			expect(phpContent).toContain('data-type="HowTo" style="display:none"');
		});

		it('should save howto_name, howto_description, and howto_steps to schema_config', () => {
			// Validates: Requirement 17.6
			expect(phpContent).toContain('config.howto_name');
			expect(phpContent).toContain('config.howto_description');
			expect(phpContent).toContain('config.howto_steps');
		});
	});

	describe('9.5: LocalBusiness Schema Fields', () => {
		it('should render LocalBusiness field group with data-type attribute', () => {
			// Validates: Requirement 18.1
			expect(phpContent).toContain('data-type="LocalBusiness"');
		});

		it('should render Business Name field', () => {
			// Validates: Requirement 18.1
			expect(phpContent).toContain('lb_name');
			expect(phpContent).toContain('Business Name');
		});

		it('should render Business Type field', () => {
			// Validates: Requirement 18.1
			expect(phpContent).toContain('lb_type');
			expect(phpContent).toContain('Business Type');
		});

		it('should render Address field', () => {
			// Validates: Requirement 18.1
			expect(phpContent).toContain('lb_address');
			expect(phpContent).toContain('Address');
		});

		it('should render Phone field', () => {
			// Validates: Requirement 18.1
			expect(phpContent).toContain('lb_phone');
			expect(phpContent).toContain('Phone');
		});

		it('should render Hours field', () => {
			// Validates: Requirement 18.1
			expect(phpContent).toContain('lb_hours');
			expect(phpContent).toContain('Opening Hours');
		});

		it('should hide LocalBusiness fields by default', () => {
			// Validates: Requirement 18.2
			expect(phpContent).toContain('data-type="LocalBusiness" style="display:none"');
		});

		it('should save LocalBusiness fields to schema_config', () => {
			// Validates: Requirement 18.3
			expect(phpContent).toContain('config[k] = $(\'#meowseo_schema_\' + k).val()');
		});
	});

	describe('9.6: Product Schema Fields', () => {
		it('should render Product field group with data-type attribute', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('data-type="Product"');
		});

		it('should render Product Name field', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('product_name');
			expect(phpContent).toContain('Product Name');
		});

		it('should render Description field', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('product_description');
			expect(phpContent).toContain('Description');
		});

		it('should render SKU field', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('product_sku');
			expect(phpContent).toContain('SKU');
		});

		it('should render Price field', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('product_price');
			expect(phpContent).toContain('Price');
		});

		it('should render Currency field', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('product_currency');
			expect(phpContent).toContain('Currency');
		});

		it('should render Availability field', () => {
			// Validates: Requirement 19.1
			expect(phpContent).toContain('product_availability');
			expect(phpContent).toContain('Availability');
		});

		it('should hide Product fields by default', () => {
			// Validates: Requirement 19.2
			expect(phpContent).toContain('data-type="Product" style="display:none"');
		});

		it('should save Product fields to schema_config', () => {
			// Validates: Requirement 19.3
			expect(phpContent).toContain('config[k] = $(\'#meowseo_schema_\' + k).val()');
		});
	});

	describe('Schema Configuration JSON Storage', () => {
		it('should have hidden input for schema_config', () => {
			expect(phpContent).toContain('id="meowseo_schema_config"');
			expect(phpContent).toContain('type="hidden"');
		});

		it('should build schema_config JSON on form submit', () => {
			expect(phpContent).toContain('$(\'#post\').on(\'submit\'');
			expect(phpContent).toContain('JSON.stringify(config)');
		});

		it('should handle empty schema type', () => {
			expect(phpContent).toContain('if (!type) { $(\'#meowseo_schema_config\').val(\'\')');
		});
	});

	describe('Data Persistence', () => {
		it('should save schema_type to postmeta', () => {
			// Validates: Requirement 14.5
			expect(phpContent).toContain('_meowseo_schema_type');
		});

		it('should save schema_config JSON to postmeta', () => {
			// Validates: Requirements 15.3, 16.5, 17.6, 18.3, 19.3
			expect(phpContent).toContain('_meowseo_schema_config');
		});

		it('should sanitize schema_type on save', () => {
			expect(phpContent).toContain('sanitize_text_field');
		});

		it('should validate and re-encode schema_config JSON on save', () => {
			expect(phpContent).toContain('json_decode');
			expect(phpContent).toContain('wp_json_encode');
		});
	});

	describe('Integration with Tab Navigation', () => {
		it('should be part of Schema tab panel', () => {
			expect(phpContent).toContain('id="meowseo-tab-schema"');
			expect(phpContent).toContain('class="meowseo-tab-panel"');
		});

		it('should have all schema field groups within Schema tab', () => {
			const schemaTabStart = phpContent.indexOf('id="meowseo-tab-schema"');
			const schemaTabEnd = phpContent.indexOf('id="meowseo-tab-advanced"');
			const schemaTabContent = phpContent.substring(schemaTabStart, schemaTabEnd);

			expect(schemaTabContent).toContain('data-type="Article"');
			expect(schemaTabContent).toContain('data-type="FAQPage"');
			expect(schemaTabContent).toContain('data-type="HowTo"');
			expect(schemaTabContent).toContain('data-type="LocalBusiness"');
			expect(schemaTabContent).toContain('data-type="Product"');
		});
	});

	describe('Accessibility and Semantics', () => {
		it('should use proper label elements for all fields', () => {
			expect(phpContent).toContain('<label');
		});

		it('should use semantic input and textarea elements', () => {
			expect(phpContent).toContain('<input type="text"');
			expect(phpContent).toContain('<textarea');
			expect(phpContent).toContain('<select');
		});

		it('should use button elements for add/remove actions', () => {
			expect(phpContent).toContain('<button type="button"');
		});
	});
});
