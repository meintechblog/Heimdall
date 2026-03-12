# Proxmox Enhanced Tile Design

**Date:** 2026-03-12

## Goal

Make the existing Proxmox app type usable in Heimdall so a configured Proxmox tile can show helpful host status directly on the dashboard.

## Approved Direction

- Keep Proxmox as a normal Heimdall enhanced app instead of adding one-off controller logic.
- Bring the Proxmox app files into the repo so the feature is versioned and replayable after updates.
- Fix the item edit flow so Proxmox configuration is visible even before any enhanced config has been saved.
- Focus the tile stats on:
  - running containers
  - CPU load
  - RAM load

## Why This Direction

The live Heimdall container already has a working Proxmox enhanced app implementation. The real problem is that the repo-managed customization layer does not contain that app, and the current form logic hides enhanced config when an item has no saved config yet.

Using the built-in enhanced app path keeps the feature aligned with Heimdall's existing `get_stats/{id}` refresh flow, avoids special-case controller code, and makes the customization easier to reapply after upstream updates.

## Configuration Behavior

- A Proxmox item should expose its optional configuration block whenever the selected app class is an enhanced app, even if the config JSON is still empty.
- The Proxmox config should keep:
  - override URL
  - node filter list
  - optional TLS skip toggle
  - API token ID
  - API token value
- The existing enable toggle remains the switch that turns live stats on.

## Tile Behavior

- Once enabled and configured, the Proxmox tile should refresh through the normal live-stats mechanism.
- The visible stats should be compact and easy to scan:
  - `LXC` running / total
  - `CPU` percentage
  - `RAM` percentage
- VM counts should not be shown on the tile for this customization.

## Data Flow

- Heimdall calls the Proxmox API with a token via `Authorization: PVEAPIToken=...`.
- If no explicit node list is configured, Heimdall first discovers the cluster nodes.
- For each selected node:
  - fetch node status for CPU and memory
  - fetch LXC list for container counts
- CPU should be shown as average node CPU usage.
- RAM should be shown as total used memory divided by total available memory across the selected nodes.

## Failure Behavior

- If the Proxmox API is unreachable or credentials are invalid, the tile should fall back to inactive stats instead of breaking the dashboard.
- Missing or empty node responses should not crash rendering.

## Updateability

- The Proxmox app files must live in the repo and in the exported overlay.
- The overlay export script must include the new `app/SupportedApps/Proxmox/*` files.
- Repo docs should mention that this Proxmox feature is part of the Hulki customization layer.

## Validation

- Add failing tests first for:
  - Proxmox config visibility on edit/create flow
  - Proxmox live-stat rendering and aggregation
- Verify the focused PHP tests locally.
- Deploy the updated overlay to `192.168.3.88`.
- Confirm that a configured Proxmox tile shows LXC, CPU, and RAM on the live dashboard.
