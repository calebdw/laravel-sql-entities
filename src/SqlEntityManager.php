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
use Illuminate\Support\ItemNotFoundException;
use InvalidArgumentException;

class SqlEntityManager
{
    /** @var Collection<class-string<SqlEntity>, SqlEntity> */
    public readonly Collection $entities;

    /**
     * The active grammar instances.
     *
     * @var array<string, Grammar>
     */
    protected array $grammars = [];

    /** @param Collection<int, SqlEntity> $entities */
    public function __construct(
        Collection $entities,
        protected DatabaseManager $db,
    ) {
        $this->entities = $entities
            ->keyBy(fn ($entity) => $entity::class);
    }

    /**
     * Get the entity by class.
     *
     * @param class-string<SqlEntity> $name
     * @throws ItemNotFoundException
     */
    public function get(string $name): SqlEntity
    {
        $entity = $this->entities->get($name);

        if ($entity === null) {
            throw new ItemNotFoundException("Entity [{$name}] not found.");
        }

        return $entity;
    }

    /**
     * Create an entity.
     *
     * @param class-string<SqlEntity>|SqlEntity $entity
     * @throws ItemNotFoundException
     */
    public function create(SqlEntity|string $entity): void
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

    /**
     * Drop an entity.
     *
     * @param class-string<SqlEntity>|SqlEntity $entity
     * @throws ItemNotFoundException
     */
    public function drop(SqlEntity|string $entity): void
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
            default   => throw new InvalidArgumentException("Unsupported driver [{$driver}]."),
        };
    }
}
