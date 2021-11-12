<?php

declare(strict_types=1);

namespace Mei\Cache;

use Mei\Entity\ICacheable;

/**
 * Simple key store interface
 *
 * @package Mei\Cache
 */
interface IKeyStore
{
    // === CACHE ===

    /** Return value stored for key, or false if not existent */
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $time = 10800): bool;

    public function delete(string $key): bool;

    public function increment(string $key, int $n = 1, int $initial = 1, int $expiry = 0): bool|int;

    public function touch(string $key, int $expiry = 10800): bool;

    public function flush(): void;

    public function setClearOnGet(bool $val): void;

    // === ENTITY ===

    public function getEntityCache(
        string $key,
        array $id = [],
        int $duration = 10800
    ): ICacheable;

    // === STATISTICS ===

    public function getAllKeys(): array;

    public function getStats(): array;

    public function getCacheHits(): array;

    public function getExecutionTime(): float;
}
