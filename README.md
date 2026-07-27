# Asneris SEO Toolkit (Beta)

Asneris SEO Toolkit is a modern technical SEO plugin for WordPress that
helps website owners configure, validate, and monitor the technical SEO
signals search engines use to discover, crawl, index, and understand
websites.

Rather than estimating rankings or assigning SEO scores, Asneris focuses
on factual diagnostics, structured validation, and continuous monitoring
using standard WordPress APIs.

> **Beta:** This project is under active development. Features and user
> interfaces may change between releases.

------------------------------------------------------------------------

## What's New in 0.1.4

-   Complete administration interface migrated from PHP to React.
-   New **Priority Pages** supporting up to **30** monitored pages.
-   New **Page Diagnostics Live Report**.
-   New **Page Diagnostics History**.
-   New **404 Monitoring** storing up to **1,000** recent records.
-   Optional background processing using native WordPress Cron.
-   Improved administration performance, usability and maintainability.

------------------------------------------------------------------------

## Highlights

-   React-powered administration interface
-   Technical SEO configuration
-   Site & Page Diagnostics
-   Priority Page monitoring
-   Page Diagnostics history
-   404 Monitoring
-   Local WordPress database storage
-   Optional WP-Cron automation
-   IndexNow integration
-   Redirect management
-   Robots.txt tools
-   Bulk SEO editing
-   JSON-LD Schema support

------------------------------------------------------------------------

## What this plugin does

### Search Appearance

-   SEO titles and meta descriptions
-   Canonical URLs
-   Robots meta
-   Open Graph
-   Twitter Cards

### Structured Data

-   Organization
-   WebSite
-   WebPage
-   Article
-   LocalBusiness

### Technical SEO

-   Site verification
-   IndexNow
-   Robots.txt management
-   Redirect management
-   Sitemap validation

### Diagnostics

-   Site Diagnostics
-   Page Diagnostics
-   Live Report
-   History

### Monitoring

-   Priority Pages (30)
-   404 Monitoring (1,000 records)

### Productivity

-   Bulk Edit
-   Templates
-   Built-in Help

------------------------------------------------------------------------

## Local Storage

Asneris stores Page Diagnostics history, Priority Page diagnostic
results and 404 Monitoring records **locally in the WordPress
database**.

-   Page Diagnostics history is retained for historical comparison.
-   Priority Pages supports monitoring up to **30** selected pages.
-   404 Monitoring stores up to **1,000** recent records.
-   No diagnostic or monitoring data is sent to Asneris servers.

------------------------------------------------------------------------

## Background Processing

Priority Pages and 404 Monitoring support optional scheduled processing
using **native WordPress Cron**.

-   Available only when WP-Cron is supported and enabled.
-   Can be enabled or disabled from plugin settings.
-   Runs entirely within your WordPress installation.
-   No external background processing service is required.

------------------------------------------------------------------------

## Privacy

-   No analytics or telemetry is collected.
-   Diagnostics and monitoring data remain within your WordPress
    database.
-   External requests occur only when:
    -   IndexNow is enabled.
    -   You explicitly run connectivity or validation tests.

------------------------------------------------------------------------

## Requirements

-   WordPress 6.5+
-   Tested up to WordPress 7.0
-   PHP 7.4+
-   Node.js 18+
-   npm 9+

------------------------------------------------------------------------

## Technology Stack

-   React 18
-   WordPress Components
-   @wordpress/scripts
-   Webpack
-   Babel
-   Native WordPress APIs

------------------------------------------------------------------------

## Development

``` bash
git clone https://github.com/asneris/asneris-seo-toolkit
cd asneris-seo-toolkit
npm install
npm run build
```

### Commands

  Command           Description
  ----------------- ------------------
  npm run build     Production build
  npm run start     Watch mode
  npm run lint:js   Lint JavaScript

The `/build` directory must be committed and included in release
packages.

------------------------------------------------------------------------

## Release Checklist

-   Update plugin version
-   Update Stable tag
-   Build React assets
-   Commit `/build`
-   Test on a clean WordPress installation
-   Prepare WordPress.org assets

------------------------------------------------------------------------

## Changelog

### 0.1.4

-   Migrated all administration screens from PHP to React.
-   Added Priority Pages (up to 30 pages).
-   Added Page Diagnostics Live Report.
-   Added Page Diagnostics History.
-   Added optional scheduled Page Diagnostics.
-   Added 404 Monitoring with storage for up to 1,000 records.
-   Added optional WordPress Cron background jobs when supported.
-   Performance, UI, UX and maintenance improvements.

### 0.1.3

-   WordPress.org compliance improvements.
-   Security hardening.
-   Packaging improvements.

### 0.1.2

-   Help system fixes.
-   Security improvements.
-   React enhancements.
-   WPCS and PHPStan compliance.

### 0.1.1

-   Nonce improvements.
-   Uninstall cleanup.
-   Rewrite rule activation.
-   Documentation updates.

### 0.1.0

-   Initial public beta.

------------------------------------------------------------------------

## License

GPLv2 or later

------------------------------------------------------------------------

## Support

-   GitHub Issues for bug reports and feature requests.
-   WordPress.org support forum after public release.
