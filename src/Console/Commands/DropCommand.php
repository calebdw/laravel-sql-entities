<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use CalebDW\SqlEntities\SqlEntityManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('sql-entities:drop', 'Drop SQL entities.')]
class DropCommand extends BaseCommand
{
    public function __invoke(SqlEntityManager $manager): int
    {
        /** @phpstan-ignore argument.type */
        $manager->dropAll($this->argument('entities'), $this->option('connection'));

        return self::SUCCESS;
    }
}
