# Discovery Service Wave Design

**Date:** 2026-03-15

## Goal

Extend the existing Heimdall discovery `+` flow so more common LAN services can be auto-discovered and added with one click.

## Approved Scope

Add discovery sources for:

- `Node-RED`
- `go2rtc`
- `openWB`
- `openDTU`
- `Homebridge`
- `Home Assistant`

Each source stays in the shared discovery inbox:

- clicking the prepared discovery card opens the service in a new browser tab
- clicking `Hinzufuegen` creates a normal Heimdall item
- no new enhanced app types are added in this wave

## Detection Rules

- `Node-RED`
  - probe `http://<host>:1880/settings`
  - treat as Node-RED when JSON contains a `version` and typical Node-RED settings fields
- `go2rtc`
  - probe `http://<host>:1984/api`
  - treat as go2rtc when JSON contains `version` and `config_path`
- `openWB`
  - probe `http://<host>/api/status?get=all`
  - treat as openWB when JSON contains the expected openWB status shape
- `openDTU`
  - probe `http://<host>/api/livedata/status`
  - treat as openDTU when JSON contains inverter live-data fields
- `Homebridge`
  - probe `http://<host>:8581/`
  - treat as Homebridge when the page HTML contains `<title>Homebridge</title>`
- `Home Assistant`
  - probe `http://<host>:8123/api/`
  - treat as Home Assistant when the endpoint returns `401 Unauthorized`

## Important Identity Rule

These services are not device-per-host sources like WLED or Mobotix. Multiple unrelated services can share the same IP.

So discovery must compare candidates by the concrete base URL including port, not just by host:

- `http://192.168.3.31:1984` must not be hidden only because `http://192.168.3.31:5000` already exists
- `http://192.168.3.7:8581` must not be hidden only because another service on `192.168.3.7` exists

## Performance Rules

- keep the current discovery model:
  - cached scans
  - chunked host requests
  - short timeouts
  - no dashboard-blocking synchronous loops
- keep the sources configurable via host-list env vars so the user can narrow scans later

## Validation

- add focused PHPUnit coverage for all six sources
- include at least one regression case for the host+port identity rule
- verify live on `http://192.168.3.88`
