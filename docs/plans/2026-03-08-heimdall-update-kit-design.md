# Heimdall Update Kit Design

## Context

The current Heimdall customizations live in the fork branch `codex/heimdall-dashboard-home-items` and are deployed to CT `100` on `proxi1`. There is already a host-side overlay deploy script in `/root/hulki-maint`, but it only covers an earlier subset of customized files and is not versioned together with the repo state that produced it.

For future Heimdall updates, the customization replay must be reproducible from the repo itself:

1. update the base Heimdall branch
2. rebase the customization branch
3. rebuild assets and rerun verification
4. export the exact production overlay from the repo
5. sync that overlay to `proxi1` and deploy it to CT `100`

## Approaches

### 1. Keep manual cherry-pick + SCP

- Lowest setup effort
- Error-prone and hard to repeat
- No single source of truth for the live overlay

### 2. Keep only host-side automation

- Makes deployment easier once overlay files are prepared
- Still leaves rebase/export steps undocumented and partially manual
- Repo and host drift over time

### 3. Recommended: repo-managed update kit with exported overlay and host sync

- The fork becomes the source of truth for both code and deployment payload
- Future updates follow one documented workflow
- Host deploy script can be refreshed from the repo instead of drifting separately

## Design

Add a repo-managed overlay tree under `ops/heimdall-ui-overlay/` that mirrors the target paths inside `/opt/Heimdall`. Populate it with a dedicated export script that copies the current customized source and built assets into the overlay tree.

Add a repo-managed host deploy script that:

- backs up replaced files in the CT
- pushes every file from the overlay tree into the mirrored destination path
- fixes ownership
- clears Laravel caches

Add a sync script that copies both the overlay tree and the repo-managed host deploy script to `proxi1:/root/hulki-maint`. This makes the host copy reproducible and refreshable at any time.

## Verification

- `bash -n` for the new shell scripts
- `npm run lint`
- `npm run test:js`
- `npx mix`
- script `--help` checks
- sync repo-managed overlay + host deploy script to `proxi1`

## Outcome

After the change, the next Heimdall update is handled by:

1. update `2.x`
2. rebase `codex/heimdall-dashboard-home-items`
3. run verification/build
4. run overlay export
5. sync to `proxi1`
6. run the host deploy script

This removes hidden one-off deployment state and makes replaying customizations deterministic.
