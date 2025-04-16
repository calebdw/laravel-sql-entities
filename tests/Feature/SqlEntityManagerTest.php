<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\SqlEntityManager;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Grammar;
use Illuminate\Support\ItemNotFoundException;
use Workbench\Database\Entities\views\FooConnectionUserView;
use Workbench\Database\Entities\views\UserView;

dataset('drivers', [
    'mariadb' => 'mariadb',
    'mysql'   => 'mysql',
    'pgsql'   => 'pgsql',
    'sqlite'  => 'sqlite',
    'sqlsrv'  => 'sqlsrv',
]);

dataset('typesAndConnections', [
    'default args'         => ['types' => null, 'connections' => null, 'times' => 2],
    'single specific type' => ['types' => UserView::class, 'connections' => null, 'times' => 1],
    'single connection'    => ['types' => null, 'connections' => 'default', 'times' => 1],
    'multiple connections' => ['types' => null, 'connections' => ['default', 'foo'], 'times' => 2],
    'single abstract type' => ['types' => View::class, 'connections' => null, 'times' => 2],
    'multiple types'       => ['types' => [UserView::class, FooConnectionUserView::class], 'connections' => null, 'times' => 2],
]);

beforeEach(function () {
    test()->connection = test()->mock(Connection::class);

    $db = test()->mock(DatabaseManager::class)
        ->shouldReceive('getDefaultConnection')->andReturn('default')
        ->shouldReceive('connection')->andReturn(test()->connection)
        ->getMock();
    app()->instance('db', $db);

    test()->manager = resolve(SqlEntityManager::class);
});

afterEach(function () {
    Mockery::close();
});

it('loads the entities')->expect(test()->manager->entities)->not->toBeEmpty();

describe('get', function () {
    it('returns the entity by class', function (string $class) {
        $entity = test()->manager->get($class);

        expect($entity)->toBeInstanceOf($class);
    })->with([
        UserView::class,
        FooConnectionUserView::class,
    ]);

    it('throws an exception for unknown entity', function () {
        $entity = test()->manager->get('unknown');
    })->throws(ItemNotFoundException::class, 'Entity [unknown] not found.');
});

describe('create', function () {
    it('creates an entity', function (string $driver, string|SqlEntity $entity) {
        test()->connection
            ->shouldReceive('getDriverName')->once()->andReturn($driver)
            ->shouldReceive('statement')
            ->once()
            ->withArgs(fn ($sql) => str_contains($sql, 'CREATE'));

        test()->manager->create($entity);
    })->with('drivers')->with([
        'class'  => UserView::class,
        'entity' => new UserView(),
    ]);

    it('can skip creation', function () {
        $entity = new UserView();

        test()->connection
            ->shouldNotReceive('getDriverName')
            ->shouldNotReceive('statement');

        $entity->shouldCreate = false;
        test()->manager->create($entity);
    });
});

describe('drop', function () {
    it('drops an entity', function (string $driver, string|SqlEntity $entity) {
        test()->connection
            ->shouldReceive('getDriverName')->once()->andReturn($driver)
            ->shouldReceive('statement')
            ->once()
            ->withArgs(fn ($sql) => str_contains($sql, 'DROP'));

        test()->manager->drop($entity);
    })->with('drivers')->with([
        'class'  => UserView::class,
        'entity' => new UserView(),
    ]);

    it('can skip dropping', function () {
        $entity = new UserView();

        test()->connection
            ->shouldNotReceive('getDriverName')
            ->shouldNotReceive('statement');

        $entity->shouldDrop = false;
        test()->manager->drop($entity);
    });
});

it('creates entities by type and connection', function (array|string|null $types, array|string|null $connections, int $times) {
    test()->connection
        ->shouldReceive('getDriverName')->times($times)->andReturn('sqlite')
        ->shouldReceive('statement')
        ->times($times)
        ->withArgs(fn ($sql) => str_contains($sql, 'CREATE'));

    test()->manager->createAll($types, $connections);
})->with('typesAndConnections');

it('drops entities by type and connection', function (array|string|null $types, array|string|null $connections, int $times) {
    test()->connection
        ->shouldReceive('getDriverName')->times($times)->andReturn('sqlite')
        ->shouldReceive('statement')
        ->times($times)
        ->withArgs(fn ($sql) => str_contains($sql, 'DROP'));

    test()->manager->dropAll($types, $connections);
})->with('typesAndConnections');

it('executes callbacks without entities', function (
    bool $transactions,
    bool $grammarLoaded,
    array|string|null $types,
    array|string|null $connections,
    int $times,
) {
    $callback = fn () => null;

    $grammar = test()->mock(Grammar::class)
        ->shouldReceive('supportsSchemaTransactions')->andReturn($transactions)
        ->getMock();

    if ($grammarLoaded) {
        test()->connection
            ->shouldReceive('getSchemaGrammar')->andReturn($grammar);
    } else {
        test()->connection
            ->shouldReceive('getSchemaGrammar')->andReturn(null, $grammar)
            ->shouldReceive('useDefaultSchemaGrammar');
    }

    test()->connection
        ->shouldReceive('getDriverName')->times($times * 2)->andReturn('sqlite')
        ->shouldReceive('statement')
        ->times($times)
        ->withArgs(fn ($sql) => str_contains($sql, 'DROP'))
        ->shouldReceive('statement')
        ->times($times)
        ->withArgs(fn ($sql) => str_contains($sql, 'CREATE'));

    if ($transactions) {
        test()->connection
            ->shouldReceive('transaction')
            ->andReturnUsing(fn ($callback) => $callback());
    }

    test()->manager->withoutEntities($callback, $types, $connections);
})->with([
    'transactions'    => true,
    'no transactions' => false,
])->with([
    'grammar loaded'     => true,
    'grammar not loaded' => false,
])->with('typesAndConnections');

it('throws exception for unsupported driver', function () {
    test()->connection
        ->shouldReceive('getDriverName')
        ->andReturn('unknown');

    test()->manager->create(new UserView());
})->throws(InvalidArgumentException::class, 'Unsupported driver [unknown].');
