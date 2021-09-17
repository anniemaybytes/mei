<?php

declare(strict_types=1);

namespace Mei\Config;

use RuntimeException;

/**
 * Class Loader
 *
 * @package Mei\Config
 */
final class Loader
{
    public static function parse(array $array, string $prefix = '', bool $deep = false): array
    {
        $output = [];

        if ($prefix !== '') {
            $prefix .= '.';
        }

        $subOutput = [];
        foreach ($array as $k => $v) {
            if (is_array($v) && !isset($v[0]) && !$deep) {
                $subOutput[] = self::parse($v, $prefix . $k, true);
            } else {
                $output[$prefix . $k] = $v;
            }
        }

        return array_merge($output, ...$subOutput);
    }

    public static function load(string $path): array
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Couldn't find config file $path");
        }
        return parse_ini_file($path, true, INI_SCANNER_TYPED);
    }
}
