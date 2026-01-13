1️⃣ Sitemap Visibility
Scope

Site-level | Configuration validation

Purpose

Confirm that search engines can discover your sitemap.

MUST do

Detect sitemap URL (WordPress core, plugin, or custom)

Check HTTP status (200 / non-200)

Confirm sitemap is reachable

Check presence in robots.txt

Identify who controls the sitemap (WP core / plugin)

MUST NOT do

Generate a sitemap

Modify sitemap contents

Submit sitemap to search engines

Analyze URLs inside sitemap

Output type

Pass / Warning / Conflict

Explanation only

Example messages

✅ Sitemap accessible and referenced in robots.txt

⚠️ Sitemap exists but not referenced in robots.txt

❌ Sitemap not accessible (non-200)

2️⃣ Duplicate Output Detector
Scope

Site-level | Conflict prevention

Purpose

Detect conflicting SEO output from multiple sources.

MUST do

Detect active SEO plugins

Detect duplicate <title>

Detect duplicate meta description

Detect duplicate canonical tags

Detect duplicate robots tags

Detect duplicate schema blocks (same type)

MUST NOT do

Disable plugins automatically

Remove duplicate output

Judge “which plugin is better”

Suggest uninstalling plugins

Output type

Pass / Warning / Conflict

Clear conflict explanation

Example messages

✅ Single SEO output detected

⚠️ Multiple schema blocks detected

❌ Duplicate canonical tags found

3️⃣ Indexing Safety
Scope

Site-level | Risk pattern detection

Purpose

Detect site-wide signals that may prevent indexing.

MUST do

Detect global noindex defaults

Detect robots.txt blocks affecting large sections

Detect sitemap URLs returning non-200

Detect repeated redirect chains

Detect header-based index blocks (X-Robots-Tag)

MUST NOT do

Test individual pages

Predict ranking impact

Automatically change indexing settings

Submit URLs for indexing

Output type

Pass / Warning / Conflict

Pattern-based explanations

Example messages

⚠️ Some URLs blocked by robots.txt

❌ Global noindex detected

✅ No site-wide indexing blocks detected

4️⃣ Canonical Consistency
Scope

Site-level | Structural integrity

Purpose

Ensure canonical usage is consistent across the site.

MUST do

Detect pages canonicalizing to homepage

Detect canonical loops

Detect canonicals pointing to redirected URLs

Detect mixed protocol (http/https)

Detect missing canonicals on many pages

MUST NOT do

Judge keyword strategy

Enforce self-referencing canonicals

Modify canonical output

Replace user-defined canonicals

Output type

Pass / Warning / Conflict

Pattern explanations

Example messages

⚠️ Multiple pages canonicalize to homepage

❌ Canonical loop detected

✅ Canonical usage appears consistent

📌 Scope Summary Table (use this internally)
Section	Scope	Level	Output
Sitemap Visibility	Discovery	Site	Validate
Duplicate Output Detector	Conflict	Site	Validate
Indexing Safety	Crawl/Index risk	Site	Validate
Canonical Consistency	URL integrity	Site	Validate
🚫 What ALL four must avoid

❌ SEO scores
❌ Rankings
❌ Traffic predictions
❌ “Fix now” buttons
❌ Automation without consent

🧠 One-line positioning (optional UI text)

Sitemap Visibility: “Can search engines find your sitemap?”

Duplicate Output Detector: “Is your SEO output conflicting?”

Indexing Safety: “Are there site-wide indexing risks?”

Canonical Consistency: “Are canonicals used consistently?”