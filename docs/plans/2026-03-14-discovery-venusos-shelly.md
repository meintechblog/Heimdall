# Discovery VenusOS And Shelly Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Extend the Heimdall discovery inbox so VenusOS and Shelly devices are auto-detected, openable from the candidate list, and addable as real Heimdall items with the correct typed app configuration.

**Architecture:** Keep the shared discovery controller and frontend. Add dedicated backend discovery services for VenusOS and Shelly, plus a small local private Shelly app type so discovered Shelly devices do not get created as generic links or misclassified as Shelly Plug.

**Tech Stack:** Laravel controllers/services/cache, local enhanced app types, Blade, vanilla JS, PHPUnit, Laravel Mix

---

### Task 1: Lock the new behavior with failing discovery tests

**Files:**
- Create: `tests/Feature/VenusOSDiscoveryTest.php`
- Create: `tests/Feature/ShellyDiscoveryTest.php`
- Modify: `tests/Feature/DiscoveryControllerTest.php` if source aggregation coverage needs extending

**Step 1: Write a failing VenusOS candidate test**

- Fake a Victron-like HTTP redirect plus websocket marker response.
- Assert the source is `venusos` and the candidate points to the correct host URL.

**Step 2: Write a failing VenusOS add test**

- Seed a cached discovery candidate.
- Assert `Hinzufuegen` creates a typed VenusOS item with the expected config payload.

**Step 3: Write a failing Shelly candidate test**

- Fake `/shelly` and `/settings`.
- Assert the preferred candidate title uses the Shelly device name.

**Step 4: Write a failing Shelly add test**

- Seed a cached discovery candidate.
- Assert `Hinzufuegen` creates a typed Shelly item and links it to the Shelly group when present.

**Step 5: Run the focused PHP tests and verify they fail for the expected reasons**

### Task 2: Implement backend discovery services and registration

**Files:**
- Create: `app/Support/Discovery/VenusOSDiscoveryService.php`
- Create: `app/Support/Discovery/ShellyDiscoveryService.php`
- Modify: `app/Http/Controllers/DiscoveryController.php`
- Modify: `config/app.php`

**Step 1: Add VenusOS discovery**

- Reuse the existing scan/cache structure.
- Detect Victron/Venus devices from the lightweight local HTTP markers.
- Create real VenusOS items with the current private app id/class/config shape.

**Step 2: Add Shelly discovery**

- Detect devices via `/shelly`.
- Use `/settings` for better titles when available.
- Create real Shelly items against the new local Shelly app type.

**Step 3: Register both services in the shared controller**

- Keep summary/candidates/store generic.
- Preserve stable sorting across all sources.

### Task 3: Add the Shelly private app type

**Files:**
- Create: `app/SupportedApps/Shelly/Shelly.php`
- Create: `app/SupportedApps/Shelly/app.json`
- Create: `app/SupportedApps/Shelly/config.blade.php`
- Create: `app/SupportedApps/Shelly/livestats.blade.php`
- Add icon asset if needed
- Modify deploy/export overlay scripts

**Step 1: Create a minimal private Shelly type**

- Enough for Heimdall to treat it as a real application type.
- Keep it simple unless live stats are needed later.

**Step 2: Register Shelly in the live deploy helper**

- Mirror the VenusOS private-app registration path.

### Task 4: Verify, deploy, and document

**Files:**
- Modify: `README.md`
- Modify: `MAINTENANCE.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`
- Modify: overlay export/deploy scripts

**Step 1: Run verification**

Run:
- `npm run lint`
- focused `node --test tests/js/discoveryPanel.test.js`
- focused `php artisan test` for `VenusOSDiscoveryTest`, `ShellyDiscoveryTest`, and `DiscoveryControllerTest`
- `npx mix`

**Step 2: Export and deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Live verification**

- confirm a real VenusOS host is discoverable
- confirm a real Shelly host is discoverable
- confirm add creates typed items
- confirm `+` behavior remains unchanged for existing sources

**Step 4: Commit and push**
