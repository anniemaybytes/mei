<?php

namespace Mei\Cache;

use InvalidArgumentException;
use Mei\Entity\ICacheable;

/**
 * Class EntityCache
 *
 * @package Mei\Cache
 */
class EntityCache implements ICacheable
{
    private $key;
    private $id;
    private $duration;
    private $dirty;

    private $dbRow;
    private $loadedValues;

    /**
     * EntityCache constructor.
     *
     * @param IKeyStore $cache
     * @param $key
     * @param array $id
     * @param int $duration
     */
    public function __construct(IKeyStore $cache, $key, $id = [], $duration = 3600)
    {
        $this->setKey($key);
        $this->setId($id);
        $this->setCacheDuration($duration);

        $this->dbRow = [];
        $this->loadedValues = [];
        $this->dirty = false;

        $this->loadCache($cache);
    }

    /**
     * @param string $key
     *
     * @return $this|ICacheable
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param $id
     *
     * @return $this|ICacheable
     */
    public function setId($id)
    {
        if (!is_array($id)) {
            throw new InvalidArgumentException("ID must be an array");
        }

        $row = $this->getRow();
        if (!is_array($row)) {
            $row = [];
        }

        $str = '';
        foreach ($id as $key => $value) {
            $row[$key] = $value;
            if ($str) {
                $str .= "_";
            }
            $str .= "{$key}_{$value}";
        }
        $id = $str;
        $this->id = $id;
        $this->setRow($row);

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $duration
     *
     * @return $this|ICacheable
     */
    public function setCacheDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return array
     */
    public function getRow()
    {
        return $this->dbRow;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getLoaded($key)
    {
        if (!array_key_exists($key, $this->loadedValues)) {
            return null;
        }
        return $this->loadedValues[$key];
    }

    /**
     * @param array $row
     *
     * @return $this|ICacheable
     */
    public function setRow($row)
    {
        $this->dirty = true;
        $this->dbRow = $row;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return $this|ICacheable
     */
    public function setLoaded($key, $value)
    {
        $this->dirty = true;
        $this->loadedValues[$key] = $value;
        return $this;
    }

    /**
     * @param IKeyStore $cache
     */
    private function loadCache(IKeyStore $cache)
    {
        $key = $this->getCacheKey();
        if (!$key) {
            return;
        }
        $cached = $cache->get($key);
        if ($cached) {
            $this->dirty = false;
            $this->setData($cached);
        }
    }

    /**
     * @return bool|string
     */
    private function getCacheKey()
    {
        if (!$this->id) {
            return false;
        }
        return sprintf("orm-%s_%s", $this->key, $this->id);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'dbRow' => $this->dbRow,
            'loadedValues' => $this->loadedValues,
        ];
    }

    /**
     * @param $cached
     *
     * @return $this|ICacheable
     */
    public function setData($cached)
    {
        if (!is_array($cached) ||
            !array_key_exists('dbRow', $cached) ||
            !array_key_exists('loadedValues', $cached)) {
            return $this;
        }

        $this->dbRow = $cached['dbRow'];
        $this->loadedValues = $cached['loadedValues'];
        return $this;
    }

    /**
     * @param IKeyStore $cache
     *
     * @return array
     */
    public function save(IKeyStore $cache)
    {
        $r = $this->getData();

        $key = $this->getCacheKey();
        if ($key) {
            $this->dirty = false;
            $cache->set($key, $r, $this->duration);
        }

        return $r;
    }

    /**
     * @param IKeyStore $cache
     */
    public function delete(IKeyStore $cache)
    {
        $key = $this->getCacheKey();
        if ($key) {
            $this->dirty = false;
            $cache->delete($key);
        }
    }
}
