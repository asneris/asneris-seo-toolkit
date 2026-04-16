# Asneris SEO Toolkit (Beta)

Asneris is a **systematic** WordPress SEO toolkit focused on **clear signals** (titles, descriptions, canonicals, robots, schema) and **readable diagnostics**. It's built for site owners and teams who want to **see what search engines can read**—without "magic" promises.

> **Beta note:** This is an early release. UI and behaviors may change between versions.

---

## What this plugin does

### Core SEO signals
- Custom SEO titles & meta descriptions (with safe fallbacks)
- Canonical URL output
- Robots meta defaults + per-content overrides
- Open Graph / Twitter tags (social previews)
- JSON‑LD schema output (Organization / WebSite / WebPage, etc.)

### Diagnostics & validation (facts-based)
- **Site Diagnostics:** site-wide checks (sitemap discovery, duplicate signals, indexing blocks, canonical consistency, redirect patterns)
- **Page Diagnostics:** inspect a single URL and see the exact tags/headers/redirects present
- **Robots.txt tools:** view and manage crawl rules safely
- **Bulk Edit:** update SEO fields & indexing rules across many posts/pages

### IndexNow (optional)
- If enabled, the plugin can notify **IndexNow participating search engines** when URLs change.

✅ **No rank guarantees. No “instant #1” claims.** Just clear output and checks.

---

## External requests & privacy

- **No tracking / telemetry** is sent to Asneris servers.
- **External network calls happen only when you enable or trigger them**, for example:
  - **IndexNow submissions** (to the IndexNow endpoint) when IndexNow is enabled.
  - Optional **user‑initiated** connectivity tests you run inside the plugin UI.
- Diagnostics generally fetch **your own site URLs** to inspect output.

If you ship to the WordPress Plugin Directory, make sure your WP.org `readme.txt` clearly discloses any external requests.

---

## Requirements

- WordPress **5.8+**
- PHP **7.4+**
- Tested on WordPress **6.9**

---

## Project structure (repo)

```
asneris-seo-toolkit/
  assets/
    css/
    js/
  docs/
  includes/
  src/
    templates/
      validation/
  build/                     # built editor assets (required for release)
  asneris-seo-toolkit.php    # main plugin file
  readme.txt                 # WP.org readme
  README.md                  # this file
  LICENSE
```

---

## Local development

### Requirements

| Tool | Version |
|------|---------|
| Node.js | 18+ |
| npm | 9+ |
| PHP | 7.4+ |
| WordPress | 5.8+ |

### Setup

```bash
# 1. Clone into your WordPress plugins directory
cd wp-content/plugins/
git clone https://github.com/asneris/asneris-seo-toolkit

# 2. Install JavaScript dependencies
cd asneris-seo-toolkit
npm install

# 3. Build production assets
npm run build

# 4. Activate in WP Admin → Plugins
```

### Available npm commands

| Command | Description |
|---------|-------------|
| `npm run build` | Compile and minify for production (outputs to `/build/`) |
| `npm run start` | Watch mode — auto-rebuild on file save |
| `npm run lint:js` | Lint JavaScript source files |

### Build output

```
build/
  index.js           # Compiled Gutenberg sidebar panel (React)
  index.asset.php    # Auto-generated dependency manifest
```

The `/build/` directory **must be committed** and included in release ZIPs. It is required for the Gutenberg sidebar to load.

### Source structure

```
src/
  index.js           # Entry point — registers Gutenberg sidebar plugin
  components/        # React components for the sidebar panel
```

### Technology stack

- **React 18** — loaded from WordPress core (`window.React`)
- **@wordpress/scripts** — webpack + babel configuration
- **@wordpress/plugins** — Gutenberg plugin registration API

---

## Generating a WordPress.org submission ZIP

> **Prerequisites:** Node.js 18+, npm 9+, WSL (Windows Subsystem for Linux)

```bash
# 1. Clone the repository
git clone https://github.com/asneris/asneris-seo-toolkit
cd asneris-seo-toolkit

# 2. Install JavaScript dependencies
npm install

# 3. Compile React/Gutenberg assets
npm run build
# Output: build/index.js and build/index.asset.php

# 4. Generate the submission ZIP (Windows PowerShell)
.\create-wordpress-org-package.ps1
# Output: asneris-seo-toolkit-0.1.2.zip
```

The script:
- Cleans any previous ZIP and temp files
- Copies only distributable files (excludes `node_modules`, `src`, dev configs)
- Creates a Unix-compatible ZIP via WSL (required by WordPress.org)
- Places the final ZIP at the plugin root ready for upload

**Upload to:** https://wordpress.org/plugins/developers/add/

---

## Release checklist (high level)

- Bump versions consistently:
  - Plugin header `Version:`
  - `ASNERISSEO_VERSION` constant
  - WP.org `readme.txt` **Stable tag**
- Build and commit `/build` artifacts for release (if required)
- Validate output on a clean site with only this plugin active
- Prepare WP.org assets (banner/icon/screenshots)

---

## License

**GPLv2 or later** (GPL‑2.0‑or‑later).

---

## Changelog

### 0.1.2 (April 16, 2026)
- **New:** Added custom SEO columns to Pages admin list
  - SEO Title column (250px width, 2-line display with ellipsis)
  - SEO Description column (300px width, 2-line display with ellipsis)
  - Hover tooltips showing full content
- **Performance:** Added SEO score caching infrastructure to post meta
- **Enhancement:** Auto-open Gutenberg sidebar from Bulk Edit and Diagnostics links using sessionStorage
- **Security:** WordPress.org submission compliance
  - Fixed unescaped nonces in inline JavaScript
  - Added SQL prepare() statements for enhanced query security
  - Added phpcs:ignore comments for read-only URL parameter checks
- **Code Quality:** Fixed PHP syntax errors in validation templates
  - Corrected duplicate output detection logic
  - Fixed missing closing brace in canonical validation
- **Standards:** Fixed script handle case consistency (standardized to lowercase)
- **Fix:** Removed is_admin() conditional to prevent fatal class loading errors on REST API and AJAX requests
- **Fix:** Resolved React Strict Mode cleanup timing issues in auto-open feature
- **Validation:** Achieved 100% pass rate on all WordPress.org requirements
  - WPCS (WordPress Coding Standards) - 0 warnings
  - PHPStan Level 8 - 0 errors
  - PHP Compatibility 7.4-8.2 - 0 warnings
  - Security pattern scans - all passed

### 0.1.1
- **Security:** Improved nonce handling and input sanitization
- **Cleanup:** Added uninstall cleanup for plugin data
- **Fix:** Fixed text domain consistency in JavaScript
- **Enhancement:** Added activation hook for rewrite rule flushing
- **Enhancement:** Robots.txt editor now uses WP_Filesystem API
- **Cleanup:** Removed unused files
- **Documentation:** Improved readme.txt with required WordPress.org sections

### 0.1.0
- Initial release
- SEO titles, meta descriptions, and canonical URLs
- Open Graph and Twitter Card support
- JSON-LD schema output
- IndexNow integration
- Site and Page Diagnostics
- Robots.txt editor and validator
- Bulk Edit for SEO fields
- Redirect management
- Template system for titles and descriptions
- Conflict detection for duplicate SEO plugins

---

## Support / issues

- GitHub Issues: use for bugs and feature requests
- For WP.org release: support will also happen in the WP.org forum once published
