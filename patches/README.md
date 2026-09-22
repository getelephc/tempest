# Finite Tempest web compatibility patch corpus

The committed Tempest source tree stays identical to upstream commit
`a14f676369bb00c935b9603fe58ccc4c85e78820`. All compiler-facing rewrites are
stored as one patch per target reached by the verified web entry point:

- `patches/source/` contains 20 Tempest source patches;
- the root vendor series is empty on the tested Elephc main baseline
  (`574105c407`);
- `patches/runtime/` contains the isolated runtime Composer manifest patch;
- `source.baseline` pins the upstream source commit;
- `runtime.composer-lock.sha256` pins the isolated runtime dependency graph.

The directory layout mirrors the target path. For example:

```text
packages/container/src/GenericContainer.php
patches/source/packages/container/src/GenericContainer.php.patch

elephc/runtime/vendor/tempest/framework/composer.json
patches/runtime/vendor/tempest/framework/composer.json.patch
```

## Applying the series

Starting from a fresh checkout:

```bash
export ELEPHC_REPO=/path/to/elephc
COMPOSER_ROOT_VERSION=3.x-dev composer install
./scripts/apply-elephc-patches.sh
./scripts/apply-elephc-patches.sh --check
./scripts/build-elephc.sh
```

`ELEPHC_REPO` must point to an Elephc repository checkout. The build script
compiles the compiler from that checkout and never assumes a local directory
layout or a globally installed Elephc binary.

The first run applies every clean root target. A second run is safe and
recognizes all patches as already present. With no root vendor patches,
`--vendor-only` is an explicit no-op. The build installs
`elephc/runtime/vendor`, applies the `--runtime-only` series, and stops if a
target is neither the pinned original blob nor the expected patched blob.

For maintenance, `--reverse` restores source and runtime targets,
while `--source-only`, `--vendor-only`, and `--runtime-only` select one series.

## Auditing

```bash
npm run audit:patches
npm run audit:patches -- --require-applied
```

The audit verifies one target per patch, full Git blob hashes, mirrored paths,
allowed target types, source/vendor/runtime state, the runtime Composer lock
checksum, and a stable SHA-256 over the complete corpus.

The corpus is intentionally scoped to the finite request graph rooted at
`elephc/runtime/server.php`. A source patch remains only while removing that
file-level rewrite makes the entry point fail to check, compile, or pass the
HTTP verification suite on the tested Elephc baseline.

This policy does not claim that unpatched, unreachable Tempest files compile
with Elephc. `full-framework.php` remains the diagnostic for that broader and
runtime-dynamic boundary.
