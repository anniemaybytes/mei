<?php

declare(strict_types=1);

namespace Mei\Entity;

use ArrayAccess;
use ArrayIterator;
use DomainException;
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
    protected ICacheable $cache;
    protected IAttributeMapper $mapper;

    protected bool $new;

    /**
     * Array of arrays; each array represents one field.
     *  - First argument is the field name,
     *  - 2nd is the type,
     *  - 3rd is default value,
     *  - 4th if set to true denotes a primary key
     */
    protected static array $columns = [];

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

    /** @inheritDoc */
    public static function getAttributes(): array
    {
        return self::getAttributesFromColumns(static::$columns);
    }

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

    /** @inheritDoc */
    public static function getDefaults(): array
    {
        return self::getDefaultsFromColumns(static::$columns);
    }

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
                $defaults[$column] = Type::deflate($type, $val[2]);
            }
        }

        return $defaults;
    }

    /** @inheritDoc */
    public static function getIdAttributes(): array
    {
        return self::getIdAttributesFromColumns(static::$columns);
    }

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

    /** @inheritDoc */
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

    /** @inheritDoc */
    public function setCacheable(ICacheable $cacheable): IEntity
    {
        $this->cache = $cacheable;
        return $this;
    }

    /** @inheritDoc */
    public function getCacheable(): ICacheable
    {
        return $this->cache;
    }

    /** @inheritDoc */
    public function getChangedValues(): array
    {
        return $this->mapper->getChangedValues($this->getCacheable());
    }

    /** @inheritDoc */
    public function getValues(): array
    {
        return $this->mapper->getValues($this->getCacheable());
    }

    /** @inheritDoc */
    public function getMappedValues(): array
    {
        $values = $this->getValues();
        $mappedValues = [];

        foreach ($values as $attribute => $value) {
            $type = self::getAttributes()[$attribute];
            $mappedValues[$attribute] = Type::inflate($type, $value);
        }

        return $mappedValues;
    }

    /** @inheritDoc */
    public function setNew(bool $new): IEntity
    {
        $this->new = $new;
        return $this;
    }

    /** @inheritDoc */
    public function isNew(): bool
    {
        return $this->new;
    }

    /** @inheritDoc */
    public function hasChanged(): bool
    {
        return $this->mapper->hasChanged($this->getCacheable());
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
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

    private function generateCachedValue(string $key): array
    {
        return [];
    }

    /// \ArrayAccess implementation

    /** @inheritDoc */
    public function offsetExists($offset): bool
    {
        return $this->__isset($offset);
    }

    /** @inheritDoc */
    public function offsetUnset($offset): void
    {
        $this->__unset($offset);
    }

    /** @inheritDoc */
    public function offsetGet($offset): mixed
    {
        return $this->__get($offset);
    }

    /** @inheritDoc */
    public function offsetSet($offset, $value): void
    {
        $this->__set($offset, $value);
    }

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

    public function __get(string $attribute): mixed
    {
        if (!$this->__isset($attribute)) {
            return null;
        }
        $value = $this->mapper->get($this->getCacheable(), $attribute);
        $type = self::getAttributes()[$attribute];
        return Type::inflate($type, $value);
    }

    public function __set(string $attribute, mixed $value): void
    {
        $attributes = self::getAttributes();
        /*
         * Note that we can't use isset here - might be setting an attribute that was never previously set;
         * however, we still don't want to do anything if the attribute does not exist
         */
        if (!array_key_exists($attribute, $attributes)) {
            return;
        }
        $type = $attributes[$attribute];
        $mappedValue = Type::deflate($type, $value);
        $cache = $this->mapper->set($this->getCacheable(), $attribute, $mappedValue);
        $this->setCacheable($cache);
    }

    /// \JsonSerializable implementation

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->getValues();
    }

    /// \IteratorAggregate implementation

    /** @inheritDoc */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getMappedValues());
    }
}
