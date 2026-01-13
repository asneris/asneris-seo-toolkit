# Local Help Content System

## Overview
The plugin loads help content from a local JSON file (`help-content.json`), providing consistent, secure, and fast help tips without any external network calls.

## Benefits
✅ **No external dependencies** - Works offline and in any environment  
✅ **Fast loading** - Instant, no network delays  
✅ **Secure** - No remote code execution risks  
✅ **Version controlled** - Help content tracked in Git  
✅ **Review URLs** - Each tip can link to detailed documentation  
✅ **Easy updates** - Edit JSON file, users get updates on plugin upgrade  

## How It Works

### 1. Local JSON File
Content stored at: `clarity-first-seo/help-content.json`

The plugin reads this file on each page load and caches in memory.

### 2. JSON Structure
```json
{
  "version": "1.0.0",
  "last_updated": "2026-01-03",
  "page-diagnostics": {
    "cards": [
      {
        "title": "Card Title",
        "icon": "dashicons-info",
        "content": "<p>HTML content here</p>",
        "review_url": "https://clarityfirstseo.com/docs/page-diagnostics/"
      }
    ]
  }
}
```

### 3. Page IDs
- `page-diagnostics` - Page Diagnostics page
- `site-diagnostics` - Site Diagnostics page  
- `bulk-edit` - Bulk Edit page
- `redirects` - Redirects page
- `robots-txt` - Robots.txt page

### 4. Review URLs
Each help card can include a `review_url` field that displays a "Review Guide" button. This encourages users to learn more before taking action.

**Purpose:**
- Link to detailed documentation
- Provide step-by-step tutorials
- Offer video guides or screenshots
- Give context for advanced features

### 5. Available Icons (WordPress Dashicons)
- `dashicons-info` - Information
- `dashicons-search` - Search/magnifying glass
- `dashicons-lightbulb` - Tips/ideas
- `dashicons-warning` - Warnings/notes
- `dashicons-yes` - Success/checkmark
- `dashicons-admin-links` - Links/connections
- `dashicons-editor-help` - Help/question mark

### 6. Usage in Code

Replace hardcoded sidebar HTML with:
```php
<?php CFSEO_Help_Content::render_sidebar('page-diagnostics'); ?>
```

### 7. Security

- Content is sanitized with `wp_kses_post()`
- Only allows safe HTML tags (p, ul, li, a, strong, em)
- Strips JavaScript and dangerous attributes
- Loads from local file (no external requests)
- Review URLs are validated with `esc_url()`

## Content Update Process

1. **Edit** `help-content.json` in the plugin directory
2. **Test** locally to ensure JSON is valid
3. **Commit** changes to version control
4. **Release** new plugin version
5. **Users update** plugin and get new help content

## Content Guidelines

✅ **Do:**
- Keep tips concise (2-3 sentences max)
- Use bullet lists for multiple points
- Include 2-4 cards per page
- Use proper HTML escaping
- Add `review_url` for detailed documentation
- Test JSON validity before committing

❌ **Don't:**
- Include JavaScript in content
- Use external images (use dashicons)
- Write long paragraphs
- Break HTML structure
- Use inline styles (use classes)

## Example: Adding Review URLs

```json
{
  "title": "Understanding Redirects",
  "icon": "dashicons-info",
  "content": "<p>301 redirects preserve SEO value when URLs change.</p>",
  "review_url": "https://clarityfirstseo.com/docs/redirects-guide/"
}
```

This will display a "Review Guide" button that opens the documentation URL in a new tab.

## Best Practices

### Review URL Strategy
- Link to comprehensive guides for complex topics
- Use consistent documentation URL structure
- Ensure documentation pages are always accessible
- Consider adding video tutorials or screenshots
- Update documentation when features change

### Content Organization
- Start with quick tips (what to do)
- Follow with detailed explanations (why/how)
- End with links to full documentation
- Group related tips in logical order
- Use consistent formatting across all pages

## Fallback System

If `help-content.json` is missing or invalid, the plugin uses hardcoded fallback content in `class-help-content.php`. This ensures help is always available.

## Example Help Card with Review Button

When rendered, each card with a `review_url` shows:
```
┌─────────────────────────────────────┐
│ 📊 About Page Diagnostics           │
│                                     │
│ Analyze a single URL to see...     │
│                                     │
│ [🔗 Review Guide]                   │
└─────────────────────────────────────┘
```

The button opens documentation in a new tab for users to review before proceeding.
