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

- 📦 Class-based definitions: bringing views, functions, triggers, and more into your application code.
- 🧠 First-class source control: you can easily track changes, review diffs, and resolve conflicts.
- 🧱 Decoupled grammars: letting you support multiple drivers without needing dialect-specific SQL.
- 🔁 Lifecycle hooks: run logic at various points, enabling logging, auditing, and more.
- 🚀 Batch operations: easily create or drop all entities in a single command or lifecycle event.
- 🧪 Testability: definitions are just code so they’re easy to test, validate, and keep consistent.

Whether you're managing reporting views, business logic functions, or automation
triggers, this package helps you treat SQL entities like real, versioned parts
of your codebase---no more scattered SQL in migrations!

> [!NOTE]
> Migration rollbacks are not supported since the definitions always reflect the latest state.
>
> ["We're never going backwards. You only go forward." -Taylor Otwell](https://www.twitch.tv/theprimeagen/clip/DrabAltruisticEggnogVoHiYo-f6CVkrqraPsWrEht)

## 📦 Installation

First pull in the package using Composer:

```bash
composer require calebdw/laravel-sql-entities
```

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag=sql-entities-config
```

The package looks for SQL entities under `database/entities/` so you might need to add
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

> [!TIP]
> This package looks for any files matching `database/entities` in the application's
> base path. This means it should automatically work for a modular setup where
> the entities might be spread across multiple directories.

## Configuration

The package ships with a configuration file that controls automatic syncing behavior:

| Option | Default | Description |
|---|---|---|
| `sync` | `true` | Automatically sync (refresh) entities whenever migrations run. |
| `drop_on_migrate` | `true` | Drop all entities before migrations start and recreate them after. When `false`, entities are only refreshed after migrations finish. |

When `drop_on_migrate` is enabled, all entities are dropped before migrations begin to prevent failures caused by dependent schema changes (e.g., dropping a column that a view references). However, this means entities will be unavailable while migrations are running, which can be problematic if the application is still serving requests.

When disabled, entities are refreshed (using `CREATE OR REPLACE`) after migrations finish. If a refresh fails due to a schema change, the entity is automatically dropped and recreated. For migrations that require specific entities to be absent, you can use the [`withoutEntities()`](#%EF%B8%8F-withoutentities) method for more granular control.

## 🛠️ Usage

### 🧱 SQL Entities

To get started, create a new class in a `database/entities/` directory
(structure is up to you) and extend the appropriate entity class (e.g. `View`, etc.).

For example, to create a view for recent orders, you might create the following class:

```php
<?php

namespace Database\Entities\Views;

use App\Models\Order;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Query\Builder;
use Override;

// will create a view named `recent_orders_view`
class RecentOrdersView extends View
{
    #[Override]
    public function definition(): Builder|string
    {
        return Order::query()
            ->select(['id', 'customer_id', 'status', 'created_at'])
            ->where('created_at', '>=', now()->subDays(30))
            ->toBase();

        // could also use raw SQL
        return <<<'SQL'
            SELECT id, customer_id, status, created_at
            FROM orders
            WHERE created_at >= NOW() - INTERVAL '30 days'
            SQL;
    }
}
```

You can also override the name and connection:

```php
<?php
class RecentOrdersView extends View
{
    protected ?string $name = 'other_name';
    // also supports schema
    protected ?string $name = 'other_schema.other_name';

    protected ?string $connection = 'other_connection';
}
```

#### 🔁 Lifecycle Hooks

You can also use the provided lifecycle hooks to run logic before or after an entity is created or dropped.
Returning `false` from the `creating` or `dropping` methods will prevent the entity from being created or dropped, respectively.

```php
<?php
use Illuminate\Database\Connection;

class RecentOrdersView extends View
{
    // ...

    #[Override]
    public function creating(Connection $connection): bool
    {
        if (/** should not create */) {
            return false;
        }

        /** other logic */

        return true;
    }

    #[Override]
    public function created(Connection $connection): void
    {
        $this->connection->statement(<<<SQL
            GRANT SELECT ON TABLE {$this->name()} TO other_user;
            SQL);
    }

    #[Override]
    public function dropping(Connection $connection): bool
    {
        if (/** should not drop */) {
            return false;
        }

        /** other logic */

        return true;
    }

    #[Override]
    public function dropped(Connection $connection): void
    {
        /** logic */
    }
}
```

#### ⚙️ Handling Dependencies

Entities may depend on one another (e.g., a view that selects from another view).
To support this, each entity can declare its dependencies using the `dependencies()` method:

```php
<?php

class RecentOrdersView extends View
{
    #[Override]
    public function dependencies(): array
    {
        return [OrdersView::class];
    }
}
```

The manager will ensure that dependencies are created in the correct order, using a topological sort behind the scenes.
In the example above, `OrdersView` will be created before `RecentOrdersView` automatically.

#### 📑 View

The `View` class is used to create views in the database.
In addition to the options above, you can use the following options to further customize the view:

```php
<?php

class RecentOrdersView extends View
{
    // to create a recursive view
    protected bool $recursive = true;
    // adds a `WITH CHECK OPTION` clause to the view
    protected string|true|null $checkOption = 'cascaded';
    // can provide explicit column listing
    protected ?array $columns = ['id', 'customer_id', 'status', 'created_at'];
}
```

Additionally, you can start a query against the view using the `query()` method:

```php
<?php

RecentOrdersView::query()
    ->where('created_at', '>=', now()->subDays(30))
    ->get();
```

<!-- #### 💿 Materialized View -->
<!---->
#### 📐 Function

The `Function_` class is used to create functions in the database.

> [!TIP]
> The class is named `Function_` as `function` is a reserved keyword in PHP.

In addition to the options above, you can use the following options to further customize the function:

```php
<?php

namespace Database\Entities\Functions;

use CalebDW\SqlEntities\Function_;

class Add extends Function_
{
    /** If the function aggregates. */
    protected bool $aggregate = false;

    protected array $arguments = [
        'integer',
        'integer',
    ];

    /** The language the function is written in. */
    protected string $language = 'SQL';

    /** The function return type. */
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

Loadable functions are also supported:

```php
<?php

namespace Database\Entities\Functions;

use CalebDW\SqlEntities\Function_;

class Add extends Function_
{
    protected array $arguments = [
        'integer',
        'integer',
    ];

    /** The language the function is written in. */
    protected string $language = 'c';

    protected bool $loadable = true;

    /** The function return type. */
    protected string $returns = 'integer';

    #[Override]
    public function definition(): string
    {
        return 'c_add';
    }
}
```

<!-- #### 📤 Procedure -->
<!---->

#### ⚡ Trigger

The `Trigger` class is used to create triggers in the database.
In addition to the options above, you can use the following options to further customize the trigger:

```php
<?php

namespace Database\Entities\Triggers;

use CalebDW\SqlEntities\Trigger;

class AccountAuditTrigger extends Trigger
{
    // if the trigger is a constraint trigger
    // PostgreSQL only
    protected bool $constraint = false;

    protected string $timing = 'AFTER';

    protected array $events = ['UPDATE'];

    protected string $table = 'accounts';

    #[Override]
    public function definition(): string
    {
        return $this->definition ?? <<<'SQL'
            EXECUTE FUNCTION record_account_audit();
            SQL;
    }
}
```

<!-- #### 🔢 Sequence -->
<!---->
<!-- #### 🧳 Domain -->
<!---->
<!-- #### 🧬 Type -->
<!---->
<!-- #### 🛡 Policy -->

### 🧠 Manager

The `SqlEntityManager` singleton is responsible for creating and dropping SQL entities at runtime.
You can interact with it directly, or use the `SqlEntity` facade for convenience.

```php
<?php
use CalebDW\SqlEntities\Facades\SqlEntity;
use CalebDW\SqlEntities\SqlEntityManager;
use CalebDW\SqlEntities\View;

// Create a single entity by class or instance
SqlEntity::create(RecentOrdersView::class);
resolve(SqlEntityManager::class)->create(RecentOrdersView::class);
resolve('sql-entities')->create(new RecentOrdersView());

// Similarly, you can drop a single entity using the class or instance
SqlEntity::drop(RecentOrdersView::class);

// Create, drop, or refresh all entities
SqlEntity::createAll();
SqlEntity::dropAll();
SqlEntity::refreshAll();

// You can also filter by type or connection
SqlEntity::createAll(types: View::class, connections: 'reporting');
SqlEntity::dropAll(types: View::class, connections: 'reporting');
SqlEntity::refreshAll(types: View::class, connections: 'reporting');
```

#### ♻️ `withoutEntities()`

Sometimes you need to run a block of logic (like renaming a table column) *without certain SQL entities present*.
The `withoutEntities()` method temporarily drops the selected entities, executes your callback, and then recreates them afterward.

If the database connection supports **schema transactions**, the entire operation is wrapped in one.

```php
<?php
use CalebDW\SqlEntities\Facades\SqlEntity;
use Illuminate\Database\Connection;

SqlEntity::withoutEntities(function (Connection $connection) {
    $connection->getSchemaBuilder()->table('orders', function ($table) {
        $table->renameColumn('old_customer_id', 'customer_id');
    });
});
```

You can also restrict the scope to certain entity types or connections:

```php
<?php
use CalebDW\SqlEntities\Facades\SqlEntity;
use Illuminate\Database\Connection;

SqlEntity::withoutEntities(
    callback: function (Connection $connection) {
        $connection->getSchemaBuilder()->table('orders', function ($table) {
            $table->renameColumn('old_customer_id', 'customer_id');
        });
    },
    types: [RecentOrdersView::class, RecentHighValueOrdersView::class],
    connections: ['reporting'],
);
```

After the callback, all affected entities are automatically recreated in dependency order.

### 💻 Console Commands

The package provides console commands to create and drop your SQL entities.

```bash
php artisan sql-entities:create [entities] [--connection=CONNECTION ...]

# Create all entities
php artisan sql-entities:create
# Create a specific entity
php artisan sql-entities:create 'Database\Entities\Views\RecentOrdersView'
# Create all entities on a specific connection
php artisan sql-entities:create -c reporting

# Similarly, drop all entities
php artisan sql-entities:drop

# Refresh all entities (attempts CREATE OR REPLACE, falls back to drop + create)
php artisan sql-entities:refresh
```

### 🚀 Automatic syncing when migrating

By default, SQL entities are automatically synced whenever migrations run.
This is controlled by the `sync` config option and is enabled out of the box.

When `drop_on_migrate` is enabled (the default), all entities are dropped before
migrations start and recreated after they finish. This prevents failures when
migrations alter tables that entities depend on.

When `drop_on_migrate` is disabled, entities are only refreshed after migrations
finish. The refresh uses `CREATE OR REPLACE` where possible and falls back to
dropping and recreating if that fails (e.g., when a view's columns have changed).

Entities are also refreshed when there are no pending migrations, ensuring any
newly added or updated entities are always created.

To disable automatic syncing entirely, set `sync` to `false` in the config.

## 🤝 Contributing

Thank you for considering contributing! You can read the contribution guide [here](CONTRIBUTING.md).

## ⚖️ License

This is open-sourced software licensed under the [MIT license](LICENSE).

## 🔀 Alternatives

- [laravel-sql-views](https://github.com/stats4sd/laravel-sql-views)
- [laravel-migration-views](https://github.com/staudenmeir/laravel-migration-views)
