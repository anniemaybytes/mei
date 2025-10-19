<?php

declare(strict_types=1);

namespace Mei\PHPStan;

use DI\Attribute\Inject;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

/**
 * Class PropertiesExtension
 *
 * @package Mei\PHPStan
 */
class PropertiesExtension implements ReadWritePropertiesExtension
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        $declaringClass = $property->getDeclaringClass();
        $className = $declaringClass->getName();

        $reflectionClass = $this->reflectionProvider->getClass($className);
        try {
            $property = $reflectionClass->getNativeProperty($propertyName);
        } catch (MissingPropertyFromReflectionException) {
            return false;
        }
        $attributes = $property->getNativeReflection()->getAttributes(Inject::class);

        return count($attributes) >= 1;
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
