<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Support;

use Illuminate\Console\Scheduling\ManagesFrequencies;

final class Frequency
{
    use ManagesFrequencies;

    public protected(set) string $expression = '* * * * *';
}
