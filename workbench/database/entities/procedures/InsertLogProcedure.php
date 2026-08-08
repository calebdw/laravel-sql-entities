<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\procedures;

use CalebDW\SqlEntities\Procedure;
use Override;

class InsertLogProcedure extends Procedure
{
    public array $arguments = [
        'message text',
    ];

    public array $characteristics = [];

    public ?string $definition = null;

    public string $language = 'SQL';

    #[Override]
    public function definition(): string
    {
        return $this->definition ?? <<<'SQL'
            INSERT INTO logs (message, created_at) VALUES (message, NOW());
            SQL;
    }
}
