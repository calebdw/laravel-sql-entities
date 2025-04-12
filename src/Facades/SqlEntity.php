<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Facades;

use CalebDW\SqlEntities\SqlEntity as SqlEntityBase;
use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static SqlEntityBase get(string $name)
 * @method static void create(SqlEntityBase|class-string<SqlEntityBase>|string $entity)
 * @method static void drop(SqlEntityBase|class-string<SqlEntityBase>|string $entity)
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
