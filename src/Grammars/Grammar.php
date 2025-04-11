<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Grammars;

use CalebDW\SqlEntities\Entities\Entity;
use CalebDW\SqlEntities\Entities\View;
use Illuminate\Database\Connection;
use InvalidArgumentException;

abstract class Grammar
{
    public function __construct(
        protected Connection $connection,
    ) {
    }

    public function compileCreate(Entity $entity): string
    {
        return match (true) {
            $entity instanceof View => $this->compileViewCreate($entity),

            default => throw new InvalidArgumentException(
                sprintf('Unsupported entity [%s].', $entity::class),
            ),
        };
    }

    public function compileDrop(Entity $entity): string
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
}
