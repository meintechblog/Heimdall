# Proxmox Icon Spinner Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make Proxmox tiles use the same left-icon loading spinner pattern as VenusOS while preserving the existing resource-aware live-stat flow.

**Architecture:** Extend the shared tile markup to recognize Proxmox as an icon-overlay loading tile, keep the generic spinner classes already introduced, and document the broader Heimdall tile rules so future app types follow the same pattern.

**Tech Stack:** Blade, vanilla JS, Sass, Node test runner, Laravel Mix

---

### Task 1: Lock the Proxmox spinner behavior with failing tests

**Files:**
- Modify: `tests/js/liveStatRefresh.test.js`

**Step 1: Write the failing test**

- Extend the generic icon-loading helper test so it clearly models a non-Venus enhanced tile using the shared overlay selector.

**Step 2: Run the focused test**

Run: `node --test tests/js/liveStatRefresh.test.js`

Expected: FAIL if the helper still depends on app-specific selectors or if the shared overlay is not wired consistently.

### Task 2: Implement shared Proxmox icon overlay support

**Files:**
- Modify: `resources/views/item.blade.php`
- Modify: `resources/assets/js/liveStatRefresh.js` only if selector plumbing still needs adjustment

**Step 1: Add Proxmox to the shared icon-overlay tile logic**

**Step 2: Keep FileFlows on its existing control overlay path**

**Step 3: Run focused JS tests**

Run:
- `node --test tests/js/liveStatRefresh.test.js`
- `node --test tests/js/fileflowsTileControls.test.js`

Expected: PASS

### Task 3: Document the Heimdall tile rules, build, deploy, and verify

**Files:**
- Create: `docs/plans/2026-03-12-heimdall-tile-ci-design.md`
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
- `./scripts/hulki/install-heimdall-host-deploy.sh --overlay-dir "$PWD/ops/heimdall-ui-overlay"`
- `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

**Step 3: Verify live**

- Confirm the Proxmox tiles render the shared icon overlay spinner before stats arrive.
- Confirm VenusOS and FileFlows still behave as before.

**Step 4: Commit and push**
