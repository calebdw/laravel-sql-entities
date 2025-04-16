<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Listeners;

use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\NoPendingMigrations;

class SyncSqlEntities
{
    public function __construct(
        protected SqlEntityManager $manager,
    ) {
    }

    public function handleStarted(MigrationsStarted $event): void
    {
        if ($event->method !== 'up') {
            return;
        }

        if ($event->options['pretend'] ?? false) {
            return;
        }

        $this->manager->dropAll();
    }

    public function handleEnded(MigrationsEnded $event): void
    {
        if ($event->method !== 'up') {
            return;
        }

        if ($event->options['pretend'] ?? false) {
            return;
        }

        $this->manager->createAll();
    }

    public function handleNoPending(NoPendingMigrations $event): void
    {
        if ($event->method !== 'up') {
            return;
        }

        // We still need to create the entities if there are no pending
        // migrations because new entities may have been added to the code.
        $this->manager->createAll();
    }

    /**
     * @codeCoverageIgnore
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            MigrationsStarted::class   => 'handleStarted',
            MigrationsEnded::class     => 'handleEnded',
            NoPendingMigrations::class => 'handleNoPending',
        ];
    }
}
