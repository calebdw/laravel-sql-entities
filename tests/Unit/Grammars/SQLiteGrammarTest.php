<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\SQLiteGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\views\UserView;

beforeEach(function () {
    $connection = Mockery::mock(Connection::class);

    test()->grammar = new SQLiteGrammar($connection);
});

it('compiles view drop', function () {
    $sql = test()->grammar->compileCreate(new UserView());

    expect($sql)->toBe(<<<'SQL'
        CREATE VIEW users_view AS
        SELECT id, name FROM users
        SQL);
});

it('compiles view create', function () {
    $sql = test()->grammar->compileDrop(new UserView());

    expect($sql)->toBe(<<<'SQL'
        DROP VIEW IF EXISTS users_view
        SQL);
});
