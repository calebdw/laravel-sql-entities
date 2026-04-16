<?php

declare(strict_types=1);

use Illuminate\Database\Query\Builder;
use Workbench\Database\Entities\views\UserView;

it('returns a query builder for the view', function () {
    $builder = UserView::query();

    expect($builder)
        ->toBeInstanceOf(Builder::class)
        ->and($builder->from)->toBe('user_view');
});

it('returns a query builder with an alias', function () {
    $builder = UserView::query('uv');

    expect($builder)
        ->toBeInstanceOf(Builder::class)
        ->and($builder->from)->toBe('user_view as uv');
});
