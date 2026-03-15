# Progressive Discovery Results Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make the discovery panel show new candidates progressively while a fresh discovery run is still being processed.

**Architecture:** Add a lightweight progress endpoint that advances discovery one source at a time and returns the current partial result set. Update the dashboard discovery panel to poll that endpoint only while the panel is open, then stop when the run is complete.

**Tech Stack:** Laravel, cache-backed discovery services, vanilla JS discovery panel, Node test runner, PHPUnit

---

### Task 1: Add the failing frontend test for progressive discovery polling

**Files:**
- Modify: `tests/js/discoveryPanel.test.js`
- Modify later: `resources/assets/js/discoveryPanel.js`

**Step 1: Write the failing test**

Add a test that:
- opens the discovery panel
- receives one partial progress payload first
- then a completed payload
- verifies the candidate list updates during the run

**Step 2: Run test to verify it fails**

Run: `node --test tests/js/discoveryPanel.test.js`

Expected: FAIL because the current panel only loads `/discoveries/candidates` once.

### Task 2: Add the failing backend test for the progress endpoint

**Files:**
- Modify: `tests/Feature/DiscoveryControllerTest.php`
- Modify later: `app/Http/Controllers/DiscoveryController.php`
- Modify later: `routes/web.php`

**Step 1: Write the failing test**

Add a test that:
- requests `/discoveries/progress?fresh=1`
- expects a valid JSON payload with:
  - `totalCount`
  - `candidates`
  - `completedSources`
  - `totalSources`
  - `isComplete`

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/DiscoveryControllerTest.php`

Expected: FAIL because the route and controller action do not exist yet.

### Task 3: Implement the backend progress endpoint

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/DiscoveryController.php`
- Modify: `app/Support/Discovery/AbstractUrlDiscoveryService.php`

**Step 1: Add route**

Add `GET /discoveries/progress`.

**Step 2: Add progress state helpers**

Implement cache-backed helpers for:
- starting a fresh scan
- advancing one source
- returning partial candidates

**Step 3: Add small service hooks**

Add helpers on the abstract discovery service to:
- report whether the cache exists
- warm the cache on demand

**Step 4: Run backend tests**

Run: `php artisan test tests/Feature/DiscoveryControllerTest.php`

Expected: PASS

### Task 4: Implement the frontend progressive polling loop

**Files:**
- Modify: `resources/assets/js/discoveryPanel.js`
- Modify: `resources/views/partials/discovery.blade.php`

**Step 1: Add progress URL to markup**

Expose the backend endpoint to the frontend.

**Step 2: Replace one-shot candidate loading**

When the panel opens:
- request a fresh progress run
- render partial results
- keep polling until complete

**Step 3: Stop polling cleanly**

Stop when:
- the panel is collapsed
- the document becomes hidden
- the run completes

**Step 4: Run frontend tests**

Run: `node --test tests/js/discoveryPanel.test.js`

Expected: PASS

### Task 5: Verify, build, document, deploy

**Files:**
- Modify if needed: `readme.md`
- Modify if needed: `MAINTENANCE.md`
- Modify if needed: `docs/UPDATING_HULKI_FORK.md`

**Step 1: Run targeted verification**

Run:
- `php artisan test tests/Feature/DiscoveryControllerTest.php`
- `node --test tests/js/discoveryPanel.test.js`
- `npm run lint`

**Step 2: Build assets**

Run: `npx mix`

**Step 3: Export and deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 4: Live-check**

Confirm:
- `GET /discoveries/summary` is healthy
- opening the panel shows partial results before the full run completes

**Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/DiscoveryController.php app/Support/Discovery/AbstractUrlDiscoveryService.php resources/assets/js/discoveryPanel.js resources/views/partials/discovery.blade.php tests/Feature/DiscoveryControllerTest.php tests/js/discoveryPanel.test.js readme.md MAINTENANCE.md docs/UPDATING_HULKI_FORK.md docs/plans/2026-03-15-discovery-progressive-results-design.md docs/plans/2026-03-15-discovery-progressive-results.md
git commit -m "feat: stream discovery candidates progressively"
```
