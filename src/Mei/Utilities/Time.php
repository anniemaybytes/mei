<?php

declare(strict_types=1);

namespace Mei\Utilities;

use DateInterval;
use DateTime;

/**
 * Class Time
 *
 * @package Mei\Utilities
 */
final class Time
{
    public const ZERO_SQLTIME = '0000-00-00 00:00:00';

    /**
     * Returns the current time.
     */
    public static function now(): DateTime
    {
        return new DateTime();
    }

    /**
     * Creates an interval from the given time string. For example,
     *  interval('-1 day');
     *  interval('+1 year');
     */
    public static function interval(string $s): DateInterval
    {
        return DateInterval::createFromDateString($s);
    }

    /**
     * Rounds given DateTime down to nearest $r seconds.
     */
    public static function round(DateTime $t, int $seconds): DateTime
    {
        return self::fromEpoch($t->getTimestamp() - ($t->getTimestamp() % $seconds));
    }

    /**
     * Checks if a given DateTime object is non-zero.
     */
    public static function timeIsNonZero(DateTime $datetime): bool
    {
        $time = self::fromSql(self::ZERO_SQLTIME);
        return !($datetime == $time);
    }

    public static function dateIsInBetween(DateTime $from, DateTime $to, DateTime $subject): bool
    {
        return ($subject->getTimestamp() >= $from->getTimestamp() && $subject->getTimestamp() <= $to->getTimestamp());
    }

    /**
     * Constructs a DateTime object from an SQL time string.
     *
     * Time string format is 'Y-m-d H:i:s'
     */
    public static function fromSql(?string $str): DateTime
    {
        if (!$str) {
            return self::fromSql(self::ZERO_SQLTIME);
        }

        if (!str_contains($str, ':')) {
            return DateTime::createFromFormat('!Y-m-d', $str);
        }

        return DateTime::createFromFormat('!Y-m-d H:i:s', $str);
    }

    /**
     * Converts the given time to an SQL time string.
     */
    public static function toSql(DateTime $t, bool $fuzzy = false): string
    {
        if (!self::timeIsNonZero($t)) {
            return self::ZERO_SQLTIME;
        }
        $format = 'Y-m-d H:i:s';
        if ($fuzzy) {
            $format = 'Y-m-d 00:00:00';
        }
        return $t->format($format);
    }

    /**
     * Constructs a DateTime object from a UNIX timestamp.
     *
     * Unix timestamp format is 'U'
     */
    public static function fromEpoch($str): DateTime
    {
        return DateTime::createFromFormat('!U', (string)(int)$str);
    }

    /**
     * Converts given DateTime to UNIX timestamp.
     */
    public static function toEpoch(DateTime $t): int
    {
        return $t->getTimestamp();
    }

    /**
     * Constructs a DateTime object from an RFC2822 time string.
     */
    public static function fromRfc2822(string $str): DateTime
    {
        return DateTime::createFromFormat('D, d M Y H:i:s O', $str);
    }

    /**
     * Converts the given time to an RFC2822 time string.
     *
     * RFC2822 format is Thu, 21 Dec 2000 16:01:07 +0000
     */
    public static function toRfc2822(DateTime $t): string
    {
        return $t->format('D, d M Y H:i:s O');
    }
}
