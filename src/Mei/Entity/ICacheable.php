<?php
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
     * @return array of string
     */
    public function getRow();

    /**
     * Set the row used to represent the entity
     * @param array $row
     *
     * @return ICacheable
     */
    public function setRow($row);

    /**
     * Get the value stored against $key
     * @param string $key
     */
    public function getLoaded($key);

    /**
     * Set the value stored against $key to $value
     * @param string $key
     * @param $value
     *
     * @return ICacheable
     */
    public function setLoaded($key, $value);

    /**
     * Set the key under which similar entries are stored
     * @param string $key
     *
     * @return ICacheable
     */
    public function setKey($key);

    /**
     * Set the cache duration
     * @param string $duration
     *
     * @return ICacheable
     */
    public function setCacheDuration($duration);

    /**
     * Set the unique identifier
     * @param $id
     *
     * @return ICacheable
     */
    public function setId($id);

    /**
     * Get the unique identifier
     */
    public function getId();

    /**
     * Store the values into cache
     * @param IKeyStore $cache
     */
    public function save(IKeyStore $cache);

    /**
     * Delete the values from cache
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
     * @param $cache
     * @return ICacheable
     */
    public function setData($cache);
}
