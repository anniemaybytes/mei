<?php declare(strict_types=1);

namespace Mei\Cache;

use Mei\Entity\ICacheable;

/**
 * Simple key store interface
 *
 * @package Mei\Cache
 */
interface IKeyStore
{
    /**
     * Return value stored for key, or false if not existent
     *
     * @param string $key
     */
    public function doGet(string $key);

    /**
     * @return array
     */
    public function getCacheHits(): array;

    /**
     * @return int
     */
    public function getExecutionTime(): int;

    /**
     * Set the key to value.
     * Return true on success or false on failure.
     *
     * @param string $key
     * @param $value
     * @param int $time
     *
     * @return bool
     */
    public function doSet(string $key, $value, int $time = 10800);

    /**
     * Delete the value stored against key.
     * Return true on success or false on failure.
     *
     * @param string $key
     *
     * @return bool
     */
    public function doDelete(string $key);

    /**
     * @param string $key
     * @param int $n
     * @param int $initial
     * @param int $expiry
     *
     * @return int|false
     */
    public function doIncrement(string $key, int $n = 1, int $initial = 1, int $expiry = 0);

    /**
     * @param string $key
     * @param int $expiry
     *
     * @return bool
     */
    public function doTouch(string $key, int $expiry = 10800): bool;

    /**
     * @param bool $val
     */
    public function setClearOnGet(bool $val);

    /**
     * @param string $key
     * @param array $id
     * @param int $duration
     *
     * @return ICacheable
     */
    public function getEntityCache(
        string $key,
        array $id = [],
        int $duration = 10800
    ): ICacheable;

    public function doFlush();

    /**
     * @return array
     */
    public function getAllKeys(): array;

    /**
     * @return array
     */
    public function getStats(): array;
}
