#!/usr/bin/env bash

set -euo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

exec "$ROOT/scripts/apply-elephc-patches.sh" --vendor-only "$@"
