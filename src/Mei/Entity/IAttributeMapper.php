<?php

namespace Mei\Entity;

/**
 *
 * This is used to access and operate on the attributes of an entity.
 */
interface IAttributeMapper
{
    public function get(ICacheable $cache, $attribute);

    /**
     * @param ICacheable $cache
     * @param $attribute
     * @param $value
     * @return ICacheable
     */
    public function set(ICacheable $cache, $attribute, $value);

    public function isAttributeSet(ICacheable $cache, $attribute);

    /**
     * @param ICacheable $cache
     * @param $attribute
     * @return ICacheable
     */
    public function unsetAttribute(ICacheable $cache, $attribute);

    /**
     * Get a list of the attributes whose values have been changed, and their values
     *
     * @param ICacheable $cache
     * @return array of string
     */
    public function getChangedValues(ICacheable $cache);

    /**
     * Get a list of the entity's values
     *
     * @param ICacheable $cache
     * @return array of string
     */
    public function getValues(ICacheable $cache);

    /**
     * Check if the data has changed
     *
     * @param ICacheable $cache
     * @return bool true if the data has changed
     */
    public function hasChanged(ICacheable $cache);

    /**
     * @return ICacheable
     */
    public function resetChangedAttributes();
}
