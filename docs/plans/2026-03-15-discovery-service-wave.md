# Discovery Service Wave Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add six more LAN service discovery sources to the Heimdall discovery inbox: Node-RED, go2rtc, openWB, openDTU, Homebridge, and Home Assistant.

**Architecture:** Keep the shared `DiscoveryController` and panel. Add one service class per source, using the existing cached discovery pattern, but compare these candidates by concrete base URL including port so services on the same host do not hide each other.

**Tech Stack:** Laravel controller/service/cache, PHPUnit, existing discovery frontend, overlay deployment scripts

---

### Task 1: Lock the new sources with failing tests

**Files:**
- Create: `tests/Feature/ServiceDiscoverySourcesTest.php`
- Modify: `tests/Feature/DiscoveryControllerTest.php`

**Step 1: Write failing source tests**

- Fake each source endpoint and assert:
  - correct `source`
  - correct prepared title/subtitle
  - correct URL including port where needed

**Step 2: Write failing add-flow tests**

- Seed cached candidates for each new source.
- Assert `Hinzufuegen` creates normal Heimdall items.

**Step 3: Write a failing identity regression test**

- Seed an existing item on the same host but a different port.
- Assert a new candidate on another port still appears.

**Step 4: Run the focused test file and verify it fails for the expected reason**

### Task 2: Implement the six discovery services

**Files:**
- Create: `app/Support/Discovery/NodeRedDiscoveryService.php`
- Create: `app/Support/Discovery/Go2rtcDiscoveryService.php`
- Create: `app/Support/Discovery/OpenWBDiscoveryService.php`
- Create: `app/Support/Discovery/OpenDTUDiscoveryService.php`
- Create: `app/Support/Discovery/HomebridgeDiscoveryService.php`
- Create: `app/Support/Discovery/HomeAssistantDiscoveryService.php`
- Modify: `app/Http/Controllers/DiscoveryController.php`
- Modify: `config/app.php`
- Modify: `scripts/hulki/export-heimdall-ui-overlay.sh`

**Step 1: Implement the per-source detection logic**

- Use the agreed probe URLs and markers.
- Keep titles stable and generic.

**Step 2: Use URL identity, not host identity**

- Compare candidates against existing items by normalized base URL including port.
- Avoid false hiding when the same host runs multiple services.

**Step 3: Register the new sources**

- Add all six classes to the shared discovery controller.
- Add env-backed config sections for each source.

### Task 3: Verification, live deploy, and docs

**Files:**
- Modify: `README.md`
- Modify: `MAINTENANCE.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`

**Step 1: Verify**

Run:
- `npm run lint`
- focused `php artisan test` for the new source test file plus `DiscoveryControllerTest`
- `php -l` on the new discovery service files

**Step 2: Deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Live-check**

- confirm the dashboard still responds normally
- confirm new candidates appear for services that are not already present
- confirm already-present services stay hidden

**Step 4: Commit and push**
