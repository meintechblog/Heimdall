# WLED Identity Dedup Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix the broken WLED icon and make WLED discovery treat `.local`, LAN IP, and WLAN IP as the same physical device by storing and using a MAC-based identity plus alias addresses.

**Architecture:** Extend `WledDiscoveryService` to build a normalized identity payload from `/json/info` and `/json/cfg`, merge duplicate discovery hits by MAC, and match discovery candidates against existing Heimdall items via stored identity metadata and alias hosts. Add a small WLED-only edit panel that stores alias metadata in the existing item `description` JSON and lets the user choose the active URL.

**Tech Stack:** Laravel, Blade, existing item config JSON, JS form helpers, PHPUnit, Node test runner, Laravel Mix.

---

### Task 1: Lock down current WLED failures with tests

**Files:**
- Modify: `/Users/hulki/codex/heimdall pimp/repo/tests/Feature/WledDiscoveryTest.php`
- Create: `/Users/hulki/codex/heimdall pimp/repo/tests/Feature/WledItemConfigTest.php`

**Step 1: Write failing discovery tests**

Add tests for:
- existing item stored as `http://wled-buero.local` suppresses discovery of `192.168.3.57`
- two discovered hosts with same WLED `mac` collapse into one candidate
- stored alias metadata suppresses a candidate even when the primary URL differs

**Step 2: Run focused PHP tests to verify they fail**

Run:

```bash
mkdir -p database && : > database/codex-wled-identity.sqlite
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-wled-identity.sqlite php artisan test --filter='WledDiscoveryTest|WledItemConfigTest'
```

Expected: failures proving MAC/alias behavior is missing.

### Task 2: Add the WLED identity model to discovery

**Files:**
- Modify: `/Users/hulki/codex/heimdall pimp/repo/app/Support/Discovery/WledDiscoveryService.php`

**Step 1: Build identity payload from WLED responses**

Store:
- `mac`
- `mdns`
- `aliases`
- current `host`

**Step 2: Merge duplicate discovery hits by MAC**

Use one candidate per physical device, merging all aliases into the candidate metadata.

**Step 3: Match existing items against aliases and identity**

Look at:
- current item URL host
- stored `wled_identity.aliases`
- stored `wled_identity.mac`

**Step 4: Re-run focused PHP tests**

Run the same command from Task 1 and confirm green.

### Task 3: Restore the real WLED icon

**Files:**
- Modify: `/Users/hulki/codex/heimdall pimp/repo/storage/app/public/icons/wled.png`
- Modify: `/Users/hulki/codex/heimdall pimp/repo/scripts/hulki/export-heimdall-ui-overlay.sh`

**Step 1: Replace the broken placeholder with the real icon asset**

Use the official Heimdall app icon source for WLED.

**Step 2: Ensure overlay export includes the repaired icon**

Keep repo and overlay consistent.

### Task 4: Add a WLED address selector to the item edit form

**Files:**
- Create: `/Users/hulki/codex/heimdall pimp/repo/resources/views/items/partials/wled-network-config.blade.php`
- Modify: `/Users/hulki/codex/heimdall pimp/repo/resources/views/items/form.blade.php`
- Modify: `/Users/hulki/codex/heimdall pimp/repo/resources/views/items/scripts.blade.php`
- Modify: `/Users/hulki/codex/heimdall pimp/repo/app/Http/Controllers/ItemController.php`

**Step 1: Write the failing edit-form test**

Verify an item with stored `wled_identity` renders the address section and marks the active URL.

**Step 2: Render the WLED-only config panel**

Show:
- known addresses
- active URL choice
- hidden fields for serialized identity config

**Step 3: Sync chosen address back into the real `url` field**

Keep save logic simple: selected alias becomes `url`.

**Step 4: Re-run the focused PHP tests**

Use the same SQLite test command and verify green.

### Task 5: Verify the frontend behavior

**Files:**
- Modify: `/Users/hulki/codex/heimdall pimp/repo/tests/js/discoveryPanel.test.js`

**Step 1: Add JS coverage if needed for WLED edit interactions**

Only if the new selection logic lives in browser JS.

**Step 2: Run JS tests**

```bash
node --test tests/js/discoveryPanel.test.js tests/js/liveStatRefresh.test.js tests/js/fileflowsTileControls.test.js
```

Expected: pass.

### Task 6: Build, deploy, and document

**Files:**
- Modify: `/Users/hulki/codex/heimdall pimp/repo/readme.md`
- Modify: `/Users/hulki/codex/heimdall pimp/repo/MAINTENANCE.md`
- Modify: `/Users/hulki/codex/heimdall pimp/repo/docs/UPDATING_HULKI_FORK.md`
- Modify overlay counterparts as generated output

**Step 1: Run full verification**

```bash
npm run lint
mkdir -p database && : > database/codex-wled-identity.sqlite
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-wled-identity.sqlite php artisan test --filter='WledDiscoveryTest|WledItemConfigTest|DashTest'
npx mix
```

**Step 2: Export overlay and deploy live**

```bash
./scripts/hulki/export-heimdall-ui-overlay.sh
./scripts/hulki/install-heimdall-host-deploy.sh
ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'
```

**Step 3: Live-check**

Verify:
- WLED icon is visible
- already-added WLEDs no longer show in discovery
- WLED edit page shows address aliases and active URL selection

**Step 4: Commit and push**

```bash
git add .
git commit -m "fix: dedupe wled discovery by device identity"
git push origin codex/heimdall-pimp
```
