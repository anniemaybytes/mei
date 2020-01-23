<?php declare(strict_types=1);

namespace Mei;

use Exception;
use RuntimeException;

/**
 * Class ConfigLoader
 *
 * @package Mei
 */
final class ConfigLoader
{
    /**
     * @param array $array
     * @param string $prefix
     * @param bool $deep
     *
     * @return array
     */
    private static function parseArray(array $array, string $prefix, bool $deep = false): array
    {
        $output = [];

        if ($prefix !== '') {
            $prefix .= '.';
        }

        $subOutput = [];
        foreach ($array as $k => $v) {
            if (is_array(
                    $v
                ) && !isset($v[0]) && !$deep) { // if it's a subarray, and it *looks* associative and is not deep
                $subOutput[] = self::parseArray($v, $prefix . $k, true);
            } else {
                $output[$prefix . $k] = $v;
            }
        }
        return array_merge($output + [[]], ...$subOutput);
    }

    /**
     * @param string $path
     *
     * @return array
     * @throws Exception
     */
    private static function loadFile(string $path): array
    {
        // load it as an ini
        if (!file_exists($path)) {
            throw new RuntimeException("Couldn't find config file $path");
        }
        $parsedFile = parse_ini_file($path, true, INI_SCANNER_TYPED);

        return self::parseArray($parsedFile, '');
    }

    /**
     * @param string $configPath
     *
     * @return array
     * @throws Exception
     */
    public static function load(string $configPath = 'config/'): array
    {
        if ($configPath[0] !== '/' && strpos($configPath, '://') === false) {
            $configPath = BASE_ROOT . '/' . $configPath;
        }
        return self::loadFile($configPath . 'private.ini');
    }
}
