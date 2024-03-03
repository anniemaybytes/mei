<?php

declare(strict_types=1);

namespace Mei\Entity;

use DateTime;
use InvalidArgumentException;
use JsonException;
use Mei\Utilities\Time;
use RuntimeException;

/**
 * Class EntityAttributeType
 *
 * @package Mei\Entity
 */
final class EntityAttributeType
{
    public static function inflate(string $type, mixed $val): mixed
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
            case 'json':
                try {
                    $val = json_decode($val, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new RuntimeException("Failed to decode value: {$e->getMessage()}", 0, $e);
                }
                break;
            case 'enum-bool':
                $val = ($val === '1' || $val === true || $val === 1);
                break;
            case 'epoch':
                $val = Time::fromEpoch($val);
                break;
            default:
                throw new InvalidArgumentException("I don't know how to inflate a $type!");
        }

        return $val;
    }

    public static function deflate(string $type, mixed $val): string
    {
        switch ($type) {
            case 'int':
            case 'float':
            case 'string':
                break;
            case 'datetime':
            case 'date':
                $val = ($val instanceof DateTime ? Time::toSql($val) : Time::ZERO_SQLTIME);
                break;
            case 'json':
                try {
                    $val = json_encode($val, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    throw new RuntimeException("Failed to encode value: {$e->getMessage()}", 0, $e);
                }
                break;
            case 'bool':
            case 'enum-bool':
                $val = $val ? '1' : '0';
                break;
            case 'epoch':
                $val = ($val instanceof DateTime ? $val->format('U') : 0);
                break;
            default:
                throw new InvalidArgumentException("I don't know how to deflate a $type!");
        }
        return (string)$val;
    }
}
