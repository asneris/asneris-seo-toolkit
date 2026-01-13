# Page Diagnostics Guide

## About Page Diagnostics

The Page Diagnostics tool allows you to analyze a single URL to see exactly what meta tags, canonicals, and schema data are detected. This is a fact-based diagnostic tool that shows what search engines see when they crawl your pages.

## How to Use

1. **Select a page** from the dropdown menu (shows your published posts/pages)
2. **Enter a custom URL** if you want to test a specific page
3. **Click "Run Diagnostics"** to fetch and analyze the page
4. **Review results** across all diagnostic sections

## What You'll See

### Fetch Results
- **HTTP Status Code**: Should be 200 for accessible pages
- **Redirects**: Shows if the URL redirects to another location
- **Response Time**: How long the page took to load
- **Final URL**: The ultimate destination after any redirects

### Canonical URL
- **Canonical Tag**: The canonical URL declared in the page
- **Target Status**: HTTP status of the canonical target
- **URL Consistency**: Whether canonical matches the actual URL
- **Self-referencing**: Checks if canonical points to itself (recommended)

### Indexing Signals
- **Robots Meta Tag**: Shows `index/noindex` and `follow/nofollow`
- **X-Robots-Tag**: HTTP header-based robots directives
- **Indexability**: Summary of whether the page can be indexed

### Meta Tags & Schema
- **Title Tag**: The page title (should be unique and under 60 chars)
- **Meta Description**: Description for search results (under 160 chars)
- **Open Graph Tags**: Social media preview tags (og:title, og:image, etc.)
- **Twitter Cards**: Twitter-specific meta tags
- **JSON-LD Schema**: Structured data found on the page

## Important Notes

### This Tool Shows Facts Only

Page Diagnostics is a **diagnostic tool**, not a scoring or grading system. It shows:
- ✅ What IS present on the page
- ❌ What is NOT present on the page
- 📊 The actual values of detected elements

**No pass/fail judgments** - you interpret the facts based on your SEO strategy.

### Understanding the Data

- **Empty fields** mean the element wasn't detected
- **Multiple values** may indicate conflicts (e.g., multiple canonical tags)
- **HTTP errors** (404, 500, etc.) prevent proper analysis
- **Redirects** may affect which page is actually analyzed

## Common Use Cases

### Before Publishing
Test a page before making it live to ensure:
- Proper meta tags are set
- Canonical URL is correct
- No noindex tags blocking indexing
- Schema markup is properly implemented

### Troubleshooting
Diagnose why a page isn't appearing in search results:
- Check HTTP status (should be 200)
- Verify no noindex directives
- Confirm canonical isn't pointing elsewhere
- Ensure meta tags are present

### Auditing
Regular checks to maintain SEO health:
- Homepage and key landing pages
- Recently updated content
- Pages with schema markup
- Pages with social sharing

## Best Practices

1. **Test your homepage first** - it's the most important page
2. **Check key landing pages regularly** - ensure they remain optimized
3. **Test after major updates** - verify changes didn't break SEO elements
4. **Use with Site Diagnostics** - combine with full-site validation
5. **Document findings** - track issues and improvements over time

## Related Tools

- **Site Diagnostics**: Validate multiple pages at once
- **Bulk Edit**: Quickly update meta tags across posts
- **Validation**: Check overall site SEO configuration

## Technical Details

### What Gets Analyzed
- HTML meta tags in `<head>`
- HTTP response headers
- JSON-LD structured data
- Open Graph and Twitter Card tags
- Canonical link elements
- Robots meta tags

### What Doesn't Get Analyzed
- JavaScript-rendered content (analyzes initial HTML only)
- Password-protected pages
- Pages blocked by robots.txt
- Internal WordPress draft/preview URLs

## Frequently Asked Questions

**Q: Why does the tool show different results than Google?**
A: The tool shows the initial HTML. Google may see additional content rendered by JavaScript, or may interpret meta tags differently based on other signals.

**Q: Can I test pages from other websites?**
A: Yes, you can enter any URL. However, analysis depth may be limited for external sites depending on their security settings.

**Q: How often should I run diagnostics?**
A: Run diagnostics after making changes to your site, or periodically (weekly/monthly) for key pages.

**Q: What if no schema is detected?**
A: Not all pages need schema. Focus on adding schema to your homepage, contact page, articles, and products/services.

## Need More Help?

- Review the [Clarity-First SEO Documentation](https://github.com/your-repo/docs/)
- Check the [FAQ](https://github.com/your-repo/docs/faq.md)
- Report issues on [GitHub](https://github.com/your-repo/issues)
