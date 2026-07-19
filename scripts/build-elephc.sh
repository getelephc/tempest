#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
RUNTIME_ROOT="$ROOT/elephc/runtime"
ELEPHC_PATCH="$ROOT/patches/elephc/object-type-name-resolution.patch"

source "$ROOT/scripts/elephc-repo.sh"
elephc_repo=$(resolve_elephc_repo)

"$ROOT/scripts/apply-elephc-patches.sh" --check

for command in cargo composer git patch tar; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required command not found: $command" >&2
        exit 1
    fi
done

if [[ ! -f "$ELEPHC_PATCH" ]]; then
    echo "Elephc compiler patch not found: $ELEPHC_PATCH" >&2
    exit 1
fi

COMPOSER_ROOT_VERSION=3.x-dev composer install \
    --working-dir "$RUNTIME_ROOT" \
    --no-interaction \
    --prefer-dist

"$ROOT/scripts/apply-elephc-patches.sh" --runtime-only
"$ROOT/scripts/apply-elephc-patches.sh" --runtime-only --check

temporary_compiler_source=$(mktemp -d "${TMPDIR:-/tmp}/tempest-elephc-compiler.XXXXXX")

cleanup()
{
    rm -rf "$temporary_compiler_source"
}

trap cleanup EXIT

git -C "$elephc_repo" archive HEAD | tar -x -C "$temporary_compiler_source"

if ! patch --dry-run -s -d "$temporary_compiler_source" -p1 < "$ELEPHC_PATCH"; then
    echo "The committed Elephc compatibility patch does not apply to ELEPHC_REPO HEAD." >&2
    exit 1
fi

patch -s -d "$temporary_compiler_source" -p1 < "$ELEPHC_PATCH"

manifest="$temporary_compiler_source/Cargo.toml"
target_directory="$elephc_repo/target"
compiler="$target_directory/debug/elephc"

cargo build --locked --manifest-path "$manifest" --target-dir "$target_directory" --bin elephc

if [[ ! -x "$compiler" ]]; then
    echo "Elephc compiler was not produced at the expected path: $compiler" >&2
    exit 1
fi

"$compiler" --php-version 8.5 --check "$ROOT/elephc/probes/object-class.php"
"$compiler" --php-version 8.5 --check "$RUNTIME_ROOT/server.php"
"$compiler" --php-version 8.5 --web "$RUNTIME_ROOT/server.php"

echo "Built $RUNTIME_ROOT/server"
