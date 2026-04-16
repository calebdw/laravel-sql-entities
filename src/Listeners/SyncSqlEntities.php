<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Listeners;

use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\NoPendingMigrations;

class SyncSqlEntities
{
    public function __construct(
        protected SqlEntityManager $manager,
        #[Config('sql-entities.drop_on_migrate')]
        protected bool $dropOnMigrate,
    ) {
    }

    public function handleStarted(MigrationsStarted $event): void
    {
        if (! $this->dropOnMigrate || $event->method !== 'up' || ($event->options['pretend'] ?? false)) {
            return;
        }

        $this->manager->dropAll();
    }

    public function handleEnded(MigrationsEnded $event): void
    {
        if ($event->method !== 'up' || ($event->options['pretend'] ?? false)) {
            return;
        }

        $this->manager->refreshAll();
    }

    public function handleNoPending(NoPendingMigrations $event): void
    {
        if ($event->method !== 'up') {
            return;
        }

        // We still need to refresh the entities if there are no pending
        // migrations because new entities may have been added/removed.
        $this->manager->refreshAll();
    }

    /**
     * @codeCoverageIgnore
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        $events = [
            MigrationsEnded::class     => 'handleEnded',
            NoPendingMigrations::class => 'handleNoPending',
        ];

        if ($this->dropOnMigrate) {
            $events[MigrationsStarted::class] = 'handleStarted';
        }

        return $events;
    }
}
