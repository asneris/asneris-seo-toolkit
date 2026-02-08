=== Asneris SEO Toolkit ===
Contributors: asneris
Tags: seo, technical seo, indexnow, search console
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Asneris: The Systematic SEO Toolkit for WordPress with intuitive UI. Clear signals, diagnostics, no ranking promises.

== Features ==
* Google site verification via meta tag
* Bing Webmaster Tools verification meta tag (msvalidate.01)
* IndexNow URL submission on publish, update, or delete (optional)
* Minimal JSON-LD output (WebSite, Organization, Article)

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
