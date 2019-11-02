<?php

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
    public function get($key);

    public function getCacheHits();

    public function getExecutionTime();

    /**
     * Set the key to value.
     * Return true on success or false on failure.
     *
     * @param $key
     * @param $value
     * @param int|number $time
     */
    public function set($key, $value, $time = 3600);

    /**
     * Delete the value stored against key.
     * Return true on success or false on failure.
     *
     * @param $key
     */
    public function delete($key);

    public function increment($key, $n = 1, $initial = 1, $expiry = 0);

    public function touch($key, $expiry = 3600);

    public function setClearOnGet($val);

    public function getStats();

    public function flush();

    public function getEntityCache($key, $id = [], $duration = 3600);
}
