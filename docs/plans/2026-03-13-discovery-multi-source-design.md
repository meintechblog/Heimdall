# Discovery Multi-Source Expansion Design

**Date:** 2026-03-13

## Goal

Expand the Heimdall discovery intake so it behaves like a real, reusable "new device inbox" instead of a WLED-only helper. The first expansion adds proper WLED detection, a new ESPresense discovery source, direct candidate-card navigation, and a tighter UI that sits next to the search field.

## Approved Behavior

- The discovery `+` button lives directly next to the dashboard search field.
- The discovery panel opens directly below the search field.
- The `+` turns into `-` while the panel is open.
- The button only appears when unmatched discovery candidates exist.
- Clicking the discovery card itself opens the device in a new browser tab/window just like a normal Heimdall tile action.
- Clicking the `Hinzufuegen` button creates a normal Heimdall item.

## UI Rules

- Discovery candidates should use the same horizontal tile language as normal Heimdall items.
- The candidate area should use the available dashboard width instead of a narrow centered strip.
- The card body is the "open device" action.
- The `Hinzufuegen` button is a separate clear action on the right.
- The loading and error behavior should stay lightweight and not block the rest of the dashboard.

## Source Rules

### WLED

- A candidate only counts as WLED when `/json/info` looks like real WLED data.
- ESPresense boxes must not be misclassified as WLED.
- The preferred suggested title should come from the configured mDNS identifier in `/json/cfg`:
  - `id.mdns`
- Fall back to:
  - WLED name from `/json/info`
  - IP-based title

### ESPresense

- Add ESPresense as a second discovery source in the same architecture.
- Detect ESPresense from its lightweight device responses rather than scraping a complex admin flow.
- Preferred suggested title should come from:
  - `room` from `/json/info`
- Fall back to:
  - page title or hostname if needed
  - IP-based title

## Architecture

- Keep one shared discovery controller and one shared discovery panel UI.
- Split source-specific behavior into separate backend services:
  - `WledDiscoveryService`
  - `EspresenseDiscoveryService`
- Each source owns:
  - scan logic
  - candidate normalization
  - cache key/lock key
  - "create Heimdall item" behavior
- The controller remains generic and only aggregates the sources.

## Matching Rules

- A discovery candidate is already known when an existing Heimdall item URL resolves to the same host/IP.
- Matching is host-based, not title-based.
- This rule is shared across WLED and ESPresense.

## Resource Rules

- Discovery remains cache-backed and off the critical dashboard render path.
- Summary checks stay lightweight.
- Candidate scans stay chunked and conservative.
- WLED chunk size should remain small because the live network showed better stability and lower risk that way.
- Candidate details are only fetched when needed for the cached scan result, not on every render.

## Validation

- Add PHP tests for:
  - WLED vs ESPresense source separation
  - WLED title suggestion from mDNS
  - ESPresense title suggestion from room
  - existing-host filtering across both sources
- Add JS tests for:
  - toggle placement/behavior with separate open vs add actions
  - card click opens the device
  - add button creates the Heimdall item
  - panel opens below search and uses the updated layout classes
- Verify live on `http://192.168.3.88`.
