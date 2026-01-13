=== Clarity First SEO ===
Contributors: yourwporgusername
Tags: seo, diagnostics, robots.txt, sitemap, redirects, indexnow, schema, open graph, meta tags, search console
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Clear, read-only SEO diagnostics + practical tools (robots.txt, redirects, verification, IndexNow) with simple, stakeholder-friendly explanations.

== Description ==

Clarity First SEO helps you verify *what your site is actually showing* to search engines and social platforms — without guesswork.

This plugin focuses on:
* **Facts-first diagnostics** (no “ranking score” gimmicks)
* **Clear site-wide checks** (sitemap, conflicts, indexing blocks, canonicals)
* **Per-page inspection** (fetch status, robots rules, canonical, meta, Open Graph/Twitter, schema JSON-LD)
* **Practical controls** (robots.txt editor, redirects, verification codes, IndexNow)

= Key Features =
* **Site Diagnostics**: checks for sitemap discovery, plugin conflicts, indexing blocks, canonical consistency, redirect patterns.
* **Page Diagnostics**: fetch any URL and inspect:
  - HTTP status + final URL
  - Robots meta + X-Robots-Tag header
  - Canonical tags + target status
  - Title + meta description
  - Open Graph + Twitter Card tags
  - JSON-LD schema blocks (raw view)
* **Robots.txt Editor & Validator**: safe defaults + live accessibility checks.
* **SEO Redirects**: create and manage 301/302 redirects with an active redirects table.
* **Search Engine Verification**: add verification meta codes for Google, Bing, and Yandex.
* **IndexNow**: notify participating search engines about URL updates.

= Privacy / Data =
Clarity First SEO does **not** collect personal data and does **not** send analytics/telemetry.
No external API calls are made automatically. Any external links shown are for your convenience only.

= Notes =
* SEO outcomes are not guaranteed. This plugin helps you validate configuration and output.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/clarity-first-seo/` or install via the Plugins screen.
2. Activate the plugin through the “Plugins” menu in WordPress.
3. Go to **Clarity First SEO** in the admin menu.
4. Run **Site Diagnostics** and use **Page Diagnostics** to inspect key pages.

== Frequently Asked Questions ==

= Does this plugin improve rankings? =
No. It helps you validate technical SEO signals and reduce configuration mistakes. Rankings depend on many factors.

= Does this plugin make external requests? =
No automatic external API calls. The plugin inspects your pages and shows what’s present. Any external links are optional clicks by the admin user.

= Can I use it alongside another SEO plugin? =
You can, but avoid duplicate outputs. Site Diagnostics can help identify conflicts (duplicate titles, canonicals, robots tags, schema).

= Should I enable IndexNow? =
IndexNow can speed up discovery for participating search engines (Bing and others). Google does not use IndexNow.

== Screenshots ==

1. Site Diagnostics overview (site-wide checks and status).
2. Page Diagnostics – Analyze Any URL and fetch results.
3. Page Diagnostics – Indexing Signals, Meta Tags, Social Preview, and Schema.
4. Robots.txt Editor & Validator (safe defaults + validation checks).
5. SEO Redirects (create redirect + active redirects table).
6. Verification Codes (Google/Bing/Yandex meta tag values).
7. IndexNow settings (key + notifications).
8. General Settings (site name/logo, sitemap info, default robots settings).

== Changelog ==

= 0.0.1 =
* Beta release.
* Site Diagnostics and Page Diagnostics modules.
* Robots.txt Editor & Validator.
* Redirect manager (301/302) with active table.
* Verification codes: Google/Bing/Yandex.
* IndexNow support.

== Upgrade Notice ==

= 0.0.1 =
Initial beta release.
