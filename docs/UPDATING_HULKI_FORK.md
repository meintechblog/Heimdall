# Updating The Hulki Heimdall Fork

This fork carries local UI and dashboard behavior customizations on top of Heimdall `2.x`. The safe replay path after future Heimdall updates is:

1. update the base branch
2. rebase the customization branch
3. rebuild and verify
4. export the production overlay
5. sync the overlay and deploy script to `proxi1`
6. deploy from `proxi1` into CT `100`

## Branch model

- Base branch: `2.x`
- Custom branch: `codex/heimdall-pimp`

Keep `2.x` close to upstream Heimdall. Keep all Hulki-specific behavior on the custom branch.

## Files deployed to the live Heimdall CT

The overlay export currently includes:

- `app/Item.php`
- `app/Search.php`
- `app/SupportedApps/Proxmox/Proxmox.php`
- `app/SupportedApps/Proxmox/app.json`
- `app/SupportedApps/Proxmox/config.blade.php`
- `app/SupportedApps/Proxmox/livestats.blade.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Controllers/ItemController.php`
- `bootstrap/app.php`
- `config/app.php`
- `resources/assets/js/app.js`
- `resources/assets/js/dashboardFilters.js`
- `resources/assets/js/fileflowsTileControls.js`
- `resources/assets/sass/_app.scss`
- `resources/views/item.blade.php`
- `resources/views/items/livestats/fileflows.blade.php`
- `resources/views/partials/taglist.blade.php`
- `resources/views/sortable.blade.php`
- `resources/views/welcome.blade.php`
- `routes/web.php`
- `public/js/app.js`
- `public/css/app.css`
- `public/mix-manifest.json`

## Regular update workflow

From the repo root:

```bash
git fetch origin
git checkout 2.x
git pull origin 2.x

git checkout codex/heimdall-pimp
git rebase 2.x
```

If the rebase hits conflicts, resolve only in the customized files, then continue:

```bash
git add <resolved-files>
git rebase --continue
```

## Verify and rebuild

```bash
npm run lint
npm run test:js
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-suite.sqlite php artisan test --filter='(ItemCreateTest|ProxmoxLiveStatsTest)'
npx mix
```

## Shortcut scripts

For regular maintenance, use the wrapper scripts in `scripts/hulki/`:

Update the custom branch on top of the latest Heimdall base and run the key checks:

```bash
./scripts/hulki/update-heimdall-custom-branch.sh
```

Do the same and deploy live immediately afterwards:

```bash
./scripts/hulki/update-heimdall-custom-branch.sh --deploy
```

Replay the current checked-out repo state to the live Heimdall instance without rebasing:

```bash
./scripts/hulki/replay-heimdall-overlay.sh
```

## Export the live overlay from the repo

```bash
./scripts/hulki/export-heimdall-ui-overlay.sh
```

This writes the mirrored deployment payload to:

```bash
ops/heimdall-ui-overlay/
```

If the built assets are stale, the export script stops with an error. To rebuild as part of the export step, use:

```bash
./scripts/hulki/export-heimdall-ui-overlay.sh --build-assets
```

## Sync the host copy on `proxi1`

```bash
./scripts/hulki/install-heimdall-host-deploy.sh
```

This refreshes:

- `/root/hulki-maint/heimdall-ui-overlay`
- `/root/hulki-maint/deploy-heimdall-ui-overlay.sh`

The fixed host overlay path is updated via a versioned release directory and a symlink swap, so failed uploads do not first wipe the current host copy.

## Deploy from `proxi1`

```bash
ssh proxi1
/root/hulki-maint/deploy-heimdall-ui-overlay.sh
```

Optional:

```bash
/root/hulki-maint/deploy-heimdall-ui-overlay.sh --ctid 100
```

## Live checks

After deployment:

```bash
curl -I http://192.168.3.88/
curl -s http://192.168.3.88/ | rg 'app.js\\?id='
```

For the dashboard search behavior, confirm:

- typing filters tiles live
- `Enter` opens Google in a new tab
- duplicate apps that belong to multiple categories appear only once while filtered

For the FileFlows tile control, confirm:

- only the two live FileFlows tiles remain visible
- clicking the tile still opens FileFlows
- clicking the logo overlay pauses or resumes processing
- the overlay icon switches between pause and play based on the current FileFlows state
- `Fileflows MacMini 3.103` and `FileFlows NUC 3.12` stay reachable while toggling
- pause uses a long-lived native FileFlows pause, not a 1-minute pause

For the Proxmox tile customization, confirm:

- the Proxmox edit form shows the optional config block
- token ID and token value can be saved
- a configured tile shows `Guests`, `CPU`, and `RAM`
- node filtering still works when multiple nodes are configured as a comma-separated list

For live operations, keep CT100 on a normal web stack (`nginx` + `php-fpm`) instead of `php artisan serve`. The built-in Laravel dev server is acceptable for quick local testing, but it is too fragile for live dashboard traffic and multiple parallel live-stat requests.

For FileFlows tiles, the safe live behavior is now:

- unreachable FileFlows instances fall back to `Unavailable`
- the dashboard should not sit on a 15-second FileFlows timeout anymore
- the FileFlows tile button should stop spinning and become unavailable instead of returning `500`

For remote icon downloads, the secure default is now TLS verification ON. If a live Heimdall instance still needs compatibility with invalid/self-signed remote icon certificates, set `ALLOW_INSECURE_REMOTE_ICON_TLS=true` in the live `.env`, clear config cache, and treat it as a temporary exception rather than the normal setup.

## FileFlows guardrails

The FileFlows pause/resume integration is intentionally implemented with the native FileFlows pause endpoint:

- pause: `POST /api/system/pause?duration=52560000`
- resume: `POST /api/system/pause?duration=0`

Do not switch this customization to `ui-settings` writes for `PausedUntil`. On the MacMini FileFlows instance, that path caused the service to fall back to `/initial-config`.

## Notes

- Do not hand-edit `/root/hulki-maint/heimdall-ui-overlay`; regenerate it from the repo.
- Do not make direct live-file changes in the CT unless you immediately backport them into the fork.
- If Heimdall changes the affected files upstream, rerun the full verification after the rebase before exporting the overlay.
