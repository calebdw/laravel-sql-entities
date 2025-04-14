<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use InvalidArgumentException;

abstract class Grammar
{
    public function __construct(
        protected Connection $connection,
    ) {
    }

    public function compileCreate(SqlEntity $entity): string
    {
        return match (true) {
            $entity instanceof View => $this->compileViewCreate($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };
    }

    public function compileDrop(SqlEntity $entity): string
    {
        return match (true) {
            $entity instanceof View => $this->compileViewDrop($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };
    }

    abstract protected function compileViewCreate(View $entity): string;

    abstract protected function compileViewDrop(View $entity): string;

    /** @param list<string>|null $columns */
    protected function compileColumnsList(?array $columns): string
    {
        if ($columns === null) {
            return '';
        }

        return ' (' . implode(', ', $columns) . ')';
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
}
