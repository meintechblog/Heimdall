# Heimdall App-Type Skill Design

**Date:** 2026-03-12

## Goal

Create a reusable Codex skill that describes how to enrich existing Heimdall application types or add new ones in the Hulki fork without losing consistency in UX, security, testing, or deployment.

## Approved Direction

- Keep one canonical skill copy inside the repo so it is versioned with the project.
- Mirror the same skill into `~/.codex/skills` so it is available locally without extra setup.
- Base the skill on two sources:
  - official Heimdall enhanced-app structure
  - Hulki-specific lessons already proven in this fork

## What The Skill Must Teach

- How to decide between:
  - generic link
  - existing supported app tweak
  - new enhanced app type
  - tile-only UI customization
- Which files to inspect first in this repo
- Which shared tile rules must stay consistent:
  - left-icon loading overlay
  - visibility- and tab-aware polling
  - compact live-stat layout
  - graceful timeout/failure behavior
- Which security rules are non-negotiable:
  - do not disable TLS verification by default
  - use explicit opt-in exceptions only
- Which verification and deploy steps are required before claiming success

## Documentation Plan

- Skill file in `skills/heimdall-app-type-extension/SKILL.md`
- Quick reference doc in `docs/CODEX_SKILLS.md`
- Short pointers from `README.md` and `MAINTENANCE.md`

## Validation

- The skill should be concise enough to load quickly but concrete enough that a future Codex instance can follow it without rediscovering the workflow.
- The local copy under `~/.codex/skills` should match the repo copy.
