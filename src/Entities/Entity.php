<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Entities;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Stringable;

abstract class Entity implements Stringable
{
    /** The entity name. */
    abstract public function name(): string;

    /** The entity definition. */
    abstract public function definition(): Builder|string;

    /** The entity connection name. */
    public function connectionName(): ?string
    {
        return null;
    }

    /** Hook before creating the entity. */
    public function creating(Connection $connection): void
    {
    }

    /** Hook after creating the entity. */
    public function created(Connection $connection): void
    {
    }

    /** Hook before dropping the entity. */
    public function dropping(Connection $connection): void
    {
    }

    /** Hook after dropping the entity. */
    public function dropped(Connection $connection): void
    {
    }

    public function toString(): string
    {
        $definition = $this->definition();

        if (is_string($definition)) {
            return $definition;
        }

        return $definition->toRawSql();
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
