<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\MaterializedView;
use Illuminate\Database\Query\Builder;
use Override;

class ActiveUserMaterializedView extends MaterializedView
{
    public ?array $columns = null;

    public array $characteristics = [];

    public bool $withData = true;

    public bool $concurrent = false;

    #[Override]
    public function definition(): Builder|string
    {
        return 'SELECT id, name FROM users WHERE active = true';
    }
}
