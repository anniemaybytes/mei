<?php

declare(strict_types=1);

namespace Mei\Entity;

/**
 * An entity is an object that can be persisted
 */
interface IEntity
{
    /**
     * Get an array of entity attributes as key and their default value as value.
     * If an attribute does not have a default value, it should not be included
     * in the array.
     *
     * @return string[]
     */
    public static function getDefaults(): array;

    /**
     * Get an array of entity attributes as key and their type as value
     *
     * @return string[]
     */
    public static function getAttributes(): array;

    /**
     * Get an array of the attributes used to uniquely identify the entity.
     * This array can also be empty.
     *
     * @return string[]
     */
    public static function getIdAttributes(): array;

    /**
     * Get array of id, key being attribute name and value being the value.
     * Return null if the id is not set, or only partially set in the case of
     * multiple ids. If there are no id attributes, should return an empty array
     *
     * @return array|null
     */
    public function getId(): ?array;

    /**
     * @param ICacheable $cacheable
     *
     * @return self
     */
    public function setCacheable(ICacheable $cacheable): IEntity;

    /**
     * @return ICacheable
     */
    public function getCacheable(): ICacheable;

    /**
     * Get an array of the attributes whose values have been changed.
     * The attribute is the key and the new value is the value.
     * The value should be a string representation usable in SQL.
     *
     * @return string[]
     */
    public function getChangedValues(): array;

    /**
     * Get an array of the entity values.
     * The attribute is the key and the value is the value.
     * If an attribute is not set, it should not be part of the array.
     * The value should be a string representation usable in SQL.
     *
     * @return string[]
     */
    public function getValues(): array;

    /**
     * Get an array of the entity values.
     * The attribute is the key and the value is the value.
     * If an attribute is not set, it should not be part of the array.
     * The value should be a php value.
     *
     * @return array
     */
    public function getMappedValues(): array;

    /**
     * @param bool $new
     *
     * @return self
     */
    public function setNew(bool $new): IEntity;

    /**
     * Check if the entity needs to be persisted (it is new)
     *
     * @return bool true if the entity is new and hasn't yet been saved
     */
    public function isNew(): bool;

    /**
     * Check if the entity data needs to be persisted (it has changed)
     *
     * @return bool true if the entity data has changed
     */
    public function hasChanged(): bool;

    /**
     * Reset the entity state, for example after the entity is saved
     *
     * @param ICacheable $cache
     *
     * @return self
     */
    public function reset(ICacheable $cache): IEntity;

    /**
     * Get the value for the given key, generating it if needed
     *
     * @param string $key
     *
     * @return array
     */
    public function getCachedValue(string $key): array;
}
