<?php

namespace Mei\Utilities;

use DateInterval;
use DateTime;
use Exception;

class Time
{
    const ZERO_SQLTIME = '0000-00-00 00:00:00';

    /**
     * Checks if a given DateTime object is non-zero
     *
     * @param DateTime $datetime
     * @return true if time not 0000-00-00 00:00:00
     * @throws Exception
     */
    public static function timeIsNonZero($datetime)
    {
        $time = self::fromSql(self::ZERO_SQLTIME);
        return $datetime != $time;
    }

    /**
     * Constructs a DateTime object from an SQL time string.
     *
     * Time string format is 'Y-m-d H:i:s'
     *
     * @param $str
     * @return DateTime
     * @throws Exception
     */
    public static function fromSql($str)
    {
        if (!$str) return self::fromSql(self::ZERO_SQLTIME);
        return new DateTime($str);
    }

    /**
     * Constructs a DateTime object from a UNIX timestamp.
     *
     * Unix timestamp format is 'U'
     *
     * @param $str
     * @return DateTime
     */
    public static function fromEpoch($str)
    {
        return DateTime::createFromFormat('U', $str);
    }

    public static function getEpoch()
    {
        return time();
    }

    /**
     * Returns the current time.
     *
     * @return DateTime
     * @throws Exception
     */
    public static function now()
    {
        return new DateTime();
    }

    /**
     * Converts the given time to an SQL time string.
     *
     * @param DateTime $t
     * @param bool $fuzzy if true returns date only, time set to 0
     * @return string
     * @throws Exception
     */
    public static function sql(DateTime $t, $fuzzy = false)
    {
        if (!self::timeIsNonZero($t)) return self::ZERO_SQLTIME;
        $format = 'Y-m-d H:i:s';
        if ($fuzzy) $format = 'Y-m-d 00:00:00';
        return $t->format($format);
    }

    /**
     * Creates an interval from the given time string. For example,
     *  interval('-1 day');
     *  interval('+1 year');
     *
     * @param $s
     * @return DateInterval
     */
    public static function interval($s)
    {
        return DateInterval::createFromDateString($s);
    }
}
