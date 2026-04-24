# WordPress Security Guidelines

## Official Documentation Sources
- [Sanitizing Data](https://developer.wordpress.org/apis/security/sanitizing/)
- [Validating Data](https://developer.wordpress.org/apis/security/data-validation/)
- [Escaping Data](https://developer.wordpress.org/apis/security/escaping/)

---

## 1. SANITIZING DATA

### Purpose
**Sanitizing input is the process of securing/cleaning/filtering input data.**

> Validation is preferred over sanitization because validation is more specific. But when "more specific" isn't possible, sanitization is the next best thing.

### Key Principle
**All untrusted data needs to be sanitized** - data from users, third-party sites, even your own database.

> Remember: Even admins are users, and users will enter incorrect data, either on purpose or accidentally. It's your job to protect them from themselves.

### Core Sanitization Functions

#### Text Fields
```php
sanitize_text_field( $input )
```
**Behind the scenes, it:**
1. Checks for invalid UTF-8
2. Converts single less-than characters (<) to entity
3. Strips all tags
4. Removes line breaks, tabs and extra white space
5. Strips octets

**Example:**
```php
$title = sanitize_text_field( $_POST['title'] );
update_post_meta( $post->ID, 'title', $title );
```

#### Textarea Fields
```php
sanitize_textarea_field( $input )
```
Like `sanitize_text_field()` but preserves line breaks.

#### URL Fields
```php
sanitize_url( $url )       // Alias of esc_url_raw()
esc_url_raw( $url )        // For database storage
esc_url( $url )            // For display
```

#### Email Fields
```php
sanitize_email( $email )
```

#### HTML Content
```php
wp_kses_post( $html )      // Allows HTML permitted in posts
wp_kses( $html, $allowed_tags )  // Custom allowed tags
```

### Complete Function List
- `sanitize_email()`
- `sanitize_file_name()`
- `sanitize_hex_color()`
- `sanitize_hex_color_no_hash()`
- `sanitize_html_class()`
- `sanitize_key()`
- `sanitize_meta()`
- `sanitize_mime_type()`
- `sanitize_option()`
- `sanitize_sql_orderby()`
- `sanitize_term()`
- `sanitize_term_field()`
- `sanitize_text_field()`
- `sanitize_textarea_field()`
- `sanitize_title()`
- `sanitize_title_for_query()`
- `sanitize_title_with_dashes()`
- `sanitize_user()`
- `sanitize_url()`
- `wp_kses()`
- `wp_kses_post()`

---

## 2. VALIDATING DATA

### Purpose
**Validation is the process of testing data against a predefined pattern with a definitive result: valid or invalid.**

> Data validation should be performed as early as possible. That means validating the data before performing any actions.

### Validation Philosophies

#### 1. Safelist (RECOMMENDED)
**Accept data only from a finite list of known and trusted values.**

**CRITICAL:** Use strict type checking to prevent type juggling attacks.

```php
// Comparison Operator
$untrusted_input = '1 malicious string';  // will evaluate to integer 1 during loose comparisons

if ( 1 === $untrusted_input ) {  // === is strict, == is loose
    echo '<p>Valid data';
} else {
    wp_die( 'Invalid data' );
}
```

```php
// in_array() - ALWAYS use strict parameter
$untrusted_input = '1 malicious string';
$safe_values = array( 1, 5, 7 );

if ( in_array( $untrusted_input, $safe_values, true ) ) {  // true = strict type checking
    echo '<p>Valid data';
} else {
    wp_die( 'Invalid data' );
}
```

```php
// switch() - Do your own strict comparison
$untrusted_input = '1 malicious string';

switch ( true ) {
    case 1 === $untrusted_input:  // strict comparison
        echo '<p>Valid data';
        break;
    default:
        wp_die( 'Invalid data' );
}
```

#### 2. Blocklist (RARELY RECOMMENDED)
Reject data from finite list of known untrusted values.

> This is very rarely a good idea.

#### 3. Format Detection
**Test if data is of the correct format. Only accept it if it is.**

```php
if ( ! ctype_alnum( $data ) ) {
    wp_die( "Invalid format" );
}

if ( preg_match( "/[^0-9.-]/", $data ) ) {
    wp_die( "Invalid format" );
}
```

#### 4. Format Correction
**Accept most any data, but remove or alter the dangerous pieces.**

```php
$trusted_integer = (int) $untrusted_integer;
$trusted_alpha = preg_replace( '/[^a-z]/i', "", $untrusted_alpha );
$trusted_slug = sanitize_title( $untrusted_slug );
```

### Validation Example: US Zip Code

```php
/**
 * Validate a US zip code.
 *
 * @param string $zip_code   RAW zip code to check.
 * @return bool              true if valid, false otherwise.
 */
function wporg_is_valid_us_zip_code( string $zip_code ): bool {
    // Scenario 1: empty.
    if ( empty( $zip_code ) ) {
        return false;
    }

    // Scenario 2: more than 10 characters.
    // The `maxlength` attribute is only enforced by 
    // the browser, so we still need to validate the
    // length of the input on the server to protect
    // against a manual submission.
    if ( 10 < strlen( trim( $zip_code ) ) ) {
        return false;
    }

    // Scenario 3: incorrect format.
    if ( ! preg_match( '/^\d{5}(-?\d{4})?$/', $zip_code ) ) {
        return false;
    }

    // Passed successfully.
    return true;
}
```

**Usage:**
```php
if ( isset( $_POST['wporg_zip_code'] ) && wporg_is_valid_us_zip_code( $_POST['wporg_zip_code'] ) ) {
    // $_POST['wporg_zip_code'] is valid; carry on
}
```

### Validation Example: Safelist for Query Orderby

```php
$allowed_keys = array( 'author', 'post_author', 'date', 'post_date' );
$orderby = sanitize_key( $_POST['orderby'] );

if ( in_array( $orderby, $allowed_keys, true ) ) {
    // $orderby is valid; carry on
}
```

**Note:** Sanitize before checking (with `sanitize_key()` for lowercase), then use strict `in_array()` with `true` parameter.

### Validation Helper Functions

- `balanceTags()` or `force_balance_tags()` - Balance HTML tags
- `count()` - Check how many items in array
- `in_array()` - Check if value exists in array
- `is_email()` - Validate email address
- `is_array()` - Check if variable is array
- `mb_strlen()` or `strlen()` - Check string length
- `preg_match()`, `strpos()` - Check for string occurrences
- `sanitize_html_class()` - Sanitize HTML class name
- `tag_escape()` - Sanitize HTML tag name
- `term_exists()` - Check if taxonomy term exists
- `username_exists()` - Check if username exists
- `validate_file()` - Validate file path is real

**Search for more:** `*_exists()`, `*_validate()`, `is_*()`

---

## 3. ESCAPING DATA

### Purpose
**Escaping output is the process of securing output data by stripping out unwanted data, like malformed HTML or script tags.**

> This process helps secure your data prior to rendering it for the end user.

### Key Principle: ALWAYS ESCAPE LATE

> It is best to do the output escaping as late as possible, ideally as data is being outputted.

**Why escape late:**
1. Code reviews and deploys can happen faster
2. Something could change the variable between casting and output
3. Easier automatic code scanning
4. Makes code more robust and future-proof
5. Removes ambiguity and adds clarity

**Good:**
```php
echo '<a href="'. esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
```

**Not Great:**
```php
$url = esc_url( $url );
$text = esc_html( $text );
echo '<a href="'. $url . '">' . $text . '</a>';
```

### Exception: When You Can't Escape Late

**For scripts that would be stripped by `wp_kses()`:**

Store in variable with suffix `_escaped`, `_safe` or `_clean`:

```php
$variable_escaped = '<script>safe_code();</script>';
echo $variable_escaped;  // OK because variable name indicates it's pre-escaped
```

### Core Escaping Functions

#### HTML Content
```php
esc_html( $text )          // Removes HTML
```
**Use:** Anytime HTML element encloses data being displayed
```php
<h4><?php echo esc_html( $title ); ?></h4>
```

#### HTML Attributes
```php
esc_attr( $text )          // For HTML element attributes
```
**Use:** Everything printed into HTML attributes
```php
<ul class="<?php echo esc_attr( $stored_class ); ?>">
```

#### URLs
```php
esc_url( $url )            // For display
esc_url_raw( $url )        // For database storage
```
**Use:** All URLs, including src and href attributes
```php
<img src="<?php echo esc_url( $media_url ); ?>" />
```

#### JavaScript
```php
esc_js( $text )            // For inline JavaScript
```
**Use:** Inline JavaScript values
```php
<div onclick='<?php echo esc_js( $value ); ?>' />
```

#### Textarea
```php
esc_textarea( $text )      // For textarea elements
```
**Use:** Text inside textarea
```php
<textarea><?php echo esc_textarea( $text ); ?></textarea>
```

#### XML
```php
esc_xml( $text )           // For XML blocks
ent2ncr( $text )           // Alternative for XML
```

#### HTML with Allowed Tags
```php
wp_kses( $html, $allowed_tags )     // Custom allowed tags
wp_kses_post( $html )               // Post-allowed HTML
wp_kses_data( $html )               // Comment-allowed HTML
```

**Example:**
```php
echo wp_kses(
    $partial_html,
    array(
        'a' => array(
            'href'  => array(),
            'title' => array(),
        ),
        'br'     => array(),
        'em'     => array(),
        'strong' => array(),
    )
);
```

### Escaping Examples

#### Numeric Variable
```php
echo (int) $number;          // Integer
echo (float) $number;        // Float
echo absint( $number );      // Absolute integer
echo number_format( $number );  // Formatted number
```

#### Variable in HTML Attribute
```php
// CORRECT
echo '<div id="' . esc_attr( $prefix . '-box' . $id ) . '">';

// INCORRECT - escaping parts separately
echo '<div id="' . esc_attr( $prefix ) . '-box' . esc_attr( $id ) . '">';
```

**Note:** Escape the whole string, not parts. This prevents escape character issues.

#### URL in HTML Attribute
```php
// CORRECT
echo '<a href="' . esc_url( $url ) . '">';

// INCORRECT
echo '<a href="' . esc_attr( $url ) . '">';  // Wrong function
echo '<a href="' . esc_attr( esc_url( $url ) ) . '">';  // Double escaping unnecessary
```

#### Variable in JavaScript Block
```php
<script type="text/javascript">
    var myVar = <?php echo esc_js( $my_var ); ?>
</script>
```

#### Variable in Inline JavaScript
```php
<a href="#" onclick="do_something(<?php echo esc_js( $var ); ?>); return false;">
```

#### Variable in Data Attribute for JavaScript
```php
<a href="#" data-json="<?php echo esc_js( $var ); ?>">
// OR
<a href="#" data-json="<?php echo wp_json_encode( $var ); ?>">
```

#### String in Textarea
```php
echo '<textarea>' . esc_textarea( $data ) . '</textarea>';
```

#### String in HTML Tags
```php
// If HTML NOT expected
echo '<div>' . esc_html( $phrase ) . '</div>';

// If HTML IS expected
echo '<div>' . wp_kses_post( $phrase ) . '</div>';
```

#### String in XML Context
```php
echo '<loc>' . ent2ncr( $var ) . '</loc>';
```

### Escaping with Localization

**Combined localization + escaping functions:**

```php
esc_html_e( 'Hello World', 'text_domain' );
// Same as:
echo esc_html( __( 'Hello World', 'text_domain' ) );
```

**Available functions:**
- `esc_html__()`
- `esc_html_e()`
- `esc_html_x()`
- `esc_attr__()`
- `esc_attr_e()`
- `esc_attr_x()`

### wp_localize_script() - No Escaping Needed

```php
wp_localize_script( 'handle', 'name',
    array(
        'prefix_nonce' => wp_create_nonce( 'plugin-name' ),
        'ajaxurl'      => admin_url( 'admin-ajax.php' ),
        'errorMsg'     => __( 'An error occurred', 'plugin-name' ),
    )
);
```

**WordPress escapes this automatically - no need for manual escaping.**

---

## ASNERIS SEO TOOLKIT IMPLEMENTATION

### Current Approach Compliance

✅ **Sanitization:** Using `sanitize_text_field()`, `esc_url_raw()` 
✅ **Validation:** Safelist for robots meta, schema types  
✅ **Escaping:** Using `esc_html()`, `esc_attr()`, `esc_url()` in templates  

### Additional Security Layers

The plugin goes **beyond WordPress standards** by adding:

1. **Pattern Blocking:** Rejects dangerous patterns even after sanitization
2. **Protocol Whitelisting:** Only allows `http://` and `https://`
3. **Extension Validation:** Verifies image URLs have proper extensions
4. **Format Detection:** Uses regex to validate URL formats
5. **Client-side Validation:** Shows errors before save attempt

### Best Practice Compliance

✅ Escape late (in templates)  
✅ Sanitize on input (in meta sanitize_callback)  
✅ Validate with strict type checking (`in_array(..., true)`)  
✅ Use appropriate escaping for context  
✅ Never trust user input  

---

## SUMMARY: The Security Triangle

```
┌─────────────┐
│  1. INPUT   │  
│  Sanitize   │──► Remove/clean dangerous data
└─────────────┘    
      │
      ▼
┌─────────────┐
│  2. PROCESS │
│  Validate   │──► Verify data meets requirements
└─────────────┘    
      │
      ▼
┌─────────────┐
│  3. OUTPUT  │
│   Escape    │──► Make safe for display context
└─────────────┘
```

**Always remember:** Sanitize input, validate early, escape late.
