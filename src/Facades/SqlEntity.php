<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Facades;

use CalebDW\SqlEntities\Contracts\SqlEntity as SqlEntityContract;
use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static SqlEntityContract get(string $name)
 * @method static void create(SqlEntityContract|class-string<SqlEntityContract>|string $entity)
 * @method static void drop(SqlEntityContract|class-string<SqlEntityContract>|string $entity)
 * @method static void createAll(?string $type = null, ?string $connection = null)
 * @method static void dropAll(?string $type = null, ?string $connection = null)
 *
 * @see SqlEntityManager
 */
class SqlEntity extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return SqlEntityManager::class;
    }
}
