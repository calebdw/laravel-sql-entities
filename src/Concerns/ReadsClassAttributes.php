<?php

declare(strict_types=1);

namespace CalebDW\SqlEntities\Concerns;

use Exception;
use ReflectionClass;

/**
 * Mirrors Illuminate\Support\Traits\ReadsClassAttributes for Laravel 12.
 */
trait ReadsClassAttributes
{
    /**
     * @param class-string $attributeClass
     */
    protected function getAttributeValue(object $target, string $attributeClass, ?string $property = null, mixed $default = null): mixed
    {
        $reflection = new ReflectionClass($target);

        $defaultProperties = $reflection->getDefaultProperties();

        if (is_string($property)
            && isset($target->{$property})
            && $target->{$property} !== ($defaultProperties[$property] ?? null)
        ) {
            return $target->{$property};
        }

        if ($found = $this->findAttribute($target, $attributeClass)) {
            [$instance, $attributeDeclaringClass] = $found;

            if ($this->propertyOverridesAttribute($target, $reflection, $property, $attributeDeclaringClass)) {
                return $target->{$property};
            }

            return $this->extractAttributeValue($instance);
        }

        return $target->{$property} ?? $default;
    }

    protected function extractAttributeValue(object $instance): mixed
    {
        $properties = get_object_vars($instance);

        return $properties === [] ? true : reset($properties);
    }

    /**
     * @param class-string $attributeClass
     */
    protected function getAttributeInstance(object $target, string $attributeClass): ?object
    {
        return $this->findAttribute($target, $attributeClass)[0] ?? null;
    }

    /**
     * @param class-string $attributeClass
     * @return array{object, ReflectionClass<*>}|null
     */
    private function findAttribute(object $target, string $attributeClass): ?array
    {
        $reflection = new ReflectionClass($target);

        try {
            do {
                $attributes = $reflection->getAttributes($attributeClass);

                if (count($attributes) > 0) {
                    return [$attributes[0]->newInstance(), $reflection];
                }

                foreach ($reflection->getTraits() as $trait) {
                    $attributes = $trait->getAttributes($attributeClass);

                    if (count($attributes) > 0) {
                        return [$attributes[0]->newInstance(), $reflection];
                    }
                }
            } while ($reflection = $reflection->getParentClass());
        } catch (Exception) {
        }

        return null;
    }

    /**
     * @param ReflectionClass<*> $reflection
     * @param ReflectionClass<*> $attributeDeclaringClass
     */
    protected function propertyOverridesAttribute(object $target, ReflectionClass $reflection, ?string $property, ReflectionClass $attributeDeclaringClass): bool
    {
        if (is_null($property) || ! $reflection->hasProperty($property)) {
            return false;
        }

        $property = $reflection->getProperty($property);

        return $property->isPublic()
            && $property->isInitialized($target)
            && $property->getDeclaringClass()->isSubclassOf($attributeDeclaringClass->getName());
    }
}
