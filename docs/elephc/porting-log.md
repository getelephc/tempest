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
composer install
./scripts/apply-elephc-patches.sh
./scripts/apply-elephc-patches.sh --check
./scripts/build-elephc.sh
npm run test:elephc
```

The applicable corpus contains 149 source patches and 2 vendor patches. Every
patch has one target, full original and patched Git blob hashes, and a path
mirroring that target. The application order is bytewise stable. Re-running
the script is a no-op, while a divergent file stops the process.

`scripts/build-elephc.sh` begins with an applied-state check and verifies the
object-expression `::class` regression probe, so compilation cannot silently
succeed from an unpatched checkout or a compiler that has lost that support.
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

The working entry point is `elephc/server.php`. It deliberately lives below
the repository root so Elephc does not eagerly import the monorepo's complete
Composer `autoload.files` graph.

The profile keeps these Tempest concepts as PHP classes:

- `Tempest\Router\HttpApplication`
- `Tempest\Router\Router` and finite routes
- `Tempest\Router\Get` method attributes
- `Tempest\Http\Request` and `Tempest\Http\Response`
- controller classes implementing a common handler contract

Runtime discovery and reflective dependency injection are replaced by the
static registration block in `HttpApplication::boot()`. This is the narrow
synthetic boundary. Request parsing, route matching, controller dispatch,
status codes, headers, bodies, and redirects all run in the compiled binary.

Verified routes:

| Route | Result |
|---|---|
| `/` | `200` HTML |
| `/health` | `200` JSON |
| `/hello/tempest` | `200` text |
| `/elephc` | `302` to `https://elephc.dev` |
| `/missing` | `404` text |

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

The full-framework probe progresses through the Tempest source series and the
Composer/Symfony UUID patches, then stops on unsupported `require` syntax in
`vendor/phpstan/phpstan/bootstrap.php`. Patching that tooling bootstrap would
not remove the discovery boundary, so the verified profile uses the static
manifest instead of claiming full dynamic compatibility.

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
