Page Diagnostics (Overall Scope)

Level: Page (single URL)
Input: One URL (must be on your site, ideally)
Output: Detected values + counts + raw snippets (optional)
No: pass/fail scoring, rankings, recommendations, site-wide claims

1) Analyze Any URL
Purpose

Fetch and display what a crawler would receive for a specific URL.

MUST do

Accept a URL input

Fetch response using WP HTTP API

Capture:

Final URL after redirects (or show redirect chain if easy)

Response headers (selected)

HTML body (limited size)

Provide a “Run Diagnostics” action

MUST show (minimal)

Tested URL (input)

Final URL (after redirects)

Fetch status (success/error)

Timestamp

MUST NOT do

Pass/warn/fail

Crawl budget talk

Anything site-wide

Notes (implementation)

Add SSRF protection (wp_http_validate_url, reject_unsafe_urls)

Optional: restrict to same host for safety

2) Indexing Signals
Purpose

Show indexing-related signals for THIS URL only.

MUST do (signals to detect)

HTTP / headers

HTTP status code (200/3xx/4xx/5xx)

X-Robots-Tag header (if present)

Meta robots

<meta name="robots" content="...">

Detect noindex, nofollow, noarchive, etc. (just display tokens)

Redirect basics

Whether it redirects (yes/no)

Final destination URL

Redirect type if available (301/302/307/308) if you can reliably detect

MUST NOT do

“Indexed in Google” claim (you can’t know)

Suggest submitting in GSC

Site-wide warnings

Output style

“Detected: …” not “Good/Bad”

“Signal present/missing” is OK

Keep it neutral

3) Canonical Details
Purpose

Show exactly what canonical signals are present on THIS URL.

MUST do

Extract all canonical tags:

<link rel="canonical" href="...">

Report:

Count (0/1/multiple)

Value(s)

Resolve canonical target status:

HTTP status of the canonical target (HEAD preferred)

Whether canonical target redirects (optional)

Compare:

Input URL vs canonical URL (same or different)

MUST NOT do

Enforce self-canonical as “best”

Change canonicals automatically

Pattern analysis across pages (that’s site-level)

Output style

“Canonical count: 2”

“Canonical href: …”

“Canonical target status: 200”

4) Meta & Schema Inspection
Purpose

Show “snippet and sharing metadata” + structured data present on THIS URL.

MUST do

SEO snippet basics

<title> tag(s) count + values

<meta name="description"> count + values

Social meta

Open Graph:

og:title, og:description, og:url, og:image (+ width/height if present)

Twitter:

twitter:card, twitter:title, twitter:description, twitter:image

Schema

Detect JSON-LD blocks:

<script type="application/ld+json">

Report:

Count

Valid JSON parse yes/no

Optional: list top-level @type values (Article, WebSite, etc.)

Provide “Show raw JSON-LD” (collapsible)

MUST NOT do

Rich Results eligibility prediction (that’s Google’s)

Schema “score”

Recommend keyword changes

Output style

Tables are fine

Show counts + values

Keep it factual

Boundary: What belongs here vs Site-level Validation

✅ Page Diagnostics = “what this URL exposes”
✅ Site-level Validation = “patterns, conflicts, and site-wide risk”

Examples:

Duplicate Output Detector → site-level

Sitemap Visibility → site-level

Canonical loop across many pages → site-level

Canonical missing for this one URL → page-level (just show missing)

Suggested UI labels (to prevent confusion)

Analyze Any URL → “Fetch & inspect a single page”

Indexing Signals → “Signals that may affect indexing (this URL)”

Canonical Details → “Canonical tags and target status (this URL)”

Meta & Schema Inspection → “Title, description, social tags, and JSON-LD (this URL)”