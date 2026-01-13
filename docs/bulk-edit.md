# Bulk Edit Guide

## Overview

The Bulk Edit tool allows you to quickly update SEO metadata across multiple posts and pages. This is useful for applying consistent metadata, fixing missing fields, or updating indexing settings en masse.

## How to Use

### 1. Filter Your Content

**By Post Type:**
- Posts
- Pages
- Custom Post Types (if registered)

**By Indexing Status:**
- All Posts
- Indexable Only (no noindex)
- Noindexed Only (has noindex)

This helps you target specific content for bulk operations.

### 2. Review the Table

The table shows:
- **Post Title**: The post/page title
- **SEO Title**: Custom SEO title (if set)
- **Meta Description**: Custom meta description
- **Indexing**: Current index/noindex status
- **Actions**: Edit individual fields inline

### 3. Select Posts to Edit

- Check boxes to select specific posts
- Use "Select All" to choose all visible posts
- Filter first to narrow your selection

### 4. Apply Bulk Actions

**Available Bulk Actions:**

1. **Set to Index**
   - Removes noindex directive
   - Allows search engines to index
   - Apply to content you want visible in search results

2. **Set to Noindex**
   - Adds noindex directive
   - Prevents search engine indexing
   - Use for: admin pages, thank-you pages, duplicate content

3. **Clear Titles**
   - Removes custom SEO titles
   - Posts will use default title
   - Useful for resetting customizations

4. **Clear Descriptions**
   - Removes custom meta descriptions
   - WordPress will generate auto-descriptions
   - Use when starting fresh

### 5. Edit Individual Fields

Click directly in the table to edit:
- SEO Title field
- Meta Description field

Changes are highlighted for review before saving.

### 6. Save All Changes

Click **"Save All Changes"** button to apply modifications.
- All visible changes are saved at once
- Confirmation message appears
- Page refreshes to show updated data

## Quick Tips

### Filtering Strategy

**Target specific content:**
1. Filter by post type to focus on blog posts or pages
2. Filter by indexing status to find problematic pages
3. Combine filters to narrow results

**Example workflows:**
- Find all noindexed pages → Review → Set to Index
- Find all posts without descriptions → Add descriptions
- Find pages with custom titles → Clear and start fresh

### Bulk Action Best Practices

**Set to Index:**
- Use for published, quality content
- Verify content is complete before indexing
- Check for duplicate content first

**Set to Noindex:**
- Use for: tag archives, category pages (sometimes), thank-you pages
- Don't noindex your main content pages
- Review regularly - easy to accidentally noindex important pages

**Clear Titles/Descriptions:**
- Use when templates are updated
- Apply to content that needs fresh optimization
- Consider keeping custom titles on high-performing pages

### Inline Editing Tips

**SEO Titles:**
- Keep under 60 characters
- Include primary keyword naturally
- Make each title unique
- Front-load important words
- Avoid keyword stuffing

**Meta Descriptions:**
- Keep under 160 characters
- Write compelling copy
- Include a call-to-action
- Make each description unique
- Use natural language

## Common Use Cases

### 1. Initial SEO Setup

**Scenario**: New site, no SEO metadata
**Solution**:
1. Filter to "All Posts"
2. Add meta descriptions to top 10 posts
3. Review and optimize titles
4. Save changes
5. Repeat for pages

### 2. Noindex Cleanup

**Scenario**: Too many pages noindexed
**Solution**:
1. Filter to "Noindexed Only"
2. Review each post
3. Select posts that should be indexed
4. Apply "Set to Index" bulk action
5. Save changes

### 3. Meta Description Refresh

**Scenario**: Old descriptions need updating
**Solution**:
1. Filter by post type
2. Review existing descriptions
3. Edit inline with improved copy
4. Include keywords and CTAs
5. Save all changes

### 4. Template Change Cleanup

**Scenario**: Changed title templates, want to reset custom titles
**Solution**:
1. Filter to all posts/pages
2. Select all
3. Apply "Clear Titles" bulk action
4. New template will apply automatically
5. Customize only high-priority pages

## Best Practices

### Before Bulk Editing

1. **Backup your data** - Just in case
2. **Test on a few posts first** - Verify changes work as expected
3. **Review current state** - Understand what you're changing
4. **Have a plan** - Know what you want to accomplish

### During Bulk Editing

1. **Work in batches** - Don't try to edit everything at once
2. **Review changes** - Check highlighted fields before saving
3. **Be consistent** - Use similar formats across content
4. **Focus on high-traffic pages first** - Maximum impact

### After Bulk Editing

1. **Verify changes** - Check a few pages to confirm
2. **Run Site Diagnostics** - Ensure no issues introduced
3. **Monitor search performance** - Track impact over time
4. **Document what you changed** - For future reference

## What Happens to Changed Content

### Indexing Changes
- **Set to Index**: Removes noindex meta tag, page becomes crawlable
- **Set to Noindex**: Adds `<meta name="robots" content="noindex">`, page hidden from search

### Title Changes
- **Custom Title**: Overrides default, shows in search results
- **Cleared Title**: Falls back to post title or template

### Description Changes
- **Custom Description**: Shows in search results
- **Cleared Description**: WordPress generates auto-excerpt or uses template

## Integration with Other Tools

### Use With Site Diagnostics
1. Run validation to find missing metadata
2. Use Bulk Edit to add missing fields
3. Re-validate to confirm improvements

### Use With Settings
1. Configure meta tag templates in Settings
2. Clear custom values in Bulk Edit
3. Templates apply automatically

### Use With Page Diagnostics
1. Test a few pages before bulk editing
2. Verify meta tags appear correctly
3. Apply successful format to other pages

## Safety Features

### Preview Before Save
- All changes highlighted in yellow
- Review before clicking "Save All Changes"
- No changes applied until you save

### Scope Control
- Only visible posts are affected
- Use filters to limit scope
- Select specific posts with checkboxes

### Reversibility
- Can re-edit any field after saving
- Use "Clear" actions to reset custom values
- Noindex/Index can be toggled back

## Performance Considerations

### Working with Large Sites

**If you have 1000+ posts:**
1. Filter by post type and date range
2. Work in batches of 50-100 posts
3. Save frequently to avoid data loss
4. Take breaks between large batches

**If page loads slowly:**
1. Reduce number of visible posts (use filters)
2. Disable other plugins temporarily
3. Increase PHP memory limit if needed
4. Work during low-traffic times

## Frequently Asked Questions

**Q: Will bulk editing affect my search rankings?**
A: Changes take time to be re-crawled. Improvements may show in weeks/months. Noindexing pages removes them from search.

**Q: Can I undo bulk changes?**
A: Yes, you can re-edit fields or clear custom values to revert to defaults. Keep backups for safety.

**Q: What if I accidentally noindex important pages?**
A: Filter to "Noindexed Only", select the pages, apply "Set to Index", and save. Run Site Diagnostics to verify.

**Q: Should I add custom titles to all posts?**
A: No, only customize when needed. Templates work well for most content. Focus on high-traffic or strategic pages.

**Q: How long until search engines see my changes?**
A: Depends on crawl frequency. High-traffic pages: days. Lower-traffic: weeks. Submit sitemap to speed up.

## Related Documentation

- [Meta Tags Best Practices](meta-tags.md)
- [Indexing Control Guide](indexing-control.md)
- [Site Diagnostics](site-diagnostics.md)
- [Settings Overview](settings-overview.md)
