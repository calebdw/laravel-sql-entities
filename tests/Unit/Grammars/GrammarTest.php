<?php

declare(strict_types=1);

use CalebDW\SqlEntities\SqlEntity;
use CalebDW\SqlEntities\View;
use CalebDW\SqlEntities\Grammars\Grammar;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

beforeEach(function () {
    $connection = Mockery::mock(Connection::class);

    test()->grammar = new TestGrammar($connection);
});

it('throws exception when creating unknown entity', function () {
    $entity = new UnknownSqlEntity();

    test()->grammar->compileCreate($entity);
})->throws(InvalidArgumentException::class, 'Unsupported entity [UnknownSqlEntity].');

it('throws exception when dropping unknown entity', function () {
    $entity = new UnknownSqlEntity();

    test()->grammar->compileDrop($entity);
})->throws(InvalidArgumentException::class, 'Unsupported entity [UnknownSqlEntity].');

class TestGrammar extends Grammar
{
    public function compileViewCreate(View $view): string
    {
        return '';
    }

    public function compileViewDrop(View $view): string
    {
        return '';
    }
}

class UnknownSqlEntity extends SqlEntity
{
    public function definition(): Builder|string
    {
        return '';
    }
}
