<?php declare(strict_types=1);

namespace Mei\Entity;

use ArrayAccess;
use ArrayIterator;
use DomainException;
use Exception;
use IteratorAggregate;
use JsonSerializable;
use Mei\Entity\EntityAttributeMapper as Mapper;
use Mei\Entity\EntityAttributeType as Type;

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

    /** {@inheritDoc} */
    public static function getAttributes(): array
    {
        return static::getAttributesFromColumns(static::$columns);
    }

    /** {@inheritDoc} */
    private static function getAttributesFromColumns(array $columns): array
    {
        $attributes = [];

        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new DomainException('Invalid attributes set');
            }
            [$column, $type] = $val;
            $attributes[$column] = $type;
        }

        return $attributes;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public static function getDefaults(): array
    {
        return static::getDefaultsFromColumns(static::$columns);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    private static function getDefaultsFromColumns(array $columns): array
    {
        $defaults = [];
        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new DomainException('Invalid defaults set');
            }
            $column = $val[0];
            if (isset($val[2])) {
                $type = self::getAttributes()[$column];
                $defaults[$column] = Type::toString($type, $val[2]);
            }
        }

        return $defaults;
    }

    /** {@inheritDoc} */
    public static function getIdAttributes(): array
    {
        return static::getIdAttributesFromColumns(static::$columns);
    }

    /** {@inheritDoc} */
    private static function getIdAttributesFromColumns(array $columns): array
    {
        $ids = [];
        foreach ($columns as $val) {
            if (!is_array($val) || count($val) < 2) {
                throw new DomainException('Invalid ID set');
            }
            $column = $val[0];
            if (isset($val[3]) && $val[3]) {
                $ids[] = $column;
            }
        }

        return $ids;
    }

    /** {@inheritDoc} */
    public function getId(): ?array
    {
        $ids = self::getIdAttributes();
        $id = [];
        foreach ($ids as $col) {
            if (!$this->mapper->isAttributeSet($this->getCacheable(), $col)) {
                return null;
            }
            $id[$col] = $this->mapper->get($this->getCacheable(), $col);
        }

        return $id;
    }

    /** {@inheritDoc} */
    public function setCacheable(ICacheable $cacheable): IEntity
    {
        $this->cache = $cacheable;
        return $this;
    }

    /** {@inheritDoc} */
    public function getCacheable(): ICacheable
    {
        return $this->cache;
    }

    /** {@inheritDoc} */
    public function getChangedValues(): array
    {
        return $this->mapper->getChangedValues($this->getCacheable());
    }

    /** {@inheritDoc} */
    public function getValues(): array
    {
        return $this->mapper->getValues($this->getCacheable());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getMappedValues(): array
    {
        $values = $this->getValues();
        $mappedValues = [];

        foreach ($values as $attribute => $value) {
            $type = self::getAttributes()[$attribute];
            $mappedValues[$attribute] = Type::fromTo($type, $value);
        }

        return $mappedValues;
    }

    /** {@inheritDoc} */
    public function setNew(bool $new): IEntity
    {
        $this->new = $new;
        return $this;
    }

    /** {@inheritDoc} */
    public function isNew(): bool
    {
        return $this->new;
    }

    /** {@inheritDoc} */
    public function hasChanged(): bool
    {
        return $this->mapper->hasChanged($this->getCacheable());
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function reset(ICacheable $cache): IEntity
    {
        $this->new = false;
        $this->mapper = new Mapper(self::getAttributes(), self::getDefaults());
        $this->setCacheable($cache);
        if (!($cache->getId()) && $this->getId() !== null) {
            $cache = $this->getCacheable()->setId($this->getId());
            $this->setCacheable($cache);
        }
        $this->mapper->resetChangedAttributes();
        return $this;
    }

    /** {@inheritDoc} */
    public function getCachedValue(string $key): array
    {
        $value = $this->getCacheable()->getLoaded($key);
        if ($value === null) {
            $value = $this->generateCachedValue($key);
            $cache = $this->getCacheable()->setLoaded($key, $value);
            $this->setCacheable($cache);
        }
        return $value;
    }

    /**
     * @param string $key
     *
     * @return array
     */
    private function generateCachedValue(string $key): array
    {
        return [];
    }

    /// \ArrayAccess implementation

    /** {@inheritDoc} */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset)
    {
        $this->__unset($offset);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function offsetGet($offset)
    {
        return $this->__get($offset);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function offsetSet($offset, $value)
    {
        $this->__set($offset, $value);
    }

    /**
     * @param string $attribute
     *
     * @return bool
     */
    public function __isset(string $attribute): bool
    {
        return $this->mapper->isAttributeSet($this->getCacheable(), $attribute);
    }

    /**
     * @param string $attribute
     */
    public function __unset(string $attribute)
    {
        $cache = $this->mapper->unsetAttribute($this->getCacheable(), $attribute);
        $this->setCacheable($cache);
    }

    /**
     * @param string $attribute
     *
     * @return mixed
     * @throws Exception
     */
    public function __get(string $attribute)
    {
        if (!$this->__isset($attribute)) {
            return null;
        }
        $value = $this->mapper->get($this->getCacheable(), $attribute);
        $type = self::getAttributes()[$attribute];
        return Type::fromTo($type, $value);
    }

    /**
     * @param string $attribute
     * @param mixed $value
     *
     * @throws Exception
     */
    public function __set(string $attribute, $value): void
    {
        $attributes = self::getAttributes();
        // note that we can't use isset here - might be setting an attribute that
        // was never previously set; however, we still don't want to do anything if
        // the attribute does not exist
        if (!array_key_exists($attribute, $attributes)) {
            return;
        }
        $type = $attributes[$attribute];
        $mappedValue = Type::toString($type, $value);
        $cache = $this->mapper->set($this->getCacheable(), $attribute, $mappedValue);
        $this->setCacheable($cache);
    }

    /// \JsonSerializable implementation

    /** {@inheritDoc} */
    public function jsonSerialize(): array
    {
        return $this->getValues();
    }

    /// \IteratorAggregate implementation

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getMappedValues());
    }
}
