<?php declare(strict_types=1);

namespace Mei\Instrumentation;

use RuntimeException;

/**
 * Class Instrumentor
 *
 * @package Mei\Instrumentation
 */
final class Instrumentor
{
    /**
     * @var bool $enabled
     */
    protected $enabled = true;

    /**
     * @var array $eventLog
     */
    protected $eventLog = [];

    /**
     * @var float $start
     */
    protected $start;

    /**
     * @var float $end
     */
    protected $end;

    /**
     * @var bool $detailedMode
     */
    protected $detailedMode = false;

    public function __construct()
    {
        $this->start = $this->now();
    }

    /**
     * @return float
     */
    private function now(): float
    {
        return microtime(true);
    }

    /**
     * @param string $event
     * @param mixed $extraData
     *
     * @return string|null
     */
    public function start(string $event, $extraData = null): ?string
    {
        if (!$this->enabled) {
            return null;
        }
        $now = $this->now();
        $myData = [
            'name' => $event,
            'data' => [
                'start' => $extraData
            ],
            'timing' => [
                'start' => $now
            ],
        ];
        if ($this->detailedMode) {
            $myData['stacktrace'] = ['start' => $this->generateStacktrace(), 'end' => null];
        }
        $len = count($this->eventLog);
        $this->eventLog[(int)$this->start . '_' . ($len)] = $myData;
        return (int)$this->start . '_' . ($len); // event ID
    }

    /**
     * @param string|null $event
     * @param mixed $extraData
     */
    public function end(?string $event, $extraData = null): void
    {
        if (!$this->enabled || $event === null) {
            return;
        }
        if (!array_key_exists($event, $this->eventLog)) {
            throw new RuntimeException("Trying to end event that hasn't happened");
        }

        $now = $this->now();
        $log = &$this->eventLog[$event];
        $log['timing']['end'] = $now;
        $log['data']['end'] = $extraData;
        $log['timing']['period'] = $now - $log['timing']['start'];

        if ($this->detailedMode) {
            $log['stacktrace']['end'] = $this->generateStacktrace();
        }
    }

    /**
     * @param string $event
     * @param mixed $extraData
     * @param callable $func
     *
     * @return mixed
     */
    public function wrap(string $event, $extraData = null, ?callable $func = null)
    {
        // convenience, for omitting extra_data
        if ($func === null) {
            $func = $extraData;
            $extraData = null;
        }

        $iid = $this->start($event, $extraData);
        /** @var callable $func */
        $res = $func();
        $this->end($iid, $res);
        return $res;
    }

    /**
     * @return array
     */
    public function getLog(): array
    {
        if (!$this->enabled) {
            return [];
        }
        $this->end = $this->now();
        return [
            'timing' => [
                'start' => $this->start,
                'end' => $this->end
            ],
            'events' => $this->eventLog
        ];
    }

    /**
     * @param bool|null $detailed
     *
     * @return bool
     */
    public function detailedMode(?bool $detailed = null): bool
    {
        if ($detailed === null) {
            return $this->detailedMode;
        }
        $this->detailedMode = (bool)$detailed;
        return $this->detailedMode;
    }

    /**
     * @param bool|null $enabled
     *
     * @return bool
     */
    public function enabled(?bool $enabled = null): bool
    {
        if ($enabled === null) {
            return $this->enabled;
        }
        $this->enabled = (bool)$enabled;
        return $this->enabled;
    }

    /**
     * @return array
     */
    protected function generateStacktrace(): array
    {
        return debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
    }
}
