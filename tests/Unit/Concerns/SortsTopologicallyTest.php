<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Concerns\SortsTopologically;

beforeEach(function () {
    test()->harness = new TopologicalSortTestHarness();
});

it('sorts linear dependencies', function () {
    $graph = [
        'c' => ['b'],
        'b' => ['a'],
        'a' => [],
    ];

    $sorted = test()->harness->sortTopologically(
        array_keys($graph),
        fn ($node) => $graph[$node],
    );

    expect($sorted)->toBe(['a', 'b', 'c']);
});

it('sorts complex DAG with branches', function () {
    $graph = [
        'd' => ['b', 'c'],
        'c' => ['a'],
        'b' => ['a'],
        'a' => [],
    ];

    $sorted = test()->harness->sortTopologically(
        array_keys($graph),
        fn (string $node) => $graph[$node],
    );

    expect($sorted)->toBe(['a', 'b', 'c', 'd']);
});

it('handles disconnected graphs', function () {
    $graph = [
        'b' => [],
        'a' => [],
        'c' => ['a'],
    ];

    $sorted = test()->harness->sortTopologically(
        array_keys($graph),
        fn ($node) => $graph[$node],
    );

    expect($sorted)->toContain('a', 'b', 'c');
    expect(array_search('a', $sorted))->toBeLessThan(array_search('c', $sorted));
});

it('throws on circular reference', function () {
    $graph = [
        'a' => ['b'],
        'b' => ['a'],
    ];

    test()->harness->sortTopologically(
        array_keys($graph),
        fn ($node) => $graph[$node],
    );
})->throws('Circular reference detected for [a]');

it('works with object nodes', function () {
    $a = new TestNode('a');
    $b = new TestNode('b', [$a]);
    $c = new TestNode('c', [$b]);
    $d = new TestNode('d', [$b, $c]);

    $sorted = test()->harness->sortTopologically(
        [$d, $c, $b, $a],
        fn ($n) => $n->deps,
        fn ($n) => $n->id,
    );

    expect($sorted)->toBe([$a, $b, $c, $d]);
});

it('detects cycles with object nodes', function () {
    $a       = new TestNode('a');
    $b       = new TestNode('b');
    $a->deps = [$b];
    $b->deps = [$a];

    test()->harness->sortTopologically(
        [$a, $b],
        fn (TestNode $n) => $n->deps,
        fn (TestNode $n) => $n->id,
    );
})->throws('Circular reference detected for [a]');

class TopologicalSortTestHarness
{
    use SortsTopologically;
}

class TestNode
{
    public function __construct(
        public string $id,
        /** @var list<TestNode> */
        public array $deps = [],
    ) {
    }
}
