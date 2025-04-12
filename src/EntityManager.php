<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Entities\Entity;
use CalebDW\SqlEntities\Grammars\Grammar;
use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use CalebDW\SqlEntities\Grammars\SQLiteGrammar;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EntityManager
{
    /**
     * The active grammar instances.
     *
     * @var array<string, Grammar>
     */
    protected array $grammars = [];

    public function __construct(
        /** @var Collection<int, Entity> */
        public readonly Collection $entities,
        protected DatabaseManager $db,
    ) {
    }

    /** @throws InvalidArgumentException if the entity is not found. */
    public function get(string $name, ?string $connection = null): Entity
    {
        $entity = $this->entities->firstWhere(
            fn (Entity $e) => $e->name() === $name
                && $e->connectionName() === $connection,
        );

        throw_if(
            $entity === null,
            new InvalidArgumentException("Entity [{$name}] not found."),
        );

        return $entity;
    }

    /** @throws InvalidArgumentException if the entity is not found. */
    public function create(Entity|string $entity): void
    {
        if (is_string($entity)) {
            $entity = $this->get($entity);
        }

        $connection = $this->connection($entity);

        if (! $entity->creating($connection)) {
            return;
        }

        $grammar = $this->grammar($connection);

        $connection->statement($grammar->compileCreate($entity));
        $entity->created($connection);
    }

    /** @throws InvalidArgumentException if the entity is not found. */
    public function drop(Entity|string $entity): void
    {
        if (is_string($entity)) {
            $entity = $this->get($entity);
        }

        $connection = $this->connection($entity);

        if (! $entity->dropping($connection)) {
            return;
        }

        $grammar = $this->grammar($connection);

        $connection->statement($grammar->compileDrop($entity));
        $entity->dropped($connection);
    }

    /** @param class-string<Entity>|null $type */
    public function createAll(?string $type = null, ?string $connection = null): void
    {
        $this->entities
            ->when($connection, fn ($c) => $c->filter(
                fn ($e) => $e->connectionName() === $connection,
            ))
            ->when($type, fn ($c, $t) => $c->filter(fn ($e) => is_a($e, $t)))
            ->each(fn ($e) => $this->create($e));
    }

    /** @param class-string<Entity>|null $type */
    public function dropAll(?string $type = null, ?string $connection = null): void
    {
        $this->entities
            ->when($connection, fn ($c) => $c->filter(
                fn ($e) => $e->connectionName() === $connection,
            ))
            ->when($type, fn ($c, $t) => $c->filter(fn ($e) => is_a($e, $t)))
            ->each(fn ($e) => $this->drop($e));
    }

    protected function connection(Entity $entity): Connection
    {
        return $this->db->connection($entity->connectionName());
    }

    protected function grammar(Connection $connection): Grammar
    {
        $driver = $connection->getDriverName();

        return $this->grammars[$driver] ??= $this->createGrammar($driver, $connection);
    }

    protected function createGrammar(string $driver, Connection $connection): Grammar
    {
        return match ($driver) {
            'sqlite' => new SQLiteGrammar($connection),
            'pgsql'  => new PostgresGrammar($connection),
            default  => throw new InvalidArgumentException(
                "Unsupported driver [{$driver}].",
            ),
        };
    }
}
