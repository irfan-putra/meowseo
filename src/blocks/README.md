# MeowSEO Gutenberg Blocks

This directory contains the React/TypeScript implementation of MeowSEO Gutenberg blocks.

## Blocks

### 1. Estimated Reading Time (`meowseo/estimated-reading-time`)

Displays the estimated reading time for the current post based on word count and reading speed.

**Features:**
- Configurable words per minute (150-300, default 200)
- Optional icon display
- Custom text prefix
- Alignment options (left, center, right)
- Accessible with ARIA labels and live regions

**Attributes:**
```typescript
{
  wordsPerMinute: number;      // 150-300, default 200
  showIcon: boolean;           // default true
  customText: string;          // optional prefix text
  alignment: string;           // 'left' | 'center' | 'right'
}
```

### 2. Related Posts (`meowseo/related-posts`)

Displays posts related by keyword, category, or tag.

**Features:**
- Multiple relationship types (keyword, category, tag)
- Configurable number of posts (1-10)
- Display styles (list or grid)
- Optional excerpt and thumbnail display
- Server-side rendering for performance

**Attributes:**
```typescript
{
  numberOfPosts: number;       // 1-10, default 3
  displayStyle: string;        // 'list' | 'grid'
  showExcerpt: boolean;        // default true
  showThumbnail: boolean;      // default true
  relationshipType: string;    // 'keyword' | 'category' | 'tag'
}
```

### 3. Siblings (`meowseo/siblings`)

Displays pages with the same parent page.

**Features:**
- Configurable ordering (menu order, title, date)
- Optional thumbnail display
- Semantic navigation markup
- Accessible list structure

**Attributes:**
```typescript
{
  showThumbnails: boolean;     // default true
  orderBy: string;             // 'menu_order' | 'title' | 'date'
}
```

### 4. Subpages (`meowseo/subpages`)

Displays child pages of the current page.

**Features:**
- Configurable depth (1-3 levels)
- Optional thumbnail display
- Hierarchical indentation
- Recursive page traversal

**Attributes:**
```typescript
{
  depth: number;               // 1-3, default 1
  showThumbnails: boolean;     // default true
}
```

## Development

### Setup

```bash
cd src/blocks
npm install
```

### Build

```bash
npm run build
```

### Development Server

```bash
npm start
```

### Linting

```bash
npm run lint:js
npm run lint:css
```

## Architecture

### Directory Structure

```
src/blocks/
├── src/
│   ├── index.ts                          # Main entry point
│   ├── utils/
│   │   ├── accessibility.ts              # Accessibility utilities
│   │   └── content.ts                    # Content analysis utilities
│   ├── estimated-reading-time/
│   │   ├── index.ts                      # Block registration
│   │   ├── edit.tsx                      # Editor component
│   │   ├── save.tsx                      # Save component
│   │   └── editor.css                    # Editor styles
│   ├── related-posts/
│   │   ├── index.ts
│   │   ├── edit.tsx
│   │   ├── save.tsx
│   │   └── editor.css
│   ├── siblings/
│   │   ├── index.ts
│   │   ├── edit.tsx
│   │   ├── save.tsx
│   │   └── editor.css
│   └── subpages/
│       ├── index.ts
│       ├── edit.tsx
│       ├── save.tsx
│       └── editor.css
├── package.json
├── tsconfig.json
└── webpack.config.js
```

### Utilities

#### Accessibility (`utils/accessibility.ts`)

- `generateUniqueId()` - Generate unique IDs for ARIA labels
- `createAccessibleHeading()` - Create semantic headings with proper hierarchy
- `createAccessibleButton()` - Create accessible buttons with keyboard support
- `createAccessibleList()` - Create accessible lists with ARIA attributes
- `announceToScreenReader()` - Announce dynamic content changes

#### Content (`utils/content.ts`)

- `calculateReadingTime()` - Calculate reading time from word count
- `formatReadingTime()` - Format reading time for display
- `extractKeywords()` - Extract keywords from content
- `calculateSimilarity()` - Calculate content similarity

## Accessibility

All blocks implement WCAG 2.1 AA accessibility standards:

- **Semantic HTML**: Proper heading hierarchy, semantic elements
- **ARIA Labels**: Descriptive labels for interactive elements
- **Keyboard Navigation**: Full keyboard support for all interactions
- **Screen Reader Support**: Proper ARIA roles and live regions
- **Focus Management**: Clear focus indicators and focus trapping
- **Color Contrast**: Sufficient contrast ratios for text and UI elements

## Server-Side Rendering

Blocks use server-side rendering for performance:

1. **Edit Component**: React component for editor UI
2. **Save Component**: Minimal markup for storage
3. **PHP Renderer**: Server-side rendering for frontend display

REST endpoints provide data for dynamic blocks:
- `/meowseo/v1/related-posts` - Get related posts
- `/meowseo/v1/siblings` - Get sibling pages
- `/meowseo/v1/subpages` - Get subpages

## Performance

- **Caching**: Server-side caching of related posts queries
- **Lazy Loading**: Images use native lazy loading
- **Code Splitting**: Each block is a separate entry point
- **Minification**: Production builds are minified

## Testing

### Unit Tests

```bash
npm test
```

### Accessibility Testing

- Automated: axe-core integration
- Manual: Keyboard navigation, screen reader testing

### Browser Compatibility

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Internationalization

All blocks support WordPress i18n:

```typescript
import { __ } from '@wordpress/i18n';

__('Block Label', 'meowseo')
```

Translations are loaded via `wp_set_script_translations()`.

## Contributing

When adding new blocks:

1. Create a new directory under `src/`
2. Implement `index.ts`, `edit.tsx`, `save.tsx`
3. Add styles in `editor.css`
4. Register in `src/index.ts`
5. Add REST endpoint in PHP if needed
6. Update this README

## License

GPL-2.0-or-later
