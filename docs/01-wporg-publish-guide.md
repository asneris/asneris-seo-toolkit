# Publish “Clarity First SEO” to WordPress.org (Plugin Directory)

Plugin slug (folder name): clarity-first-seo
Plugin display name: Clarity First SEO
Launch version: 0.0.1 (Beta)

This guide covers:
1) Requesting a WP.org plugin listing
2) Uploading via WP.org SVN
3) Assets (screenshots/banners/icons)
4) Tagging releases and updates

---

## A. Pre-flight checklist (before you request the plugin)

### 1) Make sure your plugin headers are correct
In `clarity-first-seo/clarity-first-seo.php` (main plugin file), confirm:
- Plugin Name: Clarity First SEO
- Version: 0.0.1
- Requires at least: (choose your minimum WP version)
- Requires PHP: (choose your minimum PHP version)
- License: GPLv2 or later
- Text Domain: clarity-first-seo
- Domain Path: /languages

Example header (edit values as needed):
/**
 * Plugin Name:       Clarity First SEO
 * Plugin URI:        https://wordpress.org/plugins/clarity-first-seo/
 * Description:       Clear, read-only SEO diagnostics + practical tools (robots.txt, redirects, verification, IndexNow).
 * Version:           0.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            <Your Name>
 * Author URI:        <Your URL>
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       clarity-first-seo
 * Domain Path:       /languages
 */

### 2) Confirm “no external calls”
- No remote API calls from PHP by default.
- Any links in UI/help text are just links (no background requests).
- If you load external resources (fonts, scripts), remove them.
- Keep JS/CSS local under `/assets/`.

### 3) WordPress coding basics
- Escape output (`esc_html`, `esc_attr`, `wp_kses_post`)
- Nonces for all POST actions
- Cap checks for admin pages
- Use Settings API where possible
- Sanitize inputs

### 4) Add WP.org `readme.txt`
WP.org requires a plugin-directory formatted `readme.txt` in the plugin root.
Use the `docs/03-readme.txt` content provided in this chat.

---

## B. Request the plugin on WordPress.org

1) Create / log in to your WordPress.org account
2) Go to: Add New Plugin (Developer area)
3) Upload your plugin ZIP (a clean production zip — no node_modules, no .git, no dev-only files)
4) Wait for approval email
5) You will receive:
   - Your plugin URL
   - Your SVN repository URL (wp-plugins SVN)

Important: The slug is usually derived from your upload. You want it to be: `clarity-first-seo`.

---

## C. SVN structure (what WP.org expects)

After approval, you will have an SVN repo like:
https://plugins.svn.wordpress.org/clarity-first-seo/

Inside that repo you should have:
- /trunk/                  (current development version)
- /tags/0.0.1/             (release snapshot)
- /assets/                 (WP.org directory assets: banners/icons/screenshots)

### What goes where?
- Plugin code → `/trunk/`
- Release snapshot → `/tags/0.0.1/`
- Directory assets (banners/icons/screenshots) → `/assets/` (SVN root)

---

## D. First release upload steps (recommended)

### Step 1: Checkout SVN
svn checkout https://plugins.svn.wordpress.org/clarity-first-seo/ clarity-first-seo-svn

### Step 2: Copy plugin files into /trunk
Copy the *contents* of your plugin folder into:
clarity-first-seo-svn/trunk/

(trunk should contain `clarity-first-seo.php`, `/includes`, `/assets`, etc.)

### Step 3: Add directory assets
Place these in:
clarity-first-seo-svn/assets/

Recommended:
- banner-772x250.png
- banner-1544x500.png
- icon-128x128.png
- icon-256x256.png

Screenshots:
- screenshot-1.png
- screenshot-2.png
... match your `readme.txt` “Screenshots” section

### Step 4: svn add + commit trunk + assets
cd clarity-first-seo-svn
svn status
svn add trunk/* --force
svn add assets/* --force
svn commit -m "Initial commit of Clarity First SEO 0.0.1"

### Step 5: Tag the release
svn copy trunk tags/0.0.1
svn commit -m "Tag 0.0.1"

### Step 6: Confirm in WP.org page
- WP.org will read `/trunk/readme.txt`
- “Stable tag” in readme should match `0.0.1`

---

## E. Update process (future versions)

1) Update version in:
   - plugin header (Version)
   - readme.txt (Stable tag, Changelog)
2) Commit changes to trunk
3) Create a new tag:
   svn copy trunk tags/0.0.2
4) Commit tagging

---

## F. Screenshot plan (suggested, matches your current UI)

Recommended screenshots for first release:
1. Site Diagnostics (overview of checks)
2. Page Diagnostics (Analyze Any URL + results sections)
3. Robots.txt Editor & Validator
4. SEO Redirects (Add New Redirect + Active Redirects)
5. Verification Codes (Google/Bing/Yandex)
6. IndexNow settings
7. General Settings (Site Name, Logo, Sitemap, Default Robots)

Keep screenshots:
- Cropped to the main content area
- No sensitive site data
- Use a real domain in examples if possible (avoid localhost)

---

## G. Common rejection reasons (avoid these)

- “SEO” claims that imply ranking improvements (“boost rankings”, “guaranteed SEO”)
- Tracking/telemetry without explicit opt-in
- Bundling minified JS without source (prefer providing sources or clear build notes)
- Missing GPL-compatible license
- Security issues (missing nonce, capability checks)
- External calls without clear user intent

---

## H. What to set for “Tested up to”
WordPress 6.9 was released Dec 2, 2025. Set:
Tested up to: 6.9
(Only if your plugin has been tested on 6.9) 
