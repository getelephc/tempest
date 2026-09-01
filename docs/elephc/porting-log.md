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
`HEAD` to a temporary directory and uses the checkout's `target/` only as a
Cargo cache. No compiler patch, repository-relative fallback, global
executable, machine-specific path, or Elephc source mutation is involved.

The applicable corpus contains 138 source patches, no root vendor patches, and
1 runtime manifest patch. Every patch has one target, full original and patched
Git blob hashes, and a path mirroring that target. The runtime Composer lock is
checksummed. The application order is bytewise stable; re-running the script is
a no-op, while a divergent file stops the process.

`scripts/build-elephc.sh` begins with an applied-state check, installs the
isolated runtime Composer graph, applies its manifest patch, builds the
temporary Elephc source, installs the locked PCRE2 10.47 native package, and
verifies the object-expression `::class` regression probe. Compilation
therefore cannot silently succeed from an unpatched checkout, a stale compiler,
or a compiler that has lost that support.
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

### Elephc 0.26.5 refresh

The profile was revalidated against Elephc `0.26.5` at `33b490754`. Native
support removed 45 source patch files and both root vendor patches. Eleven mixed
patches were regenerated so they retain only unrelated workarounds. The removed
rewrites covered:

- ordinary, non-promoted asymmetric `private(set)` properties;
- keyword-named methods and enum cases (`clone`, `do`, `AND`, `OR`, `TRY`,
  `CONTINUE`, and `Static`);
- foreach array destructuring;
- property and element increments in expression position;
- assignments used directly as comparison operands;
- final static methods.

The local compiler patch for `object` type-name resolution was also removed:
the fix and its regression coverage are now upstream. The local
`object-class.php` probe remains as a cheap compiler-version gate rather than a
source workaround.

Current Elephc requires regex users to declare PCRE2 through its native package
manager. The runtime therefore commits `elephc.toml` and `elephc.lock`, and the
build runs `elephc native install --locked` before final linking.

Two 0.26.5 defects surfaced only after the obsolete patches were removed. The
kernel now branches before constructing over a nullable container, avoiding the
`??` inference failure tracked in #822. The synthetic response sender emits the
profile's fixed HTML headers directly, avoiding the fluent interface/nested
header ownership corruption tracked in #835.

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
also replaced by an explicit controller dispatcher so the AOT call graph and
argument shapes stay finite. The supported dynamic route has one required string
parameter; this is not a general parameter-binding implementation.

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

- Reserved `Namespace` identifiers and `namespace\function()` relative calls.
- Standalone ternary expressions rewritten as `if`/`else`.
- Asymmetric `private(set)` visibility on promoted properties normalized to
  public promotion.
- PHP 8.5 clone-with expressions lowered to clone-and-assign or construction.
- Dynamic first-class callable, `instanceof`, and class-constant expressions
  lowered to supported local-variable or builtin forms.
- Parameter retyping, backed-enum interface property access, `array_first()`,
  namespaced `NAN`, and enum-case property defaults rewritten locally.
- Finite-profile rewrites replace runtime includes, discovery, reflection, and
  unconstrained callable dispatch with an explicit manifest.

The promoted setter-visibility rewrite relaxes encapsulation and is a known
semantic difference. The finite profile does not depend on those modified
upstream classes, but the patch series remains executable evidence for future
compiler work.

## Elephc issue coverage

Every reproducible compiler incompatibility left by this refresh was searched
across open and closed Elephc issues. Existing coverage was reused; missing
coverage was filed against the minimal reproducer.

| Incompatibility | Elephc issue |
|---|---|
| Enum case default on a typed property | [#566](https://github.com/illegalstudio/elephc/issues/566) |
| Interface/null coalescing inferred as `Mixed` | [#822](https://github.com/illegalstudio/elephc/issues/822) |
| Promoted asymmetric `private(set)` | [#823](https://github.com/illegalstudio/elephc/issues/823) |
| PHP 8.5 clone-with | [#824](https://github.com/illegalstudio/elephc/issues/824) |
| Relative `namespace\function()` call | [#825](https://github.com/illegalstudio/elephc/issues/825) |
| `Namespace` as a qualified-name segment | [#826](https://github.com/illegalstudio/elephc/issues/826) |
| Standalone ternary statement | [#827](https://github.com/illegalstudio/elephc/issues/827) |
| Dynamic first-class callable | [#828](https://github.com/illegalstudio/elephc/issues/828) |
| Object property on the right of `instanceof` | [#829](https://github.com/illegalstudio/elephc/issues/829) |
| Dynamic class constant / enum case fetch | [#830](https://github.com/illegalstudio/elephc/issues/830) |
| Parameter retyped in default local mode | [#831](https://github.com/illegalstudio/elephc/issues/831) |
| `BackedEnum::$value` after narrowing | [#832](https://github.com/illegalstudio/elephc/issues/832) |
| Missing PHP 8.4 `array_first()` | [#833](https://github.com/illegalstudio/elephc/issues/833) |
| Namespaced constant named `NAN` | [#834](https://github.com/illegalstudio/elephc/issues/834) |
| Fluent interface result corrupts nested header strings | [#835](https://github.com/illegalstudio/elephc/issues/835) |

Runtime-dynamic includes and open-ended discovery are not filed as compiler
bugs: Elephc documents them as deliberate AOT boundaries, and no finite compiler
fix can enumerate classes selected from future filesystem state. Their profile
rewrites remain explicit rather than being presented as missing PHP syntax.
