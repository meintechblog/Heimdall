#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd -- "$SCRIPT_DIR/../.." && pwd)
OVERLAY_DIR="$REPO_ROOT/ops/heimdall-ui-overlay"
REMOTE_HOST="proxi1"
REMOTE_BASE_DIR="/root/hulki-maint"
REMOTE_OVERLAY_DIR="$REMOTE_BASE_DIR/heimdall-ui-overlay"

usage() {
  cat <<EOF
Usage: $(basename "$0") [--host proxi1] [--remote-base-dir /root/hulki-maint] [--overlay-dir PATH]

Copies the repo-managed Heimdall overlay and deploy script to the Proxmox host.
Run export-heimdall-ui-overlay.sh first.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --host)
      REMOTE_HOST="$2"
      shift 2
      ;;
    --remote-base-dir)
      REMOTE_BASE_DIR="$2"
      REMOTE_OVERLAY_DIR="$REMOTE_BASE_DIR/heimdall-ui-overlay"
      shift 2
      ;;
    --overlay-dir)
      OVERLAY_DIR="$2"
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
  echo "Overlay dir looks stale or incomplete: $OVERLAY_DIR" >&2
  exit 2
}

ssh "$REMOTE_HOST" "mkdir -p '$REMOTE_BASE_DIR' && rm -rf '$REMOTE_OVERLAY_DIR'"
scp -r "$OVERLAY_DIR" "$REMOTE_HOST:$REMOTE_BASE_DIR/"
scp "$REPO_ROOT/scripts/hulki/deploy-heimdall-ui-overlay.sh" \
  "$REMOTE_HOST:$REMOTE_BASE_DIR/deploy-heimdall-ui-overlay.sh"

echo "host=$REMOTE_HOST"
echo "remote_overlay_dir=$REMOTE_OVERLAY_DIR"
