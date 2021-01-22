<?php

declare(strict_types=1);

namespace Mei\Entity;

/**
 * This is used to access and operate on the attributes of an entity.
 */
interface IAttributeMapper
{
    public function get(ICacheable $cache, string $attribute): mixed;

    public function set(ICacheable $cache, string $attribute, mixed $value): ICacheable;

    public function isAttributeSet(ICacheable $cache, string $attribute): bool;

    public function unsetAttribute(ICacheable $cache, string $attribute): ICacheable;

    /**
     * Get a list of the attributes whose values have been changed, and their values
     *
     * @param ICacheable $cache
     *
     * @return string[]
     */
    public function getChangedValues(ICacheable $cache): array;

    /**
     * Get a list of the entity's values
     *
     * @param ICacheable $cache
     *
     * @return string[]
     */
    public function getValues(ICacheable $cache): array;

    /**
     * Check if the data has changed
     *
     * @param ICacheable $cache
     *
     * @return bool true if the data has changed
     */
    public function hasChanged(ICacheable $cache): bool;

    public function resetChangedAttributes(): IAttributeMapper;
}
