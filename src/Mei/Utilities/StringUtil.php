<?php

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
    public static function generateRandomString($len = 32)
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
    public static function randomize($ok, $len)
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
    public static function base64UrlEncode($string)
    {
        return strtr(base64_encode($string), '+/', '-_');
    }

    /**
     * @param $string
     *
     * @return false|string
     */
    public static function base64UrlDecode($string)
    {
        return base64_decode(strtr($string, '-_', '+/'));
    }
}
