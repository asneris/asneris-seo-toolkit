=== GSC Clarity SEO ===
Contributors: gscclarity
Tags: seo, google search console, bing webmaster tools, indexnow
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 0.2.0
License: GPLv2 or later

Lightweight SEO plugin focused on clean, deterministic signals for Google Search Console and Bing.

== Features ==
* Google site verification meta tag
* Bing Webmaster Tools verification meta tag (msvalidate.01)
* IndexNow submission on publish/update/delete (optional)
* Minimal JSON-LD (WebSite + Organization + Article)

== IndexNow Notes ==
IndexNow requires hosting a UTF-8 key file at the site root named {key}.txt containing the key.
This plugin serves that file dynamically at /{key}.txt when IndexNow is enabled.
You may need to re-save permalinks after enabling IndexNow so the key URL starts working.
