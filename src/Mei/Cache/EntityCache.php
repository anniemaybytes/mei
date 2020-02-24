<?php

declare(strict_types=1);

namespace Mei\Cache;

use Mei\Entity\ICacheable;

/**
 * Class EntityCache
 *
 * @package Mei\Cache
 */
final class EntityCache implements ICacheable
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var bool
     */
    private $dirty;

    /**
     * @var array
     */
    private $dbRow;

    /**
     * @var array
     */
    private $loadedValues;

    /**
     * EntityCache constructor.
     *
     * @param IKeyStore $cache
     * @param string $key
     * @param array $id
     * @param int $duration
     */
    public function __construct(IKeyStore $cache, string $key, array $id = [], int $duration = 10800)
    {
        $this->setKey($key);
        $this->setId($id);
        $this->setCacheDuration($duration);

        $this->dbRow = [];
        $this->loadedValues = [];
        $this->dirty = false;

        $this->loadCache($cache);
    }

    /** {@inheritDoc} */
    public function setKey(string $key): ICacheable
    {
        $this->key = $key;
        return $this;
    }

    /** {@inheritDoc} */
    public function setId(array $id): ICacheable
    {
        $row = $this->getRow();
        if (!is_array($row)) {
            $row = [];
        }

        $str = '';
        foreach ($id as $key => $value) {
            $row[$key] = $value;
            if ($str) {
                $str .= '_';
            }
            $str .= "{$key}_{$value}";
        }
        $id = $str;
        $this->id = $id;
        $this->setRow($row);

        return $this;
    }

    /** {@inheritDoc} */
    public function getId(): string
    {
        return $this->id;
    }

    /** {@inheritDoc} */
    public function setCacheDuration(int $duration): ICacheable
    {
        $this->duration = $duration;
        return $this;
    }

    /** {@inheritDoc} */
    public function getRow(): ?array
    {
        return $this->dbRow;
    }

    /** {@inheritDoc} */
    public function getLoaded(string $key)
    {
        if (!array_key_exists($key, $this->loadedValues)) {
            return null;
        }
        return $this->loadedValues[$key];
    }

    /** {@inheritDoc} */
    public function setRow(array $row): ICacheable
    {
        $this->dirty = true;
        $this->dbRow = $row;
        return $this;
    }

    /** {@inheritDoc} */
    public function setLoaded(string $key, $value): ICacheable
    {
        $this->dirty = true;
        $this->loadedValues[$key] = $value;
        return $this;
    }

    /**
     * @param IKeyStore $cache
     */
    private function loadCache(IKeyStore $cache): void
    {
        $key = $this->getCacheKey();
        if (!$key) {
            return;
        }
        $cached = $cache->doGet($key);
        if ($cached) {
            $this->dirty = false;
            $this->setData($cached);
        }
    }

    /**
     * @return string
     */
    private function getCacheKey(): string
    {
        if (!$this->id) {
            return '';
        }
        return sprintf('orm-%s_%s', $this->key, $this->id);
    }

    /** {@inheritDoc} */
    public function getData(): array
    {
        return [
            'dbRow' => $this->dbRow,
            'loadedValues' => $this->loadedValues,
        ];
    }

    /** {@inheritDoc} */
    public function setData(array $cached): ICacheable
    {
        $this->dbRow = $cached['dbRow'];
        $this->loadedValues = $cached['loadedValues'];
        return $this;
    }

    /** {@inheritDoc} */
    public function save(IKeyStore $cache): array
    {
        $r = $this->getData();

        $key = $this->getCacheKey();
        if ($key) {
            $this->dirty = false;
            $cache->doSet($key, $r, $this->duration);
        }

        return $r;
    }

    /** {@inheritDoc} */
    public function delete(IKeyStore $cache): void
    {
        $key = $this->getCacheKey();
        if ($key) {
            $this->dirty = false;
            $cache->doDelete($key);
        }
    }
}
