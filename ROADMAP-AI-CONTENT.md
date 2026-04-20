# MeowSEO — AI Content Features Roadmap

> This roadmap covers all AI-powered **content writing** features: article rewriting,
> content expansion, paragraph improvement, inline image generation, and bulk operations.
> Every feature is planned for **both the Gutenberg block editor and the Classic Editor**.

---

## Current AI Capabilities (Already Built)

| Feature | Gutenberg | Classic Editor |
|---|---|---|
| AI-generate SEO title | ✅ | ✅ (M5, ROADMAP-CLASSIC-EDITOR) |
| AI-generate meta description | ✅ | ✅ (M5, ROADMAP-CLASSIC-EDITOR) |
| AI-generate OG / Twitter fields | ✅ | ✅ (M5, ROADMAP-CLASSIC-EDITOR) |
| AI-generate focus keyword | ✅ | — |
| AI-generate direct answer | ✅ | — |
| AI-generate schema type | ✅ | — |
| AI-generate featured image | ✅ | — |
| 5 AI providers + fallback | ✅ | ✅ (shared backend) |
| Auto-generate on first save | ✅ | ✅ (shared backend) |

**What is NOT yet built (this roadmap):**

| Feature | Gutenberg | Classic Editor |
|---|---|---|
| Rewrite entire article | ❌ | ❌ |
| Expand / lengthen content | ❌ | ❌ |
| Improve selected paragraph | ❌ | ❌ |
| Summarize article | ❌ | ❌ |
| Generate inline article images | ❌ | ❌ |
| Generate featured image from Classic Editor | ❌ | ❌ |
| Bulk AI (multiple posts at once) | ❌ | ❌ |

---

## Architecture

### Backend (shared by both editors)

One new REST endpoint handles all content-writing actions:

```
POST /meowseo/v1/ai/content
```

**Request body:**

```json
{
  "post_id":    123,
  "action":     "rewrite | expand | improve | summarize | image-inline",
  "content":    "<p>Selected or full article HTML</p>",
  "context":    "Optional surrounding context for improve/inline-image",
  "options": {
    "tone":     "professional | casual | academic | persuasive",
    "length":   "short | medium | long",
    "language": "auto | en | id | ..."
  }
}
```

**Response:**

```json
{
  "result":    "<p>AI-generated content HTML</p>",
  "provider":  "openai",
  "action":    "rewrite",
  "word_count": 420
}
```

For `image-inline`:

```json
{
  "attachment_id": 456,
  "url":           "https://example.com/wp-content/uploads/...",
  "alt":           "AI-generated alt text",
  "provider":      "dalle"
}
```

**New PHP files needed:**

```
includes/modules/ai/
├── class-ai-content-rest.php     ← new REST endpoint handler
├── class-ai-content-generator.php ← orchestrates prompts + providers
└── class-ai-content-prompt.php   ← prompt templates per action
```

The existing `AI_Provider_Manager` and `AI_Generator` classes are reused — no
new provider integrations needed. Text actions use the configured text provider
(OpenAI / Claude / Gemini). Image-inline uses the configured image provider
(DALL-E / Imagen).

---

## Milestone 1 — Content Rewrite & Expand (Gutenberg)

**Effort:** Medium  
**Depends on:** Backend new endpoint (above)

### 1.1 — "Content AI" Sidebar Panel (Gutenberg)

A new collapsible panel inside the existing MeowSEO Gutenberg sidebar, below
the Analysis tab. It contains action buttons that operate on the **entire post
content**.

```
┌─ MeowSEO Sidebar ──────────────────┐
│  [Meta] [Analysis] [Social] [Schema]│
│  [Content AI]  ← new tab/panel      │
│                                     │
│  ✨ Rewrite Article                 │
│  ✨ Expand Article                  │
│  ✨ Summarize                       │
│                                     │
│  Tone: [Professional ▼]             │
│  Length: [Medium ▼]                 │
│                                     │
│  [ Generate ]                       │
│                                     │
│  ┌─ Preview ──────────────────────┐ │
│  │ The AI-rewritten article...    │ │
│  │                                │ │
│  │  [ Apply to Editor ]           │ │
│  │  [ Discard ]                   │ │
│  └────────────────────────────────┘ │
└─────────────────────────────────────┘
```

**Work items:**

- `src/ai/components/ContentAiPanel.tsx` — new React component
- Options: action (rewrite/expand/summarize), tone, length
- Reads full post content via `wp.data.select('core/editor').getEditedPostContent()`
- Calls `POST /meowseo/v1/ai/content`
- Shows diff-style preview (original vs generated side-by-side toggle)
- "Apply" calls `wp.data.dispatch('core/editor').editPost({ content: result })`
- Spinner + cancel button during generation
- Undo support: applying registers an undo step in the block editor

### 1.2 — Rewrite Entire Article

Sends the full HTML content to AI with instruction:
> "Rewrite this article keeping all factual information, improving clarity and
> SEO value. Maintain approximately the same length."

Returns rewritten HTML. User previews and applies.

### 1.3 — Expand Article

Sends the full HTML content with instruction:
> "Expand this article by approximately 50%. Add more detail, examples, and
> supporting information. Maintain consistent tone and structure."

Target: adds ~300–500 words on a typical 600-word article.

### 1.4 — Summarize Article

Sends full content with instruction:
> "Write a concise 2–3 paragraph summary of this article suitable for use
> as the meta description or introduction."

Result is shown in preview; user can optionally copy it to the Meta Description
field with one click.

---

## Milestone 2 — Paragraph-Level AI Tools (Gutenberg)

**Effort:** Medium  
**Depends on:** Milestone 1 backend

### 2.1 — Block Toolbar "AI" Button

When a Paragraph block is selected, a new "✨" button appears in the block
toolbar. Clicking it opens a small popover with four actions:

```
┌──────────────────────┐
│  ✨ Improve          │
│  ✨ Expand           │
│  ✨ Simplify         │
│  ✨ Change Tone…     │
└──────────────────────┘
```

**Work items:**

- `src/gutenberg/components/BlockToolbarAI.tsx` — new component
- Register via `@wordpress/hooks`: `addFilter('editor.BlockEdit', 'meowseo/block-toolbar-ai', ...)`
- Only shown on `core/paragraph`, `core/heading`, `core/list` blocks
- Selected block content sent to `POST /meowseo/v1/ai/content`
- Result replaces current block content after user confirms

### 2.2 — Improve

Sends selected paragraph with instruction:
> "Improve this paragraph: fix grammar, improve flow, make it more engaging.
> Keep approximately the same length."

Inline diff preview (strike-through original, green new text) before applying.

### 2.3 — Expand

Same as article expand but scoped to a single paragraph. Adds 1–2 sentences.

### 2.4 — Simplify

> "Rewrite this paragraph at a lower reading level (Flesch score > 70).
> Use shorter sentences and simpler vocabulary."

### 2.5 — Change Tone

Sub-menu: Professional / Casual / Academic / Persuasive / Friendly.
Rewrites the paragraph in the chosen tone.

---

## Milestone 3 — Inline Image Generation (Gutenberg)

**Effort:** Medium  
**Depends on:** existing image providers (DALL-E / Imagen)

### 3.1 — "Generate Image" Block Appender

After every Paragraph block, a subtle "+🖼" button appears on hover. Clicking
it inserts a new Image block below the paragraph and immediately calls the AI
to generate an image based on that paragraph's content.

```
  ┌─────────────────────────────────┐
  │  Paragraph block content here…  │
  └─────────────────────────────────┘
  [+ Image]  ← appears on hover

→ Inserts Image block + triggers generation
→ Shows skeleton loader while generating
→ Fills block with generated image
```

### 3.2 — Image Block Toolbar "Regenerate"

When an Image block has no `src` yet, or when the user right-clicks a
MeowSEO-generated image, the block toolbar shows:

```
[✨ Regenerate] [Edit Prompt] [Style ▼]
```

- **Regenerate:** calls AI again with same context
- **Edit Prompt:** opens a text input pre-filled with the auto-prompt; user edits and confirms
- **Style:** Photography / Illustration / Minimal / Modern / Professional

### 3.3 — Auto Alt Text

After any image is generated or inserted into the editor, MeowSEO automatically
suggests alt text based on:
1. The paragraph context above the image
2. The image prompt used
3. The post's focus keyword

The alt text suggestion appears as a block notice — one click to accept.

### 3.4 — Featured Image from Classic Toolbar (bridge to Classic Editor M3)

The Gutenberg "Set featured image" panel in Document Settings gets a
"✨ Generate with AI" button, identical to what already exists in the
MeowSEO sidebar panel — this is for users who don't open the sidebar.

---

## Milestone 4 — Content AI for Classic Editor

**Effort:** Medium  
**Depends on:** Milestone 1 backend

The Classic Editor uses **TinyMCE** for the content area. All content AI
features must hook into TinyMCE's plugin API — no React, no block editor.

### 4.1 — TinyMCE Toolbar Plugin

A new TinyMCE plugin adds a "MeowSEO AI" dropdown button to the TinyMCE
toolbar (row 2). This is registered via `mce_buttons_2` filter in PHP.

```
TinyMCE row 2:
[ Bold ] [ Italic ] [ … ] [ ✨ MeowSEO AI ▼ ]
                                    │
                                    ├── Rewrite Article
                                    ├── Expand Article
                                    ├── Summarize
                                    ├── ──────────────
                                    ├── Improve Selection
                                    ├── Expand Selection
                                    ├── Simplify Selection
                                    └── Change Tone…
```

**Work items:**

- `assets/js/tinymce-ai-plugin.js` *(new)* — TinyMCE plugin code
- `assets/css/tinymce-ai-plugin.css` *(new)* — dropdown + modal styles
- PHP: register plugin via `add_filter('mce_external_plugins', ...)`
- PHP: add button via `add_filter('mce_buttons_2', ...)`
- Enqueue only on `post.php` and `post-new.php`

### 4.2 — Rewrite & Expand Article (Classic)

"Rewrite Article" and "Expand Article" items operate on the **entire TinyMCE
content**:

```js
// Get full content
var content = tinymce.activeEditor.getContent();

// Call REST API
jQuery.ajax({
  url: meowseoClassic.restUrl + '/ai/content',
  method: 'POST',
  data: JSON.stringify({ post_id, action: 'rewrite', content }),
  ...
}).done(function(data) {
  showContentPreviewModal(data.result);
});
```

A modal dialog shows the result. Buttons: "Apply" / "Discard".  
"Apply" calls `tinymce.activeEditor.setContent(result)`.

### 4.3 — Improve / Expand / Simplify Selection (Classic)

When the user selects text in TinyMCE before clicking the toolbar button,
the selection-level actions become available.

```js
var selected = tinymce.activeEditor.selection.getContent({ format: 'html' });
if (!selected) {
  // Show "no text selected" notice — full-article actions only
  return;
}
// Call REST API with selected content + surrounding context
```

After AI responds, the modal shows original vs AI result side-by-side.  
"Apply" calls `tinymce.activeEditor.selection.setContent(result)`.

### 4.4 — Inline Image Generation (Classic)

A "Generate Image" item in the MeowSEO AI toolbar dropdown opens a modal:

```
┌─ Generate Inline Image ──────────────┐
│                                      │
│  Context from surrounding text:      │
│  ┌────────────────────────────────┐  │
│  │ "The article discusses..."     │  │
│  └────────────────────────────────┘  │
│                                      │
│  Custom prompt (optional):           │
│  [________________________________]  │
│                                      │
│  Style: [Professional ▼]             │
│                                      │
│  [ Generate ]                        │
│                                      │
│  [Preview image after generation]    │
│  [ Insert into Article ]             │
│  [ Set as Featured Image ]           │
│  [ Discard ]                         │
└──────────────────────────────────────┘
```

On "Insert into Article":
```js
var img = '<img src="' + url + '" alt="' + alt + '" />';
tinymce.activeEditor.insertContent(img);
```

On "Set as Featured Image": calls WP AJAX to set the attachment as the post's
featured image, then updates the featured image preview in the classic editor.

### 4.5 — Featured Image from Classic Editor AI (Complete Parity)

The classic editor meta box (from ROADMAP-CLASSIC-EDITOR.md) gets a new section
in the **General tab**:

```html
<div class="meowseo-section-heading">Featured Image</div>
<div class="meowseo-field">
  <img id="meowseo-featured-img-preview" ... />
  <button type="button" id="meowseo-generate-featured">✨ Generate with AI</button>
  <button type="button" id="meowseo-set-featured">Set as Featured Image</button>
  <span id="meowseo-featured-status"></span>
</div>
```

JS calls `POST /meowseo/v1/ai/generate-image` (existing endpoint),
previews the result, then on confirm calls WP AJAX
`wp.ajax.post('set-post-thumbnail', { post_id, thumbnail_id })`.

---

## Milestone 5 — Bulk AI Operations

**Effort:** Large  
**Entry point:** WordPress Admin → MeowSEO → Bulk AI  
**Note:** Bulk AI is admin-screen-only (not Gutenberg or Classic Editor).

### 5.1 — Bulk AI Admin Page

A new admin page under the MeowSEO menu:

```
MeowSEO
  ├── Dashboard
  ├── Settings
  └── Bulk AI  ← new
```

The page shows a table of all posts/pages with their current SEO status:

```
[ ] Post Title            | SEO Score | Has Featured Img | AI Status
───────────────────────────────────────────────────────────────────
[x] My First Blog Post    | 42 / 100  | No               | —
[x] About Us              | 0         | Yes              | —
[ ] Contact               | 88 / 100  | Yes              | Done
```

### 5.2 — Bulk Actions

Checkboxes to select posts, then a "Run AI" button with action selector:

- **Generate missing SEO metadata** — only processes posts where `_meowseo_title` is empty
- **Regenerate all SEO metadata** — overwrites existing metadata
- **Generate missing featured images** — only posts with no featured image
- **Rewrite all selected articles** — processes content one by one
- **Expand all selected articles** — adds length to each selected post

### 5.3 — Queue Processing

Bulk operations process posts one at a time (not in parallel) to respect
API rate limits:

```
Processing: "My First Blog Post" (1 of 5)
████████░░░░░░░░ 40%

[ Cancel ]
```

PHP side: uses `WP_Background_Process` pattern — each batch is a single post,
processed on `admin_post` or via REST with progress polling.

### 5.4 — Results Log

After completion, a results table shows:

| Post | Action | Status | Provider | Time |
|---|---|---|---|---|
| My First Blog Post | Generate metadata | ✅ Done | openai | 1.2s |
| About Us | Generate featured image | ✅ Done | dalle | 3.8s |
| Contact | Generate metadata | ⚠ Skipped (exists) | — | — |

Downloadable as CSV.

---

## File Structure After All Milestones

```
includes/modules/ai/
├── class-ai-content-rest.php          ← new: /ai/content endpoint
├── class-ai-content-generator.php    ← new: rewrite/expand/improve logic
├── class-ai-content-prompt.php       ← new: prompt templates
├── class-ai-bulk.php                  ← new: bulk processing
└── class-ai-bulk-rest.php             ← new: bulk REST endpoints

src/
├── gutenberg/
│   └── components/
│       ├── BlockToolbarAI.tsx         ← new: paragraph toolbar button
│       └── ContentAiPanel.tsx         ← new: sidebar Content AI tab
└── ai/
    └── components/
        └── InlineImageButton.tsx      ← new: +🖼 after paragraph blocks

assets/
├── js/
│   └── tinymce-ai-plugin.js           ← new: TinyMCE plugin
└── css/
    └── tinymce-ai-plugin.css          ← new: TinyMCE modal styles

admin/
└── views/
    └── bulk-ai.php                    ← new: Bulk AI admin page
```

---

## Milestone Summary

| # | Milestone | Gutenberg | Classic Editor | Effort |
|---|---|---|---|---|
| **M1** | Content AI sidebar panel (rewrite, expand, summarize) | ✅ | — | Medium |
| **M2** | Paragraph-level AI block toolbar (improve, simplify, tone) | ✅ | — | Medium |
| **M3** | Inline image generation + auto alt text | ✅ | — | Medium |
| **M4** | Full content AI for Classic Editor (TinyMCE plugin + inline images + featured image generation) | — | ✅ | Medium |
| **M5** | Bulk AI admin page (process multiple posts at once) | — | — | Large |

Total new REST endpoints: **2** (`/ai/content`, `/ai/bulk`)  
Total new PHP files: **5**  
Total new JS/TS files: **5**  
Total new admin pages: **1**

---

## Parity vs Competitors After All Milestones

| Feature | Yoast Premium | RankMath Pro | MeowSEO (target) |
|---|---|---|---|
| AI SEO metadata generation | ✅ | ✅ | ✅ exists |
| AI title / description in classic editor | ✅ | ✅ | ✅ M4 |
| Article rewrite | ❌ | ✅ | ✅ M1 + M4 |
| Article expand | ❌ | ✅ | ✅ M1 + M4 |
| Paragraph improve (inline) | ❌ | ✅ | ✅ M2 + M4 |
| Featured image generation | ❌ | ❌ | ✅ exists |
| Inline article image generation | ❌ | ❌ | ✅ M3 + M4 |
| Auto alt text suggestion | ❌ | ❌ | ✅ M3 |
| Bulk AI operations | ❌ | ✅ | ✅ M5 |
| 5 AI providers + fallback | ❌ (1 provider) | ❌ (1 provider) | ✅ exists |

After this roadmap, MeowSEO leads both competitors on AI features — particularly
on **inline image generation** and **multi-provider support**, which neither
Yoast nor RankMath offers.
