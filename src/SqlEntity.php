<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Stringable;

abstract class SqlEntity implements Stringable
{
    /** The entity definition. */
    abstract public function definition(): Builder|string;

    /** The entity name. */
    public function name(): string
    {
        return Str::snake(class_basename($this));
    }

    /** The entity connection name. */
    public function connectionName(): ?string
    {
        return null;
    }

    /**
     * Hook before creating the entity.
     *
     * @return bool true to create the entity, false to skip.
     */
    public function creating(Connection $connection): bool
    {
        return true;
    }

    /** Hook after creating the entity. */
    public function created(Connection $connection): void
    {
    }

    /**
     * Hook before dropping the entity.
     *
     * @return bool true to drop the entity, false to skip.
     */
    public function dropping(Connection $connection): bool
    {
        return true;
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
