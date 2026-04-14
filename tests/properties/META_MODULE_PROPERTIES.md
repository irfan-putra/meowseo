# Meta Module Property-Based Tests

This directory contains property-based tests for the Meta Module rebuild using eris/eris.

## Overview

Property-based testing validates universal correctness properties across all valid inputs, rather than testing specific examples. The Meta Module has 29 correctness properties that must hold true.

## Property Test Files

### Output Properties
- `MetaProperty01TagOutputOrderTest.php` - Property 1: Tag Output Order
- `MetaProperty02ConditionalDescriptionTest.php` - Property 2: Conditional Description Output
- `MetaProperty03GoogleDiscoverDirectivesTest.php` - Property 3: Google Discover Directives Always Present
- `MetaProperty06OpenGraphTagOrderTest.php` - Property 6: Open Graph Tag Order
- `MetaProperty07ISO8601DateFormattingTest.php` - Property 7: ISO 8601 Date Formatting

### Fallback Chain Properties
- `MetaProperty10TitleFallbackChainTest.php` - Property 10: Title Fallback Chain Completeness
- `MetaProperty11DescriptionFallbackChainTest.php` - Property 11: Description Fallback Chain Completeness
- `MetaProperty13OGImageFallbackChainTest.php` - Property 13: OG Image Fallback Chain Completeness
- `MetaProperty16CanonicalFallbackChainTest.php` - Property 16: Canonical Fallback Chain Completeness

### URL Properties
- `MetaProperty04CanonicalPaginationStrippingTest.php` - Property 4: Canonical Pagination Stripping
- `MetaProperty05CanonicalAlwaysPresentTest.php` - Property 5: Canonical Always Present

### Social Media Properties
- `MetaProperty08TwitterCardIndependenceTest.php` - Property 8: Twitter Card Independence
- `MetaProperty09ConditionalHreflangTest.php` - Property 9: Conditional Hreflang Output

### Image Properties
- `MetaProperty14OGImageDimensionValidationTest.php` - Property 14: OG Image Dimension Validation
- `MetaProperty15OGImageReturnStructureTest.php` - Property 15: OG Image Return Structure

### Robots Properties
- `MetaProperty17RobotsDirectiveMergingTest.php` - Property 17: Robots Directive Merging

### Pattern System Properties
- `MetaProperty18VariableReplacementTest.php` - Property 18: Variable Replacement Completeness
- `MetaProperty19MissingVariableHandlingTest.php` - Property 19: Missing Variable Handling
- `MetaProperty20PaginationVariableTest.php` - Property 20: Pagination Variable Conditional
- `MetaProperty28TitlePatternRoundTripTest.php` - Property 28: Title Pattern Round-Trip
- `MetaProperty29InvalidPatternErrorTest.php` - Property 29: Invalid Pattern Error Handling

### Global SEO Properties
- `MetaProperty21GlobalSEOPageTypeCoverageTest.php` - Property 21: Global SEO Page Type Coverage
- `MetaProperty22AuthorPageNoindexTest.php` - Property 22: Author Page Noindex Rule
- `MetaProperty23SearchPageNoindexTest.php` - Property 23: Search Page Noindex Invariant

### Robots.txt Properties
- `MetaProperty24RobotsTxtSitemapURLTest.php` - Property 24: Robots.txt Sitemap URL Presence
- `MetaProperty25RobotsTxtCustomDirectivesTest.php` - Property 25: Robots.txt Custom Directives Inclusion
- `MetaProperty26RobotsTxtDefaultDirectivesTest.php` - Property 26: Robots.txt Default Directives Presence
- `MetaProperty27RobotsTxtFormattingTest.php` - Property 27: Robots.txt Formatting

### Text Processing Properties
- `MetaProperty12DescriptionTruncationTest.php` - Property 12: Description Truncation with HTML Stripping

## Running Property Tests

Run all property tests:
```bash
vendor/bin/phpunit tests/properties/MetaProperty*Test.php
```

Run a specific property test:
```bash
vendor/bin/phpunit tests/properties/MetaProperty10TitleFallbackChainTest.php
```

## Property Test Configuration

Each property test should:
- Use `Eris\TestTrait` for property-based testing
- Run minimum 100 iterations per property
- Generate random test data using Eris generators
- Document the property being tested in PHPDoc
- Use the format: `Feature: meta-module-rebuild, Property {number}: {description}`

## Example Property Test Structure

```php
use Eris\Generator;
use Eris\TestTrait;

class MetaProperty10TitleFallbackChainTest extends WP_UnitTestCase {
    use TestTrait;
    
    /**
     * Feature: meta-module-rebuild, Property 10: Title Fallback Chain Completeness
     */
    public function test_title_fallback_chain_completeness() {
        $this->forAll(
            Generator\int(1, 1000), // post_id
            Generator\string() // custom_title
        )->then(function($post_id, $custom_title) {
            // Test implementation
        });
    }
}
```

## References

- [Eris Documentation](https://github.com/giorgiosironi/eris)
- [Property-Based Testing Guide](https://hypothesis.works/articles/what-is-property-based-testing/)
- Meta Module Design Document: `.kiro/specs/meta-module-rebuild/design.md`
