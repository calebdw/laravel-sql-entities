<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\SqlEntity;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Grammars\Grammar;
use CalebDW\SqlEntities\Trigger;
use CalebDW\SqlEntities\View;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Workbench\Database\Entities\functions\AddFunction;
use Workbench\Database\Entities\triggers\AccountAuditTrigger;
use Workbench\Database\Entities\views\UserView;

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

it('compiles function drop', function () {
    $sql = test()->grammar->compileDrop(new AddFunction());

    expect($sql)->toBe(<<<'SQL'
        DROP FUNCTION IF EXISTS add_function
        SQL);
});

it('compiles trigger drop', function () {
    $sql = test()->grammar->compileDrop(new AccountAuditTrigger());

    expect($sql)->toBe(<<<'SQL'
        DROP TRIGGER IF EXISTS account_audit_trigger
        SQL);
});

it('compiles view drop', function () {
    $sql = test()->grammar->compileDrop(new UserView());

    expect($sql)->toBe(<<<'SQL'
        DROP VIEW IF EXISTS user_view
        SQL);
});

class TestGrammar extends Grammar
{
    public function compileViewCreate(View $view): string
    {
        return '';
    }

    protected function compileFunctionCreate(Function_ $entity): string
    {
        return '';
    }

    protected function compileTriggerCreate(Trigger $entity): string
    {
        return '';
    }
}

class UnknownSqlEntity implements SqlEntity
{
    use DefaultSqlEntityBehaviour;

    public function definition(): Builder|string
    {
        return '';
    }
}
