<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\functions\AddFunction;
use Workbench\Database\Entities\procedures\InsertLogProcedure;
use Workbench\Database\Entities\triggers\AccountAuditTrigger;
use Workbench\Database\Entities\views\ActiveUserMaterializedView;
use Workbench\Database\Entities\views\UserView;

beforeEach(function () {
    $connection     = Mockery::mock(Connection::class);
    test()->grammar = new PostgresGrammar($connection);
});

describe('compiles function create', function () {
    beforeEach(function () {
        test()->entity = new AddFunction();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE FUNCTION add_function(integer, integer)
            RETURNS INT
            LANGUAGE SQL
            RETURN $1 + $2;
            SQL);
    });

    it('compiles aggregate', function () {
        test()->entity->aggregate = true;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE FUNCTION add_function(integer, integer)
            RETURNS INT
            LANGUAGE SQL
            RETURN $1 + $2;
            SQL);
    });

    it('compiles loadable', function () {
        test()->entity->language   = 'c';
        test()->entity->loadable   = true;
        test()->entity->definition = "'c_add'";

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE FUNCTION add_function(integer, integer)
            RETURNS INT
            LANGUAGE c
            AS 'c_add'
            SQL);
    });

    it('compiles plpgspl', function () {
        test()->entity->language   = 'plpgsql';
        test()->entity->definition = <<<'SQL'
            BEGIN
                RETURN $1 + $2;
            END;
            SQL;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE FUNCTION add_function(integer, integer)
            RETURNS INT
            LANGUAGE plpgsql
            AS $function$
            BEGIN
                RETURN $1 + $2;
            END;
            $function$
            SQL);
    });

    it('compiles characteristics', function () {
        test()->entity->characteristics = [
            'DETERMINISTIC',
            'CONTAINS SQL',
        ];

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE FUNCTION add_function(integer, integer)
            RETURNS INT
            LANGUAGE SQL
            DETERMINISTIC
            CONTAINS SQL
            RETURN $1 + $2;
            SQL);
    });
});

describe('compiles procedure create', function () {
    beforeEach(function () {
        test()->entity = new InsertLogProcedure();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE PROCEDURE insert_log_procedure(message text)
            LANGUAGE SQL
            INSERT INTO logs (message, created_at) VALUES (message, NOW());
            SQL);
    });

    it('compiles plpgsql', function () {
        test()->entity->language   = 'plpgsql';
        test()->entity->definition = <<<'SQL'
            BEGIN
                INSERT INTO logs (message, created_at) VALUES (message, NOW());
            END;
            SQL;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE PROCEDURE insert_log_procedure(message text)
            LANGUAGE plpgsql
            AS $procedure$
            BEGIN
                INSERT INTO logs (message, created_at) VALUES (message, NOW());
            END;
            $procedure$
            SQL);
    });

    it('compiles characteristics', function () {
        test()->entity->characteristics = [
            'SECURITY DEFINER',
        ];

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE PROCEDURE insert_log_procedure(message text)
            LANGUAGE SQL
            SECURITY DEFINER
            INSERT INTO logs (message, created_at) VALUES (message, NOW());
            SQL);
    });
});

describe('compiles trigger create', function () {
    beforeEach(function () {
        test()->entity = new AccountAuditTrigger();

        test()->entity->characteristics = [
            'FOR EACH ROW',
        ];
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE TRIGGER account_audit_trigger
            AFTER UPDATE
            ON accounts
            FOR EACH ROW
            EXECUTE FUNCTION record_account_audit();
            SQL);
    });

    it('handles multiple events', function () {
        test()->entity->events = ['INSERT', 'UPDATE'];

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE TRIGGER account_audit_trigger
            AFTER INSERT OR UPDATE
            ON accounts
            FOR EACH ROW
            EXECUTE FUNCTION record_account_audit();
            SQL);
    });

    it('compiles characteristics', function () {
        test()->entity->characteristics[] = 'WHEN (NEW.id IS NOT NULL)';

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE TRIGGER account_audit_trigger
            AFTER UPDATE
            ON accounts
            FOR EACH ROW
            WHEN (NEW.id IS NOT NULL)
            EXECUTE FUNCTION record_account_audit();
            SQL);
    });

    it('compiles constraint', function () {
        test()->entity->constraint = true;
        $sql                       = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE CONSTRAINT TRIGGER account_audit_trigger
            AFTER UPDATE
            ON accounts
            FOR EACH ROW
            EXECUTE FUNCTION record_account_audit();
            SQL);
    });
});

describe('compiles view create', function () {
    beforeEach(function () {
        test()->entity = new UserView();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE VIEW user_view
            AS SELECT id, name FROM users
            SQL);
    });

    it('compiles recursive', function () {
        test()->entity->recursive = true;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE RECURSIVE VIEW user_view
            AS SELECT id, name FROM users
            SQL);
    });

    it('compiles columns', function (array $columns, string $expected) {
        test()->entity->columns = $columns;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE OR REPLACE VIEW user_view{$expected}
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
            CREATE OR REPLACE VIEW user_view
            AS SELECT id, name FROM users
            {$expected}
            SQL);
    })->with([
        'local'    => ['local', 'WITH LOCAL CHECK OPTION'],
        'cascaded' => ['cascaded', 'WITH CASCADED CHECK OPTION'],
        'true'     => [true, 'WITH CHECK OPTION'],
    ]);
});

describe('compiles materialized view create', function () {
    beforeEach(function () {
        test()->entity = new ActiveUserMaterializedView();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS active_user_materialized_view
            AS SELECT id, name FROM users WHERE active = true
            WITH DATA
            SQL);
    });

    it('compiles with no data', function () {
        test()->entity->withData = false;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS active_user_materialized_view
            AS SELECT id, name FROM users WHERE active = true
            WITH NO DATA
            SQL);
    });

    it('compiles columns', function (array $columns, string $expected) {
        test()->entity->columns = $columns;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE MATERIALIZED VIEW IF NOT EXISTS active_user_materialized_view{$expected}
            AS SELECT id, name FROM users WHERE active = true
            WITH DATA
            SQL);
    })->with([
        'one column'  => [['id'], ' (id)'],
        'two columns' => [['id', 'name'], ' (id, name)'],
    ]);

    it('compiles characteristics', function () {
        test()->entity->characteristics = [
            'TABLESPACE pg_default',
        ];

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS active_user_materialized_view
            TABLESPACE pg_default
            AS SELECT id, name FROM users WHERE active = true
            WITH DATA
            SQL);
    });
});

describe('compiles materialized view refresh data', function () {
    beforeEach(function () {
        test()->entity = new ActiveUserMaterializedView();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileRefreshData(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            REFRESH MATERIALIZED VIEW active_user_materialized_view
            SQL);
    });

    it('compiles concurrently', function () {
        test()->entity->concurrent = true;

        $sql = test()->grammar->compileRefreshData(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            REFRESH MATERIALIZED VIEW CONCURRENTLY active_user_materialized_view
            SQL);
    });
});

it('drops materialized view', function () {
    $entity = new ActiveUserMaterializedView();
    $sql    = test()->grammar->compileDrop($entity);

    expect($sql)->toBe(<<<'SQL'
        DROP MATERIALIZED VIEW IF EXISTS active_user_materialized_view
        SQL);
});

it('drops function', function () {
    $entity = new AddFunction();
    $sql    = test()->grammar->compileDrop($entity);

    expect($sql)->toBe(<<<'SQL'
        DROP FUNCTION IF EXISTS add_function(integer, integer)
        SQL);
});

it('drops trigger', function () {
    $entity = new AccountAuditTrigger();
    $sql    = test()->grammar->compileDrop($entity);

    expect($sql)->toBe(<<<'SQL'
        DROP TRIGGER IF EXISTS account_audit_trigger ON accounts
        SQL);
});
