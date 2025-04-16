<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\View;
use Override;

class SQLiteGrammar extends Grammar
{
    #[Override]
    protected function compileViewCreate(View $entity): string
    {
        $columns = $this->compileColumnsList($entity->columns());

        return <<<SQL
            CREATE VIEW IF NOT EXISTS {$entity->name()}{$columns} AS
            {$entity->toString()}
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
