<?php
namespace Mei\Entity;

use Mei\Utilities\EntityAttributeMapper as Mapper;
use Mei\Utilities\EntityAttributeType as Type;

abstract class Entity implements IEntity, \ArrayAccess, \JsonSerializable, \IteratorAggregate
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
    protected static $columns = array();

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

    public function getAttributes()
    {
        return static::getAttributesFromColumns(static::$columns);
    }

    public static function getAttributesFromColumns($columns)
    {
        if (!is_array($columns)) {
            throw new \InvalidArgumentException("Expects array argument");
        }

        $attributes = array();

        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new \Exception("Invalid attributes set");
            }
            $column = $val[0];
            $type = $val[1];
            $attributes[$column] = $type;
        }

        return $attributes;
    }

    public function getDefaults()
    {
        return static::getDefaultsFromColumns(static::$columns);
    }

    public static function getDefaultsFromColumns($columns)
    {
        if (!is_array($columns)) {
            throw new \InvalidArgumentException("Expects array argument");
        }

        $defaults = array();
        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new \Exception("Invalid defaults set");
            }
            $column = $val[0];
            if (isset($val[2])) {
                $defaults[$column] = $val[2];
            }
        }

        return $defaults;
    }

    public function getIdAttributes()
    {
        return static::getIdAttributesFromColumns(static::$columns);
    }

    public static function getIdAttributesFromColumns($columns)
    {
        if (!is_array($columns)) {
            throw new \InvalidArgumentException("Expects array argument");
        }

        $ids = array();
        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new \Exception("Invalid ID set");
            }
            $column = $val[0];
            if (isset($val[3]) && $val[3]) {
                $ids[] = $column;
            }
        }

        return $ids;
    }

    public function getId()
    {
        $ids = $this->getIdAttributes();
        $id = array();
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

    public function setCacheable(ICacheable $cacheable)
    {
        $this->cache = $cacheable;
        return $this;
    }

    public function getCacheable()
    {
        return $this->cache;
    }

    public function getChangedValues()
    {
        return $this->mapper->getChangedValues($this->getCacheable());
    }

    public function getValues()
    {
        return $this->mapper->getValues($this->getCacheable());
    }

    public function getMappedValues()
    {
        $values = $this->getValues();
        $mappedValues = array();

        foreach ($values as $attribute => $value) {
            $type = $this->getAttributes()[$attribute];
            $mappedValues[$attribute] = Type::fromString($type, $value);
        }

        return $mappedValues;
    }

    /**
     * @return IEntity
     */
    public function setNew($new)
    {
        $this->new = $new;
        return $this;
    }

    public function isNew()
    {
        return $this->new;
    }

    public function hasChanged()
    {
        return $this->mapper->hasChanged($this->getCacheable());
    }

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

    public function generateCachedValue($key)
    {
        return array();
    }

    /// \ArrayAccess implementation
    public function offsetExists($offset)
    {
        return $this->__isset($offset);
    }

    public function offsetUnset($offset)
    {
        return $this->__unset($offset);
    }

    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->__set($offset, $value);
    }

    public function __isset($attribute)
    {
        return $this->mapper->isAttributeSet($this->getCacheable(), $attribute);
    }

    public function __unset($attribute)
    {
        $cache = $this->mapper->unsetAttribute($this->getCacheable(), $attribute);
        return $this->setCacheable($cache);
    }

    public function __get($attribute)
    {
        if (!$this->__isset($attribute)) {
            return null;
        }
        $value = $this->mapper->get($this->getCacheable(), $attribute);
        $type = $this->getAttributes()[$attribute];
        return Type::fromString($type, $value);
    }

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

    public function jsonSerialize()
    {
        return $this->getValues();
    }

    /// \IteratorAggregate implementation

    public function getIterator()
    {
        return new \ArrayIterator($this->getMappedValues());
    }
}
