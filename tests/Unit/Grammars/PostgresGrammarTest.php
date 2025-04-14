<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\PostgresGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\views\UserView;

beforeEach(function () {
    $connection = Mockery::mock(Connection::class);

    test()->grammar = new PostgresGrammar($connection);
    test()->entity  = new UserView();
});

describe('create', function () {
    it('compiles view create', function () {
        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<'SQL'
            CREATE VIEW user_view AS
            SELECT id, name FROM users

            SQL);
    });

    it('compiles recursive', function () {
        test()->entity->recursive = true;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE RECURSIVE VIEW user_view AS
            SELECT id, name FROM users

            SQL);
    });

    it('compiles columns', function (array $columns, string $expected) {
        test()->entity->columns = $columns;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE VIEW user_view{$expected} AS
            SELECT id, name FROM users

            SQL);
    })->with([
        'one column' => [['id'], ' (id)'],
        'two columns' => [['id', 'name'], ' (id, name)'],
    ]);

    it('compiles check option', function (string|bool $option, string $expected) {
        test()->entity->checkOption = $option;

        $sql = test()->grammar->compileCreate(test()->entity);

        expect($sql)->toBe(<<<SQL
            CREATE VIEW user_view AS
            SELECT id, name FROM users
            {$expected}
            SQL);
    })->with([
        'local'    => ['local', 'WITH LOCAL CHECK OPTION'],
        'cascaded' => ['cascaded', 'WITH CASCADED CHECK OPTION'],
        'true'     => [true, 'WITH CHECK OPTION'],
    ]);
});

it('compiles view drop', function () {
    $sql = test()->grammar->compileDrop(test()->entity);

    expect($sql)->toBe(<<<'SQL'
        DROP VIEW IF EXISTS user_view CASCADE
        SQL);
});
