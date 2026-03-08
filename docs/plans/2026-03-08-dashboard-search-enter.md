# Dashboard Search Enter Behavior Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Remove the homepage search provider dropdown, preserve live Heimdall tile filtering while typing, and open a Google search in a new tab when the user presses `Enter` with a non-empty query.

**Architecture:** The homepage search UI will become a single-purpose dashboard control. `app/Search.php` will emit only the input and submit button markup, while `resources/assets/js/dashboardFilters.js` will own both live tile filtering and `Enter` submission behavior so the user flow stays coherent and testable.

**Tech Stack:** Laravel Blade/PHP, jQuery, Node test runner, jsdom, PHPUnit

---

### Task 1: Remove Provider Dropdown Rendering

**Files:**
- Modify: `app/Search.php`
- Test: `tests/Feature/DashTest.php`

**Step 1: Write the failing test**

Extend the dashboard feature test so the homepage no longer renders `<select name="provider">`.

**Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/DashTest.php --filter=does_not_render_search_provider_dropdown`
Expected: FAIL because the current homepage still renders the provider dropdown.

**Step 3: Write minimal implementation**

Update `Search::form()` so the homepage search markup no longer renders a provider `<select>`. Keep the text input and submit button.

**Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/DashTest.php --filter=does_not_render_search_provider_dropdown`
Expected: PASS

**Step 5: Commit**

```bash
git add app/Search.php tests/Feature/DashTest.php
git commit -m "refactor: remove homepage search provider dropdown"
```

### Task 2: Add Enter-to-Google Dashboard Behavior

**Files:**
- Modify: `resources/assets/js/dashboardFilters.js`
- Test: `tests/js/dashboardFilters.test.js`

**Step 1: Write the failing test**

Add a JS regression test that:
- types a non-empty query into the homepage search field
- submits the form or triggers `Enter`
- expects a new tab open call to Google with the encoded query

**Step 2: Run test to verify it fails**

Run: `npm run test:js`
Expected: FAIL because `Enter` is not yet redirected to Google.

**Step 3: Write minimal implementation**

Change `dashboardFilters.js` so form submission:
- keeps local filtering on `input`
- prevents default submit when the query is non-empty
- opens `https://www.google.com/search?q=<encoded query>` in a new tab
- does not rely on provider selection

**Step 4: Run test to verify it passes**

Run: `npm run test:js`
Expected: PASS

**Step 5: Commit**

```bash
git add resources/assets/js/dashboardFilters.js tests/js/dashboardFilters.test.js
git commit -m "feat: search google on dashboard enter"
```

### Task 3: Update Docs and Full Verification

**Files:**
- Modify: `readme.md`
- Verify: `resources/assets/js/dashboardFilters.js`
- Verify: `app/Search.php`

**Step 1: Update docs**

Document that the homepage search field:
- filters Heimdall tiles live while typing
- opens Google in a new tab on `Enter`
- no longer shows provider selection in the UI

**Step 2: Run full verification**

Run:
- `npm run lint`
- `npm run test:js`
- `npx mix`
- `php artisan test tests/Feature/DashTest.php`

Expected:
- lint passes
- JS tests pass
- asset build succeeds
- feature tests pass

**Step 3: Commit**

```bash
git add readme.md public/js/app.js public/mix-manifest.json public/css/app.css
git commit -m "docs: document homepage search behavior"
```
