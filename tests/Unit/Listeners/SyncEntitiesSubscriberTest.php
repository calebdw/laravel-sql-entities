<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Listeners\SyncSqlEntities;
use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\NoPendingMigrations;

beforeEach(function () {
    test()->manager  = Mockery::mock(SqlEntityManager::class);
    test()->listener = new SyncSqlEntities(test()->manager);
});

afterEach(function () {
    Mockery::close();
});

describe('started', function () {
    it('does nothing if the method is not "up"', function () {
        test()->manager->shouldNotReceive('dropAll');
        test()->listener->handleStarted(
            new MigrationsStarted(method: 'down'),
        );
    });
    it('does nothing if the pretend option is true', function () {
        test()->manager->shouldNotReceive('dropAll');
        test()->listener->handleStarted(
            new MigrationsStarted(method: 'up', options: ['pretend' => true]),
        );
    });
    it('drops all entities', function () {
        test()->manager
            ->shouldReceive('dropAll')
            ->once();

        test()->listener->handleStarted(
            new MigrationsStarted(method: 'up'),
        );
    });
});

describe('ended', function () {
    it('does nothing if the method is not "up"', function () {
        test()->manager->shouldNotReceive('createAll');
        test()->listener->handleEnded(
            new MigrationsEnded(method: 'down'),
        );
    });
    it('does nothing if the pretend option is true', function () {
        test()->manager->shouldNotReceive('createAll');
        test()->listener->handleEnded(
            new MigrationsEnded(method: 'up', options: ['pretend' => true]),
        );
    });
    it('creates all entities', function () {
        test()->manager
            ->shouldReceive('createAll')
            ->once();

        test()->listener->handleEnded(
            new MigrationsEnded(method: 'up'),
        );
    });
});

describe('no pending', function () {
    it('does nothing if the method is not "up"', function () {
        test()->manager->shouldNotReceive('createAll');
        test()->listener->handleNoPending(
            new NoPendingMigrations(method: 'down'),
        );
    });
    it('creates all entities', function () {
        test()->manager
            ->shouldReceive('createAll')
            ->once();

        test()->listener->handleNoPending(
            new NoPendingMigrations(method: 'up'),
        );
    });
});
