# Elephc compatibility patch corpus

`patches/vendor/` records the vendor parser rewrites found while probing the full Tempest boot. The patches are pinned to `composer.lock` by `vendor.composer-lock.sha256` and are intentionally retained even though the finite profile under `elephc/` does not load Composer at runtime.

Tempest source compatibility rewrites are tracked directly in Git. They cover asymmetric setter visibility, relative `namespace\` calls, dynamic object `::class`, keyword method names, PHP 8.5 clone-with syntax, and expression statements.

After installing dependencies, run:

```bash
./scripts/apply-vendor-patches.sh
npm run audit:patches
```

Patches may be removed only when the original file passes the full-framework probe and an Elephc regression test protects the compiler-side fix.
