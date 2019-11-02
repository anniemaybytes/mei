<?php

namespace Mei\Utilities;

use DateTime;
use Exception;
use InvalidArgumentException;

class EntityAttributeType
{
    /**
     * Converts a value from string to a given type.
     * @param string $type The type to convert to.
     * @param string $val The string to convert.
     * @return mixed
     * @throws Exception
     */
    public static function fromString($type, $val)
    {
        switch ($type) {
            case 'bool':
            case 'int':
            case 'float':
            case 'string':
                settype($val, $type);
                break;
            case 'datetime':
            case 'date':
                $val = Time::fromSql($val);
                break;
            case 'array':
                $val = unserialize($val);
                break;
            case 'json':
                $val = json_decode($val, true);
                if (JSON_ERROR_NONE != json_last_error()) {
                    throw new InvalidArgumentException("Failed to decode value");
                }
                break;
            case 'enum-bool':
                $val = ($val == '1');
                break;
            case 'epoch':
                $val = Time::fromEpoch($val);
                break;
            default:
                throw new InvalidArgumentException("I don't know how to inflate a $type!");
        }

        return $val;
    }

    /**
     * Converts a value from a given type to a string.
     * @param string $type The type to convert from.
     * @param mixed $val The value to convert.
     * @return string
     * @throws Exception
     */
    public static function toString($type, $val)
    {
        switch ($type) {
            case 'int':
            case 'float':
            case 'string':
                break;
            case 'datetime':
            case 'date':
                if ($val instanceof DateTime) {
                    $val = Time::sql($val);
                }
                break;
            case 'array':
                $val = serialize($val);
                break;
            case 'json':
                $val = json_encode($val);
                if (JSON_ERROR_NONE != json_last_error()) {
                    throw new InvalidArgumentException("Failed to encode value");
                }
                break;
            case 'bool':
            case 'enum-bool':
                $val = $val ? '1' : '0';
                break;
            case 'epoch':
                if ($val instanceof DateTime) {
                    $val = $val->format('U');
                }
                break;
            default:
                throw new InvalidArgumentException("I don't know how to deflate a $type!");
        }
        settype($val, 'string');
        return $val;
    }
}
