<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\View;
use Override;

class FooConnectionUserView extends View
{
    #[Override]
    public function name(): string
    {
        return 'users_view';
    }

    #[Override]
    public function definition(): string
    {
        return 'SELECT id, name FROM users';
    }

    #[Override]
    public function connectionName(): string
    {
        return 'foo';
    }
}
