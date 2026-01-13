# Settings Overview

## Introduction

The Clarity-First SEO Settings page is your central configuration hub for site-wide SEO settings. Settings are organized into tabs for easy navigation.

## Settings Tabs

### 1. General Settings

Configure basic site information and branding:

- **Organization Name**: Your company or site name
- **Organization Logo**: Upload your logo (recommended: 600x60px or square)
- **Theme Color**: Choose a color for mobile browser UI
- **Breadcrumbs**: Enable/disable breadcrumb navigation

**Tips:**
- Keep settings simple and consistent
- Logo appears in schema markup
- Theme color shows in mobile Chrome/Safari address bar

[Detailed Guide →](settings-general.md)

### 2. Verification

Add verification codes for search engine webmaster tools:

- **Google Search Console**: Track performance and indexing
- **Bing Webmaster Tools**: Monitor Bing/Yahoo rankings  
- **Yandex**: Essential for Russian/CIS markets

**Tips:**
- Get codes from each platform's admin panel
- Verification enables IndexNow and sitemap submission
- Takes 24-48 hours to activate after adding codes

[Detailed Guide →](settings-verification.md)

### 3. IndexNow

Configure instant indexing for supported search engines:

- **Enable IndexNow**: Auto-submit on publish/update
- **API Key**: Generated automatically
- **Supported Engines**: Bing, Yandex, Seznam, Naver

**Tips:**
- Get new/updated pages indexed within minutes
- No manual work - fully automatic
- Check "IndexNow" button in post editor sidebar
- Great for time-sensitive content

[Detailed Guide →](settings-indexnow.md)

### 4. Social Media

Set default Open Graph and Twitter Card settings:

- **Default OG Image**: Used when posts have no featured image
- **Twitter Username**: Enables author attribution in Twitter Cards
- **Facebook App ID**: Provides detailed analytics

**Tips:**
- Recommended OG image size: 1200x630px (1.91:1 ratio)
- Override per-page in block editor sidebar
- Test with Facebook Sharing Debugger and Twitter Card Validator

[Detailed Guide →](settings-social.md)

### 5. Schema

Configure structured data markup:

- **Organization Schema**: Your business information
- **Local Business**: For physical locations
- **Contact Info**: Phone, address, hours
- **Social Profiles**: Links to your social media

**Tips:**
- Match your Google Business Profile data exactly
- Not all fields required - fill what's relevant
- Schema helps with: Maps, Local Pack, Knowledge Panel
- Test with Google Rich Results Test

[Detailed Guide →](settings-schema.md)

### 6. Templates

Create dynamic templates for meta tags:

- **Title Templates**: By post type, archives, etc.
- **Description Templates**: Auto-generate descriptions
- **Available Variables**: `%title%`, `%sitename%`, `%category%`, etc.

**Tips:**
- Keep titles under 60 characters
- Keep descriptions under 160 characters
- Override templates per-page when needed

[Detailed Guide →](settings-templates.md)

### 7. Advanced

Configure default robots and advanced settings:

- **Default Indexing**: Index or noindex
- **Default Following**: Follow or nofollow
- **Apply Site-Wide**: Or override per-page

**Tips:**
- Most sites should use: Index, Follow
- Override per-page in block editor sidebar
- Use noindex for: admin pages, thank-you pages, duplicates

[Detailed Guide →](settings-advanced.md)

## Getting Started

### Initial Setup (15 minutes)

1. **General Tab**
   - Add organization name
   - Upload logo
   - Choose theme color

2. **Verification Tab**
   - Add Google Search Console code
   - Add Bing Webmaster Tools code

3. **IndexNow Tab**
   - Enable IndexNow
   - Key generated automatically

4. **Social Tab**
   - Upload default OG image (1200x630px)
   - Add Twitter username

5. **Schema Tab** (if applicable)
   - Add business information
   - Add contact details

6. **Save Settings**

### Ongoing Maintenance

**Monthly:**
- Review schema information for accuracy
- Update social defaults if branding changes
- Check verification codes still working

**Quarterly:**
- Review and update templates if needed
- Audit social sharing appearance
- Verify schema markup still valid

**As Needed:**
- Add new search engine verifications
- Update organization info
- Adjust advanced settings

## Best Practices

### Organization Information
- Use consistent naming across all platforms
- Keep logo updated and high-quality
- Match Google Business Profile exactly

### Verification Codes
- Add all major search engines (Google, Bing)
- Keep codes in place even after verification
- Re-verify if changing domains

### Social Defaults
- Use high-quality, branded images
- Update seasonally if relevant
- Test appearance before launching campaigns

### Schema Markup
- Fill all applicable fields
- Keep information current
- Don't make up information
- Match official business listings

### Templates
- Create once, benefit forever
- Keep consistent format across types
- Test on various post types
- Override only when needed

## Common Workflows

### New Site Launch
1. Configure General settings
2. Add verification codes
3. Set up schema markup
4. Create meta tag templates
5. Configure social defaults
6. Enable IndexNow
7. Run Site Diagnostics to verify

### Rebranding
1. Update organization name
2. Upload new logo
3. Update theme color
4. Change social default images
5. Update schema information
6. Test social sharing appearance

### Adding New Post Type
1. Create template for post type (Templates tab)
2. Set default indexing rules (Advanced tab)
3. Configure social defaults if different
4. Test with a few posts
5. Use Bulk Edit to update existing posts

### Improving Social Sharing
1. Upload high-quality default OG image
2. Add Twitter username
3. Test with Facebook Sharing Debugger
4. Test with Twitter Card Validator
5. Add per-post images for key content

## Troubleshooting

### Verification Not Working
- Check code is complete (no truncation)
- Verify code is in correct field
- Wait 24-48 hours for verification
- Try alternative verification methods (DNS, file upload)

### Schema Not Showing
- Validate with Google Rich Results Test
- Check that required fields are filled
- Ensure JSON-LD output is enabled
- Clear cache and re-test

### Social Images Not Appearing
- Check image URL is absolute (not relative)
- Verify image is publicly accessible
- Ensure image meets size requirements (1200x630px recommended)
- Clear social platform cache (Facebook Debugger, Twitter Card Validator)

### Templates Not Applying
- Check that per-page overrides aren't set
- Verify template syntax is correct
- Test with a new post/page
- Clear any caching plugins

## Integration with Other Tools

### Site Diagnostics
- Validates settings are working
- Checks schema markup output
- Verifies social tags present

### Bulk Edit
- Applies templates to existing content
- Overrides templates per-post
- Updates indexing settings

### Page Diagnostics
- Tests individual page output
- Verifies schema markup correct
- Checks social tags

## Related Documentation

- [General Settings Guide](settings-general.md)
- [Verification Setup](settings-verification.md)
- [IndexNow Configuration](settings-indexnow.md)
- [Social Media Settings](settings-social.md)
- [Schema Markup Guide](settings-schema.md)
- [Template Variables](settings-templates.md)
- [Advanced Settings](settings-advanced.md)
