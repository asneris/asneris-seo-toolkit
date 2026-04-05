=== Asneris SEO Toolkit ===
Contributors: clarityfirstseo, asneris
Tags: seo, technical seo, indexnow, search console
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Asneris: The Systematic SEO Toolkit for WordPress with intuitive UI. Clear signals, diagnostics, no ranking promises.

== Description ==

Asneris SEO Toolkit is a systematic, clarity-first SEO plugin for WordPress. It validates what search engines can see on your site — it does not predict rankings or make promises about traffic.

The plugin provides clear, understandable SEO configuration with an intuitive admin interface. All features are optional and configurable by the site administrator.

**Key capabilities:**

* Google, Bing, and Yandex site verification via meta tags
* IndexNow URL submission on publish, update, or delete (optional)
* SEO titles and meta descriptions with template support and safe fallbacks
* Canonical URL output
* Robots meta defaults and per-content overrides
* Open Graph and Twitter Card social preview tags
* JSON-LD schema output (Organization, WebSite, WebPage, Article, LocalBusiness, and more)
* Site Diagnostics: site-wide checks for configuration issues
* Page Diagnostics: inspect a single URL's meta tags, headers, and redirect chain
* Robots.txt editor and validator
* Bulk Edit: update SEO fields and indexing settings for many posts/pages at once
* Sitemap helper and conflict detection for duplicate SEO plugins
* Templates system for consistent titles and descriptions
* Redirect management (301, 302, 307) with automatic slug change tracking
* Built-in help and documentation pages

**Philosophy:** SEO is not about gaming algorithms or chasing scores. It's about making your content clear, accessible, and understandable to search engines. This plugin helps you validate that clarity — nothing more, nothing less.

== Installation ==

1. Upload the `asneris-seo-toolkit` folder to the `/wp-content/plugins/` directory, or install directly through the WordPress plugin screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Asneris SEO Toolkit** in the admin menu to configure settings.
4. (Optional) Enter your Google/Bing/Yandex verification codes under the Verification tab.
5. (Optional) Enable IndexNow under the IndexNow tab for faster search engine discovery.

== Frequently Asked Questions ==

= Does this plugin guarantee higher rankings? =

No. This plugin validates what search engines can see on your site. It provides clarity about your technical SEO signals but does not predict or promise rankings.

= Will this conflict with other SEO plugins? =

Running multiple SEO plugins simultaneously may cause duplicate meta tags. The plugin includes a conflict detector that will warn you if another SEO plugin is active. We recommend using only one SEO plugin at a time.

= What is IndexNow? =

IndexNow is an open protocol that allows websites to notify participating search engines (Bing, Yandex, and others) when content is added, updated, or removed. This can help with faster discovery but does not guarantee indexing.

= Does the plugin send any data externally? =

Only when IndexNow is enabled. The plugin sends the URL of published/updated/deleted content and the IndexNow API key to the IndexNow API. No user data or personal information is transmitted. See the External Services section for full details.

= Do I need to re-save permalinks? =

If you enable IndexNow, you may need to re-save permalinks once so WordPress registers the key file URL. The plugin attempts to flush rewrite rules on activation automatically.

== Screenshots ==

1. Dashboard with configuration status overview
2. Settings page with tabbed interface
3. Page Diagnostics showing meta tag analysis
4. Bulk Edit interface for managing SEO fields
5. Gutenberg sidebar panel for per-post SEO settings

== Changelog ==

= 0.1.1 =
* Improved nonce handling and input sanitization
* Added uninstall cleanup for plugin data
* Fixed text domain consistency in JavaScript
* Added activation hook for rewrite rule flushing
* Robots.txt editor now uses WP_Filesystem API
* Removed unused files
* Improved readme.txt with required WordPress.org sections

= 0.1.0 =
* Initial release
* SEO titles, meta descriptions, and canonical URLs
* Open Graph and Twitter Card support
* JSON-LD schema output
* IndexNow integration
* Site and Page Diagnostics
* Robots.txt editor and validator
* Bulk Edit for SEO fields
* Redirect management
* Template system for titles and descriptions
* Conflict detection for duplicate SEO plugins

== IndexNow Notes ==
IndexNow requires a UTF-8 encoded key file named {key}.txt at the site root.

When IndexNow is enabled, this plugin dynamically serves the required file at:
`/{key}.txt`

After enabling IndexNow, you may need to re-save permalinks so the key URL becomes accessible.

== External Services ==
This plugin connects to the IndexNow API to notify participating search engines when URLs are added, updated, or removed.

* **Service:** IndexNow (https://www.indexnow.org/)
* **Data sent:** The URL of the content being published/updated/deleted and the IndexNow API Key (used for verification). No user data or personal information is transmitted.
* **When data is sent:** Only when IndexNow is enabled and a supported content event (publish, update, or delete) occurs.
* **Purpose:** To inform search engines about content changes for faster discovery.
* **Privacy policy:** https://www.indexnow.org/privacy

== Notes ==
This plugin does not control search rankings or guarantee indexing.
All features are optional and configurable by the site administrator.

== Build Instructions ==

This plugin uses npm and webpack for building JavaScript assets.

**Source Code Repository:**
https://github.com/asneris/asneris-seo-toolkit

**Build from Source:**

1. Clone the repository
2. Install dependencies: `npm install`
3. Build production assets: `npm run build`

The source files are in `/src/` directory and compiled output is in `/build/`.

**Development Mode:**

For development with auto-rebuild: `npm run start`
