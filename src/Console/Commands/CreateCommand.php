<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use CalebDW\SqlEntities\SqlEntityManager;
use Illuminate\Console\Command;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('sql-entities:create', 'Create SQL entities.')]
class CreateCommand extends Command
{
    public function __invoke(SqlEntityManager $manager): int
    {
        $connections = $this->option('connection');
        $entities    = $this->argument('entities');

        /** @phpstan-ignore argument.type */
        $manager->createAll($entities, $connections);

        return self::SUCCESS;
    }

    /** @return array<mixed> */
    #[Override]
    protected function getArguments(): array
    {
        return [
            new InputArgument(
                'entities',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'The entities to create.',
                null,
            ),
        ];
    }

    /** @return array<mixed> */
    #[Override]
    protected function getOptions(): array
    {
        return [
            new InputOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'The connection(s) to use.',
            ),
        ];
    }
}
