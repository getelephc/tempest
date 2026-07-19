#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

"$ROOT/scripts/apply-elephc-patches.sh" --check

if [[ -n "${ELEPHC_BIN:-}" ]]; then
    compiler="$ELEPHC_BIN"
elif [[ -x "$ROOT/../../illegalstudio/elephc/target/debug/elephc" ]]; then
    compiler="$ROOT/../../illegalstudio/elephc/target/debug/elephc"
elif command -v elephc >/dev/null 2>&1; then
    compiler=$(command -v elephc)
else
    echo "Elephc was not found next to this repository, on PATH, or through ELEPHC_BIN." >&2
    exit 1
fi

"$compiler" --php-version 8.5 --check "$ROOT/elephc/probes/object-class.php"
"$compiler" --php-version 8.5 --check "$ROOT/elephc/server.php"
"$compiler" --php-version 8.5 --web "$ROOT/elephc/server.php"

echo "Built $ROOT/elephc/server"
