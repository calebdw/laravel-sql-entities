<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\View;
use Override;

class MySqlGrammar extends Grammar
{
    #[Override]
    protected function compileViewCreate(View $entity): string
    {
        $columns     = $this->compileColumnsList($entity->columns());
        $checkOption = $this->compileCheckOption($entity->checkOption());

        return <<<SQL
            CREATE VIEW {$entity->name()}{$columns} AS
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
