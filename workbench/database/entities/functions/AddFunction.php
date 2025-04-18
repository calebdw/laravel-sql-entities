<?php

declare(strict_types=1);

namespace Workbench\Database\Entities\functions;

use CalebDW\SqlEntities\Function_;
use Override;

class AddFunction extends Function_
{
    public bool $aggregate = false;

    public array $arguments = [
        'integer',
        'integer',
    ];

    public ?string $definition = null;

    public string $language = 'SQL';

    public bool $loadable = false;

    public array $characteristics = [];

    public string $returns = 'INT';

    #[Override]
    public function definition(): string
    {
        return $this->definition ?? <<<'SQL'
            RETURN $1 + $2;
            SQL;
    }
}
