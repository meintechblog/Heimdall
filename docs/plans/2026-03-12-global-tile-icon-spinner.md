# Global Tile Icon Spinner Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace the Venus-only icon-loading overlay and the FileFlows-specific spinner wrapper with one shared tile-icon spinner pattern.

**Architecture:** Keep the existing live-stat fetch flow and FileFlows state flow, but move both to generic icon-spinner classes so the same HTML/CSS structure is used across app types.

**Tech Stack:** Blade, vanilla JS, Sass, Node test runner, Laravel Mix

---

### Task 1: Lock the shared spinner contract with failing JS tests

**Files:**
- Modify: `tests/js/liveStatRefresh.test.js`
- Modify: `tests/js/fileflowsTileControls.test.js`

**Step 1: Cover the generic icon overlay selector**

- Update the live-stat test fixture to expose a generic icon overlay element.
- Assert that the helper finds the generic overlay and clears the live-stat body on failure.

**Step 2: Cover the shared FileFlows spinner markup**

- Assert that FileFlows loading/busy markup uses the generic icon-spinner classes.

**Step 3: Run focused tests and confirm they fail**

Run:
- `node --test tests/js/liveStatRefresh.test.js`
- `node --test tests/js/fileflowsTileControls.test.js`

Expected: FAIL until production code switches to the new shared selector/class names.

### Task 2: Implement the shared tile-icon spinner

**Files:**
- Modify: `resources/views/item.blade.php`
- Modify: `resources/assets/js/liveStatRefresh.js`
- Modify: `resources/assets/js/fileflowsTileControls.js`
- Modify: `resources/assets/sass/_app.scss`

**Step 1: Replace Venus-specific overlay markup with generic tile-icon overlay markup**

**Step 2: Move FileFlows button loading/busy markup onto the same generic visual wrapper and spinner ring**

**Step 3: Rename CSS and helper selectors so both cases share the same centering and ring sizing rules**

**Step 4: Re-run focused tests and confirm they pass**

### Task 3: Build, deploy, and verify live behavior

**Files:**
- Modify: `public/js/app.js`
- Modify: `public/css/app.css`
- Modify: `public/mix-manifest.json`
- Modify: `ops/heimdall-ui-overlay/**`

**Step 1: Run verification**

Run:
- `npm run lint`
- `node --test tests/js/liveStatRefresh.test.js tests/js/fileflowsTileControls.test.js`
- `npx mix`

**Step 2: Export and deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Browser-check VenusOS and both FileFlows tiles on `http://192.168.3.88`**

**Step 4: Push the branch**
