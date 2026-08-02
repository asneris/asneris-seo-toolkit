# Legacy React-migrated PHP inventory

These PHP templates were part of the older server-rendered validation UI and are now superseded by the React-based admin experience. They are not part of the runtime-critical release path and are excluded from the release-validation scope.

## Excluded legacy templates

- templates/validation/tab-seo-validation.php
- templates/validation/tab-seo-validation-redesign.php
- templates/validation/url-selector.php
- templates/validation/overall-score.php
- templates/validation/group-identity.php
- templates/validation/group-indexing.php
- templates/validation/group-discovery.php
- templates/validation/group-social.php
- templates/validation/group-schema.php
- templates/validation/group-content.php
- templates/validation/group-console.php
- templates/validation/group-performance.php

## Notes

- These files are retained only for historical reference or future cleanup.
- They are not required for the current React-only admin release path.
- Release validation now focuses on the core plugin bootstrap, core includes, and uninstall logic.
