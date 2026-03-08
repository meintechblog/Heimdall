# Dashboard Layout And Port 80 Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove duplicate visible dashboard tiles, pull the filtered result area directly under the category buttons, and move the live Heimdall service from port 7990 to port 80.

**Architecture:** The app layer will keep category rendering unchanged and deduplicate visible tiles client-side by `data-id` inside `resources/assets/js/dashboardFilters.js`. The layout gap will be fixed by changing `#sortable` alignment in the Sass source, then rebuilding the frontend bundle. The live CT service will be moved to port 80 through a systemd override so the packaged unit remains intact.

**Tech Stack:** Laravel, Blade, Sass, jQuery, Node test runner, jsdom, systemd

---

### Task 1: Add Duplicate-Visibility Regression Coverage

**Files:**
- Modify: `tests/js/dashboardFilters.test.js`
- Test: `resources/assets/js/dashboardFilters.js`

**Step 1: Write the failing test**

Add a JS test with one app rendered in two category wrappers using the same `data-id`. Assert that only one visible tile remains after dashboard filters initialize.

**Step 2: Run test to verify it fails**

Run: `npm run test:js`
Expected: FAIL because duplicate tiles are currently visible.

**Step 3: Write minimal implementation**

Update `resources/assets/js/dashboardFilters.js` to hide duplicate visible tiles after the normal filter pass, keeping the first visible item for each `data-id`.

**Step 4: Run test to verify it passes**

Run: `npm run test:js`
Expected: PASS

### Task 2: Fix Dashboard Result Layout Gap

**Files:**
- Modify: `resources/assets/sass/_app.scss`
- Build: `public/css/app.css`

**Step 1: Make the smallest layout change**

Adjust `#sortable` alignment so the result area starts at the top and does not vertically center sparse result sets.

**Step 2: Rebuild assets**

Run: `npx mix`
Expected: updated `public/css/app.css` and `public/mix-manifest.json`

**Step 3: Verify**

Run:
- `npm run lint`
- `npm run test:js`

Expected:
- lint passes
- JS tests still pass

### Task 3: Move Live Heimdall Service To Port 80

**Files:**
- Live only: `/etc/systemd/system/heimdall.service.d/override.conf`

**Step 1: Verify port availability**

Run on CT:
- `ss -ltnp | grep ':80 '`

Expected: no conflicting listener on port 80.

**Step 2: Add service override**

Create a systemd override that replaces the current `ExecStart` with:

```ini
[Service]
ExecStart=
ExecStart=/usr/bin/php artisan serve --port 80 --host 0.0.0.0
```

**Step 3: Reload and restart**

Run on CT:
- `systemctl daemon-reload`
- `systemctl restart heimdall.service`

**Step 4: Verify live ports**

Run:
- `curl -I http://192.168.3.88/`
- `ss -ltnp | grep ':80 '`

Expected:
- HTTP 200 on port 80
- service bound to port 80

