<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\functions\AddFunction;
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

describe('compiles view create', function () {
    beforeEach(function () {
        test()->entity = new UserView();
    });

    it('compiles successfully', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE VIEW user_view AS
            SELECT id, name FROM users
            SQL);
    });

    it('compiles recursive', function () {
        test()->entity->recursive = true;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE OR REPLACE RECURSIVE VIEW user_view AS
            SELECT id, name FROM users
            SQL);
    });

    it('compiles columns', function (array $columns, string $expected) {
        test()->entity->columns = $columns;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE OR REPLACE VIEW user_view{$expected} AS
            SELECT id, name FROM users
            SQL);
    })->with([
        'one column'  => [['id'], ' (id)'],
        'two columns' => [['id', 'name'], ' (id, name)'],
    ]);

    it('compiles check option', function (string|bool $option, string $expected) {
        test()->entity->checkOption = $option;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE OR REPLACE VIEW user_view AS
            SELECT id, name FROM users
            {$expected}
            SQL);
    })->with([
        'local'    => ['local', 'WITH LOCAL CHECK OPTION'],
        'cascaded' => ['cascaded', 'WITH CASCADED CHECK OPTION'],
        'true'     => [true, 'WITH CHECK OPTION'],
    ]);
});

it('drops function', function () {
    $entity = new AddFunction();
    $sql    = test()->grammar->compileDrop($entity);

    expect($sql)->toBe(<<<'SQL'
        DROP FUNCTION IF EXISTS add_function(integer, integer)
        SQL);
});
