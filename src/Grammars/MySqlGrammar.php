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
        $columns     = $this->compileList($entity->columns());
        $checkOption = $this->compileCheckOption($entity->checkOption());

        return <<<SQL
            CREATE OR REPLACE VIEW {$entity->name()} {$columns} AS
            {$entity->toString()}
            {$checkOption}
            SQL;
    }
}
