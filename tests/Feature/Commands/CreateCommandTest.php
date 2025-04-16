<?php

declare(strict_types=1);

use CalebDW\SqlEntities\SqlEntityManager;

beforeEach(function () {
    test()->manager = test()->mock(SqlEntityManager::class);
});

it('can create entities', function () {
    test()->manager
        ->shouldReceive('createAll')
        ->once()
        ->with(null, null);

    test()->artisan('sql-entities:create')
        ->assertExitCode(0);
});
