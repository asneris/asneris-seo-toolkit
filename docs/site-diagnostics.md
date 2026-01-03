# Site Diagnostics Guide

## Overview

Site Diagnostics (also called Validation) is your SEO health check tool. It validates multiple pages at once using a weighted scoring system to help you prioritize fixes.

## How the Scoring Works

### Weighted Categories

Pages are scored based on three categories with different weights:

1. **Critical Checks (60% weight)**
   - HTTP 200 status code
   - No noindex directives
   - Canonical URL is valid
   - These determine if search engines CAN index your page

2. **Recommended Checks (30% weight)**
   - Title tag present and optimized
   - Meta description present
   - H1 heading present
   - These improve search visibility and click-through rates

3. **Optimization Checks (10% weight)**
   - Open Graph tags for social sharing
   - Schema markup present
   - Twitter Card tags
   - These enhance search result appearance

### Score Interpretation

- **90-100%**: Excellent - page is well-optimized
- **70-89%**: Good - minor improvements recommended
- **50-69%**: Fair - important items need attention
- **Below 50%**: Poor - critical issues must be fixed

## Quick Tips

### Prioritize Your Work

1. **Fix critical issues first** - These prevent indexing
   - HTTP errors (404, 500, etc.)
   - Noindex tags on important pages
   - Broken canonical URLs
   - Missing or redirecting pages

2. **Then address recommended items** - These improve visibility
   - Add missing title tags
   - Write meta descriptions
   - Ensure H1 tags are present
   - Optimize title length (under 60 chars)

3. **Finally optimize** - These enhance appearance
   - Add Open Graph images
   - Implement schema markup
   - Configure Twitter Cards
   - Add breadcrumb navigation

### Regular Maintenance

- **Test homepage and key pages regularly** to ensure proper SEO setup
- **Run validation after major updates** to catch any issues
- **Monitor scores over time** to track improvements
- **Focus on high-traffic pages first** for maximum impact

## Understanding Check Types

### Critical Checks

**HTTP 200 Status**
- Page must be accessible with a 200 OK response
- 404, 500, or other errors prevent indexing
- Redirects (301/302) may dilute SEO value

**No Noindex Directives**
- Check for `<meta name="robots" content="noindex">`
- Check for `X-Robots-Tag: noindex` HTTP header
- Noindex tells search engines not to index the page
- Remove noindex from pages you want in search results

**Valid Canonical URL**
- Canonical must return 200 status
- Canonical should be self-referencing (pointing to itself)
- Broken canonical URLs can prevent indexing
- Canonical chains should be avoided

### Recommended Checks

**Title Tag**
- Should be unique for each page
- Keep under 60 characters for full display
- Include primary keyword naturally
- Frontload important words
- Avoid keyword stuffing

**Meta Description**
- Keep under 160 characters
- Write compelling copy that encourages clicks
- Include a call-to-action when appropriate
- Don't duplicate across pages
- Not a ranking factor, but affects click-through rate

**H1 Heading**
- One H1 per page (can have multiple, but one is clearer)
- Should summarize the page content
- Include primary keyword naturally
- Make it engaging for users

### Optimization Checks

**Open Graph Tags**
- Controls how pages appear when shared on social media
- At minimum: og:title, og:description, og:image
- Recommended image size: 1200x630px
- Use high-quality, relevant images

**Schema Markup**
- Structured data that helps search engines understand content
- Use JSON-LD format (recommended by Google)
- Common types: Article, Organization, LocalBusiness, Product
- Test with Google's Rich Results Test

**Twitter Cards**
- Similar to Open Graph but Twitter-specific
- Types: summary, summary_large_image, app, player
- Requires twitter:card, twitter:title, twitter:description
- Add twitter:image for visual appeal

## Best Practices

### Page Selection

Test these page types regularly:
1. **Homepage** - Your most important page
2. **Key landing pages** - High-traffic entry points
3. **Recent content** - New posts/pages
4. **Conversion pages** - Contact, product, service pages
5. **Problem pages** - Those with known SEO issues

### Fixing Issues

**For Critical Issues**
1. Identify all pages with critical problems
2. Fix HTTP errors first (broken links, server issues)
3. Remove noindex from important pages
4. Correct canonical URLs
5. Re-test to verify fixes

**For Recommended Issues**
1. Use Bulk Edit to add missing titles/descriptions
2. Update templates to include H1 tags
3. Review and optimize existing meta tags
4. Ensure uniqueness across pages

**For Optimization Issues**
1. Configure default Open Graph settings
2. Add schema markup via Settings → Schema
3. Set up Twitter Card settings
4. Test social sharing appearance

## Integration with Other Tools

### Works Together With:
- **Page Diagnostics**: Deep dive into individual pages
- **Bulk Edit**: Quick updates to meta tags
- **Settings**: Configure site-wide defaults
- **Redirects**: Fix broken URLs
- **Robots.txt**: Control crawling

### Combined Approach:
1. Run **Site Diagnostics** to find issues
2. Use **Page Diagnostics** to investigate specific problems
3. Fix with **Bulk Edit** or **Settings** as appropriate
4. Re-validate to confirm fixes

## Common Scenarios

### Scenario 1: Low Overall Scores
**Problem**: Most pages scoring below 70%
**Solution**:
- Check Settings → General for site-wide defaults
- Use Bulk Edit to add missing meta tags
- Review theme/template for H1 tag issues
- Configure social media defaults

### Scenario 2: Critical Issues on Key Pages
**Problem**: Homepage or important pages have critical issues
**Solution**:
- Check HTTP status (use Page Diagnostics)
- Review Indexing settings in page editor
- Verify canonical URL is correct
- Check for plugin conflicts

### Scenario 3: Inconsistent Scores
**Problem**: Some pages 90%+, others below 50%
**Solution**:
- Identify patterns (page type, author, date)
- Use Bulk Edit to standardize meta tags
- Update page templates for consistency
- Create meta tag templates (Settings → Templates)

### Scenario 4: All Pages Flagged for Schema
**Problem**: Every page missing schema markup
**Solution**:
- Not all pages need schema - prioritize
- Add Organization schema via Settings → Schema
- Add Article schema to blog posts
- Add LocalBusiness schema if applicable
- Leave other pages without schema (it's okay!)

## Frequently Asked Questions

**Q: Should all pages score 100%?**
A: Not necessarily. Focus on high scores for important pages. Some pages (like admin pages) may not need full optimization.

**Q: How often should I run validation?**
A: Weekly for key pages, monthly for full site, and immediately after major updates.

**Q: What if I disagree with a check result?**
A: These are technical checks, not SEO advice. You may intentionally noindex some pages or omit schema.

**Q: Can I export the results?**
A: Currently no, but you can screenshot or manually record scores for tracking.

**Q: Does a higher score guarantee better rankings?**
A: No. This measures technical SEO health, not content quality or backlinks. It's one piece of the SEO puzzle.

## Related Documentation

- [Page Diagnostics](page-diagnostics.md)
- [Bulk Edit Guide](bulk-edit.md)
- [Settings Overview](settings-overview.md)
- [Understanding SEO Scoring](scoring-methodology.md)
