<?php

declare(strict_types=1);

namespace Singleton;

/**
 * Class SingletonInterface
 *
 * @author Petr Knap <dev@petrknap.cz>
 * @package Singleton
 */
interface SingletonInterface
{
    public static function getInstance(): static;
}
