<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Attributes\Aggregate;
use CalebDW\SqlEntities\Attributes\Arguments;
use CalebDW\SqlEntities\Attributes\Language;
use CalebDW\SqlEntities\Attributes\Loadable;
use CalebDW\SqlEntities\Attributes\Returns;
use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\SqlEntity;

abstract class Function_ implements SqlEntity
{
    use DefaultSqlEntityBehaviour;

    /** If the function aggregates. */
    protected bool $aggregate = false;

    /**
     * The function arguments.
     *
     * @var list<string>
     */
    protected array $arguments = [];

    /** The language the function is written in. */
    protected string $language = 'SQL';

    /** If the function is loadable. */
    protected bool $loadable = false;

    /** The function return type. */
    protected string $returns;

    /** If the function aggregates. */
    public function aggregate(): bool
    {
        return $this->boolAttribute(Aggregate::class, 'aggregate');
    }

    /**
     * The function arguments.
     *
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->stringList(Arguments::class, 'arguments');
    }

    /** The language the function is written in. */
    public function language(): string
    {
        return $this->stringAttribute(Language::class, 'language');
    }

    /** If the function is loadable. */
    public function loadable(): bool
    {
        return $this->boolAttribute(Loadable::class, 'loadable');
    }

    /** The function return type. */
    public function returns(): string
    {
        return $this->stringAttribute(Returns::class, 'returns');
    }
}
