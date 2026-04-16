<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use CalebDW\SqlEntities\SqlEntityManager;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand('sql-entities:create', 'Create SQL entities.')]
class CreateCommand extends BaseCommand
{
    public function __invoke(SqlEntityManager $manager): int
    {
        /** @phpstan-ignore argument.type */
        $manager->createAll($this->argument('entities'), $this->option('connection'));

        return self::SUCCESS;
    }
}
