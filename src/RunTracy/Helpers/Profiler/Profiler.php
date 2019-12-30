<?php declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

/**
 * Class Profiler
 *
 * @package RunTracy\Helpers\Profiler
 */
class Profiler extends AdvancedProfiler
{
    /**
     * @var bool
     */
    protected static $enabled = false;

    /**
     * @var Profile[]
     */
    protected static $stack = [];

    /**
     * @var callable
     */
    protected static $postProcessor = null;

    /**
     * @inheritdoc
     */
    public static function enable(bool $realUsage = false)
    {
        ProfilerService::init();
        parent::enable($realUsage);
    }

    /**
     * @inheritdoc
     * @internal
     */
    public static function setPostProcessor(callable $postProcessor)
    {
        parent::setPostProcessor($postProcessor);
    }
}
