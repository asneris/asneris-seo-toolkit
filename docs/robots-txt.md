# Robots.txt Guide

## What is robots.txt?

The `robots.txt` file is a text file placed at the root of your website (`https://yoursite.com/robots.txt`) that tells search engine crawlers which parts of your site they can or cannot access.

**Key Point**: robots.txt controls **crawling**, NOT **ranking** or **indexing**.

## Important Limitations

### What robots.txt CAN Do
✅ Prevent crawlers from accessing specific URLs
✅ Save crawl budget by blocking unimportant pages
✅ Specify sitemap location
✅ Control crawler behavior (crawl-delay)

### What robots.txt CANNOT Do
❌ Remove pages from search results (use noindex for that)
❌ Hide sensitive content (it can still be indexed without crawling)
❌ Improve your search rankings directly
❌ Block access to images/content (determined by other factors)

## Basic Syntax

### User-agent
Specifies which crawler the rule applies to:
```
User-agent: *          # All crawlers
User-agent: Googlebot  # Only Google
User-agent: Bingbot    # Only Bing
```

### Disallow
Blocks crawlers from accessing URLs:
```
Disallow: /private/      # Block /private/ directory
Disallow: /admin         # Block /admin and subdirectories
Disallow: /*.pdf$        # Block all PDF files
```

### Allow
Explicitly allows crawling (overrides Disallow):
```
Disallow: /admin/
Allow: /admin/public/    # Allow this subfolder
```

### Sitemap
Tells crawlers where to find your sitemap:
```
Sitemap: https://yoursite.com/sitemap.xml
Sitemap: https://yoursite.com/sitemap_index.xml
```

## Default WordPress robots.txt

Clarity-First SEO provides a safe default:

```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: https://yoursite.com/sitemap.xml
```

This configuration:
- Blocks access to WordPress admin area
- Allows AJAX requests (needed for functionality)
- Points to your sitemap

## Common Rules

### Block WordPress Admin
```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
```
Prevents crawlers from wasting time on admin pages.

### Block Plugins and Themes
```
Disallow: /wp-content/plugins/
Disallow: /wp-content/themes/
```
**Caution**: This blocks CSS/JS files that Google needs to render your site properly. Generally not recommended.

### Block Search and Filter URLs
```
Disallow: /*?s=
Disallow: /*?p=
Disallow: /*/feed/
```
Prevents crawling of search results and feed URLs.

### Block Specific Bots
```
User-agent: AhrefsBot
Disallow: /

User-agent: SemrushBot
Disallow: /
```
Blocks specific SEO crawler bots if you want to save bandwidth.

### Crawl Delay
```
User-agent: *
Crawl-delay: 10
```
Asks bots to wait 10 seconds between requests. Use sparingly - can slow discovery.

## Best Practices

### ✅ DO:

1. **Always include your sitemap**
   ```
   Sitemap: https://yoursite.com/sitemap.xml
   ```

2. **Block admin areas**
   ```
   Disallow: /wp-admin/
   Disallow: /wp-login.php
   ```

3. **Keep it simple**
   - Start with defaults
   - Add rules only when needed
   - Don't over-block

4. **Test your changes**
   - Use Google Search Console's robots.txt tester
   - Verify critical pages aren't blocked

5. **Allow CSS and JavaScript**
   ```
   Allow: /wp-content/*.css
   Allow: /wp-content/*.js
   ```

### ❌ DON'T:

1. **Don't use robots.txt to hide sensitive content**
   - Pages can still be indexed without being crawled
   - Use password protection or noindex instead

2. **Don't block CSS/JS files**
   - Google needs these to render your page
   - Blocking them can hurt mobile rankings

3. **Don't block media if you want it in image search**
   - Blocking images prevents them from appearing in Google Images

4. **Don't create complex wildcard rules**
   - They're error-prone
   - Not all bots support advanced syntax

5. **Don't rely on robots.txt for SEO**
   - It's a crawling control tool
   - Use meta tags, canonicals, and noindex for SEO control

## Validation

Clarity-First SEO validates your robots.txt for:

### Syntax Errors
- ✅ Valid User-agent directives
- ✅ Valid Disallow/Allow paths
- ✅ Valid Sitemap URLs
- ❌ Typos or malformed rules

### Best Practice Checks
- ✅ Sitemap location specified
- ✅ Admin areas blocked
- ✅ CSS/JS files allowed
- ⚠️ Warnings for overly restrictive rules

### Common Issues
- ❌ Blocking entire site (`Disallow: /`)
- ❌ Blocking wp-content entirely
- ❌ Missing sitemap reference
- ❌ Blocking search engine crawlers

## Integration with Other Tools

### Validation Checks
The robots.txt editor works with:
- **Site Diagnostics**: Validates canonical and indexing
- **Meta Robots**: Controls indexing (robots.txt only controls crawling)
- **Sitemap**: Referenced in robots.txt
- **Canonical Tags**: Work independently of robots.txt

**Together they answer**: "Can search engines reach, crawl, and understand my site correctly?"

### How They Work Together

**Robots.txt** (crawling):
```
Can the bot access this URL?
```

**Meta Robots** (indexing):
```
Should this page appear in search results?
```

**Canonical** (duplication):
```
Which URL is the preferred version?
```

**Sitemap** (discovery):
```
What URLs should be crawled?
```

## Testing Your robots.txt

### Google Search Console
1. Go to Coverage → robots.txt Tester
2. Test URLs against your robots.txt rules
3. Submit updated robots.txt for re-crawling

### Manual Testing
1. Visit `https://yoursite.com/robots.txt`
2. Verify file loads correctly
3. Check for syntax errors
4. Confirm sitemap URL is absolute

### Common Test Cases
- Test your homepage (should be allowed)
- Test /wp-admin/ (should be blocked)
- Test CSS/JS files (should be allowed)
- Test sitemap URL (should be absolute and valid)

## Example robots.txt Files

### Minimal (Recommended)
```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: https://yoursite.com/sitemap.xml
```

### E-commerce Site
```
User-agent: *
Disallow: /cart/
Disallow: /checkout/
Disallow: /my-account/
Disallow: /*?add-to-cart=
Allow: /wp-content/*.css
Allow: /wp-content/*.js

Sitemap: https://yoursite.com/sitemap.xml
Sitemap: https://yoursite.com/product-sitemap.xml
```

### Blog
```
User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php
Disallow: /author/
Disallow: /*/feed/
Disallow: /*?s=

Sitemap: https://yoursite.com/sitemap.xml
```

### Block Aggressive Bots
```
User-agent: *
Disallow: /wp-admin/

User-agent: AhrefsBot
Disallow: /

User-agent: SemrushBot
Disallow: /

User-agent: DotBot
Disallow: /

Sitemap: https://yoursite.com/sitemap.xml
```

## Frequently Asked Questions

**Q: Will robots.txt remove my site from Google?**
A: No. Blocking crawling doesn't remove already-indexed pages. Use noindex meta tags to remove pages from search results.

**Q: Should I block /wp-content/?**
A: Generally no. Google needs CSS/JS files to render your site. Blocking them can hurt mobile SEO.

**Q: Can I block specific pages?**
A: Yes, but noindex is usually better for controlling what appears in search results.

**Q: How long until changes take effect?**
A: Usually within hours. Google rechecks robots.txt regularly. Force update via Search Console.

**Q: What if I don't have a robots.txt?**
A: That's okay! No robots.txt means "allow everything." It's not required unless you need to block specific paths.

**Q: Can I use robots.txt on a staging site?**
A: Yes. Block everything on staging:
```
User-agent: *
Disallow: /
```
But use noindex too for safety.

## Related Documentation

- [Meta Robots Tags Guide](meta-robots.md)
- [Canonical URLs Explained](canonical-urls.md)
- [Sitemap Configuration](sitemaps.md)
- [Crawl Budget Optimization](crawl-budget.md)
