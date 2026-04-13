# MeowSEO Tests

This directory contains unit tests for the MeowSEO plugin.

## Setup

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run tests:
   ```bash
   ./vendor/bin/phpunit
   ```

   Or use the provided script:
   ```bash
   bash run-tests.sh
   ```

## Test Structure

- `tests/modules/meta/SEOAnalyzerTest.php` - Tests for SEO analysis functionality
- `tests/modules/meta/ReadabilityTest.php` - Tests for readability analysis functionality

## Test Coverage

The tests cover:

### SEO Analyzer
- Focus keyword presence in title, description, first paragraph, headings, and slug
- Meta description length validation (50-160 characters)
- Title length validation (30-60 characters)
- Score calculation and color indicators
- Case-insensitive keyword matching

### Readability Analyzer
- Average sentence length (≤ 20 words)
- Paragraph length (≤ 150 words)
- Transition word usage (≥ 30% of sentences)
- Passive voice detection (≤ 10% of sentences)
- Score calculation and color indicators
- HTML and shortcode stripping

## Requirements

- PHP 8.0 or higher
- Composer
- PHPUnit 9.5 or higher
