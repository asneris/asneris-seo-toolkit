# Redirects Management Guide

## Understanding Redirects

Redirects are a **maintenance tool** for your website's URL structure. They serve three key purposes:

1. **Maintenance Tool** - Keep your site working smoothly when URLs change
2. **SEO Safety Net** - Catch mistakes before users and search engines hit dead ends
3. **Technical Hygiene** - Keep your site clean with fewer crawl errors and no redirect chains

**Important**: Redirects preserve SEO value when you must change URLs, but they don't create new value.

## When to Use Redirects

### Good Reasons for Redirects

✅ **Changed Slug/URL**
- You renamed a post or page
- Old URL should point to new location
- Use 301 to preserve SEO value

✅ **Merged Content**
- Combined multiple pages into one
- Old URLs should redirect to the consolidated page
- 301 redirects transfer link equity

✅ **Reorganized Site Structure**
- Moved pages to different sections
- Old URLs still exist in search engines or bookmarks
- 301 redirects maintain user access

✅ **Deleted Content with Alternative**
- Removed a page but have similar content elsewhere
- Redirect to the most relevant alternative
- Better than 404 error for users

### Bad Reasons for Redirects

❌ **Trying to boost SEO**
- Redirects don't create ranking value
- They only preserve existing value during transitions

❌ **Fixing Poor URLs**
- Don't redirect dozens of old URLs to your homepage
- This dilutes relevance signals

❌ **Avoiding 404 Errors**
- Some 404s are normal (old, outdated content)
- Not everything needs to redirect somewhere

## Redirect Types

### 301 Permanent Redirect

**Use when:**
- URL has permanently moved
- Content has been merged or consolidated
- Site structure has been reorganized
- Old URL will never return

**Effect:**
- Tells search engines to transfer ranking signals to new URL
- Browsers cache the redirect (faster but harder to change)
- Most SEO value preserved (90-99%)

**Example:**
```
/old-page/ → 301 → /new-page/
```

### 302 Temporary Redirect

**Use when:**
- Original URL will return eventually
- Testing a new page layout
- Seasonal content temporarily moved

**Effect:**
- Search engines keep indexing original URL
- No ranking signal transfer
- Less browser caching

**Note:** Clarity-First SEO focuses on 301 redirects for SEO purposes.

## How to Add a Redirect

### Manual Redirect

1. **From URL**: Enter the old path (e.g., `/old-page/`)
   - Use relative paths (start with `/`)
   - Include trailing slash for consistency
   - Don't include domain name

2. **To URL**: Enter the new path (e.g., `/new-page/`)
   - Use relative paths
   - Or full URLs for external redirects
   - Ensure destination exists and returns 200

3. **Click "Add Redirect"**
   - Redirect is saved immediately
   - Test by visiting old URL
   - Should redirect to new URL

### Automatic Redirects

**Created automatically when:**
- You change a post/page slug
- WordPress detects the URL changed
- Plugin creates 301 redirect from old to new

**Benefits:**
- No manual work required
- Prevents broken links immediately
- Preserves SEO value automatically

**Review regularly:**
- Check automatic redirects list
- Delete outdated ones (after months/years)
- Keep list clean for performance

## Managing Redirects

### Viewing Redirects

The redirect list shows:
- **From**: Original URL
- **To**: Destination URL
- **Type**: 301, 302, etc.
- **Status**: Enabled/Disabled
- **Source**: Manual or Auto
- **Actions**: Enable/Disable, Delete

### Testing Redirects

**After adding a redirect:**
1. Visit the old URL directly
2. Should instantly redirect to new URL
3. Check that new URL returns 200 (not 404)
4. Verify content is relevant

**Common issues:**
- Redirect chains (A→B→C) - avoid these
- Redirect loops (A→B→A) - will cause errors
- Redirecting to 404 - destination must exist

### Disabling vs Deleting

**Disable:**
- Temporarily turn off redirect
- Can re-enable later
- Useful for testing

**Delete:**
- Permanently remove redirect
- Old URL will 404
- Use after transition period

## Best Practices

### URL Format

✅ **Good:**
- `/old-page/` → `/new-page/`
- `/blog/old-post/` → `/blog/new-post/`
- `/category/old/` → `/category/new/`

❌ **Avoid:**
- `old-page` (missing leading slash)
- `/old-page` (missing trailing slash)
- `https://site.com/old-page/` (absolute URL for internal)

### Redirect Chains

**Problem:**
```
A → B → C → D
```
Each redirect in the chain:
- Slows page load
- Loses a bit of SEO value
- Increases crawl budget waste

**Solution:**
```
A → D
B → D  
C → D
```
All old URLs redirect directly to final destination.

### Redirect Loops

**Problem:**
```
A → B → A (infinite loop)
```
Causes browser errors, search engines can't crawl.

**Solution:**
Before adding redirect, check that destination doesn't redirect back.

### Periodic Cleanup

**Monthly:**
- Review automatic redirects
- Delete very old ones (1+ years)
- Check for redirect chains
- Test a few random redirects

**After Major Changes:**
- Verify all redirects working
- Look for 404s in new structure
- Update redirects if needed
- Monitor search console for errors

## Common Scenarios

### Scenario 1: Changed Post Slug

**What happened:**
- Changed slug from "seo-tips" to "seo-best-practices"
- Old URL: `/seo-tips/`
- New URL: `/seo-best-practices/`

**Solution:**
- Plugin automatically creates redirect
- Verify: check automatic redirects list
- Test: visit old URL, should redirect

### Scenario 2: Merged Content

**What happened:**
- Combined 3 similar posts into one
- Deleted 2 old posts
- Want to redirect them to the merged post

**Solution:**
1. Note URLs of deleted posts
2. Add manual redirects:
   - `/old-post-1/` → `/merged-post/`
   - `/old-post-2/` → `/merged-post/`
3. Test both redirects
4. Monitor traffic to merged post

### Scenario 3: Site Restructure

**What happened:**
- Moved all blog posts from `/blog/` to `/articles/`
- 100+ posts affected
- Need redirects for all

**Solution:**
1. **Don't create 100 redirects manually**
2. Use regex redirect or bulk tool (if available)
3. Or: Update WordPress permalinks settings
4. Consider `.htaccess` rules for bulk changes
5. Test a few URLs to verify pattern works

### Scenario 4: Deleted Old Content

**What happened:**
- Removed outdated product page
- Still getting traffic from old links
- Don't have direct replacement

**Solution:**
- **Option A**: Redirect to most relevant category/page
- **Option B**: Create a simple landing page explaining change
- **Option C**: Let it 404 and create custom 404 page
- Choose based on traffic volume and relevance

## SEO Impact

### Positive Effects
- ✅ Preserves link equity during transitions
- ✅ Maintains user access to content
- ✅ Reduces 404 errors in search console
- ✅ Keeps backlinks valuable

### Neutral Effects
- ⚪ Doesn't improve rankings on its own
- ⚪ Doesn't create new value
- ⚪ Small amounts of value lost in redirect (1-10%)

### Negative Effects (if misused)
- ❌ Too many redirects slow site down
- ❌ Redirect chains waste crawl budget
- ❌ Irrelevant redirects hurt user experience
- ❌ Redirect loops block access entirely

## Integration with Other Tools

### Site Diagnostics
- Validates redirect destinations
- Checks for redirect chains
- Ensures destination pages indexable

### Page Diagnostics
- Shows redirect status for any URL
- Displays final destination
- Checks destination page status

### Robots.txt
- Robots.txt doesn't block redirects
- Search engines follow redirects
- Noindex tag on destination prevents indexing

## Frequently Asked Questions

**Q: How many redirects are too many?**
A: Depends on traffic. Under 100 is fine. Thousands may slow things down. Focus on redirects for pages that actually get traffic.

**Q: Should I redirect deleted content?**
A: Only if there's a good alternative. If not, let it 404. A good 404 page is better than a bad redirect.

**Q: How long should I keep redirects?**
A: At least 6-12 months. After a year, if old URL has no traffic, you can remove it. For high-value pages, keep indefinitely.

**Q: Do redirects hurt page speed?**
A: Each redirect adds ~0.1-0.3 seconds. Chains are worse. But preserving SEO value usually outweighs the small speed cost.

**Q: What about external redirects?**
A: You can redirect to external sites, but generally only for good reasons (moved content, company acquisition, etc.).

## Related Documentation

- [301 vs 302 Redirects](301-vs-302.md)
- [SEO Readiness Guide](seo-readiness.md)
- [Site Diagnostics](site-diagnostics.md)
- [Understanding Redirect Chains](redirect-chains.md)
