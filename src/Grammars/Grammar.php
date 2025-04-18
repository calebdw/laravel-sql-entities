<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;

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
            $entity instanceof Function_ => $this->compileFunctionCreate($entity),
            $entity instanceof View      => $this->compileViewCreate($entity),

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
            $entity instanceof Function_ => $this->compileFunctionDrop($entity),
            $entity instanceof View      => $this->compileViewDrop($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };

        return $this->clean($statement);
    }

    /** Determine if the grammar supports the entity. */
    public function supportsEntity(SqlEntity $entity): bool
    {
        return match (true) {
            $entity instanceof Function_ => true,
            $entity instanceof View      => true,
            default                      => false,
        };
    }

    abstract protected function compileFunctionCreate(Function_ $entity): string;

    protected function compileFunctionDrop(Function_ $entity): string
    {
        return <<<SQL
            DROP FUNCTION IF EXISTS {$entity->name()}
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
