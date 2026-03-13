# Discovery Multi-Source Expansion Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Expand the discovery intake so WLED and ESPresense devices are detected separately, candidate cards can be opened directly, and the discovery UI lives next to the search field with full-width tiles.

**Architecture:** Keep the shared discovery controller and panel, but move device-specific logic into per-source discovery services. The frontend renders shared candidate tiles with separate actions for "open device" and "add item". Discovery remains cache-backed, chunked, and lightweight so it does not drag down dashboard responsiveness.

**Tech Stack:** Laravel controllers/services/cache, Blade, vanilla JS, Sass, PHPUnit, Node test runner, Laravel Mix

---

### Task 1: Lock the new backend contract with failing tests

**Files:**
- Modify: `tests/Feature/WledDiscoveryTest.php`
- Create: `tests/Feature/EspresenseDiscoveryTest.php`

**Step 1: Write a failing WLED test for real-device discrimination**

- Fake a WLED-like response and an ESPresense response on `/json/info`.
- Assert only the real WLED response becomes a WLED candidate.

**Step 2: Write a failing WLED test for title suggestion from mDNS**

- Fake `/json/info` plus `/json/cfg`.
- Assert the candidate title prefers `id.mdns`.

**Step 3: Write a failing ESPresense test for title suggestion from room**

- Fake `/json/info` with `room`.
- Assert the ESPresense candidate title uses the room name.

**Step 4: Run the focused PHP tests and verify they fail for the expected reasons**

### Task 2: Implement multi-source backend discovery

**Files:**
- Create: `app/Support/Discovery/EspresenseDiscoveryService.php`
- Modify: `app/Http/Controllers/DiscoveryController.php`
- Modify: `app/Support/Discovery/WledDiscoveryService.php`
- Modify: `config/app.php`

**Step 1: Add the ESPresense discovery service**

- Reuse the shared matching and creation shape used by WLED.
- Give ESPresense its own cache key, lock key, and label.

**Step 2: Tighten WLED detection**

- Reject payloads that are actually ESPresense.
- Fetch `/json/cfg` for surviving WLED candidates to extract `id.mdns`.

**Step 3: Aggregate both sources through the controller**

- Keep `summary`, `candidates`, and `store` generic.
- Ensure sorting stays stable across mixed sources.

**Step 4: Re-run focused PHP tests**

### Task 3: Redesign the discovery panel with failing JS tests first

**Files:**
- Modify: `tests/js/discoveryPanel.test.js`
- Modify: `resources/assets/js/discoveryPanel.js`
- Modify: `resources/views/partials/discovery.blade.php`
- Modify: `resources/views/partials/search.blade.php` if needed
- Modify: `resources/assets/sass/_app.scss`
- Modify: `app/Search.php`

**Step 1: Write failing JS tests for the new interaction contract**

- card click opens the candidate URL
- add button posts the add request
- `+` lives beside the search field
- candidate area renders full-width tile rows below search

**Step 2: Move the toggle into the search row**

- Keep the search field as the primary anchor.
- Render the panel directly below it.

**Step 3: Split open vs add interactions**

- card body navigates
- add button stays the create action
- loading state only applies to add

**Step 4: Re-run the focused JS tests**

### Task 4: Verify, deploy, and document

**Files:**
- Modify: `README.md`
- Modify: `MAINTENANCE.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`
- Modify: `ops/heimdall-ui-overlay/**`

**Step 1: Run verification**

Run:
- `npm run lint`
- `node --test tests/js/discoveryPanel.test.js tests/js/liveStatRefresh.test.js tests/js/fileflowsTileControls.test.js`
- focused `php artisan test` for `WledDiscoveryTest` and `EspresenseDiscoveryTest`
- `npx mix`

**Step 2: Export and deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Live verification**

- confirm the `+` sits by the search field
- confirm discovery candidates open on card click
- confirm `Hinzufuegen` creates a normal Heimdall item
- confirm `192.168.3.239` appears as ESPresense
- confirm WLED names prefer mDNS when available

**Step 4: Commit and push**
