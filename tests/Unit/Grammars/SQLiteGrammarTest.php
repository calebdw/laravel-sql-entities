<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\SQLiteGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\functions\AddFunction;
use Workbench\Database\Entities\triggers\AccountAuditTrigger;
use Workbench\Database\Entities\views\UserView;

beforeEach(function () {
    $connection     = Mockery::mock(Connection::class);
    test()->grammar = new SQLiteGrammar($connection);
});

describe('compiles create function', function () {
    it('throws exception', function () {
        test()->grammar->compileCreate(new AddFunction());
    })->throws('SQLite does not support user-defined functions.');
});

describe('compiles trigger create', function () {
    beforeEach(function () {
        test()->entity = new AccountAuditTrigger();

        test()->entity->characteristics = [
            'FOR EACH ROW',
        ];

        test()->entity->definition = <<<'SQL'
            BEGIN
                INSERT INTO account_audits (account_id, old_balance, new_balance)
                VALUES (NEW.id, OLD.balance, NEW.balance);
            END;
            SQL;
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE TRIGGER IF NOT EXISTS account_audit_trigger
            AFTER UPDATE
            ON accounts
            FOR EACH ROW
            BEGIN
                INSERT INTO account_audits (account_id, old_balance, new_balance)
                VALUES (NEW.id, OLD.balance, NEW.balance);
            END;
            SQL);
    });
});
describe('compiles create view', function () {
    beforeEach(function () {
        test()->entity = new UserView();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE VIEW IF NOT EXISTS user_view AS
            SELECT id, name FROM users
            SQL);
    });

    it('compiles columns', function (array $columns, string $expected) {
        test()->entity->columns = $columns;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE VIEW IF NOT EXISTS user_view{$expected} AS
            SELECT id, name FROM users
            SQL);
    })->with([
        'one column'  => [['id'], ' (id)'],
        'two columns' => [['id', 'name'], ' (id, name)'],
    ]);
});
