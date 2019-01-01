<?php
namespace Mei\Cache;

use InvalidArgumentException;
use Mei\Entity\ICacheable;

class EntityCachelessCache implements ICacheable
{
    private $dbRow;
    private $loadedValues;
    private $key;
    private $id;

    public function __construct()
    {
        $this->dbRow = array();
        $this->loadedValues = array();
        $this->key = null;
        $this->id = null;
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

        $this->id = implode('_', $id);

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCacheDuration($duration)
    {
        return;
    }

    public function getRow()
    {
        return $this->dbRow;
    }

    public function getLoaded($key)
    {
        if (!array_key_exists($key, $this->loadedValues)) {
            return null;
        }
        return $this->loadedValues[$key];
    }

    public function setRow($row)
    {
        $this->dbRow = $row;
        return $this;
    }

    public function setLoaded($key, $value)
    {
        $this->loadedValues[$key] = $value;
        return $this;
    }

    public function getData()
    {
        $r = array(
            'dbRow'         => $this->dbRow,
            'loadedValues'  => $this->loadedValues,
        );

        return $r;
    }

    public function setData($data)
    {
        $this->dbRow = $data['dbRow'];
        $this->loadedValues = $data['loadedValues'];
        return $this;
    }

    public function save(IKeyStore $cache)
    {
        return $this->getData();
    }

    public function delete(IKeyStore $cache)
    {
        return;
    }
}
