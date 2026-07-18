# Tempest on Elephc porting log

## Baseline

- Upstream: `tempestphp/tempest-framework`, branch `3.x`
- Imported commit: `a14f676369bb00c935b9603fe58ccc4c85e78820`
- Upstream release at import: `v3.16.2`
- Tempest PHP requirement: `^8.5`
- Compiler profile: `--php-version 8.5 --web`

The root Composer graph is pinned for repeatable diagnostics. On hosts running PHP 8.4, install it with `composer install --no-dev --ignore-platform-req=php`, then apply `scripts/apply-vendor-patches.sh`.

## Verified AOT boundary

The working entry point is `elephc/server.php`. It deliberately lives below the repository root so Elephc does not eagerly import the monorepo's entire Composer `autoload.files` graph.

The profile keeps these Tempest concepts as PHP classes:

- `Tempest\Router\HttpApplication`
- `Tempest\Router\Router` and finite routes
- `Tempest\Router\Get` method attributes
- `Tempest\Http\Request` and `Tempest\Http\Response`
- controller classes implementing a common handler contract

Runtime discovery and reflective dependency injection are replaced by the static registration block in `HttpApplication::boot()`. This is the narrow synthetic boundary. Request parsing, route matching, controller dispatch, status codes, headers, bodies, and redirects all run in the compiled binary.

Build and verify with:

```bash
npm run build:elephc
npm run test:elephc
npm run audit:patches
```

Verified routes:

| Route | Result |
|---|---|
| `/` | `200` HTML |
| `/health` | `200` JSON |
| `/hello/tempest` | `200` text |
| `/elephc` | `302` to `https://elephc.dev` |
| `/missing` | `404` text |

## Why the complete boot is not the profile boundary

`full-framework.php` is the diagnostic entry point for the unmodified architectural boot path. It makes Elephc index the root Composer metadata and then calls the real `Tempest\Router\HttpApplication`.

That path is open-ended for AOT for two independent reasons:

1. The monorepo declares many eager `autoload.files`; scanning their function bodies reaches tooling and optional packages that the HTTP request never calls.
2. `FrameworkKernel::boot()` invokes `BootDiscovery`, which scans runtime filesystem paths, converts dynamically computed paths to class names, creates `ClassReflector` instances, and falls back to `new $discoveryClass()`. Those choices cannot be enumerated by Elephc from the request entry point.

The last full-framework probe in this baseline progressed through the Tempest source rewrites and the Composer/Symfony UUID patch, then stopped while parsing `vendor/symfony/uid/Ulid.php`. Patching that file would not remove the discovery boundary, so the verified profile uses the static manifest instead of claiming full dynamic compatibility.

## Compatibility categories found

- Parenthesized assignments inside comparisons and boolean expressions.
- Reserved `Namespace` identifiers and `namespace\function()` relative calls.
- Standalone ternary expressions rewritten as `if`/`else`.
- Asymmetric `private(set)` visibility normalized to public properties for the profile source.
- Dynamic object `::class` normalized to `get_class()`.
- Keyword method and enum-case names renamed without changing represented values.
- PHP 8.5 clone-with expressions lowered to ordinary clone-and-assign or explicit construction.
- Composer production metadata pruned of Rector, which otherwise pulled PHPStan into the runtime graph.

The setter-visibility rewrite relaxes encapsulation and is therefore a known profile-level semantic difference. The working synthetic profile does not depend on those modified upstream classes, but the edits are retained as evidence for future compiler work.
