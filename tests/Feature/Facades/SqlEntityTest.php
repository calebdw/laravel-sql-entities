<?php

declare(strict_types=1);

use CalebDW\SqlEntities\EntityManager;
use CalebDW\SqlEntities\Facades\SqlEntity;

it('is a facade', function () {
    expect(SqlEntity::getFacadeRoot())->toBeInstanceOf(EntityManager::class);
});
