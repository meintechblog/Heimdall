# Heimdall Update Kit Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a reproducible repo-managed workflow for replaying Heimdall customizations after future upstream updates.

**Architecture:** Store the production overlay in a versioned repo directory, generate it from the current fork state, and keep the host deploy script versioned in the repo so `proxi1` can be refreshed from source at any time.

**Tech Stack:** Git, Bash, Laravel Mix, jQuery/Laravel app assets

---

### Task 1: Add the update kit structure

**Files:**
- Create: `ops/heimdall-ui-overlay/.gitkeep`
- Create: `scripts/hulki/export-heimdall-ui-overlay.sh`
- Create: `scripts/hulki/install-heimdall-host-deploy.sh`
- Create: `scripts/hulki/deploy-heimdall-ui-overlay.sh`

**Step 1: Create the repo-managed overlay export script**

Copy the current production-relevant customized files into `ops/heimdall-ui-overlay/` using mirrored repo-relative paths.

**Step 2: Create the host install script**

Sync the repo-managed overlay and deploy script to `proxi1:/root/hulki-maint`.

**Step 3: Create the repo-managed deploy script**

Deploy the mirrored overlay tree from `proxi1` into CT `100`, backing up replaced files first and clearing Laravel caches afterwards.

### Task 2: Document the workflow

**Files:**
- Modify: `readme.md`
- Create: `docs/UPDATING_HULKI_FORK.md`

**Step 1: Document the replay flow**

Explain the intended update process: update `2.x`, rebase the customization branch, rebuild, export overlay, sync host files, deploy to CT.

**Step 2: Document the operational commands**

Include the exact commands to run from the repo and from `proxi1`.

### Task 3: Verify and refresh the host copy

**Files:**
- Update generated: `ops/heimdall-ui-overlay/**`

**Step 1: Verify scripts**

Run:
- `bash -n scripts/hulki/export-heimdall-ui-overlay.sh`
- `bash -n scripts/hulki/install-heimdall-host-deploy.sh`
- `bash -n scripts/hulki/deploy-heimdall-ui-overlay.sh`

**Step 2: Verify app state**

Run:
- `npm run lint`
- `npm run test:js`
- `npx mix`

**Step 3: Export and sync**

Run the export script, then sync the overlay and deploy script to `proxi1`.
