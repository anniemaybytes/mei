<?php

namespace Mei\Utilities;

use Mei\Entity\ICacheable;

class EntityAttributeMapper implements \Mei\Entity\IAttributeMapper
{
    private $attributeMap;
    private $defaultValues; // stored in db string form
    private $changedAttributes;

    public function __construct($attributeMap, $defaultValues = array())
    {
        $this->attributeMap = $attributeMap;
        $this->defaultValues = $defaultValues;
        $this->changedAttributes = array();
    }

    private function getAttributeValue(ICacheable $cache, $attribute)
    {
        $values = $cache->getRow();
        if (array_key_exists($attribute, $values)) {
            return $values[$attribute];
        }

        if (array_key_exists($attribute, $this->defaultValues)) {
            return $this->defaultValues[$attribute];
        }

        throw new \InvalidArgumentException("Tried to get attribute that hasn't been set");
    }

    private function setAttributeValue(ICacheable $cache, $attribute, $value)
    {
        $values = $cache->getRow();
        if (!array_key_exists($attribute, $values) || $value != $values[$attribute]) {
            // the value is different, need to make a note
            $this->changedAttributes[$attribute] = null;
        }

        $values[$attribute] = $value;

        return $cache->setRow($values);
    }

    public function get(ICacheable $cache, $attribute)
    {
        if (!array_key_exists($attribute, $this->attributeMap)) {
            throw new \InvalidArgumentException("Tried to get unknown key name '$attribute' - not in allowed attributes");
        }

        return $this->getAttributeValue($cache, $attribute);
    }

    /**
     * Sets the attribute's value
     * @param ICacheable $cache
     * @param $attribute
     * @param $value
     * @return ICacheable
     */
    public function set(ICacheable $cache, $attribute, $value)
    {
        if (!array_key_exists($attribute, $this->attributeMap)) {
            throw new \InvalidArgumentException("Tried to set unknown key name '$attribute'");
        }

        return $this->setAttributeValue($cache, $attribute, $value);
    }

    public function isAttributeSet(ICacheable $cache, $attribute)
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

    public function unsetAttribute(ICacheable $cache, $attribute)
    {
        $values = $cache->getRow();
        unset($values[$attribute]);
        // the value was unset, need to make a note
        $this->changedAttributes[$attribute] = null;
        return $cache->setRow($values);
    }

    public function getChangedValues(ICacheable $cache)
    {
        $values = array();
        foreach (array_keys($this->changedAttributes) as $attribute) {
            if ($this->isAttributeSet($cache, $attribute)) {
                $values[$attribute] = $this->get($cache, $attribute);
            }
            else {
                $values[$attribute] = null; // this is the case when a variable is unset
            }

        }
        return $values;
    }

    public function getValues(ICacheable $cache)
    {
        $values = array();
        foreach (array_keys($this->attributeMap) as $attribute) {
            if ($this->isAttributeSet($cache, $attribute)) {
                $values[$attribute] = $this->get($cache, $attribute);
            }
        }
        return $values;
    }

    public function hasChanged(ICacheable $cache)
    {
        return (count($this->changedAttributes) > 0);
    }

    public function resetChangedAttributes()
    {
        $this->changedAttributes = array();
        return $this;
    }
}
