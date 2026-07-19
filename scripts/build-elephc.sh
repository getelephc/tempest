#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

source "$ROOT/scripts/elephc-repo.sh"
elephc_repo=$(resolve_elephc_repo)

"$ROOT/scripts/apply-elephc-patches.sh" --check

if ! command -v cargo >/dev/null 2>&1; then
    echo "Required command not found: cargo" >&2
    exit 1
fi

manifest="$elephc_repo/Cargo.toml"
target_directory="$elephc_repo/target"
compiler="$target_directory/debug/elephc"

cargo build --locked --manifest-path "$manifest" --target-dir "$target_directory" --bin elephc

if [[ ! -x "$compiler" ]]; then
    echo "Elephc compiler was not produced at the expected path: $compiler" >&2
    exit 1
fi

"$compiler" --php-version 8.5 --check "$ROOT/elephc/probes/object-class.php"
"$compiler" --php-version 8.5 --check "$ROOT/elephc/server.php"
"$compiler" --php-version 8.5 --web "$ROOT/elephc/server.php"

echo "Built $ROOT/elephc/server"
