<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities;

use CalebDW\SqlEntities\Concerns\SortsTopologically;
use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Grammars\Grammar;
use CalebDW\SqlEntities\Grammars\MariaDbGrammar;
use CalebDW\SqlEntities\Grammars\MySqlGrammar;
use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use CalebDW\SqlEntities\Grammars\SQLiteGrammar;
use CalebDW\SqlEntities\Grammars\SqlServerGrammar;
use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use InvalidArgumentException;

/**
 * @phpstan-type TEntities Collection<class-string<SqlEntity>, SqlEntity>
 */
class SqlEntityManager
{
    use SortsTopologically;

    /**
     * The active connection instances.
     *
     * @var array<string, Connection>
     */
    protected array $connections = [];

    /** @var TEntities */
    public Collection $entities;

    /**
     * The active grammar instances.
     *
     * @var array<string, Grammar>
     */
    protected array $grammars = [];

    /**
     * The states of the entities.
     *
     * @var array<class-string<SqlEntity>, 'created'|'dropped'>
     */
    protected array $states = [];

    /** @param Collection<array-key, SqlEntity> $entities */
    public function __construct(
        Collection $entities,
        protected DatabaseManager $db,
    ) {
        $this->entities = $entities->keyBy(fn ($e) => $e::class);

        $sorted = $this->sortTopologically(
            $this->entities,
            fn ($e) => collect($e->dependencies())->map($this->get(...)),
            fn ($e) => $e::class,
        );

        $this->entities = collect($sorted)->keyBy(fn ($e) => $e::class);
    }

    /**
     * Get the entity by class.
     *
     * @param class-string<SqlEntity> $class
     * @throws ItemNotFoundException
     */
    public function get(string $class): SqlEntity
    {
        $entity = $this->entities->get($class);

        if ($entity === null) {
            throw new ItemNotFoundException("Entity [{$class}] not found.");
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

        if (($this->states[$entity::class] ?? null) === 'created') {
            return;
        }

        $connection = $this->connection($entity->connectionName());

        if (! $entity->creating($connection)) {
            return;
        }

        foreach ($entity->dependencies() as $dependency) {
            $this->create($dependency);
        }

        $grammar = $this->grammar($connection);

        $connection->statement($grammar->compileCreate($entity));
        $entity->created($connection);
        $this->states[$entity::class] = 'created';
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

        if (($this->states[$entity::class] ?? null) === 'dropped') {
            return;
        }

        $connection = $this->connection($entity->connectionName());

        if (! $entity->dropping($connection)) {
            return;
        }

        $grammar = $this->grammar($connection);

        $connection->statement($grammar->compileDrop($entity));
        $entity->dropped($connection);
        $this->states[$entity::class] = 'dropped';
    }

    /**
     * Create all entities.
     *
     * @param array<int, class-string<SqlEntity>>|class-string<SqlEntity>|null $types
     * @param array<int, string>|string|null $connections
     */
    public function createAll(
        array|string|null $types = null,
        array|string|null $connections = null,
    ): void {
        $this->entities
            ->when($connections, $this->filterByConnections(...))
            ->when($types, $this->filterByTypes(...))
            ->each($this->create(...));
    }

    /**
     * Drop all entities.
     *
     * @param array<int, class-string<SqlEntity>>|class-string<SqlEntity>|null $types
     * @param array<int, string>|string|null $connections
     */
    public function dropAll(
        array|string|null $types = null,
        array|string|null $connections = null,
    ): void {
        $this->entities
            ->reverse()
            ->when($connections, $this->filterByConnections(...))
            ->when($types, $this->filterByTypes(...))
            ->each($this->drop(...));
    }

    /**
     * Execute a callback (in a transaction, if supported) without the specified entities.
     *
     * @param Closure(Connection): mixed $callback
     * @param array<int, class-string<SqlEntity>>|class-string<SqlEntity>|null $types
     * @param array<int, string>|string|null $connections
     */
    public function withoutEntities(
        Closure $callback,
        array|string|null $types = null,
        array|string|null $connections = null,
    ): void {
        $defaultConnection = $this->db->getDefaultConnection();

        $groups = $this->entities
            ->when($connections, $this->filterByConnections(...))
            ->when($types, $this->filterByTypes(...))
            ->groupBy(fn ($e) => $e->connectionName() ?? $defaultConnection);

        foreach ($groups as $connectionName => $entities) {
            $connection = $this->connection($connectionName);

            $execute = function () use ($connection, $entities, $callback) {
                $entities
                    ->reverse()
                    ->each($this->drop(...));

                $callback($connection);

                $entities->each($this->create(...));
            };

            /** @phpstan-ignore identical.alwaysFalse (bad phpdocs) */
            if ($connection->getSchemaGrammar() === null) {
                $connection->useDefaultSchemaGrammar();
            }

            $connection->getSchemaGrammar()->supportsSchemaTransactions()
                ? $connection->transaction($execute)
                : $execute();
        }
    }

    /** Flush the entity manager instance. */
    public function flush(): void
    {
        $this->connections = [];
        $this->grammars    = [];
        $this->states      = [];
    }

    /**
     * Filter entities by connection.
     *
     * @param TEntities $entities
     * @param array<int, class-string<SqlEntity>>|class-string<SqlEntity> $types
     * @return TEntities
     */
    protected function filterByTypes(
        Collection $entities,
        array|string $types,
    ): Collection {
        return $entities->filter(function ($entity) use ($types) {
            foreach (Arr::wrap($types) as $type) {
                if (is_a($entity, $type, allow_string: false)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Filter entities by connection.
     *
     * @param TEntities $entities
     * @param array<int, string>|string $connections
     * @return TEntities
     */
    protected function filterByConnections(
        Collection $entities,
        array|string $connections,
    ): Collection {
        $default = $this->db->getDefaultConnection();

        return $entities->filter(function ($entity) use ($connections, $default) {
            $name = $entity->connectionName() ?? $default;

            return in_array($name, Arr::wrap($connections), strict: true);
        });
    }

    protected function connection(?string $name): Connection
    {
        $name ??= $this->db->getDefaultConnection();

        return $this->connections[$name] ??= $this->db->connection($name);
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
