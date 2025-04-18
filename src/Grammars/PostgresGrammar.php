<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\View;
use Override;

class PostgresGrammar extends Grammar
{
    #[Override]
    protected function compileViewCreate(View $entity): string
    {
        $checkOption = $this->compileCheckOption($entity->checkOption());
        $columns     = $this->compileColumnsList($entity->columns());
        $recursive   = $entity->isRecursive() ? ' RECURSIVE' : '';

        return <<<SQL
            CREATE OR REPLACE{$recursive} VIEW {$entity->name()}{$columns} AS
            {$entity->toString()}
            {$checkOption}
            SQL;
    }
}
