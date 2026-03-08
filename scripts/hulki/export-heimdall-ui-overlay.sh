#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd -- "$SCRIPT_DIR/../.." && pwd)
OVERLAY_DIR="$REPO_ROOT/ops/heimdall-ui-overlay"

FILES=(
  "app/Search.php"
  "app/Http/Controllers/ItemController.php"
  "resources/assets/js/app.js"
  "resources/assets/js/dashboardFilters.js"
  "resources/assets/sass/_app.scss"
  "resources/views/partials/taglist.blade.php"
  "resources/views/sortable.blade.php"
  "resources/views/welcome.blade.php"
  "public/js/app.js"
  "public/css/app.css"
  "public/mix-manifest.json"
)

usage() {
  cat <<EOF
Usage: $(basename "$0") [--overlay-dir PATH]

Exports the current Heimdall customization payload into a mirrored overlay tree.

Default overlay dir:
  $OVERLAY_DIR
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
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

rm -rf "$OVERLAY_DIR"
mkdir -p "$OVERLAY_DIR"

for rel in "${FILES[@]}"; do
  src="$REPO_ROOT/$rel"
  dst="$OVERLAY_DIR/$rel"

  [[ -f "$src" ]] || {
    echo "Missing source file: $src" >&2
    exit 2
  }

  mkdir -p "$(dirname "$dst")"
  cp "$src" "$dst"
done

{
  echo "exported_at=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "git_branch=$(git -C "$REPO_ROOT" branch --show-current)"
  echo "git_commit=$(git -C "$REPO_ROOT" rev-parse HEAD)"
} > "$OVERLAY_DIR/EXPORT_METADATA"

echo "overlay_dir=$OVERLAY_DIR"
