<?php

declare(strict_types=1);

namespace Mei\Utilities;

use RandomLib\Factory;
use SecurityLib\Strength;

/**
 * Class StringUtil
 *
 * @package Mei\Utilities
 */
final class StringUtil
{
    public static function generateRandomString(int $len = 32): string
    {
        $factory = new Factory();
        $generator = $factory->getGenerator(new Strength(Strength::MEDIUM));
        return $generator->generateString($len, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
    }

    public static function base64UrlEncode(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return strtr(base64_encode($string), '+/', '-_');
    }

    public static function base64UrlDecode(string $string): string
    {
        return base64_decode(strtr($string, '-_', '+/'));
    }
}
