# Remote Icon TLS Hardening Design

## Context

The current remote icon download path in `ItemController::storelogic()` disables TLS peer verification for every HTTPS icon URL. That means a remote icon fetch can trust an invalid certificate and write attacker-controlled content into public storage.

The live Heimdall deployment is replayed through the repo-managed overlay workflow, so the fix must update both the application code and the exported overlay payload.

## Approaches

### 1. Strict TLS by default with explicit insecure opt-in

- Fixes the security issue without removing the remote icon feature
- Keeps legacy/self-signed environments possible through a conscious override
- Fits the existing `.env`-driven Heimdall configuration model

### 2. Strict TLS only, no override

- Strongest default posture
- Risks breaking existing self-signed environments immediately

### 3. Remove remote icon downloads entirely

- Eliminates the attack surface
- Larger behavior change than needed for this review item

## Recommended Design

Adopt approach 1.

Add a new config flag `ALLOW_INSECURE_REMOTE_ICON_TLS` with a safe default of `false`. `ItemController` should build its stream-context options from config, only disabling `verify_peer` and `verify_peer_name` when that flag is explicitly enabled.

Keep the existing proxy handling unchanged. Update `.env.example`, the main README, and the Hulki fork update guide so operators know that the safe path for private/self-signed HTTPS remains a proper CA bundle, while the new flag is only an emergency compatibility escape hatch.

Because the live deployment uses the repo-managed overlay, include `config/app.php` in the exported overlay file list.

## Verification

- Add regression tests covering strict default behavior and explicit insecure opt-in
- Run the available repository verification commands
- Export the overlay, sync it to `proxi1`, deploy into CT `100`, and smoke-check the live instance
