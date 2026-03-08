# Dashboard Unique Filter Results Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ensure Heimdall shows each app only once when dashboard search or category filtering is active, even if that app belongs to multiple categories.

**Architecture:** Preserve the existing category dashboard for the default unfiltered view. Introduce a dedicated flat results container in the dashboard filter module that renders canonical tiles keyed by `data-id` whenever search text or a category filter is active.

**Tech Stack:** Laravel Blade, jQuery, Webpack Mix, Node test runner with JSDOM

---

### Task 1: Add a failing regression test for live-like duplicate search results

**Files:**
- Modify: `tests/js/dashboardFilters.test.js`

**Step 1: Write the failing test**

Add a live-like DOM fixture with `Main Services` plus per-host categories that duplicate the same `Proxi` items. Assert that searching `proxi` produces exactly three visible result tiles and hides the original category grid.

**Step 2: Run test to verify it fails**

Run: `npm run test:js`
Expected: one new failing assertion for duplicate filtered results.

### Task 2: Implement flat unique filtered results

**Files:**
- Modify: `resources/assets/js/dashboardFilters.js`
- Modify: `resources/assets/sass/_app.scss`

**Step 1: Write minimal implementation**

Add helpers that:
- create/find a dedicated dashboard filtered-results container
- choose one canonical tile per `data-id`
- render cloned canonical tiles into the results container when search/category filter is active
- map category buttons to tile tag classes so a canonical tile can match multiple categories
- toggle between the original category layout and the filtered results view

**Step 2: Run tests to verify they pass**

Run: `npm run test:js`
Expected: all JS tests pass, including the new regression.

### Task 3: Rebuild assets and verify live behavior

**Files:**
- Modify: `public/js/app.js`
- Modify: `public/css/app.css`
- Modify: `public/mix-manifest.json`

**Step 1: Rebuild assets**

Run: `npx mix`

**Step 2: Verify**

Run:
- `npm run lint`
- `npm run test:js`

Expected: green lint/test/build.

### Task 4: Deploy and validate on the Heimdall instance

**Files:**
- Live instance: `/opt/Heimdall/public/js/app.js`
- Live instance: `/opt/Heimdall/public/css/app.css`
- Live instance: `/opt/Heimdall/public/mix-manifest.json`

**Step 1: Push updated assets and source**

Deploy the rebuilt bundle and source file to CT `100`.

**Step 2: Clear app caches and validate**

Run live checks for:
- dashboard HTML references the new bundle hash
- searching `proxi` shows only three unique tiles
- category buttons still filter as expected
