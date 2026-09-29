<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Attributes\CheckOption;
use CalebDW\SqlEntities\Attributes\Columns;
use CalebDW\SqlEntities\Attributes\Recursive;
use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\SqlEntity;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class View implements SqlEntity
{
    use DefaultSqlEntityBehaviour;

    /**
     * The check option for the view.
     *
     * @var 'cascaded'|'local'|true|null
     */
    protected string|true|null $checkOption = null;

    /**
     * The explicit column list for the view.
     *
     * @var ?list<string>
     */
    protected ?array $columns = null;

    /** If the view is recursive. */
    protected bool $recursive = false;

    /** The check option for the view. */
    public function checkOption(): string|true|null
    {
        $option = $this->getAttributeValue($this, CheckOption::class, 'checkOption');

        if ($option === null || $option === true || is_string($option)) {
            return $option;
        }

        return null;
    }

    /**
     * The explicit column list for the view.
     *
     * @return ?list<string>
     */
    public function columns(): ?array
    {
        return $this->optionalStringList(Columns::class, 'columns');
    }

    /** If the view is recursive. */
    public function isRecursive(): bool
    {
        return $this->boolAttribute(Recursive::class, 'recursive');
    }

    public static function query(?string $as = null): Builder
    {
        $instance = app(static::class);

        return DB::connection($instance->connectionName())
            ->table($instance->name(), $as);
    }
}
