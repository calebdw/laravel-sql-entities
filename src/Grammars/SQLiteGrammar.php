<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;
use Override;
use RuntimeException;

class SQLiteGrammar extends Grammar
{
    #[Override]
    public function supportsEntity(SqlEntity $entity): bool
    {
        return match (true) {
            $entity instanceof Function_ => false,

            default => parent::supportsEntity($entity),
        };
    }

    #[Override]
    protected function compileFunctionCreate(Function_ $entity): string
    {
        throw new RuntimeException('SQLite does not support user-defined functions.');
    }

    #[Override]
    protected function compileTriggerCreate(Trigger $entity): string
    {
        $characteristics = implode("\n", $entity->characteristics());

        return <<<SQL
            CREATE TRIGGER IF NOT EXISTS {$entity->name()}
            {$entity->timing()} {$entity->events()[0]}
            ON {$entity->table()}
            {$characteristics}
            {$entity->toString()}
            SQL;
    }

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
