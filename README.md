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

1. Clone the repo into `wp-content/plugins/asneris-seo-toolkit`
2. Activate **Asneris SEO Toolkit** in WP Admin → Plugins
3. Build editor assets (if you ship Gutenberg integration):
   - `npm install`
   - `npm run build`
4. Confirm the plugin loads without PHP errors and the admin pages render.

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

## Support / issues

- GitHub Issues: use for bugs and feature requests
- For WP.org release: support will also happen in the WP.org forum once published
