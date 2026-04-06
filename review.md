📄 Asneris SEO Toolkit – Final Review Fix Checklist
🎯 Objective

Ensure the plugin passes WordPress.org Plugin Review with:

✅ Zero automated scan errors
✅ Security compliance
✅ Coding standards compliance
✅ Proper readme structure
🔴 1. Critical Fixes (Must Fix – Blocking Approval)
1.1 Remove HEREDOC / NOWDOC

❌ Not allowed:

$css = <<<'CSS'

✅ Replace with:

ob_start();
?>
<style>...</style>
<?php
$css = ob_get_clean();
1.2 Fix PHP Syntax Errors

Ensure all function calls are properly closed.

❌ Incorrect:

echo esc_html(ASNERISSEO_Validation::get_status_badge(...);

✅ Correct:

echo esc_html( ASNERISSEO_Validation::get_status_badge($value) );
1.3 Remove Inline <script> and <style>

❌ Not allowed:

<script>
<style>

✅ Use:

wp_enqueue_script(...)
wp_enqueue_style(...)
wp_add_inline_script(...)
1.4 Do NOT Loop Entire $_GET

❌ Not allowed:

foreach ($_GET as $key => $value)

✅ Use:

$value = isset($_GET['key']) 
    ? sanitize_text_field(wp_unslash($_GET['key'])) 
    : '';
🔴 2. Security Fixes (High Priority)
2.1 JSON-LD Encoding (Already Correct ✅)

Ensure:

wp_json_encode(
  $data,
  JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
2.2 Sanitize All User Inputs
Apply to:
Admin settings values
Post meta values
Any dynamic user input
Examples:
$org_name = sanitize_text_field(
  ASNERISSEO_Admin_Settings::get('org_name', get_bloginfo('name'))
);

$org_logo = esc_url_raw(
  ASNERISSEO_Admin_Settings::get('org_logo', '')
);
2.3 FAQ Schema Inputs

❌ Current:

'name' => $item['question'],
'text' => $item['answer']

✅ Fix:

'name' => sanitize_text_field($item['question']),
'text' => wp_kses_post($item['answer'])
2.4 HowTo Schema Inputs
'name' => sanitize_text_field($step['name']),
'text' => wp_kses_post($step['text'])
2.5 Breadcrumb & Category Sanitization
'name' => sanitize_text_field($category->name),
'name' => wp_strip_all_tags(get_the_title($post_id)),
🔴 3. Prefix & Naming Fixes
3.1 Fix JS Variable Prefix

❌:

asnerisseoData

✅:

asnerisSeoData
3.2 Fix Script Handle

❌:

'ASNERISSEO-editor'

✅:

'asneris-seo-editor'
3.3 Ensure Unique Prefix Everywhere

Replace all:

gscseoData
gscseoAdmin
gscseoBulkEdit

With:

asnerisSeoData
asnerisSeoAdmin
asnerisSeoBulkEdit
🟡 4. Readme Fixes
4.1 Contributors Order
Contributors: asneris, clarityfirstseo
4.2 Section Naming

Ensure:

== Installation ==

NOT:

== Development ==   (for install steps ❌)
4.3 External Services (Already Good ✅)

Must include:

Service name
Service URL
Data sent
When data is sent
Privacy policy
Terms / documentation
4.4 Source Code Availability (Already Good ✅)

Ensure:

GitHub repo is public
/src exists
Matches /build
🟡 5. Plugin Header (Final Check)
Plugin Name: Asneris SEO Toolkit
Plugin URI: https://asneris.com/asneris-seo-toolkit
Author: Asneris
Author URI: https://asneris.com
Text Domain: asneris-seo-toolkit

✅ Plugin URI ≠ Author URI
✅ Text domain matches slug