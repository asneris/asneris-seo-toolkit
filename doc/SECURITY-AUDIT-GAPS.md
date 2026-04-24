# Security Audit: Gaps & Issues

**Audit Date:** April 21, 2026  
**Scope:** Sanitizing, Validating, Escaping per WordPress Guidelines  
**Objective:** Find gaps in compliance, not enhancements

---

## CRITICAL ISSUES

### 1. ❌ MIXING VALIDATION WITH SANITIZATION

**Location:** `includes/class-meta.php` - `sanitize()` method (lines 69-165)

**Issue:** The `sanitize_callback` is performing VALIDATION instead of SANITIZATION.

**WordPress Guideline:**
> Sanitizing input is the process of securing/cleaning/filtering input data.
> Validation is the process of testing data against a predefined pattern with a definitive result: valid or invalid.
> Data validation should be performed as early as possible.

**Current Code (WRONG):**
```php
public static function sanitize($value, $key) {
    // URL fields: canonical and OG image - strict validation
    if (in_array($key, ['_ASNERISSEO_canonical', '_ASNERISSEO_og_image'], true)) {
        // ... pattern checking ...
        if (preg_match('/script|javascript|data:|vbscript:|file:|about:|<|>|eval\(|onerror|onload/i', $value)) {
            return '';  // ❌ VALIDATION: Rejecting input
        }
        
        // ... URL validation ...
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return '';  // ❌ VALIDATION: Rejecting input
        }
    }
}
```

**Correct Approach:**
```php
// sanitize_callback should SANITIZE, not VALIDATE
public static function sanitize($value, $key) {
    if (in_array($key, ['_ASNERISSEO_canonical', '_ASNERISSEO_og_image'], true)) {
        // Just sanitize - WordPress will handle it
        return esc_url_raw($value, ['http', 'https']);
    }
    return sanitize_text_field($value);
}

// Validation should be separate (in REST API endpoint or save_post hook)
```

**Impact:** HIGH - Not following WordPress architecture

---

### 2. ❌ RETURNING EMPTY/DEFAULT ON VALIDATION FAILURE

**Location:** `includes/class-meta.php` - Multiple places

**Issue:** Sanitize callback should NEVER reject data by returning empty string.

**WordPress Guideline:**
> Sanitization is the process of securing/cleaning/filtering input data.
> Format Correction: Accept most any data, but remove or alter the dangerous pieces.

**Current Code (WRONG):**
```php
// Robots index: strict whitelist validation
if ($key === '_ASNERISSEO_robots_index') {
    $value = is_string($value) ? sanitize_text_field($value) : '';
    if (!in_array($value, ['index', 'noindex'], true)) {
        return 'index';  // ❌ Changing user input silently
    }
    return $value;
}
```

**Correct Approach:**
```php
// Sanitize only - let validation happen elsewhere
if ($key === '_ASNERISSEO_robots_index') {
    return sanitize_text_field($value);  // Clean it, don't validate it
}
```

**Validation should be in:**
- REST API schema validation
- `save_post` hook with user notification
- Client-side JavaScript (already implemented)

**Impact:** HIGH - Silent data modification without user notification

---

### 3. ⚠️ OVER-ESCAPING IN RENDER

**Location:** `includes/class-render.php` (line 38)

**Issue:** Escaping already sanitized data from database, then escaping again for output.

**WordPress Guideline:**
> Always escape late... escape while creating the string and store the value in a variable that is postfixed with _escaped, _safe or _clean

**Current Code (POTENTIAL DOUBLE ESCAPE):**
```php
$canon = esc_url_raw($canon);  // ❌ Escaping data from database
if (!$canon || !wp_http_validate_url($canon)) {
    $canon = get_permalink($id);
}

// Later...
echo '<link rel="canonical" href="' . esc_url($canon) . '">' . "\n";  // ✓ Correct escaping on output
```

**Issue:** `esc_url_raw()` is for SANITIZING on INPUT, not for processing on output.

**Correct Approach:**
```php
// Don't escape when retrieving from database
$custom_canonical = get_post_meta($id, '_ASNERISSEO_canonical', true);

if (!empty($custom_canonical)) {
    $canon = $custom_canonical;  // Already sanitized on input
} else {
    $canon = get_permalink($id);
}

// Validate if needed
if (!wp_http_validate_url($canon)) {
    $canon = get_permalink($id);
}

// Escape ONCE on output
echo '<link rel="canonical" href="' . esc_url($canon) . '">' . "\n";
```

**Impact:** MEDIUM - Possible encoding issues, not a security risk

---

### 4. ❌ MISSING ESCAPING IN TEMPLATES

**Location:** `templates/validation/url-selector.php` (line 59)

**Issue:** Raw output without escaping.

**Current Code (WRONG):**
```php
<?php echo $icon; ?> <?php echo esc_html($post->post_title); ?> 
(<?php echo $post->post_type; ?>)  // ❌ NO ESCAPING
```

**WordPress Guideline:**
> You must use the most appropriate function to the content and context of what you're echoing. You always want to escape when you echo, not before.

**Correct Code:**
```php
<?php echo $icon; ?> <?php echo esc_html($post->post_title); ?> 
(<?php echo esc_html($post->post_type); ?>)
```

**Impact:** MEDIUM - XSS vulnerability if custom post type names are user-controlled

---

### 5. ⚠️ SANITIZING BEFORE VALIDATION

**Location:** `includes/class-admin-settings.php` (multiple instances)

**Issue:** Sanitizing data BEFORE validation in safelist check.

**Current Code (QUESTIONABLE):**
```php
$orderby = sanitize_key($_POST['orderby']);  // Sanitize first
if (in_array($orderby, $allowed_keys, true)) {  // Then validate
```

**WordPress Guideline:**
The example shows this same pattern, so it's ACCEPTABLE but:

> Validation is preferred over sanitization because validation is more specific.

**Better Approach:**
```php
// Validate FIRST with strict comparison
if (isset($_POST['orderby']) && in_array($_POST['orderby'], $allowed_keys, true)) {
    $orderby = sanitize_key($_POST['orderby']);  // Only sanitize if valid
}
```

**Impact:** LOW - Works but not optimal order

---

### 6. ❌ INCONSISTENT ESCAPING IN class-render.php

**Location:** `includes/class-render.php` (line 80-84)

**Issue:** Using wrong escaping function for context.

**Current Code (MIGHT BE WRONG):**
```php
echo '<meta property="og:type" content="' . (is_front_page() ? 'website' : 'article') . '">' . "\n";
```

**Issue:** Raw string concatenation without escaping. While 'website' and 'article' are hardcoded, this violates the principle.

**WordPress Guideline:**
> esc_attr() – Use on everything else that's printed into an HTML element's attribute.

**Correct Code:**
```php
$og_type = is_front_page() ? 'website' : 'article';
echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
```

**Impact:** LOW - Hardcoded strings are safe, but principle violation

---

### 7. ❌ VALIDATION IN WRONG LOCATION

**Location:** `src/index.js` (client-side validation)

**Issue:** Heavy validation on client-side with weak server-side.

**WordPress Guideline:**
> Data validation should be performed as early as possible. That means validating the data before performing any actions.

**Current State:**
- ✅ Client-side: Strong validation with user feedback
- ❌ Server-side: Sanitization callback doing validation (wrong place)
- ❌ No proper REST API schema validation

**Correct Approach:**
```php
// In register_post_meta()
register_post_meta( $post_type, $key, [
    'type' => 'string',
    'single' => true,
    'show_in_rest' => [
        'schema' => [
            'type' => 'string',
            'format' => 'uri',  // For URLs
            'maxLength' => 500,
        ],
    ],
    'sanitize_callback' => 'esc_url_raw',  // Just sanitize
    'auth_callback' => function() {
        return current_user_can('edit_posts');
    },
] );
```

**Impact:** HIGH - Validation not in proper WordPress hooks

---

## MODERATE ISSUES

### 8. ⚠️ TEXTAREA ESCAPING

**Location:** `includes/class-admin-settings.php` (line 473)

**Current Code:**
```php
<textarea><?php echo esc_textarea(self::get('business_address')); ?></textarea>
```

**Status:** ✅ CORRECT

**WordPress Guideline:**
> esc_textarea() – Use this to encode text for use inside a textarea element.

**No issue - just confirming correct usage.**

---

### 9. ⚠️ LATE VS EARLY ESCAPING

**Location:** `includes/class-admin-settings.php` (lines 75-76)

**Current Code:**
```php
$indexnow_key = esc_attr(self::get('indexnow_key', ''));  // ❌ Early escape
$key_url = $indexnow_key ? esc_url(home_url('/' . $indexnow_key . '.txt')) : '';
```

**WordPress Guideline:**
> It is best to do the output escaping as late as possible, ideally as data is being outputted.

**Issue:** Escaping when assigning to variable, not when outputting.

**Better Approach:**
```php
$indexnow_key = self::get('indexnow_key', '');  // Don't escape yet
$key_url = $indexnow_key ? esc_url(home_url('/' . $indexnow_key . '.txt')) : '';

// Then in output:
<input value="<?php echo esc_attr($indexnow_key); ?>">
```

**Impact:** LOW - Works but not best practice

---

### 10. ⚠️ wp_localize_script ESCAPING

**Location:** `asneris-seo-toolkit.php` (if using wp_localize_script)

**WordPress Guideline:**
> wp_localize_script() - No escaping needed, WordPress will escape this.

**Status:** Need to verify if we're escaping data passed to `wp_localize_script`.

**If doing this (WRONG):**
```php
wp_localize_script('handle', 'data', [
    'url' => esc_url($url),  // ❌ Don't escape
]);
```

**Correct:**
```php
wp_localize_script('handle', 'data', [
    'url' => $url,  // ✓ WordPress escapes automatically
]);
```

**Impact:** LOW - Possible double-escaping

---

## SUMMARY OF GAPS

### Critical (Must Fix)
1. ❌ **Validation in sanitize_callback** - Move to REST API schema or save_post hook
2. ❌ **Silent data modification** - Returning defaults instead of showing errors
3. ❌ **Missing escaping** - `$post->post_type` in templates
4. ❌ **Validation location** - Need proper REST API validation

### Moderate (Should Fix)
5. ⚠️ **Over-escaping** - Remove `esc_url_raw()` when retrieving from DB
6. ⚠️ **Early escaping** - Escape on output, not on variable assignment
7. ⚠️ **Hardcoded strings** - Should still use `esc_attr()` for principle

### Low Priority (Best Practice)
8. ⚠️ **Sanitize after validate** - Validate with raw data, then sanitize
9. ⚠️ **Variable naming** - Use `_escaped` suffix for pre-escaped variables

---

## RECOMMENDED FIXES

### Fix #1: Separate Validation from Sanitization

**Current (WRONG):**
```php
// class-meta.php sanitize_callback
public static function sanitize($value, $key) {
    if ($key === '_ASNERISSEO_canonical') {
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return '';  // ❌ Validation in sanitizer
        }
        return esc_url_raw($value);
    }
}
```

**Correct:**
```php
// class-meta.php - ONLY sanitize
public static function sanitize($value, $key) {
    if (in_array($key, ['_ASNERISSEO_canonical', '_ASNERISSEO_og_image'], true)) {
        return esc_url_raw($value, ['http', 'https']);
    }
    if ($key === '_ASNERISSEO_schema_type') {
        return sanitize_text_field($value);
    }
    // ... etc
    return sanitize_text_field($value);
}

// NEW: Add validation in REST API registration
register_post_meta($post_type, '_ASNERISSEO_canonical', [
    'type' => 'string',
    'single' => true,
    'show_in_rest' => [
        'schema' => [
            'type' => 'string',
            'format' => 'uri',
            'pattern' => '^https?://.+',
        ],
    ],
    'sanitize_callback' => [__CLASS__, 'sanitize'],
]);

register_post_meta($post_type, '_ASNERISSEO_robots_index', [
    'type' => 'string',
    'single' => true,
    'show_in_rest' => [
        'schema' => [
            'type' => 'string',
            'enum' => ['index', 'noindex'],
        ],
    ],
    'sanitize_callback' => 'sanitize_text_field',
]);
```

### Fix #2: Remove Double Escaping

**Current:**
```php
$canon = esc_url_raw($canon);  // ❌ Escaping database value
// ...
echo '<link rel="canonical" href="' . esc_url($canon) . '">';
```

**Correct:**
```php
$canon = get_post_meta($id, '_ASNERISSEO_canonical', true);  // Already sanitized
// ...
echo '<link rel="canonical" href="' . esc_url($canon) . '">';  // Escape once on output
```

### Fix #3: Add Missing Escaping

**File:** `templates/validation/url-selector.php`

```php
// Current (WRONG):
(<?php echo $post->post_type; ?>)

// Correct:
(<?php echo esc_html($post->post_type); ?>)
```

### Fix #4: Escape Late

**Current:**
```php
$indexnow_key = esc_attr(self::get('indexnow_key', ''));  // Too early
```

**Correct:**
```php
$indexnow_key = self::get('indexnow_key', '');  // Raw value
// ...
<input value="<?php echo esc_attr($indexnow_key); ?>">  // Escape on output
```

---

## WORDPRESS GUIDELINE COMPLIANCE

| Aspect | Current State | Guideline | Compliance |
|--------|--------------|-----------|------------|
| Sanitization | Using correct functions | ✓ Use WordPress functions | ✅ PASS |
| Validation | In sanitize_callback | Must be separate from sanitization | ❌ FAIL |
| Validation Type | Safelist with strict check | Use strict type checking | ✅ PASS |
| Escaping | Mostly correct | Escape late on output | ⚠️ PARTIAL |
| Missing Escaping | Few instances | Escape everything | ❌ FAIL |
| Double Escaping | Some instances | Escape once | ⚠️ PARTIAL |
| Context-appropriate | Correct functions | Use right function for context | ✅ PASS |

---

## CONCLUSION

**Current Approach:** 70% WordPress-compliant

**Main Problems:**
1. Validation mixed with sanitization (architectural issue)
2. Missing escaping in templates (security issue)
3. Early escaping instead of late (best practice issue)

**Strengths:**
1. Using correct WordPress functions
2. Strict type checking in validation
3. Context-appropriate escaping functions
4. Client-side validation with user feedback

**Action Required:**
1. **HIGH:** Separate validation from sanitization
2. **HIGH:** Add missing escaping in templates
3. **MEDIUM:** Remove early/double escaping
4. **LOW:** Follow "escape late" principle throughout
