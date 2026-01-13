# Meta Tag Templates

> **Status:** Template - Content to be added

## Overview

Brief description of the template system for generating SEO meta tags.

## What are Meta Tag Templates?

**Definition:** Dynamic patterns for generating meta tags

**Purpose:** Automate meta tag creation

**Benefits:**
- Benefit 1: Consistency across pages
- Benefit 2: Save time on repetitive tasks
- Benefit 3: Dynamic content insertion

---

## Available Variables

### Page Variables

**{{title}}** - Page/post title

**{{excerpt}}** - Post excerpt

**{{content}}** - Page content (limited)

**{{author}}** - Author name

**{{date}}** - Publication date

**{{modified}}** - Last modified date

---

### Site Variables

**{{site_name}}** - Site name

**{{site_description}}** - Site tagline

**{{site_url}}** - Site URL

---

### Taxonomy Variables

**{{category}}** - Primary category

**{{categories}}** - All categories

**{{tag}}** - Primary tag

**{{tags}}** - All tags

---

## Template Syntax

### Basic Usage

**Simple Variable:** `{{variable}}`

**With Default:** `{{variable|Default Text}}`

**Conditional:** Details on conditional logic

---

## Pre-Built Templates

### Blog Posts

**Title Template Example:**
```
{{title}} | {{site_name}}
```

**Meta Description Template Example:**
```
{{excerpt|Read more about {{title}} on {{site_name}}}}
```

---

### Product Pages

**Title Template Example:**
```
{{title}} - Buy Online | {{site_name}}
```

**Meta Description Template Example:**
```
Shop {{title}}. {{excerpt|}} Free shipping available.
```

---

### Category Archives

**Title Template Example:**
```
{{category}} Articles | {{site_name}}
```

**Meta Description Template Example:**
```
Browse our {{category}} articles and guides.
```

---

### Author Archives

**Title Template Example:**
```
Posts by {{author}} | {{site_name}}
```

**Meta Description Template Example:**
```
Read articles written by {{author}} on {{site_name}}.
```

---

## Configuration

### Setting Templates

#### By Post Type

**Post Type:** Select type

**Title Template:** Pattern for title

**Meta Description Template:** Pattern for description

---

#### By Taxonomy

**Taxonomy:** Select taxonomy

**Title Template:** Pattern for title

**Meta Description Template:** Pattern for description

---

### Template Priority

**Priority Order:**
1. Individual page settings (highest)
2. Template settings
3. Plugin defaults (lowest)

**Override Behavior:** How individual settings override templates

---

## Template Best Practices

### Title Templates

- Best practice 1: Keep under 60 characters
- Best practice 2: Include brand name
- Best practice 3: Use separators (| or -)
- Best practice 4: Most important info first

---

### Meta Description Templates

- Best practice 1: Keep under 160 characters
- Best practice 2: Include call-to-action
- Best practice 3: Use active voice
- Best practice 4: Make it compelling

---

## Testing Templates

### Preview Before Applying

**How to Test:**
1. Step one
2. Step two
3. Step three

**What to Check:**
- Check 1: Length
- Check 2: Readability
- Check 3: Variable substitution

---

## Troubleshooting

### Issue: Variables Not Replacing

**Symptoms:** Variables show as {{variable}}

**Common Causes:**
- Cause 1: Typo in variable name
- Cause 2: Variable not available
- Cause 3: Template syntax error

**Solutions:**
1. Solution step one
2. Solution step two

---

### Issue: Generated Text Too Long

**Symptoms:** Meta tags exceed recommended length

**Common Causes:**
- Cause 1: Long page titles
- Cause 2: Long excerpts
- Cause 3: Too many variables

**Solutions:**
1. Use truncation modifiers
2. Adjust template pattern
3. Set character limits

---

### Issue: Empty Meta Tags

**Symptoms:** Meta tags have no content

**Common Causes:**
- Cause 1: Variable has no value
- Cause 2: Missing default text
- Cause 3: Conditional not met

**Solutions:**
1. Add default values
2. Check variable availability
3. Review conditional logic

---

## Best Practices

- Best practice 1: Test templates on sample content
- Best practice 2: Use defaults for empty variables
- Best practice 3: Keep templates simple
- Best practice 4: Monitor generated output

## Character Limits

**Title Tag:** 50-60 characters optimal

**Meta Description:** 150-160 characters optimal

**Note:** These are recommendations, not hard limits. Search engines may truncate longer text.

## Related Documentation

- [Settings Overview](settings-overview.md)
- [Bulk Edit](bulk-edit.md)
- [Page Diagnostics](page-diagnostics.md)

---

[← Back to Documentation](README.md)
