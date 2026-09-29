<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Concerns;

use BackedEnum;
use CalebDW\SqlEntities\Attributes\Characteristics;
use CalebDW\SqlEntities\Attributes\Connection as ConnectionAttribute;
use CalebDW\SqlEntities\Attributes\DependsOn;
use CalebDW\SqlEntities\Attributes\Name;
use CalebDW\SqlEntities\Contracts\SqlEntity;
use Illuminate\Database\Connection;
use Illuminate\Support\Str;
use Override;
use UnitEnum;

/** @phpstan-require-implements SqlEntity */
trait DefaultSqlEntityBehaviour
{
    use ReadsClassAttributes;

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
        $name = $this->getAttributeValue($this, Name::class, 'name');

        return is_string($name) ? $name : Str::snake(class_basename($this));
    }

    #[Override]
    public function connectionName(): ?string
    {
        $connection = $this->getAttributeValue($this, ConnectionAttribute::class, 'connection');

        if ($connection instanceof BackedEnum) {
            return (string) $connection->value;
        }

        if ($connection instanceof UnitEnum) {
            return $connection->name;
        }

        return is_string($connection) ? $connection : null;
    }

    #[Override]
    public function characteristics(): array
    {
        return $this->stringList(Characteristics::class, 'characteristics');
    }

    #[Override]
    public function dependencies(): array
    {
        $dependencies = $this->getAttributeValue($this, DependsOn::class, 'dependencies', $this->dependencies);

        if ($dependencies === $this->dependencies) {
            return $this->dependencies;
        }

        $attribute = $this->getAttributeInstance($this, DependsOn::class);

        if (! $attribute instanceof DependsOn) {
            return $this->dependencies;
        }

        return is_string($attribute->dependencies)
            ? [$attribute->dependencies]
            : $attribute->dependencies;
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

    /**
     * @param class-string $attribute
     * @return list<string>
     */
    protected function stringList(string $attribute, string $property, mixed $default = []): array
    {
        return array_values((array) $this->getAttributeValue($this, $attribute, $property, $default));
    }

    /**
     * @param class-string $attribute
     * @return list<string>|null
     */
    protected function optionalStringList(string $attribute, string $property): ?array
    {
        $values = $this->getAttributeValue($this, $attribute, $property);

        if ($values === null) {
            return null;
        }

        return $this->stringList($attribute, $property);
    }

    /** @param class-string $attribute */
    protected function stringAttribute(string $attribute, string $property): string
    {
        $value = $this->getAttributeValue($this, $attribute, $property);

        if (is_string($value)) {
            return $value;
        }

        return $this->{$property};
    }

    /** @param class-string $attribute */
    protected function boolAttribute(string $attribute, string $property): bool
    {
        return (bool) $this->getAttributeValue($this, $attribute, $property);
    }
}
