<?php declare(strict_types=1);

namespace Mei\Cache;

/**
 * Class NonPersistent
 *
 * @package Mei\Cache
 */
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

    /**
     * NonPersistent constructor.
     *
     * @param $array
     * @param $key_prefix
     */
    public function __construct(array $array, string $key_prefix)
    {
        $this->inner = $array;
        $this->key_prefix = $key_prefix;
    }

    /**
     * @param string $key
     *
     * @return bool|mixed
     */
    public function get(string $key)
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

    /**
     * @return array
     */
    public function getCacheHits(): array
    {
        return $this->cacheHits;
    }

    /**
     * @return int
     */
    public function getExecutionTime(): int
    {
        return $this->time;
    }

    /**
     * @param string $key
     * @param $value
     * @param int $expiry
     *
     * @return mixed
     */
    public function set(string $key, $value, int $expiry = 10800)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        $res = $this->inner[$key] = $value;
        $this->endCall($start);

        return $res;
    }

    /**
     * @param string $key
     *
     */
    public function delete(string $key)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        unset($this->inner[$key]);
        $this->endCall($start);
    }

    /**
     * @param string $key
     * @param int $n
     * @param int $initial
     * @param int $expiry
     *
     * @return bool|int|mixed
     */
    public function increment(string $key, int $n = 1, int $initial = 1, int $expiry = 0)
    {
        $start = $this->startCall();
        $key = $this->key_prefix . $key;

        $value = $this->get($key);
        if ($value === false) { // key does not exists yet, create it with $initial.
            $value = $initial;
        } elseif (is_int($value)) { // exists and value is numeric, increment by $n
            $value += $n;
        } else { // unhandled case. value exists but is not numeric, can not increment.
            return false;
        }


        $this->inner[$key] = $value;
        $this->endCall($start);

        return $value;
    }

    /**
     * @param string $key
     * @param int $expiry
     */
    public function touch(string $key, int $expiry = 10800)
    {
    }

    /**
     * @param string $key
     * @param array $id
     * @param int $duration
     *
     * @return EntityCache|mixed
     */
    public function getEntityCache(string $key, array $id = [], int $duration = 0)
    {
        return new EntityCache($this, $key, $id, $duration);
    }

    /**
     * @return float|string
     */
    private function startCall()
    {
        return microtime(true);
    }

    /**
     * @param $start
     */
    private function endCall($start)
    {
        $this->time += (microtime(true) - $start) * 1000;
    }
}
