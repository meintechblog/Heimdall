# FileFlows Processing Toggle Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a small play/pause overlay button to FileFlows tiles so Heimdall can show the current processing state and toggle pause/resume without changing the normal tile click behavior.

**Architecture:** Keep the feature in the Heimdall core so it is tracked in Git and deployable through the existing overlay workflow. Fetch FileFlows state from `GET /api/settings` and `GET /api/status`, expose a dedicated Heimdall toggle endpoint that calls `POST /api/system/pause` or `POST /api/system/pause?abort=true`, and let the live stats refresh update the tile button state after each poll.

**Tech Stack:** Laravel, Blade, existing Heimdall item/live-stats flow, vanilla JS, Sass, Node test runner, PHPUnit

---

### Task 1: Lock the backend contract with failing PHP tests

**Files:**
- Modify: `tests/TestCase.php`
- Create: `tests/Feature/FileFlowsProcessingToggleTest.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ItemController.php`

**Step 1: Write the failing test**

```php
public function test_get_stats_returns_fileflows_processing_state(): void
{
    Http::fake([
        'http://fileflows.local/api/status' => Http::response([
            'queue' => 4,
            'processing' => 1,
            'processed' => 20,
            'time' => '00:42',
        ], 200),
        'http://fileflows.local/api/settings' => Http::response([
            'IsPaused' => false,
            'PausedUntil' => '0001-01-01T00:00:00',
        ], 200),
    ]);

    $item = Item::factory()->create([
        'url' => 'http://fileflows.local',
        'class' => 'App\\SupportedApps\\FileFlows\\FileFlows',
        'description' => json_encode(['enabled' => '1', 'override_url' => null]),
    ]);

    $response = $this->get('/get_stats/' . $item->id);

    $response->assertOk();
    $response->assertJsonPath('processingState', 'running');
    $response->assertJsonPath('toggleAction', 'pause');
}
```

**Step 2: Run test to verify it fails**

Run: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest`

Expected: FAIL because FileFlows-specific JSON fields and toggle endpoint do not exist yet.

**Step 3: Add failing toggle test**

```php
public function test_toggle_endpoint_resumes_a_paused_fileflows_instance(): void
{
    Http::fake([
        'http://fileflows.local/api/settings' => Http::response([
            'IsPaused' => true,
            'PausedUntil' => '6109-04-04T09:56:12.3369452Z',
        ], 200),
        'http://fileflows.local/api/system/pause?abort=true' => Http::response(
            '0001-01-01T00:00:00',
            200
        ),
    ]);

    $item = Item::factory()->create([
        'url' => 'http://fileflows.local',
        'class' => 'App\\SupportedApps\\FileFlows\\FileFlows',
        'description' => json_encode(['enabled' => '1', 'override_url' => null]),
    ]);

    $response = $this->post('/items/' . $item->id . '/fileflows/toggle');

    $response->assertOk();
    $response->assertJsonPath('processingState', 'running');
}
```

**Step 4: Run test to verify it fails**

Run: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest`

Expected: FAIL because the new POST route and FileFlows action code are missing.

**Step 5: Commit**

```bash
git add tests/TestCase.php tests/Feature/FileFlowsProcessingToggleTest.php
git commit -m "test: cover fileflows processing toggle contract"
```

### Task 2: Add failing JS coverage for tile button behavior

**Files:**
- Create: `tests/js/fileflowsTileControls.test.js`
- Modify: `resources/assets/js/liveStatRefresh.js`
- Modify: `resources/assets/js/app.js`

**Step 1: Write the failing test**

```js
test("updates the fileflows button to pause when the backend reports running", async () => {
  document.body.innerHTML = `
    <section class="item-container" data-id="81">
      <div class="item">
        <div class="app-icon-container">
          <button class="fileflows-processing-toggle" data-id="81"></button>
        </div>
        <div class="livestats-container" data-id="81"></div>
      </div>
    </section>
  `;

  updateFileFlowsTileState(document.querySelector(".livestats-container"), {
    processingState: "running",
    toggleAction: "pause",
  });

  expect(document.querySelector(".fileflows-processing-toggle")).toHaveAttribute(
    "data-processing-state",
    "running"
  );
});
```

**Step 2: Run test to verify it fails**

Run: `npm run test:js -- tests/js/fileflowsTileControls.test.js`

Expected: FAIL because the tile state updater and toggle wiring do not exist yet.

**Step 3: Add failing click test**

```js
test("clicking the mini button posts to the FileFlows toggle endpoint without opening the tile", async () => {
  // Arrange DOM, stub fetch, click button, assert POST target and stopped navigation
});
```

**Step 4: Run test to verify it fails**

Run: `npm run test:js -- tests/js/fileflowsTileControls.test.js`

Expected: FAIL because there is no FileFlows toggle click handler yet.

**Step 5: Commit**

```bash
git add tests/js/fileflowsTileControls.test.js
git commit -m "test: cover fileflows tile controls"
```

### Task 3: Implement the FileFlows backend

**Files:**
- Modify: `app/Http/Controllers/ItemController.php`
- Modify: `routes/web.php`
- Create: `resources/views/items/livestats/fileflows.blade.php`

**Step 1: Write minimal implementation**

```php
protected function isFileFlowsItem(Item $item): bool
{
    return $item->class === 'App\\SupportedApps\\FileFlows\\FileFlows';
}

protected function fetchFileFlowsStats(Item $item): array
{
    // call /api/status and /api/settings
    // return queue, secondary label/value, processing state, toggle action
}

public function toggleFileFlowsProcessing(int $id): JsonResponse
{
    // load item, read current IsPaused, POST pause/resume, return refreshed state
}
```

**Step 2: Run focused PHP tests**

Run: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest`

Expected: PASS

**Step 3: Refactor only if still green**

```php
// Extract shared FileFlows request helper methods to avoid duplicated URL building
```

**Step 4: Re-run focused PHP tests**

Run: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest`

Expected: PASS

**Step 5: Commit**

```bash
git add app/Http/Controllers/ItemController.php routes/web.php resources/views/items/livestats/fileflows.blade.php
git commit -m "feat: add fileflows processing toggle backend"
```

### Task 4: Implement the tile UI and live refresh wiring

**Files:**
- Modify: `resources/views/item.blade.php`
- Modify: `resources/assets/js/liveStatRefresh.js`
- Modify: `resources/assets/js/app.js`
- Modify: `resources/assets/sass/_app.scss`

**Step 1: Write minimal implementation**

```blade
@if($app->class === 'App\\SupportedApps\\FileFlows\\FileFlows' && $app->enabled())
    <button class="fileflows-processing-toggle" data-id="{{ $app->id }}" ...></button>
@endif
```

```js
// update button state after each stats refresh
// POST to /items/{id}/fileflows/toggle on click
// stop propagation so the tile link still opens only on normal tile click
```

**Step 2: Run focused JS tests**

Run: `npm run test:js -- tests/js/fileflowsTileControls.test.js`

Expected: PASS

**Step 3: Run lint**

Run: `npm run lint`

Expected: PASS

**Step 4: Build assets**

Run: `npx mix`

Expected: PASS

**Step 5: Commit**

```bash
git add resources/views/item.blade.php resources/assets/js/liveStatRefresh.js resources/assets/js/app.js resources/assets/sass/_app.scss
git commit -m "feat: add fileflows tile processing controls"
```

### Task 5: Document, verify, and deploy

**Files:**
- Modify: `docs/UPDATING_HULKI_FORK.md`
- Modify: `scripts/hulki/export-heimdall-ui-overlay.sh`
- Modify: `ops/heimdall-ui-overlay/...` as regenerated by export script

**Step 1: Update docs**

```md
- explain that FileFlows tiles now show a play/pause overlay and toggle processing directly
- note that only the mini button toggles; the tile still opens FileFlows
```

**Step 2: Run full verification**

Run: `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest`
Expected: PASS

Run: `npm run test:js`
Expected: PASS

Run: `npm run lint`
Expected: PASS

Run: `npx mix`
Expected: PASS

**Step 3: Deploy**

Run: `./scripts/hulki/export-heimdall-ui-overlay.sh`
Expected: overlay regenerated with updated controller, routes, views, JS, CSS

Run: `./scripts/hulki/install-heimdall-host-deploy.sh`
Expected: host overlay refreshed on `proxi1`

Run on `proxi1`: `/root/hulki-maint/deploy-heimdall-ui-overlay.sh`
Expected: live CT updated

**Step 4: Smoke-test live**

Run:
- `curl -I http://192.168.3.88/`
- `curl -sS http://192.168.3.88/get_stats/<fileflows_item_id>`
- `curl -sS http://192.168.3.103:19200/api/settings | jq '{IsPaused, PausedUntil}'`
- `curl -sS http://192.168.3.12:5000/api/settings | jq '{IsPaused, PausedUntil}'`

Expected:
- Heimdall responds `200 OK`
- FileFlows stats payload includes `processingState` and `toggleAction`
- live button toggles and status refreshes on both real FileFlows tiles

**Step 5: Commit**

```bash
git add docs/UPDATING_HULKI_FORK.md scripts/hulki/export-heimdall-ui-overlay.sh ops/heimdall-ui-overlay
git commit -m "docs: document fileflows tile processing controls"
```
