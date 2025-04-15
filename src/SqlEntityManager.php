<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Grammars\Grammar;
use CalebDW\SqlEntities\Grammars\MariaDbGrammar;
use CalebDW\SqlEntities\Grammars\MySqlGrammar;
use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use CalebDW\SqlEntities\Grammars\SQLiteGrammar;
use CalebDW\SqlEntities\Grammars\SqlServerGrammar;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class SqlEntityManager
{
    /**
     * The active grammar instances.
     *
     * @var array<string, Grammar>
     */
    protected array $grammars = [];

    public function __construct(
        /** @var Collection<int, SqlEntity> */
        public readonly Collection $entities,
        protected DatabaseManager $db,
    ) {
    }

    /** @throws InvalidArgumentException if the entity is not found. */
    public function get(string $name, ?string $connection = null): SqlEntity
    {
        $entity = $this->entities->firstWhere(
            fn (SqlEntity $e) => $e->name() === $name
                && $e->connectionName() === $connection,
        );

        throw_if(
            $entity === null,
            new InvalidArgumentException("Entity [{$name}] not found."),
        );

        return $entity;
    }

    /**
     * Create an entity.
     *
     * @param class-string<SqlEntity>|string|SqlEntity $entity The entity name, class, or instance.
     * @throws InvalidArgumentException if the entity is not found.
     */
    public function create(SqlEntity|string $entity): void
    {
        if (is_string($entity)) {
            $entity = class_exists($entity)
                ? resolve($entity)
                : $this->get($entity);
        }

        assert($entity instanceof SqlEntity);
        $connection = $this->connection($entity);

        if (! $entity->creating($connection)) {
            return;
        }

        $grammar = $this->grammar($connection);

        $connection->statement($grammar->compileCreate($entity));
        $entity->created($connection);
    }

    /**
     * Drop an entity.
     *
     * @param class-string<SqlEntity>|string|SqlEntity $entity The entity name, class, or instance.
     * @throws InvalidArgumentException if the entity is not found.
     */
    public function drop(SqlEntity|string $entity): void
    {
        if (is_string($entity)) {
            $entity = class_exists($entity)
                ? resolve($entity)
                : $this->get($entity);
        }

        assert($entity instanceof SqlEntity);
        $connection = $this->connection($entity);

        if (! $entity->dropping($connection)) {
            return;
        }

        $grammar = $this->grammar($connection);

        $connection->statement($grammar->compileDrop($entity));
        $entity->dropped($connection);
    }

    /** @param class-string<SqlEntity>|null $type */
    public function createAll(?string $type = null, ?string $connection = null): void
    {
        $this->entities
            ->when($connection, fn ($c) => $c->filter(
                fn ($e) => $e->connectionName() === $connection,
            ))
            ->when($type, fn ($c, $t) => $c->filter(fn ($e) => is_a($e, $t)))
            ->each(fn ($e) => $this->create($e));
    }

    /** @param class-string<SqlEntity>|null $type */
    public function dropAll(?string $type = null, ?string $connection = null): void
    {
        $this->entities
            ->when($connection, fn ($c) => $c->filter(
                fn ($e) => $e->connectionName() === $connection,
            ))
            ->when($type, fn ($c, $t) => $c->filter(fn ($e) => is_a($e, $t)))
            ->each(fn ($e) => $this->drop($e));
    }

    protected function connection(SqlEntity $entity): Connection
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
            'mariadb' => new MariaDbGrammar($connection),
            'mysql'   => new MySqlGrammar($connection),
            'pgsql'   => new PostgresGrammar($connection),
            'sqlite'  => new SQLiteGrammar($connection),
            'sqlsrv'  => new SqlServerGrammar($connection),
            default   => throw new InvalidArgumentException(
                "Unsupported driver [{$driver}].",
            ),
        };
    }
}
