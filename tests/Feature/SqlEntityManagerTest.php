<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\SqlEntityManager;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
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

beforeEach(function () {
    test()->connection = test()->mock(Connection::class);

    $db = test()->mock(DatabaseManager::class)
        ->shouldReceive('connection')
        ->andReturn(test()->connection)
        ->getMock();
    app()->instance('db', $db);

    test()->manager = resolve(SqlEntityManager::class);
});

afterEach(function () {
    Mockery::close();
});

it('loads the entities')
    ->expect(test()->manager->entities)
    ->not->toBeEmpty();

describe('get', function () {
    it('returns the entity by class', function (string $class) {
        $entity = test()->manager->get($class);

        expect($entity)->toBeInstanceOf($class);
    })->with([
        UserView::class,
        FooConnectionUserView::class,
    ]);;

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
            ->withArgs(fn ($sql) => str_contains($sql, 'CREATE VIEW'));

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
            ->withArgs(fn ($sql) => str_contains($sql, 'DROP VIEW'));

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

it('creates entities by type and connection', function () {
    test()->connection
        ->shouldReceive('getDriverName')->once()->andReturn('sqlite')
        ->shouldReceive('statement')
        ->once()
        ->withArgs(fn ($sql) => str_contains($sql, 'CREATE VIEW'));

    test()->manager->createAll(View::class, 'foo');
});

it('drops entities by type and connection', function () {
    test()->connection
        ->shouldReceive('getDriverName')->once()->andReturn('pgsql')
        ->shouldReceive('statement')
        ->once()
        ->withArgs(fn ($sql) => str_contains($sql, 'DROP VIEW'));

    test()->manager->dropAll(View::class, 'foo');
});

it('throws exception for unsupported driver', function () {
    test()->connection
        ->shouldReceive('getDriverName')
        ->andReturn('unknown');

    resolve(SqlEntityManager::class)->create(new UserView());
})->throws(InvalidArgumentException::class, 'Unsupported driver [unknown].');
