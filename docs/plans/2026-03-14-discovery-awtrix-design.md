# Discovery AWTRIX Design

**Date:** 2026-03-14

## Goal

Extend the existing Heimdall discovery `+` flow so local Ulanzi/AWTRIX displays can be found automatically and added as normal Heimdall items.

## Approved Behavior

- `AWTRIX` becomes another discovery source beside WLED, ESPresense, VenusOS, and Shelly.
- Clicking the prepared discovery card still opens the device in a new browser tab.
- Clicking `Hinzufuegen` creates a normal Heimdall item, not a new enhanced app type.
- Existing items that already point at the same host must stay hidden from the discovery list.

## Detection Rules

- Probe `http://<host>/api/stats`.
- A device counts as AWTRIX when the JSON payload contains a `uid` starting with `awtrix_`.
- The subtitle should use the useful live hints already present in the payload:
  - firmware version
  - current app
- The title can stay simple and stable:
  - `AWTRIX <host>`

## Scope Control

- Do not add a private AWTRIX app type yet.
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
  - candidate detection from `/api/stats`
  - add flow creating a normal Heimdall item
  - host-based filtering of already known AWTRIX entries
- Verify live on `http://192.168.3.88` against at least:
  - `192.168.3.141`
  - `192.168.3.154`
