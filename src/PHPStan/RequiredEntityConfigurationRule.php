<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\PHPStan;

use CalebDW\SqlEntities\Attributes\Events;
use CalebDW\SqlEntities\Attributes\Returns;
use CalebDW\SqlEntities\Attributes\Table;
use CalebDW\SqlEntities\Attributes\Timing;
use CalebDW\SqlEntities\Function_;
use CalebDW\SqlEntities\Trigger;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use ReflectionClass;

/**
 * @implements Rule<InClassNode>
 */
final class RequiredEntityConfigurationRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if ($class->isAbstract() || $class->isAnonymous()) {
            return [];
        }

        $requirements = match (true) {
            $class->is(Function_::class) => [
                'kind'  => 'Function',
                'base'  => Function_::class,
                'rules' => [
                    'returns' => [Returns::class, 'Returns', 'a return type'],
                ],
            ],
            $class->is(Trigger::class) => [
                'kind'  => 'Trigger',
                'base'  => Trigger::class,
                'rules' => [
                    'table'  => [Table::class, 'Table', 'a table'],
                    'timing' => [Timing::class, 'Timing', 'a timing'],
                    'events' => [Events::class, 'Events', 'events'],
                ],
            ],
            default => null,
        };

        if ($requirements === null) {
            return [];
        }

        $reflection = $class->getNativeReflection();
        $errors     = [];

        foreach ($requirements['rules'] as $property => [$attribute, $attributeName, $label]) {
            if ($this->satisfies($reflection, $requirements['base'], $property, $attribute)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                '%s entity %s must define %s via the #[%s] attribute or the $%s property.',
                $requirements['kind'],
                $class->getName(),
                $label,
                $attributeName,
                $property,
            ))
                ->identifier('sqlEntities.missing' . ucfirst($property))
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * @param ReflectionClass<*> $class
     * @param class-string $base
     * @param class-string $attribute
     */
    private function satisfies(ReflectionClass $class, string $base, string $property, string $attribute): bool
    {
        $current = $class;

        while ($current->getName() !== $base) {
            if ($this->declaresProperty($current, $property) || $this->hasAttribute($current, $attribute)) {
                return true;
            }

            $parent = $current->getParentClass();

            if ($parent === false) {
                return false;
            }

            $current = $parent;
        }

        return false;
    }

    /**
     * @param ReflectionClass<*> $class
     */
    private function declaresProperty(ReflectionClass $class, string $property): bool
    {
        if (! $class->hasProperty($property)) {
            return false;
        }

        $reflection = $class->getProperty($property);

        return $reflection->getDeclaringClass()->getName() === $class->getName()
            && $reflection->hasDefaultValue();
    }

    /**
     * @param ReflectionClass<*> $class
     * @param class-string $attribute
     */
    private function hasAttribute(ReflectionClass $class, string $attribute): bool
    {
        if ($class->getAttributes($attribute) !== []) {
            return true;
        }

        foreach ($class->getTraits() as $trait) {
            if ($trait->getAttributes($attribute) !== []) {
                return true;
            }
        }

        return false;
    }
}
