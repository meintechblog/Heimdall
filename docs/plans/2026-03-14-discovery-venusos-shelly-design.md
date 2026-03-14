# Discovery VenusOS And Shelly Design

**Date:** 2026-03-14

## Goal

Expand the existing Heimdall discovery `+` flow so it can also find Victron `VenusOS` devices and `Shelly` devices, not just WLED and ESPresense. Adding a discovered device should create a normal Heimdall tile with the correct application type already selected.

## Approved Behavior

- `VenusOS` and `Shelly` participate in the same cached discovery flow as the existing sources.
- The `+` button continues to appear only when unmatched devices exist.
- Clicking the discovery card itself opens the device in a new browser tab.
- Clicking `Hinzufuegen` creates a real Heimdall item.
- The created item should already use the correct application type:
  - `VenusOS` -> `VenusOS`
  - `Shelly` -> `Shelly`
- If a matching existing tag/group exists, the new item should be placed there.
- If no matching group exists, the item should still be created without forcing a wrong group.

## Source Rules

### VenusOS

- Discovery should recognize the local Victron/Venus web stack rather than scraping the full UI.
- A practical local marker set is:
  - HTTP root redirects to `/gui-v1`
  - `/websocket-mqtt` responds like a Victron websocket endpoint
  - MQTT port `1883` is open when available
- Suggested title can fall back to `VenusOS <host>` when no better local label is cheaply available.
- Created items should reuse the existing local private `VenusOS` enhanced app type and store the usual config structure with `enabled`, `mqtt_port`, optional `portal_id`, and `override_url`.

### Shelly

- Discovery should recognize Shelly devices via `/shelly`.
- Suggested title should prefer the user-defined Shelly name from `/settings`.
- Fall back to a type-based or host-based title when no name is present.
- Because the current repo only has a generic manual Shelly link pattern and not a fitting reusable app type, add a small local private `Shelly` application type instead of misusing `Shelly Plug` for every model.

## Matching Rules

- Discovery stays host-first when comparing already known items.
- `VenusOS` and `Shelly` should also avoid obvious rediscovery of existing entries by current URL host.
- WLED-style deep identity deduplication is not required here unless the devices expose a reliable marker that makes it cheap.

## Architecture

- Keep one shared `DiscoveryController`.
- Add two new discovery services:
  - `VenusOSDiscoveryService`
  - `ShellyDiscoveryService`
- Each service owns:
  - host scan inputs
  - candidate detection
  - title preparation
  - icon selection
  - item creation
  - target tag lookup
- Add a small private `Shelly` app type under `app/SupportedApps/Shelly` so created Shelly items are typed consistently and remain replayable through the overlay/deploy workflow.

## Performance Rules

- Follow the existing discovery pattern:
  - cache-backed scans
  - conservative chunking
  - short timeouts
  - no dashboard-blocking synchronous work
- Keep the shared UI unchanged except for the extra sources and counts.

## Validation

- Add focused PHP tests for:
  - VenusOS candidate detection
  - Shelly candidate detection and preferred title
  - creation of typed VenusOS items
  - creation of typed Shelly items
  - correct summary/candidate aggregation
- Reuse the current JS discovery-panel tests unless a UI contract changes.
- Verify the result live on `http://192.168.3.88`, including at least one real VenusOS host and one real Shelly host.
