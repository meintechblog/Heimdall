#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
REPO_ROOT=$(cd -- "$SCRIPT_DIR/../.." && pwd)
BASE_BRANCH="2.x"
CUSTOM_BRANCH="codex/heimdall-pimp"
DEPLOY_AFTER_VERIFY=0

usage() {
  cat <<EOF
Usage: $(basename "$0") [--base-branch 2.x] [--custom-branch codex/heimdall-pimp] [--deploy]

Fetches the latest base Heimdall branch, rebases the custom Hulki branch on top of it,
runs the key verification commands, and optionally replays the live overlay deployment.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --base-branch)
      BASE_BRANCH="$2"
      shift 2
      ;;
    --custom-branch)
      CUSTOM_BRANCH="$2"
      shift 2
      ;;
    --deploy)
      DEPLOY_AFTER_VERIFY=1
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

cd "$REPO_ROOT"

if [[ -n "$(git status --short)" ]]; then
  echo "Worktree is dirty. Commit or stash changes before running this script." >&2
  exit 2
fi

git fetch origin
git checkout "$BASE_BRANCH"
git pull --ff-only origin "$BASE_BRANCH"

git checkout "$CUSTOM_BRANCH"
git rebase "$BASE_BRANCH"

npm run lint
npm run test:js
APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=codex-fileflows-feature.sqlite php artisan test --filter=FileFlowsProcessingToggleTest
npx mix

if [[ "$DEPLOY_AFTER_VERIFY" -eq 1 ]]; then
  "$SCRIPT_DIR/replay-heimdall-overlay.sh"
fi

echo "base_branch=$BASE_BRANCH"
echo "custom_branch=$CUSTOM_BRANCH"
echo "deploy_after_verify=$DEPLOY_AFTER_VERIFY"
