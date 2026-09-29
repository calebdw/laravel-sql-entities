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

Prefer attributes over properties:

- `#[Recursive]` -- create a recursive view. `#[Recursive(false)]` turns it off.
- `#[CheckOption]` or `#[CheckOption('cascaded')]` -- `WITH CHECK OPTION`. Also accepts `'local'`.
- `#[Columns('id')]` or `#[Columns(['id', 'name'])]` -- explicit column listing.

Query a view directly:

```php
RecentOrdersView::query()->where('status', 'shipped')->get();
```

### Materialized Views

Extend `CalebDW\SqlEntities\MaterializedView`. PostgreSQL only.

```php
<?php

namespace Database\Entities\Views;

use CalebDW\SqlEntities\Attributes\Columns;
use CalebDW\SqlEntities\Attributes\Concurrent;
use CalebDW\SqlEntities\MaterializedView;
use Override;

#[Columns(['id', 'name', 'email'])]
#[Concurrent]
class ActiveUsersView extends MaterializedView
{
    #[Override]
    public function definition(): Builder|string
    {
        return <<<'SQL'
            SELECT id, name, email FROM users WHERE active = true
            SQL;
    }
}
```

Prefer attributes over properties:

- `#[Columns(['id', 'name'])]` -- explicit column listing.
- `#[WithData(false)]` -- create with `WITH NO DATA`. Omit it to populate on creation.
- `#[Concurrent]` -- use `REFRESH ... CONCURRENTLY` (requires a unique index).

Query a materialized view: `ActiveUsersView::query()->get();`

Refresh materialized view data (re-run the query):

```php
SqlEntity::refreshMaterializedData();
SqlEntity::refreshMaterializedData(connections: 'reporting');
```

Command: `php artisan sql-entities:refresh-materialized-data`

**Self-scheduling:** Override the `schedule()` method to automatically register a materialized view with Laravel's scheduler:

```php
use CalebDW\SqlEntities\Support\Frequency;

public function schedule(Frequency $refresh): ?Frequency
{
    return $refresh->everyFifteenMinutes();
}
```

Return `null` (default) to disable automatic scheduling. Scheduled refreshes use `withoutOverlapping()` by default.

Definition changes require explicit drop+create via `withoutEntities()` in a migration, since there is no `CREATE OR REPLACE` for materialized views.

Materialized views implement `RequiresExplicitDrop`, protecting them from accidental blanket drops. They are only dropped when:

- Explicitly named: `SqlEntity::drop(MyView::class)` or `dropAll(types: MaterializedView::class)`
- Force flag: `SqlEntity::dropAll(force: true)` or `php artisan sql-entities:drop --force`
- CLI with arguments: `php artisan sql-entities:drop 'App\Views\MyView'`
- `withoutEntities()` follows the same rules: blanket calls skip protected entities, pass `types:` or `force: true` to include them.
- Any entity can implement `RequiresExplicitDrop` for the same protection.

### Functions

Extend `CalebDW\SqlEntities\Function_` (trailing underscore because `function` is reserved in PHP).

```php
<?php

namespace Database\Entities\Functions;

use CalebDW\SqlEntities\Attributes\Arguments;
use CalebDW\SqlEntities\Attributes\Returns;
use CalebDW\SqlEntities\Function_;
use Override;

#[Arguments(['integer', 'integer'])]
#[Returns('integer')]
class Add extends Function_
{
    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            RETURN $1 + $2;
            SQL;
    }
}
```

Prefer attributes over properties. `#[Returns]` is required.

- `#[Aggregate]` -- if the function aggregates.
- `#[Arguments(['integer', 'integer'])]` -- argument types.
- `#[Language('plpgsql')]` -- language. Defaults to SQL.
- `#[Loadable]` -- for loadable (shared library) functions.

### Procedures

Extend `CalebDW\SqlEntities\Procedure`.

```php
<?php

namespace Database\Entities\Procedures;

use CalebDW\SqlEntities\Attributes\Arguments;
use CalebDW\SqlEntities\Procedure;
use Override;

#[Arguments('message text')]
class InsertLogProcedure extends Procedure
{
    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            INSERT INTO logs (message, created_at) VALUES (message, NOW());
            SQL;
    }
}
```

Prefer attributes over properties:

- `#[Arguments('message text')]` or `#[Arguments(['message text'])]` -- argument types.
- `#[Language('plpgsql')]` -- language. Defaults to SQL.

Note: SQLite does not support stored procedures. The grammar will skip procedure entities on SQLite connections.

### Triggers

Extend `CalebDW\SqlEntities\Trigger`.

```php
<?php

namespace Database\Entities\Triggers;

use CalebDW\SqlEntities\Attributes\Events;
use CalebDW\SqlEntities\Attributes\Table;
use CalebDW\SqlEntities\Attributes\Timing;
use CalebDW\SqlEntities\Trigger;
use Override;

#[Events('UPDATE')]
#[Table('accounts')]
#[Timing('AFTER')]
class AccountAuditTrigger extends Trigger
{
    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            EXECUTE FUNCTION record_account_audit();
            SQL;
    }
}
```

Prefer attributes over properties. `#[Table]`, `#[Timing]`, and `#[Events]` are required.

- `#[Constraint]` -- constraint trigger (PostgreSQL only).
- `#[Events('UPDATE')]` or `#[Events(['INSERT', 'UPDATE'])]` -- trigger events.
- `#[Table('accounts')]` -- the table the trigger fires on.
- `#[Timing('AFTER')]` -- `BEFORE`, `AFTER`, or `INSTEAD OF`.

## Configuration

Prefer attributes over properties for every option below. Properties still work, but do not use them in new entities.

- `#[Name('other_schema.entity_name')]` -- defaults to `snake_case` of the class basename. Supports a schema prefix.
- `#[Connection('reporting')]` -- database connection name. Also accepts a `UnitEnum`.
- `#[Characteristics('WITH SCHEMABINDING')]` -- additional SQL characteristics appended to the statement.
- `#[DependsOn(OrdersView::class)]` -- entity class this entity depends on.

List attributes (`Characteristics`, `DependsOn`, `Columns`, `Arguments`, `Events`) take one value or an array: `#[DependsOn([OrdersView::class, CustomersView::class])]`. Flag attributes default to `true`; pass `false` to disable an inherited flag. An attribute applies while the matching property is still at its default.

```php
use CalebDW\SqlEntities\Attributes\Connection;
use CalebDW\SqlEntities\Attributes\DependsOn;
use CalebDW\SqlEntities\Attributes\Name;

#[Connection('reporting')]
#[DependsOn(OrdersView::class)]
#[Name('recent_orders')]
class RecentOrdersView extends View
{
}
```

Functions must define a return type, and triggers must define a table, timing, and events. PHPStan reports a missing property or attribute when `vendor/calebdw/laravel-sql-entities/extension.neon` is included.

## Dependencies

Declare dependencies so entities are created in the correct order (topologically sorted). Prefer `#[DependsOn]`:

```php
use CalebDW\SqlEntities\Attributes\DependsOn;

#[DependsOn([OrdersView::class, CustomersView::class])]
class RecentOrdersView extends View
{
}
```

Override `dependencies()` only when the list is dynamic. A method override replaces the attribute.

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

# Drop all entities (skips RequiresExplicitDrop entities)
php artisan sql-entities:drop
php artisan sql-entities:drop --force

# Refresh all (CREATE OR REPLACE, falls back to drop + create)
php artisan sql-entities:refresh
php artisan sql-entities:refresh --force
```

## Migration Sync Configuration

In `config/sql-entities.php`:

```php
return [
    'sync' => true,            // auto-sync entities when migrations run
    'drop_on_migrate' => false, // drop all entities before migrations start
];
```

- `sync => true` (default): entities are automatically refreshed after migrations.
- `drop_on_migrate => false` (default): entities are refreshed via `CREATE OR REPLACE` after migrations finish. Failures fall back to drop + create.
- `drop_on_migrate => true`: all entities are dropped before migrations start and recreated after. Prevents dependency failures but entities are unavailable during migration.

Use `withoutEntities()` for granular control in individual migrations when `drop_on_migrate` is disabled.

## Supported Databases

Views, functions, and triggers are supported across PostgreSQL, MySQL, MariaDB, SQLite, and SQL Server, with dialect-specific SQL generated automatically by the grammar layer.

## Important Notes

- Migration rollbacks are not supported---entity definitions always reflect the latest state.
- Entity discovery scans all `database/entities` paths in the application base, supporting modular directory layouts.
- The `Function_` class has a trailing underscore because `function` is a PHP reserved keyword.
