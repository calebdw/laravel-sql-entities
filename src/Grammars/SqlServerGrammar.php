<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\View;
use Override;

class SqlServerGrammar extends Grammar
{
    #[Override]
    protected function compileViewCreate(View $entity): string
    {
        $checkOption = $this->compileCheckOption($entity->checkOption());
        $columns     = $this->compileList($entity->columns());

        return <<<SQL
            CREATE OR ALTER VIEW {$entity->name()} {$columns} AS
            {$entity->toString()}
            {$checkOption}
            SQL;
    }
}
