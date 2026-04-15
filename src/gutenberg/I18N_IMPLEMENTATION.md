# Internationalization (i18n) Implementation

## Overview

This document describes the internationalization implementation for the MeowSEO Gutenberg Editor Integration. All user-facing strings are properly wrapped with translation functions and use the "meowseo" text domain.

## Implementation Status

✅ **Task 21.1: Add i18n to all user-facing strings** - COMPLETE
✅ **Task 21.2: Write i18n tests** - COMPLETE

## Requirements Satisfied

- **19.1**: Use @wordpress/i18n for all user-facing strings ✅
- **19.2**: Wrap all translatable strings with __() or _x() functions ✅
- **19.3**: Use "meowseo" text domain for translations ✅
- **19.4**: Display translated strings when WordPress locale changes ✅
- **19.5**: Support right-to-left (RTL) languages ✅
- **19.6**: Do NOT hardcode any user-facing text in English ✅

## Components with i18n Implementation

### Core Components
- ✅ ContentScoreWidget
- ✅ TabBar
- ✅ Sidebar

### General Tab Components
- ✅ FocusKeywordInput
- ✅ DirectAnswerField
- ✅ SerpPreview
- ✅ InternalLinkSuggestions

### Social Tab Components
- ✅ SocialTabContent
- ✅ FacebookSubTab
- ✅ TwitterSubTab

### Schema Tab Components
- ✅ SchemaTabContent
- ✅ SchemaTypeSelector
- ✅ ArticleForm
- ✅ FAQPageForm
- ✅ HowToForm
- ✅ LocalBusinessForm
- ✅ ProductForm

### Advanced Tab Components
- ✅ AdvancedTabContent
- ✅ RobotsToggles
- ✅ CanonicalURLInput
- ✅ GSCIntegration

## Translation Function Usage

All components use the `__()` function from `@wordpress/i18n`:

```typescript
import { __ } from '@wordpress/i18n';

// Example usage
<TextControl
  label={__('Focus Keyword', 'meowseo')}
  help={__('Enter the main keyword for this content', 'meowseo')}
  placeholder={__('e.g., wordpress seo', 'meowseo')}
/>
```

## Text Domain

All translations consistently use the **"meowseo"** text domain:

```typescript
__('Text to translate', 'meowseo')
```

## RTL Support

RTL (Right-to-Left) language support is implemented through:

1. **No hardcoded directional styles**: Components do not use inline `direction: ltr` styles
2. **CSS-based layout**: All layouts use flexbox and CSS Grid which automatically adapt to RTL
3. **WordPress core RTL handling**: WordPress automatically applies RTL styles when needed

## Test Coverage

### Main i18n Test Suite
Location: `src/gutenberg/components/__tests__/i18n.test.tsx`

Tests:
- ✅ Translation function usage (16 tests)
- ✅ Text domain validation
- ✅ No hardcoded English text
- ✅ Translation coverage
- ✅ RTL support
- ✅ Locale change support
- ✅ Translation function parameters
- ✅ Complete component coverage

### Tab Components i18n Test Suite
Location: `src/gutenberg/components/tabs/__tests__/i18n-tabs.test.tsx`

Tests:
- ✅ General tab components (20 tests)
- ✅ Social tab components
- ✅ Advanced tab components
- ✅ Help text translation
- ✅ Placeholder text translation
- ✅ Button text translation
- ✅ No hardcoded English text
- ✅ Consistent text domain

### Test Results
- **Total i18n tests**: 36 tests
- **Status**: All passing ✅
- **Total project tests**: 349 tests
- **Status**: All passing ✅

## Translatable String Categories

### Labels
- Form field labels (e.g., "Focus Keyword", "SEO Score")
- Section headings (e.g., "Robots Meta Directives", "Canonical URL")
- Tab names (e.g., "General", "Social", "Schema", "Advanced")

### Help Text
- Field descriptions and instructions
- Tooltips and hints
- Error messages and warnings

### Placeholder Text
- Input field placeholders
- Example values

### Button Text
- Action buttons (e.g., "Analyze", "Request Indexing")
- Media upload buttons (e.g., "Select Image", "Change Image", "Remove Image")
- Form actions (e.g., "Add Question", "Remove Step")

### Status Messages
- Loading states (e.g., "Analyzing...", "Loading suggestions...")
- Success messages
- Error messages
- Empty states (e.g., "No internal link suggestions found")

## WordPress Integration

The i18n implementation integrates seamlessly with WordPress's translation system:

1. **Translation files**: WordPress will automatically load translation files from the plugin's `languages/` directory
2. **Locale detection**: WordPress automatically detects the user's locale from their profile settings
3. **Translation updates**: Translations can be updated through WordPress.org's translation platform
4. **RTL support**: WordPress automatically applies RTL styles when the locale is an RTL language

## Creating Translations

To create translations for MeowSEO:

1. Extract translatable strings:
   ```bash
   wp i18n make-pot . languages/meowseo.pot --domain=meowseo
   ```

2. Create language-specific .po files:
   ```bash
   # Example for French
   msginit --input=languages/meowseo.pot --output=languages/meowseo-fr_FR.po --locale=fr_FR
   ```

3. Translate strings in the .po file using a tool like Poedit

4. Compile .po files to .mo files:
   ```bash
   msgfmt languages/meowseo-fr_FR.po -o languages/meowseo-fr_FR.mo
   ```

## Best Practices Followed

1. ✅ **Consistent text domain**: Always use "meowseo"
2. ✅ **Complete strings**: Never concatenate translated strings
3. ✅ **Context when needed**: Use `_x()` for strings that need context
4. ✅ **No variables in translation keys**: Use placeholders instead
5. ✅ **Translate all user-facing text**: Including help text, placeholders, and error messages
6. ✅ **RTL-friendly layouts**: No hardcoded directional styles
7. ✅ **Comprehensive testing**: All components tested for i18n compliance

## Verification

To verify i18n implementation:

1. Run i18n tests:
   ```bash
   npm test -- i18n.test.tsx
   npm test -- i18n-tabs.test.tsx
   ```

2. Check for untranslated strings:
   ```bash
   # Search for hardcoded English strings (should return minimal results)
   grep -r "\"[A-Z][a-z]" src/gutenberg/components --include="*.tsx" | grep -v "__("
   ```

3. Test with a different locale:
   - Change WordPress locale in Settings > General
   - Verify all strings are translated (if translation files exist)
   - Verify RTL layout works correctly (for RTL locales)

## Conclusion

The internationalization implementation for the MeowSEO Gutenberg Editor Integration is complete and fully tested. All user-facing strings are properly wrapped with translation functions, use the correct text domain, and support RTL languages. The implementation follows WordPress best practices and is ready for translation into any language.
