=== Asneris SEO Toolkit ===
Contributors: asneris, asiva
Tags: seo, technical seo, indexnow, search console, diagnostics
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Asneris SEO Toolkit is a WordPress plugin for technical SEO settings, diagnostics, monitoring, and indexing utilities.

== Description ==

Asneris SEO Toolkit helps WordPress site administrators configure, validate, and monitor the technical SEO signals that search engines use to discover, crawl, index, and understand websites.

The plugin does not predict rankings or guarantee indexing. All features are optional and configurable by the site administrator.

This plugin focuses on technical SEO configuration and validation using standard WordPress APIs.

**Key capabilities:**

* Modern React-powered administration interface
* Google, Bing, and Yandex site verification via meta tags
* IndexNow URL submission on publish, update, or delete (optional)
* SEO titles and meta descriptions with template support
* Canonical URL output
* Robots meta defaults and per-content overrides
* Open Graph and Twitter Card tags
* JSON-LD schema output
* Site Diagnostics
* Page Diagnostics with locally stored history
* Priority Pages (up to 30 pages)
* Page Diagnostics Live Report
* Page Diagnostics History
* 404 Monitoring (stores up to 1,000 recent records)
* Optional background processing using native WordPress Cron when supported and enabled
* Robots.txt editor and validator
* Bulk Edit
* Sitemap helper and conflict detection
* Redirect management
* Built-in help and documentation
* AI Searchability for generating llms.txt drafts and published outputs

**Philosophy:** SEO is about making your website understandable, accessible, and discoverable by search engines. Asneris SEO Toolkit validates these technical signals using standard WordPress APIs.

== Installation ==

1. Upload the plugin or install from WordPress.
2. Activate the plugin.
3. Open **Asneris SEO Toolkit**.
4. Configure your SEO settings.
5. Optionally enable IndexNow and scheduled background jobs.

== Frequently Asked Questions ==

= Does this plugin guarantee higher rankings? =

No. It validates technical SEO signals but cannot guarantee rankings or indexing.

= Will this conflict with other SEO plugins? =

Running multiple SEO plugins may generate duplicate metadata. A conflict detector is included.

= What are Priority Pages? =

Priority Pages allow you to monitor up to 30 important pages using scheduled diagnostics.

= What is Page Diagnostics? =

Page Diagnostics analyzes a page's technical SEO and stores the results locally for historical review.

= What is the Live Report? =

The Live Report performs an on-demand analysis and displays the latest results without relying on previously stored data.

= What is the History page? =

It lets you review previously saved Page Diagnostics results over time.

= What is 404 Monitoring? =

404 Monitoring records broken URL requests locally in WordPress to help identify missing pages and redirect opportunities.

= What is AI Searchability? =

AI Searchability generates and manages llms.txt content for your site so AI systems and automated tools can discover your most important public pages and resources.

= Can I edit the generated llms.txt draft before publishing? =

Yes. You can review, edit, validate, and publish the generated draft from the AI Searchability panel in the plugin admin.

= How many 404 records are stored? =

Up to 1,000 recent records.

= Does the plugin require WP-Cron? =

No. Core features work without WP-Cron. Scheduled diagnostics and maintenance are available only when native WordPress Cron is supported and enabled.

= What should I exclude from page, REST, or CDN cache? =

Asneris SEO Toolkit sends WordPress no-cache headers for its REST API responses. If your host, reverse proxy, or caching service can serve cached content before WordPress/PHP runs, exclude the plugin REST namespace from LiteSpeed Cache, SpeedLite, WP Rocket, Cloudflare Cache Rules, Nginx FastCGI Cache, Varnish, or similar systems:

`/wp-json/asneris-seo/v1/*`

For plain permalink fallback REST URLs, also exclude:

`/?rest_route=/asneris-seo/v1/*`

The plugin also appends a private cache-bypass parameter to REST read requests from its admin interface, but server or CDN exclusions are still recommended because some caches can respond before WordPress runs.

= Where are Page Diagnostics and 404 Monitoring data stored? =

All Page Diagnostics history, Priority Pages results, and 404 Monitoring records are stored locally in your WordPress database. The plugin does not send diagnostic or monitoring data to external services.

= Why does the plugin use WordPress Cron? =

WordPress Cron is used only for optional background tasks, such as scheduled Priority Page diagnostics and 404 Monitoring maintenance. These jobs run only when WP-Cron is supported and enabled by your WordPress installation and can be enabled or disabled from the plugin settings.

How Page Diagnostics & 404 Monitoring Work
Asneris SEO Toolkit stores Page Diagnostics history and 404 Monitoring
records locally in your WordPress database. No diagnostic or
monitoring data is transmitted to external services.
Page Diagnostics
Stores page diagnostic history locally for comparison over time.
Supports monitoring of up to 30 Priority Pages.
Provides both Live Reports (on-demand analysis) and History
(previously stored diagnostic results).
404 Monitoring
Automatically records up to 1,000 recent 404 requests in the
local WordPress database.
Helps identify broken links, missing pages, and redirect
opportunities.
Older records can be automatically removed to help control database
size.
WordPress Cron
Priority Pages and 404 Monitoring support optional background
processing using native WordPress Cron.
Scheduled jobs are available only when WP-Cron is supported and
enabled by the WordPress installation.
Background processing can be enabled or disabled from the plugin
settings.
All scheduled tasks run within your own WordPress installation. No
external processing service is required.
---

= Does the plugin send any data externally? =

Only IndexNow submissions when IndexNow is enabled.

== Screenshots ==

1. Dashboard
2. Settings
3. Priority Pages
4. Page Diagnostics
5. Page Diagnostics Live Report
6. Site Diagnostics
7. AI Searchability
8. 404 Monitoring
9. Redirect Management
10. Robots.txt Editor
11. Bulk Edit

== Changelog ==

= 0.1.5 =
* Added Page Diagnostics History.
* Performance, UI, UX and maintenance improvements for Page Diagnostics Pages

== IndexNow Notes ==

IndexNow dynamically serves the required key file from WordPress when enabled.

== External Services ==

Only IndexNow is used when enabled.

Service: https://www.indexnow.org/

== Notes ==

All Page Diagnostics history and 404 Monitoring data are stored locally in the WordPress database.

== Build Instructions ==

This plugin includes compiled JavaScript assets in the /build/ directory. Source files are in /src/.

Requirements:
* Node.js 18+
* npm 9+

Source Code Repository:
https://github.com/asneris/asneris-seo-toolkit

Setup and Build:
1. Clone or download the repository.
2. Open the plugin directory.
3. Install dependencies with npm install.
4. Build production assets with npm run build.
5. Compiled files are generated at /build/index.js and /build/index.asset.php.

Development Mode:
npm run start

The /build/ directory is included in the release package and is required for plugin functionality.