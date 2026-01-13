📌 Developer Prompt: Clarity-First SEO – UI & Architecture Redesign
Context

We are building a Clarity-First SEO WordPress plugin.
This plugin does NOT promise rankings, SEO scores, or algorithm predictions.

Its purpose is to:

validate what search engines can actually see

surface technical clarity issues

prevent accidental SEO breakage

This redesign must reflect a calm, diagnostic, validation-first philosophy.

🎯 Core Design Principles (must follow)

No SEO scores, no percentages, no grades

No “optimize” or “boost” language

Read-only diagnostics and validation

Clear separation of concerns

Explain, don’t judge

Use neutral terms like:

Found / Missing

Valid / Warning / Conflict

Indexable / Blocked

Avoid:

Good / Bad SEO

SEO Health Score

Ranking impact claims

🧱 Architecture: Required Separation

The UI must reflect three layers clearly:

1️⃣ Diagnostics (Facts only)

What exists on the page / site

No pass/fail

No recommendations

No changes

2️⃣ Validation (Interpretation)

Are there conflicts, risks, or missing signals?

Status: ✅ Pass | ⚠️ Warning | ❌ Conflict

Human-readable explanation

No auto-fix buttons

3️⃣ Settings (Configuration)

Where users intentionally change output

🧭 Required Top-Level Tabs

Redesign the plugin UI with these tabs only:

Clarity-First SEO
 ├─ Dashboard
 ├─ Diagnostics
 ├─ Validation
 ├─ Redirects
 ├─ Robots.txt
 ├─ Bulk Edit
 ├─ Settings
 └─ Help

📊 Tab-by-Tab Responsibilities
🟦 Dashboard

Purpose: high-level clarity overview

Summary of validation results

Counts only (no scoring):

X issues

Y warnings

Z passed checks

Link to Validation tab

🟦 Diagnostics (Read-only)

Purpose: show what is detected

Sections:

HTTP status

Title tags (count + values)

Meta descriptions

Canonical URLs

Meta robots

Schema blocks (JSON-LD count)

Social meta

Verification tags

Rules:

No “fix” buttons

No judgments

Raw values visible

🟦 Validation (Core feature)

Purpose: explain clarity risks

Each validation item must include:

Status: Pass / Warning / Conflict

Short explanation

“Why this matters” (1 sentence)

“What this does NOT mean” (optional)

Example:

⚠️ Canonical points to a redirected URL
This can cause search engines to ignore your preferred URL.
This does not guarantee indexing issues.

🟦 Redirects

Purpose: maintenance & safety

301 redirects only (302/307 allowed with warning)

Detect loops and chains

Validate destination (200 OK)

Clear warning text:

Redirects preserve existing value. They do not improve rankings.

🟦 Robots.txt

Purpose: crawl control

Editable robots.txt

Syntax validation

Detect:

Disallow: /

blocked assets

missing sitemap reference

“Restore safe default” option

🟦 Bulk Edit

Purpose: scale metadata safely

Bulk edit:

page titles

meta descriptions

Character guidance only (no enforcement)

Confirmation before save

No keyword scoring

🟦 Settings

Purpose: intentional configuration

Subsections:

Setup

Social

Schema

IndexNow

Verification

Templates

Advanced

Import / Export

Rules:

No hidden defaults

Explain what each setting outputs

No automation without user action

🟦 Help

Purpose: education & support

Include:

CTR explanation

Redirect explanation

Canonical explanation

“What this plugin does NOT do”

Link to GitHub Issues

🎨 UX Guidelines

Use neutral colors

Avoid red “panic” styling

Tooltips for SEO terms (CTR, canonical, robots)

Always explain context

Beta label visible

❌ Explicitly Do NOT Implement

SEO scores

Keyword density checks

Ranking predictions

Competitor analysis

AI rewriting

Backlink counts

✅ Definition of Success

The redesign is successful if:

A non-SEO user understands what is happening

No feature implies ranking guarantees

Diagnostics ≠ Validation ≠ Settings

WordPress.org reviewers see clarity and restraint

🧠 Guiding Sentence (keep visible)

“Clarity-First SEO validates what search engines can see. It does not predict rankings.”