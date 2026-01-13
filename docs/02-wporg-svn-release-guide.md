# WordPress.org SVN Release Guide — clarity-first-seo

This guide assumes:
- Plugin slug: clarity-first-seo
- PHP-only (no build step)
- Your source repo is GitHub (recommended), but deployment goes to WP.org SVN

## A. SVN repository structure (WP.org standard)
SVN root contains:
- /trunk        → next release candidate code
- /tags         → versioned releases (1.0.0, 1.0.1...)
- /assets       → directory listing assets (banner, icon, screenshots)

WP.org serves the plugin from the "Stable tag" in readme.txt (usually the latest tag).

## B. First-time setup (after WP.org approval)
1) Install SVN client
2) Checkout your plugin SVN repo:

   svn checkout https://plugins.svn.wordpress.org/clarity-first-seo/ clarity-first-seo-svn

You should now have:
clarity-first-seo-svn/trunk
clarity-first-seo-svn/tags
clarity-first-seo-svn/assets

## C. What you deploy into /trunk
Include:
- /assets/css
- /assets/js
- /docs (optional; can be excluded if you don’t want docs on WP.org)
- /includes
- /src/templates/validation (if used at runtime)
- main plugin PHP files
- readme.txt
- LICENSE
- uninstall.php (if relevant)

Exclude:
- .git
- .github
- node_modules (should not exist for PHP-only)
- local/dev-only files

## D. Release workflow (recommended)
### Step 1 — bump versions in GitHub
- Update plugin header: Version: 1.0.0
- Update readme.txt:
  - Stable tag: 1.0.0
  - Changelog section

Commit and tag in GitHub:
- Git tag: v1.0.0 (recommended)

### Step 2 — sync GitHub code → SVN trunk
From your SVN checkout folder:
- Copy files from your GitHub working tree into SVN trunk.

Example (from SVN repo root):
- Remove old trunk contents (keep trunk directory itself)
- Copy fresh release contents into trunk

Then:
svn status
svn add --force trunk/* --auto-props --parents --depth infinity
svn commit -m "Deploy clarity-first-seo 1.0.0 to trunk"

### Step 3 — tag the release in SVN
svn copy trunk tags/1.0.0 -m "Tag 1.0.0"

## E. Add WP.org listing images in /assets (SVN root)
Add:
- banner-772x250.png
- banner-1544x500.png
- icon-128x128.png
- icon-256x256.png
- screenshot-1.png
- screenshot-2.png ...
Update readme.txt "Screenshots" section to match numbering.

Commit:
svn add assets/*
svn commit -m "Add listing assets (banners, icons, screenshots)"

## F. Updating to a new version (1.0.1, 1.1.0...)
1) Update Version in plugin header
2) Update readme.txt Stable tag + Changelog
3) Sync code into SVN trunk
4) Commit trunk
5) svn copy trunk tags/1.0.1 -m "Tag 1.0.1"

## G. Quick troubleshooting
- If the directory still shows old version:
  - Confirm readme.txt Stable tag matches the tag you created
  - Confirm tags/1.0.0 exists and contains the correct code
- If screenshots don’t appear:
  - Confirm they are in SVN /assets (root)
  - Confirm readme.txt screenshot captions exist and numbering matches
