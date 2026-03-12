# Heimdall Update Replay Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Document the FileFlows customization and add a simple, repeatable update/replay workflow so Hulki-specific Heimdall changes survive future Heimdall updates.

**Architecture:** Keep the current overlay deployment model and add thin wrapper scripts around the existing export/sync/deploy steps. Update the repo docs so future maintenance has one clear branch model, one replay path, and one warning about the safe FileFlows pause implementation.

**Tech Stack:** git, bash, existing Hulki deploy scripts, Markdown docs, PHP test runner

---

### Task 1: Add design-level documentation

**Files:**
- Create: `docs/plans/2026-03-12-heimdall-update-replay-design.md`
- Create: `docs/plans/2026-03-12-heimdall-update-replay.md`

**Step 1: Write the design doc**

Capture:
- why overlay replay remains the chosen base
- why a helper script layer is enough
- why FileFlows must stay on native pause endpoints

**Step 2: Save the implementation plan**

Capture:
- helper script responsibilities
- repo docs to update
- validation commands

**Step 3: Commit**

```bash
git add docs/plans/2026-03-12-heimdall-update-replay-design.md docs/plans/2026-03-12-heimdall-update-replay.md
git commit -m "docs: plan heimdall update replay workflow"
```

### Task 2: Add replay helper scripts

**Files:**
- Create: `scripts/hulki/replay-heimdall-overlay.sh`
- Create: `scripts/hulki/update-heimdall-custom-branch.sh`

**Step 1: Add the deploy replay wrapper**

The wrapper should run:
- export overlay
- host sync
- remote deploy

**Step 2: Add the update helper**

The helper should:
- require a clean worktree
- fetch origin
- fast-forward `2.x`
- rebase `codex/heimdall-pimp`
- run the core verification commands

**Step 3: Verify script syntax**

Run:

```bash
bash -n scripts/hulki/replay-heimdall-overlay.sh
bash -n scripts/hulki/update-heimdall-custom-branch.sh
```

**Step 4: Commit**

```bash
git add scripts/hulki/replay-heimdall-overlay.sh scripts/hulki/update-heimdall-custom-branch.sh
git commit -m "feat: add heimdall replay helper scripts"
```

### Task 3: Update repo docs

**Files:**
- Modify: `README.md`
- Modify: `docs/UPDATING_HULKI_FORK.md`

**Step 1: Document the FileFlows feature**

Include:
- logo-area toggle
- native pause/resume behavior
- supported live instances

**Step 2: Document the durable replay path**

Include:
- current custom branch name
- helper script usage
- warning not to use direct live edits
- note about the native FileFlows pause endpoint choice

**Step 3: Commit**

```bash
git add README.md docs/UPDATING_HULKI_FORK.md
git commit -m "docs: describe fileflows customization replay workflow"
```

### Task 4: Verify and publish

**Files:**
- Modify: generated metadata if overlay export is rerun

**Step 1: Run verification**

Run:

```bash
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest
bash -n scripts/hulki/replay-heimdall-overlay.sh
bash -n scripts/hulki/update-heimdall-custom-branch.sh
```

**Step 2: Push**

```bash
git push origin codex/heimdall-pimp
```

**Step 3: Report**

Explain the new replay path in simple language.
