<?php declare(strict_types=1);

namespace Mei\Cache;

use Mei\Entity\ICacheable;

/**
 * Class NonPersistent
 *
 * @package Mei\Cache
 */
class NonPersistent implements IKeyStore
{
    /**
     * @var array $inner
     */
    private $inner = [];

    /**
     * @var string $keyPrefix
     */
    private $keyPrefix;

    /**
     * @var bool $clearOnGet
     */
    private $clearOnGet = false;

    /**
     * @var array $cacheHits
     */
    private $cacheHits = [];

    /**
     * @var int $time
     */
    private $time = 0;

    /**
     * NonPersistent constructor.
     *
     * @param string $keyPrefix
     */
    public function __construct(string $keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;
    }

    /** {@inheritDoc} */
    public function doGet(string $key)
    {
        $start = $this->startCall();
        $keyOld = $key;
        $key = $this->keyPrefix . $key;

        if ($this->clearOnGet) {
            $this->doDelete($keyOld);
            $this->endCall($start);
            return false;
        }

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

    /** {@inheritDoc} */
    public function getCacheHits(): array
    {
        return $this->cacheHits;
    }

    /** {@inheritDoc} */
    public function getExecutionTime(): int
    {
        return $this->time;
    }

    /** {@inheritDoc} */
    public function doSet(string $key, $value, int $expiry = 10800): bool
    {
        $start = $this->startCall();
        $key = $this->keyPrefix . $key;

        $this->inner[$key] = $value;
        $this->endCall($start);

        return true;
    }

    /** {@inheritDoc} */
    public function doDelete(string $key): bool
    {
        $start = $this->startCall();
        $key = $this->keyPrefix . $key;

        unset($this->inner[$key]);
        $this->endCall($start);

        return true;
    }

    /** {@inheritDoc} */
    public function doIncrement(string $key, int $n = 1, int $initial = 1, int $expiry = 0)
    {
        $start = $this->startCall();
        $key = $this->keyPrefix . $key;

        if ($this->clearOnGet) {
            $this->endCall($start);
            return $initial;
        }

        if (array_key_exists($key, $this->inner)) { // key does not exists yet, create it with $initial.
            $value = $initial;
        } elseif (is_int($this->inner['key'])) { // exists and value is numeric, increment by $n
            $value = $this->inner['key'] + $n;
        } else { // unhandled case. value exists but is not numeric, can not increment.
            $this->endCall($start);
            return false;
        }

        $this->inner[$key] = $value;
        $this->endCall($start);

        return $value;
    }

    /** {@inheritDoc} */
    public function doTouch(string $key, int $expiry = 10800): bool
    {
        return true;
    }

    /** {@inheritDoc} */
    public function getEntityCache(string $key, array $id = [], int $duration = 0): ICacheable
    {
        return new EntityCache($this, $key, $id, $duration);
    }

    /**
     * @return float
     */
    private function startCall(): float
    {
        return microtime(true);
    }

    /**
     * @param float $start
     */
    private function endCall(float $start)
    {
        $this->time += (microtime(true) - $start) * 1000;
    }

    /** {@inheritDoc} */
    public function doFlush()
    {
        foreach ($this->inner as $key => $value) {
            unset($this->inner[$key]);
        }
    }

    /** {@inheritDoc} */
    public function getAllKeys(): array
    {
        return array_keys($this->inner);
    }

    /** {@inheritDoc} */
    public function setClearOnGet(bool $val)
    {
        $this->clearOnGet = $val;
    }

    /**
     * @return array
     */
    public function getStats(): array
    {
        return ['count' => count($this->inner)];
    }
}
