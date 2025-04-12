<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\Entities\View;
use Override;

class UserView extends View
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
}
