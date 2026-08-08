<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

it('subscribes to migration events when sync is enabled', function () {
    config()->set('sql-entities.sync', true);

    (new CalebDW\SqlEntities\ServiceProvider(app()))->boot();

    expect(Event::hasListeners(Illuminate\Database\Events\MigrationsEnded::class))
        ->toBeTrue();
});

it('registers scheduled materialized view refreshes', function () {
    $schedule = resolve(Schedule::class);

    $events = collect($schedule->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'sql-entities:refresh-materialized-data'));

    expect($events)->toHaveCount(1);

    $event = $events->first();
    expect($event->expression)->toBe('0 * * * *');
    expect($event->withoutOverlapping)->toBeTrue();
});
