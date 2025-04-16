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
        $columns     = $this->compileColumnsList($entity->columns());

        return <<<SQL
            CREATE OR ALTER VIEW {$entity->name()}{$columns} AS
            {$entity->toString()}
            {$checkOption}
            SQL;
    }

    #[Override]
    protected function compileViewDrop(View $entity): string
    {
        return <<<SQL
            DROP VIEW IF EXISTS {$entity->name()}
            SQL;
    }
}
