<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Concerns\DefaultSqlEntityBehaviour;
use CalebDW\SqlEntities\Contracts\SqlEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

beforeEach(function () {
    test()->entity = new FooEntity();
});

it('converts entity to string', function () {
    $expected = 'select "id", "name" from "foo"';

    expect((string) test()->entity)
        ->toBe($expected);

    expect(test()->entity->toString())
        ->toBe($expected);
});

class Foo extends Model
{
    protected $table = 'foo';
}

class FooEntity implements SqlEntity
{
    use DefaultSqlEntityBehaviour;

    public function definition(): Builder|string
    {
        return Foo::query()
            ->select('id', 'name')
            ->toBase();
    }
}
