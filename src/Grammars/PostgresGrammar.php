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
        return <<<SQL
            CREATE OR REPLACE VIEW {$entity->name()} AS
            {$entity}
            SQL;
    }

    #[Override]
    protected function compileViewDrop(View $entity): string
    {
        return <<<SQL
            DROP VIEW IF EXISTS {$entity->name()} CASCADE
            SQL;
    }
}
