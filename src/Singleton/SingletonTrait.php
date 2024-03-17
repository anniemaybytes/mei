<?php

declare(strict_types=1);

namespace Singleton;

/**
 * Trait SingletonTrait
 *
 * @author Petr Knap <dev@petrknap.cz>
 * @package Singleton
 */
trait SingletonTrait
{
    private static array $instances = [];

    public static function getInstance(): static
    {
        if (!isset(self::$instances[static::class])) {
            self::$instances[static::class] = new static();
        }

        return self::$instances[static::class];
    }

    protected static function hasInstance(): bool
    {
        return isset(self::$instances[static::class]);
    }
}
