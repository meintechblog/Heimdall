---
name: heimdall-app-type-extension
description: Use when enriching an existing Heimdall application type or creating a new Foundation or Enhanced app type, especially in the Hulki fork with live tile stats, shared loading states, and overlay deployment.
---

# Heimdall App-Type Extension

## Overview

Use this skill when Heimdall needs a smarter application tile: more live data, a new supported app type, or a tile-specific interaction that still has to fit the existing Hulki dashboard style.

This skill combines the official Heimdall enhanced-app structure with the concrete rules already proven in this repo.

## When to Use

Use this skill when you need to:

- enrich an existing Heimdall app type with better live stats
- add a new private or custom app type
- decide whether something should be a `Generic`, `Foundation`, or `Enhanced` app
- add tile-specific behavior without breaking the shared Heimdall look and performance rules
- make an app-type change that must be versioned, deployed live, and survive future Heimdall updates

Do not use this skill for:

- simple text/icon/color edits on a normal link tile
- changes that are unrelated to Heimdall application types or live-stat tiles

## Official Heimdall Baseline

Start from the official model:

- Heimdall supports `Generic`, `Foundation`, and `Enhanced Apps`
- Enhanced apps expose extra config and render live stats on the tile
- The usual enhanced-app structure is:
  - `app/SupportedApps/<Name>/app.json`
  - `app/SupportedApps/<Name>/<Name>.php`
  - `app/SupportedApps/<Name>/config.blade.php`
  - `app/SupportedApps/<Name>/livestats.blade.php`

Official references:

- [Heimdall site](https://heimdall.site)
- [Official repo README](https://github.com/linuxserver/Heimdall)

## Decision Order

1. Check whether the app already exists in Heimdall.
2. If it exists, prefer extending or restoring the existing app path instead of adding controller-only special cases.
3. If the app does not exist and needs live API data, create a new `Enhanced App`.
4. If it only needs a static icon/color default, consider `Foundation`.
5. If the request is only about tile behavior, prefer shared UI paths before inventing app-specific front-end code.

## Repo-First Workflow

In this repo, inspect these paths before changing anything:

- `app/SupportedApps/`
- `resources/views/item.blade.php`
- `resources/assets/js/liveStatRefresh.js`
- `resources/assets/js/fileflowsTileControls.js`
- `resources/assets/sass/_app.scss`
- `README.md`
- `MAINTENANCE.md`
- `docs/plans/2026-03-12-heimdall-tile-ci-design.md`

Also inspect the nearest app-specific design notes when relevant:

- `docs/plans/2026-03-12-proxmox-enhanced-tile-design.md`
- `docs/plans/2026-03-12-venusos-enhanced-tile-design.md`
- `docs/plans/2026-03-12-global-tile-icon-spinner-design.md`
- `docs/plans/2026-03-12-fileflows-logo-toggle-refinement-design.md`

## Hulki Fork Rules

### Shared Tile UI

- Live-stat loading belongs on the left icon area, not in the stat text area.
- Reuse the shared icon-spinner pattern:
  - `tile-icon-loading-overlay`
  - `tile-icon-loading-visual`
  - `tile-icon-loading-spinner`
- Keep stat density compact and comparable across app types.
- If an app needs a special control on the icon area, keep the rest of the tile opening normally.
- FileFlows is the special-case model for icon-area controls, not the default for every app.

### Performance Rules

- Keep polling visibility-aware.
- Pause live polling when the browser tab is inactive.
- Keep staggered initial loading.
- On timeout/failure, remove the loading treatment and avoid broken placeholders.

### Security Rules

- Do not disable TLS verification by default.
- If an insecure fallback is absolutely needed, make it an explicit opt-in and document it.
- Prefer official/internal config mechanisms over hidden one-off bypasses.
- Do not repeat the earlier unsafe remote-icon pattern that disabled certificate checks by default.

### Repo and Deploy Rules

- Make changes in the repo, not directly in the live container.
- Keep the overlay export path current.
- Document the feature in the repo when it changes user-visible behavior or maintenance steps.
- If you add files under `app/SupportedApps`, remember this repo may require force-adding those files because the parent path is ignored.

## Implementation Checklist

1. Design the change first and capture it under `docs/plans/`.
2. Add or update focused regression tests before implementation.
3. Prefer enhancing existing app-type files over controller-only exceptions.
4. Reuse the shared tile loading and polling behavior.
5. Keep security defaults safe.
6. Build and verify:
   - `npm run lint`
   - focused `node --test ...`
   - focused `php artisan test ...` when PHP behavior changes
   - `npx mix`
7. Export and deploy:
   - `./scripts/hulki/export-heimdall-ui-overlay.sh`
   - `./scripts/hulki/install-heimdall-host-deploy.sh --overlay-dir "$PWD/ops/heimdall-ui-overlay"`
   - `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`
8. Verify live on `http://192.168.3.88`
9. Commit and push

## What Good Looks Like

- The app type is understandable from the edit form and tile behavior.
- The tile looks like it belongs with the rest of the Hulki Heimdall UI.
- Slow or broken upstream services do not drag the whole dashboard down.
- The change is replayable after Heimdall updates.
- The implementation is documented well enough that a future Codex session can continue without rediscovering the workflow.
