# Tempest on Elephc porting log

## Baseline

- Upstream: `tempestphp/tempest-framework`, branch `3.x`
- Imported commit: `a14f676369bb00c935b9603fe58ccc4c85e78820`
- Upstream release at import: `v3.16.2`
- Tempest PHP requirement: `^8.5`
- Compiler profile: `--php-version 8.5 --web`

The committed Tempest source targets are byte-identical to the imported
upstream tree. `composer.lock` pins the dependency graph, while Composer's
platform is fixed to PHP 8.5 so the install describes the language version
compiled by Elephc even when Composer itself runs on PHP 8.4. Runtime platform
checks are disabled because the generated Composer runtime is not the AOT
runtime.

## Reproducible workflow

From a source-only checkout:

```bash
export ELEPHC_REPO=/path/to/elephc
composer install
./scripts/apply-elephc-patches.sh
./scripts/apply-elephc-patches.sh --check
./scripts/build-elephc.sh
npm run test:elephc
```

`ELEPHC_REPO` is mandatory for scripts that invoke the compiler. It may point
to an Elephc checkout anywhere on disk and is resolved to an absolute path
before the clean-room export changes directory. The build exports its committed
`HEAD` to a temporary directory, applies the compiler patch there, and uses the
checkout's `target/` only as a Cargo cache. No repository-relative fallback,
global executable, machine-specific path, or Elephc source mutation is involved.

The applicable corpus contains 183 source patches, 2 root vendor patches, and
1 runtime manifest patch. Every patch has one target, full original and patched
Git blob hashes, and a path mirroring that target. Both Composer lock files are
checksummed. The application order is bytewise stable; re-running the script is
a no-op, while a divergent file stops the process.

`scripts/build-elephc.sh` begins with an applied-state check, installs the
isolated runtime Composer graph, applies its manifest patch, builds the
temporary Elephc source, and verifies the object-expression `::class` regression
probe. Compilation therefore cannot silently succeed from an unpatched
checkout, a stale compiler, or a compiler that has lost that support.
`npm run test:clean-room` exports only committed files to a temporary directory
and repeats Composer installation, first application, idempotence, compilation,
and HTTP tests.

## Elephc main compatibility refresh

Elephc main commit `9abe136ea` supports `::class` on object expressions. The
change is covered by compiler tests, although it is not yet listed under the
Elephc changelog's `Unreleased` section. The native support removed 70
`get_class()` rewrite lines across 47 Tempest targets: 40 single-purpose patch
files were removed, while 7 mixed patches were regenerated with their unrelated
compatibility rewrites intact.

The current `Unreleased` section additionally lists static local declarations
without an initializer and the implicit `null` default for untyped properties.
Neither feature had a corresponding workaround in this corpus, so no patch was
removed for those changes.

## Verified AOT boundary

The working entry point is `elephc/runtime/server.php`. The runtime has a pinned
production Composer graph and mirrors the patched Tempest package. A one-file
runtime patch renames the package's eager `autoload.files` key so unrelated
framework helpers are not imported into the finite request graph.

The profile keeps these Tempest concepts as PHP classes:

- `Tempest\Router\HttpApplication`
- `Tempest\Router\GenericRouter` and `RouteConfig`
- `Tempest\Router\MatchRouteMiddleware`
- `Tempest\Router\Routing\Matching\GenericRouteMatcher`
- `Tempest\Router\Get` method attributes
- `Tempest\Http\Request` and `Tempest\Http\Response`
- Tempest response classes and application controller classes

Runtime discovery and reflective dependency injection are replaced by the
manifest in `Elephc\TempestRuntime\Bootstrap`. `AotRequest`, `StaticContainer`,
`AotRouteHandler`, `AotResponseSender`, and `AotKernel` are the narrow synthetic
boundary. A request still crosses Tempest's application, router, route
middleware, matcher, matched-route value, controller, and response objects.

The route attributes are not reflected at runtime: their URIs and controller
classes are repeated in the finite manifest. Dynamic callable invocation is
also replaced by an explicit controller dispatcher because it corrupts typed
arguments in the current compiler. The supported dynamic route has one required
string parameter; this is not a general parameter-binding implementation.

Verified routes:

| Route | Result |
|---|---|
| `/` | `200` HTML |
| `/health` | `200` JSON |
| `/hello/tempest` | `200` text |
| `/elephc` | `302` to `https://elephc.dev` |
| `/missing` | empty `404` response |
| `POST /health` | `404` (method rejected) |

## Why the complete boot is not the profile boundary

`full-framework.php` is the diagnostic entry point for the architectural boot
path. It makes Elephc index the root Composer metadata and then calls the real
`Tempest\Router\HttpApplication`.

That path is open-ended for AOT for two independent reasons:

1. The monorepo declares many eager `autoload.files`; indexing their function
   bodies reaches tooling and optional packages that the HTTP request never calls.
2. `FrameworkKernel::boot()` invokes `BootDiscovery`, which scans runtime
   filesystem paths, computes class names, creates `ClassReflector` instances,
   and falls back to `new $discoveryClass()`. Those choices cannot be enumerated
   by Elephc from the request entry point.

The full-framework probe is intentionally outside the reproducible build. Its
next compiler error may move as Elephc main evolves, but resolving individual
syntax errors would not remove the open-ended discovery boundary. The verified
profile therefore uses the static manifest instead of claiming full dynamic
compatibility.

## Compatibility categories represented

- Parenthesized assignments inside comparisons and boolean expressions.
- Reserved `Namespace` identifiers and `namespace\function()` relative calls.
- Standalone ternary expressions rewritten as `if`/`else`.
- Asymmetric `private(set)` visibility normalized to public properties.
- Keyword method and enum-case names renamed without changing represented values.
- PHP 8.5 clone-with expressions lowered to clone-and-assign or construction.
- Composer production metadata pruned of Rector to keep tooling out of the
  runtime graph.

The setter-visibility rewrite relaxes encapsulation and is a known semantic
difference. The finite profile does not depend on those modified upstream
classes, but the patch series remains executable evidence for future compiler
work.
