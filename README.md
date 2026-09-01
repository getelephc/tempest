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
  <strong>Tempest 3.16.2 &middot; 138 source + 0 vendor + 1 runtime patches &middot; real Tempest HTTP pipeline &middot; finite web AOT profile</strong>
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
  138 Tempest source patches, no Composer dependency patches, and 1 isolated
  runtime Composer patch. Every patch
  has one target, original and patched blob hashes, and a path mirroring its
  target. Application is idempotent; a divergent file stops the process.
- The profile targets PHP 8.5 and is compiled with
  `--php-version 8.5 --web`. The build refuses to run unless the complete
  patch series is applied.
- A real request runs through Tempest's `HttpApplication`, `GenericRouter`,
  `MatchRouteMiddleware`, `GenericRouteMatcher`, route model, controllers, and
  response classes. Runtime discovery, reflective dependency injection, and
  dynamic callable dispatch are replaced by finite AOT adapters.
- Verified routes: `/` (`200` HTML), `/health` (`200` JSON), `/hello/:name`
  (`200` text), `/elephc` (`302` to the Elephc website), and a real `404`
  fallback for unknown paths.

## Reproduce the build

`ELEPHC_REPO` must point to an elephc checkout; the build runs Cargo from that
checkout, so no global elephc executable is involved.

```bash
export ELEPHC_REPO=/path/to/elephc
COMPOSER_ROOT_VERSION=3.x-dev composer install
./scripts/apply-elephc-patches.sh
./scripts/build-elephc.sh
./elephc/runtime/server --listen 127.0.0.1:8080 --workers 1
```

Verify the supported behavior:

```bash
npm run test:elephc      # HTTP checks against the compiled binary
npm run audit:patches    # patch corpus integrity
npm run test:clean-room  # full install-patch-build-test cycle in a source-only export
```

`./scripts/apply-elephc-patches.sh --check` reports the applied root source
state without modifying files (the vendor series is now empty).
`scripts/build-elephc.sh` then installs the isolated runtime Composer graph,
applies its one manifest patch, builds the compiler from a temporary archive of
`ELEPHC_REPO`, installs the locked PCRE2 native package required by current
Elephc, verifies the object-expression `::class` regression probe, and compiles
the web binary. The Elephc checkout itself remains unchanged.

## Current boundaries

The entry point is `elephc/runtime/server.php`. Its isolated Composer project
mirrors the patched Tempest package instead of symlinking it and disables the
framework's eager `autoload.files` list with a committed runtime patch.

The verified request path is:

```text
elephc worker
  -> Tempest\Router\HttpApplication
  -> Tempest\Router\GenericRouter
  -> Tempest\Router\MatchRouteMiddleware
  -> Tempest\Router\Routing\Matching\GenericRouteMatcher
  -> AOT controller dispatcher
  -> controller with #[Get]
  -> Tempest\Http\Response
  -> AOT response sender
```

`Bootstrap` contains the finite route manifest. The `#[Get]` attributes remain
on the controller methods, but Elephc does not discover them with runtime
reflection. `AotRequest`, `StaticContainer`, `AotRouteHandler`,
`AotResponseSender`, and `AotKernel` are the explicit synthetic boundary.

The supported profile currently has GET routes, one required string parameter,
status/header/body/JSON/redirect responses, HEAD body suppression, and a 404
fallback. Runtime discovery, general reflective DI, arbitrary middleware
stacks, views, sessions, cookies, uploaded files, and parsed request input are
not claimed as supported.

`full-framework.php` remains a diagnostic probe for the reflective
`FrameworkKernel::boot()` path. That path stays open-ended for AOT because
`BootDiscovery` scans runtime filesystem paths and instantiates classes
reflectively, which elephc cannot enumerate from the request entry point. The
verified profile therefore uses the static manifest instead of claiming full
dynamic compatibility.

After patching, the working tree is an AOT build tree and should not be
treated as a normal upstream Tempest checkout.

## Documentation

- [Porting log: baseline, patch policy, and full-framework probe](docs/elephc/porting-log.md)
