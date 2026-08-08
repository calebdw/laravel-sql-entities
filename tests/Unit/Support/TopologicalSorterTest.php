<?php

declare(strict_types=1);

use CalebDW\SqlEntities\Support\TopologicalSorter;

beforeEach(function (): void {
    test()->service = new TopologicalSorter();
});

it('sorts linear dependencies', function (): void {
    $graph = [
        'c' => ['b'],
        'b' => ['a'],
        'a' => [],
    ];

    $sorted = test()->service->sort(
        array_keys($graph),
        fn ($node) => $graph[$node],
    );

    expect($sorted)->toBe(['a', 'b', 'c']);
});

it('sorts complex DAG with branches', function (): void {
    $graph = [
        'd' => ['b', 'c'],
        'c' => ['a'],
        'b' => ['a'],
        'a' => [],
    ];

    $sorted = test()->service->sort(
        array_keys($graph),
        fn (string $node) => $graph[$node],
    );

    expect($sorted)->toBe(['a', 'b', 'c', 'd']);
});

it('handles disconnected graphs', function (): void {
    $graph = [
        'b' => [],
        'a' => [],
        'c' => ['a'],
    ];

    $sorted = test()->service->sort(
        array_keys($graph),
        fn ($node) => $graph[$node],
    );

    expect($sorted)->toContain('a', 'b', 'c');
    expect(array_search('a', $sorted, true))->toBeLessThan(array_search('c', $sorted, true));
});

it('throws on circular reference', function (): void {
    $graph = [
        'a' => ['b'],
        'b' => ['a'],
    ];

    test()->service->sort(
        array_keys($graph),
        fn ($node) => $graph[$node],
    );
})->throws('Circular reference detected for [a]');

it('works with object nodes', function (): void {
    $a = new TestNode('a');
    $b = new TestNode('b', [$a]);
    $c = new TestNode('c', [$b]);
    $d = new TestNode('d', [$b, $c]);

    $sorted = test()->service->sort(
        [$d, $c, $b, $a],
        fn ($n) => $n->deps,
        fn ($n) => $n->id,
    );

    expect($sorted)->toBe([$a, $b, $c, $d]);
});

it('detects cycles with object nodes', function (): void {
    $a       = new TestNode('a');
    $b       = new TestNode('b');
    $a->deps = [$b];
    $b->deps = [$a];

    test()->service->sort(
        [$a, $b],
        fn (TestNode $n) => $n->deps,
        fn (TestNode $n) => $n->id,
    );
})->throws('Circular reference detected for [a]');

class TestNode
{
    public function __construct(
        public string $id,
        /** @var list<TestNode> */
        public array $deps = [],
    ) {
    }
}
