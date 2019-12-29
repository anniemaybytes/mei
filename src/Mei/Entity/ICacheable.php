<?php declare(strict_types=1);

namespace Mei\Entity;

use Mei\Cache\IKeyStore;

/**
 *
 * A cacheable is used to store an entity's data and related data
 *
 */
interface ICacheable
{
    /**
     * Get the row used to represent the entity
     *
     * @return string[]
     */
    public function getRow();

    /**
     * Set the row used to represent the entity
     *
     * @param array $row
     *
     * @return self
     */
    public function setRow(array $row);

    /**
     * Get the value stored against $key
     *
     * @param string $key
     */
    public function getLoaded(string $key);

    /**
     * Set the value stored against $key to $value
     *
     * @param string $key
     * @param $value
     *
     * @return self
     */
    public function setLoaded(string $key, $value);

    /**
     * Set the key under which similar entries are stored
     *
     * @param string $key
     *
     * @return self
     */
    public function setKey(string $key);

    /**
     * Set the cache duration
     *
     * @param int $duration
     *
     * @return self
     */
    public function setCacheDuration(int $duration);

    /**
     * Set the unique identifier
     *
     * @param array $id
     *
     * @return self
     */
    public function setId(array $id);

    /**
     * Get the unique identifier
     */
    public function getId();

    /**
     * Store the values into cache
     *
     * @param IKeyStore $cache
     */
    public function save(IKeyStore $cache);

    /**
     * Delete the values from cache
     *
     * @param IKeyStore $cache
     */
    public function delete(IKeyStore $cache);

    /**
     * Get the data that gets stored into cache
     */
    public function getData();

    /**
     * Set the data that gets loaded from cache
     *
     * @param array $cache
     *
     * @return ICacheable
     */
    public function setData(array $cache);
}
