<?php

declare(strict_types=1);

namespace Mei\Cache;

use Mei\Entity\ICacheable;
use Throwable;
use Tracy\Debugger;

/**
 * Class NonPersistent
 *
 * @package Mei\Cache
 */
final class NonPersistent implements IKeyStore
{
    private string $keyPrefix;

    private bool $clearOnGet = false;

    private array $cacheHits = [];
    private float $time = 0;

    private array $inner = [];

    public function __construct(string $keyPrefix)
    {
        $this->keyPrefix = $keyPrefix;

        $bar = new CacheTracyBarPanel($this);
        Debugger::getBar()->addPanel($bar);
        Debugger::getBlueScreen()->addPanel(
            static function (?Throwable $e) use ($bar) {
                if ($e) {
                    return null;
                }
                return [
                    'tab' => 'Cache hits',
                    'panel' => $bar->getPanel(),
                ];
            }
        );
    }

    // === CACHE ===

    /** @inheritDoc */
    public function get(string $key): mixed
    {
        $start = $this->startCall();
        $keyOld = $key;
        $key = $this->keyPrefix . $key;

        if ($this->clearOnGet) {
            $this->delete($keyOld);
            $this->endCall($start);
            return false;
        }

        if (array_key_exists($key, $this->inner)) {
            $res = $this->inner[$key];
            $this->cacheHits[$key] = $res;
        } else {
            $res = false;
        }

        $this->endCall($start);

        return $res;
    }

    public function set(string $key, mixed $value, int $time = 10800): bool
    {
        $start = $this->startCall();
        $key = $this->keyPrefix . $key;

        $this->inner[$key] = $value;
        $this->endCall($start);

        return true;
    }

    public function delete(string $key): bool
    {
        $start = $this->startCall();
        $key = $this->keyPrefix . $key;

        unset($this->inner[$key]);
        $this->endCall($start);

        return true;
    }

    public function increment(string $key, int $n = 1, int $initial = 1, int $expiry = 0): bool|int
    {
        $start = $this->startCall();
        $key = $this->keyPrefix . $key;

        if ($this->clearOnGet) {
            $this->endCall($start);
            return $initial;
        }

        if (!array_key_exists($key, $this->inner)) { // key does not exists yet, create it with $initial.
            $value = $initial;
        } elseif (is_int($this->inner[$key])) { // exists and value is numeric, increment by $n
            $value = $this->inner[$key] + $n;
        } else { // unhandled case - value exists but is not numeric, can not increment.
            $this->endCall($start);
            return false;
        }

        $this->inner[$key] = $value;
        $this->endCall($start);

        return $value;
    }

    public function touch(string $key, int $expiry = 10800): bool
    {
        return true;
    }

    public function flush(): void
    {
        foreach ($this->inner as $key => $value) {
            unset($this->inner[$key]);
        }
    }

    public function setClearOnGet(bool $val): void
    {
        $this->clearOnGet = $val;
    }

    // === ENTITY ===

    public function getEntityCache(string $key, array $id = [], int $duration = 0): ICacheable
    {
        return new EntityCache($this, $key, $id, $duration);
    }

    // === STATISTICS ===

    public function getAllKeys(): array
    {
        return array_keys($this->inner);
    }

    public function getStats(): array
    {
        return ['count' => count($this->inner)];
    }

    public function getCacheHits(): array
    {
        return $this->cacheHits;
    }

    public function getExecutionTime(): float
    {
        return $this->time;
    }

    // === HELPER ===

    private function startCall(): float
    {
        return microtime(true);
    }

    private function endCall(float $start): void
    {
        $this->time += (microtime(true) - $start) * 1000;
    }
}
