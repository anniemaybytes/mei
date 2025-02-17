<?php

declare(strict_types=1);

namespace RunTracy\Helpers\Profiler;

use Singleton\SingletonInterface;
use Singleton\SingletonTrait;

/**
 * Class ProfilerService
 *
 * @author Petr Knap <dev@petrknap.cz>
 * @package RunTracy\Helpers\Profiler
 */
final class ProfilerService implements SingletonInterface
{
    use SingletonTrait;

    public const string META_TIME_ZERO = 'meta_time_zero';
    public const string META_TIME_TOTAL = 'meta_time_total';
    public const string META_MEMORY_PEAK = 'meta_memory_peak';
    public const string META_TIME_LINE = 'meta_time_line';
    public const string META_TIME_LINE__MEMORY_USAGE = 'meta_time_line__memory_usage';

    public const string TIME_LINE_BEFORE = 'time_line_before'; // int [0 - 100] percentage
    public const string TIME_LINE_ACTIVE = 'time_line_active'; // int [0 - 100] percentage
    public const string TIME_LINE_INACTIVE = 'time_line_inactive'; // int [0 - 100] percentage
    public const string TIME_LINE_AFTER = 'time_line_after'; // int [0 - 100] percentage

    private array $metaData = [];

    /** @var Profile[] */
    private array $profiles = [];

    private function __construct()
    {
        Profiler::setPostProcessor([$this, 'addProfile']);
    }

    public static function init(): void
    {
        self::getInstance();
    }

    /**
     * @param Profile $profile
     *
     * @return Profile
     * @internal
     */
    public function addProfile(Profile $profile): Profile
    {
        $this->metaData = [];
        $this->profiles[] = $profile;
        return $profile;
    }

    /** @return Profile[] */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    public function iterateProfiles(callable $callback): void
    {
        $metaData = $this->getMetaData();
        foreach ($this->profiles as $profile) {
            $profile->meta[self::TIME_LINE_BEFORE] = floor(
                ($profile->meta[Profiler::START_TIME] - $metaData[self::META_TIME_ZERO])
                / $metaData[self::META_TIME_TOTAL] * 100
            );
            $profile->meta[self::TIME_LINE_ACTIVE] = floor(
                $profile->duration / $metaData[self::META_TIME_TOTAL] * 100
            );
            $profile->meta[self::TIME_LINE_INACTIVE] = floor(
                ($profile->absoluteDuration - $profile->duration) / $metaData[self::META_TIME_TOTAL] * 100
            );
            $profile->meta[self::TIME_LINE_AFTER] = 100 - $profile->meta[self::TIME_LINE_BEFORE] -
                $profile->meta[self::TIME_LINE_ACTIVE] - $profile->meta[self::TIME_LINE_INACTIVE];

            $callback($profile, $metaData);
        }
    }

    private function getMetaData(): array
    {
        if (empty($this->metaData)) {
            $this->metaData[self::META_TIME_LINE] = [];
            $this->metaData[self::META_MEMORY_PEAK] = 0;
            if (count($this->profiles) === 0) {
                $this->metaData[self::META_TIME_ZERO] = 0;
                $timeEnd = 0;
            } else {
                $this->metaData[self::META_TIME_ZERO] = $this->profiles[0]->meta[Profiler::START_TIME];
                $timeEnd = $this->profiles[0]->meta[Profiler::FINISH_TIME];
                foreach ($this->profiles as $profile) {
                    $this->metaData[self::META_TIME_ZERO] = min(
                        $this->metaData[self::META_TIME_ZERO],
                        $profile->meta[Profiler::START_TIME]
                    );
                    $timeEnd = max(
                        $timeEnd,
                        $profile->meta[Profiler::FINISH_TIME]
                    );
                    $this->metaData[self::META_MEMORY_PEAK] = max(
                        $this->metaData[self::META_MEMORY_PEAK],
                        $profile->meta[Profiler::START_MEMORY_USAGE],
                        $profile->meta[Profiler::FINISH_MEMORY_USAGE]
                    );
                    $this->metaData[self::META_TIME_LINE][(int)$profile->meta[Profiler::START_TIME] * 1000] = [
                        self::META_TIME_LINE__MEMORY_USAGE => $profile->meta[Profiler::START_MEMORY_USAGE]
                    ];
                    $this->metaData[self::META_TIME_LINE][(int)$profile->meta[Profiler::FINISH_TIME] * 1000] = [
                        self::META_TIME_LINE__MEMORY_USAGE => $profile->meta[Profiler::FINISH_MEMORY_USAGE]
                    ];
                }
            }
            $this->metaData[self::META_TIME_TOTAL] = max($timeEnd - $this->metaData[self::META_TIME_ZERO], 0.001);
            ksort($this->metaData[self::META_TIME_LINE]);
        }

        return $this->metaData;
    }

    public function iterateMemoryTimeLine(callable $callback): void
    {
        $metaData = $this->getMetaData();
        $totalTime = 0;
        $height = 0;
        foreach ($metaData[self::META_TIME_LINE] as $time => $values) {
            $time = $time / 1000 - $metaData[self::META_TIME_ZERO];
            $height = floor(
                $values[self::META_TIME_LINE__MEMORY_USAGE] / $metaData[self::META_MEMORY_PEAK] * 100
            );
            $totalTime += $time;
            $callback($time, $height, $metaData);
        }
        if ($totalTime < $metaData[self::META_TIME_TOTAL]) {
            $callback($metaData[self::META_TIME_TOTAL] - $totalTime, $height, $metaData);
        }
    }
}
