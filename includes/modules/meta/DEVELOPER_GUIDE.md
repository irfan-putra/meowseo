# Developer Guide: Extending the Meta Module Pattern System

## Overview

The Meta Module's Title_Patterns class provides a flexible, extensible system for managing SEO title patterns across your WordPress site. This guide explains how to add custom variables, create custom patterns, and understand the validation rules that ensure pattern integrity.

The pattern system is designed to be extended without modifying core code, using WordPress filters and hooks to inject custom functionality.

## Table of Contents

1. [Adding Custom Variables](#adding-custom-variables)
2. [Adding Custom Patterns](#adding-custom-patterns)
3. [Pattern Validation Rules](#pattern-validation-rules)
4. [Code Examples](#code-examples)
5. [Best Practices](#best-practices)
6. [Testing Custom Patterns](#testing-custom-patterns)

---

## Adding Custom Variables

### Understanding Variables

Variables in title patterns are placeholders enclosed in curly braces that get replaced with dynamic content:

```
{title} {sep} {site_name}
```

The Meta Module ships with 10 built-in variables:
- `{title}` - Post/page title
- `{sep}` - Separator character (default: `|`)
- `{site_name}` - Site name
- `{tagline}` - Site tagline
- `{page}` - Page number for paginated content
- `{term_name}` - Category/tag name
- `{term_description}` - Category/tag description
- `{author_name}` - Author display name
- `{current_year}` - Current year (4 digits)
- `{current_month}` - Current month name

### Adding Custom Variables via Filter

To add custom variables, use the `meowseo_title_pattern_variables` filter:

```php
add_filter('meowseo_title_pattern_variables', function($variables) {
    // Add your custom variables
    $variables['custom_field'] = 'Custom Field Value';
    $variables['post_count'] = 'Post Count';
    
    return $variables;
});
```

### Adding Variables with Dynamic Values

For variables that need to be computed based on context, use a callback:

```php
add_filter('meowseo_title_pattern_variables', function($variables, $context) {
    // Add a variable that depends on the current post
    if (!empty($context['post_id'])) {
        $post_id = $context['post_id'];
        
        // Custom variable: number of comments
        $variables['comment_count'] = get_comments_number($post_id);
        
        // Custom variable: post category
        $categories = get_the_category($post_id);
        $variables['primary_category'] = !empty($categories) 
            ? $categories[0]->name 
            : '';
        
        // Custom variable: reading time estimate
        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $variables['reading_time'] = ceil($word_count / 200); // 200 words per minute
    }
    
    return $variables;
}, 10, 2);
```

### Registering Custom Variables Permanently

For variables that should always be available, register them in your plugin's initialization:

```php
class My_Custom_Variables {
    public static function register() {
        add_filter('meowseo_title_pattern_variables', [
            self::class,
            'add_variables'
        ], 10, 2);
    }
    
    public static function add_variables($variables, $context) {
        // Add custom variables
        $variables['brand_name'] = 'My Brand';
        $variables['location'] = get_option('my_plugin_location', '');
        
        // Add context-aware variables
        if (!empty($context['post_id'])) {
            $variables['post_type_label'] = get_post_type_object(
                get_post_type($context['post_id'])
            )->labels->singular_name;
        }
        
        return $variables;
    }
}

// In your plugin initialization
My_Custom_Variables::register();
```

### Variable Naming Conventions

When creating custom variables, follow these conventions:

- **Use lowercase with underscores**: `{my_custom_var}` not `{MyCustomVar}`
- **Be descriptive**: `{primary_category}` not `{cat}`
- **Avoid conflicts**: Check existing variables before naming
- **Document your variables**: Add comments explaining what they do

### Example: Adding a Custom Variable

Here's a complete example of adding a custom variable for a WooCommerce store:

```php
add_filter('meowseo_title_pattern_variables', function($variables, $context) {
    // Only add WooCommerce variables if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return $variables;
    }
    
    // Add product price variable
    if (!empty($context['post_id'])) {
        $product = wc_get_product($context['post_id']);
        if ($product) {
            $variables['product_price'] = $product->get_price_html();
            $variables['product_rating'] = $product->get_average_rating();
            $variables['product_stock'] = $product->get_stock_status();
        }
    }
    
    return $variables;
}, 10, 2);
```

---

## Adding Custom Patterns

### Understanding Patterns

Patterns are template strings that define how titles should be formatted for different page types. Each pattern contains variables that get replaced with actual values.

### Default Patterns

The Meta Module includes default patterns for common page types:

```php
[
    'post' => '{title} {sep} {site_name}',
    'page' => '{title} {sep} {site_name}',
    'homepage' => '{site_name} {sep} {tagline}',
    'category' => '{term_name} Archives {sep} {site_name}',
    'tag' => '{term_name} Tag {sep} {site_name}',
    'author' => '{author_name} {sep} {site_name}',
    'date' => '{current_month} {current_year} Archives {sep} {site_name}',
    'search' => 'Search Results {sep} {site_name}',
    '404' => 'Page Not Found {sep} {site_name}',
    'attachment' => '{title} {sep} {site_name}'
]
```

### Adding Custom Patterns via Filter

To add or override patterns, use the `meowseo_title_patterns` filter:

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Add a pattern for a custom post type
    $patterns['product'] = '{title} - Buy at {site_name}';
    
    // Override an existing pattern
    $patterns['post'] = '{title} | {site_name} Blog';
    
    return $patterns;
});
```

### Adding Patterns for Custom Post Types

If you have custom post types, add patterns for them:

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Pattern for 'portfolio' custom post type
    $patterns['portfolio'] = '{title} - Portfolio {sep} {site_name}';
    
    // Pattern for 'testimonial' custom post type
    $patterns['testimonial'] = 'Testimonial: {title} {sep} {site_name}';
    
    // Pattern for 'case_study' custom post type
    $patterns['case_study'] = 'Case Study: {title} {sep} {site_name}';
    
    return $patterns;
});
```

### Adding Patterns for Custom Page Types

For non-singular pages (archives, custom pages), add patterns:

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Pattern for WooCommerce product category
    $patterns['product_category'] = '{term_name} Products {sep} {site_name}';
    
    // Pattern for WooCommerce shop page
    $patterns['shop'] = 'Shop {sep} {site_name}';
    
    // Pattern for custom archive
    $patterns['my_custom_archive'] = '{term_name} Archive {sep} {site_name}';
    
    return $patterns;
});
```

### Dynamic Pattern Generation

For complex scenarios, generate patterns dynamically:

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Get all custom post types
    $post_types = get_post_types(['_builtin' => false], 'objects');
    
    foreach ($post_types as $post_type) {
        // Generate a pattern for each custom post type
        $key = $post_type->name;
        if (!isset($patterns[$key])) {
            $patterns[$key] = '{title} {sep} ' . $post_type->labels->name;
        }
    }
    
    return $patterns;
});
```

### Example: Adding Patterns for an E-commerce Site

Here's a complete example for a WooCommerce store:

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Product patterns
    $patterns['product'] = '{title} - {product_price} {sep} {site_name}';
    $patterns['product_category'] = '{term_name} Products {sep} {site_name}';
    $patterns['product_tag'] = 'Products Tagged: {term_name} {sep} {site_name}';
    
    // Shop patterns
    $patterns['shop'] = 'Shop {sep} {site_name}';
    
    // Sale/promotion patterns
    $patterns['sale'] = 'Sale: {title} {sep} {site_name}';
    
    return $patterns;
});
```

---

## Pattern Validation Rules

### Understanding Validation

The Title_Patterns class validates patterns to ensure they are syntactically correct and use only supported variables. Validation happens when patterns are parsed or saved.

### Validation Rules

#### Rule 1: Balanced Curly Braces

All opening braces `{` must have matching closing braces `}`.

**Valid**:
```
{title} {sep} {site_name}
```

**Invalid**:
```
{title {sep} {site_name}        // Missing closing brace
{title} {sep {site_name}        // Missing closing brace
{title}} {sep} {site_name}      // Extra closing brace
```

#### Rule 2: Supported Variables Only

All variables must be from the supported list (built-in or registered via filter).

**Valid**:
```
{title} {sep} {site_name}
{term_name} Archives {sep} {site_name}
```

**Invalid**:
```
{title} {sep} {invalid_variable}
{my_undefined_var} {site_name}
```

#### Rule 3: Proper Variable Syntax

Variables must be enclosed in curly braces with no spaces inside.

**Valid**:
```
{title} {sep} {site_name}
```

**Invalid**:
```
{ title } {sep} {site_name}     // Spaces inside braces
{title} { sep } {site_name}     // Spaces inside braces
```

### Validation Error Handling

When a pattern fails validation, the parser returns an error object:

```php
$result = Title_Patterns::parse('{title {sep} {site_name}');

// Returns:
[
    'error' => true,
    'message' => 'Unbalanced curly braces at position 6'
]
```

### Validating Patterns Programmatically

Use the `validate()` method to check if a pattern is valid:

```php
$patterns = new Title_Patterns($options);

// Check if pattern is valid
$result = $patterns->validate('{title} {sep} {site_name}');

if ($result === true) {
    // Pattern is valid
    echo "Pattern is valid!";
} else {
    // Pattern has errors
    echo "Error: " . $result['message'];
}
```

### Custom Validation Rules

To add custom validation rules, use the `meowseo_validate_title_pattern` filter:

```php
add_filter('meowseo_validate_title_pattern', function($is_valid, $pattern, $variables) {
    // Add custom validation: pattern must not exceed 60 characters
    if (strlen($pattern) > 60) {
        return [
            'error' => true,
            'message' => 'Pattern exceeds 60 character limit'
        ];
    }
    
    // Add custom validation: pattern must contain {title}
    if (strpos($pattern, '{title}') === false) {
        return [
            'error' => true,
            'message' => 'Pattern must contain {title} variable'
        ];
    }
    
    return $is_valid;
}, 10, 3);
```

### Error Handling Best Practices

When working with patterns, always handle validation errors:

```php
$patterns = new Title_Patterns($options);

// Parse a user-provided pattern
$parsed = $patterns->parse($user_pattern);

// Check for errors
if (is_array($parsed) && isset($parsed['error']) && $parsed['error']) {
    // Handle error
    wp_die('Invalid pattern: ' . $parsed['message']);
} else {
    // Pattern is valid, use it
    $title = $patterns->resolve($user_pattern, $context);
}
```

---

## Code Examples

### Example 1: Adding a Custom Variable for Post Metadata

```php
add_filter('meowseo_title_pattern_variables', function($variables, $context) {
    if (!empty($context['post_id'])) {
        $post_id = $context['post_id'];
        
        // Add custom field value as a variable
        $custom_value = get_post_meta($post_id, 'my_custom_field', true);
        $variables['custom_field'] = $custom_value ?: '';
    }
    
    return $variables;
}, 10, 2);

// Now use it in a pattern
add_filter('meowseo_title_patterns', function($patterns) {
    $patterns['post'] = '{custom_field} - {title} {sep} {site_name}';
    return $patterns;
});
```

### Example 2: Adding Variables for a Blog Site

```php
add_filter('meowseo_title_pattern_variables', function($variables, $context) {
    if (!empty($context['post_id'])) {
        $post_id = $context['post_id'];
        
        // Add reading time
        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(strip_tags($content));
        $variables['reading_time'] = ceil($word_count / 200);
        
        // Add primary category
        $categories = get_the_category($post_id);
        $variables['primary_category'] = !empty($categories) 
            ? $categories[0]->name 
            : '';
        
        // Add post format
        $format = get_post_format($post_id);
        $variables['post_format'] = $format ?: 'article';
    }
    
    return $variables;
}, 10, 2);

// Use in patterns
add_filter('meowseo_title_patterns', function($patterns) {
    $patterns['post'] = '{primary_category}: {title} ({reading_time} min read) {sep} {site_name}';
    return $patterns;
});
```

### Example 3: Adding Patterns for a Multi-Language Site

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Get current language (using Polylang)
    if (function_exists('pll_current_language')) {
        $lang = pll_current_language();
        
        // Adjust patterns based on language
        if ($lang === 'es') {
            $patterns['post'] = '{title} - Blog {sep} {site_name}';
            $patterns['category'] = 'Categoría: {term_name} {sep} {site_name}';
        } elseif ($lang === 'fr') {
            $patterns['post'] = '{title} - Blogue {sep} {site_name}';
            $patterns['category'] = 'Catégorie: {term_name} {sep} {site_name}';
        }
    }
    
    return $patterns;
});
```

### Example 4: Conditional Patterns Based on Post Type

```php
add_filter('meowseo_title_patterns', function($patterns) {
    // Get all public post types
    $post_types = get_post_types(['public' => true], 'objects');
    
    foreach ($post_types as $post_type) {
        $key = $post_type->name;
        
        // Skip built-in types
        if ($post_type->_builtin) {
            continue;
        }
        
        // Generate pattern based on post type
        switch ($key) {
            case 'portfolio':
                $patterns[$key] = 'Portfolio: {title} {sep} {site_name}';
                break;
            case 'testimonial':
                $patterns[$key] = 'Testimonial from {author_name} {sep} {site_name}';
                break;
            case 'case_study':
                $patterns[$key] = 'Case Study: {title} {sep} {site_name}';
                break;
            default:
                $patterns[$key] = '{title} {sep} {site_name}';
        }
    }
    
    return $patterns;
});
```

### Example 5: Validating Custom Patterns

```php
add_filter('meowseo_validate_title_pattern', function($is_valid, $pattern, $variables) {
    // Ensure pattern is not too long (SEO best practice: < 60 chars)
    if (strlen($pattern) > 60) {
        return [
            'error' => true,
            'message' => 'Pattern exceeds recommended 60 character limit'
        ];
    }
    
    // Ensure pattern contains at least one variable
    if (!preg_match('/\{[a-z_]+\}/', $pattern)) {
        return [
            'error' => true,
            'message' => 'Pattern must contain at least one variable'
        ];
    }
    
    // Ensure pattern doesn't start with a variable
    if (preg_match('/^\{/', $pattern)) {
        return [
            'error' => true,
            'message' => 'Pattern should not start with a variable'
        ];
    }
    
    return $is_valid;
}, 10, 3);
```

---

## Best Practices

### 1. Variable Naming

- Use lowercase with underscores: `{my_variable}`
- Be descriptive: `{primary_category}` not `{cat}`
- Avoid abbreviations: `{author_name}` not `{auth_nm}`
- Check for conflicts before naming

### 2. Pattern Design

- Keep patterns under 60 characters (SEO best practice)
- Always include `{site_name}` for branding
- Use `{sep}` for consistency
- Test patterns with real data before deploying

### 3. Variable Availability

- Check if variables exist before using them
- Provide fallback values for optional variables
- Document which variables are available for each context

### 4. Performance

- Avoid expensive operations in variable callbacks
- Cache computed values when possible
- Use lazy-loading for complex variables

### 5. Internationalization

- Make patterns translatable using `__()` or `_x()`
- Consider language-specific patterns
- Test with multiple languages

### 6. Documentation

- Document custom variables in code comments
- Explain what each variable represents
- Provide examples of patterns using custom variables

### 7. Error Handling

- Always validate patterns before saving
- Provide clear error messages to users
- Log validation errors for debugging

### 8. Testing

- Test patterns with various post types
- Test with edge cases (empty values, special characters)
- Test with multilingual plugins
- Verify output length doesn't exceed limits

---

## Testing Custom Patterns

### Unit Testing Custom Variables

```php
class Test_Custom_Variables extends WP_UnitTestCase {
    public function test_custom_variable_is_added() {
        // Register custom variable
        add_filter('meowseo_title_pattern_variables', function($variables) {
            $variables['custom_var'] = 'Custom Value';
            return $variables;
        });
        
        // Create patterns instance
        $patterns = new Title_Patterns($this->options);
        
        // Resolve pattern with custom variable
        $result = $patterns->resolve('{custom_var}', []);
        
        // Assert custom variable was replaced
        $this->assertEquals('Custom Value', $result);
    }
    
    public function test_custom_variable_with_context() {
        // Register context-aware custom variable
        add_filter('meowseo_title_pattern_variables', function($variables, $context) {
            if (!empty($context['post_id'])) {
                $variables['post_title'] = get_the_title($context['post_id']);
            }
            return $variables;
        }, 10, 2);
        
        // Create test post
        $post_id = $this->factory->post->create(['post_title' => 'Test Post']);
        
        // Create patterns instance
        $patterns = new Title_Patterns($this->options);
        
        // Resolve pattern with context
        $result = $patterns->resolve('{post_title}', ['post_id' => $post_id]);
        
        // Assert context variable was replaced
        $this->assertEquals('Test Post', $result);
    }
}
```

### Unit Testing Custom Patterns

```php
class Test_Custom_Patterns extends WP_UnitTestCase {
    public function test_custom_pattern_is_applied() {
        // Register custom pattern
        add_filter('meowseo_title_patterns', function($patterns) {
            $patterns['post'] = '{title} - Custom {sep} {site_name}';
            return $patterns;
        });
        
        // Create patterns instance
        $patterns = new Title_Patterns($this->options);
        
        // Get pattern for post type
        $pattern = $patterns->get_pattern_for_post_type('post');
        
        // Assert custom pattern is used
        $this->assertStringContainsString('Custom', $pattern);
    }
    
    public function test_custom_pattern_resolves_correctly() {
        // Register custom pattern
        add_filter('meowseo_title_patterns', function($patterns) {
            $patterns['post'] = '{title} - {primary_category}';
            return $patterns;
        });
        
        // Register custom variable
        add_filter('meowseo_title_pattern_variables', function($variables) {
            $variables['primary_category'] = 'Technology';
            return $variables;
        });
        
        // Create patterns instance
        $patterns = new Title_Patterns($this->options);
        
        // Resolve pattern
        $result = $patterns->resolve('{title} - {primary_category}', []);
        
        // Assert pattern resolved correctly
        $this->assertStringContainsString('Technology', $result);
    }
}
```

### Property-Based Testing Custom Patterns

```php
class Test_Custom_Patterns_Properties extends WP_UnitTestCase {
    use TestTrait;
    
    /**
     * Property: Custom patterns always resolve without errors
     */
    public function test_custom_patterns_resolve_without_errors() {
        // Register custom patterns
        add_filter('meowseo_title_patterns', function($patterns) {
            $patterns['custom_type'] = '{title} {sep} {site_name}';
            return $patterns;
        });
        
        // Create patterns instance
        $patterns = new Title_Patterns($this->options);
        
        $this->forAll(
            Generator\string(),
            Generator\string()
        )->then(function($title, $site_name) {
            $context = [
                'title' => $title,
                'site_name' => $site_name
            ];
            
            // Resolve pattern
            $result = $patterns->resolve('{title} {sep} {site_name}', $context);
            
            // Assert result is a string
            $this->assertIsString($result);
            
            // Assert result is not empty
            $this->assertNotEmpty($result);
        });
    }
}
```

### Integration Testing Custom Patterns

```php
class Test_Custom_Patterns_Integration extends WP_UnitTestCase {
    public function test_custom_pattern_in_meta_output() {
        // Register custom pattern
        add_filter('meowseo_title_patterns', function($patterns) {
            $patterns['post'] = '{title} - {site_name}';
            return $patterns;
        });
        
        // Create test post
        $post_id = $this->factory->post->create(['post_title' => 'Test Post']);
        
        // Set up globals for wp_head
        global $post;
        $post = get_post($post_id);
        setup_postdata($post);
        
        // Capture output
        ob_start();
        do_action('wp_head');
        $output = ob_get_clean();
        
        // Assert custom pattern is in output
        $this->assertStringContainsString('Test Post', $output);
        $this->assertStringContainsString(get_bloginfo('name'), $output);
        
        wp_reset_postdata();
    }
}
```

### Manual Testing Checklist

When testing custom patterns, verify:

- [ ] Pattern syntax is valid (balanced braces)
- [ ] All variables are supported
- [ ] Pattern resolves correctly with test data
- [ ] Output length is reasonable (< 60 chars recommended)
- [ ] Special characters are handled correctly
- [ ] Empty values are handled gracefully
- [ ] Pattern works with all relevant post types
- [ ] Pattern works with multilingual plugins
- [ ] Pattern doesn't break with long values
- [ ] Pattern displays correctly in search results

---

## Troubleshooting

### Custom Variable Not Appearing

**Problem**: Custom variable is not being replaced in pattern

**Solution**:
1. Verify variable is registered with correct filter: `meowseo_title_pattern_variables`
2. Check variable name matches exactly (case-sensitive)
3. Verify context array contains required data
4. Test with `Title_Patterns::resolve()` directly

### Custom Pattern Not Applied

**Problem**: Custom pattern is not being used

**Solution**:
1. Verify pattern is registered with correct filter: `meowseo_title_patterns`
2. Check pattern key matches post type or page type exactly
3. Verify pattern syntax is valid
4. Clear any caching plugins
5. Test with `Title_Patterns::get_pattern_for_post_type()` directly

### Pattern Validation Error

**Problem**: Pattern fails validation

**Solution**:
1. Check for unbalanced curly braces
2. Verify all variables are supported
3. Check for spaces inside braces
4. Use `Title_Patterns::validate()` to get specific error message
5. Review custom validation rules

### Performance Issues

**Problem**: Custom variables are causing slow page loads

**Solution**:
1. Avoid expensive operations in variable callbacks
2. Cache computed values
3. Use lazy-loading for complex variables
4. Profile code to identify bottlenecks
5. Consider moving logic to background tasks

---

## Additional Resources

- [Title_Patterns Class Documentation](class-title-patterns.php)
- [Meta_Resolver Class Documentation](class-meta-resolver.php)
- [Meta Module README](README.md)
- [WordPress Filters Documentation](https://developer.wordpress.org/plugins/hooks/filters/)
- [WordPress Actions Documentation](https://developer.wordpress.org/plugins/hooks/actions/)

---

## Support

For questions or issues with extending the pattern system:

1. Check this guide for common solutions
2. Review code examples for similar use cases
3. Test with property-based tests to verify correctness
4. Check WordPress debug log for errors
5. Contact MeowSEO support with detailed information

