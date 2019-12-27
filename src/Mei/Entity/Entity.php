<?php

namespace Mei\Entity;

use ArrayAccess;
use ArrayIterator;
use DomainException;
use Exception;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Mei\Entity\EntityAttributeMapper as Mapper;
use Mei\Entity\EntityAttributeType as Type;
use Traversable;

/**
 * Class Entity
 *
 * @package Mei\Entity
 */
abstract class Entity implements IEntity, ArrayAccess, JsonSerializable, IteratorAggregate
{
    /**
     * @var ICacheable
     */
    protected $cache;

    /**
     * @var IAttributeMapper
     */
    protected $mapper;

    /**
     * @var bool
     */
    protected $new;

    // array of arrays; each array represents one field;
    // first argument is the field name, 2nd is the type, 3rd is default value,
    // 4th if set to true denotes a primary key
    protected static $columns = [];

    /**
     * Entity constructor.
     *
     * @param ICacheable $cache
     *
     * @throws Exception
     */
    public function __construct(ICacheable $cache)
    {
        $this->reset($cache);
    }

    public function __clone()
    {
        $this->cache = clone $this->cache;
        $this->mapper = clone $this->mapper;
    }

    /// IEntity Implementation

    /**
     * Get an array of entity attributes as key and their type as value
     *
     * @return array of string
     * @throws Exception
     */
    public function getAttributes()
    {
        return static::getAttributesFromColumns(static::$columns);
    }

    /**
     * @param $columns
     *
     * @return array
     */
    public static function getAttributesFromColumns($columns)
    {
        if (!is_array($columns)) {
            throw new InvalidArgumentException("Expects array argument");
        }

        $attributes = [];

        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new DomainException("Invalid attributes set");
            }
            $column = $val[0];
            $type = $val[1];
            $attributes[$column] = $type;
        }

        return $attributes;
    }

    /**
     * Get an array of entity attributes as key and their default value as value.
     * If an attribute does not have a default value, it should not be included
     * in the array.
     *
     * @return array of string
     */
    public function getDefaults()
    {
        return static::getDefaultsFromColumns(static::$columns);
    }

    /**
     * @param $columns
     *
     * @return array
     */
    public static function getDefaultsFromColumns($columns)
    {
        if (!is_array($columns)) {
            throw new InvalidArgumentException("Expects array argument");
        }

        $defaults = [];
        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new DomainException("Invalid defaults set");
            }
            $column = $val[0];
            if (isset($val[2])) {
                $defaults[$column] = $val[2];
            }
        }

        return $defaults;
    }

    /**
     * Get an array of the attributes used to uniquely identify the entity.
     * This array can also be empty.
     *
     * @return array of string
     */
    public function getIdAttributes()
    {
        return static::getIdAttributesFromColumns(static::$columns);
    }

    /**
     * @param $columns
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws DomainException
     */
    public static function getIdAttributesFromColumns($columns)
    {
        if (!is_array($columns)) {
            throw new InvalidArgumentException("Expects array argument");
        }

        $ids = [];
        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new DomainException("Invalid ID set");
            }
            $column = $val[0];
            if (isset($val[3]) && $val[3]) {
                $ids[] = $column;
            }
        }

        return $ids;
    }

    /**
     * Get array of id, key being attribute name and value being the value.
     * Return null if the id is not set, or only partially set in the case of
     * multiple ids. If there are no id attributes, should return an empty array
     *
     * @return array|null
     */
    public function getId()
    {
        $ids = $this->getIdAttributes();
        $id = [];
        foreach ($ids as $col) {
            if (is_null($this->getCacheable()) ||
                is_null($this->mapper) ||
                !$this->mapper->isAttributeSet($this->getCacheable(), $col)) {
                return null;
            }
            $id[$col] = $this->mapper->get($this->getCacheable(), $col);
        }

        return $id;
    }

    /**
     * @param ICacheable $cacheable
     *
     * @return $this|IEntity
     */
    public function setCacheable(ICacheable $cacheable)
    {
        $this->cache = $cacheable;
        return $this;
    }

    /**
     * @return ICacheable
     */
    public function getCacheable()
    {
        return $this->cache;
    }

    /**
     * @return array
     */
    public function getChangedValues()
    {
        return $this->mapper->getChangedValues($this->getCacheable());
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->mapper->getValues($this->getCacheable());
    }

    /**
     * Get an array of the entity values.
     * The attribute is the key and the value is the value.
     * If an attribute is not set, it should not be part of the array.
     * The value should be a php value.
     *
     * @return array of mixed
     * @throws Exception
     */
    public function getMappedValues()
    {
        $values = $this->getValues();
        $mappedValues = [];

        foreach ($values as $attribute => $value) {
            $type = $this->getAttributes()[$attribute];
            $mappedValues[$attribute] = Type::fromString($type, $value);
        }

        return $mappedValues;
    }

    /**
     * @param $new
     *
     * @return IEntity
     */
    public function setNew($new)
    {
        $this->new = $new;
        return $this;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * @return bool
     */
    public function hasChanged()
    {
        return $this->mapper->hasChanged($this->getCacheable());
    }

    /**
     * Reset the entity state, for example after the entity is saved
     *
     * @param ICacheable $cache
     *
     * @return IEntity
     * @throws Exception
     */
    public function reset(ICacheable $cache)
    {
        $this->new = false;
        $this->mapper = new Mapper($this->getAttributes(), $this->getDefaults());
        $this->setCacheable($cache);
        if (!($cache->getId()) && !is_null($this->getId())) {
            $cache = $this->getCacheable()->setId($this->getId());
            $this->setCacheable($cache);
        }
        $this->mapper->resetChangedAttributes();
        return $this;
    }

    /**
     * @param $key
     *
     * @return array
     */
    public function getCachedValue($key)
    {
        $value = $this->getCacheable()->getLoaded($key);
        if (is_null($value)) {
            $value = $this->generateCachedValue($key);
            $cache = $this->getCacheable()->setLoaded($key, $value);
            $this->setCacheable($cache);
        }
        return $value;
    }

    /**
     * @param $key
     *
     * @return array
     */
    public function generateCachedValue($key)
    {
        return [];
    }

    /// \ArrayAccess implementation

    /**
     * @param mixed $offset
     *
     * @return bool|mixed
     */
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return $this|IEntity|void
     */
    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @return $this|IEntity|void
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    /**
     * @param $attribute
     *
     * @return mixed
     */
    public function __isset($attribute)
    {
        return $this->mapper->isAttributeSet($this->getCacheable(), $attribute);
    }

    /**
     * @param $attribute
     *
     * @return $this|IEntity
     */
    public function __unset($attribute)
    {
        $cache = $this->mapper->unsetAttribute($this->getCacheable(), $attribute);
        return $this->setCacheable($cache);
    }

    /**
     * @param $attribute
     *
     * @return mixed|null
     * @throws Exception
     */
    public function __get($attribute)
    {
        if (!$this->__isset($attribute)) {
            return null;
        }
        $value = $this->mapper->get($this->getCacheable(), $attribute);
        $type = $this->getAttributes()[$attribute];
        return Type::fromString($type, $value);
    }

    /**
     * @param $attribute
     * @param $value
     *
     * @return $this|IEntity
     * @throws Exception
     */
    public function __set($attribute, $value)
    {
        $attributes = $this->getAttributes();
        // note that we can't use isset here - might be setting an attribute that
        // was never previously set; however, we still don't want to do anything if
        // the attribute does not exist
        if (!array_key_exists($attribute, $attributes)) {
            return $this;
        }
        $type = $attributes[$attribute];
        $mappedValue = Type::toString($type, $value);
        $cache = $this->mapper->set($this->getCacheable(), $attribute, $mappedValue);
        return $this->setCacheable($cache);
    }

    /// \JsonSerializable implementation

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return $this->getValues();
    }

    /// \IteratorAggregate implementation

    /**
     * @return ArrayIterator|Traversable
     * @throws Exception
     */
    public function getIterator()
    {
        return new ArrayIterator($this->getMappedValues());
    }
}
