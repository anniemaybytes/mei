<?php

declare(strict_types=1);

namespace Mei\PDO;

/**
 * Class PDOLogger
 *
 * @phpstan-type Event array{statement: string, time: float}
 * @package Mei\PDO
 */
final class PDOLogger
{
    private bool $enabled = true;
    /** @var list<Event> */
    private array $queries = [];

    private string $provider;

    public function __construct(string $provider)
    {
        $this->provider = $provider;
    }

    public function toggleCollector(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function recordEvent(string $statement, float $time): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->queries[] = [
            'statement' => $statement,
            'time' => $time * 1000
        ];
    }

    /** @return list<Event> */
    public function getEvents(): array
    {
        return $this->queries;
    }

    public function getEventsCount(): int
    {
        return count($this->queries);
    }

    public function getExecutionTime(): float
    {
        return array_sum(array_column($this->queries, 'time'));
    }

    public function getProvider(): string
    {
        return $this->provider;
    }
}
