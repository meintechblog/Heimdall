# Discovery Mobotix Design

**Date:** 2026-03-14

## Goal

Extend the existing Heimdall discovery `+` flow so local Mobotix cameras can be found automatically and added as normal Heimdall items.

## Approved Behavior

- `Mobotix` becomes another discovery source beside WLED, ESPresense, VenusOS, Shelly, and AWTRIX.
- Clicking the prepared discovery card still opens the device in a new browser tab.
- Clicking `Hinzufuegen` creates a normal Heimdall item, not a new enhanced app type.
- Existing items that already point at the same host must stay hidden from the discovery list.

## Detection Rules

- Probe `http://<host>/`.
- A device counts as Mobotix when the response redirects to `/control/userimage.html`.
- Keep the first version simple and stable:
  - title: `Mobotix <host>`
  - subtitle: `Mobotix Camera`

## Scope Control

- Do not add a private Mobotix app type yet.
- Do not add live tile stats yet.
- Keep this as a lightweight discovery-only integration that creates normal items.

## Performance Rules

- Reuse the current discovery model:
  - cached scans
  - chunked host requests
  - short timeouts
  - no synchronous dashboard blocking

## Validation

- Add focused PHPUnit coverage for:
  - candidate detection from the redirect marker
  - add flow creating a normal Heimdall item
  - host-based filtering of already known Mobotix entries
- Verify live on `http://192.168.3.88` against at least:
  - `192.168.3.21`
  - `192.168.3.24`
