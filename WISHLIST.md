# Asneris SEO Toolkit — Future Development Wishlist

Items collected during v0.1.1 development and peer review. Prioritized by impact.

---

## 🎯 UX Enhancements

- [ ] **Auto-open SEO sidebar from Bulk Edit** — When clicking the Edit (🔗) button in Bulk Edit, append `?asneris-seo-open=1` to the post edit URL and have the Gutenberg JS detect it to auto-open the Asneris SEO sidebar panel. Requires `src/index.js` change + `npm run build`.

- [ ] **Gutenberg sidebar: show template preview** — Display the resolved template title/description as a live preview below the empty SEO Title/Description fields, similar to how Bulk Edit now shows "Auto: Page Title | Site".

- [ ] **Recalculate Score button enhancement** — The SEO Score panel currently shows 0% when no manual overrides are set. Consider factoring in template-generated values when calculating the score.

---

## 🔧 Code Quality

- [ ] **Add `@since` PHPDoc tags** — Add version tags to all public methods across ~20 classes (~100+ methods). WordPress.org recommends for public APIs.

- [ ] **Replace `extract()` in templates** — All 8 `templates/validation/*.php` files use `extract($data)` implicitly. WordPress coding standards discourage this. Replace with explicit `$data['key']` access.

- [ ] **Convert anonymous closures to named methods** — Hook callbacks in `asneris-seo-toolkit.php` use anonymous functions, making them impossible to `remove_action()` from child themes. Convert major hooks to named static methods.

- [ ] **PSR-4 autoloader** — Replace 20 `require_once` statements with `spl_autoload_register()` for cleaner bootstrapping.

- [ ] **Static-to-instance refactor** — All classes use static-only architecture. Consider refactoring to instantiated classes with dependency injection for better testability (target: v1.0).

---

## ⚡ Performance

- [ ] **Cache redirect lookups** — `class-redirects.php` iterates ALL redirects on every frontend request. Use a transient or indexed lookup for sites with many redirects.

- [ ] **Async validation processing** — `class-validation.php` makes many HTTP requests synchronously on form submit. Consider background processing via WP Cron or AJAX polling for slow servers.

---

## 🛡️ Robustness

- [ ] **Schema meta key UI** — Many schema types (Event, Recipe, HowTo, Video, Job, Course) depend on custom meta keys (`_ASNERISSEO_event_start_date`, etc.) that have no admin UI to populate. Either add the UI or remove the dead code paths.

- [ ] **Uninstall cleanup for extended meta keys** — `uninstall.php` doesn't clean up schema-specific meta keys like `_ASNERISSEO_event_*`, `_ASNERISSEO_recipe_*`, `_ASNERISSEO_faq_items`, etc.

- [ ] **Import settings validation** — `ajax_import_settings()` accepts any JSON structure. Add schema validation to reject malformed imports.

- [ ] **Normalize redirect URL trailing slashes** — `handle_redirects()` uses `rtrim($request_path, '/')` inconsistently between the if/else branches.

---

## 📄 Documentation

- [ ] **Inline help for Gutenberg sidebar** — Add contextual tooltips or links in the editor sidebar panels (Search Appearance, Robots Meta, Social Media, Schema).

- [ ] **Admin notice for template system** — When templates are configured but a user manually sets a title, show a subtle note: "This overrides your template: {title} {separator} {site}".

---

*Last updated: April 6, 2026 — v0.1.1 development cycle*
