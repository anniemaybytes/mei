<?php declare(strict_types=1);

namespace Mei\Instrumentation;

use RuntimeException;

/**
 * Class Instrumentor
 *
 * @package Mei\Instrumentation
 */
class Instrumentor
{
    /** @var bool $enabled */
    protected $enabled = true;
    /** @var array $eventLog */
    protected $eventLog = [];
    /** @var float $start */
    protected $start;
    /** @var float $end */
    protected $end;
    /** @var bool $detailedMode */
    protected $detailedMode = false;

    public function __construct()
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $this->start = (float)$_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            $this->start = $this->now();
        }
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
        $this->eventLog[intval($this->start) . '_' . ($len)] = $myData;
        return intval($this->start) . '_' . ($len); // event ID
    }

    /**
     * @param string|null $event
     * @param mixed $extraData
     */
    public function end(?string $event, $extraData = null)
    {
        if (!$this->enabled || is_null($event)) {
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
        if (is_null($func)) {
            $func = $extraData;
            $extraData = null;
        }

        $iid = $this->start($event, $extraData);
        /** @var callable $func */
        $res = $func();
        $this->end($iid);
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
        if (is_null($detailed)) {
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
        if (is_null($enabled)) {
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
