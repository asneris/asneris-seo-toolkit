=== Clarity First SEO ===
Contributors: clarityfirstseo
Tags: seo, schema, meta tags, canonical, indexnow
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Facts-first SEO signals and diagnostics for WordPress — titles, descriptions, canonicals, robots, schema, and clear site/page checks.

== Description ==

Clarity-First SEO helps you publish **clear, single SEO signals** (title, description, canonical, robots, schema) and provides **facts-based diagnostics** so you can verify what search engines and social platforms can read.

This plugin is designed for teams who want **clarity**: readable checks, clean output, and no ranking promises.

= Key Features =

* **SEO Titles & Descriptions** — set custom values or fall back safely to templates / excerpts.
* **Canonical URLs** — consistent canonical output to avoid duplicate URL confusion.
* **Robots Controls** — site defaults plus page/post overrides (index/noindex, follow/nofollow).
* **Schema (JSON-LD)** — structured data for better understanding (rich results not guaranteed).
* **Social Preview Tags** — Open Graph and Twitter Cards.
* **Site Diagnostics** — checks sitemap discovery, duplicate signals, indexing blocks, canonical consistency, redirect patterns.
* **Page Diagnostics** — inspect a single URL’s headers/tags/redirect chain and see what’s present.
* **Robots.txt Tools** — review crawl rules in a safer UI.
* **SEO Bulk Edit** — update many posts/pages at once (titles, descriptions, indexing status).
* **IndexNow (Optional)** — notify participating search engines about URL changes.

= External Requests & Privacy (Important) =

Clarity-First SEO does **not** send tracking or telemetry to Clarity-First SEO servers.

Some features can make network requests:
* **IndexNow (optional):** If you enable IndexNow, the plugin will submit changed URLs to the IndexNow endpoint.
* **User-initiated tests:** Some diagnostics/actions may fetch your own URLs (or URLs you provide) to inspect output.

If you do not enable IndexNow and do not run tests, the plugin performs no third‑party “phoning home.”

= Notes =

* Diagnostics are **read-only**: they show facts and detected patterns, not ranking predictions.
* Structured data can help understanding, but rich results are never guaranteed.

== Installation ==

1. Upload `clarity-first-seo` to the `/wp-content/plugins/` directory, or install via the Plugins screen (once listed).
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Clarity‑First SEO → Settings** to configure defaults.
4. Use **Diagnostics** and **Validation** to review signals and site patterns.

== Frequently Asked Questions ==

= Does this plugin improve rankings instantly? =
No. It helps you publish clear technical signals and identify issues. Rankings depend on many factors.

= Does verification (Google/Bing/Yandex) improve SEO? =
Verification does not improve rankings. It grants access to webmaster tools and reports.

= Does IndexNow guarantee indexing? =
No. IndexNow can speed up discovery for participating search engines, but indexing decisions remain theirs.

= Is this plugin “safe” to use with other SEO plugins? =
Running multiple SEO plugins often creates duplicate tags. If you use another SEO plugin, disable overlapping outputs or use one primary SEO plugin.

== Screenshots ==

1. Dashboard overview (quick status and entry points)
2. Site Diagnostics (site-wide checks and patterns)
3. Page Diagnostics (single URL inspection)
4. Robots.txt tools
5. SEO Bulk Edit (titles/descriptions/indexing in bulk)
6. Settings (site identity, defaults, templates)

== Changelog ==

= 0.0.1 =
* Beta release: core SEO outputs, diagnostics pages, robots tools, bulk edit, and optional IndexNow.

== Upgrade Notice ==

= 0.0.1 =
Initial beta release.
