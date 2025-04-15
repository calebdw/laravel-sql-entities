<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Grammars\MariaDbGrammar;
use Illuminate\Database\Connection;
use Workbench\Database\Entities\views\UserView;

beforeEach(function () {
    $connection = Mockery::mock(Connection::class);

    test()->grammar = new MariaDbGrammar($connection);
});

it('compiles view drop', function () {
    $sql = test()->grammar->compileCreate(new UserView());

    expect($sql)->toBe(<<<'SQL'
        CREATE VIEW user_view AS
        SELECT id, name FROM users
        SQL);
});

it('compiles view create', function () {
    $sql = test()->grammar->compileDrop(new UserView());

    expect($sql)->toBe(<<<'SQL'
        DROP VIEW IF EXISTS user_view
        SQL);
});
