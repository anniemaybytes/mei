<?php

declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

/**
 * Advanced PHP class for profiling
 *
 * @author   Petr Knap <dev@petrknap.cz>
 * @since    2015-12-19
 * @license  https://github.com/petrknap/php-profiler/blob/master/LICENSE MIT
 */
class AdvancedProfiler extends SimpleProfiler
{
    /**
     * @var bool
     */
    protected static bool $enabled;

    /**
     * @var Profile[]
     */
    protected static array $stack;

    /**
     * @var callable
     */
    protected static $postProcessor;

    /**
     * Set post processor
     *
     * Post processor is callable with one input argument (return from finish method)
     * and is called at the end of finish method.
     *
     * @param callable $postProcessor
     */
    public static function setPostProcessor(callable $postProcessor): void
    {
        static::$postProcessor = $postProcessor;
    }

    /**
     * @param string|null $labelOrFormat
     * @param mixed $args
     * @param mixed $opt
     *
     * @return bool
     */
    public static function start(?string $labelOrFormat = null, $args = null, $opt = null): bool
    {
        if (static::$enabled) {
            if ($labelOrFormat === null) {
                $labelOrFormat = static::getCurrentFileHashLine(1);
                $args = null;
                $opt = null;
            }

            return parent::start($labelOrFormat, $args, $opt);
        }

        return false;
    }

    /**
     * Get current "{file}#{line}"
     *
     * @return string|bool current "{file}#{line}" on success or false on failure
     */
    public static function getCurrentFileHashLine()
    {
        $args = func_get_args();

        $deep = &$args[0];

        $backtrace = debug_backtrace();
        $backtrace = &$backtrace[$deep ?: 0];

        if ($backtrace) {
            return sprintf(
                '%s#%s',
                $backtrace['file'],
                $backtrace['line']
            );
        }

        return false;
    }

    /**
     * @param string|null $labelOrFormat
     * @param null $args
     * @param mixed $opt
     *
     * @return bool|Profile
     */
    public static function finish(?string $labelOrFormat = null, $args = null, $opt = null)
    {
        if (static::$enabled) {
            if ($labelOrFormat === null) {
                $labelOrFormat = static::getCurrentFileHashLine(1);
                $args = null;
                $opt = null;
            }

            $profile = parent::finish($labelOrFormat, $args, $opt);

            if (static::$postProcessor === null) {
                return $profile;
            }

            return call_user_func(static::$postProcessor, $profile);
        }

        return false;
    }
}
