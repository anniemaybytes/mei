<?php

namespace Mei\Instrumentation;

use RuntimeException;

/**
 * Class Instrumentor
 *
 * @package Mei\Instrumentation
 */
class Instrumentor
{
    protected $enabled = true;
    protected $eventLog = [];
    protected $start;
    protected $end;
    protected $detailedMode = false;

    public function __construct()
    {
        if (isset($_SERVER['REQUEST_TIME_FLOAT'])) {
            $this->start = $_SERVER['REQUEST_TIME_FLOAT'];
        } else {
            $this->start = microtime(true);
        }
    }

    /**
     * @return float|string
     */
    private function now()
    {
        return microtime(true);
    }

    /**
     * @param $event
     * @param null $extraData
     *
     * @return string|null
     */
    public function start($event, $extraData = null)
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
     * @param $event_id
     * @param null $extraData
     */
    public function end($event_id, $extraData = null)
    {
        if (!$this->enabled) {
            return;
        }
        if (!array_key_exists($event_id, $this->eventLog)) {
            throw new RuntimeException("Trying to end event that hasn't happened");
        }

        $now = $this->now();
        $log = &$this->eventLog[$event_id];
        $log['timing']['end'] = $now;
        $log['data']['end'] = $extraData;
        $log['timing']['period'] = $now - $log['timing']['start'];

        if ($this->detailedMode) {
            $log['stacktrace']['end'] = $this->generateStacktrace();
        }
    }

    /**
     * @param $event
     * @param $extraData
     * @param null $func
     *
     * @return mixed
     */
    public function wrap($event, $extraData, $func = null)
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
    public function getLog()
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
     * @param null $detailed
     *
     * @return bool
     */
    public function detailedMode($detailed = null)
    {
        if (is_null($detailed)) {
            return $this->detailedMode;
        }
        $this->detailedMode = (bool)$detailed;
        return $this->detailedMode;
    }

    /**
     * @param null $enabled
     *
     * @return bool
     */
    public function enabled($enabled = null)
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
    protected function generateStacktrace()
    {
        return debug_backtrace();
    }
}
