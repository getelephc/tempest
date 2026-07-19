# Tempest compatibility patch corpus

The committed Tempest source tree stays identical to upstream commit
`a14f676369bb00c935b9603fe58ccc4c85e78820`. All compiler-facing rewrites are
stored as one patch per target:

- `patches/source/` contains 149 Tempest source, test, and API-documentation patches;
- `patches/vendor/` contains 2 PHP dependency patches;
- `source.baseline` pins the upstream source commit;
- `vendor.composer-lock.sha256` pins the exact Composer dependency graph.

The directory layout mirrors the target path. For example:

```text
packages/container/src/GenericContainer.php
patches/source/packages/container/src/GenericContainer.php.patch

vendor/symfony/uid/Uuid.php
patches/vendor/symfony/uid/Uuid.php.patch
```

## Applying the series

Starting from a fresh checkout:

```bash
composer install
./scripts/apply-elephc-patches.sh
./scripts/apply-elephc-patches.sh --check
./scripts/build-elephc.sh
```

The first run applies every clean target. A second run is safe and recognizes
all patches as already present. The script stops if a target is neither the
pinned original blob nor the expected patched blob.

Run the patch command again after every `composer install`, because Composer
regenerates files under `vendor/composer/`.

For maintenance, `--reverse` restores both source and installed dependencies,
while `--source-only` and `--vendor-only` limit the selected series.

## Auditing

```bash
npm run audit:patches
npm run audit:patches -- --require-applied
```

The audit verifies one target per patch, full Git blob hashes, mirrored paths,
allowed target types, source state, vendor state, the Composer lock checksum,
and a stable SHA-256 over the complete corpus.

Patches are retained as compatibility evidence until the original source
compiles and an Elephc regression test protects the compiler-side fix. A
narrower finite profile is not by itself a reason to discard them.
