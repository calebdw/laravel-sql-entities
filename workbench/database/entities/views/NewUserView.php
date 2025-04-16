<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\views;

use CalebDW\SqlEntities\View;
use Override;

class NewUserView extends View
{
    /** @inheritDoc */
    protected array $dependencies = [UserView::class];

    #[Override]
    public function definition(): string
    {
        return <<<'SQL'
            SELECT id, name FROM users_view
            WHERE created_at > NOW() - INTERVAL '1 day'
            SQL;
    }
}
