<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\View;
use Override;

class FooConnectionUserView extends View
{
    protected ?string $connection = 'foo';

    protected ?string $name = 'user_view';

    #[Override]
    public function definition(): string
    {
        return 'SELECT id, name FROM users';
    }
}
