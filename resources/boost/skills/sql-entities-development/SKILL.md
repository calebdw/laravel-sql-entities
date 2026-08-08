---
name: sql-entities-development
description: Use this skill when creating, modifying, or working with SQL entities (views, functions, triggers) managed by the calebdw/laravel-sql-entities package. Activate when working with files in database/entities/, when a user asks about SQL views, functions, triggers, or when referencing the SqlEntity facade or manager.
---

# SQL Entities Development

## When to use this skill

Use this skill when:

- Creating or modifying SQL entity classes (views, functions, triggers)
- Working with files in `database/entities/`
- Configuring entity sync behavior with migrations
- Using the `SqlEntity` facade or `SqlEntityManager`
- Writing migrations that interact with or depend on SQL entities

## Project Setup

Entity classes live in `database/entities/` with a PSR-4 namespace. The project's `composer.json` should include:

```json
{
  "autoload": {
    "psr-4": {
      "Database\\Entities\\": "database/entities/"
    }
  }
}
```

Publish the config with: `php artisan vendor:publish --tag=sql-entities-config`

## Entity Types

### Views

Extend `CalebDW\SqlEntities\View` and implement `definition()` returning a `Builder` or raw SQL string.

```php
<?php

namespace Database\Entities\Views;

use App\Models\Order;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Query\Builder;
use Override;

class RecentOrdersView extends View
{
    #[Override]
    public function definition(): Builder|string
    {
        return Order::query()
            ->select(['id', 'customer_id', 'status', 'created_at'])
            ->where('created_at', '>=', now()->subDays(30))
            ->toBase();
    }
}
```

View-specific properties:

- `protected bool $recursive = false` -- create a recursive view.
- `protected string|true|null $checkOption = null` -- `'cascaded'`, `'local'`, or `true` for `WITH CHECK OPTION`.
- `protected ?array $columns = null` -- explicit column listing.

Query a view directly:

```php
RecentOrdersView::query()->where('status', 'shipped')->get();
```

### Functions

Extend `CalebDW\SqlEntities\Function_` (trailing underscore because `function` is reserved in PHP).

```php
<?php

namespace Database\Entities\Functions;

use CalebDW\SqlEntities\Function_;
use Override;

class Add extends Function_
{
    protected array $arguments = ['integer', 'integer'];
    protected string $language = 'SQL';
    protected string $returns = 'integer';

    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            RETURN $1 + $2;
            SQL;
    }
}
```

Function-specific properties:

- `protected bool $aggregate = false` -- if the function aggregates.
- `protected array $arguments = []` -- argument types.
- `protected string $language = 'SQL'` -- language (SQL, plpgsql, c, etc.).
- `protected bool $loadable = false` -- for loadable (shared library) functions.
- `protected string $returns` -- return type.

### Procedures

Extend `CalebDW\SqlEntities\Procedure`.

```php
<?php

namespace Database\Entities\Procedures;

use CalebDW\SqlEntities\Procedure;
use Override;

class InsertLogProcedure extends Procedure
{
    protected array $arguments = ['message text'];
    protected string $language = 'SQL';

    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            INSERT INTO logs (message, created_at) VALUES (message, NOW());
            SQL;
    }
}
```

Procedure-specific properties:
- `protected array $arguments = []` -- argument types.
- `protected string $language = 'SQL'` -- language (SQL, plpgsql, etc.).

Note: SQLite does not support stored procedures. The grammar will skip procedure entities on SQLite connections.

### Triggers

Extend `CalebDW\SqlEntities\Trigger`.

```php
<?php

namespace Database\Entities\Triggers;

use CalebDW\SqlEntities\Trigger;
use Override;

class AccountAuditTrigger extends Trigger
{
    protected string $timing = 'AFTER';
    protected array $events = ['UPDATE'];
    protected string $table = 'accounts';

    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            EXECUTE FUNCTION record_account_audit();
            SQL;
    }
}
```

Trigger-specific properties:

- `protected bool $constraint = false` -- constraint trigger (PostgreSQL only).
- `protected array $events` -- trigger events (UPDATE, INSERT, DELETE).
- `protected string $table` -- the table the trigger fires on.
- `protected string $timing` -- BEFORE, AFTER, or INSTEAD OF.

## Common Entity Properties

All entity types share these properties:

- `protected ?string $name = null` -- defaults to `snake_case` of class basename. Supports schema prefix: `'other_schema.entity_name'`.
- `protected ?string $connection = null` -- database connection name.
- `protected array $characteristics = []` -- additional SQL characteristics appended to the statement.
- `protected array $dependencies = []` -- array of entity class names this entity depends on.

## Dependencies

Declare dependencies so entities are created in the correct order (topologically sorted):

```php
class RecentOrdersView extends View
{
    protected array $dependencies = [OrdersView::class];

    // Or override the method for dynamic dependencies:
    #[Override]
    public function dependencies(): array
    {
        return [OrdersView::class];
    }
}
```

## Lifecycle Hooks

Override these methods on any entity for custom logic. Return `false` from `creating`/`dropping` to skip the operation.

```php
#[Override]
public function creating(Connection $connection): bool
{
    return true; // return false to skip creation
}

#[Override]
public function created(Connection $connection): void
{
    // e.g., grant permissions
}

#[Override]
public function dropping(Connection $connection): bool
{
    return true; // return false to skip dropping
}

#[Override]
public function dropped(Connection $connection): void
{
    // cleanup logic
}
```

## Manager & Facade

Use `CalebDW\SqlEntities\Facades\SqlEntity` or resolve `SqlEntityManager`:

```php
use CalebDW\SqlEntities\Facades\SqlEntity;
use CalebDW\SqlEntities\View;

SqlEntity::create(RecentOrdersView::class);
SqlEntity::drop(RecentOrdersView::class);

SqlEntity::createAll();
SqlEntity::dropAll();
SqlEntity::refreshAll();

// Filter by type or connection
SqlEntity::createAll(types: View::class, connections: 'reporting');
```

## withoutEntities()

Temporarily drop entities for a migration or schema change, then recreate them:

```php
use CalebDW\SqlEntities\Facades\SqlEntity;

SqlEntity::withoutEntities(function (Connection $connection) {
    $connection->getSchemaBuilder()->table('orders', function ($table) {
        $table->renameColumn('old_customer_id', 'customer_id');
    });
});

// Scoped to specific entities or connections:
SqlEntity::withoutEntities(
    callback: fn (Connection $connection) => /* schema changes */,
    types: [RecentOrdersView::class],
    connections: ['reporting'],
);
```

## Console Commands

```bash
# Create all entities
php artisan sql-entities:create

# Create specific entity
php artisan sql-entities:create 'Database\Entities\Views\RecentOrdersView'

# Create on specific connection
php artisan sql-entities:create -c reporting

# Drop all entities
php artisan sql-entities:drop

# Refresh all (CREATE OR REPLACE, falls back to drop + create)
php artisan sql-entities:refresh
```

## Migration Sync Configuration

In `config/sql-entities.php`:

```php
return [
    'sync' => false,           // auto-sync entities when migrations run
    'drop_on_migrate' => false, // drop all entities before migrations start
];
```

- `sync => true`: entities are automatically refreshed after migrations.
- `drop_on_migrate => false` (default): entities are refreshed via `CREATE OR REPLACE` after migrations finish. Failures fall back to drop + create.
- `drop_on_migrate => true`: all entities are dropped before migrations start and recreated after. Prevents dependency failures but entities are unavailable during migration.

Use `withoutEntities()` for granular control in individual migrations when `drop_on_migrate` is disabled.

## Supported Databases

Views, functions, and triggers are supported across PostgreSQL, MySQL, MariaDB, SQLite, and SQL Server, with dialect-specific SQL generated automatically by the grammar layer.

## Important Notes

- Migration rollbacks are not supported---entity definitions always reflect the latest state.
- Entity discovery scans all `database/entities` paths in the application base, supporting modular directory layouts.
- The `Function_` class has a trailing underscore because `function` is a PHP reserved keyword.
