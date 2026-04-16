<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Listeners\SyncSqlEntities;
use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\NoPendingMigrations;

beforeEach(function () {
    test()->manager  = test()->mock(SqlEntityManager::class);
    test()->listener = resolve(SyncSqlEntities::class);
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
    it('does not drop when dropAllWhenMigrating is false', function () {
        $listener = new SyncSqlEntities(test()->manager, dropOnMigrate: false);

        test()->manager->shouldNotReceive('dropAll');

        $listener->handleStarted(
            new MigrationsStarted(method: 'up'),
        );
    });
});

describe('ended', function () {
    it('does nothing if the method is not "up"', function () {
        test()->manager->shouldNotReceive('refreshAll');
        test()->listener->handleEnded(
            new MigrationsEnded(method: 'down'),
        );
    });
    it('does nothing if the pretend option is true', function () {
        test()->manager->shouldNotReceive('refreshAll');
        test()->listener->handleEnded(
            new MigrationsEnded(method: 'up', options: ['pretend' => true]),
        );
    });
    it('refreshes all entities', function () {
        test()->manager
            ->shouldReceive('refreshAll')
            ->once();

        test()->listener->handleEnded(
            new MigrationsEnded(method: 'up'),
        );
    });
});

describe('no pending', function () {
    it('does nothing if the method is not "up"', function () {
        test()->manager->shouldNotReceive('refreshAll');
        test()->listener->handleNoPending(
            new NoPendingMigrations(method: 'down'),
        );
    });
    it('refreshes all entities', function () {
        test()->manager
            ->shouldReceive('refreshAll')
            ->once();

        test()->listener->handleNoPending(
            new NoPendingMigrations(method: 'up'),
        );
    });
});

describe('subscribe', function () {
    it('includes MigrationsStarted when dropAllWhenMigrating is true', function () {
        $events = test()->listener->subscribe();

        expect($events)
            ->toHaveKey(MigrationsStarted::class)
            ->toHaveKey(MigrationsEnded::class)
            ->toHaveKey(NoPendingMigrations::class);
    });

    it('excludes MigrationsStarted when dropAllWhenMigrating is false', function () {
        $subscriber = new SyncSqlEntities(test()->manager, dropOnMigrate: false);

        $events = $subscriber->subscribe();

        expect($events)
            ->not->toHaveKey(MigrationsStarted::class)
            ->toHaveKey(MigrationsEnded::class)
            ->toHaveKey(NoPendingMigrations::class);
    });
});
