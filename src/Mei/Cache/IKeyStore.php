<?php declare(strict_types=1);

namespace Mei\Cache;

/**
 * Simple key store interface
 *
 */
interface IKeyStore
{
    /**
     * Return value stored for key, or false if not existent
     *
     * @param string $key
     */
    public function get(string $key);

    public function getCacheHits();

    public function getExecutionTime();

    /**
     * Set the key to value.
     * Return true on success or false on failure.
     *
     * @param string $key
     * @param $value
     * @param int $time
     */
    public function set(string $key, $value, int $time = 10800);

    /**
     * Delete the value stored against key.
     * Return true on success or false on failure.
     *
     * @param string $key
     */
    public function delete(string $key);

    /**
     * @param string $key
     * @param int $n
     * @param int $initial
     * @param int $expiry
     *
     * @return mixed
     */
    public function increment(string $key, int $n = 1, int $initial = 1, int $expiry = 0);

    /**
     * @param string $key
     * @param int $expiry
     *
     * @return mixed
     */
    public function touch(string $key, int $expiry = 10800);

    /**
     * @param string $key
     * @param array $id
     * @param int $duration
     *
     * @return mixed
     */
    public function getEntityCache(string $key, array $id = [], int $duration = 10800);
}
