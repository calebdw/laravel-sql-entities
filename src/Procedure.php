<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Attributes\Arguments;
use CalebDW\SqlEntities\Attributes\Language;
use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\SqlEntity;

abstract class Procedure implements SqlEntity
{
    use DefaultSqlEntityBehaviour;

    /**
     * The procedure arguments.
     *
     * @var list<string>
     */
    protected array $arguments = [];

    /** The language the procedure is written in. */
    protected string $language = 'SQL';

    /**
     * The procedure arguments.
     *
     * @return list<string>
     */
    public function arguments(): array
    {
        return $this->stringList(Arguments::class, 'arguments');
    }

    /** The language the procedure is written in. */
    public function language(): string
    {
        return $this->stringAttribute(Language::class, 'language');
    }
}
