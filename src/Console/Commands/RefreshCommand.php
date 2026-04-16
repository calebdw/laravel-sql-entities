<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use CalebDW\SqlEntities\SqlEntityManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('sql-entities:refresh', 'Refresh SQL entities.')]
class RefreshCommand extends BaseCommand
{
    public function __invoke(SqlEntityManager $manager): int
    {
        /** @phpstan-ignore argument.type */
        $manager->refreshAll($this->argument('entities'), $this->option('connection'));

        return self::SUCCESS;
    }
}
