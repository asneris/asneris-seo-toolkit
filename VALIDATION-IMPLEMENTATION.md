# Comprehensive Input Validation Implementation

## Overview
All user inputs are now validated, sanitized, and checked for security threats before being saved to the database. Invalid values trigger user-friendly warning messages while automatically correcting or clearing problematic data.

## Validation Features

### 1. Length Limit Validation
**Fields with maximum length enforcement:**
- Google Verification Code: 100 characters
- Bing Verification Code: 100 characters
- Yandex Verification Code: 100 characters
- Organization Name: 200 characters
- Twitter Username: 50 characters
- Facebook App ID: 50 characters
- Payment Methods: 500 characters
- Languages Spoken: 500 characters
- Business Address: 1,000 characters
- Business Hours: 2,000 characters
- Service Area: 1,000 characters

**Behavior:**
- Values exceeding limits are automatically truncated
- Warning message shows: "{Field} exceeds maximum length of {N} characters. Value has been truncated."

### 2. URL Validation
**Fields with URL format and protocol checks:**
- Default OG Image
- Organization Logo

**Validation rules:**
- Must be valid URL format
- Must use http:// or https:// protocol only
- JavaScript, data:, and other protocols are rejected
- **Image URLs must have valid image file extensions**
  - Allowed: jpg, jpeg, png, gif, webp, svg, bmp, ico
  - Example: `https://example.com/logo.png` ✅
  - Example: `https://example.com/page` ❌

**Behavior:**
- Invalid URLs are cleared
- Non-image URLs are rejected for image fields
- Warning messages explain the issue

### 3. Color Validation
**Field: Theme Color**
- Must be valid hex color code (#000000 format)
- Invalid values are cleared with warning

### 4. Template Variable Validation
**Fields: Title Templates, Description Templates**

**Valid template variables:**
- `{title}` - Post/page title
- `{site}` - Site name
- `{excerpt}` - Post excerpt
- `{date}` - Publication date
- `{author}` - Author name
- `{category}` - Primary category
- `{separator}` - Title separator character

**Behavior:**
- Extracts all `{variable}` patterns from templates
- Warns if invalid variables are used
- Shows list of valid variables in error message

### 5. Dangerous Pattern Detection
**All text and textarea fields are scanned for:**

| Pattern | Type | Action |
|---------|------|--------|
| `.ps1` | PowerShell script | Field cleared |
| `.exe` | Executable file | Field cleared |
| `.bat`, `.cmd` | Batch/command file | Field cleared |
| `.sh` | Shell script | Field cleared |
| `<script>` | Script tag | Field cleared |
| `<iframe>` | Iframe tag | Field cleared |
| `<object>` | Object tag | Field cleared |
| `<embed>` | Embed tag | Field cleared |
| `<?php` | PHP code | Field cleared |
| `javascript:` | JavaScript protocol | Field cleared |
| `data:text/html` | Data URL | Field cleared |
| `on*=` | Event handlers | Field cleared |

**Behavior:**
- Detection triggers immediate field clearing
- Warning message identifies the suspicious content type
- Example: "Description template contains suspicious content (PowerShell script). This has been removed for security."

### 6. Whitelist-Based Validation
**Existing validations maintained:**
- Robots Index: "index" or "noindex"
- Robots Follow: "follow" or "nofollow"
- Business Type: 60+ Schema.org business types
- Price Range: empty, $, $$, $$$, $$$$
- Title Separator: | - – — • : ·
- Phone Number: digits and common symbols only
- IndexNow Key: 32-128 alphanumeric characters

## Validation Feedback System

### User Experience
1. **Settings save normally** - Form submits successfully
2. **Success message displays** - "Settings saved successfully!"
3. **Warning message appears** (if validation issues found)
   - Yellow warning box with dismissible close button
   - Clear explanation: "Some values were adjusted:"
   - Bulleted list of specific issues
4. **Corrected values persist** - Invalid data replaced with safe defaults or cleared

### Technical Implementation
```php
// Validation errors collected during sanitization
$validation_errors = [];

// Store errors in transient (survives redirect)
set_transient('asneris_settings_validation_errors', $validation_errors, 30);

// Display after settings-updated redirect
$validation_errors = get_transient('asneris_settings_validation_errors');
if (!empty($validation_errors)) {
  // Show warning notice with list
}
```

## Security Benefits

### Prevents Common Attacks
- **XSS (Cross-Site Scripting)**: Script tags, event handlers blocked
- **Code Injection**: PHP, JavaScript, shell commands blocked
- **File Inclusion**: File path patterns blocked
- **Protocol Attacks**: Only http/https allowed for URLs

### WordPress.org Compliance
- All inputs sanitized using WordPress functions
- Validation provides additional security layer
- User feedback improves transparency
- Prevents silent data corruption

## Example Validation Messages

### Length Exceeded
> "Business address exceeds maximum length of 1000 characters. Value has been truncated."

### Invalid Template Variable
> "Title template contains invalid variable {invalid}. Valid variables: {title}, {site}, {excerpt}, {date}, {author}, {category}, {separator}"

### Dangerous Content
> "Description template contains suspicious content (PowerShell script). This has been removed for security."

### Invalid URL
> "Organization logo must use http:// or https:// protocol. Field has been cleared."

### Invalid Image URL
> "Default OG image must be a valid image URL (allowed extensions: jpg, jpeg, png, gif, webp, svg, bmp, ico). Field has been cleared."

### Invalid Format
> "Facebook App ID contains invalid characters. Only numbers are allowed."

## Testing Checklist

- [ ] Test field length limits (exceed max for each field)
- [ ] Test URL validation (invalid format, wrong protocol)
- [ ] Test image URL validation (non-image URL in image fields)
- [ ] Test template variables (use {invalid} in templates)
- [ ] Test dangerous patterns (paste `.ps1` command in template)
- [ ] Test script injection (`<script>alert('xss')</script>`)
- [ ] Test PHP injection (`<?php echo 'test'; ?>`)
- [ ] Test event handlers (`<img onerror="alert(1)">`)
- [ ] Test hex color validation (invalid color codes)
- [ ] Verify warning messages display correctly
- [ ] Verify corrected values persist after save

## Files Modified
- `includes/class-admin-settings.php` - Added validation methods and integration

## Version
Implemented in v0.1.3 (pending commit and release)
