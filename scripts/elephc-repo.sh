#!/usr/bin/env bash

resolve_elephc_repo()
{
    if [[ -z "${ELEPHC_REPO:-}" ]]; then
        echo "ELEPHC_REPO is required and must point to an Elephc repository checkout." >&2
        echo "Example: ELEPHC_REPO=/path/to/elephc $0" >&2
        return 1
    fi

    if [[ ! -d "$ELEPHC_REPO" ]]; then
        echo "ELEPHC_REPO does not exist or is not a directory: $ELEPHC_REPO" >&2
        return 1
    fi

    local repository
    repository=$(cd "$ELEPHC_REPO" && pwd -P)

    if [[ ! -f "$repository/Cargo.toml" ]]; then
        echo "ELEPHC_REPO does not contain Cargo.toml: $repository" >&2
        return 1
    fi

    printf '%s\n' "$repository"
}
