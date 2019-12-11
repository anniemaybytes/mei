<?php

namespace Mei\Cache;

class NonPersistent implements IKeyStore
{
    /** @var array $inner */
    private $inner;
    /** @var array $initInner */
    protected $initInner;
    /** @var string $key_prefix */
    private $key_prefix;
    /** @var array $cacheHits */
    private $cacheHits = [];
    /** @var int $time */
    private $time = 0;

    public function __construct($array, $key_prefix)
    {
        $this->inner = $array;
        $this->key_prefix = $key_prefix;
    }

    public function get($key)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        if (array_key_exists($key, $this->inner)) {
            $res = $this->inner['key'];
        } else {
            $res = false;
        }

        if ($res) {
            $this->cacheHits[$key] = $res;
        }

        $this->endCall($start);

        return $res;
    }

    public function getCacheHits()
    {
        return $this->cacheHits;
    }

    public function getExecutionTime()
    {
        return $this->time;
    }

    public function set($key, $value, $expiry = 3600)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        $res = $this->inner[$key] = $value;
        $this->endCall($start);

        return $res;
    }

    public function delete($key)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        unset($this->inner[$key]);
        $this->endCall($start);

        return true;
    }

    public function increment($key, $n = 1, $initial = 1, $expiry = 0)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        $value = $this->get($key);
        if ($value === false) { // key does not exists yet, create it with $initial.
            $value = $initial;
        } else if (is_int($value)) { // exists and value is numeric, increment by $n
            $value += $n;
        } else { // unhandled case. value exists but is not numeric, can not increment.
            return false;
        }


        $this->inner[$key] = $value;
        $this->endCall($start);

        return $value;
    }

    public function touch($key, $expiry = 3600)
    {
        return true;
    }

    public function getEntityCache($key, $id = [], $duration = 0)
    {
        return new EntityCache($this, $key, $id, $duration);
    }

    private function startCall()
    {
        return microtime(true);
    }

    private function endCall($start)
    {
        $this->time += (microtime(true) - $start) * 1000;
    }
}
