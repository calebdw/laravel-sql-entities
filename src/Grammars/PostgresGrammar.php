<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;
use Override;

class PostgresGrammar extends Grammar
{
    #[Override]
    protected function compileFunctionCreate(Function_ $entity): string
    {
        $arguments       = $this->compileList($entity->arguments());
        $language        = $entity->language();
        $definition      = $entity->toString();
        $characteristics = implode("\n", $entity->characteristics());

        $definition = match (true) {
            $entity->loadable()             => "AS {$definition}",
            strtolower($language) !== 'sql' => "AS \$function$\n{$definition}\n\$function$",
            default                         => $definition,
        };

        return <<<SQL
            CREATE OR REPLACE FUNCTION {$entity->name()}{$arguments}
            RETURNS {$entity->returns()}
            LANGUAGE {$language}
            {$characteristics}
            {$definition}
            SQL;
    }

    #[Override]
    protected function compileFunctionDrop(Function_ $entity): string
    {
        $arguments = $this->compileList($entity->arguments());

        return <<<SQL
            DROP FUNCTION IF EXISTS {$entity->name()}{$arguments}
            SQL;
    }

    #[Override]
    protected function compileTriggerCreate(Trigger $entity): string
    {
        $contraint       = $entity->constraint() ? 'CONSTRAINT' : '';
        $events          = implode(' OR ', $entity->events());
        $characteristics = implode("\n", $entity->characteristics());

        return <<<SQL
            CREATE OR REPLACE {$contraint} TRIGGER {$entity->name()}
            {$entity->timing()} {$events}
            ON {$entity->table()}
            {$characteristics}
            {$entity->toString()}
            SQL;
    }

    #[Override]
    protected function compileTriggerDrop(Trigger $entity): string
    {
        return <<<SQL
            DROP TRIGGER IF EXISTS {$entity->name()} ON {$entity->table()}
            SQL;
    }

    #[Override]
    protected function compileViewCreate(View $entity): string
    {
        $checkOption     = $this->compileCheckOption($entity->checkOption());
        $columns         = $this->compileList($entity->columns());
        $recursive       = $entity->isRecursive() ? ' RECURSIVE' : '';
        $characteristics = implode("\n", $entity->characteristics());

        return <<<SQL
            CREATE OR REPLACE {$recursive} VIEW {$entity->name()} {$columns}
            {$characteristics}
            AS {$entity->toString()}
            {$checkOption}
            SQL;
    }
}
