<?php

declare(strict_types=1);

namespace Mei\PHPStan;

use DI\Attribute\Inject;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use ReflectionClass;
use ReflectionException;

/**
 * Class PropertiesExtension
 *
 * @package Mei\PHPStan
 */
class PropertiesExtension implements ReadWritePropertiesExtension
{
    public function isAlwaysRead(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    /** @throws ReflectionException */
    public function isAlwaysWritten(PropertyReflection $property, string $propertyName): bool
    {
        $declaringClass = $property->getDeclaringClass();
        $className = $declaringClass->getName();

        $reflectionClass = new ReflectionClass($className);
        $property = $reflectionClass->getProperty($propertyName);
        $attributes = $property->getAttributes(Inject::class);

        return sizeof($attributes) >= 1;
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
