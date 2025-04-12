<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Facades;

use CalebDW\SqlEntities\EntityManager;
use Illuminate\Support\Facades\Facade;
use Override;

/** @mixin EntityManager */
class SqlEntity extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return EntityManager::class;
    }
}
