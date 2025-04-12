<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Facades;

use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Support\Facades\Facade;
use Override;

/** @mixin SqlEntityManager */
class SqlEntity extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return SqlEntityManager::class;
    }
}
