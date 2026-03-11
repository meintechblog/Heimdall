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
- Custom branch: `codex/heimdall-dashboard-home-items`

Keep `2.x` close to upstream Heimdall. Keep all Hulki-specific behavior on the custom branch.

## Files deployed to the live Heimdall CT

The overlay export currently includes:

- `app/Search.php`
- `app/Http/Controllers/ItemController.php`
- `config/app.php`
- `resources/assets/js/app.js`
- `resources/assets/js/dashboardFilters.js`
- `resources/assets/sass/_app.scss`
- `resources/views/partials/taglist.blade.php`
- `resources/views/sortable.blade.php`
- `resources/views/welcome.blade.php`
- `public/js/app.js`
- `public/css/app.css`
- `public/mix-manifest.json`

## Regular update workflow

From the repo root:

```bash
git fetch origin
git checkout 2.x
git pull origin 2.x

git checkout codex/heimdall-dashboard-home-items
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
npx mix
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

For remote icon downloads, the secure default is now TLS verification ON. If a live Heimdall instance still needs compatibility with invalid/self-signed remote icon certificates, set `ALLOW_INSECURE_REMOTE_ICON_TLS=true` in the live `.env`, clear config cache, and treat it as a temporary exception rather than the normal setup.

## Notes

- Do not hand-edit `/root/hulki-maint/heimdall-ui-overlay`; regenerate it from the repo.
- Do not make direct live-file changes in the CT unless you immediately backport them into the fork.
- If Heimdall changes the affected files upstream, rerun the full verification after the rebase before exporting the overlay.
