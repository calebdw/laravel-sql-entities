<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Entities\Entity;
use CalebDW\SqlEntities\Entities\View;
use CalebDW\SqlEntities\Grammars\Grammar;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

beforeEach(function () {
    $connection = Mockery::mock(Connection::class);

    test()->grammar = new TestGrammar($connection);
});

it('throws exception when creating unknown entity', function () {
    $entity = new UnknownEntity();

    test()->grammar->compileCreate($entity);
})->throws(InvalidArgumentException::class, 'Unsupported entity [UnknownEntity].');

it('throws exception when dropping unknown entity', function () {
    $entity = new UnknownEntity();

    test()->grammar->compileDrop($entity);
})->throws(InvalidArgumentException::class, 'Unsupported entity [UnknownEntity].');

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

class UnknownEntity extends Entity
{
    public function name(): string
    {
        return 'unknown_entity';
    }

    public function definition(): Builder|string
    {
        return '';
    }
}
