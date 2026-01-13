# WordPress.org Submission Checklist — clarity-first-seo

Plugin Slug: clarity-first-seo  
Type: PHP plugin (no build step)  
External Calls: None

## A. Must-have before submission
- [ ] Plugin main file header exists (Plugin Name, Version, License, Text Domain)
- [ ] License is GPLv2-or-later (recommended) and present in repository
- [ ] `readme.txt` exists in WordPress.org format (not only README.md)
- [ ] No hidden links, spam behavior, or undisclosed data collection
- [ ] Admin pages are properly capability-checked (e.g., `manage_options`)
- [ ] All user inputs are sanitized + validated
- [ ] All outputs are escaped (HTML attributes, URLs, text)
- [ ] Nonces used for all state-changing actions (save settings, create redirects, robots updates)
- [ ] Plugin does not require external services; no tracking, no remote posts

## B. Required “polish” to reduce review questions
- [ ] `uninstall.php` removes plugin options (only if you store options)
- [ ] No fatal errors when plugin is activated/deactivated
- [ ] Works with default themes and common WP setups
- [ ] Doesn’t override other SEO plugins silently (only warns/conflict-detects)

## C. Files to include in the repo root
Recommended baseline:
- [ ] `clarity-first-seo.php` (or your main plugin file)
- [ ] `readme.txt` (WordPress.org)
- [ ] `LICENSE` (GPL)
- [ ] `uninstall.php` (if options are stored)
- [ ] Optional: `README.md` (developer notes)

## D. WordPress.org listing content readiness
- [ ] “Description” explains what the plugin does in simple terms
- [ ] “Installation” steps are correct
- [ ] “FAQ” covers common questions:
  - Robots.txt editor limitations
  - Redirects & 301 vs 302
  - IndexNow (if included) and “Google not supported”
  - Verification meta tags (GSC/Bing/Yandex)
  - “No external calls” confirmation
- [ ] Screenshots:
  - 1: General settings
  - 2: Robots.txt editor & validator
  - 3: Redirects
  - 4: Site Diagnostics
  - 5: Page Diagnostics
- [ ] Changelog has at least 1.0.0

## E. Submission steps (high level)
1) Create WP.org account  
2) Submit plugin ZIP via Add New Plugin (developer submission)  
3) After approval, you’ll get an SVN repo URL  
4) Upload plugin to SVN `trunk/`, then tag `tags/1.0.0`  
5) Add `/assets/` banners/icons/screenshots in SVN for the listing page

## F. Final sanity checks
- [ ] Activate plugin → no warnings/notices in debug mode
- [ ] Run through your main admin screens quickly:
  - Settings → General
  - Robots.txt Editor
  - Redirects
  - Diagnostics pages
- [ ] Confirm default settings do not “noindex” the site accidentally
