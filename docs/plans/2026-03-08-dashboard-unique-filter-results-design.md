# Dashboard Unique Filter Results Design

## Context

The Heimdall dashboard in `categories` mode renders the same app tile multiple times when the app belongs to more than one category. The current client-side dedupe works against the static DOM, but the live browser state still shows duplicate tiles during search in some cases. From the user's perspective, a filtered result set should show each app once.

## Approaches

### 1. Keep the existing category DOM and keep patching hide/show behavior

- Smallest code change.
- Still depends on multiple copies of the same tile staying in sync across browser/runtime paths.
- Keeps the current fragile shape that already regressed.

### 2. Remove duplicate category renders server-side

- Would eliminate duplicate DOM at the source.
- Breaks category filtering unless category membership metadata and filtering semantics are redesigned together.
- Too broad for this bugfix.

### 3. Recommended: render a flat, unique result set whenever search or category filtering is active

- Keeps the existing unfiltered dashboard intact.
- Uses one canonical tile per item id for filtered/search results.
- Avoids browser-specific behavior around hiding duplicate copies inside multiple category wrappers.

## Design

When the dashboard is unfiltered (`All` selected and search empty), keep the current category layout. When the user types a search term or selects a category button, hide the category grid and show a dedicated flat results container built from canonical tiles keyed by `data-id`. Category matching in filtered mode will use the tile's own tag classes instead of its wrapper category, so one canonical tile can still match multiple category buttons.

## Testing

- Add a JS regression test with a live-like DOM:
  - `Main Services` contains all three `Proxi` apps
  - dedicated `Proxi1`, `Proxi2`, `Proxi3` categories each contain one duplicate copy
- Assert that searching `proxi` shows exactly three visible tiles in the flat results container and hides the original category layout.
- Keep existing tests for live typing, category counts, and Google-on-Enter behavior.
