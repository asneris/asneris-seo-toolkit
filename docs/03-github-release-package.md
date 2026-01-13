# GitHub Release Package — clarity-first-seo

Goal:
- Keep GitHub as the source of truth
- Deploy a clean subset to WordPress.org SVN
- PHP-only, so no build pipeline required

## A. Add these files to your repo root (recommended)
- LICENSE
- readme.txt (WordPress.org listing)
- README.md (developer notes)
- .distignore (controls what deploys to WP.org)
- uninstall.php (if plugin stores options)

## B. Recommended .distignore for your structure
Create a file: .distignore

Suggested content:

# Dev / VCS
.git
.gitignore
.github
.idea
.vscode

# Local env / OS
.DS_Store
Thumbs.db

# Docs (optional)
# If you want docs on WP.org, remove these lines:
docs/
*.md

# Tests / tooling (if you add later)
tests/
phpunit.xml
composer.json
composer.lock

# Anything not needed at runtime
node_modules/
package.json
package-lock.json
pnpm-lock.yaml
yarn.lock

# If /src is only for build-time assets, exclude it.
# BUT you said you have runtime templates in:
# ./src/templates/validation
# so we keep src/templates/validation and exclude the rest of src safely by excluding specific folders later if needed.
# For now, do NOT exclude /src globally unless you confirm nothing else in /src is runtime.

## C. Minimal Git tagging convention
- Tag releases in GitHub: v1.0.0, v1.0.1, v1.1.0
- Match plugin header Version: 1.0.0 (no "v" prefix)
- Match readme.txt Stable tag: 1.0.0

## D. Optional: Simple manual “release checklist” in GitHub
Before tagging:
- Update Version in main plugin header
- Update readme.txt:
  - Stable tag
  - Changelog
- Verify no default “NoIndex” is enabled unintentionally
- Quick smoke test in WP admin

## E. Optional GitHub Actions (later)
If you want automation later:
- Action triggers on tag push (v*.*.*)
- Exports repo excluding .distignore patterns
- Pushes to WP.org SVN trunk + tags
- Pushes /assets for banners/screenshots

(We can add this later once your first manual SVN release is done.)
