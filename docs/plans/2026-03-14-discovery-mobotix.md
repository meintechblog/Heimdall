# Discovery Mobotix Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add Mobotix camera discovery to the existing Heimdall discovery inbox and create normal Heimdall items from those candidates.

**Architecture:** Keep the shared `DiscoveryController` and panel. Add a dedicated `MobotixDiscoveryService` that probes `/`, recognizes the Mobotix redirect marker, prepares lightweight candidates, and creates plain Heimdall link items.

**Tech Stack:** Laravel controller/service/cache, PHPUnit, existing discovery frontend, overlay deployment scripts

---

### Task 1: Lock Mobotix behavior with failing tests

**Files:**
- Create: `tests/Feature/MobotixDiscoveryTest.php`
- Modify: `tests/Feature/DiscoveryControllerTest.php`

**Step 1: Write a failing candidate test**

- Fake `http://192.168.3.21/` with a `302` redirect to `/control/userimage.html`.
- Assert the candidate source is `mobotix` and the title/subtitle are prepared correctly.

**Step 2: Write a failing add test**

- Seed a cached Mobotix candidate.
- Assert `Hinzufuegen` creates a normal item with the expected URL and title.

**Step 3: Write a failing already-known-host test**

- Seed an existing item for the same host.
- Assert the Mobotix candidate no longer appears.

**Step 4: Run the focused test file and verify it fails for the expected reason**

### Task 2: Implement the Mobotix discovery source

**Files:**
- Create: `app/Support/Discovery/MobotixDiscoveryService.php`
- Modify: `app/Http/Controllers/DiscoveryController.php`
- Modify: `config/app.php`

**Step 1: Add the Mobotix service**

- Reuse the same cache/lock/host-scan structure as the other plain discovery sources.
- Detect candidates from the `/ -> /control/userimage.html` redirect marker.

**Step 2: Create normal Heimdall items**

- No app type, no enhanced config.
- Keep the creation flow aligned with AWTRIX/ESPresense behavior.

**Step 3: Register the source**

- Add Mobotix to summary/candidates/store aggregation through the shared controller.

### Task 3: Verification, deploy, and docs

**Files:**
- Modify: `README.md`
- Modify: `MAINTENANCE.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`
- Modify: `scripts/hulki/export-heimdall-ui-overlay.sh`

**Step 1: Verify**

Run:
- `npm run lint`
- focused `php artisan test` for `MobotixDiscoveryTest` and `DiscoveryControllerTest`
- `php -l app/Support/Discovery/MobotixDiscoveryService.php`

**Step 2: Deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Live-check**

- confirm `192.168.3.21` and `192.168.3.24` are visible as Mobotix candidates when not already present
- confirm adding a Mobotix device creates a normal Heimdall item
- confirm the dashboard still responds normally

**Step 4: Commit and push**
