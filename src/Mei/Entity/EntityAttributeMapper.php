<?php

declare(strict_types=1);

namespace Mei\Entity;

use InvalidArgumentException;

/**
 * Class EntityAttributeMapper
 *
 * @package Mei\Entity
 */
final class EntityAttributeMapper implements IAttributeMapper
{
    private $attributeMap;
    private $defaultValues; // stored in db string form
    private $changedAttributes;

    /**
     * EntityAttributeMapper constructor.
     *
     * @param array $attributeMap
     * @param array $defaultValues
     */
    public function __construct(array $attributeMap, array $defaultValues = [])
    {
        $this->attributeMap = $attributeMap;
        $this->defaultValues = $defaultValues;
        $this->changedAttributes = [];
    }

    /**
     * @param ICacheable $cache
     * @param string $attribute
     *
     * @return mixed
     */
    private function getAttributeValue(ICacheable $cache, string $attribute)
    {
        $values = $cache->getRow();
        if (array_key_exists($attribute, $values)) {
            return $values[$attribute];
        }

        if (array_key_exists($attribute, $this->defaultValues)) {
            return $this->defaultValues[$attribute];
        }

        throw new InvalidArgumentException("Tried to get attribute that hasn't been set");
    }

    /**
     * @param ICacheable $cache
     * @param string $attribute
     * @param mixed $value
     *
     * @return ICacheable
     * @noinspection TypeUnsafeComparisonInspection
     */
    private function setAttributeValue(ICacheable $cache, string $attribute, $value): ICacheable
    {
        $values = $cache->getRow();
        if (!array_key_exists($attribute, $values) || $value != $values[$attribute]) {
            // the value is different, need to make a note
            $this->changedAttributes[$attribute] = null;
        }

        $values[$attribute] = $value;

        return $cache->setRow($values);
    }

    /** {@inheritDoc} */
    public function get(ICacheable $cache, string $attribute)
    {
        if (!array_key_exists($attribute, $this->attributeMap)) {
            throw new InvalidArgumentException(
                "Tried to get unknown key name '$attribute' - not in allowed attributes"
            );
        }

        return $this->getAttributeValue($cache, $attribute);
    }

    /** {@inheritDoc} */
    public function set(ICacheable $cache, string $attribute, $value): ICacheable
    {
        if (!array_key_exists($attribute, $this->attributeMap)) {
            throw new InvalidArgumentException("Tried to set unknown key name '$attribute'");
        }

        return $this->setAttributeValue($cache, $attribute, $value);
    }

    /** {@inheritDoc} */
    public function isAttributeSet(ICacheable $cache, string $attribute): bool
    {
        $values = $cache->getRow();
        if (array_key_exists($attribute, $values)) {
            return true;
        }

        if (array_key_exists($attribute, $this->defaultValues)) {
            return true;
        }

        return false;
    }

    /** {@inheritDoc} */
    public function unsetAttribute(ICacheable $cache, string $attribute): ICacheable
    {
        $values = $cache->getRow();
        unset($values[$attribute]);
        // the value was unset, need to make a note
        $this->changedAttributes[$attribute] = null;
        return $cache->setRow($values);
    }

    /** {@inheritDoc} */
    public function getChangedValues(ICacheable $cache): array
    {
        $values = [];
        foreach (array_keys($this->changedAttributes) as $attribute) {
            if ($this->isAttributeSet($cache, $attribute)) {
                $values[$attribute] = $this->get($cache, $attribute);
            } else {
                $values[$attribute] = null; // this is the case when a variable is unset
            }
        }
        return $values;
    }

    /** {@inheritDoc} */
    public function getValues(ICacheable $cache): array
    {
        $values = [];
        foreach (array_keys($this->attributeMap) as $attribute) {
            if ($this->isAttributeSet($cache, $attribute)) {
                $values[$attribute] = $this->get($cache, $attribute);
            }
        }
        return $values;
    }

    /** {@inheritDoc} */
    public function hasChanged(ICacheable $cache): bool
    {
        return (count($this->changedAttributes) > 0);
    }

    /** {@inheritDoc} */
    public function resetChangedAttributes(): IAttributeMapper
    {
        $this->changedAttributes = [];
        return $this;
    }
}
