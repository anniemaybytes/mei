<?php declare(strict_types=1);

namespace Mei;

use BadMethodCallException;

/**
 * Class Singleton
 *
 * @package Mei
 */
class Singleton
{
    /**
     * @var object[]
     */
    private static $instances = [];

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new BadMethodCallException('Cannot unserialize singleton');
    }

    /**
     * @return object
     */
    public static function getInstance(): object
    {
        $cls = static::class; // late-static-bound class name
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }
        return self::$instances[$cls];
    }
}
