<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\SqlEntityManager;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Workbench\Database\Entities\views\FooConnectionUserView;
use Workbench\Database\Entities\views\UserView;

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
    it('returns the entity by name', function () {
        $entity = test()->manager->get('user_view');

        expect($entity)->toBeInstanceOf(UserView::class);
    });

    it('returns the entity by name and connection', function () {
        $entity = test()->manager->get('user_view', 'foo');

        expect($entity)->toBeInstanceOf(FooConnectionUserView::class);
    });

    it('throws an exception for unknown entity', function () {
        $entity = test()->manager->get('unknown');
    })->throws(InvalidArgumentException::class, 'Entity [unknown] not found.');
});

describe('create', function () {
    it('creates an entity', function (string|SqlEntity $entity) {
        test()->connection
            ->shouldReceive('getDriverName')->once()->andReturn('sqlite')
            ->shouldReceive('statement')
            ->once()
            ->withArgs(fn ($sql) => str_contains($sql, 'CREATE VIEW'));

        test()->manager->create($entity);
    })->with([
        'name'   => 'user_view',
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
    it('drops an entity', function (string|SqlEntity $entity) {
        test()->connection
            ->shouldReceive('getDriverName')->once()->andReturn('pgsql')
            ->shouldReceive('statement')
            ->once()
            ->withArgs(fn ($sql) => str_contains($sql, 'DROP VIEW'));

        test()->manager->drop($entity);
    })->with([
        'name'   => 'user_view',
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
