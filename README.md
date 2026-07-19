<p align="center">
  <a href="https://github.com/illegalstudio/elephc"><img src="assets/elephc-logo-mark.png" width="130" alt="elephc logo"></a>
</p>

<p align="center">
  <a href="https://tempestphp.com"><img src="https://github.com/tempestphp/.github/raw/refs/heads/main/logo_current/tempest-logo.png" width="100" alt="Tempest logo"></a>
</p>

<h1 align="center">Tempest 3 on elephc</h1>

<p align="center">
  <em>Tempest, compiled ahead of time.</em>
</p>

<p align="center">
  <a href="https://opensource.org/license/mit"><img src="https://img.shields.io/badge/License-MIT-FF7A1A?style=flat-square" alt="License: MIT"></a>
  <a href="https://x.com/nahime0"><img src="https://img.shields.io/badge/Follow-%40nahime0-FF7A1A?style=flat-square&logo=x&logoColor=white" alt="Follow @nahime0 on X"></a>
</p>

<p align="center">
  <strong>Tempest 3.16.2 &middot; 149 source + 2 vendor patches &middot; static discovery manifest &middot; finite web AOT profile</strong>
</p>

<p align="center">
  An experimental port that compiles a pinned Tempest 3.x checkout into a finite native <a href="https://github.com/illegalstudio/elephc">elephc</a> web binary while retaining every compiler compatibility rewrite as a reviewable patch corpus.
</p>

<p align="center">
  <a href="https://elephc.dev"><strong>Official Website</strong></a>
</p>

---

> [!IMPORTANT]
> This is a narrow AOT web profile, not complete Tempest compatibility and not
> a production-ready application.

## Current status

- Upstream `tempestphp/tempest-framework`, branch `3.x`, release `v3.16.2`
  (commit `a14f676369`) is the pinned baseline. Tempest sources stay
  byte-identical to that import in a fresh checkout.
- Compiler compatibility rewrites live in an explicit, file-level patch corpus:
  149 Tempest source patches plus 2 Composer dependency patches. Every patch
  has one target, original and patched blob hashes, and a path mirroring its
  target. Application is idempotent; a divergent file stops the process.
- The profile targets PHP 8.5 and is compiled with
  `--php-version 8.5 --web`. The build refuses to run unless the complete
  patch series is applied.
- The web binary keeps Tempest-style controllers, `#[Get]` attributes,
  request/response objects, and routing. Runtime discovery and container
  reflection are replaced by a static manifest in `HttpApplication::boot()`.
- Verified routes: `/` (`200` HTML), `/health` (`200` JSON), `/hello/:name`
  (`200` text), `/elephc` (`302` to the Elephc website), and a real `404`
  fallback for unknown paths.

## Reproduce the build

`ELEPHC_REPO` must point to an elephc checkout; the build runs Cargo from that
checkout, so no global elephc executable is involved.

```bash
export ELEPHC_REPO=/path/to/elephc
composer install
./scripts/apply-elephc-patches.sh
./scripts/build-elephc.sh
./elephc/server --listen 127.0.0.1:8080 --workers 1
```

Verify the supported behavior:

```bash
npm run test:elephc      # HTTP checks against the compiled binary
npm run audit:patches    # patch corpus integrity
npm run test:clean-room  # full install-patch-build-test cycle in a source-only export
```

`./scripts/apply-elephc-patches.sh --check` reports the applied state without
modifying files. `scripts/build-elephc.sh` starts from an applied-state check
and verifies the object-expression `::class` regression probe, so compilation
cannot silently succeed from an unpatched checkout or a stale compiler.

## Current boundaries

The entry point is `elephc/server.php`, deliberately below the repository root
so elephc does not eagerly import the monorepo's complete Composer
`autoload.files` graph. Request parsing, route matching, controller dispatch,
status codes, headers, bodies, and redirects all run in the compiled binary;
route and dependency registration is the static manifest, not runtime
discovery.

`full-framework.php` is the diagnostic probe for the real
`FrameworkKernel::boot()` path. That path stays open-ended for AOT because
`BootDiscovery` scans runtime filesystem paths and instantiates classes
reflectively, which elephc cannot enumerate from the request entry point. The
verified profile therefore uses the static manifest instead of claiming full
dynamic compatibility.

After patching, the working tree is an AOT build tree and should not be
treated as a normal upstream Tempest checkout.

## Documentation

- [Porting log: baseline, patch policy, and full-framework probe](docs/elephc/porting-log.md)
