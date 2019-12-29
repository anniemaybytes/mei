<?php declare(strict_types=1);

namespace Mei\Cache;

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

    /**
     * @param string $key
     *
     * @return $this
     */
    public function setKey(string $key): self
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function setId(array $id): self
    {
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
     * @param int $duration
     *
     * @return $this
     */
    public function setCacheDuration(int $duration): self
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
    public function getLoaded(string $key)
    {
        if (!array_key_exists($key, $this->loadedValues)) {
            return null;
        }
        return $this->loadedValues[$key];
    }

    /**
     * @param array $row
     *
     * @return $this
     */
    public function setRow(array $row): self
    {
        $this->dirty = true;
        $this->dbRow = $row;
        return $this;
    }

    /**
     * @param string $key
     * @param $value
     *
     * @return $this
     */
    public function setLoaded(string $key, $value): self
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
     * @return string
     */
    private function getCacheKey(): string
    {
        if (!$this->id) {
            return "";
        }
        return sprintf("orm-%s_%s", $this->key, $this->id);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return [
            'dbRow' => $this->dbRow,
            'loadedValues' => $this->loadedValues,
        ];
    }

    /**
     * @param array $cached
     *
     * @return $this
     */
    public function setData(array $cached): self
    {
        $this->dbRow = $cached['dbRow'];
        $this->loadedValues = $cached['loadedValues'];
        return $this;
    }

    /**
     * @param IKeyStore $cache
     *
     * @return array
     */
    public function save(IKeyStore $cache): array
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
