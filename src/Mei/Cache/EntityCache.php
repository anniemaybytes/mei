<?php
namespace Mei\Cache;

use InvalidArgumentException;
use Mei\Entity\ICacheable;

class EntityCache implements ICacheable
{
    private $key;
    private $id;
    private $duration;
    private $dirty;

    private $dbRow;
    private $loadedValues;

    public function __construct(IKeyStore $cache, $key, $id = array(), $duration = 3600)
    {
        $this->setKey($key);
        $this->setId($id);
        $this->setCacheDuration($duration);

        $this->dbRow = array();
        $this->loadedValues = array();
        $this->dirty = false;

        $this->loadCache($cache);
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function setId($id)
    {
        if (!is_array($id)) {
            throw new InvalidArgumentException("ID must be an array");
        }

        $row = $this->getRow();
        if (!is_array($row)) {
            $row = array();
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

    public function setCacheDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    public function getRow()
    {
        return $this->dbRow;
    }

    public function getLoaded($key)
    {
        if (!array_key_exists($key, $this->loadedValues)) return null;
        return $this->loadedValues[$key];
    }

    public function setRow($row)
    {
        $this->dirty = true;
        $this->dbRow = $row;
        return $this;
    }

    public function setLoaded($key, $value)
    {
        $this->dirty = true;
        $this->loadedValues[$key] = $value;
        return $this;
    }

    private function loadCache(IKeyStore $cache)
    {
        $key = $this->getCacheKey();
        if (!$key) return;
        $cached = $cache->get($key);
        if ($cached) {
            $this->dirty = false;
            $this->setData($cached);
        }
    }

    private function getCacheKey()
    {
        if (!$this->id) return false;
        return sprintf("%s_%s", $this->key, $this->id);
    }

    public function getData()
    {
        $r = array(
            'dbRow'         => $this->dbRow,
            'loadedValues'  => $this->loadedValues,
        );

        return $r;
    }

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

    public function delete(IKeyStore $cache)
    {
        $key = $this->getCacheKey();
        if ($key) {
            $this->dirty = false;
            $cache->delete($key);
        }
    }
}
