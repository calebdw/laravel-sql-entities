<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\SqlServerGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\functions\AddFunction;
use Workbench\Database\Entities\triggers\AccountAuditTrigger;
use Workbench\Database\Entities\views\UserView;

beforeEach(function () {
    $connection     = Mockery::mock(Connection::class);
    test()->grammar = new SqlServerGrammar($connection);
});

describe('compiles function create', function () {
    beforeEach(function () {
        test()->entity = new AddFunction();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR ALTER FUNCTION add_function (integer, integer)
            RETURNS INT
            RETURN $1 + $2;
            SQL);
    });

    it('compiles aggregate', function () {
        test()->entity->aggregate = true;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR ALTER FUNCTION add_function (integer, integer)
            RETURNS INT
            RETURN $1 + $2;
            SQL);
    });

    it('compiles loadable', function () {
        test()->entity->loadable   = true;
        test()->entity->definition = "'c_add'";

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR ALTER FUNCTION add_function (integer, integer)
            RETURNS INT
            EXTERNAL NAME 'c_add'
            SQL);
    });

    it('compiles characteristics', function () {
        test()->entity->characteristics = [
            'DETERMINISTIC',
            'CONTAINS SQL',
        ];

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR ALTER FUNCTION add_function (integer, integer)
            RETURNS INT
            DETERMINISTIC
            CONTAINS SQL
            RETURN $1 + $2;
            SQL);
    });
});

describe('compiles trigger create', function () {
    beforeEach(function () {
        test()->entity = new AccountAuditTrigger();

        test()->entity->definition = <<<'SQL'
            AS BEGIN
                INSERT INTO account_audits (account_id, old_balance, new_balance)
                VALUES (NEW.id, OLD.balance, NEW.balance);
            END;
            SQL;
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR ALTER TRIGGER account_audit_trigger
            ON accounts
            AFTER UPDATE
            AS BEGIN
                INSERT INTO account_audits (account_id, old_balance, new_balance)
                VALUES (NEW.id, OLD.balance, NEW.balance);
            END;
            SQL);
    });

    it('compiles characteristics', function () {
        test()->entity->characteristics[] = 'WITH APPEND';

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR ALTER TRIGGER account_audit_trigger
            ON accounts
            AFTER UPDATE
            WITH APPEND
            AS BEGIN
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
            CREATE OR ALTER VIEW user_view
            AS SELECT id, name FROM users
            SQL);
    });

    it('compiles columns', function (array $columns, string $expected) {
        test()->entity->columns = $columns;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE OR ALTER VIEW user_view{$expected}
            AS SELECT id, name FROM users
            SQL);
    })->with([
        'one column'  => [['id'], ' (id)'],
        'two columns' => [['id', 'name'], ' (id, name)'],
    ]);

    it('compiles check option', function (string|bool $option, string $expected) {
        test()->entity->checkOption = $option;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE OR ALTER VIEW user_view
            AS SELECT id, name FROM users
            {$expected}
            SQL);
    })->with([
        'true' => [true, 'WITH CHECK OPTION'],
    ]);
});
