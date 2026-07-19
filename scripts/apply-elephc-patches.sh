#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
SOURCE_PATCH_ROOT="$ROOT/patches/source"
VENDOR_PATCH_ROOT="$ROOT/patches/vendor"
RUNTIME_PATCH_ROOT="$ROOT/patches/runtime"
LOCK_SHA256_FILE="$ROOT/patches/vendor.composer-lock.sha256"

mode=apply
include_source=true
include_vendor=true
include_runtime=false

for argument in "$@"; do
    case "$argument" in
        --check)
            mode=check
            ;;
        --reverse)
            mode=reverse
            ;;
        --source-only)
            include_vendor=false
            ;;
        --vendor-only)
            include_source=false
            ;;
        --runtime-only)
            include_source=false
            include_vendor=false
            include_runtime=true
            ;;
        *)
            echo "Unknown argument: $argument" >&2
            echo "Usage: $0 [--check|--reverse] [--source-only|--vendor-only|--runtime-only]" >&2
            exit 2
            ;;
    esac
done

cd "$ROOT"

for command in git find; do
    if ! command -v "$command" >/dev/null 2>&1; then
        echo "Required command not found: $command" >&2
        exit 1
    fi
done

sha256_file()
{
    if command -v sha256sum >/dev/null 2>&1; then
        sha256sum "$1" | awk '{print $1}'
    else
        shasum -a 256 "$1" | awk '{print $1}'
    fi
}

verify_vendor_baseline()
{
    if [[ ! -d vendor ]]; then
        echo "vendor/ does not exist. Run 'composer install' first." >&2
        exit 1
    fi

    if [[ ! -f "$LOCK_SHA256_FILE" ]]; then
        echo "Composer lock checksum not found: $LOCK_SHA256_FILE" >&2
        exit 1
    fi

    local expected
    local actual
    expected=$(tr -d '[:space:]' < "$LOCK_SHA256_FILE")
    actual=$(sha256_file composer.lock)

    if [[ "$actual" != "$expected" ]]; then
        echo "composer.lock does not match the vendor patch baseline." >&2
        echo "Expected: $expected" >&2
        echo "Actual:   $actual" >&2
        exit 1
    fi
}

applied=0
already_applied=0
reversed=0
already_clean=0
pending=0
processed=0

process_series()
{
    local label=$1
    local patch_root=$2

    if [[ ! -d "$patch_root" ]]; then
        echo "$label patch tree not found: $patch_root" >&2
        exit 1
    fi

    local count
    count=$(find "$patch_root" -type f -name '*.patch' | wc -l | tr -d '[:space:]')

    if [[ "$count" == "0" ]]; then
        echo "$label patch tree is empty: $patch_root" >&2
        exit 1
    fi

    while IFS= read -r patch; do
        local relative_patch=${patch#"$ROOT/"}
        processed=$((processed + 1))

        case "$mode" in
            apply)
                if git apply --unidiff-zero --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
                    git apply --unidiff-zero --whitespace=nowarn "$patch"
                    applied=$((applied + 1))
                elif git apply --unidiff-zero --reverse --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
                    already_applied=$((already_applied + 1))
                else
                    echo "Cannot apply $label patch: $relative_patch" >&2
                    echo "Its target is neither clean nor already patched." >&2
                    exit 1
                fi
                ;;
            check)
                if git apply --unidiff-zero --reverse --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
                    already_applied=$((already_applied + 1))
                elif git apply --unidiff-zero --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
                    pending=$((pending + 1))
                    if (( pending <= 10 )); then
                        echo "Patch is not applied: $relative_patch" >&2
                    elif (( pending == 11 )); then
                        echo "Additional unapplied patches omitted..." >&2
                    fi
                else
                    echo "Divergent patch target: $relative_patch" >&2
                    exit 1
                fi
                ;;
            reverse)
                if git apply --unidiff-zero --reverse --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
                    git apply --unidiff-zero --reverse --whitespace=nowarn "$patch"
                    reversed=$((reversed + 1))
                elif git apply --unidiff-zero --check --whitespace=nowarn "$patch" >/dev/null 2>&1; then
                    already_clean=$((already_clean + 1))
                else
                    echo "Cannot reverse $label patch: $relative_patch" >&2
                    echo "Its target is neither patched nor clean." >&2
                    exit 1
                fi
                ;;
        esac
    done < <(find "$patch_root" -type f -name '*.patch' | LC_ALL=C sort)
}

if [[ "$include_vendor" == true ]]; then
    verify_vendor_baseline
fi

if [[ "$mode" == reverse ]]; then
    if [[ "$include_runtime" == true ]]; then
        process_series runtime "$RUNTIME_PATCH_ROOT"
    fi
    if [[ "$include_vendor" == true ]]; then
        process_series vendor "$VENDOR_PATCH_ROOT"
    fi
    if [[ "$include_source" == true ]]; then
        process_series source "$SOURCE_PATCH_ROOT"
    fi
else
    if [[ "$include_source" == true ]]; then
        process_series source "$SOURCE_PATCH_ROOT"
    fi
    if [[ "$include_vendor" == true ]]; then
        process_series vendor "$VENDOR_PATCH_ROOT"
    fi
    if [[ "$include_runtime" == true ]]; then
        process_series runtime "$RUNTIME_PATCH_ROOT"
    fi
fi

if [[ "$pending" != "0" ]]; then
    echo "Patch check failed: $pending of $processed patches are not applied." >&2
    exit 1
fi

case "$mode" in
    apply)
        echo "Elephc patches: $applied applied, $already_applied already present ($processed total)."
        ;;
    check)
        echo "Elephc patches: all $processed patches are applied."
        ;;
    reverse)
        echo "Elephc patches: $reversed reversed, $already_clean already clean ($processed total)."
        ;;
esac
