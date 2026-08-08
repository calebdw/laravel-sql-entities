<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Support\Frequency;
use Workbench\Database\Entities\views\ActiveUserMaterializedView;

it('returns null schedule by default', function () {
    $entity = new ActiveUserMaterializedView();

    expect($entity->schedule(new Frequency()))->toBeNull();
});

it('returns a query builder for the materialized view', function () {
    $query = ActiveUserMaterializedView::query();

    expect($query)->toBeInstanceOf(Illuminate\Database\Query\Builder::class);
    expect($query->from)->toBe('active_user_materialized_view');
});

it('returns a query builder with an alias', function () {
    $query = ActiveUserMaterializedView::query('mv');

    expect($query)->toBeInstanceOf(Illuminate\Database\Query\Builder::class);
});
