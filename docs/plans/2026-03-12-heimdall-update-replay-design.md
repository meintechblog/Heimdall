# Heimdall Update Replay Design

**Date:** 2026-03-12

## Goal

Make the Hulki Heimdall customizations easy to keep after future Heimdall updates.

## Approved Direction

- Keep the current repo-managed overlay approach.
- Do not rely on direct live edits inside the Heimdall container.
- Add small helper scripts so the replay path is fast and repeatable.
- Document the FileFlows tile customization clearly in the GitHub repo.

## Why This Approach

The overlay approach is already the right long-term base because it keeps the live delta small and explicit:

- custom source files stay in git
- the deployed payload is exported from the repo
- redeploying after an upstream Heimdall update does not require manual file hunting

What is missing is convenience and clarity. The new scripts should reduce the repeat work, not replace the existing model with a more fragile one.

## Workflow Shape

Two helper scripts are enough:

1. `update-heimdall-custom-branch.sh`
- fetch base branch changes
- fast-forward the base branch
- rebase the custom branch on top
- run the key verification commands

2. `replay-heimdall-overlay.sh`
- export the overlay from the current repo state
- sync it to `proxi1`
- deploy it into CT `100`

This keeps branch management and live deployment understandable while still allowing a one-command replay path.

## FileFlows Notes

The FileFlows tile control must be documented as a custom extension with one important implementation rule:

- use only the native FileFlows pause endpoint
- permanent pause is implemented as a very large native duration
- resume is implemented with native `duration=0`
- do not use `ui-settings` writes for pause/resume because that path caused the `initial-config` issue on the MacMini instance

## Validation

- verify both FileFlows instances still respond normally
- run the focused FileFlows PHP test
- syntax-check the new shell scripts with `bash -n`
- confirm the new helper scripts are documented in README and `docs/UPDATING_HULKI_FORK.md`
