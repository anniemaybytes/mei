<?php

/** @noinspection PhpDeprecationInspection */

declare(strict_types=1);

namespace Mei\PHPStan;

use DI\Annotation\Inject;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
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
    private SimpleAnnotationReader $annotationReader;

    public function __construct()
    {
        /**
         * Deprecated but version 2.0 is not released yet :\
         *
         * @phpstan-ignore-next-line
         */
        AnnotationRegistry::registerLoader('class_exists');

        /**
         * Same as above, no autoloading means we're still bound to legacy SimpleAnnotationReader
         *
         * @phpstan-ignore-next-line
         */
        $this->annotationReader = new SimpleAnnotationReader();
        $this->annotationReader->addNamespace('DI\Annotation');
    }

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

        /** @noinspection PhpParamsInspection */
        $annotation = $this->annotationReader->getPropertyAnnotation($property, Inject::class);

        return $annotation instanceof Inject;
    }

    public function isInitialized(PropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}
