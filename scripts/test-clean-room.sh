#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

source "$ROOT/scripts/elephc-repo.sh"
elephc_repo=$(resolve_elephc_repo)

for command in composer git node; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required command not found: $command" >&2
        exit 1
    fi
done

temporary_root=$(mktemp -d "${TMPDIR:-/tmp}/tempest-elephc-clean-room.XXXXXX")
temporary_index="$temporary_root.index"
trap 'rm -rf "$temporary_root"; rm -f "$temporary_index"' EXIT

archive_ref=${CLEAN_ROOM_TREEISH:-HEAD}
GIT_INDEX_FILE="$temporary_index" git -C "$ROOT" read-tree "$archive_ref"
GIT_INDEX_FILE="$temporary_index" git -C "$ROOT" checkout-index --all --prefix="$temporary_root/"

(
    cd "$temporary_root"

    COMPOSER_ROOT_VERSION=3.x-dev composer install --no-interaction --prefer-dist
    ./scripts/apply-elephc-patches.sh
    ./scripts/apply-elephc-patches.sh
    ./scripts/apply-elephc-patches.sh --check
    node scripts/audit-patches.mjs --require-applied
    ELEPHC_REPO="$elephc_repo" ./scripts/build-elephc.sh
    node scripts/test-elephc.mjs
)

echo "Clean-room install, patch, build, and HTTP tests passed."
