<?php

declare(strict_types=1);

namespace Mei\Entity;

use Error;
use Exception;
use JsonException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Tracy\Debugger;
use UnexpectedValueException;

/**
 * Class EntityHelper
 *
 * @package Mei\Entity
 */
final class EntityHelper
{
    /**
     * @throws JsonException
     */
    public static function objectToArray(object|array $d): array
    {
        return json_decode(json_encode($d, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    public static function flattenArray(array $array): array
    {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)), false);
    }

    /**
     * @return mixed - false if unserialize failed, null if $imp is known to be empty, otherwise unserialized data
     */
    public static function safelyUnserialize(?string $imp): mixed
    {
        if ($imp === null || $imp === '') {
            return null;
        }

        $e = null;
        $meta = false;

        $signalingException = new UnexpectedValueException();
        $prevUnserializeHandler = ini_set('unserialize_callback_func', '');
        $prevErrorHandler = set_error_handler(
            static function (
                int $type,
                string $msg,
                string $file,
                int $line,
                array $context = []
            ) use (
                &$prevErrorHandler,
                $signalingException
            ) {
                if (E_WARNING === $type && 'Class __PHP_Incomplete_Class has no unserializer' === $msg) {
                    throw $signalingException;
                }

                if (E_NOTICE === $type) {
                    Debugger::log($msg, Debugger::WARNING);
                }
                return $prevErrorHandler ? $prevErrorHandler($type, $msg, $file, $line, $context) : false;
            }
        );

        try {
            $meta = unserialize($imp, ['allowed_classes' => false]);
        } catch (Error | Exception $e) {
            // do nothing
            Debugger::log($e, Debugger::WARNING);
        }

        restore_error_handler();
        ini_set('unserialize_callback_func', $prevUnserializeHandler);

        if (null !== $e && $e !== $signalingException) {
            throw $e;
        }

        return $meta;
    }
}
