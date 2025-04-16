<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Facades;

use CalebDW\SqlEntities\Contracts\SqlEntity as SqlEntityContract;
use CalebDW\SqlEntities\SqlEntityManager;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static SqlEntityContract get(string $name)
 * @method static void create(SqlEntityContract|class-string<SqlEntityContract> $entity)
 * @method static void drop(SqlEntityContract|class-string<SqlEntityContract> $entity)
 * @method static void createAll(array<int, class-string<SqlEntityContract>>|class-string<SqlEntityContract>|null $types = null, array<int, string>|string|null $connections = null)
 * @method static void dropAll(array<int, class-string<SqlEntityContract>>|class-string<SqlEntityContract>|null $types = null, array<int, string>|string|null $connections = null)
 * @method static void withoutEntities(Closure(Connection): mixed $callback, array<int, class-string<SqlEntityContract>>|class-string<SqlEntityContract>|null $types = null, array<int, string>|string|null $connections = null)
 *
 * @see SqlEntityManager
 */
class SqlEntity extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return 'sql-entities';
    }
}
