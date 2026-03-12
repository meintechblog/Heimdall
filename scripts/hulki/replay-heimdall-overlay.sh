#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd -- "$SCRIPT_DIR/../.." && pwd)
REMOTE_HOST="proxi1"
REMOTE_BASE_DIR="/root/hulki-maint"
CTID=100
TARGET_ROOT="/opt/Heimdall"
BUILD_ASSETS=0

usage() {
  cat <<EOF
Usage: $(basename "$0") [--build-assets] [--host proxi1] [--remote-base-dir /root/hulki-maint] [--ctid 100] [--target-root /opt/Heimdall]

Exports the current repo state into the Heimdall overlay, syncs it to the Proxmox host,
and deploys it into the target Heimdall container.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --build-assets)
      BUILD_ASSETS=1
      shift
      ;;
    --host)
      REMOTE_HOST="$2"
      shift 2
      ;;
    --remote-base-dir)
      REMOTE_BASE_DIR="$2"
      shift 2
      ;;
    --ctid)
      CTID="$2"
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

EXPORT_CMD=("$SCRIPT_DIR/export-heimdall-ui-overlay.sh")
if [[ "$BUILD_ASSETS" -eq 1 ]]; then
  EXPORT_CMD+=(--build-assets)
fi

"${EXPORT_CMD[@]}"
"$SCRIPT_DIR/install-heimdall-host-deploy.sh" \
  --host "$REMOTE_HOST" \
  --remote-base-dir "$REMOTE_BASE_DIR"

ssh "$REMOTE_HOST" "$REMOTE_BASE_DIR/deploy-heimdall-ui-overlay.sh --ctid '$CTID' --target-root '$TARGET_ROOT'"

echo "deployed_host=$REMOTE_HOST"
echo "deployed_ctid=$CTID"
echo "target_root=$TARGET_ROOT"
