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
        $columns = $this->compileList($entity->columns());

        return <<<SQL
            CREATE VIEW IF NOT EXISTS {$entity->name()} {$columns} AS
            {$entity->toString()}
            SQL;
    }
}
