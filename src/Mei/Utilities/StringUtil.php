<?php declare(strict_types=1);

namespace Mei\Utilities;

use Exception;

/**
 * Class StringUtil
 *
 * @package Mei\Utilities
 */
class StringUtil
{
    /**
     * @param int $len
     *
     * @return string
     * @throws Exception
     */
    public static function generateRandomString(int $len = 32): string
    {
        $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return self::randomize($charset, $len);
    }

    /**
     * @param $ok
     * @param $len
     *
     * @return string
     * @throws Exception
     */
    public static function randomize(string $ok, int $len): string
    {
        $token = '';
        $max = mb_strlen($ok, '8bit') - 1;
        for ($i = 0; $i < $len; $i++) {
            $token .= $ok[random_int(0, $max)];
        }

        return str_shuffle($token);
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function base64UrlEncode(?string $string): string
    {
        if (is_null($string)) {
            return '';
        }
        return strtr(base64_encode($string), '+/', '-_');
    }

    /**
     * @param $string
     *
     * @return false|string
     */
    public static function base64UrlDecode($string)
    {
        if (is_null($string)) return '';
        return base64_decode(strtr($string, '-_', '+/'));
    }
}
