<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Contracts\SqlEntity;
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

    public function compileCreate(SqlEntity $entity): string
    {
        $statement = match (true) {
            $entity instanceof View => $this->compileViewCreate($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };

        return $this->clean($statement);
    }

    public function compileDrop(SqlEntity $entity): string
    {
        $statement = match (true) {
            $entity instanceof View => $this->compileViewDrop($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };

        return $this->clean($statement);
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
            ->replaceMatches('/ +/', ' ')
            ->trim()
            ->value();
    }
}
