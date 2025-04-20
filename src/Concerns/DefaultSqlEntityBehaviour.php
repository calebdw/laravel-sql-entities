<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Concerns;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Override;

/** @phpstan-require-implements SqlEntity */
trait DefaultSqlEntityBehaviour
{
    /** The connection name. */
    protected ?string $connection = null;

    /**
     * Any additional characteristics for the entity.
     *
     * @var list<string>
     */
    protected array $characteristics = [];

    /**
     * Any dependencies that need to be handled before this entity.
     *
     * @var array<int, class-string<SqlEntity>>
     */
    protected array $dependencies = [];

    /** The entity name. */
    protected ?string $name = null;

    #[Override]
    public function name(): string
    {
        return $this->name ?? Str::snake(class_basename($this));
    }

    #[Override]
    public function connectionName(): ?string
    {
        return $this->connection;
    }

    #[Override]
    public function characteristics(): array
    {
        return $this->characteristics;
    }

    #[Override]
    public function dependencies(): array
    {
        return $this->dependencies;
    }

    #[Override]
    public function creating(Connection $connection): bool
    {
        return true;
    }

    #[Override]
    public function created(Connection $connection): void
    {
    }

    #[Override]
    public function dropping(Connection $connection): bool
    {
        return true;
    }

    #[Override]
    public function dropped(Connection $connection): void
    {
    }

    #[Override]
    public function toString(): string
    {
        $definition = $this->definition();

        if (is_string($definition)) {
            return $definition;
        }

        return $definition->toRawSql();
    }

    #[Override]
    public function __toString(): string
    {
        return $this->toString();
    }
}
