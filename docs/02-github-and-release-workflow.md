# GitHub + Release ZIP Workflow (Clarity First SEO)

Goal:
- Keep development in GitHub
- Publish clean zips for WP.org submission and GitHub Releases
- Ensure no dev files are included

Repository layout (you shared):
- clarity-first-seo/
  - assets/css
  - assets/js
  - docs
  - includes
  - src/templates/validation
  - *.php

---

## A. Recommended repo conventions

### Branching
- main: stable development
- release tags: v0.0.1, v0.0.2, etc.

### Versioning
- Plugin header Version: 0.0.1
- Git tag: v0.0.1
- WP SVN tag folder: /tags/0.0.1/

Keep these aligned.

---

## B. What to exclude from production ZIP

Do NOT ship:
- .git, .github
- node_modules
- package-lock.json (optional; okay to keep in repo, exclude from zip)
- docs/ (optional; WP zip can include docs, but keep it small)
- src/ (only include if used at runtime; if it’s dev-only templates, exclude)
- tests/
- composer.* (if not used in runtime)

Ship ONLY runtime code:
- clarity-first-seo.php
- includes/
- assets/ (css/js used by admin)
- languages/ (if you add translations)
- readme.txt

---

## C. Build a clean ZIP locally

From the repo root:
1) Ensure version is updated in:
   - clarity-first-seo.php
   - readme.txt

2) Create a staging folder:
   mkdir -p dist/clarity-first-seo

3) Copy runtime files:
   rsync -av \
     --exclude='.git' \
     --exclude='.github' \
     --exclude='node_modules' \
     --exclude='docs' \
     --exclude='src' \
     --exclude='tests' \
     --exclude='dist' \
     ./ dist/clarity-first-seo/

4) Zip it:
   cd dist
   zip -r clarity-first-seo-0.0.1.zip clarity-first-seo

Result:
- dist/clarity-first-seo-0.0.1.zip

Use this zip for:
- WP.org plugin submission
- GitHub Releases asset upload

---

## D. GitHub Release steps

1) Tag:
   git tag v0.0.1
   git push origin v0.0.1

2) Create Release in GitHub:
- Title: v0.0.1 (Beta)
- Attach: clarity-first-seo-0.0.1.zip
- Notes: paste the changelog entry

---

## E. Optional: Auto-deploy to WP.org SVN (CI)

Later, you can automate SVN deploy from GitHub Actions.
Concept:
- On tag vX.Y.Z, build zip → export to svn trunk → svn copy to tags/X.Y.Z → commit.

If you do this, keep secrets:
- SVN_USERNAME
- SVN_PASSWORD

---

## F. Release checklist (quick)

- [ ] Version bumped in plugin header
- [ ] Stable tag updated in readme.txt
- [ ] Changelog updated
- [ ] No external calls
- [ ] Nonces + cap checks
- [ ] ZIP contains only runtime files
- [ ] WP.org screenshots + assets updated
- [ ] Tag created in git + SVN
