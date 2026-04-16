<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Sync Entities on Migration
    |--------------------------------------------------------------------------
    |
    | When enabled, SQL entities will be automatically synced (refreshed)
    | whenever migrations are run. This ensures your views, functions,
    | triggers, etc. stay up to date with your schema changes.
    |
    */
    'sync' => false,

    /*
    |--------------------------------------------------------------------------
    | Drop Entities Before Migrating
    |--------------------------------------------------------------------------
    |
    | When enabled, all SQL entities will be dropped before migrations
    | start and recreated after they finish. This prevents migration
    | failures caused by dependent entities (e.g. dropping a column
    | that a view references).
    |
    | However, this can cause issues if your application is still
    | serving requests during migrations, since entities like views
    | will be unavailable until migrations complete.
    |
    | When disabled, entities will only be refreshed (CREATE OR REPLACE)
    | after migrations finish. If a refresh fails due to a schema
    | change, the entity will be dropped and recreated automatically.
    |
    | If you need more granular control within specific migrations, you
    | can use the `SqlEntityManager::withoutEntities()` method to wrap
    | schema changes that conflict with particular entities. This lets
    | you drop only the relevant entities for the duration of that
    | migration, rather than dropping all entities for the entire
    | migration run.
    |
    */
    'drop_on_migrate' => true,
];
