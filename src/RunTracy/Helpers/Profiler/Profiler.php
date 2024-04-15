<?php

declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

use RuntimeException;

/**
 * Class Profiler
 *
 * @author Petr Knap <dev@petrknap.cz>
 * @author 1f7.wizard@gmail.com
 * @package RunTracy\Helpers\Profiler
 */
final class Profiler
{
    public const string START_LABEL = 'start_label'; // string
    public const string START_TIME = 'start_time'; // float start time in seconds
    public const string START_MEMORY_USAGE = 'start_memory_usage'; // int amount of used memory at start in bytes
    public const string FINISH_LABEL = 'finish_label'; // string
    public const string FINISH_TIME = 'finish_time'; // float finish time in seconds
    public const string FINISH_MEMORY_USAGE = 'finish_memory_usage'; // int amount of used memory at finish in bytes
    public const string TIME_OFFSET = 'time_offset'; // float time offset in seconds
    public const string MEMORY_USAGE_OFFSET = 'memory_usage_offset'; // int amount of memory usage offset in bytes

    /** @var Profile[] */
    protected static array $stack = [];

    /** @var callable|null */
    protected static $postProcessor;

    protected static bool $realUsage = false;
    protected static bool $enabled = false;

    public static function enable(bool $realUsage = false): void
    {
        ProfilerService::init();

        self::$enabled = true;
        self::$realUsage = $realUsage;
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    public static function isMemRealUsage(): bool
    {
        return self::$realUsage;
    }

    public static function start(string $label): bool
    {
        if (self::$enabled) {
            $now = microtime(true);

            $memoryUsage = self::$realUsage ? memory_get_usage(true) : memory_get_usage();

            $profile = new Profile();
            $profile->meta = [
                self::START_LABEL => $label,
                self::TIME_OFFSET => 0,
                self::MEMORY_USAGE_OFFSET => 0,
                self::START_TIME => $now,
                self::START_MEMORY_USAGE => $memoryUsage
            ];

            self::$stack[] = $profile;

            return true;
        }

        return false;
    }

    public static function finish(string $label): ?Profile
    {
        if (self::$enabled) {
            $now = microtime(true);

            $memoryUsage = self::$realUsage ? memory_get_usage(true) : memory_get_usage();

            if (empty(self::$stack)) {
                throw new RuntimeException('The stack is empty. Call ' . __CLASS__ . '::start() first.');
            }

            $profile = array_pop(self::$stack);
            $profile->meta[self::FINISH_LABEL] = $label;
            $profile->meta[self::FINISH_TIME] = $now;
            $profile->meta[self::FINISH_MEMORY_USAGE] = $memoryUsage;
            $profile->absoluteDuration = $profile->meta[self::FINISH_TIME] - $profile->meta[self::START_TIME];
            $profile->duration = $profile->absoluteDuration - $profile->meta[self::TIME_OFFSET];
            $profile->absoluteMemoryUsageChange = $profile->meta[self::FINISH_MEMORY_USAGE] -
                $profile->meta[self::START_MEMORY_USAGE];
            $profile->memoryUsageChange = $profile->absoluteMemoryUsageChange -
                $profile->meta[self::MEMORY_USAGE_OFFSET];

            if (!empty(self::$stack)) {
                $timeOffset = &self::$stack[count(self::$stack) - 1]->meta[self::TIME_OFFSET];
                $timeOffset += $profile->absoluteDuration;

                $memoryUsageOffset = &self::$stack[count(self::$stack) - 1]->meta[self::MEMORY_USAGE_OFFSET];
                $memoryUsageOffset += $profile->absoluteMemoryUsageChange;
            }

            if (self::$postProcessor) {
                call_user_func(self::$postProcessor, $profile);
            }

            return $profile;
        }

        return null;
    }

    public static function setPostProcessor(callable $postProcessor): void
    {
        self::$postProcessor = $postProcessor;
    }
}
