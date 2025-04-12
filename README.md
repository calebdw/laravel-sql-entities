<div align="center">
  <p>
    <img src="/art/sql-entities.webp" alt="SQL Entities" width="40%">
  </p>
  <p>Manage SQL entities in <a href="https://laravel.com">Laravel</a> with ease!</p>
  <p>
    <a href="https://github.com/calebdw/laravel-sql-entities/actions/workflows/tests.yml"><img src="https://github.com/calebdw/laravel-sql-entities/actions/workflows/tests.yml/badge.svg" alt="Test Results"></a>
    <a href="https://codecov.io/github/calebdw/laravel-sql-entities"><img src="https://codecov.io/github/calebdw/laravel-sql-entities/graph/badge.svg?token=RPLQKWDM5G" alt="Code Coverage"></a>
    <a href="https://github.com/calebdw/laravel-sql-entities"><img src="https://img.shields.io/github/license/calebdw/laravel-sql-entities" alt="License"></a>
    <a href="https://packagist.org/packages/calebdw/laravel-sql-entities"><img src="https://img.shields.io/packagist/v/calebdw/laravel-sql-entities.svg" alt="Packagist Version"></a>
    <a href="https://packagist.org/packages/calebdw/laravel-sql-entities"><img src="https://img.shields.io/packagist/dt/calebdw/laravel-sql-entities.svg" alt="Total Downloads"></a>
  </p>
</div>

Laravel's schema builder and migration system are great for managing tables and
indexes---but offer no built-in support for other SQL entities, such as
(materialized) views, procedures, functions, and triggers.
These often get handled via raw SQL in migrations, making them hard to manage,
prone to unknown conflicts, and difficult to track over time.

`laravel-sql-entities` solves this by offering:

- 📦 Class-based definitions for SQL entities: bringing views, functions, triggers, and more into your application code.
- 🧠 First-class source control: definitions live in PHP files, so you can track changes, review diffs in PRs, and resolve conflicts easily.
- 🧱 Decoupled grammars per database: letting you support multiple drivers (e.g., PostgreSQL) without cluttering your logic with dialect-specific SQL.
- 🔁 Lifecycle hooks: run logic before/after an entity is created or dropped, enabling logging, auditing, or cleanup.
- 🚀 Batch operations: easily create or drop all entities in a single command or lifecycle event.
- 🧪 Testability: because definitions are just code, they’re easy to test, validate, and keep consistent with your schema.

Whether you're managing reporting views, business logic functions, or automation
triggers, this package helps you treat SQL entities like real, versioned parts
of your codebase---no more scattered SQL in migrations!

> [!NOTE]
> Migration rollbacks are not supported since the definitions always reflect the latest state.
>
> ["We're never going backwards. You only go forward." -Taylor Otwell](https://www.twitch.tv/theprimeagen/clip/DrabAltruisticEggnogVoHiYo-f6CVkrqraPsWrEht)

## Installation

First pull in the package using Composer:

```bash
composer require calebdw/laravel-sql-entities
```

<!-- And then publish the package's configuration file: -->
<!---->
<!-- ```bash -->
<!-- php artisan vendor:publish --provider="CalebDW\SqlEntities\ServiceProvider" -->
<!-- ``` -->

The package looks for Entities under `database/entities/` so you will need to add
a namespace to your `composer.json` file, for example:

```diff
{
  "autoload": {
    "psr-4": {
      "App\\": "app/",
+     "Database\\Entities\\": "database/entities/",
      "Database\\Factories\\": "database/factories/",
      "Database\\Seeders\\": "database/seeders/"
    }
  }
}
```

> [!NOTE]
> This package looks for any files matching `database/entities` in the application's
> base path. This means it should automatically work for a modular setup.

<!-- ## Configuration -->

## Usage

## Contributing

Thank you for considering contributing! You can read the contribution guide [here](CONTRIBUTING.md).

## License

This is open-sourced software licensed under the [MIT license](LICENSE).

## Alternatives

- [laravel-sql-views](https://github.com/stats4sd/laravel-sql-views)
- [laravel-migration-views](https://github.com/staudenmeir/laravel-migration-views)
