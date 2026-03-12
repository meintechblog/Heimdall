# WLED Discovery Add-Flow Design

**Date:** 2026-03-12

## Goal

Add a scalable auto-discovery entry point to the Heimdall dashboard that can surface newly found WLED devices as ready-to-add tiles without forcing the user through the manual item form.

## Approved Behavior

- A prominent `+` button appears in the top-right config area only when newly discovered WLED devices are available.
- "Newly discovered" means:
  - the discovery cache contains WLED devices
  - and those devices do not already match an existing Heimdall item by URL/IP host
- Clicking `+` expands a discovery strip above the search bar.
- The `+` changes to `-` while the strip is open.
- Clicking `-` closes the strip again.
- Each discovered WLED appears as a prepared service tile.
- Clicking one prepared tile immediately creates a normal Heimdall item and adds it into the regular list.

## Scope For First Iteration

- Start with WLED only.
- Keep the architecture generic enough for later sources like Shelly or Mobotix.
- Only admins should see and use the discovery controls.

## Discovery Strategy

- Discovery should not run as a blocking dashboard page load.
- The browser asks a lightweight discovery-status endpoint after the dashboard is already usable.
- The endpoint uses a cache with TTL and a scan lock.
- If cached results are fresh, it returns them immediately.
- If cached results are stale, it performs a sparse WLED subnet scan and refreshes the cache.

## WLED Detection

- First iteration scans the private /24 of the Heimdall host URL.
- Each candidate IP is probed at `http://<ip>/json/info`.
- A response counts as WLED when the JSON matches the expected WLED info shape.
- Matching against existing Heimdall items uses the URL host/IP, not the title.

## Auto-Creation Rules

- Newly added items should be created as real WLED application items, not generic links.
- Use the official WLED application entry from Heimdall's app list.
- Auto-added WLED items should be pinned and assigned to the existing `WLED` tag/category when available.
- The created item should keep a sensible title from WLED's reported device name plus IP when needed.

## UI Rules

- The discovery strip belongs above the search bar so it feels like a temporary intake area, not a permanent dashboard section.
- Prepared tiles should visually match normal Heimdall tiles but clearly act as "add" actions.
- Existing dashboard performance rules remain in force:
  - no heavy synchronous scan on the main page render
  - no repeated stale scans without cache expiry
  - no uncontrolled background polling

## Scalability Rules

- Separate the architecture into:
  - discovery source
  - cache/summary
  - match filtering
  - candidate-to-item creation
- Only the WLED source should be device-specific in this first step.
- The UI and cache flow should already be reusable for additional discovery sources later.

## Validation

- Add PHP tests for:
  - filtering out already-existing WLED devices by host/IP
  - creating a new WLED item from a cached candidate
  - showing the discovery button only when new candidates exist
- Add JS tests for:
  - toggling the discovery strip
  - rendering candidate tiles
  - creating a new item from a candidate
- Verify live on `http://192.168.3.88`.
