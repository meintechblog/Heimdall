# FileFlows Logo Toggle Refinement Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Change the FileFlows tile control so the full logo area toggles pause/resume, the rest of the tile still opens FileFlows, and the visible symbol shows the current state.

**Architecture:** Keep the existing backend contract unchanged and refine only the tile markup, JS state mapping, and CSS stacking/hitbox rules. Render the control as a large centered overlay in the logo area with a higher stacking order than the tile link so toggling no longer falls through to navigation.

**Tech Stack:** Blade, vanilla JS, Sass, Node test runner, existing Heimdall live-stats refresh flow

---

### Task 1: Lock the new UI behavior with failing JS tests

**Files:**
- Modify: `tests/js/fileflowsTileControls.test.js`

**Step 1: Write the failing state-icon test**

```js
test("paused FileFlows tiles render the pause symbol while keeping resume as the action", () => {
  controls.updateTileState(container, {
    processingState: "paused",
    toggleAction: "resume",
  });

  assert.match(button.innerHTML, /fileflows-toggle-icon-pause/);
});
```

**Step 2: Run test to verify it fails**

Run: `node --test tests/js/fileflowsTileControls.test.js`

Expected: FAIL because the UI currently renders the symbol from `toggleAction`.

**Step 3: Write the failing hitbox test**

```js
test("clicking anywhere in the logo toggle area posts without opening the tile", async () => {
  // click the centered logo toggle element and assert defaultPrevented === true
});
```

**Step 4: Run test to verify it fails**

Run: `node --test tests/js/fileflowsTileControls.test.js`

Expected: FAIL because the current DOM and event handling still model a small corner button.

**Step 5: Commit**

```bash
git add tests/js/fileflowsTileControls.test.js
git commit -m "test: cover fileflows logo toggle behavior"
```

### Task 2: Implement the larger logo-area toggle

**Files:**
- Modify: `resources/views/item.blade.php`
- Modify: `resources/assets/js/fileflowsTileControls.js`
- Modify: `resources/assets/sass/_app.scss`

**Step 1: Update the tile markup**

```blade
<button class="fileflows-processing-toggle fileflows-processing-toggle-logo">
```

Move the control into the logo area as a centered overlay and keep it above the normal tile link.

**Step 2: Update the JS state mapping**

```js
function getStateIcon(processingState) {
  return processingState === "paused" ? "pause" : "play";
}
```

Keep the button label/title action-based, but render the visible SVG from `processingState`.

**Step 3: Update CSS for hitbox and layout**

```scss
.fileflows-processing-toggle {
  inset: 0;
  width: 60px;
  height: 60px;
  z-index: 3;
}
```

Center a larger icon, remove corner placement, and ensure the logo toggle reliably sits above the tile link.

**Step 4: Run focused JS tests**

Run: `node --test tests/js/fileflowsTileControls.test.js`

Expected: PASS

**Step 5: Commit**

```bash
git add resources/views/item.blade.php resources/assets/js/fileflowsTileControls.js resources/assets/sass/_app.scss
git commit -m "fix: enlarge fileflows logo toggle hitbox"
```

### Task 3: Build and verify end-to-end

**Files:**
- Modify: `public/js/app.js`
- Modify: `public/css/app.css`
- Modify: `public/mix-manifest.json`
- Modify: `ops/heimdall-ui-overlay/**`

**Step 1: Run local verification**

Run: `npm run lint`
Run: `node --test tests/js/fileflowsTileControls.test.js`
Run: `npx mix`

Expected: PASS

**Step 2: Export and deploy**

Run: `./scripts/hulki/export-heimdall-ui-overlay.sh`
Run: `./scripts/hulki/install-heimdall-host-deploy.sh`
Run: `ssh proxi1 '/root/hulki-maint/deploy-heimdall-ui-overlay.sh'`

Expected: overlay synced and deployed

**Step 3: Browser-check the live UI**

Run Playwright against `http://192.168.3.88`

Expected:
- logo click toggles
- non-logo click opens FileFlows
- state symbols are no longer inverted

**Step 4: Push**

```bash
git push origin codex/heimdall-pimp
```

**Step 5: Report**

Summarize the visible behavior in simple language for the user.
