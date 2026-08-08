<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use CalebDW\SqlEntities\SqlEntityManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('sql-entities:refresh-materialized-data', 'Refresh data for materialized views.')]
class RefreshMaterializedDataCommand extends BaseCommand
{
    public function __invoke(SqlEntityManager $manager): int
    {
        $concurrent = match (true) {
            $this->option('concurrent')    => true,
            $this->option('no-concurrent') => false,
            default                        => null,
        };

        $manager->refreshMaterializedData(
            /** @phpstan-ignore argument.type */
            entities: $this->argument('entities'),
            connections: $this->option('connection'),
            concurrent: $concurrent,
        );

        return self::SUCCESS;
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption('concurrent', null, InputOption::VALUE_NONE, 'Force concurrent refresh (requires a unique index).'),
            new InputOption('no-concurrent', null, InputOption::VALUE_NONE, 'Force non-concurrent refresh.'),
        ];
    }
}
