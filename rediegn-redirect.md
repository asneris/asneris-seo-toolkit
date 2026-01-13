Perfect. Below is developer-ready pseudo-code that you can hand over directly.
It’s explicit, deterministic, and matches Clarity-First philosophy (no guessing, no ranking talk).

I’ll structure it so it’s easy to implement in PHP.

Redirect Warning Logic — Pseudo-Code
Inputs expected per redirect
source_url
destination_url
redirect_type        // 301, 302, 307
http_status_chain    // array of statuses (optional)
created_at           // timestamp (optional but recommended)
last_checked_at


Optional but very useful:

canonical_of_destination
is_in_sitemap(source_url)
is_content_page(source_url)

Helper functions (conceptual)
is_permanent(type):
    return type == 301

is_temporary(type):
    return type == 302 OR type == 307

is_long_term(created_at):
    return now - created_at > 30 days

1️⃣ Base Redirect Validation
if destination_url is empty:
    status = CONFLICT
    message = "Redirect has no destination URL"
    stop

2️⃣ Redirect Loop Detection (Critical)
if destination_url == source_url:
    status = CONFLICT
    message = "Redirect loop detected (URL redirects to itself)"


OR

if source_url appears again in redirect_chain:
    status = CONFLICT
    message = "Redirect loop detected"

3️⃣ Redirect Chain Detection (Warning)
if length(redirect_chain) > 1:
    status = WARNING
    message = "Redirect chain detected (multiple hops)"

4️⃣ Final Destination Status Check (Critical)
final_status = get_http_status(final_destination)

if final_status >= 400:
    status = CONFLICT
    message = "Redirect destination returns non-200 status"

5️⃣ 301 Redirect Logic (Pass)
if redirect_type == 301:
    status = PASS
    message = "Permanent redirect (301)"


Additional soft check:

if canonical_of_destination exists AND canonical_of_destination != destination_url:
    status = WARNING
    message = "Destination canonical does not match redirect target"

6️⃣ 302 Redirect Warning Logic
Rule A — Always warn by default
if redirect_type == 302:
    status = WARNING
    message = "Temporary redirect (302)"

Rule B — Long-term 302 (important)
if redirect_type == 302 AND is_long_term(created_at):
    status = WARNING
    message = "302 redirect used long-term. If the move is permanent, consider 301."

Rule C — Canonical mismatch
if redirect_type == 302 AND canonical_of_destination == destination_url:
    status = WARNING
    message = "Temporary redirect combined with permanent canonical sends mixed signals"

Rule D — Sitemap conflict
if redirect_type == 302 AND is_in_sitemap(source_url):
    status = WARNING
    message = "Temporarily redirected URL appears in sitemap"

7️⃣ 307 Redirect Warning Logic
Rule A — Content page usage
if redirect_type == 307 AND is_content_page(source_url):
    status = WARNING
    message = "307 redirects are typically used for APIs or forms, not content pages"

Rule B — Long-term 307
if redirect_type == 307 AND is_long_term(created_at):
    status = WARNING
    message = "307 redirect used long-term. If permanent, use 301."

Rule C — Canonical conflict
if redirect_type == 307 AND canonical_of_destination == destination_url:
    status = WARNING
    message = "Temporary redirect combined with permanent canonical"

8️⃣ Priority Resolution (Important)

Use highest severity wins logic:

severity_order = [CONFLICT, WARNING, PASS]

if multiple messages triggered:
    show highest severity
    list secondary messages as details

9️⃣ Severity Definitions (UI contract)
Severity	Meaning
PASS	Expected, clear intent
WARNING	Temporary vs permanent intent mismatch
CONFLICT	Broken, looping, or unreachable
10️⃣ Message Tone Rules (Clarity-First)

✔ Say “may cause mixed signals”
✔ Say “temporary vs permanent intent”
❌ Never say “ranking loss”
❌ Never say “Google penalty”

11️⃣ One-line explanation to reuse in UI

Temporary redirects are safe when used temporarily.
Warnings appear only when intent and usage may not align.

Final Notes for Your Developer

This logic is deterministic — no guessing

No Google-specific assumptions

Reviewer-safe for WordPress.org

Easy to unit-test

Extensible later

If you want next, I can:

Convert this into actual PHP skeleton

Design redirect table schema

Define export format for redirects

Add tooltips mapped to each warning

Just say 👍