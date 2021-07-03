<?php

declare(strict_types=1);

namespace Mei\Entity;

use Mei\Cache\IKeyStore;

/**
 * Interface ICacheable
 *
 * @package Mei\Entity
 */
interface ICacheable
{
    /**
     * Get the row used to represent the entity
     */
    public function getRow(): array;

    /**
     * Set the row used to represent the entity
     */
    public function setRow(array $row): ICacheable;

    /**
     * Get the value stored against $key
     */
    public function getLoaded(string $key): mixed;

    /**
     * Set the value stored against $key to $value
     */
    public function setLoaded(string $key, mixed $value): ICacheable;

    /**
     * Set the key under which similar entries are stored
     */
    public function setKey(string $key): ICacheable;

    /**
     * Set the cache duration
     */
    public function setCacheDuration(int $duration): ICacheable;

    /**
     * Set the unique identifier
     */
    public function setId(array $id): ICacheable;

    /**
     * Get the unique identifier
     */
    public function getId();

    /**
     * Store the values into cache
     */
    public function save(IKeyStore $cache): array;

    /**
     * Delete the values from cache
     */
    public function delete(IKeyStore $cache): void;

    /**
     * Get the data that gets stored into cache
     */
    public function getData(): array;

    /**
     * Set the data that gets loaded from cache
     */
    public function setData(array $cache): ICacheable;
}
