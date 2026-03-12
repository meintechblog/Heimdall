# VenusOS Enhanced Tile Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a new Heimdall `VenusOS` enhanced app type that shows PV production, battery percentage, and grid import/export with directional styling.

**Architecture:** Create a new supported app under `app/SupportedApps/VenusOS`, read local Victron metrics via a minimal plain-MQTT PHP client, and register `VenusOS` as a private app entry so it remains selectable in the Heimdall UI. Keep the implementation dependency-free and compatible with the existing overlay deployment flow.

**Tech Stack:** Laravel 11, PHP 8.2+, Blade, existing Heimdall SupportedApps pattern, node-based asset build, sqlite-backed Heimdall app registry.

---

### Task 1: Document the feature shape

**Files:**
- Create: `docs/plans/2026-03-12-venusos-enhanced-tile-design.md`
- Create: `docs/plans/2026-03-12-venusos-enhanced-tile.md`

**Step 1: Save the approved design**

Write the design document describing the data source, UI, config fields, and update strategy.

**Step 2: Save this implementation plan**

Store the step-by-step implementation plan in the repo so the feature remains reproducible after upstream updates.

### Task 2: Write the failing tests

**Files:**
- Create: `tests/Fixtures/VenusOS.php`
- Create: `tests/Feature/VenusOSLiveStatsTest.php`
- Modify: `tests/Feature/ItemCreateTest.php`

**Step 1: Write the live-stats test**

Add a fixture class extending the real `VenusOS` class and override the raw metric fetch method so the test can inject known values.

Test expectations:

- PV renders as `W` below 1000.
- PV renders as `kW` at or above 1000.
- Battery percentage appears.
- Grid import is red/down.
- Grid export is green/up.
- Grid total is aggregated from L1/L2/L3 values.

**Step 2: Run the focused feature test and verify it fails**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-venusos.sqlite php artisan test --filter=VenusOSLiveStatsTest
```

Expected: fail because the class/files do not exist yet.

**Step 3: Add a small registry/form regression test**

Extend `ItemCreateTest` with an assertion that a `VenusOS` item with class `App\SupportedApps\VenusOS\VenusOS` is treated as enhanced.

### Task 3: Implement the new VenusOS supported app

**Files:**
- Create: `app/SupportedApps/VenusOS/VenusOS.php`
- Create: `app/SupportedApps/VenusOS/app.json`
- Create: `app/SupportedApps/VenusOS/config.blade.php`
- Create: `app/SupportedApps/VenusOS/livestats.blade.php`

**Step 1: Implement the supported app class**

Add the new class implementing `\App\EnhancedApps`.

Responsibilities:

- resolve target host from `override_url` or item URL
- connect to local MQTT on configurable port
- discover `portal_id` if missing
- request fresh values using keepalive
- read PV, battery SOC, and grid phase powers
- aggregate/format the metrics for the tile

**Step 2: Keep the MQTT logic minimal**

Implement a small internal socket-based MQTT helper inside the class or nearby helper methods:

- CONNECT
- SUBSCRIBE
- PUBLISH keepalive
- read retained/current PUBLISH packets

No new Composer dependency should be introduced.

**Step 3: Add the Blade views**

Config view:

- enable toggle
- optional override URL
- MQTT port default 1883
- optional portal ID

Live-stats view:

- `PV`
- `Battery`
- `Grid`
- CSS classes for import/export direction

### Task 4: Register the private app type

**Files:**
- Create or modify a repo-managed registration helper under `scripts/hulki/`
- Update: relevant docs if registration is part of deploy

**Step 1: Ensure VenusOS appears in Heimdall app selection**

Add a small registration helper that upserts a private `applications` row for `VenusOS` with:

- `name = VenusOS`
- `class = App\\SupportedApps\\VenusOS\\VenusOS`
- `enhanced = 1`

**Step 2: Make registration replayable after updates**

Wire this helper into the documented deploy/update flow so the app type is quickly restorable after upstream changes.

### Task 5: Verify locally

**Files:**
- Test: `tests/Feature/VenusOSLiveStatsTest.php`
- Test: `tests/Feature/ItemCreateTest.php`

**Step 1: Run the focused PHP tests**

Run:

```bash
mkdir -p database
: > database/codex-venusos.sqlite
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-venusos.sqlite php artisan test --filter='(VenusOSLiveStatsTest|ItemCreateTest)'
```

Expected: passing tests, with only existing PHP 8.5 deprecation warnings if present.

**Step 2: Re-run lint/build if frontend or Blade-related assets change**

Run:

```bash
npm run lint
npx mix
```

### Task 6: Deploy and register live

**Files:**
- Update overlay export outputs under `ops/heimdall-ui-overlay/`

**Step 1: Export overlay**

```bash
./scripts/hulki/export-heimdall-ui-overlay.sh
```

**Step 2: Sync overlay and deploy on proxi1**

Use the established host sync and live deploy flow.

**Step 3: Run the VenusOS registration helper on live**

Upsert the app type in CT100 so it appears in the Heimdall UI.

**Step 4: Attach item 36 to the new type**

Update `VenusOS Hallbude 3.11` to use the new `VenusOS` class and config if needed.

### Task 7: Verify live and document

**Files:**
- Update: `README.md`
- Update: `docs/UPDATING_HULKI_FORK.md`
- Update: `MAINTENANCE.md`

**Step 1: Verify live tile data**

Check on `http://192.168.3.88` that the Hallbude tile shows:

- PV
- Battery
- Grid with arrow/color

**Step 2: Document the new custom app**

Capture:

- what VenusOS reads
- required MQTT-on-LAN prerequisite
- how registration is replayed after updates

**Step 3: Commit and push**

Commit in focused steps and push to `origin/codex/heimdall-pimp`.
