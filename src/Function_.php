<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

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

    /** The language the function is written in. */
    public function aggregate(): bool
    {
        return $this->aggregate;
    }

    /**
     * The function arguments.
     *
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->arguments;
    }

    /** The language the function is written in. */
    public function language(): string
    {
        return $this->language;
    }

    /** If the function is loadable. */
    public function loadable(): bool
    {
        return $this->loadable;
    }

    /** The function return type. */
    public function returns(): string
    {
        return $this->returns;
    }
}
