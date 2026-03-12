# Heimdall Tile CI Design

**Date:** 2026-03-12

## Goal

Capture the shared design and behavior rules for the custom Heimdall tiles so future extensions stay visually and functionally consistent.

## Approved Rules

- Live stats should feel like one system, not one-off per app type.
- Loading indicators belong on the left icon area, not inside the text stats area.
- The icon loading treatment should reuse the shared `tile-icon-loading-*` pattern whenever the tile is waiting for first live data.
- If live-stat loading fails or times out, the loading treatment disappears and no broken placeholder remains.
- Polling should stay resource-aware:
  - only for visible tiles
  - pause when the browser tab is inactive
  - keep the staggered initial load
- Tile interactions should stay local to the left icon area only when that area has a clear app-specific control, like FileFlows pause/resume.
- Visual language should stay compact and comparable across app types:
  - same spinner family
  - same stat density
  - similar fallback behavior

## Current App-Type Mapping

- `FileFlows`
  - left icon area doubles as the processing control
  - uses the shared icon spinner ring around the play/pause symbol
- `VenusOS`
  - uses the shared icon overlay spinner while initial live stats are loading
- `Proxmox`
  - should match `VenusOS` and use the shared icon overlay spinner while initial live stats are loading

## Implementation Notes

- Keep the generic tile spinner classes in shared Blade/CSS/JS paths, not inside app-specific files.
- If a new enhanced app needs an icon loading state, prefer adding it to the shared overlay allowlist instead of inventing new markup.
- Any new live-stat tile should preserve the visibility- and tab-aware polling behavior already in `liveStatRefresh.js`.

## Validation

- JS tests should cover generic spinner helper behavior.
- Browser verification should check at least one tile with overlay loading and one tile with a control-centric loading ring.
- Live deployment should confirm the shared asset bundle is active on `http://192.168.3.88`.
