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

<!-- And then publish the package's configuration file: -->
<!---->
<!-- ```bash -->
<!-- php artisan vendor:publish --provider="CalebDW\SqlEntities\ServiceProvider" -->
<!-- ``` -->

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

<!-- ## Configuration -->

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

<!-- #### 💿 Materialized View -->
<!---->
<!-- #### 🛠 Function -->
<!---->
<!-- #### 📤 Procedure -->
<!---->
<!-- #### ⚡ Trigger -->
<!---->
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

// Create or drop all entities
SqlEntity::createAll();
SqlEntity::dropAll();

// You can also filter by type or connection
SqlEntity::createAll(types: View::class, connections: 'reporting');
SqlEntity::dropAll(types: View::class, connections: 'reporting');
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

### 🚀 Automatic syncing when migrating (Optional)

You may want to automatically drop all SQL entities before migrating, and then
recreate them after the migrations are complete. This is helpful when the entities
depend on schema changes. To do this, register the built-in subscriber in a service provider:

```php
<?php
use CalebDW\SqlEntities\Listeners\SyncSqlEntities;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::subscribe(SyncSqlEntities::class);
    }
}
```

## 🤝 Contributing

Thank you for considering contributing! You can read the contribution guide [here](CONTRIBUTING.md).

## ⚖️ License

This is open-sourced software licensed under the [MIT license](LICENSE).

## 🔀 Alternatives

- [laravel-sql-views](https://github.com/stats4sd/laravel-sql-views)
- [laravel-migration-views](https://github.com/staudenmeir/laravel-migration-views)
