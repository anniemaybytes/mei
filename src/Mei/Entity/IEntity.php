<?php

namespace Mei\Entity;

/**
 *
 * An entity is an object that can be persisted
 *
 */
interface IEntity
{
    /**
     * Get an array of entity attributes as key and their default value as value.
     * If an attribute does not have a default value, it should not be included
     * in the array.
     *
     * @return array of string
     */
    public function getDefaults();

    /**
     * Get an array of entity attributes as key and their type as value
     *
     * @return array of string
     */
    public function getAttributes();

    /**
     * Get an array of the attributes used to uniquely identify the entity.
     * This array can also be empty.
     *
     * @return array of string
     */
    public function getIdAttributes();

    /**
     * Get array of id, key being attribute name and value being the value.
     * Return null if the id is not set, or only partially set in the case of
     * multiple ids. If there are no id attributes, should return an empty array
     *
     * @return array|null
     */
    public function getId();

    /**
     * @param ICacheable $cacheable
     *
     * @return IEntity
     */
    public function setCacheable(ICacheable $cacheable);

    /**
     * @return ICacheable
     */
    public function getCacheable();

    /**
     * Get an array of the attributes whose values have been changed.
     * The attribute is the key and the new value is the value.
     * The value should be a string representation usable in SQL.
     *
     * @return array of string
     */
    public function getChangedValues();

    /**
     * Get an array of the entity values.
     * The attribute is the key and the value is the value.
     * If an attribute is not set, it should not be part of the array.
     * The value should be a string representation usable in SQL.
     *
     * @return array of string
     */
    public function getValues();

    /**
     * Get an array of the entity values.
     * The attribute is the key and the value is the value.
     * If an attribute is not set, it should not be part of the array.
     * The value should be a php value.
     *
     * @return array of mixed
     */
    public function getMappedValues();

    /**
     * @param bool $new
     *
     * @return IEntity
     */
    public function setNew($new);

    /**
     * Check if the entity needs to be persisted (it is new)
     *
     * @return bool true if the entity is new and hasn't yet been saved
     */
    public function isNew();

    /**
     * Check if the entity data needs to be persisted (it has changed)
     *
     * @return bool true if the entity data has changed
     */
    public function hasChanged();

    /**
     * Reset the entity state, for example after the entity is saved
     *
     * @param ICacheable $cacheable
     *
     * @return IEntity
     */
    public function reset(ICacheable $cacheable);

    /**
     * Get the value for the given key, generating it if needed
     *
     * @param $key
     */
    public function getCachedValue($key);
}
