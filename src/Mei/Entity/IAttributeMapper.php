<?php declare(strict_types=1);

namespace Mei\Entity;

/**
 *
 * This is used to access and operate on the attributes of an entity.
 */
interface IAttributeMapper
{
    /**
     * @param ICacheable $cache
     * @param string $attribute
     *
     * @return mixed
     */
    public function get(ICacheable $cache, string $attribute);

    /**
     * @param ICacheable $cache
     * @param string $attribute
     * @param $value
     *
     * @return ICacheable
     */
    public function set(ICacheable $cache, string $attribute, $value);

    /**
     * @param ICacheable $cache
     * @param string $attribute
     *
     * @return bool
     */
    public function isAttributeSet(ICacheable $cache, string $attribute);

    /**
     * @param ICacheable $cache
     * @param string $attribute
     *
     * @return ICacheable
     */
    public function unsetAttribute(ICacheable $cache, string $attribute);

    /**
     * Get a list of the attributes whose values have been changed, and their values
     *
     * @param ICacheable $cache
     *
     * @return string[]
     */
    public function getChangedValues(ICacheable $cache);

    /**
     * Get a list of the entity's values
     *
     * @param ICacheable $cache
     *
     * @return string[]
     */
    public function getValues(ICacheable $cache);

    /**
     * Check if the data has changed
     *
     * @param ICacheable $cache
     *
     * @return bool true if the data has changed
     */
    public function hasChanged(ICacheable $cache);

    /**
     * @return ICacheable
     */
    public function resetChangedAttributes();
}
