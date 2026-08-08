<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\RequiresExplicitDrop;
use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Support\Frequency;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class MaterializedView implements RequiresExplicitDrop, SqlEntity
{
    use DefaultSqlEntityBehaviour;

    /**
     * The explicit column list for the materialized view.
     *
     * @var ?list<string>
     */
    protected ?array $columns = null;

    /** Whether to populate data on creation. */
    protected bool $withData = true;

    /** Whether to refresh concurrently (requires a unique index). */
    protected bool $concurrent = false;

    /**
     * The explicit column list for the materialized view.
     *
     * @return ?list<string>
     */
    public function columns(): ?array
    {
        return $this->columns;
    }

    /** Whether to populate data on creation. */
    public function withData(): bool
    {
        return $this->withData;
    }

    /** Whether to refresh concurrently. */
    public function isConcurrent(): bool
    {
        return $this->concurrent;
    }

    /** The refresh schedule for this materialized view or null if none. */
    public function schedule(Frequency $frequency): ?Frequency
    {
        return null;
    }

    public static function query(?string $as = null): Builder
    {
        $instance = app(static::class);

        return DB::connection($instance->connectionName())
            ->table($instance->name(), $as);
    }
}
