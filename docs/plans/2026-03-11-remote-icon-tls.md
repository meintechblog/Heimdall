# Remote Icon TLS Hardening Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Make remote icon downloads verify TLS by default, with an explicit opt-in escape hatch for insecure certificates.

**Architecture:** Move the remote icon stream-context options into a small controller helper driven by config. Keep proxy support intact, document the new environment flag, and ensure the live overlay export includes the changed config file.

**Tech Stack:** Laravel, PHPUnit, PHP stream contexts, Bash deploy scripts

---

### Task 1: Add regression coverage for TLS option selection

**Files:**
- Create: `tests/Unit/Http/Controllers/ItemControllerTest.php`

**Step 1: Write the failing tests**

Add tests that assert:
- remote icon downloads do not disable TLS verification by default
- insecure TLS can only be enabled through explicit config

**Step 2: Run the targeted PHPUnit test**

Run: `php artisan test --filter=ItemControllerTest`
Expected: fail because the helper/config behavior does not exist yet

### Task 2: Implement config-gated TLS behavior

**Files:**
- Modify: `app/Http/Controllers/ItemController.php`
- Modify: `config/app.php`
- Modify: `.env.example`

**Step 1: Add the config entry**

Expose `ALLOW_INSECURE_REMOTE_ICON_TLS` through `config/app.php` with a default of `false`.

**Step 2: Implement the helper**

Move remote icon stream-context option building into a dedicated helper in `ItemController`, preserving proxy support and only disabling TLS verification when the config flag is enabled.

**Step 3: Use the helper from the remote icon download path**

Replace the inline stream-context array in `storelogic()` with the helper output.

### Task 3: Document and ship the live rollout path

**Files:**
- Modify: `readme.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`
- Modify: `scripts/hulki/export-heimdall-ui-overlay.sh`

**Step 1: Document the new environment flag**

Explain that CA bundles are the preferred solution and the insecure flag is a compatibility-only escape hatch.

**Step 2: Update the overlay export**

Add `config/app.php` to the exported live overlay payload so the live CT receives the new config entry.

### Task 4: Verify, deploy, and publish

**Files:**
- Update generated: `ops/heimdall-ui-overlay/**`

**Step 1: Run verification**

Run the available checks:
- `php artisan test --filter=ItemControllerTest`
- `npm run lint`
- `npm run test:js`

**Step 2: Export and deploy**

Run:
- `./scripts/hulki/export-heimdall-ui-overlay.sh --build-assets`
- `./scripts/hulki/install-heimdall-host-deploy.sh`
- `ssh proxi1 /root/hulki-maint/deploy-heimdall-ui-overlay.sh`

**Step 3: Publish**

Commit the code and docs, then push the branch to GitHub.
