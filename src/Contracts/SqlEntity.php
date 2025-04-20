<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Contracts;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Stringable;

interface SqlEntity extends Stringable
{
    /** The entity definition. */
    public function definition(): Builder|string;

    /** The entity name. */
    public function name(): string;

    /** The entity connection name. */
    public function connectionName(): ?string;

    /**
     * Any additional characteristics for the entity.
     *
     * @return list<string>
     */
    public function characteristics(): array;

    /**
     * Any dependencies that need to be handled before this entity.
     *
     * @return array<int, class-string<self>>
     */
    public function dependencies(): array;

    /**
     * Hook before creating the entity.
     *
     * @return bool true to create the entity, false to skip.
     */
    public function creating(Connection $connection): bool;

    /** Hook after creating the entity. */
    public function created(Connection $connection): void;

    /**
     * Hook before dropping the entity.
     *
     * @return bool true to drop the entity, false to skip.
     */
    public function dropping(Connection $connection): bool;

    /** Hook after dropping the entity. */
    public function dropped(Connection $connection): void;

    /** Returns a string representation of the entity. */
    public function toString(): string;
}
