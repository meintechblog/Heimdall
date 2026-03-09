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

echo "[1/4] prepare backup dir"
pct exec "$CTID" -- sh -lc "mkdir -p '$BACKUP_DIR'"

echo "[2/4] back up current files"
for rel in "${FILES[@]}"; do
  rel="${rel#./}"
  pct exec "$CTID" -- sh -lc "\
    mkdir -p '$BACKUP_DIR/$(dirname "$rel")' '$TARGET_ROOT/$(dirname "$rel")'; \
    if [ -e '$TARGET_ROOT/$rel' ]; then cp -a '$TARGET_ROOT/$rel' '$BACKUP_DIR/$rel.bak'; fi"
done

echo "[3/4] push overlay files"
for rel in "${FILES[@]}"; do
  rel="${rel#./}"
  pct push "$CTID" "$OVERLAY_DIR/$rel" "$TARGET_ROOT/$rel"
done

echo "[4/4] clear laravel caches"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan view:clear >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan cache:clear >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan config:clear >/dev/null 2>&1"
pct exec "$CTID" -- sh -lc "cd '$TARGET_ROOT' && php artisan route:clear >/dev/null 2>&1"

echo "backup_dir=$BACKUP_DIR"
