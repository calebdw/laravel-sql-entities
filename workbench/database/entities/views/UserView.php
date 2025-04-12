<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Override;

class UserView extends View
{
    public bool $shouldCreate = true;

    public bool $shouldDrop = true;

    #[Override]
    public function definition(): string
    {
        return 'SELECT id, name FROM users';
    }

    #[Override]
    public function creating(Connection $connection): bool
    {
        return $this->shouldCreate;
    }

    #[Override]
    public function dropping(Connection $connection): bool
    {
        return $this->shouldDrop;
    }
}
