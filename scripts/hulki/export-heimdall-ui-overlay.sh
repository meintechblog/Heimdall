#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd -- "$SCRIPT_DIR/../.." && pwd)
OVERLAY_DIR="$REPO_ROOT/ops/heimdall-ui-overlay"
BUILD_ASSETS=0

FILES=(
  "app/Search.php"
  "app/Providers/AppServiceProvider.php"
  "app/Http/Controllers/ItemController.php"
  "bootstrap/app.php"
  "config/app.php"
  "resources/assets/js/app.js"
  "resources/assets/js/dashboardFilters.js"
  "resources/assets/js/fileflowsTileControls.js"
  "resources/assets/sass/_app.scss"
  "resources/views/item.blade.php"
  "resources/views/items/livestats/fileflows.blade.php"
  "resources/views/partials/taglist.blade.php"
  "resources/views/sortable.blade.php"
  "resources/views/welcome.blade.php"
  "routes/web.php"
  "public/js/app.js"
  "public/css/app.css"
  "public/mix-manifest.json"
)

usage() {
  cat <<EOF
Usage: $(basename "$0") [--overlay-dir PATH] [--build-assets]

Exports the current Heimdall customization payload into a mirrored overlay tree.

Default overlay dir:
  $OVERLAY_DIR
EOF
}

require_fresh_asset() {
  local label="$1"
  local asset_path="$2"
  shift 2
  local source_dirs=("$@")
  local asset_abs="$REPO_ROOT/$asset_path"

  [[ -f "$asset_abs" ]] || {
    echo "Missing built $label asset: $asset_abs" >&2
    exit 2
  }

  for source_dir in "${source_dirs[@]}"; do
    local source_abs="$REPO_ROOT/$source_dir"
    if find "$source_abs" -type f -newer "$asset_abs" -print -quit | grep -q .; then
      echo "$label asset is stale: $asset_path" >&2
      echo "Run 'npx mix' or re-run this script with --build-assets." >&2
      exit 2
    fi
  done
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --overlay-dir)
      OVERLAY_DIR="$2"
      shift 2
      ;;
    --build-assets)
      BUILD_ASSETS=1
      shift
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

if [[ "$BUILD_ASSETS" -eq 1 ]]; then
  (cd "$REPO_ROOT" && npx mix)
fi

require_fresh_asset "JavaScript" "public/js/app.js" "resources/assets/js"
require_fresh_asset "CSS" "public/css/app.css" "resources/assets/sass"

[[ -f "$REPO_ROOT/public/mix-manifest.json" ]] || {
  echo "Missing built asset manifest: $REPO_ROOT/public/mix-manifest.json" >&2
  exit 2
}

if [[ "$REPO_ROOT/public/mix-manifest.json" -ot "$REPO_ROOT/public/js/app.js" ]] || \
  [[ "$REPO_ROOT/public/mix-manifest.json" -ot "$REPO_ROOT/public/css/app.css" ]]; then
  echo "Asset manifest is older than the built assets." >&2
  echo "Run 'npx mix' or re-run this script with --build-assets." >&2
  exit 2
fi

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
