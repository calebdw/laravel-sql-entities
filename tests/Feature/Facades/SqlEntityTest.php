<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Facades\SqlEntity;
use CalebDW\SqlEntities\SqlEntityManager;

it('is a facade', function () {
    expect(SqlEntity::getFacadeRoot())->toBeInstanceOf(SqlEntityManager::class);
});
