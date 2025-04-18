<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\View;
use Override;

class MySqlGrammar extends Grammar
{
    #[Override]
    protected function compileFunctionCreate(Function_ $entity): string
    {
        $arguments       = $this->compileList($entity->arguments());
        $aggregate       = '';
        $definition      = $entity->toString();
        $characteristics = implode("\n", $entity->characteristics());

        if ($entity->loadable()) {
            $aggregate  = $entity->aggregate() ? 'AGGREGATE' : '';
            $arguments  = '';
            $definition = "SONAME {$definition}";
        }

        return <<<SQL
            CREATE {$aggregate} FUNCTION IF NOT EXISTS {$entity->name()}{$arguments}
            RETURNS {$entity->returns()}
            {$characteristics}
            {$definition}
            SQL;
    }

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
