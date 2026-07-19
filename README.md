<p align="center">
  <a href="https://tempestphp.com">
    <img src="https://github.com/tempestphp/.github/raw/refs/heads/main/logo_current/tempest-logo.png" width="100" />
  </a>
</p>

<h1 align="center">Tempest</h1>
<div align="center">
  Tempest is a community-driven, modern PHP framework that gets out of your way and dares to think outside the box. Read the <a href="https://tempestphp.com">documentation</a> to get started.
</div>

<br />
<br />

## Elephc AOT profile

This repository tracks the Tempest 3.x framework plus a finite native web profile for [Elephc](https://elephc.dev). Tempest sources stay byte-identical to the pinned upstream baseline in a fresh checkout; compiler compatibility rewrites live in an explicit, file-level patch corpus.

```bash
export ELEPHC_REPO=/path/to/elephc
composer install
./scripts/apply-elephc-patches.sh
./scripts/build-elephc.sh
./elephc/server --listen 127.0.0.1:8080 --workers 1
```

`ELEPHC_REPO` is required by every script that invokes the compiler and may point to an Elephc checkout anywhere on disk. The build compiles that checkout with Cargo before compiling the profile; it does not rely on a neighboring checkout or a globally installed binary.

The patch command is idempotent and applies 149 Tempest source patches plus 2 Composer dependency patches. The build refuses to run unless the complete series is present. Run `npm run test:elephc` for the HTTP checks or `ELEPHC_REPO=/path/to/elephc npm run test:clean-room` to repeat the complete install-patch-build-test workflow in a temporary source-only export.

The verified profile serves `/`, `/health`, `/hello/:name`, and `/elephc`, with a real `404` fallback. It keeps Tempest-style controllers, `#[Get]` attributes, request/response objects, and routing; runtime discovery and container reflection are replaced by a static manifest. See [the porting log](docs/elephc/porting-log.md) for the exact boundary, patch policy, and full-framework probe.

## Introduction

Tempest is a PHP framework that _gets out of your way_.

Its design philosophy is that developers should write as little framework-related code as possible, so that they can focus on application code instead.

Zero config, zero overhead. This is Tempest:

```php
final class BookController
{
    #[Get('/books/{book}')]
    public function show(Book $book): Response
    {
        return new Ok($book);
    }

    #[Post('/books')]
    public function store(CreateBookRequest $request): Response
    {
        $book = map($request)->to(Book::class)->save();

        return new Redirect([self::class, 'show'], book: $book->id);
    }

    // …
}
```

```php
final class MigrateUpCommand
{
    public function __construct(
        private Console $console,
        private MigrationManager $migrationManager,
    ) {}

    #[ConsoleCommand(
        name: 'migrate:up',
        description: 'Run all new migrations',
        middleware: [ForceMiddleware::class, CautionMiddleware::class],
    )]
    public function __invoke(): void
    {
        $this->migrationManager->up();

        $this->console->success("Everything migrated");
    }

    #[EventHandler]
    public function onMigrationMigrated(MigrationMigrated $migrationMigrated): void
    {
        $this->console->writeln("- {$migrationMigrated->name}");
    }
}
```

Read how to get started with Tempest [here](https://tempestphp.com).

&nbsp;

## Installation

Create a Tempest project from scratch:

```
composer create-project tempest/app <name>
```

Or install Tempest in any existing project:

```
composer require tempest/framework
```

Continue to read how Tempest works in [the docs](https://tempestphp.com).

&nbsp;

## Contributing

We welcome contributing to Tempest! We only ask that you take a quick look at our [guidelines](https://tempestphp.com/main/extra-topics/contributing).

An easy way to get started is to head on over to the issues page to see some ways you might help out.

<p align="center">
	<br />
	<br />
	<sub>
		Check out the <a href="https://tempestphp.com">documentation</a>
		&nbsp;
		·
		&nbsp;
		Join the <a href="https://tempestphp.com/discord">Discord</a> server
  </sub>
</p>
