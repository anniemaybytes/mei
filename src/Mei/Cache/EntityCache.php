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
    private string $key;

    private string $id;

    private int $duration;

    private bool $dirty;

    private array $dbRow;

    private array $loadedValues;

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
    public function getRow(): array
    {
        return $this->dbRow;
    }

    /** {@inheritDoc} */
    public function getLoaded(string $key): mixed
    {
        return $this->loadedValues[$key] ?? null;
    }

    /** {@inheritDoc} */
    public function setRow(array $row): ICacheable
    {
        $this->dirty = true;
        $this->dbRow = $row;
        return $this;
    }

    /** {@inheritDoc} */
    public function setLoaded(string $key, mixed $value): ICacheable
    {
        $this->dirty = true;
        $this->loadedValues[$key] = $value;
        return $this;
    }

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
    public function setData(array $cache): ICacheable
    {
        $this->dbRow = $cache['dbRow'];
        $this->loadedValues = $cache['loadedValues'];
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
