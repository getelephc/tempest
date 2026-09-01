#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
RUNTIME_ROOT="$ROOT/elephc/runtime"

source "$ROOT/scripts/elephc-repo.sh"
elephc_repo=$(resolve_elephc_repo)

"$ROOT/scripts/apply-elephc-patches.sh" --check

for command in cargo composer git tar; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required command not found: $command" >&2
        exit 1
    fi
done

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

manifest="$temporary_compiler_source/Cargo.toml"
target_directory="$elephc_repo/target"
compiler="$target_directory/debug/elephc"

cargo build --locked --manifest-path "$manifest" --target-dir "$target_directory" --bin elephc

if [[ ! -x "$compiler" ]]; then
    echo "Elephc compiler was not produced at the expected path: $compiler" >&2
    exit 1
fi

"$compiler" native install --locked --manifest-path "$RUNTIME_ROOT/elephc.toml"
"$compiler" --php-version 8.5 --check "$ROOT/elephc/probes/object-class.php"
"$compiler" --php-version 8.5 --check "$RUNTIME_ROOT/server.php"
"$compiler" --php-version 8.5 --web "$RUNTIME_ROOT/server.php"

echo "Built $RUNTIME_ROOT/server"
