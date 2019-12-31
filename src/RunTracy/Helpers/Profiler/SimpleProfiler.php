<?php declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

use RunTracy\Helpers\Profiler\Exception\EmptyStackException;
use RunTracy\Helpers\Profiler\Exception\ProfilerException;

/**
 * Simple PHP class for profiling
 *
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2015-12-13
 * @license  https://github.com/petrknap/php-profiler/blob/master/LICENSE MIT
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

    /**
     * @var bool
     */
    protected static $enabled = false;

    /**
     * @var Profile[]
     */
    protected static $stack = [];

    /**
     * memory_get_usage
     *
     * @var bool
     */
    protected static $realUsage = false;

    /**
     * Enable profiler
     *
     * @param bool $realUsage
     */
    public static function enable(bool $realUsage = false)
    {
        static::$enabled = true;
        static::$realUsage = $realUsage ? true : false;
    }

    /**
     * Disable profiler
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * @return bool true if profiler is enabled, otherwise false
     */
    public static function isEnabled(): bool
    {
        return static::$enabled;
    }

    /**
     * @return bool true if use realUsage memory, , otherwise false
     */
    public static function isMemRealUsage(): bool
    {
        return static::$realUsage;
    }

    /**
     * Start profiling
     *
     * @param string|null $labelOrFormat
     * @param mixed $args [optional]
     *
     * @return bool true on success or false on failure
     */
    public static function start(?string $labelOrFormat = null, $args = null): bool
    {
        if (static::$enabled) {
            if ($args === null) {
                $label = $labelOrFormat;
            } else {
                $label = call_user_func_array('sprintf', func_get_args());
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

            array_push(static::$stack, $profile);

            return true;
        }

        return false;
    }

    /**
     * Finish profiling and get result
     *
     * @param string|null $labelOrFormat
     * @param mixed $args [optional]
     *
     * @return bool|Profile profile on success or false on failure
     * @throws ProfilerException
     */
    public static function finish(?string $labelOrFormat = null, $args = null)
    {
        if (static::$enabled) {
            $now = microtime(true);

            $memoryUsage = static::$realUsage ? memory_get_usage(true) : memory_get_usage();

            if (empty(static::$stack)) {
                throw new EmptyStackException('The stack is empty. Call ' . get_called_class() . '::start() first.');
            }

            if ($args === null) {
                $label = $labelOrFormat;
            } else {
                $label = call_user_func_array('sprintf', func_get_args());
            }

            /** @var Profile $profile */
            $profile = array_pop(static::$stack);
            $profile->meta[self::FINISH_LABEL] = $label;
            $profile->meta[self::FINISH_TIME] = $now;
            $profile->meta[self::FINISH_MEMORY_USAGE] = $memoryUsage;
            $profile->absoluteDuration = $profile->meta[self::FINISH_TIME] - $profile->meta[self::START_TIME];
            $profile->duration = $profile->absoluteDuration - $profile->meta[self::TIME_OFFSET];
            $profile->absoluteMemoryUsageChange = $profile->meta[self::FINISH_MEMORY_USAGE] -
                $profile->meta[self::START_MEMORY_USAGE];
            $profile->memoryUsageChange = $profile->absoluteMemoryUsageChange -
                $profile->meta[self::MEMORY_USAGE_OFFSET];

            if (!empty(static::$stack)) {
                $timeOffset = &static::$stack[count(static::$stack) - 1]->meta[self::TIME_OFFSET];
                $timeOffset = $timeOffset + $profile->absoluteDuration;

                $memoryUsageOffset = &static::$stack[count(static::$stack) - 1]->meta[self::MEMORY_USAGE_OFFSET];
                $memoryUsageOffset = $memoryUsageOffset + $profile->absoluteMemoryUsageChange;
            }

            return $profile;
        }

        return false;
    }
}
