<?php

declare(strict_types=1);

use CalebDW\SqlEntities\SqlEntityManager;

beforeEach(function () {
    test()->manager = test()->mock(SqlEntityManager::class);
});

it('can refresh entities', function () {
    test()->manager
        ->shouldReceive('refreshAll')
        ->once()
        ->with(null, null);

    test()->artisan('sql-entities:refresh')
        ->assertExitCode(0);
});
