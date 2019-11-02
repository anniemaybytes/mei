<?php

namespace Mei\Utilities;

class StringUtil
{
    public static function generateRandomString($len = 32)
    {
        $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return self::randomize($charset, $len);
    }

    public static function randomize($ok, $len)
    {
        $token = '';
        $max = mb_strlen($ok, '8bit') - 1;
        for ($i = 0; $i < $len; $i++) {
            $token .= $ok[random_int(0, $max)];
        }

        return str_shuffle($token);
    }

    public static function base64UrlEncode($string)
    {
        return strtr(base64_encode($string), '+/', '-_');
    }

    public static function base64UrlDecode($string)
    {
        return base64_decode(strtr($string, '-_', '+/'));
    }
}
