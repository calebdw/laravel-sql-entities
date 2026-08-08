<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use CalebDW\SqlEntities\SqlEntityManager;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('sql-entities:refresh', 'Refresh SQL entities.')]
class RefreshCommand extends BaseCommand
{
    public function __invoke(SqlEntityManager $manager): int
    {
        $manager->refreshAll(
            /** @phpstan-ignore argument.type */
            $this->argument('entities'),
            $this->option('connection'),
            (bool) $this->option('force'),
        );

        return self::SUCCESS;
    }

    #[Override]
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption('force', null, InputOption::VALUE_NONE, 'Include entities that implement RequiresExplicitDrop (e.g., materialized views) in the drop+recreate fallback. Only needed when no specific entities are given.'),
        ];
    }
}
