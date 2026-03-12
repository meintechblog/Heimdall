# Global Tile Icon Spinner Design

**Date:** 2026-03-12

## Goal

Unify the icon loading spinner used by VenusOS and FileFlows so both tiles render the same centered overlay and future enhanced apps can reuse it without copying app-specific markup or CSS.

## Approved Direction

- Keep the current Heimdall look: spinner sits on the app icon, not in the stats area.
- VenusOS and FileFlows use the same spinner structure and positioning rules.
- The shared spinner must support two cases:
  - full icon overlay while live stats are still loading
  - centered ring around a tile control icon while the control stays visible
- On timeout or failed live-stat load, the overlay spinner disappears and no placeholder text is left behind.

## Technical Approach

- Introduce generic icon-spinner classes instead of reusing FileFlows-specific class names in other app types.
- Update the Blade tile markup so VenusOS uses the shared overlay element.
- Update the FileFlows tile control renderer so busy/loading states also use the shared visual wrapper and spinner ring.
- Make the live-stat refresh helper target the generic overlay selector instead of a Venus-only selector.

## Validation

- JS tests must prove the generic icon overlay is found, primed, and hidden correctly.
- JS tests must prove the FileFlows control markup uses the shared spinner classes.
- Browser verification on `http://192.168.3.88` must confirm:
  - VenusOS spinner is centered on the icon while stats are loading
  - FileFlows initial and busy spinners are centered on the icon
  - the spinner disappears once stats arrive
