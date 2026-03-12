# FileFlows Logo Toggle Refinement Design

**Date:** 2026-03-12

## Goal

Make the FileFlows control on Heimdall tiles easier to understand and easier to click.

## Approved Behavior

- Only the two FileFlows tiles keep the extra control.
- The whole left logo area becomes the toggle hitbox.
- Clicking the logo area switches FileFlows between pause and resume immediately.
- Clicking the rest of the tile still opens the FileFlows instance as before.
- The symbol shown on the logo reflects the current FileFlows state:
  - running -> play symbol
  - paused -> pause symbol

## UI Direction

- Remove the tiny corner button behavior.
- Center a larger symbol on the FileFlows logo area so it is easier to hit.
- Do not rely on Font Awesome for the play/pause symbol.
- Keep the visual treatment simple and avoid state color noise unless needed later.

## Technical Notes

- The current tile link spans the full tile width, so the logo toggle must sit above that link and stop the click from bubbling into normal navigation.
- The JS tile updater should render the visible symbol from `processingState`, not from `toggleAction`.
- Accessibility text can still describe the action that will happen on click, while the visible symbol represents the current state.

## Validation

- JS tests must cover:
  - state-to-icon mapping
  - clicking the logo toggle posts to the toggle endpoint
  - clicking the logo toggle prevents normal tile navigation
- Browser verification on `http://192.168.3.88` must confirm:
  - the logo area is the clickable toggle
  - the rest of the tile still opens FileFlows
  - the two visible FileFlows tiles show the expected state symbols
