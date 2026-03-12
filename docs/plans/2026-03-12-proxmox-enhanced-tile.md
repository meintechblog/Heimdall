# Proxmox Enhanced Tile Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Restore and version the Proxmox enhanced app in the repo, fix Proxmox configuration visibility in the item form, and show running container, CPU, and RAM stats on Proxmox Heimdall tiles.

**Architecture:** Reuse Heimdall's enhanced-app model instead of building custom controller code. Add the missing Proxmox supported-app files to the repo, repair the form condition that hides enhanced config for unconfigured items, and keep live stats flowing through the existing `get_stats/{id}` route.

**Tech Stack:** Laravel, Blade, PHP feature tests, repo-managed Heimdall overlay scripts, Proxmox API token auth

---

### Task 1: Add planning docs for the Proxmox customization

**Files:**
- Create: `docs/plans/2026-03-12-proxmox-enhanced-tile-design.md`
- Create: `docs/plans/2026-03-12-proxmox-enhanced-tile.md`

**Step 1: Save the approved design**

Capture:
- why Proxmox should stay an enhanced app
- why the form currently hides the config
- which three stats belong on the tile

**Step 2: Save the implementation plan**

Capture:
- tests to write first
- supported-app files to add
- overlay and doc updates

**Step 3: Commit**

```bash
git add docs/plans/2026-03-12-proxmox-enhanced-tile-design.md docs/plans/2026-03-12-proxmox-enhanced-tile.md
git commit -m "docs: plan proxmox enhanced tile support"
```

### Task 2: Write failing tests for the broken and desired behavior

**Files:**
- Modify: `tests/Feature/ItemCreateTest.php`
- Create: `tests/Feature/ProxmoxLiveStatsTest.php`

**Step 1: Add a form visibility test**

Write a feature test that renders the edit form for a Proxmox item with no saved enhanced config and asserts the Proxmox config inputs are still visible.

**Step 2: Run the focused test to verify it fails**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-form.sqlite php artisan test --filter=ItemCreateTest
```

Expected:
- FAIL because the form still hides the Proxmox config block

**Step 3: Add a Proxmox live-stats test**

Write a focused feature test that fakes Proxmox API responses and asserts:
- running LXC count is aggregated correctly
- CPU percentage is averaged correctly
- RAM percentage is calculated from used/total memory
- the rendered HTML contains `LXC`, `CPU`, and `RAM`

**Step 4: Run the focused test to verify it fails**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-stats.sqlite php artisan test --filter=ProxmoxLiveStatsTest
```

Expected:
- FAIL because the Proxmox app files are not yet in the repo or the tile still renders the old shape

### Task 3: Add the Proxmox enhanced app to the repo

**Files:**
- Create: `app/SupportedApps/Proxmox/Proxmox.php`
- Create: `app/SupportedApps/Proxmox/config.blade.php`
- Create: `app/SupportedApps/Proxmox/livestats.blade.php`
- Create: `app/SupportedApps/Proxmox/app.json`

**Step 1: Add the minimal working Proxmox app files**

Use the live implementation as the base, then trim the view so the tile shows only:
- LXC running / total
- CPU percentage
- RAM percentage

**Step 2: Run the Proxmox live-stats test**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-stats.sqlite php artisan test --filter=ProxmoxLiveStatsTest
```

Expected:
- PASS

### Task 4: Fix enhanced-config visibility in the item form

**Files:**
- Modify: `app/Item.php`
- Modify: `resources/views/items/form.blade.php`

**Step 1: Implement the minimal fix**

Make enhanced-app detection rely on the configured class, not on whether `description` already contains saved JSON.

**Step 2: Run the form test**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-form.sqlite php artisan test --filter=ItemCreateTest
```

Expected:
- PASS

**Step 3: Run the focused Proxmox tests together**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-suite.sqlite php artisan test --filter='(ItemCreateTest|ProxmoxLiveStatsTest)'
```

Expected:
- PASS

### Task 5: Make the overlay and docs replayable

**Files:**
- Modify: `scripts/hulki/export-heimdall-ui-overlay.sh`
- Modify: `README.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`

**Step 1: Export the Proxmox app files in the overlay**

Add the new `app/SupportedApps/Proxmox/*` files to the managed overlay export list.

**Step 2: Document the customization**

Document:
- Proxmox enhanced tile support
- required token fields
- visible stats on the tile
- replay path after upstream Heimdall updates

**Step 3: Commit**

```bash
git add app/SupportedApps/Proxmox app/Item.php resources/views/items/form.blade.php tests/Feature/ItemCreateTest.php tests/Feature/ProxmoxLiveStatsTest.php scripts/hulki/export-heimdall-ui-overlay.sh README.md docs/UPDATING_HULKI_FORK.md
git commit -m "feat: add proxmox enhanced tile support"
```

### Task 6: Build, deploy, and verify live

**Files:**
- Modify: `ops/heimdall-ui-overlay/*` if the export script is rerun

**Step 1: Run verification**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-proxmox-suite.sqlite php artisan test --filter='(ItemCreateTest|ProxmoxLiveStatsTest)'
npm run lint
npx mix
```

**Step 2: Export and deploy**

Run:

```bash
./scripts/hulki/export-heimdall-ui-overlay.sh
./scripts/hulki/install-heimdall-host-deploy.sh
ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'
```

**Step 3: Live-check**

Confirm on `http://192.168.3.88`:
- Proxmox edit form shows config fields
- a configured Proxmox tile shows `LXC`, `CPU`, and `RAM`

**Step 4: Push**

```bash
git push origin codex/heimdall-pimp
```
