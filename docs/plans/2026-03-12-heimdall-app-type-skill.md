# Heimdall App-Type Skill Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a reusable Codex skill that captures the official Heimdall app-type workflow and the Hulki fork's proven extension rules.

**Architecture:** Keep the repo copy as the canonical source, mirror it into `~/.codex/skills` for local use, and add lightweight project docs that tell future sessions where the skill lives and how to reuse it on a new machine.

**Tech Stack:** Markdown skill files, repo docs, local Codex skill directory

---

### Task 1: Gather the rules the skill must encode

**Files:**
- Read: `README.md`
- Read: `MAINTENANCE.md`
- Read: `docs/plans/2026-03-12-heimdall-tile-ci-design.md`
- Read: `docs/plans/2026-03-12-global-tile-icon-spinner-design.md`
- Read: `docs/plans/2026-03-12-proxmox-enhanced-tile-design.md`
- Read: `docs/plans/2026-03-12-venusos-enhanced-tile-design.md`

**Step 1: Extract the stable rules**

- Enhanced-app file layout
- shared tile-loading behavior
- security and polling rules
- test/build/deploy expectations

### Task 2: Write the skill

**Files:**
- Create: `skills/heimdall-app-type-extension/SKILL.md`
- Create: `~/.codex/skills/heimdall-app-type-extension/SKILL.md`

**Step 1: Write a concise skill with clear trigger wording**

**Step 2: Include official Heimdall references and Hulki-specific rules**

**Step 3: Mirror the repo copy into the local Codex skills directory**

### Task 3: Document access and verify

**Files:**
- Create: `docs/CODEX_SKILLS.md`
- Modify: `README.md`
- Modify: `MAINTENANCE.md`

**Step 1: Add a short doc explaining where the skill lives and how to copy it to a new machine**

**Step 2: Verify repo/local copies match**

Run:
- `diff -u skills/heimdall-app-type-extension/SKILL.md ~/.codex/skills/heimdall-app-type-extension/SKILL.md`

**Step 3: Commit and push**
