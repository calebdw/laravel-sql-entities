<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Function_;
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
    protected function compileViewCreate(View $entity): string
    {
        $checkOption = $this->compileCheckOption($entity->checkOption());
        $columns     = $this->compileList($entity->columns());
        $recursive   = $entity->isRecursive() ? ' RECURSIVE' : '';

        return <<<SQL
            CREATE OR REPLACE {$recursive} VIEW {$entity->name()} {$columns} AS
            {$entity->toString()}
            {$checkOption}
            SQL;
    }
}
