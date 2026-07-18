#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
PATCH_ROOT="$ROOT/patches/vendor"

cd "$ROOT"

if [[ ! -d vendor ]]; then
    echo "vendor/ does not exist. Run Composer first." >&2
    exit 1
fi

expected=$(tr -d '[:space:]' < patches/vendor.composer-lock.sha256)
actual=$(shasum -a 256 composer.lock | awk '{print $1}')

if [[ "$actual" != "$expected" ]]; then
    echo "composer.lock does not match the patch baseline." >&2
    echo "Expected: $expected" >&2
    echo "Actual:   $actual" >&2
    exit 1
fi

applied=0
skipped=0

while IFS= read -r patch; do
    if git apply --unidiff-zero --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
        git apply --unidiff-zero --whitespace=nowarn "$patch"
        applied=$((applied + 1))
    elif git apply --unidiff-zero --reverse --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
        skipped=$((skipped + 1))
    else
        echo "Cannot apply patch: ${patch#"$ROOT/"}" >&2
        exit 1
    fi
done < <(find "$PATCH_ROOT" -type f -name '*.patch' | LC_ALL=C sort)

echo "Vendor patches: $applied applied, $skipped already present."
