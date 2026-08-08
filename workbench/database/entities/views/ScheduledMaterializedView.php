<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\MaterializedView;
use CalebDW\SqlEntities\Support\Frequency;
use Illuminate\Database\Query\Builder;
use Override;

class ScheduledMaterializedView extends MaterializedView
{
    public ?array $columns = null;

    public array $characteristics = [];

    public bool $withData = true;

    public bool $concurrent = false;

    #[Override]
    public function schedule(Frequency $frequency): ?Frequency
    {
        return $frequency->hourly();
    }

    #[Override]
    public function definition(): Builder|string
    {
        return 'SELECT id, name FROM users WHERE active = true';
    }
}
