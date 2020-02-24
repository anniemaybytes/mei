<?php

declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

/**
 * Class Profiler
 *
 * @package RunTracy\Helpers\Profiler
 */
final class Profiler extends AdvancedProfiler
{
    /**
     * @inheritdoc
     */
    public static function enable(bool $realUsage = false): void
    {
        ProfilerService::init();
        parent::enable($realUsage);
    }
}
