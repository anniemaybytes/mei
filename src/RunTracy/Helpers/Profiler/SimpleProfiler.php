<?php

declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

use RuntimeException;

/**
 * Simple PHP class for profiling
 *
 * @author Petr Knap <dev@petrknap.cz>
 * @package RunTracy\Helpers\Profiler
 */
class SimpleProfiler
{
    public const START_LABEL = 'start_label'; // string
    public const START_TIME = 'start_time'; // float start time in seconds
    public const START_MEMORY_USAGE = 'start_memory_usage'; // int amount of used memory at start in bytes
    public const FINISH_LABEL = 'finish_label'; // string
    public const FINISH_TIME = 'finish_time'; // float finish time in seconds
    public const FINISH_MEMORY_USAGE = 'finish_memory_usage'; // int amount of used memory at finish in bytes
    public const TIME_OFFSET = 'time_offset'; // float time offset in seconds
    public const MEMORY_USAGE_OFFSET = 'memory_usage_offset'; // int amount of memory usage offset in bytes

    protected static bool $enabled = false;

    /**
     * @var Profile[]
     */
    protected static array $stack = [];

    protected static bool $realUsage = false;

    public static function enable(bool $realUsage = false): void
    {
        static::$enabled = true;
        static::$realUsage = $realUsage ? true : false;
    }

    public static function disable(): void
    {
        static::$enabled = false;
    }

    public static function isEnabled(): bool
    {
        return static::$enabled;
    }

    public static function isMemRealUsage(): bool
    {
        return static::$realUsage;
    }

    public static function start(?string $labelOrFormat = null, mixed $args = null): bool
    {
        if (static::$enabled) {
            if ($args === null) {
                $label = $labelOrFormat;
            } else {
                $label = sprintf(...func_get_args());
            }

            $now = microtime(true);

            $memoryUsage = static::$realUsage ? memory_get_usage(true) : memory_get_usage();

            $profile = new Profile();
            $profile->meta = [
                self::START_LABEL => $label,
                self::TIME_OFFSET => 0,
                self::MEMORY_USAGE_OFFSET => 0,
                self::START_TIME => $now,
                self::START_MEMORY_USAGE => $memoryUsage
            ];

            static::$stack[] = $profile;

            return true;
        }

        return false;
    }

    public static function finish(?string $labelOrFormat = null, mixed $args = null): Profile|bool
    {
        if (static::$enabled) {
            $now = microtime(true);

            $memoryUsage = static::$realUsage ? memory_get_usage(true) : memory_get_usage();

            if (empty(static::$stack)) {
                throw new RuntimeException('The stack is empty. Call ' . static::class . '::start() first.');
            }

            if ($args === null) {
                $label = $labelOrFormat;
            } else {
                $label = sprintf(...func_get_args());
            }

            $profile = array_pop(static::$stack);
            $profile->meta[self::FINISH_LABEL] = $label;
            $profile->meta[self::FINISH_TIME] = $now;
            $profile->meta[self::FINISH_MEMORY_USAGE] = $memoryUsage;
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $profile->absoluteDuration = $profile->meta[self::FINISH_TIME] - $profile->meta[self::START_TIME];
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $profile->duration = $profile->absoluteDuration - $profile->meta[self::TIME_OFFSET];
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $profile->absoluteMemoryUsageChange = $profile->meta[self::FINISH_MEMORY_USAGE] -
                $profile->meta[self::START_MEMORY_USAGE];
            /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
            $profile->memoryUsageChange = $profile->absoluteMemoryUsageChange -
                $profile->meta[self::MEMORY_USAGE_OFFSET];

            if (!empty(static::$stack)) {
                $timeOffset = &static::$stack[count(static::$stack) - 1]->meta[self::TIME_OFFSET];
                $timeOffset += $profile->absoluteDuration;

                $memoryUsageOffset = &static::$stack[count(static::$stack) - 1]->meta[self::MEMORY_USAGE_OFFSET];
                $memoryUsageOffset += $profile->absoluteMemoryUsageChange;
            }

            return $profile;
        }

        return false;
    }
}
