<?php

declare(strict_types=1);

use CalebDW\SqlEntities\SqlEntityManager;

beforeEach(function () {
    test()->manager = test()->mock(SqlEntityManager::class);
});

it('can refresh materialized data', function () {
    test()->manager
        ->shouldReceive('refreshMaterializedData')
        ->once()
        ->with(null, null, null);

    test()->artisan('sql-entities:refresh-materialized-data')
        ->assertExitCode(0);
});

it('can refresh specific entities', function () {
    test()->manager
        ->shouldReceive('refreshMaterializedData')
        ->once();

    test()->artisan('sql-entities:refresh-materialized-data', [
        'entities' => ['Database\Entities\Views\ActiveUsersView'],
    ])->assertExitCode(0);
});

it('can force concurrent refresh', function () {
    test()->manager
        ->shouldReceive('refreshMaterializedData')
        ->once()
        ->with(null, null, true);

    test()->artisan('sql-entities:refresh-materialized-data --concurrent')
        ->assertExitCode(0);
});

it('can force non-concurrent refresh', function () {
    test()->manager
        ->shouldReceive('refreshMaterializedData')
        ->once()
        ->with(null, null, false);

    test()->artisan('sql-entities:refresh-materialized-data --no-concurrent')
        ->assertExitCode(0);
});
