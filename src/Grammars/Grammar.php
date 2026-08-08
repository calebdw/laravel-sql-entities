<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\MaterializedView;
use CalebDW\SqlEntities\Procedure;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

abstract class Grammar
{
    public function __construct(
        protected Connection $connection,
    ) {
    }

    /** Compile the SQL statement to create the entity. */
    public function compileCreate(SqlEntity $entity): string
    {
        $statement = match (true) {
            $entity instanceof Function_        => $this->compileFunctionCreate($entity),
            $entity instanceof MaterializedView => $this->compileMaterializedViewCreate($entity),
            $entity instanceof Procedure        => $this->compileProcedureCreate($entity),
            $entity instanceof Trigger          => $this->compileTriggerCreate($entity),
            $entity instanceof View             => $this->compileViewCreate($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };

        return $this->clean($statement);
    }

    /** Compile the SQL statement to drop the entity. */
    public function compileDrop(SqlEntity $entity): string
    {
        $statement = match (true) {
            $entity instanceof Function_        => $this->compileFunctionDrop($entity),
            $entity instanceof MaterializedView => $this->compileMaterializedViewDrop($entity),
            $entity instanceof Procedure        => $this->compileProcedureDrop($entity),
            $entity instanceof Trigger          => $this->compileTriggerDrop($entity),
            $entity instanceof View             => $this->compileViewDrop($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };

        return $this->clean($statement);
    }

    /** Compile the SQL statement to refresh a materialized view's data. */
    public function compileRefreshData(MaterializedView $entity, ?bool $concurrent = null): string
    {
        throw new RuntimeException('Driver does not support materialized views.');
    }

    /** Determine if the grammar supports the entity. */
    public function supportsEntity(SqlEntity $entity): bool
    {
        return match (true) {
            $entity instanceof Function_,
            $entity instanceof Procedure,
            $entity instanceof Trigger,
            $entity instanceof View => true,
            default                 => false,
        };
    }

    abstract protected function compileFunctionCreate(Function_ $entity): string;

    protected function compileFunctionDrop(Function_ $entity): string
    {
        return <<<SQL
            DROP FUNCTION IF EXISTS {$entity->name()}
            SQL;
    }

    protected function compileMaterializedViewCreate(MaterializedView $entity): string
    {
        throw new RuntimeException('Driver does not support materialized views.');
    }

    protected function compileMaterializedViewDrop(MaterializedView $entity): string
    {
        throw new RuntimeException('Driver does not support materialized views.');
    }

    abstract protected function compileProcedureCreate(Procedure $entity): string;

    protected function compileProcedureDrop(Procedure $entity): string
    {
        return <<<SQL
            DROP PROCEDURE IF EXISTS {$entity->name()}
            SQL;
    }

    abstract protected function compileTriggerCreate(Trigger $entity): string;

    protected function compileTriggerDrop(Trigger $entity): string
    {
        return <<<SQL
            DROP TRIGGER IF EXISTS {$entity->name()}
            SQL;
    }

    abstract protected function compileViewCreate(View $entity): string;

    protected function compileViewDrop(View $entity): string
    {
        return <<<SQL
            DROP VIEW IF EXISTS {$entity->name()}
            SQL;
    }

    /** @param list<string>|null $values */
    protected function compileList(?array $values): string
    {
        if ($values === null) {
            return '';
        }

        return '(' . implode(', ', $values) . ')';
    }

    protected function compileCheckOption(string|true|null $option): string
    {
        if ($option === null) {
            return '';
        }

        if ($option === true) {
            return 'WITH CHECK OPTION';
        }

        $option = strtoupper($option);

        return "WITH {$option} CHECK OPTION";
    }

    protected function clean(string $value): string
    {
        return Str::of($value)
            // remove extra spaces in between words
            ->replaceMatches('/(?<=\S) {2,}(?=\S)/', ' ')
            // remove trailing spaces at end of line
            ->replaceMatches('/ +\n/', "\n")
            // remove duplicate new lines
            ->replaceMatches('/\n{2,}/', "\n")
            ->trim()
            ->value();
    }
}
