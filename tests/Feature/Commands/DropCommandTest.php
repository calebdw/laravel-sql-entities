<?php

declare(strict_types=1);

use CalebDW\SqlEntities\SqlEntityManager;

beforeEach(function () {
    test()->manager = test()->mock(SqlEntityManager::class);
});

it('can drop entities', function () {
    test()->manager
        ->shouldReceive('dropAll')
        ->once()
        ->with(null, null);

    test()->artisan('sql-entities:drop')
        ->assertExitCode(0);
});
