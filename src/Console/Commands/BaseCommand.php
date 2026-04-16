<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Console\Commands;

use Illuminate\Console\Command;
use Override;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class BaseCommand extends Command
{
    /** @return array<mixed> */
    #[Override]
    protected function getArguments(): array
    {
        return [
            new InputArgument('entities', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The entities to create.', null),
        ];
    }

    /** @return array<mixed> */
    #[Override]
    protected function getOptions(): array
    {
        return [
            new InputOption('connection', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The connection(s) to use.'),
        ];
    }
}
