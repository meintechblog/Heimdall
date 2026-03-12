# WLED Discovery Add-Flow Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a cache-backed WLED discovery flow that surfaces new devices behind a `+` button and lets the user add them directly as normal Heimdall items.

**Architecture:** Keep discovery off the critical dashboard render path. Use a small backend discovery service with cache and lock semantics, expose lightweight dashboard endpoints for discovery summary/candidates/create, and render a collapsible intake strip above the search area. Reuse official Heimdall WLED application metadata so created items behave like normal WLED items.

**Tech Stack:** Laravel controllers/services/cache, Blade, vanilla JS, Sass, Node test runner, PHPUnit, Laravel Mix

---

### Task 1: Lock the backend discovery contract with failing tests

**Files:**
- Create: `tests/Feature/WledDiscoveryTest.php`

**Step 1: Write a failing test for filtering existing WLED matches**

- Seed an existing item with a URL host that matches a discovered WLED IP.
- Assert the discovery summary excludes that candidate.

**Step 2: Write a failing test for creating a WLED item from a cached candidate**

- Seed a cached WLED candidate.
- POST to the create endpoint.
- Assert the item is created with the WLED app id, pinned state, and WLED tag assignment.

**Step 3: Write a failing test for the dashboard UI payload**

- Assert the dashboard response contains the discovery toggle placeholder only for admin-capable views.

**Step 4: Run the focused PHP tests and confirm failure**

### Task 2: Implement the backend discovery flow

**Files:**
- Create: `app/Support/Discovery/WledDiscoveryService.php`
- Create: `app/Support/Discovery/DiscoveredServiceFormatter.php` if needed
- Modify: `app/Http/Controllers/ItemController.php`
- Modify: `routes/web.php`

**Step 1: Implement cache-backed WLED scanning**

- Derive the private /24 from the configured Heimdall host URL.
- Probe `/json/info` with strict timeouts and chunked requests.
- Cache raw candidates and filtered candidates separately if helpful.

**Step 2: Implement existing-item matching**

- Treat a candidate as already known when any item URL host equals the candidate IP.

**Step 3: Implement dashboard endpoints**

- summary endpoint for button/count state
- candidates endpoint for prepared tiles
- create endpoint for turning a cached candidate into a normal item

**Step 4: Re-run focused PHP tests**

### Task 3: Implement the dashboard UI with failing JS tests first

**Files:**
- Create: `resources/assets/js/discoveryPanel.js`
- Create: `tests/js/discoveryPanel.test.js`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/welcome.blade.php`
- Modify: `resources/assets/js/app.js`
- Modify: `resources/assets/sass/_app.scss`

**Step 1: Write failing JS tests**

- toggle `+` to `-`
- expand/collapse the discovery strip
- render prepared WLED candidate tiles
- POST candidate creation and remove the tile from the strip

**Step 2: Add discovery placeholders to the dashboard layout**

- top-right toggle button container
- collapsible strip above search

**Step 3: Implement the JS controller**

- fetch summary after page load
- show button only when count > 0
- fetch/render candidates on expand
- create item on candidate click

**Step 4: Re-run JS tests**

### Task 4: Document, build, deploy, and verify

**Files:**
- Modify: `README.md`
- Modify: `MAINTENANCE.md`
- Modify: `public/js/app.js`
- Modify: `public/css/app.css`
- Modify: `public/mix-manifest.json`
- Modify: `ops/heimdall-ui-overlay/**`

**Step 1: Run verification**

Run:
- `npm run lint`
- `node --test tests/js/discoveryPanel.test.js tests/js/liveStatRefresh.test.js tests/js/fileflowsTileControls.test.js`
- focused `php artisan test` for the new WLED discovery tests
- `npx mix`

**Step 2: Export and deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh --overlay-dir "$PWD/ops/heimdall-ui-overlay"`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Live verification**

- confirm `+` only appears when unmatched WLEDs are cached
- confirm expanding/collapsing the panel works
- confirm clicking a candidate creates a normal Heimdall WLED item

**Step 4: Commit and push**
