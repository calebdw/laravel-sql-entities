<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;
use Override;

class SqlServerGrammar extends Grammar
{
    #[Override]
    protected function compileFunctionCreate(Function_ $entity): string
    {
        $arguments       = $this->compileList($entity->arguments());
        $characteristics = implode("\n", $entity->characteristics());
        $definition      = $entity->toString();

        if ($entity->loadable()) {
            $definition = "EXTERNAL NAME {$definition}";
        }

        return <<<SQL
            CREATE OR ALTER FUNCTION {$entity->name()} {$arguments}
            RETURNS {$entity->returns()}
            {$characteristics}
            {$definition}
            SQL;
    }

    #[Override]
    protected function compileTriggerCreate(Trigger $entity): string
    {
        $events          = implode(', ', $entity->events());
        $characteristics = implode("\n", $entity->characteristics());

        return <<<SQL
            CREATE OR ALTER TRIGGER {$entity->name()}
            ON {$entity->table()}
            {$entity->timing()} {$events}
            {$characteristics}
            {$entity->toString()}
            SQL;
    }

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
