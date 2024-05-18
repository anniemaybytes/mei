<?php

declare(strict_types=1);

namespace Mei\Config;

use ArrayAccess;
use ClosedGeneratorException;
use RuntimeException;

/**
 * Class Config
 *
 * @package Mei\Config
 */
final class Config implements ArrayAccess
{
    private const array FILES = [
        BASE_ROOT . '/config/private.ini'
    ];

    private array $config;

    public function __construct(array $files = [])
    {
        if (!count($files)) {
            $files = self::FILES;
        }

        $mergedArray = Defaults::CONFIG;
        foreach ($files as $file) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $mergedArray = array_merge($mergedArray, Loader::load($file));
        }
        $this->config = Loader::parse($mergedArray);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!isset($this->config[$offset])) {
            throw new RuntimeException("$offset is not a valid configuration property");
        }

        if ($this->config[$offset] === UndefinedValue::class) {
            throw new RuntimeException("Config property $offset is uninitialized");
        }

        if (
            isset(AllowedValues::CONFIG[$offset]) &&
            !in_array($this->config[$offset], AllowedValues::CONFIG[$offset], true)
        ) {
            throw new RuntimeException(
                "Config property '$offset' has wrong value " . var_export($this->config[$offset], true) .
                ' - must be one of: ' .
                implode(', ', array_map(static fn($imp) => var_export($imp, true), AllowedValues::CONFIG[$offset]))
            );
        }

        return $this->config[$offset];
    }

    /** @throws ClosedGeneratorException */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new ClosedGeneratorException('Unable to modify configuration during runtime');
    }

    /** @throws ClosedGeneratorException */
    public function offsetUnset(mixed $offset): void
    {
        throw new ClosedGeneratorException('Unable to modify configuration during runtime');
    }
}
