<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\SqlEntity;

abstract class Trigger implements SqlEntity
{
    use DefaultSqlEntityBehaviour;

    /**
     * The trigger characteristics.
     *
     * @var list<string>
     */
    protected array $characteristics = [];

    /** If the trigger is a constraint trigger. */
    protected bool $constraint = false;

    /**
     * The trigger events.
     *
     * @var list<string>
     */
    protected array $events;

    /** The table the trigger is associated with. */
    protected string $table;

    /** The trigger timing. */
    protected string $timing;

    /**
     * The function characteristics.
     *
     * @return list<string>
     */
    public function characteristics(): array
    {
        return $this->characteristics;
    }

    /** If the trigger is a constraint trigger. */
    public function constraint(): bool
    {
        return $this->constraint;
    }

    /**
     * The trigger events.
     *
     * @return list<string>
     */
    public function events(): array
    {
        return $this->events;
    }

    /** The table the trigger is associated with. */
    public function table(): string
    {
        return $this->table;
    }

    /** The trigger timing. */
    public function timing(): string
    {
        return $this->timing;
    }
}
