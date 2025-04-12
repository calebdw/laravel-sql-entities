<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Listeners;

use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;

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

    /**
     * @codeCoverageIgnore
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            MigrationsStarted::class => 'handleStarted',
            MigrationsEnded::class   => 'handleEnded',
        ];
    }
}
