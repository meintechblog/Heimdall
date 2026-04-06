#!/usr/bin/env bash
set -euo pipefail

CTID=100
OVERLAY_DIR="/root/hulki-maint/heimdall-ui-overlay"
TARGET_ROOT="/opt/Heimdall"

usage() {
  cat <<EOF
Usage: $(basename "$0") [--ctid 100] [--overlay-dir /root/hulki-maint/heimdall-ui-overlay] [--target-root /opt/Heimdall]

Deploys a mirrored Heimdall overlay tree from the Proxmox host into the target CT.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --ctid)
      CTID="$2"
      shift 2
      ;;
    --overlay-dir)
      OVERLAY_DIR="$2"
      shift 2
      ;;
    --target-root)
      TARGET_ROOT="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown arg: $1" >&2
      usage >&2
      exit 2
      ;;
  esac
done

[[ -d "$OVERLAY_DIR" ]] || {
  echo "Overlay dir not found: $OVERLAY_DIR" >&2
  exit 2
}

[[ -f "$OVERLAY_DIR/EXPORT_METADATA" ]] || {
  echo "Overlay metadata missing: $OVERLAY_DIR/EXPORT_METADATA" >&2
  exit 2
}

mapfile -t FILES < <(cd "$OVERLAY_DIR" && find . -type f ! -name 'EXPORT_METADATA' | sort)

[[ ${#FILES[@]} -gt 0 ]] || {
  echo "No overlay files found in: $OVERLAY_DIR" >&2
  exit 2
}

TS=$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="$TARGET_ROOT/.hulki-backups/$TS"

echo "[1/5] prepare backup dir"
pct exec "$CTID" -- sh -lc "mkdir -p '$BACKUP_DIR'"

echo "[2/5] back up current files"
for rel in "${FILES[@]}"; do
  rel="${rel#./}"
  pct exec "$CTID" -- sh -lc "\
    mkdir -p '$BACKUP_DIR/$(dirname "$rel")' '$TARGET_ROOT/$(dirname "$rel")'; \
    if [ -e '$TARGET_ROOT/$rel' ]; then cp -a '$TARGET_ROOT/$rel' '$BACKUP_DIR/$rel.bak'; fi"
done

echo "[3/5] push overlay files"
for rel in "${FILES[@]}"; do
  rel="${rel#./}"
  pct push "$CTID" "$OVERLAY_DIR/$rel" "$TARGET_ROOT/$rel"
done

PRIVATE_APPS_TINKER='$apps = [[
    "appid" => "191c67b4933ec1ca9dda6ccb69a4a7e0d40d42e9",
    "name" => "VenusOS",
    "class" => "App\\SupportedApps\\VenusOS\\VenusOS",
    "enhanced" => 1,
    "icon" => "icons/venusos.png",
    "website" => "https://www.victronenergy.com/live/venus-os:start",
    "license" => "Victron Energy documentation and software ecosystem",
    "description" => "Venus OS local Victron metrics.",
    "tile_background" => "dark",
], [
    "appid" => "d65462dfcc2066849a1aeac8712497f95315ecd9",
    "name" => "Shelly",
    "class" => "App\\SupportedApps\\Shelly\\Shelly",
    "enhanced" => 0,
    "icon" => "icons/shelly.png",
    "website" => "https://www.shelly.com/",
    "license" => "Shelly device firmware and local web UI",
    "description" => "Shelly devices discovered on the local network.",
    "tile_background" => "dark",
], [
    "appid" => "a3f7b2c1e8d94056b1c2e3f4a5b6c7d8e9f0a1b2",
    "name" => "Shelly Plug",
    "class" => "App\\SupportedApps\\ShellyPlug\\ShellyPlug",
    "enhanced" => 1,
    "icon" => "icons/shelly.png",
    "website" => "https://www.shelly.com/",
    "license" => "Shelly device firmware and local web UI",
    "description" => "Shelly Plug smart power outlet with live energy monitoring.",
    "tile_background" => "dark",
]];

foreach ($apps as $app) {
    $model = \App\Application::query()->where("appid", $app["appid"])->first();
    if (! $model) {
        $model = new \App\Application();
    }

    foreach ($app as $key => $value) {
        $model->{$key} = $value;
    }

    $model->save();
}'

echo "[4/5] register private app types"
pct exec "$CTID" -- bash -lc "cd '$TARGET_ROOT' && php artisan tinker --execute=$(printf '%q' "$PRIVATE_APPS_TINKER") >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "\
  mkdir -p '$TARGET_ROOT/storage/app/public/icons' && \
  cp -f '$TARGET_ROOT/app/SupportedApps/VenusOS/venusos.png' '$TARGET_ROOT/storage/app/public/icons/venusos.png' && \
  cp -f '$TARGET_ROOT/app/SupportedApps/Shelly/shelly.png' '$TARGET_ROOT/storage/app/public/icons/shelly.png'"

echo "[5/5] clear laravel caches"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan view:clear >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan cache:clear >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan config:clear >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan route:clear >/dev/null 2>&1"

echo "backup_dir=$BACKUP_DIR"
