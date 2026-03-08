# Dashboard Layout And Port 80 Design

## Goal
Address three related usability issues on the live Heimdall dashboard:

- remove the excessive vertical gap between category buttons and visible results
- avoid showing the same tile multiple times when one app belongs to multiple categories
- expose the running Heimdall instance on port 80 instead of port 7990

## Current State
- The homepage search and tag/category controls are already handled by `resources/assets/js/dashboardFilters.js`.
- Category-mode items are rendered once per category block in `resources/views/sortable.blade.php`, so one app can appear multiple times in the DOM.
- The dashboard layout uses a flex container in `resources/assets/sass/_app.scss` that vertically centers `#sortable`, which creates a large empty gap when only a few results are visible.
- The live CT runs Heimdall through `php artisan serve --port 7990 --host 0.0.0.0` in `heimdall.service`.

## Chosen Approach
1. Keep server-side category rendering as-is and deduplicate only the visible client-side results by item id.
2. Adjust the `#sortable` flex layout so visible content starts at the top of the results region instead of being vertically centered.
3. Move the live systemd service to port 80 by adding a clean systemd override instead of rewriting the base unit file in place.

## Why This Approach
- Client-side deduplication preserves category relationships while removing duplicate tiles from the user-facing result set.
- The layout fix is localized to the dashboard results container and does not require structural Blade changes.
- A systemd override is safer and easier to reason about than editing the installed unit directly.

## Affected Areas
- `resources/assets/js/dashboardFilters.js`
- `tests/js/dashboardFilters.test.js`
- `resources/assets/sass/_app.scss`
- `public/css/app.css`
- `public/js/app.js`
- `public/mix-manifest.json`
- live CT100 systemd override for `heimdall.service`

## Testing Strategy
- JS regression test for duplicate item ids rendering only once in visible results
- existing JS regression tests for live filtering and Enter-to-Google behavior
- build verification for Sass/JS bundle output
- live service verification:
  - port 80 responds
  - port 7990 no longer serves Heimdall

