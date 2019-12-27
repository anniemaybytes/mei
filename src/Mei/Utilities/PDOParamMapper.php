<?php

namespace Mei\Utilities;

use PDO;

/**
 * Class PDOParamMapper
 *
 * @package Mei\Utilities
 */
class PDOParamMapper
{
    /**
     * @param $entityAttrType
     *
     * @return int
     */
    public static function map($entityAttrType)
    {
        switch ($entityAttrType) {
            case 'int':
                return PDO::PARAM_INT;
            case 'bool':
                return PDO::PARAM_BOOL;
            default:
                return PDO::PARAM_STR;
        }
    }
}
