<?php

declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

/**
 * Class Profiler
 *
 * @author 1f7.wizard@gmail.com
 * @package RunTracy\Helpers\Profiler
 */
final class Profiler extends AdvancedProfiler
{
    public static function enable(bool $realUsage = false): void
    {
        ProfilerService::init();
        parent::enable($realUsage);
    }
}
