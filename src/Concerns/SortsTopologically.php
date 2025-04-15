<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Concerns;

use RuntimeException;

trait SortsTopologically
{
    /**
     * Sorts the given nodes topologically.
     *
     * @template TNode
     *
     * @param iterable<TNode> $nodes The nodes to sort.
     * @param (callable(TNode): iterable<TNode>) $edges A function that returns the edges of a node.
     * @param (callable(TNode): array-key)|null $getKey A function that returns the key of a node.
     * @return list<TNode> The sorted nodes.
     * @throws RuntimeException if a circular reference is detected.
     */
    public function sortTopologically(
        iterable $nodes,
        callable $edges,
        ?callable $getKey = null,
    ): array {
        $sorted  = [];
        $visited = [];
        $getKey ??= fn ($node) => $node;

        foreach ($nodes as $node) {
            $this->visit($node, $edges, $sorted, $visited, $getKey);
        }

        return $sorted;
    }

    /**
     * Visits a node and its dependencies.
     *
     * @template TNode
     *
     * @param TNode $node The node to visit.
     * @param (callable(TNode): iterable<TNode>) $edges A function that returns the edges of a node.
     * @param list<TNode> $sorted The sorted nodes.
     * @param-out list<TNode> $sorted The sorted nodes.
     * @param array<array-key, bool> $visited The visited nodes.
     * @param (callable(TNode): array-key) $getKey A function that returns the key of a node.
     * @throws RuntimeException if a circular reference is detected.
     */
    protected function visit(
        mixed $node,
        callable $edges,
        array &$sorted,
        array &$visited,
        callable $getKey,
    ): void {
        $key = $getKey($node);

        if (isset($visited[$key])) {
            throw_if($visited[$key] === false, "Circular reference detected for [{$key}].");

            return;
        }

        $visited[$key] = false;

        foreach ($edges($node) as $edge) {
            $this->visit($edge, $edges, $sorted, $visited, $getKey);
        }

        $visited[$key] = true;
        $sorted[]      = $node;
    }
}
